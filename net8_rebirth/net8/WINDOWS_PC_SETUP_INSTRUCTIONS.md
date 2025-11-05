# Windows PC セットアップ指示書（Claude Code用）

## 🎯 目的

Windows PC上で、NET8カメラ配信システムのクライアント側設定を行います。

---

## 📋 前提条件

- Windows 10/11
- Claude Codeがインストール済み
- `C:\serverset\` ディレクトリが存在
- 以下のプログラムが配置済み：
  - `slotserver.exe`
  - `chromeCameraV2.exe`
  - その他必要なEXEファイル

---

## 🌐 サーバー接続情報

**Mac側ngrok URL:**
- Webサーバー: `https://aicrypto.ngrok.dev`
- PeerJSサーバー: `https://aimoderation.ngrok-free.app`

**テストカメラ情報:**
- MAC Address: `00:00:00:00:00:01`
- License ID: `IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=`
- CD: `6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c`

---

## 📝 タスク1: 設定ファイルの作成

### ファイル名
`C:\serverset\slotserver_ngrok.ini`

### ファイル内容

```ini
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c
domain = aicrypto.ngrok.dev

[PatchServer]
# 開発環境では無効化
filesurl =
url =

[API]
# Mac側サーバーのAPI（ngrok経由）
url = https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=

[Chrome]
# Mac側カメラ配信ページ（ngrok経由）
url = https://aicrypto.ngrok.dev/server_v2/

[Monitor]
# WebSocketマシンコントロール（将来実装）
url = wss://aicrypto.ngrok.dev/ws

[Credit]
playmin = 3
```

### 作成手順

1. Claude Codeで以下のコマンドを実行：
   ```
   以下の内容で C:\serverset\slotserver_ngrok.ini を作成してください：
   ```
   （上記のファイル内容を貼り付け）

2. ファイルが作成されたことを確認

---

## 📝 タスク2: 起動用バッチファイルの作成

### ファイル名
`C:\serverset\start_ngrok.bat`

### ファイル内容

```batch
@echo off
echo ================================================
echo NET8 Camera Client - ngrok Connection
echo ================================================
echo.
echo Server: https://aicrypto.ngrok.dev
echo PeerJS: https://aimoderation.ngrok-free.app
echo.
echo Press any key to start...
pause

REM slotserver_ngrok.iniを使用してslotserver.exeを起動
copy /Y slotserver_ngrok.ini slotserver.ini

echo.
echo Starting slotserver.exe...
slotserver.exe
```

### 作成手順

Claude Codeで上記のバッチファイルを作成してください。

---

## 📝 タスク3: 接続テスト

### 3-1: API疎通確認

PowerShellまたはCMDで以下を実行：

```powershell
# API疎通テスト
curl "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
```

**期待されるレスポンス:**
```json
{
  "status":"ok",
  "machine_no":1,
  "category":1,
  "leavetime":180,
  "renchan_games":0,
  "tenjo_games":9999,
  "version":"1",
  "max":10,
  "max_rate":1,
  "navel":3
}
```

### 3-2: カメラ配信ページ確認

ブラウザで以下のURLにアクセス：

```
https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01
```

**確認項目:**
- ✅ ページが正常に表示される
- ✅ カメラアクセス許可ダイアログが表示される
- ✅ ブラウザコンソールにエラーがない

### 3-3: PeerJSサーバー確認

```powershell
curl https://aimoderation.ngrok-free.app/peerjs/id
```

**期待される結果:**
ランダムなPeer ID（例: `abc123xyz456`）が返ってくる

---

## 📝 タスク4: プログラムの起動

### 起動手順

1. `C:\serverset\start_ngrok.bat` をダブルクリック
2. コンソールログを確認：
   - "Starting slotserver.exe..." と表示される
   - slotserver.exeが起動する
   - APIに接続成功
   - カメラ番号を取得
   - Chromeが自動起動
   - カメラ配信ページが開く

### 確認項目

