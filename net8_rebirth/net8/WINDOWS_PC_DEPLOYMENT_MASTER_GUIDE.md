# Windows PC デプロイメント完全ガイド

## 🎯 目的

Windows PC上でNET8カメラ配信システムのクライアント側を完全自動セットアップし、Mac側サーバーとngrok経由で接続する。

**作成日:** 2025-10-23
**対象:** Windows PC側Claude Code
**前提:** Mac側ngrokトンネル起動済み

---

## 📊 システムアーキテクチャ概要

```
┌─────────────────────────────────────────────────────────────┐
│                      インターネット                          │
│                                                             │
│  ┌──────────────────┐              ┌──────────────────┐   │
│  │   Mac Server     │              │   Windows PC     │   │
│  │   (別のWAN)      │              │   (別のWAN)      │   │
│  ├──────────────────┤              ├──────────────────┤   │
│  │ Docker:          │              │ カメラPC:        │   │
│  │ - web:8080       │◄─────────────┤ - slotserver.exe │   │
│  │ - db:3306        │   ngrok      │ - camera.bat     │   │
│  │ - signaling:59000│   tunnels    │ - Chrome         │   │
│  ├──────────────────┤              ├──────────────────┤   │
│  │ ngrok:           │              │ 物理接続:        │   │
│  │ aicrypto.        │              │ - RS-232C        │   │
│  │ ngrok.dev        │              │ - USBカメラ      │   │
│  │                  │              │ - スロット実機   │   │
│  └──────────────────┘              └──────────────────┘   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ ngrok Tunnels:                                       │  │
│  │ https://aicrypto.ngrok.dev → Mac:8080 (Web/API)     │  │
│  │ https://aimoderation.ngrok-free.app → Mac:59000     │  │
│  │                                     (PeerJS Signaling)│  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 必要なファイル一覧

### Mac側から転送するファイル

#### 1. セットアップスクリプト（修正版）
```
camera_localpcsetup/ngrok_modified_setup/
├── 1_Office_AutoRun_ngrok.bat         # 初期セットアップ（修正版）
├── 2_Site_AutoRun_ngrok.bat           # アプリインストール（修正版）
├── slotserver_ngrok.ini               # ngrok用設定ファイル
├── start_ngrok.bat                    # 手動起動用バッチ
└── README_ngrok_setup.md              # セットアップ手順書
```

#### 2. 元のセットアップリソース（必要に応じて）
```
camera_localpcsetup/カメラ端末設置ファイル/01.セットアップ用ファイル/WorksetClientSetup_ja_local/
├── 3_Last_AutoRun.bat                 # 自動化設定（そのまま使用）
├── 4_DisableUpdate_AutoRun.bat        # 更新無効化（そのまま使用）
├── *.ps1                              # PowerShellスクリプト群
├── setupapp.exe                       # メインインストーラー（34MB）
├── GoogleChromeStandaloneEnterprise64.msi
└── その他必要なファイル
```

#### 3. 実行プログラム（オプション: setupapp.exeに含まれる）
```
server_localpc/serverset/
├── slotserver.exe                     # スロットサーバー（47MB）
├── pachiserver.exe                    # パチンコサーバー（47MB）
├── chromeCameraV2.exe                 # Chrome起動プログラム
├── camera_ctrl.exe                    # カメラ制御
├── getcategory.exe                    # 機種判定
├── updatefilesV2.exe                  # 更新チェック
├── camera.bat                         # 起動スクリプト
└── camera.ini                         # カメラ設定
```

---

## 🚀 セットアップ手順（Windows PC側）

### 前提条件チェック

```powershell
# Windows バージョン確認
systeminfo | findstr /B /C:"OS Name" /C:"OS Version"

# 管理者権限で実行しているか確認
net session >nul 2>&1
if %errorlevel% == 0 (echo 管理者権限OK) else (echo 管理者権限で実行してください)

