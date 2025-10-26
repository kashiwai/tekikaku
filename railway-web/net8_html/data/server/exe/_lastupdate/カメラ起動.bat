echo off
cd /d %~dp0
appclose.exe
copy updatefiles.exe updatefiles_bk.exe
echo File Update Check...
updatefiles_bk.exe
echo Server Start...
start /min keysocket.exe
timeout 3 /nobreak >nul
start /min digiserver.exe
echo Chrome start...
chromeCamera.exe
echo DigiCounter Start...
digicounter.exe
