# Windows側 - PHP修正後のリロード

Mac側でPHP側のAPIエラーを修正しました。
ページを再読み込みしてください。

## 実行手順

### 1. Chromeでページを強制リロード

**Ctrl+Shift+R** （またはCtrl+F5）で強制リロード：
```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

### 2. エラーメッセージの確認

以前表示されていたエラーが消えているか確認：
```
❌ 以前のエラー（消えるはず）:
?M=add&oneTimeAuthID=d9803d9b0b78ba08de20ccad01839bf72aa200a7 [_geturl error]
```

### 3. F12開発者ツールで確認

Consoleタブで以下を確認：

#### ✅ 期待される動作:

1. **カメラストリーム取得成功**:
```
Got stream with constraints: {video: {...}, audio: {...}}
Using video device: Full HD webcam
```

2. **PeerJS接続成功**:
```
PeerJS: Socket open
PeerJS: Server connection established
```

3. **認証エラーなし**:
```
❌ これが出ないこと: PeerJS: ERROR Error: faild auth
```

4. **カメラ映像表示**:
ページ上部のvideoエリアにカメラ映像が表示される

### 4. ビデオサイズ確認

Consoleで以下を実行：
```javascript
const video = document.getElementById('video') || document.getElementById('localVideo');
console.log(`Video size: ${video.videoWidth} x ${video.videoHeight}`);
console.log(`srcObject:`, video.srcObject);
```

✅ **正常な結果**:
```
Video size: 640 x 480
srcObject: MediaStream {id: "...", active: true, ...}
```

### 5. 結果報告

**報告テンプレート：**
```
【PHP修正後のテスト結果】

1. APIエラー: [消えた/まだ出る]
2. PeerJS接続: [成功/失敗]
3. カメラ映像: [見える/見えない]
4. ビデオサイズ: [○ x ○]

【Consoleログ（最新20行）】
[ここにConsoleの内容をコピペ]
```

## トラブルシューティング

### まだエラーが出る場合

1. **ブラウザのキャッシュをクリア**:
   - Ctrl+Shift+Delete
   - 「キャッシュされた画像とファイル」をクリア

2. **slotserver.exeを再起動**:
   ```powershell
   # Ctrl+C で停止
   cd C:\serverset
   .\slotserver.exe -c COM4
   ```

3. **別のブラウザで試す**（Edgeなど）

## 成功の判断基準

以下がすべて✅なら成功：
- [ ] APIエラーが画面に表示されない
- [ ] PeerJS Socket が open する
- [ ] "faild auth" エラーが出ない
- [ ] カメラ映像がブラウザに表示される
- [ ] video.videoWidth と video.videoHeight が 0 以外

成功したら、Mac側のブラウザから視聴テストに進みます。
