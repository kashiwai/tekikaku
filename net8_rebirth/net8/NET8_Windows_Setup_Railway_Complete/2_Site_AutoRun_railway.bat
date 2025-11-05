@echo off
cd /d %~dp0


rem ----- Railway本番環境用の設定

rem Railway本番環境のドメイン設定（GCP Cloud SQL統合済み）
set DOMAIN=mgg-webservice-production.up.railway.app


rem ----- アプリケーションのインストール

rem Net8アプリのインストール（ドライバも含む）
call .\Net8AppInstall.bat %DOMAIN%


rem 終了
echo インストールが完了しました。
rem exit /b
rem pause
cmd /k
