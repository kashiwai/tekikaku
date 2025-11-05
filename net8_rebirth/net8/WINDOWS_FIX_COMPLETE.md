# ✅ PeerJS接続問題 - 修正完了

## 📅 修正日時
2025-10-25

---

## ✅ 修正完了

### 修正ファイル
`server_v2/js/cameraServer_slotv2.js`

### 修正内容
Line 926に `path: '/peerjs'` を追加しました。

**修正前:**
```javascript
var peer = new Peer(
    cameraid, {
    host: sigHost,
    port: sigPort,
    secure: true,
    key:peerjskey,
    ...
});
```

**修正後:**
```javascript
var peer = new Peer(
    cameraid, {
    host: sigHost,
    port: sigPort,
    path: '/peerjs',  // PeerJSサーバーのパス（必須）
    secure: true,
    key:peerjskey,
    ...
});
```

---

## 🔧 Windows側で実施すべき手順

### Step 1: ブラウザキャッシュを完全クリア

1. **Ctrl+Shift+Delete** を押す
2. 「キャッシュされた画像とファイル」にチェック
3. 期間：「全期間」を選択
4. 「データを削除」をクリック

### Step 2: ブラウザを完全に再起動

1. Chrome を完全に閉じる
2. タスクマネージャーで Chrome プロセスが残っていないか確認
3. Chrome を再起動

### Step 3: ページにアクセス

```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

### Step 4: カメラアクセス許可

カメラアクセス許可ダイアログが表示されたら「許可」をクリック

### Step 5: F12コンソールで接続確認

以下のコードを実行して接続状態を確認：

```javascript
console.log('=== 接続確認 ===');
console.log('peer.id:', _peer ? _peer.id : 'not set');
console.log('peer.open:', _peer ? _peer.open : 'N/A');
console.log('peer._socket:', _peer && _peer._socket ? 'EXISTS ✅' : 'NULL ❌');
console.log('peer.options.path:', _peer ? _peer.options.path : 'N/A');

if (_peer && _peer._socket) {
    console.log('WebSocket URL:', _peer._socket.url);
    console.log('WebSocket State:', _peer._socket.readyState, '(1=OPEN)');
}

console.log('cameraid:', cameraid);
```

---

## ✅ 成功の基準

以下が全て表示されれば成功：

```
peer.id: camera_5_XXXXXXXX
peer.open: true
peer._socket: EXISTS ✅
peer.options.path: /peerjs
WebSocket URL: wss://aimoderation.ngrok-free.app/peerjs?key=peerjs&id=camera_5_XXXXXXXX&token=...
WebSocket State: 1 (1=OPEN)
cameraid: camera_5_XXXXXXXX
```

---

## 📞 Mac側でテスト

Windows側の接続が成功したら、Mac側でテストを実施します。

### Mac側の手順

1. **test_simple.htmlを開く**
   ```
   https://aicrypto.ngrok.dev/play_v2/test_simple.html
   ```

2. **Windows側のカメラIDを確認**
   - Windows側のConsoleで `cameraid` の値を確認
   - 例: `camera_5_1761394822`

3. **カメラIDを入力**
   - test_simple.htmlの入力フィールドに貼り付け
   - 「Start Connection」をクリック

4. **映像表示を確認**
   - ビデオエリアにWindows側のカメラ映像が表示される

---

## 🎯 期待される結果

### Windows側（カメラ配信側）

```
✅ PeerJS connection opened
✅ WebSocket connected (readyState: 1)
✅ Camera stream started
✅ Peer ID: camera_5_XXXXXXXX
```

### Mac側（視聴側）

```
✅ PeerJS connection opened
✅ Calling camera: camera_5_XXXXXXXX
✅ Received remote stream!
✅ Video playing
```

---

## ❌ トラブルシューティング

### それでも接続できない場合

#### 1. シークレットモードで試す
- **Ctrl+Shift+N** でシークレットウィンドウを開く
- https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73 にアクセス

#### 2. 別のブラウザで試す
- Microsoft Edge
- Firefox

#### 3. slotserver.exe を再起動
```powershell
# Ctrl+C で停止
cd C:\serverset
.\slotserver.exe -c COM4
```

#### 4. Windows Firewall確認
- WebSocket通信がブロックされていないか確認

---

## 📊 修正の影響範囲

この修正により、以下が改善されます：

1. **WebSocket接続が確立される**
   - `_peer._socket` が `null` から `exists` に
   - 正しいURLに接続: `wss://aimoderation.ngrok-free.app/peerjs?...`

2. **Mac側から接続可能になる**
   - カメラIDを使ってpeer.call()が成功
   - ビデオストリームが受信可能

3. **安定した配信が可能になる**
   - WebSocketが確立されているため、切断時の再接続も正常動作

---

## 📝 結果報告

Windows側でテスト完了後、以下の情報を報告してください：

```
【修正後の結果】

1. ブラウザキャッシュクリア: [完了/未完了]
2. ページリロード: [完了/未完了]
3. peer._socket: [EXISTS ✅ / NULL ❌]
4. peer.options.path: [/peerjs / その他]
5. WebSocket接続: [成功/失敗]
6. cameraid: [camera_5_XXXXXXXX]

【Consoleログ（最新20行）】
[ここにConsoleの内容をコピペ]

【Mac側テスト】
- test_simple.html: [テスト実施/未実施]
- 映像受信: [成功/失敗]
```

---

## 🎉 まとめ

**問題**: PeerJS WebSocket接続が確立できない（`_peer._socket: null`）

**原因**: cameraServer_slotv2.js で `path: '/peerjs'` パラメータが欠落

**解決**: Line 926 に `path: '/peerjs'` を追加

**次のステップ**: Windows側でブラウザキャッシュクリア→リロード→接続確認

---

**修正完了日:** 2025-10-25
**修正者:** Mac側 Claude Code
**次のアクション:** Windows側でキャッシュクリアとテスト実施
