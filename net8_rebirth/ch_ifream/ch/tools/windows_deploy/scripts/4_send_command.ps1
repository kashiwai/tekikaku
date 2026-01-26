# ============================================
# Net8 コマンド送信スクリプト
# 特定マシンにコマンドを送信
# ============================================

$ErrorActionPreference = "Continue"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RootDir = Split-Path -Parent $ScriptDir
$LogDir = Join-Path $RootDir "logs"
$ConfigFile = Join-Path $RootDir "config\machines.csv"
$LogFile = Join-Path $LogDir ("command_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".txt")

# ログディレクトリ作成
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir | Out-Null
}

function Write-Log {
    param([string]$Message, [string]$Color = "White")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage -ForegroundColor $Color
    Add-Content -Path $LogFile -Value $logMessage
}

function Send-RemoteCommand {
    param(
        [string]$IP,
        [string]$Command
    )

    try {
        $result = Invoke-Command -ComputerName $IP -ScriptBlock {
            param($cmd)
            Invoke-Expression $cmd
        } -ArgumentList $Command -ErrorAction Stop

        return @{
            success = $true
            output = $result
        }
    } catch {
        return @{
            success = $false
            error = $_.Exception.Message
        }
    }
}

# コマンド一覧
$commands = @{
    "1" = @{
        name = "Chrome再起動"
        script = {
            Stop-Process -Name "chrome" -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 2
            Start-Process -FilePath "C:\Net8\start_camera.bat" -WindowStyle Hidden
            return "Chrome restarted"
        }
    }
    "2" = @{
        name = "Chrome停止"
        script = {
            Stop-Process -Name "chrome" -Force -ErrorAction SilentlyContinue
            return "Chrome stopped"
        }
    }
    "3" = @{
        name = "PC再起動"
        script = {
            Restart-Computer -Force
            return "Restarting..."
        }
    }
    "4" = @{
        name = "設定確認"
        script = {
            $config = Get-Content "C:\Net8\config.json" -Raw
            return $config
        }
    }
    "5" = @{
        name = "プロセス一覧"
        script = {
            $procs = Get-Process | Where-Object { $_.ProcessName -match "chrome|net8" } | Select-Object ProcessName, Id, CPU
            return $procs | Format-Table | Out-String
        }
    }
    "6" = @{
        name = "Net8フォルダ内容"
        script = {
            $files = Get-ChildItem "C:\Net8" -ErrorAction SilentlyContinue
            return $files | Format-Table Name, Length, LastWriteTime | Out-String
        }
    }
}

Write-Log "============================================"
Write-Log "Net8 コマンド送信ツール"
Write-Log "============================================"
Write-Log ""

# マシン一覧読み込み
$machines = Import-Csv $ConfigFile

# 対象選択
Write-Host ""
Write-Host "対象を選択してください:" -ForegroundColor Cyan
Write-Host "  A = 全台"
Write-Host "  S = 特定のマシン番号を入力"
Write-Host "  P = 問題があるマシンのみ"
$targetType = Read-Host "選択"

$targetMachines = @()

switch ($targetType.ToUpper()) {
    "A" {
        $targetMachines = $machines
        Write-Log "対象: 全 $($machines.Count) 台"
    }
    "S" {
        Write-Host "マシン番号をカンマ区切りで入力 (例: 1,3,5,10):" -ForegroundColor Cyan
        $nums = (Read-Host "番号") -split ","
        foreach ($num in $nums) {
            $m = $machines | Where-Object { $_.machine_no -eq $num.Trim() }
            if ($m) {
                $targetMachines += $m
            }
        }
        Write-Log "対象: $($targetMachines.Count) 台"
    }
    "P" {
        # オフラインまたはChrome停止中のマシンを検索
        Write-Log "問題マシンを検索中..."
        foreach ($machine in $machines) {
            $pingResult = Test-Connection -ComputerName $machine.ip_address -Count 1 -Quiet -ErrorAction SilentlyContinue
            if (-not $pingResult) {
                $targetMachines += $machine
                continue
            }
            try {
                $chrome = Invoke-Command -ComputerName $machine.ip_address -ScriptBlock {
                    Get-Process -Name "chrome" -ErrorAction SilentlyContinue
                } -ErrorAction Stop
                if (-not $chrome) {
                    $targetMachines += $machine
                }
            } catch {
                $targetMachines += $machine
            }
        }
        Write-Log "問題マシン: $($targetMachines.Count) 台"
    }
}

