@echo off
REM ============================================
REM Net8 初期セットアップスクリプト
REM Chrome Remote Desktopで接続して実行
REM ============================================

echo ============================================
echo Net8 初期セットアップ
echo ============================================
echo.

REM マシン番号を入力
set /p MACHINE_NO="このマシンの番号を入力 (1-68): "

REM フォルダ作成
echo.
echo C:\Net8 フォルダを作成中...
if not exist "C:\Net8" mkdir "C:\Net8"

REM config.json作成
echo config.json を作成中...
(
echo {
echo     "machine_no": %MACHINE_NO%,
echo     "camera_no": %MACHINE_NO%,
echo     "server_url": "https://mgg-webservice-production.up.railway.app",
echo     "model_name": ""
echo }
) > "C:\Net8\config.json"

REM report_status.bat作成
echo report_status.bat を作成中...
(
echo @echo off
echo cd /d "%%~dp0"
echo for /f "tokens=2 delims=:," %%%%a in ^('type config.json ^^^| findstr "machine_no"'^) do set MACHINE_NO=%%%%a
echo set MACHINE_NO=%%MACHINE_NO: =%%
echo for /f "tokens=2 delims=:" %%%%a in ^('ipconfig ^^^| findstr /i "IPv4"'^) do set IP_ADDR=%%%%a
echo set IP_ADDR=%%IP_ADDR: =%%
echo for /f "tokens=1 delims=," %%%%a in ^('getmac /fo csv /nh'^) do set MAC_ADDR=%%%%a
echo set MAC_ADDR=%%MAC_ADDR:"=%%
echo curl -s -X POST "https://mgg-webservice-production.up.railway.app/api/machine_report.php" -H "Content-Type: application/json" -d "{\"machine_no\":%%MACHINE_NO%%,\"ip\":\"%%IP_ADDR%%\",\"mac\":\"%%MAC_ADDR%%\",\"status\":\"online\"}"
) > "C:\Net8\report_status.bat"

REM start_camera.bat作成
echo start_camera.bat を作成中...
(
echo @echo off
echo cd /d "%%~dp0"
echo taskkill /F /IM chrome.exe ^>nul 2^>^&1
echo timeout /t 2 ^>nul
echo start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --use-fake-ui-for-media-stream "https://mgg-webservice-production.up.railway.app/camera/?camera_no=%MACHINE_NO%"
) > "C:\Net8\start_camera.bat"

REM スタートアップに登録
echo スタートアップに登録中...
copy "C:\Net8\report_status.bat" "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\" >nul

REM 即座に報告実行
echo.
echo サーバーに報告中...
call "C:\Net8\report_status.bat"

echo.
echo ============================================
echo セットアップ完了！
echo ============================================
echo.
echo マシン番号: %MACHINE_NO%
echo 設定ファイル: C:\Net8\config.json
echo 状態報告: 起動時に自動実行されます
echo.
echo 管理画面で確認:
echo https://mgg-webservice-production.up.railway.app/xxxadmin/machine_monitor.php
echo.
pause
