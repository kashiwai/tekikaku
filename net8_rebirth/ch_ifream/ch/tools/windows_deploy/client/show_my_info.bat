@echo off
REM ============================================
REM マシン情報表示スクリプト
REM このPCのIP/MACアドレスを表示
REM ============================================

echo ============================================
echo このPCの情報
echo ============================================
echo.
echo コンピュータ名: %COMPUTERNAME%
echo.

REM IPアドレスとMACアドレスを表示
echo ネットワーク情報:
for /f "tokens=2 delims=:" %%a in ('ipconfig /all ^| findstr /i "物理アドレス Physical Address"') do (
    echo   MACアドレス:%%a
    goto :showip
)
:showip
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    echo   IPアドレス: %%a
)
echo.
echo ============================================
echo この情報をメモして、ルーターのDHCP予約に登録してください
echo ============================================
pause