# インターネット接続確認
ping 8.8.8.8 -n 2
curl https://aicrypto.ngrok.dev
```

### ステップ1: 初期セットアップ

**実行:** `1_Office_AutoRun_ngrok.bat`（管理者権限）

**入力情報:**
- カメラ端末番号: `1`
- ホスト番号: `1`

**実行内容（8ステップ）:**

| # | 処理内容 | 実行スクリプト | 確認方法 |
|---|---------|--------------|---------|
| 1 | Windows Update無効化 | DisableWindowsUpdate.ps1 | レジストリキー確認 |
| 2 | タイムゾーン設定 | TimeZone.ps1 Tokyo | `tzutil /g` |
| 3 | 電源管理（スリープ無効） | PowerControl.bat | `powercfg /query` |
| 4 | PC名変更 | RenameComputer.ps1 | `hostname` |
| 5 | 固定IP設定スキップ | ~~FixedIPAddress.ps1~~ | スキップ |
| 6 | ネットワークPrivate設定 | UpdatePrivateNetwork.ps1 | `Get-NetConnectionProfile` |
| 7 | リモートデスクトップ有効化 | RemoteDesktop.ps1 | `Get-ItemProperty HKLM:\System\CurrentControlSet\Control\"Terminal Server"` |
| 8 | Chrome自動インストール | ChromeLocalInstall.bat | `chrome --version` |

**完了後:**
```batch
再起動が必要です。
PC名が CAMERA-001-0001 に変更されています。
```

**再起動実行:**
```powershell
shutdown /r /t 60 /c "セットアップ完了。60秒後に再起動します"
```

### ステップ2: アプリケーションインストール

**実行:** `2_Site_AutoRun_ngrok.bat`（再起動後、管理者権限）

**入力情報:**
- ホスト番号: `1`

**実行内容:**
```batch
1. DOMAIN=aicrypto.ngrok.dev を設定
2. Net8AppInstall.bat を実行
   └→ setupapp.exe を実行
       └→ C:\serverset\ にインストール
```

**確認:**
```powershell
# インストール先確認
dir C:\serverset

# 期待されるファイル:
# - slotserver.exe (47MB)
# - pachiserver.exe (47MB)
# - camera.bat
# - chromeCameraV2.exe
# - camera_ctrl.exe
# - getcategory.exe
# - updatefilesV2.exe
# - camera.ini
# - slotserver.ini (テンプレート)
```

**トラブルシューティング:**
```powershell
# setupapp.exeが見つからない場合
ls *.exe | Where-Object { $_.Name -like "*setup*" }

# インストールログ確認
type C:\serverset\install.log
```

### ステップ3: 自動化設定

**実行:** `3_Last_AutoRun.bat`（元のファイル、管理者権限）

**入力情報:**
- ユーザー名: `pcuser`
- パスワード: `pcpass`
- シャットダウン時刻: `09:00:00`

**実行内容:**

| 処理 | スクリプト | 詳細 |
|-----|-----------|------|
| 自動ログイン | Net8AutoLogin.ps1 | pcuser/pcpassで自動ログイン |
| 自動起動 | Net8AutoLogin.ps1 | ログイン3分後にcamera.bat実行 |
| 自動シャットダウン | Net8AutoShutdown.ps1 | 毎日09:00に再起動 |
| 音量ミュート | MuteVolume.ps1 | システム音量0%＆ミュート |
| 画面解像度 | Set-ScreenResolution_1920-1080.ps1 | 1920x1080固定 |

**タスクスケジューラ確認:**
```powershell
# 登録されたタスク確認
Get-ScheduledTask -TaskPath "\Net8\"

# 期待される結果:
# TaskName: AutoRun
# State: Ready
# Triggers: AtLogon, Delay PT3M (3分遅延)

