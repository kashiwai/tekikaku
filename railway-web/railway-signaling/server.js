const { PeerServer } = require('peer');
const express = require('express');
const cors = require('cors');

const app = express();
const port = process.env.PORT || 8080;

// CORS設定
app.use(cors());

// ヘルスチェック用エンドポイント
app.get('/', (req, res) => {
  res.json({
    status: 'ok',
    service: 'PeerJS Signaling Server',
    timestamp: new Date().toISOString()
  });
});

app.get('/health', (req, res) => {
  res.json({ status: 'healthy' });
});

// PeerServerを起動
const peerServer = PeerServer({
  port: port,
  path: '/',
  allow_discovery: true,
  proxied: true  // Railway の reverse proxy 対応
});

peerServer.on('connection', (client) => {
  console.log(`[${new Date().toISOString()}] Client connected: ${client.getId()}`);
});

peerServer.on('disconnect', (client) => {
  console.log(`[${new Date().toISOString()}] Client disconnected: ${client.getId()}`);
});

console.log(`[${new Date().toISOString()}] PeerJS server started on port ${port}`);
console.log(`[${new Date().toISOString()}] Path: /`);
console.log(`[${new Date().toISOString()}] Ready for connections`);
