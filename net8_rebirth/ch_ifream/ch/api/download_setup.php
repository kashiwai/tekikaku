<?php
/**
 * セットアップスクリプトをダウンロード
 * PCでワンライナーを実行するだけでセットアップ完了
 */

$machine_no = intval($_GET['no'] ?? 0);

if ($machine_no <= 0 || $machine_no > 100) {
    die("Error: Invalid machine number");
}

$server_url = "https://mgg-webservice-production.up.railway.app";

// BATファイルを生成
$bat_content = <<<BAT
@echo off
REM ============================================
REM Net8 Auto Setup - Machine $machine_no
REM ============================================

echo Setting up Machine $machine_no...

if not exist "C:\\Net8" mkdir "C:\\Net8"

echo Creating config.json...
(
echo {
echo     "machine_no": $machine_no,
echo     "camera_no": $machine_no,
echo     "server_url": "$server_url",
echo     "model_name": ""
echo }
) > "C:\\Net8\\config.json"

echo Creating report_status.bat...
(
echo @echo off
echo cd /d "%%~dp0"
echo for /f "tokens=2 delims=:," %%%%a in ^('type config.json ^^^| findstr "machine_no"'^) do set MACHINE_NO=%%%%a
echo set MACHINE_NO=%%MACHINE_NO: =%%
echo for /f "tokens=2 delims=:" %%%%a in ^('ipconfig ^^^| findstr /i "IPv4"'^) do set IP_ADDR=%%%%a
echo set IP_ADDR=%%IP_ADDR: =%%
echo for /f "tokens=1 delims=," %%%%a in ^('getmac /fo csv /nh'^) do set MAC_ADDR=%%%%a
echo set MAC_ADDR=%%MAC_ADDR:"=%%
echo curl -s -X POST "$server_url/api/machine_report.php" -H "Content-Type: application/json" -d "{\"machine_no\":%%MACHINE_NO%%,\"ip\":\"%%IP_ADDR%%\",\"mac\":\"%%MAC_ADDR%%\",\"status\":\"online\"}"
) > "C:\\Net8\\report_status.bat"

echo Creating start_camera.bat...
(
echo @echo off
echo cd /d "%%~dp0"
echo taskkill /F /IM chrome.exe ^>nul 2^>^&1
echo timeout /t 2 ^>nul
echo start "" "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe" --kiosk --use-fake-ui-for-media-stream "$server_url/camera/?camera_no=$machine_no"
) > "C:\\Net8\\start_camera.bat"

echo Adding to startup...
copy "C:\\Net8\\report_status.bat" "%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs\\Startup\\" >nul

echo Reporting to server...
call "C:\\Net8\\report_status.bat"

echo.
echo ============================================
echo Setup Complete! Machine $machine_no
echo ============================================
pause
BAT;

// ダウンロードとして送信
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="setup_machine_' . $machine_no . '.bat"');
echo $bat_content;