# TaskName: AutoShutdown
# State: Ready
# Triggers: Daily, At 09:00:00
```

**レジストリ確認（自動ログイン）:**
```powershell
Get-ItemProperty "HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon" | Select AutoAdminLogon, DefaultUsername
```

### ステップ4: ngrok設定ファイルの配置

#### 4-1: slotserver_ngrok.ini の配置

**場所:** `C:\serverset\slotserver_ngrok.ini`

**Claude Codeで実行:**
```
C:\serverset\slotserver_ngrok.ini を以下の内容で作成してください：

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
```

#### 4-2: start_ngrok.bat の作成（オプション: 手動起動用）

**場所:** `C:\serverset\start_ngrok.bat`

**Claude Codeで実行:**
```
C:\serverset\start_ngrok.bat を以下の内容で作成してください：

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
```

#### 4-3: 設定ファイルの検証

**PowerShellで確認:**
```powershell
# ファイル存在確認
Test-Path C:\serverset\slotserver_ngrok.ini
Test-Path C:\serverset\start_ngrok.bat

# 内容確認
Get-Content C:\serverset\slotserver_ngrok.ini

# APIセクション確認
Select-String -Path C:\serverset\slotserver_ngrok.ini -Pattern "aicrypto.ngrok.dev"
```

### ステップ5: 接続テスト（再起動前）

#### 5-1: API疎通テスト

```powershell
# Mac側サーバーへの接続テスト
$response = Invoke-WebRequest -Uri "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
$response.Content

# 期待されるレスポンス:
# {"status":"ok","machine_no":1,"category":1,"leavetime":180,...}
```

#### 5-2: PeerJSサーバー疎通テスト

```powershell
# PeerJSシグナリングサーバー確認
Invoke-WebRequest -Uri "https://aimoderation.ngrok-free.app/peerjs/id"

# 期待される結果: ランダムなPeer ID（例: "abc123xyz456"）
```

#### 5-3: カメラ配信ページアクセステスト

**ブラウザで確認:**
```
https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01
```

**期待される動作:**
- ✅ ページが正常に表示される
- ✅ カメラアクセス許可ダイアログが表示される
- ✅ ブラウザコンソールにエラーがない（F12で確認）

### ステップ6: 最終再起動と動作確認

#### 再起動実行

```powershell
shutdown /r /t 60 /c "セットアップ完了。自動起動テストのため再起動します"
```

#### 再起動後の自動動作フロー

```
1. ⏱️  Windows起動
2. 🔐 自動ログイン（pcuser）
3. ⏳ 3分待機
4. 🚀 camera.bat自動実行
   │
   ├─ 📝 updatefiles_bk.exe（更新チェック）
   │   └→ サーバーから最新ファイルをダウンロード（あれば）
   │
   ├─ 🎥 camera_ctrl.exe（カメラ制御初期化）
   │   └→ カメラデバイスの初期化
   │
   ├─ 🔍 getcategory.exe（機種カテゴリ取得）
   │   └→ slotserver.ini読み込み → API呼び出し
   │   └→ category=1 → pachiserver.exe起動
   │   └→ category=2 → slotserver.exe起動
   │
   ├─ 🎰 slotserver.exe起動（カテゴリ=2の場合）
   │   ├→ slotserver.ini読み込み
   │   ├→ API呼び出し（machine_no取得）
   │   ├→ シリアルポート制御開始
   │   └→ ログ出力
   │
   └─ 🌐 chromeCameraV2.exe（10秒後）
       ├→ Chrome起動
       ├→ https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01
       ├→ カメラアクセス許可
       ├→ PeerJS接続（aimoderation.ngrok-free.app）
       └→ 映像配信開始
```

#### 動作確認チェックリスト

```powershell
# ===== プロセス確認 =====
Get-Process | Where-Object { $_.Name -like "*slot*" -or $_.Name -like "*pachi*" -or $_.Name -like "*chrome*" }

# 期待される結果:
# - slotserver.exe または pachiserver.exe が起動している
# - chrome.exe が起動している

# ===== タスクマネージャー確認 =====
tasklist | findstr /i "slotserver pachiserver chrome"

