# ============================================
# Net8 Windows PowerShell Agent
# Pull型エージェント - 定期的にサーバーからコマンドを取得・実行
# ============================================

param(
    [string]$ConfigPath = "C:\Net8\agent_config.json",
    [switch]$Install,
    [switch]$Uninstall,
    [switch]$Status
)

$ErrorActionPreference = "Continue"

# ============================================
# 設定
# ============================================
$DefaultConfig = @{
    ServerUrl = "https://mgg-webservice-production.up.railway.app"
    ApiKey = "agent_dev_key_2024"
    AgentId = ""
    PollIntervalSeconds = 10
    LogPath = "C:\Net8\agent.log"
    MaxLogSizeMB = 10
}

# ============================================
# ログ関数
# ============================================
function Write-AgentLog {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logLine = "[$timestamp] [$Level] $Message"
    Write-Host $logLine

    if ($script:Config -and $script:Config.LogPath) {
        try {
            # ログローテーション
            if (Test-Path $script:Config.LogPath) {
                $logFile = Get-Item $script:Config.LogPath
                if ($logFile.Length -gt ($script:Config.MaxLogSizeMB * 1MB)) {
                    $backupPath = $script:Config.LogPath + ".old"
                    Move-Item -Path $script:Config.LogPath -Destination $backupPath -Force
                }
            }
            Add-Content -Path $script:Config.LogPath -Value $logLine
        } catch {}
    }
}

# ============================================
# 設定読み込み
# ============================================
function Get-AgentConfig {
    if (Test-Path $ConfigPath) {
        try {
            $config = Get-Content $ConfigPath -Raw | ConvertFrom-Json
            # デフォルト値とマージ
            $merged = $DefaultConfig.Clone()
            $config.PSObject.Properties | ForEach-Object {
                $merged[$_.Name] = $_.Value
            }
            return $merged
        } catch {
            Write-AgentLog "Config parse error: $_" "ERROR"
        }
    }
    return $DefaultConfig
}

# ============================================
# エージェントID取得
# ============================================
function Get-AgentId {
    # config.json から machine_no を取得
    $net8Config = "C:\Net8\config.json"
    if (Test-Path $net8Config) {
        try {
            $cfg = Get-Content $net8Config -Raw | ConvertFrom-Json
            if ($cfg.machine_no) {
                return "CAMERA-001-{0:D4}" -f [int]$cfg.machine_no
            }
        } catch {}
    }

    # コンピュータ名をフォールバック
    return $env:COMPUTERNAME
}

# ============================================
# システム情報取得
# ============================================
function Get-SystemInfo {
    $ipAddress = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike "127.*" } | Select-Object -First 1).IPAddress
    $macAddress = (Get-NetAdapter | Where-Object { $_.Status -eq "Up" } | Select-Object -First 1).MacAddress

    return @{
        ip = $ipAddress
        mac = $macAddress
        hostname = $env:COMPUTERNAME
    }
}

# ============================================
# API呼び出し
# ============================================
function Invoke-AgentApi {
    param(
        [string]$Endpoint,
        [string]$Method = "GET",
        [hashtable]$Body = @{},
        [hashtable]$Query = @{}
    )

    $url = "$($script:Config.ServerUrl)$Endpoint"

    # クエリパラメータ追加
    if ($Query.Count -gt 0) {
        $queryString = ($Query.GetEnumerator() | ForEach-Object { "$($_.Key)=$([uri]::EscapeDataString($_.Value))" }) -join "&"
        $url += "?$queryString"
    }

    $headers = @{
        "Authorization" = "Bearer $($script:Config.ApiKey)"
        "Content-Type" = "application/json"
    }

    try {
        if ($Method -eq "GET") {
            $response = Invoke-RestMethod -Uri $url -Method Get -Headers $headers -TimeoutSec 30
        } else {
            $jsonBody = $Body | ConvertTo-Json -Compress
            $response = Invoke-RestMethod -Uri $url -Method Post -Headers $headers -Body $jsonBody -TimeoutSec 30
        }
        return $response
    } catch {
        Write-AgentLog "API Error: $_" "ERROR"
        return $null
    }
}

# ============================================
# コマンド取得
# ============================================
function Get-PendingCommand {
    $sysInfo = Get-SystemInfo

    $query = @{
        agentId = $script:AgentId
        ip = $sysInfo.ip
        mac = $sysInfo.mac
        hostname = $sysInfo.hostname
    }

    $response = Invoke-AgentApi -Endpoint "/api/agent/pull.php" -Query $query

    if ($response -and $response.success -and $response.command) {
        return @{
            CommandId = $response.commandId
            Command = $response.command
        }
    }

    return $null
}

# ============================================
# コマンド実行
# ============================================
function Invoke-AgentCommand {
    param(
        [int]$CommandId,
        [string]$Command
    )

    Write-AgentLog "Executing: $Command"

    $startTime = Get-Date
    $output = ""
    $exitCode = 0

    try {
        # PowerShellでコマンド実行
        $result = Invoke-Expression $Command 2>&1 | Out-String
        $output = $result
        $exitCode = $LASTEXITCODE
        if ($null -eq $exitCode) { $exitCode = 0 }
    } catch {
        $output = "ERROR: $_"
        $exitCode = 1
    }

    $endTime = Get-Date
    $executionTimeMs = [int](($endTime - $startTime).TotalMilliseconds)

    Write-AgentLog "Completed in ${executionTimeMs}ms, exit code: $exitCode"

    return @{
        CommandId = $CommandId
        Output = $output
        ExitCode = $exitCode
        ExecutionTimeMs = $executionTimeMs
    }
}

