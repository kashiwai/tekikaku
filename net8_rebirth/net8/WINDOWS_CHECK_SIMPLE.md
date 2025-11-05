# Windows側 - 簡易接続確認

## F12 Consoleで以下を1つずつコピペして実行

### 1. カメラIDの確認
```javascript
console.log('cameraid:', cameraid);
```

### 2. Peerオブジェクトの存在確認
```javascript
console.log('peer exists:', typeof _peer);
```

### 3. Peer IDの確認
```javascript
console.log('peer.id:', _peer.id);
```

### 4. Peer接続状態の確認
```javascript
console.log('peer.open:', _peer.open);
```

### 5. WebSocketの確認
```javascript
console.log('peer._socket:', _peer._socket);
```

### 6. Pathの確認
```javascript
console.log('peer.options.path:', _peer.options.path);
```

### 7. WebSocket URLの確認（_socketが存在する場合）
```javascript
if (_peer._socket) { console.log('WebSocket URL:', _peer._socket.url); }
```

### 8. WebSocket状態の確認（_socketが存在する場合）
```javascript
if (_peer._socket) { console.log('WebSocket State:', _peer._socket.readyState); }
```

---

## 結果を全て報告してください

各行の実行結果をそのままコピーして送ってください。