# ===== コンソールウィンドウ確認 =====
# slotserver.exeのコンソールが表示されているか
# エラーメッセージがないか
```

#### ブラウザ確認（Windows PC側）

**Chrome起動確認:**
- ✅ Chromeが自動的に開いている
- ✅ URL: `https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01`
- ✅ カメラアクセス許可ダイアログが表示される
- ✅ カメラのプレビュー映像が表示される

**デベロッパーツール確認（F12）:**
```javascript
// Consoleタブで確認
// 期待されるログ:
// - PeerJS: Connected to server
// - PeerJS: Listening on peer: camera-XXX-XXX
// - MediaStream: Camera started
```

**Networkタブ確認:**
- ✅ WebSocket接続: `wss://aimoderation.ngrok-free.app/peerjs/...`
- ✅ Status: 101 Switching Protocols（WebSocket確立）

### ステップ7: Mac側で視聴確認

**Mac側ブラウザでアクセス:**
```
https://aicrypto.ngrok.dev/play_v2/?NO=1
```

**期待される動作:**
- ✅ Windows PCのカメラ映像が表示される
- ✅ 映像が滑らかに再生される（30fps目標）
- ✅ 遅延が許容範囲内（<2秒）
- ✅ 音声も聞こえる（カメラにマイクがある場合）

**デベロッパーツール確認:**
```javascript
// Consoleタブ
// 期待されるログ:
// - PeerJS: Connecting to peer: camera-XXX-XXX
// - PeerJS: Connection established
// - MediaStream: Remote stream received
```

---

## 🔧 トラブルシューティング

### 問題1: camera.batが自動実行されない

**症状:**
再起動後、slotserver.exeのコンソールが表示されない

**原因:**
- タスクスケジューラに登録されていない
- Net8AutoLogin.ps1が正しく実行されていない

**診断:**
```powershell
# タスク確認
Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun"

# タスクが存在しない場合のエラー:
# Get-ScheduledTask : No MSFT_ScheduledTask objects found...
```

**対処法:**
```powershell
# 手動でタスク登録
$Action = New-ScheduledTaskAction -Execute "c:\serverset\camera.bat"
$Trigger = New-ScheduledTaskTrigger -AtLogon
$Trigger.Delay = "PT3M"
Register-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun" -Action $Action -Trigger $Trigger -Force | Enable-ScheduledTask

# 再起動してテスト
shutdown /r /t 60
```

### 問題2: slotserver.exeが起動するがすぐに終了する

**症状:**
コンソールウィンドウが一瞬表示されて消える

**原因:**
- slotserver.ini が存在しない
- INIファイルの内容が不正

**診断:**
```powershell
# INIファイル確認
Test-Path C:\serverset\slotserver.ini

# slotserver.iniが存在しない場合
dir C:\serverset\*.ini
```

**対処法:**
```batch
cd C:\serverset
copy /Y slotserver_ngrok.ini slotserver.ini
type slotserver.ini

# 手動実行してエラーメッセージ確認
slotserver.exe
```

### 問題3: API接続エラー

**症状:**
slotserver.exeのコンソールに「API接続失敗」

**原因:**
- Mac側ngrokトンネルが停止している
- INIファイルのURL設定が間違っている
- インターネット接続が切れている

**診断:**
```powershell
# API疎通確認
curl "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="

# タイムアウトまたはエラーの場合
ping aicrypto.ngrok.dev

# DNS解決確認
nslookup aicrypto.ngrok.dev

# INIファイルのURL確認
Get-Content C:\serverset\slotserver.ini | Select-String "aicrypto"
```

**対処法（Mac側）:**
```bash
# ngrokプロセス確認
ps aux | grep ngrok

# ngrok再起動
ngrok http 8080 --domain=aicrypto.ngrok.dev

# 別ターミナル
ngrok http 59000 --domain=aimoderation.ngrok-free.app
```

### 問題4: PeerJS接続エラー

**症状:**
Chromeのコンソールに「PeerJS connection failed」

