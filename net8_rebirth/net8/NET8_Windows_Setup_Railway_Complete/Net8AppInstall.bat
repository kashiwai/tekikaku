@echo off
cd /d %~dp0

rem スタンドアロンインストーラからネットパチスロ制御用アプリケーションをインストール
rem インストーラーは同階層ディレクトリに配置しておく前提
rem ドメインは本スクリプトへのパラメータ(%1)で指定されるものとする

rem 現在のカレントディレクトリ取得
set CURRENT_DIR=%~dp0

rem インストール先ディレクトリ指定
set SETUP_DIR=c:\serverset

rem インストールファイル名設定
set INSTALLER=setupapp.exe

rem インストール用ディレクトリ作成
mkdir %SETUP_DIR% > NUL 2>&1

rem ----- インストーラファイルをUSB等からローカルコピーする場合はコチラ -----
rem インストーラファイルコピー
copy /Y %CURRENT_DIR%\%INSTALLER% %SETUP_DIR%\%INSTALLER%
rem ----- インストーラファイルを配布サーバから取得する場合はコチラ ----------
rem インストーラファイルをDL
rem bitsadmin.exe /TRANSFER setup /PRIORITY HIGH https://web.example.com/server_v2/exe/%INSTALLER% %SETUP_DIR%\%INSTALLER%
rem echo .
rem -------------------------------------------------------------------------

rem カレントディレクトリ移動
cd /d %SETUP_DIR%

rem インストール開始
rem パラメータでドメインが指定されている場合はパイプでドメインを自動入力
rem ※パイプでバッチファイルに入力を自動化させるのは一度だけ有効なので注意
if "%1"=="" (
    call %INSTALLER%
) else (
    call %INSTALLER% %1
)

rem インストーラファイル削除
del /Q %SETUP_DIR%\%INSTALLER%

rem カレントディレクトリを元に戻す
cd /d %CURRENT_DIR%

rem デスクトップにショートカット作成
rem   https://blogs.osdn.jp/2019/10/08/create-shortcut.html 配布のバイナリ(shortcut.exe)を使わせてもらった
rem     第1引数は実体であるファイルのパス、 第2引数はショートカットを作成するパス
rem     ショートカットのパスの末尾は .lnk とすること
call .\shortcut.exe "%SETUP_DIR%\camera.bat" "%USERPROFILE%\Desktop\camera.lnk"


rem --- 追加 ---
rem フォーカス等、カメラ調整用アプリをデスクトップにコピー
set CAMERASETAPP=amcap v3.0.9.exe
copy /Y """%CURRENT_DIR%%CAMERASETAPP%""" """%USERPROFILE%\Desktop\%CAMERASETAPP%"""
rem ------------


rem 自身の処理(のみ)を終了
exit /b 0
