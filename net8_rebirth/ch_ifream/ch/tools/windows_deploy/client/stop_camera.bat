@echo off
REM ============================================
REM Net8 カメラサーバー停止スクリプト
REM ============================================

echo ============================================
echo Net8 カメラサーバー停止
echo ============================================

REM Chromeプロセスを終了
taskkill /F /IM chrome.exe >nul 2>&1

if %ERRORLEVEL% == 0 (
    echo Chrome stopped successfully
) else (
    echo Chrome was not running
)

echo ============================================
