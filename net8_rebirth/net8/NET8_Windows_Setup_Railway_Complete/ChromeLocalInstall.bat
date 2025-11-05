@echo off
cd /d %~dp0

rem スタンドアロンインストーラからGoogle Chromeをインストール
rem インストーラーは同階層ディレクトリに配置しておく前提

rem インストール開始
rem 対話オプション
rem     /quiet      : サイレント・モード、ユーザーとの対話はない
rem     /passive    : 無人モード  - プログレスバーのみ
rem     /q[n|b|r|f] : ユーザー・インターフェースのレベルの設定
rem         n - UIなし
rem         b -基本UI
rem         r - 縮小UI
rem         f - フルUI (省略値)
rem 再起動オプション
rem     /norestart     : インストール完了後に再起動しない
rem     /promptrestart : 必要な場合、ユーザーに再起動のダイアログを表示
rem     /forcerestart  : インストール後、常にコンピュータを再起動する

rem msiexec /i .\GoogleChromeStandaloneEnterprise64.msi /passive /norestart
start /wait msiexec /i GoogleChromeStandaloneEnterprise64.msi /quiet /norestart


rem 自身の処理(のみ)を終了
exit /b 0
