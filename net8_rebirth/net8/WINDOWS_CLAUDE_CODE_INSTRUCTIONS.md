# Windows PC側Claude Code - 完全セットアップ指示書

## 🎯 あなたのミッション

NET8カメラクライアントシステムのWindows PC側セットアップを完全自動で実行してください。

**環境:** Windows 10/11
**役割:** カメラ配信クライアントのセットアップと動作確認
**接続先:** Mac側サーバー（ngrok経由）

---

## 📋 前提条件の確認

### 1. 必要なファイルの確認

以下のファイルがWindows PCに転送されているか確認してください：

```powershell
# 確認コマンド
Test-Path ".\WorksetClientSetup_ngrok.zip"
```

**期待される結果:** True

### 2. 管理者権限の確認

```powershell
# 管理者権限で実行しているか確認
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
$currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
```

**期待される結果:** True

もしFalseの場合：
```
PowerShellを右クリック → 「管理者として実行」で再起動してください
```

### 3. インターネット接続確認

```powershell
# インターネット接続確認
Test-Connection -ComputerName 8.8.8.8 -Count 2

# Mac側ngrokサーバー疎通確認
Invoke-WebRequest -Uri "https://aicrypto.ngrok.dev" -Method Head
```

**期待される結果:** 両方とも成功

---

## 🚀 セットアップ手順

### ステップ1: ファイル展開

```powershell
# zipファイルを展開
Expand-Archive -Path ".\WorksetClientSetup_ngrok.zip" -DestinationPath "." -Force

# 展開確認
Test-Path ".\camera_localpcsetup\WorksetClientSetup_ngrok"
```

**期待される結果:** True

### ステップ2: セットアップディレクトリに移動

```powershell
cd .\camera_localpcsetup\WorksetClientSetup_ngrok

# ファイル数確認（29個あるはず）
(Get-ChildItem).Count
```

**期待される結果:** 29

### ステップ3: 初期セットアップ実行

**重要:** この作業には再起動が必要です

```powershell
# 1_Office_AutoRun.bat を実行
.\1_Office_AutoRun.bat
```

**対話式入力が求められます：**
- カメラ端末番号: `1` を入力
- ホスト番号: `1` を入力

**実行内容:**
- [1/8] Windows Update無効化
- [2/8] タイムゾーン設定（Tokyo）
- [3/8] 電源管理（スリープ無効）
- [4/8] PC名変更（CAMERA-001-0001）
- [5/8] 固定IP設定スキップ ← **ngrok環境のため**
- [6/8] ネットワークPrivate設定
- [7/8] リモートデスクトップ有効化
- [8/8] Chrome自動インストール

**完了後:**
```
「セットアップが完了しました。再起動してください」と表示されます
```

**実行:**
```powershell
shutdown /r /t 60 /c "初期セットアップ完了。60秒後に再起動します"
```

---

### ⏸️ **ここで一度再起動します**

---

### ステップ4: アプリケーションインストール（再起動後）

**再起動後、再度PowerShellを管理者権限で起動してください**

```powershell
# セットアップディレクトリに移動
cd .\camera_localpcsetup\WorksetClientSetup_ngrok

# 2_Site_AutoRun.bat を実行
.\2_Site_AutoRun.bat
```

**対話式入力が求められます：**
- ホスト番号: `1` を入力

**実行内容:**
- DOMAIN=aicrypto.ngrok.dev を設定
- Net8AppInstall.bat 実行
  - setupapp.exe が C:\serverset\ にインストール

**インストール確認:**
```powershell
# C:\serverset が作成されたか確認
Test-Path "C:\serverset"

# 必要なファイルが存在するか確認
Test-Path "C:\serverset\slotserver.exe"
Test-Path "C:\serverset\pachiserver.exe"
Test-Path "C:\serverset\camera.bat"
Test-Path "C:\serverset\chromeCameraV2.exe"
```

**期待される結果:** 全てTrue

### ステップ5: 自動化設定

```powershell
# 3_Last_AutoRun.bat を実行
.\3_Last_AutoRun.bat
```

**対話式入力が求められます：**
- ユーザー名: `pcuser` を入力
- パスワード: `pcpass` を入力
- シャットダウン時刻: `09:00:00` を入力

**実行内容:**
- 自動ログイン設定（pcuser/pcpass）
- ログイン3分後にcamera.bat自動実行
- 毎日09:00に自動再起動
- 音量ミュート
- 画面解像度1920x1080

