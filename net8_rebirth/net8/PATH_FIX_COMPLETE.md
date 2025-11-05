# ✅ PeerJSパス重複問題 - 修正完了

## 📅 修正日時
2025-10-25

---

## 🔴 問題内容

### エラーログ
```
POST https://aimoderation.ngrok-free.app/peerjs/peerjs/camera_5_1761396897/...
                                         ^^^^^^^^^^^^^^
                                         パスが2回重複！

404 (Not Found)
peer disconnected
```

### 原因

1. **PeerJSサーバー側**（server.js Line 35）:
   ```javascript
   var path = path + (path[path.length - 1] != "/" ? "/" : "") + "peerjs";
   ```
   - 自動的に `/peerjs` を追加

2. **クライアント側**:
   - `path: '/peerjs'` を指定すると、PeerJSライブラリが更に `/peerjs` を追加
   - 結果: `/peerjs` + `/peerjs` = `/peerjs/peerjs/` → 404エラー

---

## ✅ 修正内容

### 修正したファイル

1. **server_v2/js/cameraServer_slotv2.js** (Line 926)
2. **play_v2/test_simple.html** (Line 155)
3. **play_v2/test_view.php** (Line 168)

### 修正内容

**修正前**:
```javascript
var peer = new Peer(id, {
    host: sigHost,
    port: sigPort,
    path: '/peerjs',  // ❌ これが重複の原因
    secure: true,
    ...
});
```

**修正後**:
```javascript
var peer = new Peer(id, {
    host: sigHost,
    port: sigPort,
    path: '/',  // ✅ サーバー側が自動的に/peerjsを追加
    secure: true,
    ...
});
```

---

## 🧪 Windows側で実施すべきテスト手順

### Step 1: ブラウザキャッシュを完全クリア（重要！）

1. **Ctrl+Shift+Delete** を押す
2. 「キャッシュされた画像とファイル」にチェック
3. 期間：「全期間」を選択
4. 「データを削除」をクリック

### Step 2: ブラウザを完全に再起動

1. Chrome を完全に閉じる
2. タスクマネージャーで Chrome プロセスが残っていないか確認
3. Chrome を再起動

### Step 3: ページに再アクセス

```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

### Step 4: カメラアクセス許可

カメラアクセス許可ダイアログが表示されたら「許可」をクリック

### Step 5: F12コンソールで接続確認

以下のコードをコピー＆ペーストして実行：

```javascript
console.log('=== 接続確認 ===');
console.log('peer.id:', _peer ? _peer.id : 'not set');
console.log('peer.open:', _peer ? _peer.open : 'N/A');
console.log('peer._socket:', _peer && _peer._socket ? 'EXISTS ✅' : 'NULL ❌');
console.log('peer.options.path:', _peer ? _peer.options.path : 'N/A');

if (_peer && _peer._socket) {
    console.log('WebSocket URL:', _peer._socket.url);
    console.log('WebSocket State:', _peer._socket.readyState, '(1=OPEN)');
    console.log('Ready State: 0=CONNECTING, 1=OPEN, 2=CLOSING, 3=CLOSED');
}

