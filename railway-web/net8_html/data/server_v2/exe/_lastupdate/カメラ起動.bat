echo off
cd /d %~dp0
copy updatefilesV2.exe updatefiles_bk.exe
echo File Update Check...
updatefiles_bk.exe
echo Server Start...
camera_ctrl.exe
start /min slotserver.exe
timeout 10 /nobreak >nul
echo Chrome start...
chromeCameraV2.exe
