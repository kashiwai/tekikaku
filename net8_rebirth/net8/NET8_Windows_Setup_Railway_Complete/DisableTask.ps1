#Get-ScheduledTask -TaskPath \Hewlett-Packard\*
#Get-ScheduledTask -TaskPath \HP\*

# HP関連のタスク無効
Get-ScheduledTask -TaskPath \Hewlett-Packard\* | Disable-ScheduledTask
Get-ScheduledTask -TaskPath \HP\* | Disable-ScheduledTask


# 終了
exit 0