if ($targetMachines.Count -eq 0) {
    Write-Host "対象マシンがありません" -ForegroundColor Yellow
    exit
}

# コマンド選択
Write-Host ""
Write-Host "コマンドを選択してください:" -ForegroundColor Cyan
foreach ($key in $commands.Keys | Sort-Object) {
    Write-Host "  $key = $($commands[$key].name)"
}
Write-Host "  C = カスタムコマンド"
$cmdChoice = Read-Host "選択"

$scriptToRun = $null
$cmdName = ""

if ($cmdChoice -eq "C" -or $cmdChoice -eq "c") {
    Write-Host "実行するコマンドを入力:" -ForegroundColor Cyan
    $customCmd = Read-Host "コマンド"
    $cmdName = "カスタム: $customCmd"
    $scriptToRun = [ScriptBlock]::Create($customCmd)
} elseif ($commands.ContainsKey($cmdChoice)) {
    $cmdName = $commands[$cmdChoice].name
    $scriptToRun = $commands[$cmdChoice].script
} else {
    Write-Host "無効な選択" -ForegroundColor Red
    exit
}

Write-Log ""
Write-Log "============================================"
Write-Log "実行: $cmdName"
Write-Log "対象: $($targetMachines.Count) 台"
Write-Log "============================================"
Write-Log ""

# 実行確認
Write-Host "実行しますか？ (Y/N)" -ForegroundColor Yellow
$confirm = Read-Host
if ($confirm -ne "Y" -and $confirm -ne "y") {
    Write-Log "キャンセルされました"
    exit
}

# 実行
$results = @()
foreach ($machine in $targetMachines) {
    $machineNo = $machine.machine_no
    $ip = $machine.ip_address
    $modelName = $machine.model_name

    Write-Log "[$machineNo] $ip ($modelName) - 実行中..."

    # Ping確認
    $pingResult = Test-Connection -ComputerName $ip -Count 1 -Quiet -ErrorAction SilentlyContinue
    if (-not $pingResult) {
        Write-Log "  [ERROR] オフライン" "Red"
        $results += @{
            machine_no = $machineNo
            ip = $ip
            model = $modelName
            result = "OFFLINE"
            output = ""
        }
        continue
    }

    try {
        $output = Invoke-Command -ComputerName $ip -ScriptBlock $scriptToRun -ErrorAction Stop
        Write-Log "  [OK] 成功" "Green"
        if ($output) {
            Write-Log "  出力: $output"
        }
        $results += @{
            machine_no = $machineNo
            ip = $ip
            model = $modelName
            result = "SUCCESS"
            output = $output
        }
    } catch {
        Write-Log "  [ERROR] $_" "Red"
        $results += @{
            machine_no = $machineNo
            ip = $ip
            model = $modelName
            result = "ERROR"
            output = $_.Exception.Message
        }
    }
}

# サマリー
Write-Log ""
Write-Log "============================================"
Write-Log "結果サマリー"
Write-Log "============================================"

$successCount = ($results | Where-Object { $_.result -eq "SUCCESS" }).Count
$errorCount = ($results | Where-Object { $_.result -eq "ERROR" }).Count
$offlineCount = ($results | Where-Object { $_.result -eq "OFFLINE" }).Count

Write-Log "  成功: $successCount 台" "Green"
Write-Log "  エラー: $errorCount 台" "Red"
Write-Log "  オフライン: $offlineCount 台" "DarkGray"
Write-Log ""
Write-Log "ログファイル: $LogFile"

# 結果をCSVに出力
$resultCsv = Join-Path $LogDir ("command_result_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".csv")
$results | ForEach-Object {
    [PSCustomObject]@{
        machine_no = $_.machine_no
        ip = $_.ip
        model = $_.model
        result = $_.result
        output = $_.output
    }
} | Export-Csv -Path $resultCsv -NoTypeInformation -Encoding UTF8

Write-Log "結果CSV: $resultCsv"
