# Windows Claude Code - PeerJS接続デバッグ指示

## 目的
Windows側のPeerJS WebSocket接続を確立し、Mac側からカメラ映像を受信できるようにする。

## 現在の問題
- `peer.id` は生成されている
- `peer.open: true` になっている
- しかし `peer._socket: null` でWebSocket接続が確立していない
- 結果、Mac側から接続できない

## タスク1: PeerJSログのリアルタイム監視

PowerShellで以下を実行：

```powershell
# 新しいPowerShellウィンドウを開いて実行
# このウィンドウは開いたままにする
```

**重要：このウィンドウは閉じずに、画面の横に配置してログを監視し続けてください。**

## タスク2: ChromeでPeerJSイベント監視コードを実行

### 2-1. Chromeを開く

以下のURLにアクセス：
```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

### 2-2. F12を開いてConsoleタブを表示

### 2-3. 以下のコードをコピー＆ペーストして実行

```javascript
if (_peer) {
    console.log('=== PeerJS Event Monitor Started ===');

    _peer.on('open', function(id) {
        console.log('EVENT: OPEN - ID:', id);
        console.log('Socket state:', _peer._socket ? _peer._socket.readyState : 'null');
        if (!_peer._socket) {
            console.error('PROBLEM: Socket is null even after OPEN event!');
        } else {
            console.log('SUCCESS: Socket exists with state:', _peer._socket.readyState);
        }
    });

    _peer.on('connection', function(conn) {
        console.log('EVENT: CONNECTION from', conn.peer);
    });

    _peer.on('call', function(call) {
        console.log('EVENT: CALL from', call.peer);
    });

    _peer.on('close', function() {
        console.log('EVENT: CLOSE');
    });

    _peer.on('disconnected', function() {
        console.log('EVENT: DISCONNECTED');
    });

    _peer.on('error', function(err) {
        console.error('EVENT: ERROR -', err.type, ':', err.message);
    });

    console.log('Event listeners registered.');
    console.log('Current state:');
    console.log('  peer.id:', _peer.id);
    console.log('  peer.open:', _peer.open);
    console.log('  peer._socket:', _peer._socket ? 'exists (state: ' + _peer._socket.readyState + ')' : 'NULL');
}
```

### 2-4. 現在の状態を確認

上記のコードを実行した直後のConsole出力を全てコピーして、Mac側に報告してください。

## タスク3: ページをリロードして接続テスト

### 3-1. Ctrl+Shift+R で強制リロード

### 3-2. リロード後3秒待つ

### 3-3. Consoleに何が表示されたか確認

特に以下を確認：
- `EVENT: OPEN` が表示されたか？
- `EVENT: ERROR` が表示されたか？
- `Socket state:` の値は何か？

### 3-4. 現在の状態を再確認

Consoleで以下を実行：
```javascript
console.log('=== Current Status ===');
console.log('peer.id:', _peer ? _peer.id : 'not set');
console.log('peer.open:', _peer ? _peer.open : 'N/A');
console.log('peer._socket:', _peer && _peer._socket ? _peer._socket.readyState : 'NULL');
console.log('cameraid:', cameraid);
```

## タスク4: WebSocket接続の詳細確認

Consoleで以下を実行：
```javascript
if (_peer && _peer._socket) {
    console.log('=== WebSocket Details ===');
    console.log('URL:', _peer._socket.url);
    console.log('Protocol:', _peer._socket.protocol);
    console.log('ReadyState:', _peer._socket.readyState);
    console.log('  0 = CONNECTING');
    console.log('  1 = OPEN');
    console.log('  2 = CLOSING');
    console.log('  3 = CLOSED');
} else {
    console.error('PROBLEM: WebSocket (_peer._socket) does not exist!');
    console.log('This means PeerJS failed to establish WebSocket connection.');
    console.log('Checking PeerJS configuration...');
    console.log('sigHost:', typeof sigHost !== 'undefined' ? sigHost : 'not defined');
    console.log('sigPort:', typeof sigPort !== 'undefined' ? sigPort : 'not defined');
    console.log('peerjskey:', typeof peerjskey !== 'undefined' ? peerjskey : 'not defined');
}
```

## タスク5: 手動でWebSocket接続を試みる（診断用）

Consoleで以下を実行：
```javascript
// WebSocketに直接接続できるかテスト
var testWS = new WebSocket('wss://aimoderation.ngrok-free.app/peerjs?key=peerjs&id=test_' + Date.now() + '&token=test123');

testWS.onopen = function() {
    console.log('TEST: Direct WebSocket connection SUCCESS');
    testWS.close();
};

testWS.onerror = function(err) {
    console.error('TEST: Direct WebSocket connection FAILED', err);
};

testWS.onclose = function() {
    console.log('TEST: WebSocket closed');
};
```

## 報告内容

以下の情報を全てMac側に報告してください：

### 1. タスク2-4の実行結果
```
=== Current Status ===
peer.id: (ここに値)
peer.open: (ここに値)
peer._socket: (ここに値)
cameraid: (ここに値)
```

### 2. タスク3のリロード後の結果
```
(Consoleに表示された全てのログ)
```

### 3. タスク4の実行結果
```
(WebSocket詳細または問題メッセージ)
```

### 4. タスク5の実行結果
```
TEST: Direct WebSocket connection (SUCCESS or FAILED)
```

### 5. 追加情報

以下も確認して報告：
```javascript
console.log('Browser:', navigator.userAgent);
console.log('Location:', window.location.href);
```

## トラブルシューティング

### もし "TEST: Direct WebSocket connection FAILED" が表示された場合

これはngrok経由のWebSocket接続に問題があることを意味します。以下を試してください：

1. **slotserver.exeを再起動**
   ```powershell
   # Ctrl+C で停止
   cd C:\serverset
   .\slotserver.exe -c COM4
   ```

2. **ブラウザを変更**
   - Microsoft Edgeで試す
   - シークレットモードで試す

3. **Windows Firewallを確認**
   - WebSocket通信がブロックされていないか

---

## 成功の基準

以下が全て✅なら成功：
- [ ] `EVENT: OPEN` が表示される
- [ ] `peer._socket` が `NULL` ではない
- [ ] `peer._socket.readyState` が `1` (OPEN)
- [ ] `TEST: Direct WebSocket connection SUCCESS` が表示される

成功したら、最新の `cameraid` の値をMac側に報告してください。

---

**全てのタスクを順番に実行して、結果を報告してください。**
