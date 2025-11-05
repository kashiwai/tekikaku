@echo off
cd /d %~dp0


rem ----- ここからサイト毎の設定

rem ★自動ログイン用ユーザ情報の設定
set USERNAME=pcuser
set PASSWORD=pcpass

rem ★自動シャットダウン(再起動)時間の設定
rem 毎日 9時 に実行
SET SHUTDOWNTIME=09:00:00


rem ----- ここから念のための処理

rem ■HP関連のタスクをOFFにする(WindowsUpdateをすると、HPのソフトウェアが勝手に入ってしまうことがあるので再度・・・)
echo HP関連のタスクをOFFにしています...
powershell -ExecutionPolicy RemoteSigned -File .\DisableTask.ps1

rem □本番ルータ配下に設置/再起動後にはネットワークがPublicになっている可能性があるので再度Privateにしとく
echo プライベートネットワークに設定しています...
powershell -ExecutionPolicy RemoteSigned -File .\UpdatePrivateNetwork.ps1


rem ----- ここから本番

rem □自動ログイン設定＋カメラ自動起動
powershell -ExecutionPolicy RemoteSigned -File .\Net8AutoLogin.ps1 -username %USERNAME% -password %PASSWORD%

rem □自動シャットダウン(再起動)のタスク登録
powershell -ExecutionPolicy RemoteSigned -File .\Net8AutoShutdown.ps1 -shutdowntime %SHUTDOWNTIME%


rem ----- ここからMoさまの追加要望分

rem □スピーカーをミュートに
powershell -ExecutionPolicy RemoteSigned -File .\MuteVolume.ps1

rem □画面解像度を 1920*1080 に変更
powershell -NoProfile -ExecutionPolicy RemoteSigned -File .\Set-ScreenResolution_1920-1080.ps1

rem □マウスキーの設定は断念・・・
rem 左 [Shift] キー + 左 [Alt] キー + [Num Lock] キー
rem でキーボードショートカットでマウス キー機能を有効にするダイアログが出せるので
rem これで代用してー


rem 終了
echo 処理が完了しました。
rem exit /b
rem pause
cmd /k
