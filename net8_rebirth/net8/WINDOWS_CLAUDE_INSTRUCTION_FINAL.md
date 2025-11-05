# Windows Claude Code - 実行指示書

以下の作業を順番に実行してください。

## タスク1: Windows PCのハードウェア情報からcd値を生成

以下のPowerShellスクリプトを作成して実行してください：

ファイル名: `C:\serverset\generate_cd.ps1`

```powershell
# Windows PC固有のcd値を生成するスクリプト

Write-Host "================================================"
Write-Host "NET8 License CD Value Generator"
Write-Host "================================================"
Write-Host ""

# MACアドレス取得
Write-Host "Step 1: Getting MAC Address..."
$adapter = Get-NetAdapter | Where-Object {$_.Status -eq "Up"} | Select-Object -First 1
$macRaw = $adapter.MacAddress
Write-Host "  Raw MAC: $macRaw"

# MACアドレスをハイフン区切りの小文字に変換（例: aa-bb-cc-dd-ee-ff）
$macFormatted = $macRaw.Replace("-","").Replace(":","")
$macBytes = $macFormatted -split '(.{2})' | Where-Object {$_}
$macFormatted = ($macBytes -join '-').ToLower()
Write-Host "  Formatted MAC: $macFormatted"

# シリアルナンバー取得
Write-Host ""
Write-Host "Step 2: Getting Serial Number..."
$serial = (Get-WmiObject -Class Win32_ComputerSystemProduct).IdentifyingNumber
Write-Host "  Serial: $serial"

# 結合文字列
Write-Host ""
Write-Host "Step 3: Combining..."
$combined = "$macFormatted$serial"
Write-Host "  Combined String: $combined"

# SHA256ハッシュ計算
Write-Host ""
Write-Host "Step 4: Calculating SHA256 hash..."
$bytes = [System.Text.Encoding]::UTF8.GetBytes($combined)
$sha256 = [System.Security.Cryptography.SHA256]::Create()
$hashBytes = $sha256.ComputeHash($bytes)
$cd = [System.BitConverter]::ToString($hashBytes).Replace("-","").ToLower()

Write-Host ""
Write-Host "=========================================="
Write-Host "GENERATED CD VALUE:"
Write-Host $cd
Write-Host "=========================================="
Write-Host ""

# cd値を返す
return $cd
```

実行方法：
```powershell
cd C:\serverset
$generatedCD = .\generate_cd.ps1
Write-Host "CD Value: $generatedCD"
```

## タスク2: 生成したcd値でiniファイルを作成

上記で生成した$generatedCD を使って、slotserver.ini を作成してください：

```powershell
cd C:\serverset

# タスク1で生成したcd値を使用
# ここに $generatedCD = .\generate_cd.ps1 の結果が入っている前提

# slotserver.iniを作成
$iniContent = @"
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = $generatedCD
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

# UTF-8でファイルに書き込み（BOM無し）
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText("C:\serverset\slotserver.ini", $iniContent, $utf8NoBom)

Write-Host "slotserver.ini created successfully!"
Write-Host ""

# 内容確認
Write-Host "File contents:"
Get-Content slotserver.ini
```

## タスク3: slotserver.exeを起動して動作確認

```powershell
cd C:\serverset
.\slotserver.exe
```

期待される出力：
```
version 1.4.8 - Remove logic check NG License
start slotserver.py
MAC:XX-XX-XX-XX-XX-XX
url:https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=...
API connection...
Machine No: 1
Category: 1
Chrome starting...
```

## まとめ：一連の流れを自動実行

上記をすべて自動で実行するマスタースクリプト：

ファイル名: `C:\serverset\setup_license.ps1`

```powershell
# NET8 License Setup Script

Write-Host "================================================"
Write-Host "NET8 Camera Client - License Setup"
Write-Host "================================================"
Write-Host ""

# Step 1: cd値生成
Write-Host "[1/3] Generating CD value..."
Write-Host ""

$adapter = Get-NetAdapter | Where-Object {$_.Status -eq "Up"} | Select-Object -First 1
$macRaw = $adapter.MacAddress
$macFormatted = $macRaw.Replace("-","").Replace(":","")
$macBytes = $macFormatted -split '(.{2})' | Where-Object {$_}
$macFormatted = ($macBytes -join '-').ToLower()

$serial = (Get-WmiObject -Class Win32_ComputerSystemProduct).IdentifyingNumber
$combined = "$macFormatted$serial"

$bytes = [System.Text.Encoding]::UTF8.GetBytes($combined)
$sha256 = [System.Security.Cryptography.SHA256]::Create()
$hashBytes = $sha256.ComputeHash($bytes)
$cd = [System.BitConverter]::ToString($hashBytes).Replace("-","").ToLower()

Write-Host "MAC Address: $macFormatted"
Write-Host "Serial Number: $serial"
Write-Host "Generated CD: $cd"
Write-Host ""

# Step 2: iniファイル作成
Write-Host "[2/3] Creating slotserver.ini..."
Write-Host ""

$iniContent = @"
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = $cd
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

$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText("C:\serverset\slotserver.ini", $iniContent, $utf8NoBom)

Write-Host "slotserver.ini created!"
Write-Host ""
Write-Host "File contents:"
Write-Host "----------------------------------------"
Get-Content slotserver.ini
Write-Host "----------------------------------------"
Write-Host ""

# Step 3: 起動テスト
Write-Host "[3/3] Starting slotserver.exe..."
Write-Host ""
Write-Host "Press Ctrl+C to stop if needed..."
Write-Host ""

Start-Sleep -Seconds 2

.\slotserver.exe
```

## 実行方法

Windows Claude Codeで以下を実行してください：

1. 上記の `setup_license.ps1` を作成
2. 実行：
   ```powershell
   cd C:\serverset
   .\setup_license.ps1
   ```

これで自動的に：
- ✅ Windows PCのハードウェア情報を取得
- ✅ cd値を生成
- ✅ slotserver.iniを作成
- ✅ slotserver.exeを起動

すべて完了します。

## エラーが出た場合

実行結果の全文をMac側に報告してください。