console.log('cameraid:', typeof cameraid !== 'undefined' ? cameraid : 'not set');
```

---

## ✅ 成功の基準

以下が全て表示されれば成功：

```
=== 接続確認 ===
peer.id: camera_5_XXXXXXXX
peer.open: true
peer._socket: EXISTS ✅
peer.options.path: /
WebSocket URL: wss://aimoderation.ngrok-free.app/peerjs?key=peerjs&id=camera_5_XXXXXXXX&token=...
WebSocket State: 1 (1=OPEN)
cameraid: camera_5_XXXXXXXX
```

**重要ポイント**:
- ✅ `peer._socket: EXISTS`
- ✅ `peer.options.path: /` （`/peerjs` ではない）
- ✅ `WebSocket URL` に `/peerjs` が1回だけ含まれる
- ✅ `WebSocket State: 1` (OPEN状態)

---

## 📞 Mac側でカメラ映像受信テスト

Windows側の接続が成功したら、Mac側でテストを実施します。

### Mac側の手順

1. **test_simple.htmlを開く**
   ```
   https://aicrypto.ngrok.dev/play_v2/test_simple.html
   ```

2. **Windows側のカメラIDを確認**
   - Windows側のConsoleで確認した `cameraid` の値をコピー
   - 例: `camera_5_1761396897`

3. **カメラIDを入力して接続**
   - test_simple.htmlの入力フィールドに貼り付け
   - 「Start Connection」ボタンをクリック

4. **映像表示を確認**
   - ビデオエリアにWindows側のカメラ映像が表示される

---

## 🎯 期待される結果

### Windows側（カメラ配信側）

```
✅ PeerJS connection opened
✅ WebSocket connected: wss://aimoderation.ngrok-free.app/peerjs?...
✅ WebSocket State: 1 (OPEN)
✅ Camera stream started
```

### Mac側（視聴側）

```
✅ PeerJS connection opened
✅ Calling camera: camera_5_XXXXXXXX
✅ Received remote stream!
✅ Video tracks: 1
✅ Video dimensions: 1920x1080
```

---

## ❌ トラブルシューティング

### それでもエラーが出る場合

#### 1. 404エラーが続く場合
- **原因**: ブラウザキャッシュが残っている
- **対策**: シークレットモードで試す（Ctrl+Shift+N）

#### 2. "peer disconnected" が表示される場合
- **原因**: WebSocket接続が確立されていない
- **対策**:
  1. slotserver.exe を再起動
  2. Windows Firewall設定を確認

#### 3. 別のブラウザで試す
- Microsoft Edge
- Firefox

---

## 📊 修正の技術的詳細

### PeerJSサーバー側の動作（server.js）

```javascript
// Line 34-35
var path = this.mountpath;  // "/"
var path = path + (path[path.length - 1] != "/" ? "/" : "") + "peerjs";
// 結果: "/" + "peerjs" = "/peerjs"

// Line 38
this._wss = new WebSocketServer({ path: path, server: server });
// WebSocketは "/peerjs" でリッスン
```

### クライアント側の設定

```javascript
// 正しい設定
path: '/'

// PeerJSライブラリが自動的に以下のURLを構築:
// wss://aimoderation.ngrok-free.app/ + peerjs + ?key=...
// = wss://aimoderation.ngrok-free.app/peerjs?key=...
```

---

## 📝 結果報告

Windows側でテスト完了後、以下の情報を報告してください：

```
【修正後の結果】

1. ブラウザキャッシュクリア: [完了/未完了]
2. ページリロード: [完了/未完了]
3. peer._socket: [EXISTS ✅ / NULL ❌]
4. peer.options.path: [/ / その他]
5. WebSocket URL: [コピペ]
6. WebSocket State: [0/1/2/3]
7. cameraid: [camera_5_XXXXXXXX]

【エラーログ】
- 404エラー: [なし/あり]
- peer disconnected: [なし/あり]
- その他のエラー: [なし/内容]

【Mac側テスト】
- test_simple.html: [テスト実施/未実施]
- 映像受信: [成功/失敗]
```

---

## 🎉 まとめ

**問題**: URLパスが `/peerjs/peerjs/` と重複して404エラー

**原因**:
- サーバー側: `/peerjs` を自動追加
- クライアント側: `path: '/peerjs'` を指定 → 重複

**解決**: クライアント側を `path: '/'` に変更

**影響**: 全てのPeerJS接続（カメラ側・視聴側）

**優先度**: 最高 - WebRTC配信の根幹に関わる問題

---

**修正完了日:** 2025-10-25
**修正ファイル数:** 3ファイル
**次のアクション:** Windows側でキャッシュクリア→リロード→接続テスト
