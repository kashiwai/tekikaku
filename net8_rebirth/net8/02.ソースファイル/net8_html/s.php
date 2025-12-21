<?php
/**
 * 短縮URL - セットアップファイルダウンロード
 * 使用方法: ブラウザで /s.php?1 にアクセス → BATダウンロード
 */

// URLから番号取得 (/s.php?23 または /s.php?no=23)
$machine_no = 0;
if (!empty($_SERVER['QUERY_STRING']) && is_numeric($_SERVER['QUERY_STRING'])) {
    $machine_no = intval($_SERVER['QUERY_STRING']);
} else {
    $machine_no = intval($_GET['no'] ?? 0);
}

// 番号入力フォーム
if ($machine_no <= 0) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Net8 Setup</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #1a1a2e;
                color: #fff;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .box {
                background: #16213e;
                padding: 40px;
                border-radius: 20px;
                text-align: center;
            }
            h1 { color: #00d4ff; margin-bottom: 30px; }
            input {
                font-size: 48px;
                width: 150px;
                text-align: center;
                padding: 20px;
                border: none;
                border-radius: 10px;
                background: #0f3460;
                color: #fff;
            }
            button {
                display: block;
                width: 100%;
                margin-top: 20px;
                padding: 20px;
                font-size: 24px;
                background: #00d4ff;
                color: #000;
                border: none;
                border-radius: 10px;
                cursor: pointer;
            }
            button:hover { background: #00a8cc; }
            p { color: #888; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Net8 Setup</h1>
            <form method="get">
                <input type="number" name="no" min="1" max="99" placeholder="No" autofocus>
                <button type="submit">Download</button>
            </form>
            <p>マシン番号を入力してダウンロード</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 有効な番号の場合、BATファイルを生成
if ($machine_no < 1 || $machine_no > 100) {
    die("Invalid number");
}

$server_url = "https://mgg-webservice-production.up.railway.app";

$bat = <<<BAT
@echo off
chcp 65001 >nul
echo ============================================
echo Net8 Setup - Machine $machine_no
echo ============================================

if not exist "C:\\Net8" mkdir "C:\\Net8"

(
echo {
echo     "machine_no": $machine_no,
echo     "camera_no": $machine_no,
echo     "server_url": "$server_url"
echo }
) > "C:\\Net8\\config.json"

(
echo @echo off
echo for /f "tokens=2 delims=:," %%%%a in ^('type "C:\\Net8\\config.json" ^^^| findstr "machine_no"'^) do set MNO=%%%%a
echo set MNO=%%MNO: =%%
echo for /f "tokens=2 delims=:" %%%%a in ^('ipconfig ^^^| findstr /i "IPv4"'^) do set IP=%%%%a
echo set IP=%%IP: =%%
echo for /f "tokens=1 delims=," %%%%a in ^('getmac /fo csv /nh'^) do set MAC=%%%%a
echo set MAC=%%MAC:"=%%
echo curl -s -X POST "$server_url/api/machine_report.php" -H "Content-Type: application/json" -d "{\"machine_no\":%%MNO%%,\"ip\":\"%%IP%%\",\"mac\":\"%%MAC%%\",\"status\":\"online\"}"
) > "C:\\Net8\\report.bat"

copy "C:\\Net8\\report.bat" "%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs\\Startup\\" >nul 2>&1

echo Sending to server...
call "C:\\Net8\\report.bat"

echo.
echo ============================================
echo Done! Machine $machine_no
echo ============================================
echo.
pause
BAT;

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="net8_' . $machine_no . '.bat"');
echo $bat;
