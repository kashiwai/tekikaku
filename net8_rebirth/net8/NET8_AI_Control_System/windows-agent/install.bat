@echo off
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo NET8 Windows PC Agent - インストーラー
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

REM マシン番号を入力
set /p MACHINE_NO="マシン番号を入力してください (1-40): "

echo.
echo インストール開始...
echo.

REM インストール先ディレクトリ作成
if not exist "C:\NET8\agent" mkdir "C:\NET8\agent"

REM ファイルをコピー
echo ファイルをコピー中...
xcopy /E /I /Y "%~dp0\*" "C:\NET8\agent\"

REM .envファイル作成
echo.
echo 設定ファイル作成中...
(
echo MACHINE_NO=%MACHINE_NO%
echo MACHINE_NAME=MACHINE-%MACHINE_NO%
echo HUB_URL=wss://net8-websocket-hub.up.railway.app
echo AUTH_TOKEN=
) > "C:\NET8\agent\.env"

REM Node.js依存関係インストール
echo.
echo 依存関係インストール中...
cd C:\NET8\agent
call npm install

REM タスクスケジューラに登録（ログイン時に自動起動）
echo.
echo 自動起動設定中...
schtasks /create /tn "NET8-Agent" /tr "C:\NET8\agent\start.bat" /sc onlogon /ru "%USERNAME%" /f

REM start.bat作成
(
echo @echo off
echo cd C:\NET8\agent
echo node agent.js
) > "C:\NET8\agent\start.bat"

echo.
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ✅ インストール完了！
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.
echo 📍 インストール先: C:\NET8\agent
echo 📍 マシン番号: %MACHINE_NO%
echo.
echo 次回ログイン時に自動起動します。
echo 今すぐ起動する場合は、以下を実行してください:
echo     C:\NET8\agent\start.bat
echo.
pause
