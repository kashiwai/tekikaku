# 🎮 NET8 Windows PC（ゲーム機）完全セットアップガイド

**対象:** Windows PCゲーム機（他の台）のインストール
**前提:** Windows側でClaude Codeを使用
**接続:** Railway本番環境 + ngrok トンネル経由
**作成日:** 2025-11-05

---

## 📋 目次

1. [事前準備](#事前準備)
2. [ステップ1: 初期セットアップ](#ステップ1-初期セットアップ)
3. [ステップ2: アプリケーションインストール](#ステップ2-アプリケーションインストール)
4. [ステップ3: 自動化設定](#ステップ3-自動化設定)
5. [ステップ4: ngrok設定ファイル作成](#ステップ4-ngrok設定ファイル作成)
6. [ステップ5: 接続テスト](#ステップ5-接続テスト)
7. [ステップ6: 最終再起動と動作確認](#ステップ6-最終再起動と動作確認)
8. [トラブルシューティング](#トラブルシューティング)

---

## 事前準備

### 必要なもの

1. **Windows PC** (Windows 10/11)
2. **Claude Code** インストール済み
3. **セットアップファイル一式** (`WorksetClientSetup_ngrok.zip` - 92MB)
4. **管理者権限**

### サーバー接続情報（確認用）

- **Webサーバー:** `https://aicrypto.ngrok.dev`
- **PeerJSサーバー:** `https://aimoderation.ngrok-free.app`
- **テストMAC:** `00:00:00:00:00:01`

### Windows側 Claude Codeで確認すべきこと

Windows PCで Claude Code を開き、以下をコピー&ペーストして実行：

```powershell
# 1. 管理者権限確認
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
$isAdmin = $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if ($isAdmin) {
    Write-Host "✅ 管理者権限で実行中" -ForegroundColor Green
} else {
    Write-Host "❌ 管理者権限がありません。PowerShellを管理者として実行してください" -ForegroundColor Red
    exit
}

# 2. インターネット接続確認
Write-Host "インターネット接続を確認中..." -ForegroundColor Cyan
Test-Connection -ComputerName 8.8.8.8 -Count 2 -ErrorAction Stop
Write-Host "✅ インターネット接続OK" -ForegroundColor Green

# 3. ngrokサーバー接続確認
Write-Host "ngrokサーバーへの接続を確認中..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "https://aicrypto.ngrok.dev" -Method Head -TimeoutSec 10
    Write-Host "✅ ngrokサーバー接続OK" -ForegroundColor Green
} catch {
    Write-Host "⚠️ ngrokサーバーに接続できません" -ForegroundColor Yellow
}
```

---

## ステップ1: 初期セットアップ

### ステップ1-1: zipファイルの展開

```powershell
# カレントディレクトリ確認
Write-Host "現在のディレクトリ: $(Get-Location)" -ForegroundColor Cyan

# zipファイルの存在確認
if (Test-Path ".\WorksetClientSetup_ngrok.zip") {
    Write-Host "✅ zipファイルを発見しました" -ForegroundColor Green
} else {
    Write-Host "❌ WorksetClientSetup_ngrok.zip が見つかりません" -ForegroundColor Red
    Write-Host "ファイルの場所を確認してください" -ForegroundColor Yellow
    exit
}

# zipファイルを展開
Write-Host "zipファイルを展開中..." -ForegroundColor Cyan
Expand-Archive -Path ".\WorksetClientSetup_ngrok.zip" -DestinationPath "." -Force
Write-Host "✅ 展開完了" -ForegroundColor Green

# セットアップディレクトリに移動
cd .\camera_localpcsetup\WorksetClientSetup_ngrok
Write-Host "セットアップディレクトリに移動: $(Get-Location)" -ForegroundColor Green
```

### ステップ1-2: 1_Office_AutoRun.bat 実行

⚠️ **重要:** このステップの後、PCの再起動が必要です

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ1: 初期セットアップを開始" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1_Office_AutoRun.bat を実行します" -ForegroundColor Yellow
Write-Host "対話式で以下の入力を求められます:" -ForegroundColor Yellow
Write-Host "  - Camera Terminal Number: 1 を入力" -ForegroundColor White
Write-Host "  - Host Number: 1 を入力" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

# バッチファイル実行
.\1_Office_AutoRun.bat
```

**入力が求められたら:**
1. `Enter Camera Terminal Number:` → **1** を入力してEnter
2. `Enter Host Number:` → **1** を入力してEnter

**実行内容:**
- Windows Update無効化
- タイムゾーン設定（Tokyo）
- 電源管理設定（スリープ無効）
- PC名変更（CAMERA-001-0001）
- 固定IP設定スキップ（ngrok環境のため）
- ネットワークPrivate設定
- リモートデスクトップ有効化
- Chrome自動インストール

### ステップ1-3: 再起動

```powershell
Write-Host "================================================" -ForegroundColor Green
Write-Host "ステップ1が完了しました" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "60秒後に再起動します..." -ForegroundColor Cyan
shutdown /r /t 60 /c "ステップ1完了。PC名変更を適用するため再起動します"
```

⏸️ **ここで一度PCを再起動してください**

---

## ステップ2: アプリケーションインストール

**再起動後、PowerShell（管理者）を開いて以下を実行:**

### ステップ2-1: セットアップディレクトリに移動

```powershell
# セットアップディレクトリに移動（パスは環境に合わせて変更）
cd C:\Users\<ユーザー名>\<展開した場所>\camera_localpcsetup\WorksetClientSetup_ngrok
```

### ステップ2-2: 2_Site_AutoRun.bat 実行

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ2: アプリケーションをインストール" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "2_Site_AutoRun.bat を実行します" -ForegroundColor Yellow
Write-Host "対話式で以下の入力を求められます:" -ForegroundColor Yellow
Write-Host "  - Host Number: 1 を入力" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

# バッチファイル実行
.\2_Site_AutoRun.bat
```

**入力が求められたら:**
1. `Enter Host Number:` → **1** を入力してEnter

**実行内容:**
- DOMAIN=aicrypto.ngrok.dev を設定
- setupapp.exe を C:\serverset\ にインストール

### ステップ2-3: インストール確認

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "インストール結果を確認中..." -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan

# C:\serverset ディレクトリの確認
if (Test-Path "C:\serverset") {
    Write-Host "✅ C:\serverset ディレクトリが作成されました" -ForegroundColor Green
} else {
    Write-Host "❌ C:\serverset ディレクトリが見つかりません" -ForegroundColor Red
}

# 必要なファイルの確認
$requiredFiles = @(
    "C:\serverset\slotserver.exe",
    "C:\serverset\pachiserver.exe",
    "C:\serverset\camera.bat",
    "C:\serverset\chromeCameraV2.exe",
    "C:\serverset\camera_ctrl.exe",
    "C:\serverset\getcategory.exe"
)

Write-Host ""
Write-Host "必要なファイルの確認:" -ForegroundColor Cyan
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "  ✅ $(Split-Path $file -Leaf)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $(Split-Path $file -Leaf) が見つかりません" -ForegroundColor Red
    }
}
```

---

## ステップ3: 自動化設定

### ステップ3-1: 3_Last_AutoRun.bat 実行

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ3: 自動化設定を実行" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "3_Last_AutoRun.bat を実行します" -ForegroundColor Yellow
Write-Host "対話式で以下の入力を求められます:" -ForegroundColor Yellow
Write-Host "  - Username: pcuser を入力" -ForegroundColor White
Write-Host "  - Password: pcpass を入力" -ForegroundColor White
Write-Host "  - Shutdown time: 09:00:00 を入力" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

# バッチファイル実行
.\3_Last_AutoRun.bat
```

**入力が求められたら:**
1. ユーザー名: **pcuser** を入力してEnter
2. パスワード: **pcpass** を入力してEnter
3. シャットダウン時刻: **09:00:00** を入力してEnter

**実行内容:**
- 自動ログイン設定（pcuser/pcpass）
- ログイン3分後にcamera.bat自動実行
- 毎日09:00に自動再起動
- 音量ミュート
- 画面解像度1920x1080

### ステップ3-2: タスクスケジューラ確認

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "タスクスケジューラの確認..." -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan

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

## ステップ4: ngrok設定ファイル作成

### ステップ4-1: slotserver_ngrok.ini 作成

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ4: ngrok設定ファイルを作成" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan

# slotserver_ngrok.ini の内容
$iniContent = @"
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
"@

# ファイルを作成
Write-Host "C:\serverset\slotserver_ngrok.ini を作成中..." -ForegroundColor Cyan
$iniContent | Out-File -FilePath "C:\serverset\slotserver_ngrok.ini" -Encoding ASCII -Force

# 確認
if (Test-Path "C:\serverset\slotserver_ngrok.ini") {
    Write-Host "✅ slotserver_ngrok.ini を作成しました" -ForegroundColor Green
    Write-Host ""
    Write-Host "内容確認:" -ForegroundColor Cyan
    Get-Content "C:\serverset\slotserver_ngrok.ini" | Select-Object -First 10
} else {
    Write-Host "❌ ファイル作成に失敗しました" -ForegroundColor Red
}
```

### ステップ4-2: start_ngrok.bat 作成（オプション: 手動起動用）

```powershell
# start_ngrok.bat の内容
$batContent = @"
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

REM slotserver_ngrok.ini を使用
copy /Y slotserver_ngrok.ini slotserver.ini

echo.
echo Starting slotserver.exe...
start slotserver.exe

echo.
echo Slotserver started.
pause
"@

# ファイルを作成
Write-Host ""
Write-Host "C:\serverset\start_ngrok.bat を作成中..." -ForegroundColor Cyan
$batContent | Out-File -FilePath "C:\serverset\start_ngrok.bat" -Encoding ASCII -Force

if (Test-Path "C:\serverset\start_ngrok.bat") {
    Write-Host "✅ start_ngrok.bat を作成しました（手動起動用）" -ForegroundColor Green
} else {
    Write-Host "❌ ファイル作成に失敗しました" -ForegroundColor Red
}
```

---

## ステップ5: 接続テスト

### ステップ5-1: API疎通テスト

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ5: 接続テストを実行" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "5-1: API疎通テスト..." -ForegroundColor Cyan

try {
    $apiUrl = "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
    $response = Invoke-WebRequest -Uri $apiUrl -TimeoutSec 10
    $content = $response.Content | ConvertFrom-Json

    Write-Host "✅ API接続成功" -ForegroundColor Green
    Write-Host "レスポンス:" -ForegroundColor Cyan
    Write-Host "  - status: $($content.status)" -ForegroundColor White
    Write-Host "  - machine_no: $($content.machine_no)" -ForegroundColor White
    Write-Host "  - category: $($content.category)" -ForegroundColor White
} catch {
    Write-Host "❌ API接続失敗" -ForegroundColor Red
    Write-Host "エラー: $($_.Exception.Message)" -ForegroundColor Yellow
}
```

### ステップ5-2: PeerJSサーバー疎通テスト

```powershell
Write-Host ""
Write-Host "5-2: PeerJSサーバー疎通テスト..." -ForegroundColor Cyan

try {
    $peerUrl = "https://aimoderation.ngrok-free.app/peerjs/id"
    $response = Invoke-WebRequest -Uri $peerUrl -TimeoutSec 10
    $peerId = $response.Content

    Write-Host "✅ PeerJSサーバー接続成功" -ForegroundColor Green
    Write-Host "Peer ID: $peerId" -ForegroundColor White
} catch {
    Write-Host "❌ PeerJSサーバー接続失敗" -ForegroundColor Red
    Write-Host "エラー: $($_.Exception.Message)" -ForegroundColor Yellow
}
```

### ステップ5-3: カメラ配信ページアクセステスト

```powershell
Write-Host ""
Write-Host "5-3: カメラ配信ページをブラウザで開きます..." -ForegroundColor Cyan
$cameraUrl = "https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01"
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
Write-Host "最終再起動を実行しますか? (Y/N)" -ForegroundColor Yellow
Write-Host "再起動後、以下の動作が自動的に行われます:" -ForegroundColor Cyan
Write-Host "  1. 自動ログイン（pcuser）" -ForegroundColor White
Write-Host "  2. 3分待機" -ForegroundColor White
Write-Host "  3. camera.bat 自動実行" -ForegroundColor White
Write-Host "  4. slotserver.exe 起動" -ForegroundColor White
Write-Host "  5. Chrome自動起動" -ForegroundColor White
Write-Host "  6. カメラ配信開始" -ForegroundColor White
Write-Host ""

$reboot = Read-Host
if ($reboot -eq "Y" -or $reboot -eq "y") {
    Write-Host "60秒後に再起動します..." -ForegroundColor Cyan
    shutdown /r /t 60 /c "セットアップ完了。自動起動テストのため再起動します"
} else {
    Write-Host "手動で再起動してください" -ForegroundColor Yellow
}
```

### ステップ6-2: 再起動後の動作確認（約3分後）

```powershell
Write-Host "実行中のプロセスを確認中..." -ForegroundColor Cyan
$processes = Get-Process | Where-Object { $_.Name -like "*slot*" -or $_.Name -like "*pachi*" -or $_.Name -like "*chrome*" }

if ($processes) {
    Write-Host "✅ プロセスが起動しています:" -ForegroundColor Green
    $processes | Format-Table Name, Id -AutoSize
} else {
    Write-Host "❌ プロセスが起動していません" -ForegroundColor Red
    Write-Host "camera.bat が自動実行されていない可能性があります" -ForegroundColor Yellow
}
```

### ステップ6-3: 完了報告

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "Windows PC側セットアップ完了報告" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green

# 基本情報
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
Write-Host "  - camera.bat: $(if (Test-Path 'C:\serverset\camera.bat') {'✅'} else {'❌'})" -ForegroundColor White

Write-Host ""
Write-Host "【自動化確認】" -ForegroundColor Cyan
$autoRunTask = Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun" -ErrorAction SilentlyContinue
$autoShutdownTask = Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoShutdown" -ErrorAction SilentlyContinue
Write-Host "  - 自動ログイン: ✅" -ForegroundColor White
Write-Host "  - AutoRunタスク: $(if ($autoRunTask) {'✅'} else {'❌'})" -ForegroundColor White
Write-Host "  - AutoShutdownタスク: $(if ($autoShutdownTask) {'✅'} else {'❌'})" -ForegroundColor White

Write-Host ""
Write-Host "【実行中プロセス】" -ForegroundColor Cyan
$slotProcess = Get-Process -Name "slotserver" -ErrorAction SilentlyContinue
$pachiProcess = Get-Process -Name "pachiserver" -ErrorAction SilentlyContinue
$chromeProcess = Get-Process -Name "chrome" -ErrorAction SilentlyContinue
Write-Host "  - slotserver.exe: $(if ($slotProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White
Write-Host "  - pachiserver.exe: $(if ($pachiProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White
Write-Host "  - chrome.exe: $(if ($chromeProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White

Write-Host ""
Write-Host "【視聴確認依頼】" -ForegroundColor Yellow
Write-Host "以下のURLで映像が表示されるか確認をお願いします:" -ForegroundColor Yellow
Write-Host "https://aicrypto.ngrok.dev/play_v2/?NO=1" -ForegroundColor White

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
```

---

## トラブルシューティング

### 問題1: camera.batが自動実行されない

**診断:**
```powershell
Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun"
```

**対処法:**
```powershell
$Action = New-ScheduledTaskAction -Execute "c:\serverset\camera.bat"
$Trigger = New-ScheduledTaskTrigger -AtLogon
$Trigger.Delay = "PT3M"
Register-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun" -Action $Action -Trigger $Trigger -Force | Enable-ScheduledTask
```

### 問題2: API接続エラー

**診断:**
```powershell
curl "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
```

**対処法:**
- ngrokトンネルが起動しているか確認
- INIファイルのURL設定を確認

### 問題3: カメラが認識されない

**診断:**
```powershell
Get-PnpDevice | Where-Object { $_.FriendlyName -like "*camera*" -or $_.FriendlyName -like "*webcam*" }
```

**対処法:**
- Windowsの設定 → プライバシーとセキュリティ → カメラ
- 「アプリがカメラにアクセスできるようにする」をオンに

---

## ✅ 完了チェックリスト

- [ ] ステップ1: 1_Office_AutoRun.bat実行完了
- [ ] 再起動1: PC名変更適用のため再起動
- [ ] ステップ2: 2_Site_AutoRun.bat実行完了
- [ ] 確認: C:\serverset\ディレクトリ＆ファイル確認
- [ ] ステップ3: 3_Last_AutoRun.bat実行完了
- [ ] ステップ4: slotserver_ngrok.ini配置完了
- [ ] ステップ4: start_ngrok.bat作成完了
- [ ] ステップ5: API疎通テスト成功
- [ ] ステップ5: PeerJS疎通テスト成功
- [ ] ステップ5: カメラ配信ページアクセス成功
- [ ] 再起動2: 自動化テストのため再起動
- [ ] 確認: 自動ログイン動作確認
- [ ] 確認: camera.bat自動実行確認（3分後）
- [ ] 確認: slotserver.exe起動確認
- [ ] 確認: Chrome自動起動確認
- [ ] 確認: カメラ映像配信開始確認
- [ ] Mac側: 視聴ページで映像確認

---

**作成日:** 2025-11-05
**システム:** NET8 WebRTC Camera System
**接続:** Railway本番環境 + ngrok トンネル
**対象:** Windows PC（ゲーム機）他の台のインストール
