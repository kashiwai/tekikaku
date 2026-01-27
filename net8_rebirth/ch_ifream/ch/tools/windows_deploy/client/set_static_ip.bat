@echo off
REM ============================================
REM 静的IPアドレス設定スクリプト
REM 管理者権限で実行してください
REM ============================================

echo ============================================
echo Net8 静的IPアドレス設定
echo ============================================
echo.

REM 現在のIP情報を表示
echo 現在のネットワーク情報:
ipconfig | findstr /i "IPv4 Subnet Gateway"
echo.

REM 設定値を入力
set /p MACHINE_NO="マシン番号を入力 (1-68): "
set /p NETWORK="ネットワーク部を入力 (例: 192.168.1): "

REM IPアドレスを計算 (100 + マシン番号)
set /a IP_LAST=100+%MACHINE_NO%
set IP_ADDRESS=%NETWORK%.%IP_LAST%

echo.
echo 設定内容:
echo   IPアドレス: %IP_ADDRESS%
echo   サブネット: 255.255.255.0
echo   ゲートウェイ: %NETWORK%.1
echo   DNS: %NETWORK%.1
echo.

set /p CONFIRM="この設定でよろしいですか？ (Y/N): "
if /i not "%CONFIRM%"=="Y" (
    echo キャンセルしました
    pause
    exit /b
)

REM ネットワークアダプタ名を取得（通常は「イーサネット」または「Ethernet」）
for /f "tokens=1,2,3,4" %%a in ('netsh interface show interface ^| findstr /i "接続済み Connected"') do (
    set ADAPTER_NAME=%%d
)

echo.
echo アダプタ: %ADAPTER_NAME%
echo IPアドレスを設定中...

REM 静的IP設定
netsh interface ip set address name="%ADAPTER_NAME%" static %IP_ADDRESS% 255.255.255.0 %NETWORK%.1

REM DNS設定
netsh interface ip set dns name="%ADAPTER_NAME%" static %NETWORK%.1

echo.
echo ============================================
echo 設定完了
echo ============================================
echo.
echo 新しいIP設定:
ipconfig | findstr /i "IPv4 Subnet Gateway"
echo.

REM config.jsonも更新
echo config.jsonを更新中...
echo { > C:\Net8\config.json
echo     "machine_no": %MACHINE_NO%, >> C:\Net8\config.json
echo     "camera_no": %MACHINE_NO%, >> C:\Net8\config.json
echo     "server_url": "https://mgg-webservice-production.up.railway.app", >> C:\Net8\config.json
echo     "ip_address": "%IP_ADDRESS%", >> C:\Net8\config.json
echo     "model_name": "" >> C:\Net8\config.json
echo } >> C:\Net8\config.json

echo 完了しました
pause
