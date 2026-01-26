echo off
cd /d %~dp0
copy updatefilesV2.exe updatefiles_bk.exe
echo File Update Check...
updatefiles_bk.exe
echo Server Start...
camera_ctrl.exe
getcategory.exe
if %errorlevel% == 1 start /min pachiserver.exe
if %errorlevel% == 2 start /min slotserver.exe
timeout 10 /nobreak >nul
echo Chrome start...
chromeCameraV2.exe
