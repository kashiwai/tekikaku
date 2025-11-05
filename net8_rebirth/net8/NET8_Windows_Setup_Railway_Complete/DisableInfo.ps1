# レジストリで通知の表示設定を無効化
Set-ItemProperty "HKCU:\Software\Microsoft\Windows\CurrentVersion\PushNotifications" -Name "ToastEnabled" -Value 0 -Force


# 終了
exit 0
