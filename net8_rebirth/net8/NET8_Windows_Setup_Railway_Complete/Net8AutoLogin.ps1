# 自動ログイン用ユーザ情報パラメータの取得
Param(
    [string]$username
  , [string]$password
)

# 再起動後の自動ログインを設定
# レジストリにユーザ名とパスワードを設定すれば良いみたい・・・
$RegKey = "HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon"
Set-ItemProperty $RegKey -Name "AutoAdminLogon" -Value 1 -Force
Set-ItemProperty $RegKey -Name "DefaultUsername" -Value $username -Force
Set-ItemProperty $RegKey -Name "DefaultPassword" -Value $password -Force


# ログイン後にスクリプトを自動実行
# レジストリに起動後一度だけ実行するコマンドを渡してもできるみたい・・・

# レジストリ登録でやる場合
# $RegKey = "HKLM:\Software\Microsoft\Windows\CurrentVersion\RunOnce"
# $Script = "c:\serverset\camera.bat"
# Set-ItemProperty $RegKey -Name "Restart-And-RunOnce" -Value "$Script"

# タスクでやる場合
# タスク登録に関するパラメータは Net8AutoShutdown.ps1 のコメント参照
$Action = New-ScheduledTaskAction -Execute "c:\serverset\camera.bat"
$Trigger = New-ScheduledTaskTrigger -AtLogon
$Trigger.Delay = "PT3M"
Register-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoRun" -Action $Action -Trigger $Trigger -Force | Enable-ScheduledTask


# 終了
exit 0
