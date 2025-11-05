@echo off
cd /d %~dp0

rem 現在のアクティブな電源設定を変更

rem AC電源動作の時、指定した時間（分）の経過後モニタの電源を切る
powercfg /CHANGE monitor-timeout-ac 0

rem AC電源動作の時、指定した時間（分）の経過後ハードディスクの電源を切る
powercfg /CHANGE disk-timeout-ac 0

rem AC電源動作の時、指定した時間（分）の経過後スタンバイ状態にする
powercfg /CHANGE standby-timeout-ac 0

rem AC電源動作の時、指定した時間（分）の経過後休止状態にする
powercfg /CHANGE hibernate-timeout-ac 0


rem 自身の処理(のみ)を終了
exit /b 0
