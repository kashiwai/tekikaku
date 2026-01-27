@echo off
REM ============================================
REM Net8 状態報告スクリプト
REM 起動時に自動でサーバーに報告
REM ============================================

cd /d "%~dp0"

REM config.jsonからマシン番号を取得
for /f "tokens=2 delims=:," %%a in ('type config.json ^| findstr "machine_no"') do set MACHINE_NO=%%a
set MACHINE_NO=%MACHINE_NO: =%

REM IPアドレス取得
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do set IP_ADDR=%%a
set IP_ADDR=%IP_ADDR: =%

REM MACアドレス取得
for /f "tokens=2 delims=:" %%a in ('getmac /fo csv /nh ^| findstr /i "-"') do set MAC_ADDR=%%a
set MAC_ADDR=%MAC_ADDR:"=%

REM サーバーに報告
curl -X POST "https://mgg-webservice-production.up.railway.app/api/machine_report.php" ^
  -H "Content-Type: application/json" ^
  -d "{\"machine_no\":%MACHINE_NO%,\"ip\":\"%IP_ADDR%\",\"mac\":\"%MAC_ADDR%\",\"status\":\"online\"}"

echo 報告完了: Machine %MACHINE_NO% / IP: %IP_ADDR%