**タスクスケジューラ確認:**
```powershell
# 登録されたタスク確認
Get-ScheduledTask -TaskPath "\Net8\"
```

**期待される結果:**
```
TaskName        State
--------        -----
AutoRun         Ready
AutoShutdown    Ready
```

### ステップ6: ngrok設定ファイルの配置

**重要:** このステップは手動で行う必要があります

#### 6-1: slotserver_ngrok.ini の作成

```powershell
# C:\serverset\slotserver_ngrok.ini を作成
@"
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c
domain = aicrypto.ngrok.dev

[PatchServer]
filesurl =
url =

[API]
url = https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=

[Chrome]
url = https://aicrypto.ngrok.dev/server_v2/

[Monitor]
url = wss://aicrypto.ngrok.dev/ws

[Credit]
playmin = 3
"@ | Out-File -FilePath "C:\serverset\slotserver_ngrok.ini" -Encoding ASCII
```

**確認:**
```powershell
Get-Content "C:\serverset\slotserver_ngrok.ini"
```

#### 6-2: start_ngrok.bat の作成（オプション: 手動起動用）

```powershell
# C:\serverset\start_ngrok.bat を作成
@"
@echo off
echo ================================================
echo NET8 Camera Client - ngrok Connection
echo ================================================
echo.
echo Server: https://aicrypto.ngrok.dev
echo PeerJS: https://aimoderation.ngrok-free.app
echo MAC Address: 00:00:00:00:00:01
echo.
echo Press any key to start...
pause

REM slotserver_ngrok.iniを使用
copy /Y slotserver_ngrok.ini slotserver.ini

echo.
echo Starting slotserver.exe...
start slotserver.exe

echo.
echo Slotserver started.
pause
"@ | Out-File -FilePath "C:\serverset\start_ngrok.bat" -Encoding ASCII
```

**確認:**
```powershell
Test-Path "C:\serverset\start_ngrok.bat"
```

### ステップ7: 接続テスト（再起動前）

#### 7-1: API疎通テスト

```powershell
# Mac側サーバーへの接続テスト
$response = Invoke-WebRequest -Uri "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
$response.Content
```

**期待される結果:**
```json
{"status":"ok","machine_no":1,"category":1,"leavetime":180,...}
```

もしエラーの場合：
- Mac側のngrokトンネルが起動しているか確認してください
- インターネット接続を確認してください

#### 7-2: PeerJSサーバー疎通テスト

```powershell
# PeerJSシグナリングサーバー確認
$response = Invoke-WebRequest -Uri "https://aimoderation.ngrok-free.app/peerjs/id"
$response.Content
```

**期待される結果:**
ランダムなPeer ID（例: "abc123xyz456"）が返ってくる

#### 7-3: カメラ配信ページアクセステスト

```powershell
# ブラウザで確認（自動的に開く）
Start-Process "https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01"
```

**確認項目:**
- ✅ ページが正常に表示される
- ✅ カメラアクセス許可ダイアログが表示される
- ✅ F12でコンソールを開き、エラーがないか確認

### ステップ8: 最終再起動

**全てのテストが成功したら：**

```powershell
shutdown /r /t 60 /c "セットアップ完了。自動起動テストのため60秒後に再起動します"
```

---

## 🔍 再起動後の動作確認

### 自動起動の確認（再起動から約3分後）

**期待される自動動作:**
1. 自動ログイン（pcuser）
2. 3分待機
3. camera.bat自動実行
4. slotserver.exeまたはpachiserver.exe起動
5. Chromeが自動的に開く
6. カメラ配信開始

### 確認コマンド

```powershell
# プロセス確認
Get-Process | Where-Object { $_.Name -like "*slot*" -or $_.Name -like "*pachi*" -or $_.Name -like "*chrome*" }

# 期待される結果:
# slotserver または pachiserver が実行中
# chrome が実行中
```

### コンソールウィンドウの確認

**slotserver.exeのコンソールが開いているはずです。確認項目:**
- ✅ "API接続成功" のようなメッセージ
- ✅ "マシン番号: 1" のような表示
- ✅ "カテゴリ: 1 または 2" の表示
- ✅ エラーメッセージがない

### Chromeの確認

**Chromeが自動的に開いているはずです。確認項目:**
- ✅ URL: `https://aicrypto.ngrok.dev/server_v2/?MAC=...`
- ✅ カメラのプレビュー映像が表示されている
- ✅ F12 → Consoleタブでエラーがない