- ✅ slotserver.exeのコンソールに「接続成功」メッセージ
- ✅ Chromeが自動的に開く
- ✅ カメラアクセスの許可ダイアログが表示
- ✅ カメラ映像のプレビューが表示
- ✅ PeerJSサーバーへの接続成功メッセージ

---

## 📝 タスク5: 視聴側でテスト（Mac側）

Mac側のブラウザで以下のURLにアクセス：

```
https://aicrypto.ngrok.dev/play_v2/?NO=1
```

**期待される動作:**
- Windows PCのカメラ映像が表示される
- 映像が滑らかに再生される
- 遅延が許容範囲内（<2秒）

---

## 🔧 トラブルシューティング

### 問題1: API接続エラー

**症状:** curlでAPIにアクセスできない

**対処法:**
1. ngrok URLが正しいか確認
2. Mac側のngrokトンネルが起動しているか確認
3. ファイアウォールがブロックしていないか確認

### 問題2: PeerJS接続エラー

**症状:** カメラ配信ページで "PeerJS connection failed"

**対処法:**
1. `https://aimoderation.ngrok-free.app/peerjs/id` にアクセスできるか確認
2. ブラウザコンソールのエラーメッセージを確認
3. Mac側のPeerJSシグナリングサーバーが起動しているか確認

### 問題3: カメラが起動しない

**症状:** Chrome起動後、カメラが認識されない

**対処法:**
1. Chromeの設定 → プライバシーとセキュリティ → カメラ
2. カメラの許可を確認
3. ページをリロード

### 問題4: slotserver.exeが起動しない

**症状:** バッチファイル実行後、すぐに終了する

**対処法:**
1. `slotserver_ngrok.ini` が正しく作成されているか確認
2. 必要なEXEファイルが全て揃っているか確認
3. コンソールのエラーメッセージを確認

---

## 📊 接続フロー図

```
[Windows PC]
    │
    ├─ slotserver.exe起動
    │   └→ API呼び出し
    │       └→ https://aicrypto.ngrok.dev/api/...
    │            └→ カメラ番号取得: 1
    │
    ├─ Chrome自動起動
    │   └→ https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01
    │       │
    │       ├─ カメラ映像キャプチャ開始
    │       │
    │       └─ PeerJS接続
    │            └→ https://aimoderation.ngrok-free.app
    │                 └→ Peer ID生成
    │
    └─ WebRTC P2P接続確立
         └→ Mac側視聴者と直接接続
              └→ 映像ストリーミング開始
```

---

## ✅ 完了チェックリスト

- [ ] `slotserver_ngrok.ini` 作成完了
- [ ] `start_ngrok.bat` 作成完了
- [ ] API疎通確認成功
- [ ] カメラ配信ページアクセス成功
- [ ] PeerJSサーバー疎通確認成功
- [ ] slotserver.exe起動成功
- [ ] Chrome自動起動確認
- [ ] カメラ映像配信開始確認
- [ ] Mac側で視聴成功

---

## 📞 サポート情報

問題が発生した場合は、以下の情報を収集してください：

1. **エラーメッセージ:**
   - slotserver.exeのコンソール出力
   - Chromeのコンソールログ（F12で開く）

2. **環境情報:**
   - Windows バージョン
   - Chrome バージョン
   - カメラデバイス名

3. **ネットワーク情報:**
   - ngrok URLへの疎通確認結果
   - ファイアウォール設定

---

## 🎉 次のステップ

テストが成功したら：

1. **本番用MACアドレスの登録**
   - 実際のカメラPCのMACアドレスをMac側で登録

2. **複数カメラの設定**
   - カメラ2, 3, ... の設定を追加

3. **安定性テスト**
   - 長時間接続テスト
   - 再接続テスト

---

**作成日:** 2025-10-22
**バージョン:** 1.0
**対象システム:** NET8 WebRTC Camera System
**ngrok設定:** aicrypto.ngrok.dev / aimoderation.ngrok-free.app
