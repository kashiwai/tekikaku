# 🚀 Windows PC - Railway環境 緊急セットアップ手順

**最終更新**: 2025-10-30
**対象**: Windows PC（Railway本番環境接続）
**所要時間**: 5分

---

## 🎯 前提条件

- [ ] Windows PCにslotserver.exeがインストール済み（C:\serverset\）
- [ ] インターネット接続が正常
- [ ] Railway環境が稼働中（Mac側で確認済み ✅）

---

## 📋 緊急セットアップ（5ステップ）

### ステップ1: 設定ファイルをWindows PCに転送

**Mac側で実行（このファイルと同じディレクトリ）:**

```bash
# slotserver_railway.ini が作成されています
ls -la slotserver_railway.ini
```

**Windows PCに転送する方法（いずれか）:**

#### 方法A: USBメモリ経由
```bash
# Mac側
cp slotserver_railway.ini /Volumes/USB/

# Windows側（管理者権限のコマンドプロンプト）
copy E:\slotserver_railway.ini C:\serverset\slotserver.ini
```

#### 方法B: ネットワーク共有経由
```bash
# Mac側で共有フォルダに配置
cp slotserver_railway.ini ~/Shared/

# Windows側でコピー
copy \\MacPC\Shared\slotserver_railway.ini C:\serverset\slotserver.ini
```

#### 方法C: 手動作成（推奨：確実）

**Windows PC上で以下を実行:**

1. メモ帳を管理者権限で起動
2. 以下の内容を貼り付け：

```ini
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c
domain = mgg-webservice-production.up.railway.app

[PatchServer]
filesurl =
url =

[API]
url = https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=

[Chrome]
url = https://mgg-webservice-production.up.railway.app/server_v2/

[Monitor]
url = wss://mgg-webservice-production.up.railway.app/ws

[Credit]
playmin = 3
```

3. 名前を付けて保存: `C:\serverset\slotserver.ini`
4. 文字コード: **ANSI** または **UTF-8（BOMなし）**

---

### ステップ2: ネットワーク接続テスト

**Windows PCのPowerShellで実行:**

```powershell
# Railway Webサービス疎通確認
Invoke-WebRequest -Uri "https://mgg-webservice-production.up.railway.app/" -UseBasicParsing

# 期待される結果: StatusCode 200

# PeerJSサーバー疎通確認
Invoke-WebRequest -Uri "https://mgg-signaling-production-c1bd.up.railway.app/" -UseBasicParsing

# 期待される結果: "PeerJS Server" の文字列が含まれる
```

**エラーが出た場合:**
- インターネット接続を確認
- ファイアウォール設定を確認
- プロキシ設定を確認

---

### ステップ3: API接続テスト

**Windows PCのブラウザまたはPowerShellで実行:**

```powershell
# MAC address 00:00:00:00:00:01 でテスト
$mac = "00:00:00:00:00:01"
$id = "IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
$url = "https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=$mac&ID=$id"

Invoke-WebRequest -Uri $url -UseBasicParsing | Select-Object -ExpandProperty Content
```

**期待される結果:**
```json
{"status":"ok","machine_no":1,"category":2,...}
```

**エラーが出た場合:**
```json
{"status":"error","message":"..."}
```
→ Mac側でRailway DBを確認する必要があります

---

### ステップ4: slotserver.exe 起動テスト

**Windows PCで実行（管理者権限のコマンドプロンプト）:**

```batch
cd C:\serverset

REM 設定ファイル確認
type slotserver.ini

REM slotserver.exe 起動
slotserver.exe
```

**期待される動作:**
```
🚀 Slotserver 起動中...
📝 設定ファイル読み込み: slotserver.ini
✅ License認証成功
🌐 API接続中: https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php...
✅ API接続成功
📊 マシン番号: 1
📊 カテゴリ: 2（スロット）
🔌 シリアルポート: COM3
⏳ 待機中...
```

**エラーが出た場合:**

#### エラー1: 「設定ファイルが見つかりません」
```batch
# slotserver.iniが存在するか確認
dir C:\serverset\slotserver.ini

# 存在しない場合、ステップ1をやり直し
```

#### エラー2: 「License認証失敗」
```
原因: INIファイルの[License]セクションが不正

解決策:
1. slotserver.iniの内容を確認
2. id と cd の値が正しいか確認
3. 文字化けしていないか確認（文字コードANSIまたはUTF-8）
```

#### エラー3: 「API接続失敗」
```
原因: Railway環境への接続が失敗

解決策:
1. ステップ2のネットワーク接続テストを実施
2. ステップ3のAPI接続テストを実施
3. Mac側でRailway環境が稼働しているか確認
```