**原因:**
- PeerJSシグナリングサーバーが停止
- ngrokトンネルが切れている
- ファイアウォールがWebSocketをブロック

**診断:**
```powershell
# PeerJSサーバー疎通確認
curl https://aimoderation.ngrok-free.app/peerjs/id

# WebSocket接続テスト（Chrome DevTools）
# F12 → Console → Network → WS タブ
# wss://aimoderation.ngrok-free.app/peerjs/... を確認
```

**対処法:**
```powershell
# ファイアウォール確認
Get-NetFirewallProfile | Select Name, Enabled

# Private プロファイルの確認
Get-NetConnectionProfile

# NetworkCategory が Public の場合、Private に変更
Get-NetConnectionProfile | Set-NetConnectionProfile -NetworkCategory Private

# Chrome再起動
taskkill /IM chrome.exe /F
C:\serverset\chromeCameraV2.exe
```

### 問題5: カメラが認識されない

**症状:**
Chrome起動後、「カメラが見つかりません」

**原因:**
- カメラドライバが未インストール
- Windowsのカメラプライバシー設定
- 他のアプリがカメラを占有

**診断:**
```powershell
# カメラデバイス確認
Get-PnpDevice | Where-Object { $_.FriendlyName -like "*camera*" -or $_.FriendlyName -like "*webcam*" }

# 期待される結果:
# Status: OK
# Class: Camera

# カメラプライバシー設定確認
Get-ItemProperty "HKCU:\Software\Microsoft\Windows\CurrentVersion\CapabilityAccessManager\ConsentStore\webcam"
```

**対処法:**
```powershell
# Windowsの設定でカメラアクセスを許可
# 設定 → プライバシーとセキュリティ → カメラ
# 「アプリがカメラにアクセスできるようにする」をオンに

# デバイスマネージャーで確認
devmgmt.msc

# カメラデバイスを右クリック → 「デバイスのアンインストール」
# PC再起動（自動的に再インストール）
shutdown /r /t 60
```

### 問題6: 固定IP設定を誤って実行してしまった

**症状:**
インターネット接続が切れた

**原因:**
誤って元の `1_Office_AutoRun.bat` を実行し、FixedIPAddress.ps1が実行された

**診断:**
```powershell
# ネットワーク設定確認
Get-NetIPAddress | Where-Object { $_.AddressFamily -eq "IPv4" }

# IPが 192.168.11.100 系になっている場合、固定IP設定されている
```

**対処法:**
```powershell
# DHCP に戻す
$adapter = Get-NetAdapter | Where-Object { $_.Status -eq "Up" }
Set-NetIPInterface -InterfaceAlias $adapter.Name -Dhcp Enabled
Set-DnsClientServerAddress -InterfaceAlias $adapter.Name -ResetServerAddresses

# ネットワーク再起動
Restart-NetAdapter -Name $adapter.Name

# 接続確認
ping 8.8.8.8
curl https://aicrypto.ngrok.dev
```

---

## 📊 システム動作確認マトリックス

### 完全チェックリスト

