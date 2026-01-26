# ============================================
# Net8 マシン情報収集スクリプト
# 現在接続中のマシンのIP/MAC/ホスト名を収集
# ============================================

$ErrorActionPreference = "Continue"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RootDir = Split-Path -Parent $ScriptDir
$OutputFile = Join-Path $RootDir "config\discovered_machines.csv"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Net8 マシン情報収集ツール" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ネットワーク範囲を入力
Write-Host "スキャンするネットワーク範囲を入力してください"
Write-Host "例: 192.168.1 (192.168.1.1-254をスキャン)"
$networkPrefix = Read-Host "ネットワーク"

if (-not $networkPrefix) {
    $networkPrefix = "192.168.1"
}

Write-Host ""
Write-Host "スキャン範囲: ${networkPrefix}.1 - ${networkPrefix}.254"
Write-Host "スキャン中... (数分かかります)"
Write-Host ""

$results = @()
$found = 0

# 並列でPingスキャン
$jobs = @()
for ($i = 1; $i -le 254; $i++) {
    $ip = "${networkPrefix}.$i"
    $jobs += Start-Job -ScriptBlock {
        param($targetIP)
        $ping = Test-Connection -ComputerName $targetIP -Count 1 -Quiet -ErrorAction SilentlyContinue
        if ($ping) {
            return $targetIP
        }
        return $null
    } -ArgumentList $ip
}

# 結果を収集
Write-Host "Pingスキャン完了待ち..."
$activeIPs = $jobs | Wait-Job | Receive-Job | Where-Object { $_ -ne $null }
$jobs | Remove-Job

Write-Host "アクティブなIP: $($activeIPs.Count) 件"
Write-Host ""

# 各アクティブIPの詳細情報を取得
foreach ($ip in $activeIPs) {
    Write-Host "[$ip] 情報取得中..." -NoNewline

    $machineInfo = @{
        ip_address = $ip
        hostname = ""
        mac_address = ""
        os_version = ""
        chrome_installed = $false
    }

    try {
        # ホスト名取得
        $dns = [System.Net.Dns]::GetHostEntry($ip)
        $machineInfo.hostname = $dns.HostName
    } catch {
        $machineInfo.hostname = "Unknown"
    }

    try {
        # MACアドレス取得（ARPテーブルから）
        $arp = arp -a $ip 2>$null | Select-String $ip
        if ($arp) {
            $mac = ($arp -split '\s+' | Where-Object { $_ -match '^[0-9a-f]{2}(-[0-9a-f]{2}){5}$' })
            if ($mac) {
                $machineInfo.mac_address = $mac.ToUpper()
            }
        }
    } catch {}

    try {
        # リモートで追加情報取得（WinRM有効の場合のみ）
        $remoteInfo = Invoke-Command -ComputerName $ip -ScriptBlock {
            $info = @{
                os = (Get-WmiObject -Class Win32_OperatingSystem).Caption
                chrome = Test-Path "C:\Program Files\Google\Chrome\Application\chrome.exe"
            }
            return $info
        } -ErrorAction Stop

        $machineInfo.os_version = $remoteInfo.os
        $machineInfo.chrome_installed = $remoteInfo.chrome
        Write-Host " [WinRM OK]" -ForegroundColor Green
    } catch {
        Write-Host " [WinRM NG]" -ForegroundColor Yellow
    }

    $results += $machineInfo
    $found++
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "検出結果: $found 台" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 結果表示
$results | ForEach-Object {
    $chromeStatus = if ($_.chrome_installed) { "[Chrome OK]" } else { "" }
    Write-Host "$($_.ip_address) | $($_.hostname) | $($_.mac_address) $chromeStatus"
}

# CSVに保存
$results | ForEach-Object {
    [PSCustomObject]@{
        machine_no = ""
        hostname = $_.hostname
        ip_address = $_.ip_address
        mac_address = $_.mac_address
        os_version = $_.os_version
        chrome_installed = $_.chrome_installed
        model_name = ""
        status = ""
    }
} | Export-Csv -Path $OutputFile -NoTypeInformation -Encoding UTF8

Write-Host ""
Write-Host "結果を保存しました: $OutputFile" -ForegroundColor Green
Write-Host ""
Write-Host "次のステップ:"
Write-Host "1. $OutputFile を開いて machine_no と model_name を入力"
Write-Host "2. machines.csv にコピーまたはリネーム"
Write-Host "3. 各マシンでIPアドレスを固定設定"
