# Windows側 - 認証バイパス後の接続テスト

Mac側でPeerJSサーバーの認証を一時的に無効化しました。
これでPeerJS接続が成功するはずです。

## 実行手順

### 1. Chromeでページを強制リロード

**Ctrl+Shift+R** （またはCtrl+F5）で強制リロード：
```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

### 2. F12開発者ツールで確認

Consoleタブで以下を確認してください：

#### ✅ 成功の兆候:

1. **PeerJS接続成功**:
```
PeerJS: Socket open
PeerJS: Server connection established
```

2. **認証エラーが消える**:
以下のエラーが **出なくなる** はず：
```
❌ PeerJS: ERROR Error: faild auth  ← これが消えるはず
```

3. **カメラ映像が表示される**:
ページ上部のvideoエリアにカメラ映像が表示される

4. **ビデオサイズが正常**:
Consoleで確認：
```javascript
const video = document.getElementById('video') || document.getElementById('localVideo');
console.log(`Video size: ${video.videoWidth} x ${video.videoHeight}`);
```

期待される結果: `Video size: 640 x 480` （または実際の解像度）

### 3. 結果報告

以下の情報をMac側に報告してください：

**報告テンプレート：**
```
【認証バイパステスト結果】

1. PeerJS接続: [成功/失敗]
2. 認証エラー: [消えた/まだ出る]
3. カメラ映像表示: [見える/見えない]
4. ビデオサイズ: [○ x ○]

【Consoleログ】
[F12のConsoleタブの最新20行をコピペ]
```

## 期待される動作

✅ **認証バイパスが成功している場合:**
- PeerJS Socket が open する
- "faild auth" エラーが出ない
- PeerJS接続が確立される
- カメラ映像がvideoエレメントに表示される
- video.videoWidth と video.videoHeight が 0 以外になる

❌ **まだ問題がある場合:**
- 別のエラーメッセージが表示される
- Consoleログ全文をMac側に報告してください

## 次のステップ

PeerJS接続が成功したら、次はMac側のブラウザから視聴テストを行います。