| カテゴリ | 確認項目 | 確認コマンド | 期待される結果 |
|---------|---------|------------|--------------|
| **OS設定** | Windows Update無効化 | `Get-ItemProperty HKLM:\SOFTWARE\Policies\Microsoft\Windows\WindowsUpdate\AU` | NoAutoUpdate=1 |
| | タイムゾーン | `tzutil /g` | Tokyo Standard Time |
| | 電源管理 | `powercfg /query` | スリープ・休止=0 |
| | PC名 | `hostname` | CAMERA-001-0001 |
| | ネットワークカテゴリ | `Get-NetConnectionProfile` | NetworkCategory: Private |
| | リモートデスクトップ | `Get-ItemProperty HKLM:\System\CurrentControlSet\Control\"Terminal Server"` | fDenyTSConnections=0 |
| **アプリ** | Chrome | `chrome --version` | Google Chrome XXX |
| | serversetディレクトリ | `Test-Path C:\serverset` | True |
| | slotserver.exe | `Test-Path C:\serverset\slotserver.exe` | True |
| | camera.bat | `Test-Path C:\serverset\camera.bat` | True |
| **設定** | slotserver_ngrok.ini | `Test-Path C:\serverset\slotserver_ngrok.ini` | True |
| | ngrok URL設定 | `Select-String -Path C:\serverset\slotserver_ngrok.ini -Pattern "aicrypto.ngrok.dev"` | 複数行ヒット |
| **自動化** | 自動ログイン | `Get-ItemProperty HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon` | AutoAdminLogon=1 |
| | AutoRunタスク | `Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun"` | State: Ready |
| | AutoShutdownタスク | `Get-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoShutdown"` | State: Ready |
| **ネットワーク** | インターネット接続 | `ping 8.8.8.8` | Reply from 8.8.8.8 |
| | API疎通 | `curl https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=...` | {"status":"ok",...} |
| | PeerJS疎通 | `curl https://aimoderation.ngrok-free.app/peerjs/id` | ランダムID |
| **実行中** | slotserver.exe | `Get-Process slotserver` | プロセス存在 |
| | Chrome | `Get-Process chrome` | プロセス存在 |
| **配信** | カメラ映像 | Mac側で `https://aicrypto.ngrok.dev/play_v2/?NO=1` にアクセス | 映像表示 |

---

## 🎓 補足情報

### camera.bat の実行フロー詳細

```batch
echo off
cd /d %~dp0

# 1. 更新チェック
copy updatefilesV2.exe updatefiles_bk.exe
echo File Update Check...
updatefiles_bk.exe
# → slotserver.iniのPatchServerセクションを参照
# → 新しいEXEファイルがあればダウンロード

# 2. カメラ制御初期化
echo Server Start...
camera_ctrl.exe
# → カメラデバイスの初期化
# → camera.iniの読み込み

# 3. 機種カテゴリ取得
getcategory.exe
# → slotserver.iniのAPIセクションを参照
# → https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=...
# → category を取得（1=パチンコ, 2=スロット）
# → errorlevel に category を設定

# 4. カテゴリに応じたサーバー起動
if %errorlevel% == 1 start /min pachiserver.exe
if %errorlevel% == 2 start /min slotserver.exe
# → /min: 最小化起動
# → コンソールウィンドウは表示されるが最小化

# 5. 待機
timeout 10 /nobreak >nul
# → 10秒待機（サーバー起動完了を待つ）

# 6. Chrome起動
echo Chrome start...
chromeCameraV2.exe
# → slotserver.iniのChromeセクションを参照
# → https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01
# → Chromeを起動してカメラ配信ページを開く
```

### slotserver.exe の動作詳細

```
slotserver.exe 起動
    ↓
slotserver.ini 読み込み
    ↓
[License]セクション検証
    ↓
[API]セクション → API呼び出し
    https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=...
    ↓
レスポンス取得: {"status":"ok","machine_no":1,"category":2,...}
    ↓
シリアルポート制御開始（RS-232C）
    ↓
[Monitor]セクション → WebSocket接続（将来実装）
    wss://aicrypto.ngrok.dev/ws
    ↓
コンソールログ出力:
    - API接続成功
    - マシン番号: 1
    - カテゴリ: 2（スロット）
    - シリアルポート: COM3
    ↓
待機ループ（マシン制御コマンド受信待ち）
```

### chromeCameraV2.exe の動作詳細

