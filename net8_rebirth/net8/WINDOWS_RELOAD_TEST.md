# Windows側 - ページリロードとPeerJS接続確認

Mac側でsignaling_idの設定を修正しました（"4" → "1"）。
これでPeerJSのWebSocket接続エラーが解決されるはずです。

## 実行手順

### 1. Chromeでページをリロード

以下のURLをChromeでリロード（Ctrl+F5で強制リロード）：

```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

### 2. F12開発者ツールで確認

Chromeで **F12キー** を押して開発者ツールを開き、**Console**タブを確認してください。

#### 確認ポイント1: WebSocketエラーが消えたか

❌ **以前のエラー**（これが出なくなっているはず）:
```
SyntaxError: Failed to construct 'WebSocket': The URL 'wss://:/peerjs?...' is invalid.
```

✅ **期待される表示**（正常な接続）:
```
🔍 Requesting camera access...
✅ Camera stream obtained
📹 Stream assigned to video element
▶️ Video playback started
```

#### 確認ポイント2: PeerJS接続ログ

Consoleに以下のようなPeerJS接続メッセージが表示されるか確認：
```
PeerJS: Connected to server
PeerJS: ID: camera_...
```

#### 確認ポイント3: ビデオ要素のサイズ

Consoleで以下のコマンドを実行してビデオのサイズを確認：
```javascript
const video = document.getElementById('video');
console.log(`Video size: ${video.videoWidth} x ${video.videoHeight}`);
```

✅ **期待される結果**: `Video size: 640 x 480` （または実際のカメラ解像度）
❌ **NGな結果**: `Video size: 0 x 0`

### 3. カメラ映像の確認

ページ上部のビデオエリアにカメラ映像が表示されているか目視確認してください。

### 4. 結果報告

以下の情報をMac側に報告してください：

**報告テンプレート：**
```
【リロード結果】
1. WebSocketエラー: [出た/出なくなった]
2. PeerJS接続: [成功/失敗]
3. ビデオサイズ: [○ x ○]
4. カメラ映像表示: [見える/見えない]

【Consoleログ】
[F12のConsoleタブの内容を全文コピー]
```

## トラブルシューティング

### エラーが続く場合

1. slotserver.exeを一度停止して再起動：
   ```powershell
   # Ctrl+C で停止
   # 再起動
   cd C:\serverset
   .\slotserver.exe -c COM4
   ```

2. Chromeのキャッシュを完全クリア：
   - F12 → Network タブ → "Disable cache" にチェック
   - Ctrl+Shift+Delete → キャッシュクリア

3. 別のブラウザで試す（Edgeなど）

## 成功の判断基準

以下がすべて✅なら成功：
- [ ] WebSocketエラーが出ない
- [ ] PeerJS接続成功メッセージが表示される
- [ ] video.videoWidth と video.videoHeight が 0 以外
- [ ] カメラ映像がブラウザに表示される

成功したら、次はMac側のブラウザから視聴テストに進みます。
