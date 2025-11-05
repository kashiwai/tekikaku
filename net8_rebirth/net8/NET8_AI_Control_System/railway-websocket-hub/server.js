/**
 * NET8 WebSocket Hub - Railway中継サーバー
 *
 * 本社Mac ↔ このサーバー ↔ 各Windows PC
 * 全ての通信を中継します
 */

import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import cors from 'cors';

const app = express();
app.use(cors());
app.use(express.json());

const server = createServer(app);
const io = new Server(server, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST']
  },
  transports: ['websocket', 'polling']
});

// 接続中のクライアント管理
const clients = new Map();

// 統計情報
let stats = {
  totalConnections: 0,
  centralConnections: 0,
  agentConnections: 0,
  messagesRelayed: 0,
  startTime: new Date()
};

// ヘルスチェック
app.get('/', (req, res) => {
  res.json({
    status: 'running',
    service: 'NET8 WebSocket Hub',
    version: '1.0.0',
    stats: {
      ...stats,
      uptime: Math.floor((Date.now() - stats.startTime) / 1000),
      connectedClients: clients.size,
      centralConnected: Array.from(clients.values()).filter(c => c.type === 'central').length,
      agentsConnected: Array.from(clients.values()).filter(c => c.type === 'agent').length
    }
  });
});

// 接続されているエージェント一覧
app.get('/api/agents', (req, res) => {
  const agents = Array.from(clients.values())
    .filter(client => client.type === 'agent')
    .map(client => ({
      machineNo: client.machineNo,
      machineName: client.machineName,
      connectedAt: client.connectedAt,
      lastSeen: client.lastSeen
    }));

  res.json({ agents });
});

// WebSocket接続処理
io.on('connection', (socket) => {
  console.log(`[${new Date().toISOString()}] New connection: ${socket.id}`);
  stats.totalConnections++;

  // クライアント登録
  socket.on('register', (data) => {
    const { type, machineNo, machineName, token } = data;

    // 簡易認証（本番では強化が必要）
    // if (token !== process.env.AUTH_TOKEN) {
    //   socket.disconnect();
    //   return;
    // }

    clients.set(socket.id, {
      socket,
      type,  // 'central' or 'agent'
      machineNo,
      machineName,
      connectedAt: new Date(),
      lastSeen: new Date()
    });

    if (type === 'central') {
      stats.centralConnections++;
      console.log(`[${new Date().toISOString()}] ✅ Central registered`);
    } else if (type === 'agent') {
      stats.agentConnections++;
      console.log(`[${new Date().toISOString()}] ✅ Agent registered: MACHINE-${machineNo} (${machineName})`);
    }

    // 接続確認を返す
    socket.emit('registered', {
      success: true,
      clientId: socket.id,
      type,
      machineNo
    });

    // 中央サーバーに新しいエージェント接続を通知
    if (type === 'agent') {
      broadcastToCentral('agent_connected', {
        machineNo,
        machineName,
        timestamp: new Date().toISOString()
      });
    }
  });

  // 本社 → Windows PC への指示転送
  socket.on('send_to_machine', (data) => {
    const { machineNo, instruction, requestId } = data;

    console.log(`[${new Date().toISOString()}] 📤 Central → MACHINE-${machineNo}: ${instruction}`);

    // 該当マシンを探す
    let found = false;
    for (const [id, client] of clients.entries()) {
      if (client.type === 'agent' && client.machineNo === String(machineNo)) {
        client.socket.emit('instruction', {
          instruction,
          requestId,
          timestamp: new Date().toISOString()
        });
        client.lastSeen = new Date();
        found = true;
        stats.messagesRelayed++;
        console.log(`[${new Date().toISOString()}] ✅ Message relayed to MACHINE-${machineNo}`);
        break;
      }
    }

    if (!found) {
      socket.emit('error', {
        requestId,
        error: `MACHINE-${machineNo} is not connected`
      });
      console.log(`[${new Date().toISOString()}] ❌ MACHINE-${machineNo} not found`);
    }
  });

  // Windows PC → 本社 への結果転送
  socket.on('result', (data) => {
    const { machineNo, requestId, success, output, error } = data;

    console.log(`[${new Date().toISOString()}] 📥 MACHINE-${machineNo} → Central: ${success ? 'Success' : 'Error'}`);

    // 中央サーバーに転送
    broadcastToCentral('execution_result', {
      machineNo,
      requestId,
      success,
      output,
      error,
      timestamp: new Date().toISOString()
    });

    stats.messagesRelayed++;
  });

  // ハートビート
  socket.on('heartbeat', (data) => {
    const client = clients.get(socket.id);
    if (client) {
      client.lastSeen = new Date();
      socket.emit('heartbeat_ack', { timestamp: new Date().toISOString() });
    }
  });

  // ログ送信（Windows PC → 中央）
  socket.on('log', (data) => {
    const { machineNo, level, message } = data;
    broadcastToCentral('agent_log', {
      machineNo,
      level,
      message,
      timestamp: new Date().toISOString()
    });
  });

  // 切断処理
  socket.on('disconnect', () => {
    const client = clients.get(socket.id);
    if (client) {
      console.log(`[${new Date().toISOString()}] ❌ Disconnected: ${client.type} ${client.machineNo || ''}`);

      if (client.type === 'agent') {
        broadcastToCentral('agent_disconnected', {
          machineNo: client.machineNo,
          machineName: client.machineName,
          timestamp: new Date().toISOString()
        });
      }

      clients.delete(socket.id);
    }
  });
});

// 中央サーバーにブロードキャスト
function broadcastToCentral(event, data) {
  for (const [id, client] of clients.entries()) {
    if (client.type === 'central') {
      client.socket.emit(event, data);
    }
  }
}

// 定期的な切断確認（60秒以上応答がないクライアントを切断）
setInterval(() => {
  const now = Date.now();
  for (const [id, client] of clients.entries()) {
    if (now - client.lastSeen > 60000) {
      console.log(`[${new Date().toISOString()}] ⚠️ Timeout: ${client.type} ${client.machineNo || ''}`);
      client.socket.disconnect();
      clients.delete(id);
    }
  }
}, 30000);

// サーバー起動
const PORT = process.env.PORT || 3001;
server.listen(PORT, '0.0.0.0', () => {
  console.log('');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('🚀 NET8 WebSocket Hub Server');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log(`📡 Server running on port ${PORT}`);
  console.log(`🌐 Health check: http://localhost:${PORT}/`);
  console.log(`🔌 WebSocket endpoint: ws://localhost:${PORT}`);
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('');
});
