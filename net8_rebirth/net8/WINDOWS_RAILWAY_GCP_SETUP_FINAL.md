# 🎮 NET8 Windows PC（ゲーム機）完全セットアップガイド - Railway+GCP環境版

**対象:** Windows PCゲーム機（他の台）の完全インストール
**環境:** Railway本番環境 + GCP Cloud SQL
**作成日:** 2025-11-05
**所要時間:** 約30分（再起動含む）

---

## 📋 目次

1. [システム構成](#システム構成)
2. [事前準備](#事前準備)
3. [ステップ1: 初期セットアップ](#ステップ1-初期セットアップ)
4. [ステップ2: アプリケーションインストール](#ステップ2-アプリケーションインストール)
5. [ステップ3: 自動化設定](#ステップ3-自動化設定)
6. [ステップ4: Railway設定ファイル作成](#ステップ4-railway設定ファイル作成)
7. [ステップ5: 接続テスト](#ステップ5-接続テスト)
8. [ステップ6: 最終再起動と動作確認](#ステップ6-最終再起動と動作確認)
9. [トラブルシューティング](#トラブルシューティング)

---

## システム構成

### ✅ 正しいシステムアーキテクチャ

```
Windows PC（ゲーム機）
    ↓ インターネット経由で直接接続
Railway Webサーバー
https://mgg-webservice-production.up.railway.app/
    ↓ Private Network
GCP Cloud SQL
136.116.70.86:3306 (net8_dev)
```

### ❌ ngrok は使いません

- このセットアップでは **ngrokは一切使用しません**
- Railway本番環境に直接接続します
- GCP Cloud SQLをデータベースとして使用します

---

## 事前準備

### 必要なもの

1. **Windows PC** (Windows 10/11)
2. **管理者権限**
3. **インターネット接続**
4. **セットアップファイル一式** (約95MB)

### 📦 必要なファイル一覧

以下のファイルをUSBメモリまたはネットワーク経由でWindows PCに転送してください：

```
WorksetClientSetup_ja_local/  (約95MB)
├── setupapp.exe                                    (33MB) ← 重要！
├── GoogleChromeStandaloneEnterprise64.msi          (59MB)
├── 1_Office_AutoRun.bat                            (3KB)
├── 2_Site_AutoRun.bat                              (301B)
├── 3_Last_AutoRun.bat                              (1.6KB)
├── Net8AppInstall.bat                              (2KB)
├── ChromeLocalInstall.bat                          (909B)
├── PowerControl.bat                                (540B)
└── PowerShellスクリプト群/
    ├── DisableWindowsUpdate.ps1
    ├── TimeZone.ps1
    ├── RenameComputer.ps1
    ├── UpdatePrivateNetwork.ps1
    ├── RemoteDesktop.ps1
    ├── Net8AutoLogin.ps1
    └── Net8AutoShutdown.ps1
```

**ファイルの場所（Mac側）:**
```
/Users/kotarokashiwai/net8_rebirth/net8/camera_localpcsetup/カメラ端末設置ファイル/01.セットアップ用ファイル/WorksetClientSetup_ja_local/
```

### Windows側での事前確認

Windows PCで以下を確認してください：

```powershell
# PowerShellを管理者として実行

# 1. 管理者権限確認
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
$isAdmin = $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if ($isAdmin) {
    Write-Host "✅ 管理者権限で実行中" -ForegroundColor Green
} else {
    Write-Host "❌ 管理者権限がありません" -ForegroundColor Red
    exit
}

# 2. インターネット接続確認
Test-Connection -ComputerName 8.8.8.8 -Count 2
Write-Host "✅ インターネット接続OK" -ForegroundColor Green

# 3. Railway環境への接続確認
try {
    Invoke-WebRequest -Uri "https://mgg-webservice-production.up.railway.app/" -Method Head -TimeoutSec 10
    Write-Host "✅ Railwayサーバー接続OK" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Railwayサーバーに接続できません" -ForegroundColor Yellow
}
```

---

## ステップ1: 初期セットアップ

### ステップ1-1: セットアップファイルを展開

```powershell
# セットアップディレクトリに移動
cd C:\Users\<ユーザー名>\Downloads\WorksetClientSetup_ja_local

# ファイル確認
dir

# 必要なファイルが揃っているか確認
Test-Path .\setupapp.exe
Test-Path .\1_Office_AutoRun.bat
Test-Path .\2_Site_AutoRun.bat
Test-Path .\3_Last_AutoRun.bat
```

### ステップ1-2: 1_Office_AutoRun.bat 実行

⚠️ **重要:** このステップの後、PCの再起動が必要です

**実行方法:**
1. `1_Office_AutoRun.bat` を**右クリック** → **管理者として実行**
2. 以下の入力を求められます：

```
Enter Camera Terminal Number: 1  （カメラ端末番号）
Enter Host Number: 1              （ホスト番号）
```

**実行内容（8ステップ）:**
- [1/8] Windows Update無効化
- [2/8] タイムゾーン設定（Tokyo）
- [3/8] 電源管理設定（スリープ・休止無効）
- [4/8] PC名変更（CAMERA-001-0001）
- [5/8] 固定IP設定スキップ ← **Railway環境のためスキップ**
- [6/8] ネットワークPrivate設定
- [7/8] リモートデスクトップ有効化
- [8/8] Chrome自動インストール

**完了後の表示:**
```
================================================
Setup completed.
================================================

Next steps:
1. Reboot this PC
2. After reboot, execute the next batch file:
   2_Site_AutoRun.bat

================================================
```

### ステップ1-3: 再起動

```powershell
# 60秒後に再起動
shutdown /r /t 60 /c "ステップ1完了。PC名変更を適用するため再起動します"
```

⏸️ **ここで一度PCを再起動してください**

**再起動後に確認:**
- PC名が `CAMERA-001-0001` に変更されている
- `hostname` コマンドで確認

---

## ステップ2: アプリケーションインストール

### ステップ2-1: セットアップディレクトリに戻る

再起動後、PowerShell（管理者）を開いて：

```powershell
cd C:\Users\<ユーザー名>\Downloads\WorksetClientSetup_ja_local
```

### ステップ2-2: 2_Site_AutoRun.bat を編集（重要！）

**Railway環境用にDOMAIN設定を変更:**

1. メモ帳で `2_Site_AutoRun.bat` を開く
2. 以下の行を変更：

```batch
# 変更前
set DOMAIN=example.com

# 変更後（Railway環境）
set DOMAIN=mgg-webservice-production.up.railway.app
```

3. 保存して閉じる

### ステップ2-3: 2_Site_AutoRun.bat 実行

```powershell
# 管理者権限で実行
.\2_Site_AutoRun.bat
```

**入力が求められたら:**
```
Enter Host Number: 1  （ホスト番号を入力）
```

**実行内容:**
- `Net8AppInstall.bat` を呼び出し
- `setupapp.exe` を実行
- `C:\serverset\` ディレクトリに以下をインストール：
  - `slotserver.exe` (47MB)
  - `pachiserver.exe` (47MB)
  - `camera.bat`
  - `chromeCameraV2.exe`
  - `camera_ctrl.exe`
  - `getcategory.exe`
  - `updatefilesV2.exe`
  - `camera.ini`

### ステップ2-4: インストール確認

```powershell
# C:\serverset ディレクトリ確認
Test-Path C:\serverset
dir C:\serverset

# 必要なファイルの確認
$requiredFiles = @(
    "C:\serverset\slotserver.exe",
    "C:\serverset\pachiserver.exe",
    "C:\serverset\camera.bat",
    "C:\serverset\chromeCameraV2.exe",
    "C:\serverset\camera_ctrl.exe",
    "C:\serverset\getcategory.exe"
)

Write-Host "必要なファイルの確認:" -ForegroundColor Cyan
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "  ✅ $(Split-Path $file -Leaf)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $(Split-Path $file -Leaf) が見つかりません" -ForegroundColor Red
    }
}
```

**期待される結果:**
```
✅ slotserver.exe
✅ pachiserver.exe
✅ camera.bat
✅ chromeCameraV2.exe
✅ camera_ctrl.exe
✅ getcategory.exe
```

---

## ステップ3: 自動化設定

### ステップ3-1: 3_Last_AutoRun.bat 実行

```powershell
# セットアップディレクトリで実行
.\3_Last_AutoRun.bat
```

**入力が求められたら:**
```
Username: pcuser                （自動ログイン用ユーザー名）
Password: pcpass                （自動ログイン用パスワード）
Shutdown time: 09:00:00         （毎日自動シャットダウン時刻）
```

**実行内容:**
- 自動ログイン設定（pcuser/pcpass）
- ログイン3分後にcamera.bat自動実行
- 毎日09:00に自動再起動
- 音量ミュート
- 画面解像度1920x1080

### ステップ3-2: タスクスケジューラ確認

```powershell
# タスク登録確認
$tasks = Get-ScheduledTask -TaskPath "\Net8\" -ErrorAction SilentlyContinue
if ($tasks) {
    Write-Host "✅ タスクが登録されました:" -ForegroundColor Green
    $tasks | Format-Table TaskName, State -AutoSize
} else {
    Write-Host "❌ タスクが登録されていません" -ForegroundColor Red
}
```

**期待される結果:**
```
✅ タスクが登録されました:

TaskName      State
--------      -----
AutoRun       Ready
AutoShutdown  Ready
```

---

## ステップ4: Railway設定ファイル作成

### ステップ4-1: slotserver_railway.ini 作成

**重要: この設定ファイルがRailway環境への接続を制御します**

```powershell
# C:\serverset に移動
cd C:\serverset

# slotserver_railway.ini を作成
$iniContent = @"
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
"@

# ファイルを作成
$iniContent | Out-File -FilePath "C:\serverset\slotserver_railway.ini" -Encoding ASCII -Force

# 確認
if (Test-Path "C:\serverset\slotserver_railway.ini") {
    Write-Host "✅ slotserver_railway.ini を作成しました" -ForegroundColor Green
    Get-Content "C:\serverset\slotserver_railway.ini"
} else {
    Write-Host "❌ ファイル作成に失敗しました" -ForegroundColor Red
}
```

### ステップ4-2: slotserver.ini にコピー

```powershell
# slotserver.exeが読み込む設定ファイルにコピー
Copy-Item -Path "C:\serverset\slotserver_railway.ini" -Destination "C:\serverset\slotserver.ini" -Force

# 確認
if (Test-Path "C:\serverset\slotserver.ini") {
    Write-Host "✅ slotserver.ini にコピーしました" -ForegroundColor Green
} else {
    Write-Host "❌ コピーに失敗しました" -ForegroundColor Red
}
```

### ステップ4-3: start_railway.bat 作成（オプション: 手動起動用）

```powershell
$batContent = @"
@echo off
echo ================================================
echo NET8 Camera Client - Railway Production
echo ================================================
echo.
echo Server: https://mgg-webservice-production.up.railway.app/
echo Database: GCP Cloud SQL (136.116.70.86)
echo MAC Address: 00:00:00:00:00:01
echo.
echo Press any key to start...
pause

REM slotserver_railway.ini を使用
copy /Y slotserver_railway.ini slotserver.ini

echo.
echo Starting slotserver.exe...
start slotserver.exe

echo.
echo Slotserver started.
pause
"@

$batContent | Out-File -FilePath "C:\serverset\start_railway.bat" -Encoding ASCII -Force

if (Test-Path "C:\serverset\start_railway.bat") {
    Write-Host "✅ start_railway.bat を作成しました（手動起動用）" -ForegroundColor Green
} else {
    Write-Host "❌ ファイル作成に失敗しました" -ForegroundColor Red
}
```

---

## ステップ5: 接続テスト

### ステップ5-1: Railway環境への接続テスト

```powershell
Write-Host "Railway環境への接続テストを開始します..." -ForegroundColor Cyan
Write-Host ""

# 1. Webサーバー疎通確認
Write-Host "5-1-1: Webサーバー疎通テスト..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "https://mgg-webservice-production.up.railway.app/" -UseBasicParsing -TimeoutSec 10
    Write-Host "✅ Webサーバー接続成功 (Status: $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "❌ Webサーバー接続失敗" -ForegroundColor Red
    Write-Host "エラー: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""

# 2. API疎通確認
Write-Host "5-1-2: API疎通テスト..." -ForegroundColor Cyan
try {
    $mac = "00:00:00:00:00:01"
    $id = "IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
    $apiUrl = "https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=$mac&ID=$id"

    $response = Invoke-WebRequest -Uri $apiUrl -UseBasicParsing -TimeoutSec 10
    $content = $response.Content | ConvertFrom-Json

    Write-Host "✅ API接続成功" -ForegroundColor Green
    Write-Host "  - status: $($content.status)" -ForegroundColor White
    Write-Host "  - machine_no: $($content.machine_no)" -ForegroundColor White
    Write-Host "  - category: $($content.category)" -ForegroundColor White
} catch {
    Write-Host "❌ API接続失敗" -ForegroundColor Red
    Write-Host "エラー: $($_.Exception.Message)" -ForegroundColor Yellow
}
```

**期待される結果:**
```
✅ Webサーバー接続成功 (Status: 200)
✅ API接続成功
  - status: ok
  - machine_no: 1
  - category: 2
```

### ステップ5-2: slotserver.exe 起動テスト

```batch
cd C:\serverset

REM 設定ファイル確認
type slotserver.ini

REM slotserver.exe 起動
slotserver.exe
```

**期待される動作:**
```
Slotserver 起動中...
設定ファイル読み込み: slotserver.ini
License認証成功
API接続中: https://mgg-webservice-production.up.railway.app/api/...
API接続成功
マシン番号: 1
カテゴリ: 2（スロット）
シリアルポート: COM3
待機中...
```

### ステップ5-3: カメラ配信ページアクセステスト

```powershell
# カメラ配信ページをブラウザで開く
$cameraUrl = "https://mgg-webservice-production.up.railway.app/server_v2/?MAC=00:00:00:00:00:01"
Start-Process $cameraUrl

Write-Host "✅ ブラウザでカメラページを開きました" -ForegroundColor Green
Write-Host ""
Write-Host "確認事項:" -ForegroundColor Yellow
Write-Host "  - ページが正常に表示されるか" -ForegroundColor White
Write-Host "  - カメラアクセス許可ダイアログが表示されるか" -ForegroundColor White
Write-Host "  - F12でコンソールを開き、エラーがないか確認" -ForegroundColor White
```

---

## ステップ6: 最終再起動と動作確認

### ステップ6-1: 最終再起動

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "全てのセットアップが完了しました！" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "最終再起動を実行します" -ForegroundColor Yellow
Write-Host "再起動後、以下の動作が自動的に行われます:" -ForegroundColor Cyan
Write-Host "  1. 自動ログイン（pcuser）" -ForegroundColor White
Write-Host "  2. 3分待機" -ForegroundColor White
Write-Host "  3. camera.bat 自動実行" -ForegroundColor White
Write-Host "  4. slotserver.exe 起動" -ForegroundColor White
Write-Host "  5. Chrome自動起動" -ForegroundColor White
Write-Host "  6. カメラ配信開始" -ForegroundColor White
Write-Host ""

# 60秒後に再起動
shutdown /r /t 60 /c "セットアップ完了。自動起動テストのため再起動します"
```

### ステップ6-2: 再起動後の動作確認（約3分後）

```powershell
# プロセス起動確認
Write-Host "実行中のプロセスを確認中..." -ForegroundColor Cyan
$processes = Get-Process | Where-Object { $_.Name -like "*slot*" -or $_.Name -like "*pachi*" -or $_.Name -like "*chrome*" }

if ($processes) {
    Write-Host "✅ プロセスが起動しています:" -ForegroundColor Green
    $processes | Format-Table Name, Id -AutoSize
} else {
    Write-Host "❌ プロセスが起動していません" -ForegroundColor Red
}
```

### ステップ6-3: 完了報告

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "Windows PC側セットアップ完了報告" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green

$computerInfo = Get-ComputerInfo | Select-Object WindowsVersion, OsArchitecture
$ipAddress = (Get-NetIPAddress | Where-Object { $_.AddressFamily -eq "IPv4" -and $_.PrefixOrigin -eq "Dhcp" }).IPAddress

Write-Host ""
Write-Host "【基本情報】" -ForegroundColor Cyan
Write-Host "  - PC名: $env:COMPUTERNAME" -ForegroundColor White
Write-Host "  - Windows バージョン: $($computerInfo.WindowsVersion)" -ForegroundColor White
Write-Host "  - IPアドレス: $ipAddress" -ForegroundColor White

Write-Host ""
Write-Host "【インストール確認】" -ForegroundColor Cyan
Write-Host "  - C:\serverset: $(if (Test-Path 'C:\serverset') {'✅'} else {'❌'})" -ForegroundColor White
Write-Host "  - slotserver.exe: $(if (Test-Path 'C:\serverset\slotserver.exe') {'✅'} else {'❌'})" -ForegroundColor White
Write-Host "  - slotserver.ini: $(if (Test-Path 'C:\serverset\slotserver.ini') {'✅'} else {'❌'})" -ForegroundColor White

Write-Host ""
Write-Host "【実行中プロセス】" -ForegroundColor Cyan
$slotProcess = Get-Process -Name "slotserver" -ErrorAction SilentlyContinue
$chromeProcess = Get-Process -Name "chrome" -ErrorAction SilentlyContinue
Write-Host "  - slotserver.exe: $(if ($slotProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White
Write-Host "  - chrome.exe: $(if ($chromeProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White

Write-Host ""
Write-Host "【視聴確認依頼】" -ForegroundColor Yellow
Write-Host "以下のURLで映像が表示されるか確認をお願いします:" -ForegroundColor Yellow
Write-Host "https://mgg-webservice-production.up.railway.app/play_v2/?NO=1" -ForegroundColor White

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
```

---

## トラブルシューティング

### 問題1: setupapp.exeが見つからない

**症状:**
```
'setupapp.exe' is not recognized as an internal or external command
```

**対処法:**
1. セットアップファイル一式が正しく転送されているか確認
2. setupapp.exe (33MB) が存在するか確認
3. ファイルが破損していないか確認（ファイルサイズ確認）

---

### 問題2: API接続エラー

**症状:**
```
{"status":"error","message":"Connection failed"}
```

**診断:**
```powershell
# Railway環境への疎通確認
curl "https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=..."
```

**対処法:**
1. インターネット接続を確認
2. ファイアウォール設定を確認
3. slotserver.iniのURL設定を確認

---

### 問題3: slotserver.exeがすぐに終了する

**症状:**
コンソールウィンドウが一瞬表示されて消える

**診断:**
```batch
cd C:\serverset
type slotserver.ini | findstr "url"
```

**対処法:**
1. slotserver.iniが存在するか確認
2. INIファイルの内容が正しいか確認
3. URLに `mgg-webservice-production.up.railway.app` が設定されているか確認

---

### 問題4: camera.batが自動実行されない

**診断:**
```powershell
Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun"
```

**対処法:**
```powershell
# 手動でタスク登録
$Action = New-ScheduledTaskAction -Execute "c:\serverset\camera.bat"
$Trigger = New-ScheduledTaskTrigger -AtLogon
$Trigger.Delay = "PT3M"
Register-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun" -Action $Action -Trigger $Trigger -Force | Enable-ScheduledTask
```

---

### 問題5: カメラが認識されない

**診断:**
```powershell
Get-PnpDevice | Where-Object { $_.FriendlyName -like "*camera*" -or $_.FriendlyName -like "*webcam*" }
```

**対処法:**
1. Windowsの設定 → プライバシーとセキュリティ → カメラ
2. 「アプリがカメラにアクセスできるようにする」をオンに
3. カメラドライバを再インストール

---

## ✅ 完了チェックリスト

### セットアップ完了確認

- [ ] ステップ1: 1_Office_AutoRun.bat実行完了
- [ ] 再起動1: PC名変更適用のため再起動
- [ ] ステップ2: 2_Site_AutoRun.bat実行完了（DOMAIN変更済み）
- [ ] 確認: C:\serverset\ディレクトリ＆ファイル確認
- [ ] ステップ3: 3_Last_AutoRun.bat実行完了
- [ ] ステップ4: slotserver_railway.ini作成完了
- [ ] ステップ4: slotserver.iniにコピー完了
- [ ] ステップ5: Railway環境への接続テスト成功
- [ ] ステップ5: API疎通テスト成功
- [ ] ステップ5: slotserver.exe起動テスト成功
- [ ] 再起動2: 自動化テストのため再起動
- [ ] 確認: 自動ログイン動作確認
- [ ] 確認: camera.bat自動実行確認（3分後）
- [ ] 確認: slotserver.exe起動確認
- [ ] 確認: Chrome自動起動確認
- [ ] 確認: カメラ映像配信開始確認
- [ ] 確認: 視聴ページで映像確認完了

---

## 🔗 重要なURL一覧

### Railway本番環境

```
Webサーバー: https://mgg-webservice-production.up.railway.app/
API: https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php
カメラ配信: https://mgg-webservice-production.up.railway.app/server_v2/
視聴ページ: https://mgg-webservice-production.up.railway.app/play_v2/
```

### GCP Cloud SQL（データベース）

```
Host: 136.116.70.86
Port: 3306
Database: net8_dev
User: net8tech001
Password: GbSzu+9Fgr?5`Us5
```

---

## 📞 サポート情報

### 問題が発生した場合

以下の情報を収集してください：

```powershell
# システム情報
systeminfo > C:\serverset\logs\systeminfo.txt

# ネットワーク情報
ipconfig /all > C:\serverset\logs\ipconfig.txt

# タスクスケジューラ
Get-ScheduledTask -TaskPath "\Net8\" > C:\serverset\logs\scheduled_tasks.txt

# 実行中のプロセス
Get-Process > C:\serverset\logs\processes.txt

# slotserver.iniの内容
Get-Content C:\serverset\slotserver.ini > C:\serverset\logs\slotserver_ini.txt
```

---

## 🎉 セットアップ完了

お疲れ様でした！

Windows PCとRailway本番環境の連携が完了しました。

**次のステップ:**
1. 長時間稼働テスト（24時間）
2. 再起動後の自動復旧確認
3. 複数台のWindows PCをセットアップ

---

**作成日:** 2025-11-05
**環境:** Railway本番環境 + GCP Cloud SQL
**対象:** Windows PC（ゲーム機）完全セットアップ
**システム:** NET8 WebRTC Camera System