# ============================================
# 結果送信
# ============================================
function Send-CommandResult {
    param(
        [int]$CommandId,
        [string]$Output,
        [int]$ExitCode,
        [int]$ExecutionTimeMs
    )

    $sysInfo = Get-SystemInfo

    $body = @{
        agentId = $script:AgentId
        commandId = $CommandId
        output = $Output
        exitCode = $ExitCode
        executionTimeMs = $ExecutionTimeMs
        ip = $sysInfo.ip
        mac = $sysInfo.mac
        hostname = $sysInfo.hostname
    }

    $response = Invoke-AgentApi -Endpoint "/api/agent/result.php" -Method "POST" -Body $body

    if ($response -and $response.success) {
        Write-AgentLog "Result sent successfully"
        return $true
    } else {
        Write-AgentLog "Failed to send result" "ERROR"
        return $false
    }
}

# ============================================
# メインループ
# ============================================
function Start-AgentLoop {
    Write-AgentLog "=========================================="
    Write-AgentLog "Net8 Agent Starting"
    Write-AgentLog "Agent ID: $script:AgentId"
    Write-AgentLog "Server: $($script:Config.ServerUrl)"
    Write-AgentLog "Poll Interval: $($script:Config.PollIntervalSeconds)s"
    Write-AgentLog "=========================================="

    while ($true) {
        try {
            # コマンド取得
            $cmd = Get-PendingCommand

            if ($cmd) {
                Write-AgentLog "Received command ID: $($cmd.CommandId)"

                # コマンド実行
                $result = Invoke-AgentCommand -CommandId $cmd.CommandId -Command $cmd.Command

                # 結果送信
                Send-CommandResult `
                    -CommandId $result.CommandId `
                    -Output $result.Output `
                    -ExitCode $result.ExitCode `
                    -ExecutionTimeMs $result.ExecutionTimeMs
            }
        } catch {
            Write-AgentLog "Loop error: $_" "ERROR"
        }

        Start-Sleep -Seconds $script:Config.PollIntervalSeconds
    }
}

# ============================================
# サービスインストール
# ============================================
function Install-Agent {
    Write-Host "Installing Net8 Agent..."

    # 設定ファイル作成
    $config = @{
        ServerUrl = "https://mgg-webservice-production.up.railway.app"
        ApiKey = "agent_dev_key_2024"
        PollIntervalSeconds = 10
        LogPath = "C:\Net8\agent.log"
    }

    $configJson = $config | ConvertTo-Json
    $configJson | Out-File -FilePath $ConfigPath -Encoding UTF8

    # スケジュールタスク作成
    $action = New-ScheduledTaskAction `
        -Execute "powershell.exe" `
        -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$PSCommandPath`""

    $trigger = New-ScheduledTaskTrigger -AtStartup

    $principal = New-ScheduledTaskPrincipal `
        -UserId "SYSTEM" `
        -LogonType ServiceAccount `
        -RunLevel Highest

    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -RestartCount 3 `
        -RestartInterval (New-TimeSpan -Minutes 1)

    Register-ScheduledTask `
        -TaskName "Net8Agent" `
        -Action $action `
        -Trigger $trigger `
        -Principal $principal `
        -Settings $settings `
        -Force

    Write-Host "Installation complete!"
    Write-Host "Task 'Net8Agent' created."
    Write-Host "Starting agent..."

    Start-ScheduledTask -TaskName "Net8Agent"
}

# ============================================
# サービスアンインストール
# ============================================
function Uninstall-Agent {
    Write-Host "Uninstalling Net8 Agent..."

    Stop-ScheduledTask -TaskName "Net8Agent" -ErrorAction SilentlyContinue
    Unregister-ScheduledTask -TaskName "Net8Agent" -Confirm:$false -ErrorAction SilentlyContinue

    Write-Host "Uninstallation complete!"
}

# ============================================
# ステータス確認
# ============================================
function Get-AgentStatus {
    Write-Host "Net8 Agent Status"
    Write-Host "=========================================="

    $task = Get-ScheduledTask -TaskName "Net8Agent" -ErrorAction SilentlyContinue

    if ($task) {
        Write-Host "Task Status: $($task.State)"
        $taskInfo = Get-ScheduledTaskInfo -TaskName "Net8Agent"
        Write-Host "Last Run: $($taskInfo.LastRunTime)"
        Write-Host "Next Run: $($taskInfo.NextRunTime)"
    } else {
        Write-Host "Task Status: Not Installed"
    }

    if (Test-Path $ConfigPath) {
        $config = Get-Content $ConfigPath -Raw | ConvertFrom-Json
        Write-Host "Server: $($config.ServerUrl)"
    }

    $agentId = Get-AgentId
    Write-Host "Agent ID: $agentId"
}

# ============================================
# メイン
# ============================================

# 設定読み込み
$script:Config = Get-AgentConfig
$script:AgentId = Get-AgentId

# オプション処理
if ($Install) {
    Install-Agent
    exit 0
}

if ($Uninstall) {
    Uninstall-Agent
    exit 0
}

if ($Status) {
    Get-AgentStatus
    exit 0
}

# 通常実行（エージェントループ開始）
Start-AgentLoop
