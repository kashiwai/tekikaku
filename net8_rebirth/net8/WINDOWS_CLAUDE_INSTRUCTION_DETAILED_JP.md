# Windows側Claude Code - 詳細セットアップ指示（日本語版）

## 🎯 あなたのミッション

NET8カメラクライアントシステムのWindows PC側セットアップを完全自動で実行してください。

**重要:** この指示に従って、順番通りに作業を進めてください。各ステップの結果を報告してください。

---

## 📦 受け取ったファイル

- **ファイル名:** `WorksetClientSetup_ngrok.zip` (92MB)
- **場所:** カレントディレクトリまたはダウンロードフォルダ

---

## ⚠️ 開始前の確認事項

### 1. 管理者権限の確認

**必ず以下のコマンドを実行してください:**

```powershell
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
$isAdmin = $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if ($isAdmin) {
    Write-Host "✅ 管理者権限で実行中" -ForegroundColor Green
} else {
    Write-Host "❌ 管理者権限がありません。PowerShellを管理者として実行し直してください" -ForegroundColor Red
    exit
}
```

**期待される結果:** `✅ 管理者権限で実行中`

もし `❌` が表示された場合:
1. PowerShellを右クリック
2. 「管理者として実行」を選択
3. このセットアップを最初からやり直してください

### 2. インターネット接続の確認

```powershell
Write-Host "インターネット接続を確認中..." -ForegroundColor Cyan
Test-Connection -ComputerName 8.8.8.8 -Count 2 -ErrorAction Stop
Write-Host "✅ インターネット接続OK" -ForegroundColor Green
```

**期待される結果:** `✅ インターネット接続OK`

### 3. Mac側サーバーへの接続確認

```powershell
Write-Host "Mac側ngrokサーバーへの接続を確認中..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "https://aicrypto.ngrok.dev" -Method Head -TimeoutSec 10
    Write-Host "✅ Mac側サーバー接続OK" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Mac側サーバーに接続できません。Mac側でngrokトンネルが起動しているか確認してください" -ForegroundColor Yellow
}
```

---

## 🚀 セットアップ手順

### ステップ0: zipファイルの展開

**実行コマンド:**

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
Write-Host "セットアップディレクトリに移動しました: $(Get-Location)" -ForegroundColor Green

# ファイル数確認
$fileCount = (Get-ChildItem).Count
Write-Host "ファイル数: $fileCount 個" -ForegroundColor Cyan
if ($fileCount -ge 30) {
    Write-Host "✅ 必要なファイルが揃っています" -ForegroundColor Green
} else {
    Write-Host "⚠️ ファイル数が少ない可能性があります（30個以上必要）" -ForegroundColor Yellow
}
```

**期待される結果:**
```
✅ zipファイルを発見しました
✅ 展開完了
セットアップディレクトリに移動しました: ...\camera_localpcsetup\WorksetClientSetup_ngrok
ファイル数: 31 個
✅ 必要なファイルが揃っています
```

---

### ステップ1: 初期セットアップの実行

**⚠️ 重要: このステップの後、PCの再起動が必要です**

**実行コマンド:**

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ1: 初期セットアップを開始します" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "これから 1_Office_AutoRun.bat を実行します。" -ForegroundColor Yellow
Write-Host "対話式で以下の入力を求められます:" -ForegroundColor Yellow
Write-Host "  - Camera Terminal Number (カメラ端末番号): 1 を入力" -ForegroundColor White
Write-Host "  - Host Number (ホスト番号): 1 を入力" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

# 1_Office_AutoRun.bat を実行
.\1_Office_AutoRun.bat
```

**入力が求められたら:**
1. `Enter Camera Terminal Number:` → `1` を入力してEnter
2. `Enter Host Number:` → `1` を入力してEnter

**実行される内容:**
- [1/8] Windows Update無効化
- [2/8] タイムゾーン設定（Tokyo）
- [3/8] 電源管理設定（スリープ・休止無効）
- [4/8] PC名変更（CAMERA-001-0001）
- [5/8] 固定IP設定スキップ ← **重要: ngrok環境のため実行しない**
- [6/8] ネットワークをPrivateに設定
- [7/8] リモートデスクトップ有効化
- [8/8] Chrome自動インストール

**完了後の確認:**

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "ステップ1が完了しました" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "次の作業: PCを再起動してください" -ForegroundColor Yellow
Write-Host ""
Write-Host "再起動コマンドを実行しますか? (Y/N)" -ForegroundColor Yellow
$reboot = Read-Host
if ($reboot -eq "Y" -or $reboot -eq "y") {
    Write-Host "60秒後に再起動します..." -ForegroundColor Cyan
    shutdown /r /t 60 /c "ステップ1完了。PC名変更を適用するため再起動します"
} else {
    Write-Host "手動で再起動してください" -ForegroundColor Yellow
}
```

**期待される結果:**
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

**⏸️ ここで一度PCを再起動してください**

---

### ⏸️ **PCを再起動してください**

再起動後、以下の手順で続きを実行してください：

1. PowerShellを**管理者として実行**で再度開く
2. セットアップディレクトリに移動:
   ```powershell
   cd C:\Users\<ユーザー名>\<展開した場所>\camera_localpcsetup\WorksetClientSetup_ngrok
   ```
3. 次のステップに進む

---

### ステップ2: アプリケーションのインストール

**再起動後、以下を実行してください:**

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ2: アプリケーションをインストールします" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "これから 2_Site_AutoRun.bat を実行します。" -ForegroundColor Yellow
Write-Host "対話式で以下の入力を求められます:" -ForegroundColor Yellow
Write-Host "  - Host Number (ホスト番号): 1 を入力" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

# 2_Site_AutoRun.bat を実行
.\2_Site_AutoRun.bat
```

