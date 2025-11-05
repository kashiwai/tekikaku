# 自動シャットダウン(再起動)のタスク登録


# 再起動時間指定パラメータの取得
Param(
    [string]$shutdowntime
)


# New-ScheduledTaskActionコマンドレットの主なオプション
#   -Execute	実行するプログラムを指定する
#   -Argument	実行するプログラムの引数を指定する。省略可能
#   -WorkingDirectory	プログラム実行時に一時ファイルなどを保存するフォルダを指定する。省略可能
# ----------
# shutdown.exe を以下のパラメータで実行
#   -r    : 再起動
#   -t 60 : 60秒後にシャットダウンを開始
#   -f    : プロセスの強制終了
$Action = New-ScheduledTaskAction -Execute "C:\Windows\System32\shutdown.exe" -Argument "-r -t 60 -f"

# New-ScheduledTaskTriggerコマンドレットの主なオプション
#   -Once	1回だけ実行する。省略可能
#   -AtLogon	ログオン時に実行する。省略可能
#   -AtStartUp	コンピュータの起動時に実行する。省略可能
#   -Daily	毎日実行する。省略可能
#   -At	実行する時間を指定する。省略可能
#   -Week	毎週実行する。省略可能
#   -DaysOfWeek	実行する曜日を指定する。省略可能
# ----------
# 指定時刻に実行
$Trigger = New-ScheduledTaskTrigger -Daily -At $shutdowntime

# Register-ScheduledTaskコマンドレットの主なオプション
#   -TaskName	タスクの名前を指定する
#   -Action	タスク実行時に実行するプログラムを指定する
#   -Trigger	タスクを実行する条件を指定する。省略可能
#   -TaskPath	作成したタスクの保存場所を指定する。省略可能
#   -User	タスクを実行するユーザーを指定する。省略可能
#   -Password	タスクを実行するユーザーのパスワードを指定する。省略可能
# ----------
# タスク追加
Register-ScheduledTask -TaskPath "\Net8\" -TaskName "AutoShutdown" -Action $Action -Trigger $Trigger -RunLevel Highest -Force | Enable-ScheduledTask


# 終了
exit 0
