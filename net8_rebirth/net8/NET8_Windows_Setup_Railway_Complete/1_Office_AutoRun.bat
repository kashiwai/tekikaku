@echo off
cd /d %~dp0


rem #### 手動セットアップ時の作業手順確認時には、外界と接続できなくなることを危惧して
rem #### 最後にIPの変更を行っているが、それでは「最大限自動化」するには都合が悪い
rem #### ・・・というか面倒くさい！
rem #### セットアップする大前提として、現地ネットワーク配下で行うか、
rem #### 本番ネットワークと同じローカルネットワーク設定を施したローカルルータを立てて
rem #### ハナから本番環境と同じローカルセグメントの帯域で作業すること！！


rem ----- ここからサイト毎の設定

rem ★顧客NOの設定
set CLIENTNO=1

rem ホスト番号の指定(1から連番)
set HOSTNO=
set /P HOSTNO="ホスト番号を数値で入力してください。"
if "%HOSTNO%"=="" (
    echo ホスト番号が入力されませんでした。処理を終了します。
    exit /b
)

rem ★ホストIPの決定に必要な基準IPの設定(ここで指定されたIPにホスト番号が足されていく)
rem     因みにデフォルトゲートウェイはその帯域の .1、サブネットマスクは /24(255.255.255.0) 固定
rem     DNSはデフォルトゲートウェイと一緒
rem     やっつけで書いている(ネットワーク的計算をしてない)ので、末尾 .254 を超える範囲はおかしくなるぉ
set BASEIP=192.168.11.100

rem ★タイムゾーン(サイト運営)国の指定
rem   タイムゾーンIDに表記されている(「Standard Time」を除いた)国名で指定すること！
rem     日本のタイムゾーンは「(UTC+09:00) 大阪、札幌、東京」: Tokyo Standard Time
rem     台湾のタイムゾーンは「(UTC+08:00) 台北」            : Taipei Standard Time
set COUNTRY=Taipei


rem ----- ここから本番

rem □エクスプローラーのフォルダオプションで拡張子を表示する・・・他
echo エクスプローラーの設定をしています...
powershell -ExecutionPolicy RemoteSigned -File .\DisplayExtent.ps1


rem ----- ここから本番

rem ■WindowsUpdateの無効化
echo WindowsUpdateを無効にしています...
powershell -ExecutionPolicy RemoteSigned -File .\DisableWindowsUpdate.ps1

rem □アプリやその他の送信者からの通知をOFFにする
echo アプリやその他の送信者からの通知をOFFにしています(再起動後有効)...
powershell -ExecutionPolicy RemoteSigned -File .\DisableInfo.ps1

rem ■HP関連のタスクをOFFにする
echo HP関連のタスクをOFFにしています...
powershell -ExecutionPolicy RemoteSigned -File .\DisableTask.ps1

rem ■タイムゾーンの変更
echo タイムゾーンを設定しています...
powershell -ExecutionPolicy RemoteSigned -File .\TimeZone.ps1 -country %COUNTRY%

rem ■電源/スリープ管理
echo 電源/スリープ管理を設定しています...
call .\PowerControl.bat

rem ■PC名の変更
echo コンピュータ名を設定しています...
powershell -ExecutionPolicy RemoteSigned -File .\RenameComputer.ps1 -clientno %CLIENTNO% -hostno %HOSTNO%

rem ■ローカルIPアドレスの固定
echo IPアドレスを設定しています...
powershell -ExecutionPolicy RemoteSigned -File .\FixedIPAddress.ps1 -baseip %BASEIP% -hostno %HOSTNO%

rem ■リモートデスクトップ
echo リモートデスクトップを設定しています...
powershell -ExecutionPolicy RemoteSigned -File .\RemoteDesktop.ps1

rem ■Chromeのインストール
echo Chromeをインストールしています...
call .\ChromeLocalInstall.bat


rem 終了
echo 処理が完了しました。
rem exit /b
rem pause
cmd /k