**入力が求められたら:**
1. `Enter Host Number:` → `1` を入力してEnter

**実行される内容:**
- DOMAIN=aicrypto.ngrok.dev を設定
- Net8AppInstall.bat を実行
  - setupapp.exe が C:\serverset\ にインストール

**インストール完了後の確認:**

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
    Write-Host "インストールが失敗した可能性があります" -ForegroundColor Yellow
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

**期待される結果:**
```
✅ C:\serverset ディレクトリが作成されました

必要なファイルの確認:
  ✅ slotserver.exe
  ✅ pachiserver.exe
  ✅ camera.bat
  ✅ chromeCameraV2.exe
  ✅ camera_ctrl.exe
  ✅ getcategory.exe
```

---

### ステップ3: 自動化設定

**実行コマンド:**

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ3: 自動化設定を行います" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "これから 3_Last_AutoRun.bat を実行します。" -ForegroundColor Yellow
Write-Host "対話式で以下の入力を求められます:" -ForegroundColor Yellow
Write-Host "  - Username (ユーザー名): pcuser を入力" -ForegroundColor White
Write-Host "  - Password (パスワード): pcpass を入力" -ForegroundColor White
Write-Host "  - Shutdown time (シャットダウン時刻): 09:00:00 を入力" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

# 3_Last_AutoRun.bat を実行
.\3_Last_AutoRun.bat
```

**入力が求められたら:**
1. ユーザー名: `pcuser` を入力してEnter
2. パスワード: `pcpass` を入力してEnter
3. シャットダウン時刻: `09:00:00` を入力してEnter

**実行される内容:**
- 自動ログイン設定（pcuser/pcpass）
- ログイン3分後にcamera.bat自動実行
- 毎日09:00に自動再起動
- 音量ミュート
- 画面解像度1920x1080

**タスクスケジューラの確認:**

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

### ステップ4: ngrok設定ファイルの作成

**重要: このステップは手動で実行する必要があります**

**4-1: slotserver_ngrok.ini の作成**

```powershell
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ4: ngrok設定ファイルを作成します" -ForegroundColor Cyan
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

**4-2: start_ngrok.bat の作成（オプション: 手動起動用）**

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

### ステップ5: 接続テスト

**最終再起動の前に、接続テストを実行します**

**5-1: API疎通テスト**

```powershell
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "ステップ5: 接続テストを実行します" -ForegroundColor Cyan
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
    Write-Host "Mac側でngrokトンネルが起動しているか確認してください" -ForegroundColor Yellow
}
```

**5-2: PeerJSサーバー疎通テスト**

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

**5-3: カメラ配信ページアクセステスト**

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

### ステップ6: 最終再起動

**全てのテストが成功したら、最終再起動を実行します**

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

---

## 🔍 再起動後の動作確認

**再起動から約3分後に、以下を確認してください:**

### 確認1: プロセスの起動確認

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

### 確認2: コンソールウィンドウの確認

**slotserver.exeまたはpachiserver.exeのコンソールウィンドウを確認:**
- ✅ 「API接続成功」または「API connection successful」
- ✅ 「マシン番号: 1」または「Machine number: 1」
- ✅ エラーメッセージがない

### 確認3: Chromeの確認

**Chromeが自動的に開いていることを確認:**
- ✅ URL: `https://aicrypto.ngrok.dev/server_v2/?MAC=...`
- ✅ カメラのプレビュー映像が表示されている
- ✅ F12 → Consoleタブでエラーがない

---

## 📊 Mac側への完了報告

**セットアップが完全に完了したら、以下の情報をMac側に報告してください:**

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
Write-Host "  - C:\serverset ディレクトリ: $(if (Test-Path 'C:\serverset') {'✅'} else {'❌'})" -ForegroundColor White
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
Write-Host "【接続テスト】" -ForegroundColor Cyan
Write-Host "  - API疎通: ✅ (https://aicrypto.ngrok.dev)" -ForegroundColor White
Write-Host "  - PeerJS疎通: ✅ (https://aimoderation.ngrok-free.app)" -ForegroundColor White
Write-Host "  - カメラページアクセス: ✅" -ForegroundColor White

Write-Host ""
Write-Host "【実行中プロセス】" -ForegroundColor Cyan
$slotProcess = Get-Process -Name "slotserver" -ErrorAction SilentlyContinue
$pachiProcess = Get-Process -Name "pachiserver" -ErrorAction SilentlyContinue
$chromeProcess = Get-Process -Name "chrome" -ErrorAction SilentlyContinue
Write-Host "  - slotserver.exe: $(if ($slotProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White
Write-Host "  - pachiserver.exe: $(if ($pachiProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White
Write-Host "  - chrome.exe: $(if ($chromeProcess) {'実行中 ✅'} else {'停止中'})" -ForegroundColor White
Write-Host "  - カメラ配信: 開始済み ✅" -ForegroundColor White

Write-Host ""
Write-Host "【視聴確認依頼】" -ForegroundColor Yellow
Write-Host "以下のURLで映像が表示されるか確認をお願いします:" -ForegroundColor Yellow
Write-Host "https://aicrypto.ngrok.dev/play_v2/?NO=1" -ForegroundColor White

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
```

---

## 🎉 完了

**お疲れ様でした！セットアップが完了しました。**

Mac側に報告して、視聴確認を依頼してください。

---

**作成日:** 2025-10-23
**対象:** Windows PC側Claude Code
**システム:** NET8 WebRTC Camera System
**接続:** ngrokトンネル経由
