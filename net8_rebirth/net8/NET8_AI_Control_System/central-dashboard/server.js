/**
 * NET8 Central Dashboard Server
 *
 * 本社Macで実行するローカルサーバー
 * - Webダッシュボード提供
 * - Railway WebSocket Hubに接続
 * - Claude APIでAI指示解釈
 */

import express from 'express';
import { io as ioClient } from 'socket.io-client';
import Anthropic from '@anthropic-ai/sdk';
import dotenv from 'dotenv';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const app = express();
app.use(express.json());
app.use(express.static(join(__dirname, 'public')));

// Railway WebSocket Hubに接続
const HUB_URL = process.env.HUB_URL || 'ws://localhost:3001';
const socket = ioClient(HUB_URL, {
  transports: ['websocket'],
  reconnection: true,
  reconnectionDelay: 1000
});

// Claude API
const anthropic = new Anthropic({
  apiKey: process.env.CLAUDE_API_KEY,
});

// 接続中のマシン管理
const machines = new Map();

// WebSocket Hub接続
socket.on('connect', () => {
  console.log('✅ Connected to WebSocket Hub');
  socket.emit('register', { type: 'central' });
});

socket.on('disconnect', () => {
  console.log('❌ Disconnected from WebSocket Hub');
});

socket.on('registered', (data) => {
  console.log('✅ Registered as central:', data);
});

// エージェント接続通知
socket.on('agent_connected', (data) => {
  const { machineNo, machineName, timestamp } = data;
  machines.set(machineNo, {
    machineNo,
    machineName,
    status: 'online',
    connectedAt: timestamp,
    lastSeen: timestamp
  });
  console.log(`✅ Agent connected: MACHINE-${machineNo}`);
});

// エージェント切断通知
socket.on('agent_disconnected', (data) => {
  const { machineNo } = data;
  const machine = machines.get(machineNo);
  if (machine) {
    machine.status = 'offline';
  }
  console.log(`❌ Agent disconnected: MACHINE-${machineNo}`);
});

// 実行結果受信
socket.on('execution_result', (data) => {
  console.log('📥 Execution result:', data);
});

// API: マシン一覧取得
app.get('/api/machines', (req, res) => {
  res.json({
    machines: Array.from(machines.values())
  });
});

// API: AI指示送信
app.post('/api/send-instruction', async (req, res) => {
  const { machineNo, instruction } = req.body;

  if (!machineNo || !instruction) {
    return res.status(400).json({ error: 'machineNo and instruction are required' });
  }

  const requestId = `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

  try {
    // Claude APIで指示を解釈してPowerShellコマンドに変換
    const message = await anthropic.messages.create({
      model: 'claude-3-5-sonnet-20241022',
      max_tokens: 1024,
      messages: [
        {
          role: 'user',
          content: `
あなたはWindows PCのシステム管理者です。以下のユーザー指示をPowerShellコマンドに変換してください。

【ユーザー指示】
${instruction}

【利用可能なコマンド】
1. カメラ再起動: taskkill /F /IM chrome.exe; taskkill /F /IM slotserver.exe; Start-Sleep 3; Start-Process "C:\\serverset\\slotserver.exe"; Start-Sleep 5; Start-Process "C:\\serverset\\camera.bat"
2. 状態確認: Get-Process -Name chrome,slotserver -ErrorAction SilentlyContinue | Select-Object Name,Id,CPU,StartTime | ConvertTo-Json
3. システム情報: Get-CimInstance Win32_ComputerSystem,Win32_OperatingSystem | ConvertTo-Json
4. slotserver再起動: taskkill /F /IM slotserver.exe; Start-Sleep 3; Start-Process "C:\\serverset\\slotserver.exe"
5. PC再起動: shutdown /r /t 60

実行すべきPowerShellコマンドのみを返してください。説明は不要です。
          `.trim()
        }
      ]
    });

    const command = message.content[0].text.trim();

    console.log(`📤 Sending instruction to MACHINE-${machineNo}:`, command);

    // WebSocket Hubを経由してWindows PCに送信
    socket.emit('send_to_machine', {
      machineNo,
      instruction: command,
      requestId
    });

    res.json({
      success: true,
      requestId,
      message: `指示をMACHINE-${machineNo}に送信しました`,
      command
    });
  } catch (error) {
    console.error('Error:', error);
    res.status(500).json({ error: error.message });
  }
});

// WebSocket Server for Dashboard
import { createServer } from 'http';
import { Server } from 'socket.io';

const httpServer = createServer(app);
const dashboardIO = new Server(httpServer);

// ダッシュボードクライアントへのリアルタイム通知
dashboardIO.on('connection', (dashboardSocket) => {
  console.log('Dashboard client connected');

  // 現在のマシン一覧を送信
  dashboardSocket.emit('machines_update', {
    machines: Array.from(machines.values())
  });
});

// Hubからのイベントをダッシュボードに転送
socket.on('agent_connected', (data) => {
  dashboardIO.emit('agent_connected', data);
});

socket.on('agent_disconnected', (data) => {
  dashboardIO.emit('agent_disconnected', data);
});

socket.on('execution_result', (data) => {
  dashboardIO.emit('execution_result', data);
});

// サーバー起動
const PORT = process.env.PORT || 3000;
httpServer.listen(PORT, () => {
  console.log('');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('🎯 NET8 Central Dashboard');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log(`📊 Dashboard: http://localhost:${PORT}`);
  console.log(`🔌 WebSocket Hub: ${HUB_URL}`);
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('');
});