**期待されるコンソールログ:**
```
PeerJS: Connected to server
PeerJS: Listening on peer: camera-XXX-XXX
MediaStream: Camera started
```

---

## 🎉 Mac側での視聴確認

**Windows PC側のセットアップが完了したら、Mac側に報告してください：**

```
「Windows PC側のセットアップが完了しました。
以下のURLで視聴確認をお願いします：
https://aicrypto.ngrok.dev/play_v2/?NO=1」
```

---

## 🔧 トラブルシューティング

### 問題1: camera.batが自動実行されない

**診断:**
```powershell
Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun"
```

**対処:**
```powershell
# 手動でタスク登録
$Action = New-ScheduledTaskAction -Execute "c:\serverset\camera.bat"
$Trigger = New-ScheduledTaskTrigger -AtLogon
$Trigger.Delay = "PT3M"
Register-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun" -Action $Action -Trigger $Trigger -Force | Enable-ScheduledTask

# 再起動してテスト
shutdown /r /t 60
```

### 問題2: slotserver.exeが起動しない

**診断:**
```powershell
Test-Path "C:\serverset\slotserver.exe"
Test-Path "C:\serverset\slotserver.ini"
```

**対処:**
```powershell
# slotserver.iniが存在しない場合
cd C:\serverset
copy /Y slotserver_ngrok.ini slotserver.ini

# 手動実行してエラーメッセージ確認
.\slotserver.exe
```

### 問題3: API接続エラー

**診断:**
```powershell
# API疎通確認
Invoke-WebRequest -Uri "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="

# DNS解決確認
nslookup aicrypto.ngrok.dev
```

**対処:**
- Mac側に連絡: ngrokトンネルが起動しているか確認してもらう
- インターネット接続を確認

### 問題4: PeerJS接続エラー

**診断:**
```powershell
# PeerJSサーバー疎通確認
Invoke-WebRequest -Uri "https://aimoderation.ngrok-free.app/peerjs/id"

# ネットワークカテゴリ確認
Get-NetConnectionProfile
```

**対処:**
```powershell
# NetworkCategoryがPublicの場合、Privateに変更
Get-NetConnectionProfile | Set-NetConnectionProfile -NetworkCategory Private

# ファイアウォール確認
Get-NetFirewallProfile | Select Name, Enabled

# Chrome再起動
taskkill /IM chrome.exe /F
C:\serverset\chromeCameraV2.exe
```

### 問題5: カメラが認識されない

**診断:**
```powershell
# カメラデバイス確認
Get-PnpDevice | Where-Object { $_.FriendlyName -like "*camera*" -or $_.FriendlyName -like "*webcam*" }
```

**対処:**
1. Windowsの設定を開く
2. プライバシーとセキュリティ → カメラ
3. 「アプリがカメラにアクセスできるようにする」をオンに
4. Chromeを再起動

### 問題6: 固定IPを誤って設定してしまった

**症状:** インターネット接続が切れた

**診断:**
```powershell
Get-NetIPAddress | Where-Object { $_.AddressFamily -eq "IPv4" }
# IPが 192.168.11.xxx になっている
```

**対処:**
```powershell
# DHCPに戻す
$adapter = Get-NetAdapter | Where-Object { $_.Status -eq "Up" }
Set-NetIPInterface -InterfaceAlias $adapter.Name -Dhcp Enabled
Set-DnsClientServerAddress -InterfaceAlias $adapter.Name -ResetServerAddresses
Restart-NetAdapter -Name $adapter.Name

# 接続確認
Test-Connection 8.8.8.8
Invoke-WebRequest "https://aicrypto.ngrok.dev"
```

---

## 📊 完了チェックリスト

セットアップ完了前に以下を確認してください：

### 初期セットアップ
- [ ] WorksetClientSetup_ngrok.zip 展開完了
- [ ] 1_Office_AutoRun.bat 実行完了
- [ ] 再起動完了（PC名変更適用）

### アプリケーションインストール
- [ ] 2_Site_AutoRun.bat 実行完了
- [ ] C:\serverset ディレクトリ作成確認
- [ ] slotserver.exe 存在確認
- [ ] camera.bat 存在確認

### 自動化設定
- [ ] 3_Last_AutoRun.bat 実行完了
- [ ] タスクスケジューラにAutoRun登録確認
- [ ] タスクスケジューラにAutoShutdown登録確認

### ngrok設定
- [ ] slotserver_ngrok.ini 作成完了
- [ ] start_ngrok.bat 作成完了（オプション）

