# ============================================
# Net8 一括配布スクリプト
# 全PCにプログラムとバッチファイルを配布
# ============================================

$ErrorActionPreference = "Continue"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RootDir = Split-Path -Parent $ScriptDir
$LogDir = Join-Path $RootDir "logs"
$ClientDir = Join-Path $RootDir "client"
$ConfigFile = Join-Path $RootDir "config\machines.csv"
$LogFile = Join-Path $LogDir ("deploy_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".txt")

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

Write-Log "============================================"
Write-Log "Net8 一括配布 開始"
Write-Log "============================================"

# マシン一覧読み込み
$machines = Import-Csv $ConfigFile
$total = $machines.Count
$success = 0
$failed = 0

Write-Log "対象マシン: $total 台"
Write-Log ""

foreach ($machine in $machines) {
    $machineNo = $machine.machine_no
    $hostname = $machine.hostname
    $ip = $machine.ip_address
    $modelName = $machine.model_name

    Write-Log "--------------------------------------------"
    Write-Log "[$machineNo] $hostname ($ip) - $modelName"

    # 接続テスト
    $pingResult = Test-Connection -ComputerName $ip -Count 1 -Quiet -ErrorAction SilentlyContinue

    if (-not $pingResult) {
        Write-Log "  [ERROR] 接続失敗 - PCがオフラインです"
        $failed++
        continue
    }

    Write-Log "  [OK] 接続確認"

    try {
        # 配布先フォルダ
        $destPath = "\\$ip\C$\Net8"

        # フォルダ作成
        if (-not (Test-Path $destPath)) {
            New-Item -ItemType Directory -Path $destPath -Force | Out-Null
            Write-Log "  [OK] フォルダ作成: C:\Net8"
        }

        # ファイルコピー
        Copy-Item -Path "$ClientDir\*" -Destination $destPath -Recurse -Force
        Write-Log "  [OK] ファイルコピー完了"

        # 設定ファイルにマシン番号を書き込み
        $configPath = Join-Path $destPath "config.json"
        $config = @{
            machine_no = [int]$machineNo
            camera_no = [int]$machineNo
            server_url = "https://mgg-webservice-production.up.railway.app"
            model_name = $modelName
        } | ConvertTo-Json
        $config | Out-File -FilePath $configPath -Encoding UTF8
        Write-Log "  [OK] 設定ファイル生成"

        $success++
        Write-Log "  [DONE] 配布完了"

    } catch {
        Write-Log "  [ERROR] $_"
        $failed++
    }
}

Write-Log ""
Write-Log "============================================"
Write-Log "配布完了"
Write-Log "  成功: $success 台"
Write-Log "  失敗: $failed 台"
Write-Log "============================================"
Write-Log "ログファイル: $LogFile"

Write-Host ""
Write-Host "完了しました。ログを確認してください。"
Write-Host "次のステップ: .\2_start_batch.ps1 で起動"