#### エラー4: 「シリアルポートが開けません」
```
原因: RS-232Cケーブルが接続されていない、またはドライバ未インストール

解決策（テスト環境の場合は無視してOK）:
1. デバイスマネージャーでCOMポートを確認
2. RS-232Cドライバをインストール
3. 実機がなくても、slotserver.exeは起動します
```

---

### ステップ5: カメラ配信ページ確認

#### 5-1: Windows PC側でChrome起動

**ブラウザで以下のURLにアクセス:**

```
https://mgg-webservice-production.up.railway.app/server_v2/?MAC=00:00:00:00:00:01
```

**期待される動作:**
1. ✅ ページが正常に読み込まれる
2. ✅ カメラアクセス許可ダイアログが表示される
3. ✅ カメラのプレビュー映像が表示される

**デベロッパーツール確認（F12）:**
```javascript
// Consoleタブで以下のログを確認:
// ✅ PeerJS: Connected to server
// ✅ PeerJS: Listening on peer: camera-XXX-XXX
// ✅ MediaStream: Camera started
```

#### 5-2: Mac側で視聴確認

**Mac側のブラウザでアクセス:**

```
https://mgg-webservice-production.up.railway.app/play_v2/?NO=1
```

**期待される動作:**
- ✅ Windows PCのカメラ映像が表示される
- ✅ 映像が滑らかに再生される
- ✅ 遅延が許容範囲内（<2秒）

---

## 🔧 トラブルシューティング

### 問題1: slotserver.exeがすぐに終了する

**診断:**
```batch
cd C:\serverset

REM ログファイル確認（もしあれば）
type slotserver.log

REM 設定ファイル確認
type slotserver.ini | findstr "url"
```

**対処法:**
- INIファイルのURL設定を確認
- `mgg-webservice-production.up.railway.app` が正しく設定されているか

---

### 問題2: カメラ配信ページが開かない

**診断:**
```powershell
# URLにアクセスできるか確認
Invoke-WebRequest -Uri "https://mgg-webservice-production.up.railway.app/server_v2/" -UseBasicParsing
```

**対処法:**
1. Railway環境が稼働しているか確認（Mac側）
2. DNSキャッシュクリア: `ipconfig /flushdns`
3. ブラウザキャッシュクリア

---

### 問題3: PeerJS接続エラー

**症状:**
Chromeコンソールに「PeerJS connection failed」

**診断:**
```powershell
# PeerJSサーバー確認
curl https://mgg-signaling-production-c1bd.up.railway.app/
```

**対処法:**
1. Mac側でPeerJSサーバーが稼働しているか確認
2. ファイアウォール設定を確認（HTTPS/443ポート許可）
3. ブラウザのWebSocket設定を確認

---

### 問題4: カメラが認識されない

**診断:**
```powershell
# カメラデバイス確認
Get-PnpDevice | Where-Object { $_.FriendlyName -like "*camera*" -or $_.FriendlyName -like "*webcam*" }
```

**対処法:**
1. Windowsのプライバシー設定でカメラアクセスを許可
2. カメラドライバを再インストール
3. 他のアプリがカメラを使用していないか確認

---

## ✅ セットアップ完了チェックリスト

- [ ] ステップ1: slotserver.iniをC:\serverset\に配置完了
- [ ] ステップ2: ネットワーク接続テスト成功（200 OK）
- [ ] ステップ3: API接続テスト成功（JSON応答あり）
- [ ] ステップ4: slotserver.exe起動成功（エラーなし）
- [ ] ステップ5: カメラ配信ページ正常表示
- [ ] Mac側で映像視聴確認完了

---

## 📞 次のステップ

### すべて成功した場合:

✅ **Windows PCとRailway環境の連携完了！**

次は自動化設定:
1. タスクスケジューラで自動起動設定
2. 自動シャットダウン設定
3. 運用テスト

### エラーが残っている場合:

❌ **以下の情報をMac側Claudeに報告:**

```
1. 失敗したステップ番号
2. エラーメッセージの全文
3. 実行したコマンドと結果
4. slotserver.iniの内容（type C:\serverset\slotserver.ini）
```

---

## 🔗 重要なURL（メモ）

### Railway環境
```
PHPアプリ: https://mgg-webservice-production.up.railway.app/
PeerJS: https://mgg-signaling-production-c1bd.up.railway.app/
DB: meticulous-vitality-production-f216.up.railway.app:3306
```

### カメラ配信（Windows PC側）
```
配信ページ: https://mgg-webservice-production.up.railway.app/server_v2/?MAC=00:00:00:00:00:01
```

### 視聴（Mac側）
```
視聴ページ: https://mgg-webservice-production.up.railway.app/play_v2/?NO=1
```

---

**作成日**: 2025-10-30
**対象**: Windows PC（Railway本番環境接続）
**緊急度**: 🔥 高
