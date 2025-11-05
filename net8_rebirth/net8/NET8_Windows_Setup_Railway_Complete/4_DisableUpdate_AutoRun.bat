@echo off
cd /d %~dp0

rem ■WindowsStoreの自動更新の無効化
echo WindowsStoreの自動更新を無効にしています...
powershell -ExecutionPolicy RemoteSigned -File .\DisableStoreUpdate.ps1

rem ■Chromeの自動更新の無効化
echo Chromeの自動更新を無効にしています...
powershell -ExecutionPolicy RemoteSigned -File .\DisableChromeUpdate.ps1


rem 終了
echo 処理が完了しました。
rem exit /b
rem pause
cmd /k
