@echo off
cd /d %~dp0


rem ----- ここからサイト毎の設定

rem ★ドメインの設定
set DOMAIN=example.com


rem ----- ここから本番

rem ■アプリのインストール(ドライバ入れてからねー)
call .\Net8AppInstall.bat %DOMAIN%


rem 終了
echo 処理が完了しました。
rem exit /b
rem pause
cmd /k