### 接続テスト
- [ ] API疎通テスト成功
- [ ] PeerJS疎通テスト成功
- [ ] カメラ配信ページアクセス成功

### 最終確認
- [ ] 再起動完了
- [ ] 自動ログイン動作確認
- [ ] camera.bat自動実行確認（3分後）
- [ ] slotserver.exe起動確認
- [ ] Chrome自動起動確認
- [ ] カメラ映像配信開始確認

---

## 📞 Mac側への報告フォーマット

セットアップ完了時、以下の情報をMac側に報告してください：

```
✅ Windows PC側セットアップ完了報告

【基本情報】
- PC名: CAMERA-001-0001
- Windows バージョン: (Get-ComputerInfo | Select WindowsVersion)
- IPアドレス: (Get-NetIPAddress | Where-Object {$_.AddressFamily -eq "IPv4" -and $_.PrefixOrigin -eq "Dhcp"}).IPAddress

【インストール確認】
- C:\serverset ディレクトリ: ✅
- slotserver.exe: ✅
- camera.bat: ✅

【自動化確認】
- 自動ログイン: ✅
- AutoRunタスク: ✅
- AutoShutdownタスク: ✅

【接続テスト】
- API疎通: ✅ (https://aicrypto.ngrok.dev)
- PeerJS疎通: ✅ (https://aimoderation.ngrok-free.app)
- カメラページアクセス: ✅

【実行中プロセス】
- slotserver.exe: 実行中
- chrome.exe: 実行中
- カメラ配信: 開始済み

【視聴確認依頼】
以下のURLで映像が表示されるか確認をお願いします：
https://aicrypto.ngrok.dev/play_v2/?NO=1

【備考】
(問題があれば記載)
```

---

## 🎯 重要な注意事項

### ❌ やってはいけないこと

1. **元の `1_Office_AutoRun.bat` を実行しない**
   - 固定IP設定が含まれており、インターネット接続が切れます
   - 必ず修正版（WorksetClientSetup_ngrok内）を使用してください

2. **FixedIPAddress.ps1 を単体で実行しない**
   - ローカルLAN専用のスクリプトです
   - ngrok環境では使用できません

3. **slotserver.ini を直接編集しない**
   - slotserver_ngrok.ini をコピーして使用してください
   - camera.bat が自動的にコピーします

### ✅ 必ずやること

1. **管理者権限で実行**
   - 全てのバッチファイルは管理者権限で実行してください

2. **再起動を忘れない**
   - 1_Office_AutoRun.bat の後、必ず再起動してください
   - 3_Last_AutoRun.bat の後、必ず再起動してください

3. **テストを省略しない**
   - ステップ7の接続テストは必ず実行してください
   - 問題を早期発見できます

---

## 📚 参考情報

### ngrok URL
- **Webサーバー:** https://aicrypto.ngrok.dev (Mac:8080)
- **PeerJSサーバー:** https://aimoderation.ngrok-free.app (Mac:59000)

### テスト用ライセンス
- **MAC Address:** 00:00:00:00:00:01
- **License ID:** IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
- **CD:** 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c

### ログ収集方法（問題発生時）

```powershell
# ログディレクトリ作成
New-Item -Path "C:\serverset\logs" -ItemType Directory -Force

# システム情報
systeminfo > C:\serverset\logs\systeminfo.txt

# ネットワーク情報
ipconfig /all > C:\serverset\logs\ipconfig.txt
Get-NetConnectionProfile > C:\serverset\logs\network_profile.txt

# タスクスケジューラ
Get-ScheduledTask -TaskPath "\Net8\" > C:\serverset\logs\scheduled_tasks.txt

# 実行中のプロセス
Get-Process > C:\serverset\logs\processes.txt

# 設定ファイル
Get-Content C:\serverset\slotserver.ini > C:\serverset\logs\slotserver_ini.txt
```

---

**作成日:** 2025-10-23
**対象:** Windows PC側Claude Code
**Mac側担当:** Claude Code (Mac)
**システム:** NET8 WebRTC Camera System
**接続方式:** ngrokトンネル経由

---

## 🚀 それでは、セットアップを開始してください！

この指示書に従って、順番に作業を進めてください。
各ステップの結果を確認しながら、慎重に進めてください。

問題が発生した場合は、トラブルシューティングセクションを参照するか、
Mac側に報告してサポートを受けてください。

頑張ってください！
