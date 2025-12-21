<?php
/**
 * マシンセットアップ用コマンド一覧
 * 各PCで実行するワンライナーを表示
 */
require_once __DIR__ . '/../lib/db.php';

$pdo = getDbConnection();

// 稼働中マシン取得
$sql = "SELECT dm.machine_no, mm.model_name, dm.mac_address, dm.ip_address, dm.last_report
        FROM dat_machine dm
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        WHERE dm.machine_no > 0
        ORDER BY dm.machine_no";
$machines = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$server_url = "https://mgg-webservice-production.up.railway.app";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Net8 セットアップコマンド一覧</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
        }
        h1 { text-align: center; margin-bottom: 10px; color: #00d4ff; }
        .subtitle { text-align: center; color: #888; margin-bottom: 30px; }

        .instructions {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .instructions h2 { color: #00d4ff; margin-bottom: 15px; }
        .instructions ol { margin-left: 20px; }
        .instructions li { margin: 10px 0; line-height: 1.6; }
        .instructions code {
            background: #0f3460;
            padding: 2px 8px;
            border-radius: 4px;
            color: #00ff88;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #16213e;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #0f3460;
        }
        th { background: #0f3460; color: #00d4ff; }
        tr:hover { background: #1f4068; }

        .status-done { color: #00ff88; }
        .status-pending { color: #ffaa00; }

        .cmd-box {
            background: #0a0a1a;
            padding: 8px 12px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 11px;
            color: #00ff88;
            cursor: pointer;
            position: relative;
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .cmd-box:hover {
            background: #1a1a3a;
        }
        .cmd-box::after {
            content: "クリックでコピー";
            position: absolute;
            right: 8px;
            color: #666;
            font-size: 10px;
        }
        .copied {
            background: #00ff88 !important;
            color: #000 !important;
        }
        .copied::after {
            content: "コピー完了!" !important;
            color: #000 !important;
        }

        .download-btn {
            display: inline-block;
            background: #00d4ff;
            color: #000;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }
        .download-btn:hover {
            background: #00a8cc;
        }
    </style>
</head>
<body>
    <h1>Net8 セットアップコマンド一覧</h1>
    <p class="subtitle">Chrome Remote Desktopで接続後、コマンドをコピペするだけ</p>

    <div class="instructions">
        <h2>セットアップ手順</h2>
        <ol>
            <li>Chrome Remote Desktopで対象PCに接続</li>
            <li><code>Win + R</code> キーを押して「ファイル名を指定して実行」を開く</li>
            <li>下の表からコマンドをコピー（クリックでコピー）</li>
            <li>貼り付けて Enter</li>
            <li>完了！（自動でサーバーに報告されます）</li>
        </ol>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>機種名</th>
                <th>状態</th>
                <th>ワンライナーコマンド</th>
                <th>BAT</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // マスターCSVから機種リストを取得
            $machine_list = [
                1 => '吉宗(ピンク)', 2 => '番長', 3 => '吉宗(ピンク)', 4 => '番長',
                5 => '吉宗(ピンク)', 6 => '番長', 7 => '吉宗(ピンク)', 8 => 'カイジ',
                9 => 'カイジ', 10 => '吉宗(ピンク)', 11 => '吉宗(青)', 12 => '吉宗(青)',
                13 => '南国物語', 14 => '南国物語', 15 => '南国物語', 16 => 'ミリオンゴッド',
                18 => 'ミリオンゴッド', 20 => 'ミリオンゴッド', 23 => '北斗の拳',
                24 => '北斗の拳', 26 => '北斗の拳', 29 => 'ジャグラー', 30 => 'ジャグラー',
                33 => 'ジャグラー', 37 => 'ファイヤードリフト', 38 => '鬼武者',
                40 => 'ビンゴ', 41 => '北斗の拳', 43 => '北斗の拳', 49 => '北斗の拳',
                50 => '北斗の拳', 51 => '銭形', 53 => '銭形', 54 => 'ファイヤードリフト',
                55 => '鬼武者', 57 => 'ビンゴ', 58 => '秘宝伝', 59 => '秘宝伝',
                60 => '番長', 64 => '島唄', 67 => '島唄', 68 => '秘宝伝'
            ];

            foreach ($machine_list as $no => $model):
                // DBから状態を取得
                $db_machine = null;
                foreach ($machines as $m) {
                    if ($m['machine_no'] == $no) {
                        $db_machine = $m;
                        break;
                    }
                }
                $is_done = !empty($db_machine['mac_address']);
                $cmd = "powershell -c \"Invoke-WebRequest -Uri '{$server_url}/api/download_setup.php?no={$no}' -OutFile 'C:\\setup.bat'; Start-Process 'C:\\setup.bat' -Verb RunAs\"";
            ?>
            <tr>
                <td><strong><?= $no ?></strong></td>
                <td><?= htmlspecialchars($model) ?></td>
                <td class="<?= $is_done ? 'status-done' : 'status-pending' ?>">
                    <?= $is_done ? '✓ 完了' : '○ 未設定' ?>
                    <?php if ($is_done): ?>
                    <br><small><?= htmlspecialchars($db_machine['mac_address'] ?? '') ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="cmd-box" onclick="copyCmd(this, '<?= htmlspecialchars($cmd, ENT_QUOTES) ?>')">
                        <?= htmlspecialchars($cmd) ?>
                    </div>
                </td>
                <td>
                    <a href="<?= $server_url ?>/api/download_setup.php?no=<?= $no ?>" class="download-btn">DL</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function copyCmd(elem, cmd) {
            navigator.clipboard.writeText(cmd).then(() => {
                elem.classList.add('copied');
                setTimeout(() => elem.classList.remove('copied'), 1500);
            });
        }
    </script>
</body>
</html>
