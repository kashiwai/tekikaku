/**
 * NET8 Windows PC Agent
 *
 * 軽量エージェント（10MB以下）
 * Railway WebSocket Hubに接続して本社からの指示を受信・実行
 */

import { io } from 'socket.io-client';
import { exec } from 'child_process';
import { promisify } from 'util';
import dotenv from 'dotenv';

dotenv.config();

const execAsync = promisify(exec);

// 設定
const MACHINE_NO = process.env.MACHINE_NO || '1';
const MACHINE_NAME = process.env.MACHINE_NAME || `MACHINE-${MACHINE_NO}`;
const HUB_URL = process.env.HUB_URL || 'ws://localhost:3001';
const AUTH_TOKEN = process.env.AUTH_TOKEN || '';

console.log('');
console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
console.log('🤖 NET8 Windows PC Agent');
console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
console.log(`📍 Machine: ${MACHINE_NAME} (No.${MACHINE_NO})`);
console.log(`🔌 Hub: ${HUB_URL}`);
console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
console.log('');

// WebSocket接続
const socket = io(HUB_URL, {
  transports: ['websocket', 'polling'],
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionAttempts: Infinity
});

socket.on('connect', () => {
  console.log(`[${timestamp()}] ✅ Connected to WebSocket Hub`);
  socket.emit('register', {
    type: 'agent',
    machineNo: MACHINE_NO,
    machineName: MACHINE_NAME,
    token: AUTH_TOKEN
  });
});

socket.on('disconnect', () => {
  console.log(`[${timestamp()}] ❌ Disconnected from WebSocket Hub`);
});

socket.on('registered', (data) => {
  console.log(`[${timestamp()}] ✅ Registered successfully:`, data);
});

// 指示受信
socket.on('instruction', async (data) => {
  const { instruction, requestId } = data;
  console.log(`[${timestamp()}] 📝 Instruction received: ${instruction}`);

  try {
    // PowerShell実行
    const { stdout, stderr } = await execAsync(
      `powershell -Command "${instruction}"`,
      { timeout: 30000 }  // 30秒タイムアウト
    );

    const output = stdout.trim();

    console.log(`[${timestamp()}] ✅ Execution completed`);
    if (output) {
      console.log(`Output: ${output.substring(0, 200)}${output.length > 200 ? '...' : ''}`);
    }

    // 結果を返す
    socket.emit('result', {
      machineNo: MACHINE_NO,
      requestId,
      success: true,
      output: output || '実行完了',
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    console.error(`[${timestamp()}] ❌ Execution failed:`, error.message);

    socket.emit('result', {
      machineNo: MACHINE_NO,
      requestId,
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    });
  }
});

// ハートビート（30秒ごと）
setInterval(() => {
  if (socket.connected) {
    socket.emit('heartbeat', {
      machineNo: MACHINE_NO,
      timestamp: new Date().toISOString()
    });
  }
}, 30000);

socket.on('heartbeat_ack', () => {
  // console.log(`[${timestamp()}] 💓 Heartbeat acknowledged`);
});

// エラーハンドリング
socket.on('error', (error) => {
  console.error(`[${timestamp()}] ⚠️ Socket error:`, error.message);
});

socket.on('connect_error', (error) => {
  console.error(`[${timestamp()}] ⚠️ Connection error:`, error.message);
});

// タイムスタンプヘルパー
function timestamp() {
  return new Date().toLocaleTimeString('ja-JP');
}

// プロセス終了時の処理
process.on('SIGINT', () => {
  console.log('');
  console.log(`[${timestamp()}] 🛑 Shutting down agent...`);
  socket.disconnect();
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log(`[${timestamp()}] 🛑 Shutting down agent...`);
  socket.disconnect();
  process.exit(0);
});

console.log(`[${timestamp()}] 🚀 Agent started and waiting for instructions...`);
