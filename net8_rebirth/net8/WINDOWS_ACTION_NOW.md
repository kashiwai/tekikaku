# ⚡ Windows側 - 今すぐ実行してください

## 📅 緊急修正完了
2025-10-25

---

## ✅ Mac側で修正完了

**問題**: URLパスが `/peerjs/peerjs/` と重複 → 404エラー

**修正**: `path: '/'` に変更（3ファイル修正済み）

---

## 🔥 Windows側で今すぐ実行

### Step 1: キャッシュクリア（必須）

1. **Ctrl+Shift+Delete** を押す
2. 「キャッシュされた画像とファイル」にチェック
3. 期間：「全期間」
4. 「データを削除」

### Step 2: ブラウザ再起動

Chrome を完全に閉じて再起動

### Step 3: ページアクセス

```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

カメラ許可をクリック

### Step 4: 接続確認（F12 Console）

以下をコピペして実行：

```javascript
console.log('=== 接続確認 ===');
console.log('peer.id:', _peer ? _peer.id : 'not set');
console.log('peer._socket:', _peer && _peer._socket ? 'EXISTS ✅' : 'NULL ❌');
console.log('peer.options.path:', _peer ? _peer.options.path : 'N/A');

if (_peer && _peer._socket) {
    console.log('WebSocket URL:', _peer._socket.url);
    console.log('WebSocket State:', _peer._socket.readyState, '(1=OPEN)');
}

console.log('cameraid:', typeof cameraid !== 'undefined' ? cameraid : 'not set');
```

---

## ✅ 成功の確認

以下が表示されればOK：

```
peer._socket: EXISTS ✅
peer.options.path: /
WebSocket State: 1 (1=OPEN)
WebSocket URL: wss://aimoderation.ngrok-free.app/peerjs?key=...
                                                    ^^^^^^
                                                    1回だけ！
```

---

## 📊 結果報告

成功/失敗に関わらず、Consoleの内容を全てコピーして報告してください。

**特に重要**:
- `peer._socket:` の値
- `WebSocket URL:` の内容
- エラーメッセージ（あれば）

---

**Mac側: 修正完了・ログ監視中・待機中**