```
chromeCameraV2.exe 起動
    ↓
slotserver.ini 読み込み
    ↓
[Chrome]セクション → URL取得
    https://aicrypto.ngrok.dev/server_v2/
    ↓
MACアドレス取得（ネットワークアダプタから）
    例: 00:15:5D:XX:XX:XX
    ↓
URL構築
    https://aicrypto.ngrok.dev/server_v2/?MAC=00:15:5D:XX:XX:XX
    ↓
Chrome起動
    chrome.exe --app=<URL> --kiosk --start-fullscreen
    ↓
カメラ配信ページ読み込み
    ↓
JavaScript実行:
    1. PeerJS初期化
       peer = new Peer(cameraid, {
           host: "aimoderation.ngrok-free.app",
           port: 443,
           secure: true
       })
    2. カメラアクセス
       navigator.mediaDevices.getUserMedia({video: true, audio: true})
    3. WebRTC接続確立
       peer.on('call', (call) => { call.answer(stream); })
    ↓
配信開始（視聴者接続待ち）
```

---

## 📞 サポート情報

### ログ収集方法

**問題発生時に収集すべき情報:**

```powershell
# 1. システム情報
systeminfo > C:\serverset\logs\systeminfo.txt

# 2. ネットワーク情報
ipconfig /all > C:\serverset\logs\ipconfig.txt
Get-NetConnectionProfile > C:\serverset\logs\network_profile.txt

# 3. タスクスケジューラ
Get-ScheduledTask -TaskPath "\Net8\" > C:\serverset\logs\scheduled_tasks.txt

# 4. 実行中のプロセス
Get-Process > C:\serverset\logs\processes.txt

# 5. slotserver.iniの内容（機密情報注意）
Get-Content C:\serverset\slotserver.ini > C:\serverset\logs\slotserver_ini.txt

# 6. イベントログ（直近100件）
Get-EventLog -LogName Application -Newest 100 > C:\serverset\logs\event_log.txt
```

### リモートサポート用コマンド

**Remote Desktop接続情報:**
```powershell
# RDP接続可能か確認
Get-ItemProperty -Path "HKLM:\System\CurrentControlSet\Control\Terminal Server" -Name "fDenyTSConnections"

# IPアドレス確認
(Get-NetIPAddress | Where-Object { $_.AddressFamily -eq "IPv4" -and $_.PrefixOrigin -eq "Dhcp" }).IPAddress

# ファイアウォール確認
Get-NetFirewallRule -DisplayName "*Remote Desktop*" | Where-Object { $_.Enabled -eq $true }
```

---

## ✅ 最終チェックリスト

### セットアップ完了確認

- [ ] **ステップ1:** 1_Office_AutoRun_ngrok.bat実行完了
- [ ] **再起動1:** PC名変更適用のため再起動
- [ ] **ステップ2:** 2_Site_AutoRun_ngrok.bat実行完了
- [ ] **確認:** C:\serverset\ディレクトリ＆ファイル確認
- [ ] **ステップ3:** 3_Last_AutoRun.bat実行完了
- [ ] **ステップ4:** slotserver_ngrok.ini配置完了
- [ ] **ステップ4:** start_ngrok.bat作成完了
- [ ] **ステップ5:** API疎通テスト成功
- [ ] **ステップ5:** PeerJS疎通テスト成功
- [ ] **ステップ5:** カメラ配信ページアクセス成功
- [ ] **再起動2:** 自動化テストのため再起動
- [ ] **確認:** 自動ログイン動作確認
- [ ] **確認:** camera.bat自動実行確認（3分後）
- [ ] **確認:** slotserver.exe起動確認
- [ ] **確認:** Chrome自動起動確認
- [ ] **確認:** カメラ映像配信開始確認
- [ ] **Mac側:** 視聴ページで映像確認

### 運用確認

- [ ] **毎日09:00:** 自動再起動動作確認
- [ ] **再起動後:** 自動ログイン→camera.bat実行確認
- [ ] **長時間稼働:** 24時間連続稼働テスト
- [ ] **再接続:** ネットワーク切断→再接続テスト
- [ ] **Mac側:** ngrokトンネル再起動後の動作確認

---

**作成日:** 2025-10-23
**対象:** Windows PC側Claude Code
**バージョン:** 1.0
**システム:** NET8 WebRTC Camera System
**ngrok:** aicrypto.ngrok.dev / aimoderation.ngrok-free.app
