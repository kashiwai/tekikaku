# ============================================
# Net8 全台状態確認スクリプト
# 全PCの状態をテキストで出力
# ============================================

$ErrorActionPreference = "Continue"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RootDir = Split-Path -Parent $ScriptDir
$LogDir = Join-Path $RootDir "logs"
$ConfigFile = Join-Path $RootDir "config\machines.csv"
$LogFile = Join-Path $LogDir ("status_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".txt")

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

function Get-MachineStatus {
    param([string]$IP)

    $result = @{
        ping = $false
        chrome = $false
        net8_folder = $false
        error = ""
    }

    # Ping確認
    $pingResult = Test-Connection -ComputerName $IP -Count 1 -Quiet -ErrorAction SilentlyContinue
    $result.ping = $pingResult

    if (-not $pingResult) {
        $result.error = "PC_OFFLINE"
        return $result
    }

    try {
        # リモートで詳細確認
        $remoteResult = Invoke-Command -ComputerName $IP -ScriptBlock {
            $status = @{
                chrome = $false
                net8_folder = $false
                chrome_pid = 0
                uptime = ""
            }

            # Chromeプロセス確認
            $chrome = Get-Process -Name "chrome" -ErrorAction SilentlyContinue
            if ($chrome) {
                $status.chrome = $true
                $status.chrome_pid = $chrome[0].Id
            }

            # Net8フォルダ確認
            $status.net8_folder = Test-Path "C:\Net8"

            # PC稼働時間
            $os = Get-WmiObject -Class Win32_OperatingSystem
            $uptime = (Get-Date) - $os.ConvertToDateTime($os.LastBootUpTime)
            $status.uptime = "{0}d {1}h {2}m" -f $uptime.Days, $uptime.Hours, $uptime.Minutes

            return $status
        } -ErrorAction Stop

        $result.chrome = $remoteResult.chrome
        $result.net8_folder = $remoteResult.net8_folder
        $result.chrome_pid = $remoteResult.chrome_pid
        $result.uptime = $remoteResult.uptime

    } catch {
        $result.error = "REMOTE_ERROR: $_"
    }

    return $result
}

Write-Log "============================================"
Write-Log "Net8 全台状態確認"
Write-Log "============================================"
Write-Log ""

# マシン一覧読み込み
$machines = Import-Csv $ConfigFile
$total = $machines.Count

Write-Log "対象マシン: $total 台"
Write-Log ""
Write-Log "確認中..."
Write-Log ""

$allResults = @()
$online = 0
$offline = 0
$running = 0
$stopped = 0

foreach ($machine in $machines) {
    $machineNo = $machine.machine_no
    $hostname = $machine.hostname
    $ip = $machine.ip_address
    $modelName = $machine.model_name

    $status = Get-MachineStatus -IP $ip

    # ステータス判定
    $pcStatus = if ($status.ping) { "ON " } else { "OFF" }
    $chromeStatus = if ($status.chrome) { "RUN" } else { "---" }
    $folderStatus = if ($status.net8_folder) { "OK" } else { "--" }

    # カウント
    if ($status.ping) { $online++ } else { $offline++ }
    if ($status.chrome) { $running++ } else { $stopped++ }

    # 表示色
    $color = "White"
    if (-not $status.ping) {
        $color = "DarkGray"
    } elseif ($status.chrome) {
        $color = "Green"
    } else {
        $color = "Yellow"
    }

    $line = "[$machineNo] $pcStatus | Chrome:$chromeStatus | Folder:$folderStatus | $modelName"
    if ($status.uptime) {
        $line += " | Up:$($status.uptime)"
    }
    if ($status.error) {
        $line += " | ERR:$($status.error)"
    }

    Write-Log $line $color

    $allResults += @{
        machine_no = $machineNo
        ip = $ip
        model = $modelName
        pc_online = $status.ping
        chrome_running = $status.chrome
        net8_folder = $status.net8_folder
        uptime = $status.uptime
        error = $status.error
    }
}

# サマリー
Write-Log ""
Write-Log "============================================"
Write-Log "サマリー"
Write-Log "============================================"
Write-Log ""
Write-Log "  PC状態:"
Write-Log "    オンライン:  $online 台"
Write-Log "    オフライン:  $offline 台"
Write-Log ""
Write-Log "  Chrome状態:"
Write-Log "    起動中:      $running 台"
Write-Log "    停止中:      $stopped 台"
Write-Log ""
Write-Log "============================================"
Write-Log "ログファイル: $LogFile"

# 結果をCSVに出力
$resultCsv = Join-Path $LogDir ("status_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".csv")
$allResults | ForEach-Object {
    [PSCustomObject]@{
        machine_no = $_.machine_no
        ip = $_.ip
        model = $_.model
        pc_online = $_.pc_online
        chrome_running = $_.chrome_running
        net8_folder = $_.net8_folder
        uptime = $_.uptime
        error = $_.error
    }
} | Export-Csv -Path $resultCsv -NoTypeInformation -Encoding UTF8

Write-Log "結果CSV: $resultCsv"

# 問題があるマシンを表示
$problemMachines = $allResults | Where-Object { -not $_.pc_online -or -not $_.chrome_running }
if ($problemMachines.Count -gt 0) {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Red
    Write-Host "問題のあるマシン ($($problemMachines.Count)台)" -ForegroundColor Red
    Write-Host "============================================" -ForegroundColor Red
    foreach ($m in $problemMachines) {
        $reason = if (-not $m.pc_online) { "PC OFF" } else { "Chrome停止" }
        Write-Host "  [$($m.machine_no)] $($m.ip) - $reason" -ForegroundColor Red
    }
}
