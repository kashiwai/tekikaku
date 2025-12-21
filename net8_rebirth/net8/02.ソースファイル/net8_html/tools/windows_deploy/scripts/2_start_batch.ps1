# ============================================
# Net8 3台ずつ起動スクリプト
# 3台起動 → 確認 → 次の3台...
# ============================================

$ErrorActionPreference = "Continue"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RootDir = Split-Path -Parent $ScriptDir
$LogDir = Join-Path $RootDir "logs"
$ConfigFile = Join-Path $RootDir "config\machines.csv"
$LogFile = Join-Path $LogDir ("start_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".txt")

# 設定
$BatchSize = 3  # 一度に起動する台数
$WaitSeconds = 10  # 起動後の待機秒数

# ログディレクトリ作成
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir | Out-Null
}

function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage
    Add-Content -Path $LogFile -Value $logMessage
}

function Start-RemoteCamera {
    param([string]$IP, [int]$MachineNo)

    try {
        # リモートでバッチファイル実行
        $result = Invoke-Command -ComputerName $IP -ScriptBlock {
            # カメラサーバー起動
            $batPath = "C:\Net8\start_camera.bat"
            if (Test-Path $batPath) {
                Start-Process -FilePath $batPath -WindowStyle Hidden
                return "OK"
            } else {
                return "BAT_NOT_FOUND"
            }
        } -ErrorAction Stop

        return $result
    } catch {
        return "ERROR: $_"
    }
}

function Check-CameraStatus {
    param([string]$IP)

    try {
        $result = Invoke-Command -ComputerName $IP -ScriptBlock {
            # Chromeプロセス確認
            $chrome = Get-Process -Name "chrome" -ErrorAction SilentlyContinue
            if ($chrome) {
                return "RUNNING"
            } else {
                return "NOT_RUNNING"
            }
        } -ErrorAction Stop

        return $result
    } catch {
        return "UNREACHABLE"
    }
}

Write-Log "============================================"
Write-Log "Net8 3台ずつ起動 開始"
Write-Log "============================================"

# マシン一覧読み込み
$machines = Import-Csv $ConfigFile
$total = $machines.Count
$batchCount = [math]::Ceiling($total / $BatchSize)

Write-Log "対象マシン: $total 台"
Write-Log "バッチサイズ: $BatchSize 台"
Write-Log "バッチ数: $batchCount 回"
Write-Log ""

$currentBatch = 0
$allResults = @()

for ($i = 0; $i -lt $total; $i += $BatchSize) {
    $currentBatch++
    $batchMachines = $machines[$i..([Math]::Min($i + $BatchSize - 1, $total - 1))]

    Write-Log "============================================"
    Write-Log "バッチ $currentBatch / $batchCount"
    Write-Log "============================================"

    # このバッチのマシンを起動
    $batchResults = @()

    foreach ($machine in $batchMachines) {
        $machineNo = $machine.machine_no
        $ip = $machine.ip_address
        $modelName = $machine.model_name

        Write-Log "  [$machineNo] $ip ($modelName) - 起動中..."

        $startResult = Start-RemoteCamera -IP $ip -MachineNo $machineNo

        if ($startResult -eq "OK") {
            Write-Log "    [OK] 起動コマンド送信"
        } else {
            Write-Log "    [ERROR] $startResult"
        }

        $batchResults += @{
            machine_no = $machineNo
            ip = $ip
            model = $modelName
            start_result = $startResult
        }
    }

    # 待機
    Write-Log ""
    Write-Log "  $WaitSeconds 秒待機中..."
    Start-Sleep -Seconds $WaitSeconds

    # 状態確認
    Write-Log ""
    Write-Log "  状態確認:"
    foreach ($result in $batchResults) {
        $status = Check-CameraStatus -IP $result.ip

        $statusIcon = switch ($status) {
            "RUNNING" { "[OK]" }
            "NOT_RUNNING" { "[NG]" }
            default { "[??]" }
        }

        Write-Log "    [$($result.machine_no)] $statusIcon $status"
        $result.status = $status
        $allResults += $result
    }

    Write-Log ""

    # 次のバッチへ進むか確認（最後のバッチ以外）
    if ($currentBatch -lt $batchCount) {
        Write-Host ""
        Write-Host "次の $BatchSize 台を起動しますか？ (Y/N/A)" -ForegroundColor Yellow
        Write-Host "  Y = 次へ進む"
        Write-Host "  N = ここで終了"
        Write-Host "  A = 残り全て自動で実行"
        $input = Read-Host "選択"

        if ($input -eq "N" -or $input -eq "n") {
            Write-Log "ユーザーにより中断"
            break
        } elseif ($input -eq "A" -or $input -eq "a") {
            Write-Log "残り全て自動実行モード"
            # 以降は確認なしで続行
        }
    }
}

# 最終結果
Write-Log ""
Write-Log "============================================"
Write-Log "最終結果"
Write-Log "============================================"

$running = ($allResults | Where-Object { $_.status -eq "RUNNING" }).Count
$notRunning = ($allResults | Where-Object { $_.status -eq "NOT_RUNNING" }).Count
$unreachable = ($allResults | Where-Object { $_.status -eq "UNREACHABLE" }).Count

Write-Log "  起動成功: $running 台"
Write-Log "  起動失敗: $notRunning 台"
Write-Log "  接続不可: $unreachable 台"
Write-Log ""
Write-Log "ログファイル: $LogFile"

# 結果をCSVに出力
$resultCsv = Join-Path $LogDir ("result_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".csv")
$allResults | ForEach-Object {
    [PSCustomObject]@{
        machine_no = $_.machine_no
        ip = $_.ip
        model = $_.model
        start_result = $_.start_result
        status = $_.status
    }
} | Export-Csv -Path $resultCsv -NoTypeInformation -Encoding UTF8

Write-Log "結果CSV: $resultCsv"
