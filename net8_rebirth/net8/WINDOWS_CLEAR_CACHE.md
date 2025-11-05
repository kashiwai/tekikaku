# Windows側 - ブラウザキャッシュクリアと再接続

Mac側のPeerJSサーバーは正常に動作しています（認証バイパス成功）。
Windows側のブラウザが古いキャッシュを使用しているため、更新が必要です。

## 実行手順

### 方法1: ブラウザキャッシュを完全クリア（推奨）

1. **Chromeでキャッシュクリア**
   - **Ctrl+Shift+Delete** を押す
   - 「キャッシュされた画像とファイル」にチェック
   - 期間：「全期間」を選択
   - 「データを削除」をクリック

2. **ブラウザを再起動**
   - Chromeを完全に閉じる（タスクマネージャーでもプロセスが残っていないか確認）
   - Chromeを再起動

3. **ページに再アクセス**
   ```
   https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
   ```

4. **カメラ許可**
   - カメラアクセス許可ダイアログが表示されたら「許可」をクリック

### 方法2: シークレットモードで試す

1. **Ctrl+Shift+N** でシークレットウィンドウを開く

2. 以下のURLにアクセス：
   ```
   https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
   ```

3. カメラ許可を与える

### 方法3: slotserver.exeを再起動

現在のslotserver.exeを停止して再起動：

```powershell
# Ctrl+C で停止

# 再起動
cd C:\serverset
.\slotserver.exe -c COM4
```

## 確認ポイント

F12開発者ツールのConsoleで以下を確認：

### ✅ 成功の兆候：

1. **PeerJS接続成功**:
```
PeerJS: Socket open
```

2. **認証エラーが消える**:
```
❌ これが出ないこと: PeerJS: ERROR Error: faild auth
```

3. **peer_idが生成される**:
```javascript
console.log('peer_id:', peer_id);
// 何か値が表示される（nullやundefinedでない）
```

4. **カメラ配信開始**:
```
📹 Stream assigned to video element
```

## トラブルシューティング

### それでもエラーが出る場合

**別のブラウザで試す：**
- Microsoft Edge
- Firefox
- 新しいChromeプロファイル

**時刻を確認：**
- PeerJSログのタイムスタンプ（22:28）以降にアクセスしているか確認

## Mac側ログの確認

認証バイパスは正常に動作しています：
```
✅ 20:06以降: auth:bypassed (成功)
❌ 20:04以前: auth:fail (失敗 - 古いコード)
```

最新のPeerJSサーバー（20:06以降）に接続すれば、認証エラーは出ません。

## 結果報告

再接続後、以下を報告してください：

```
【キャッシュクリア後の結果】

1. 認証エラー: [消えた/まだ出る]
2. peer_id: [生成された/nullのまま]
3. PeerJS接続: [成功/失敗]
4. カメラ配信: [開始した/開始しない]

【Consoleログ（最新20行）】
[ここにConsoleの内容をコピペ]
```
