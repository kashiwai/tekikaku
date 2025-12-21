@echo off
REM ============================================
REM Net8 カメラサーバー起動スクリプト
REM ============================================

cd /d "%~dp0"

REM 設定読み込み
for /f "tokens=1,* delims=:" %%a in ('type config.json ^| findstr "machine_no"') do (
    set MACHINE_NO=%%b
)
for /f "tokens=1,* delims=:" %%a in ('type config.json ^| findstr "server_url"') do (
    set SERVER_URL=%%b
)

REM 余分な文字を除去
set MACHINE_NO=%MACHINE_NO: =%
set MACHINE_NO=%MACHINE_NO:,=%
set MACHINE_NO=%MACHINE_NO:"=%
set SERVER_URL=%SERVER_URL: =%
set SERVER_URL=%SERVER_URL:,=%
set SERVER_URL=%SERVER_URL:"=%

echo ============================================
echo Net8 カメラサーバー起動
echo Machine No: %MACHINE_NO%
echo Server URL: %SERVER_URL%
echo ============================================

REM 既存のChromeを終了
taskkill /F /IM chrome.exe >nul 2>&1
timeout /t 2 >nul

REM Chrome起動（キオスクモード）
set CHROME_URL=%SERVER_URL%/camera/?camera_no=%MACHINE_NO%
echo Starting Chrome: %CHROME_URL%

start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" ^
    --kiosk ^
    --disable-infobars ^
    --disable-session-crashed-bubble ^
    --disable-translate ^
    --no-first-run ^
    --disable-features=TranslateUI ^
    --use-fake-ui-for-media-stream ^
    --enable-features=WebRTCPipeWireCapturer ^
    "%CHROME_URL%"

echo Chrome started successfully
echo ============================================
