<?php
/**
 * 画像確認スクリプト
 * データベースとファイルの画像状態を確認
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// DB接続
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>画像確認 - NET8</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .section { margin: 30px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f8fafc; }
        .status-ok { color: #10b981; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        img { max-width: 200px; height: auto; }
        .info { background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ 画像確認ツール</h1>

        <?php
        try {
            $pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                $db_user,
                $db_password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            echo '<div class="info">✅ データベース接続成功</div>';

            // 画像パス修正処理（img/model/ を削除してファイル名のみに）
            if (isset($_POST['fix_paths'])) {
                echo '<div class="info"><strong>🔄 画像パス修正を実行中...</strong></div>';

                $fixSql = "UPDATE mst_model
                          SET image_list = REPLACE(image_list, 'img/model/', '')
                          WHERE del_flg = 0
                            AND image_list IS NOT NULL
                            AND image_list != ''
                            AND image_list LIKE 'img/model/%'";

                $affectedRows = $pdo->exec($fixSql);

                if ($affectedRows > 0) {
                    echo '<div class="info" style="background: #d1fae5; color: #065f46;">';
                    echo '✅ 画像パス修正完了！<br>';
                    echo '修正した機種数: ' . $affectedRows . '件<br>';
                    echo '<a href="?" style="color: #065f46; font-weight: bold;">ページを更新して確認</a>';
                    echo '</div>';
                } else {
                    echo '<div class="info">すべてのパスは正常です。修正不要です。</div>';
                }
            }

            // 画像パス復元処理（ファイル名のみの状態から img/model/ を追加）
            if (isset($_POST['restore_paths'])) {
                echo '<div class="info"><strong>🔄 画像パス復元を実行中...</strong></div>';

                $restoreSql = "UPDATE mst_model
                              SET image_list = CONCAT('img/model/', image_list)
                              WHERE del_flg = 0
                                AND image_list IS NOT NULL
                                AND image_list != ''
                                AND image_list NOT LIKE 'img/model/%'
                                AND image_list NOT LIKE '%/%'";

                $affectedRows = $pdo->exec($restoreSql);

                if ($affectedRows > 0) {
                    echo '<div class="info" style="background: #d1fae5; color: #065f46;">';
                    echo '✅ 画像パス復元完了！<br>';
                    echo '復元した機種数: ' . $affectedRows . '件<br>';
                    echo '<a href="?" style="color: #065f46; font-weight: bold;">ページを更新して確認</a>';
                    echo '</div>';
                } else {
                    echo '<div class="info">復元が必要なパスはありません。</div>';
                }
            }

            // 画像が登録されている機種を取得
            $sql = "SELECT model_no, model_cd, model_name, image_list
                    FROM mst_model
                    WHERE del_flg = 0
                    ORDER BY model_no";
            $stmt = $pdo->query($sql);
            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<div class="section">';
            echo '<h2>📋 データベース登録状況（全機種）</h2>';
            echo '<table>';
            echo '<tr><th>機種No</th><th>機種コード</th><th>機種名</th><th>画像パス</th><th>ファイル存在</th><th>プレビュー</th></tr>';

            foreach ($models as $model) {
                $imagePath = $model['image_list'];
                $fileExists = false;

                if (!empty($imagePath)) {
                    // ファイル存在チェック
                    // image_list が "hokuto4go.jpg" なら img/model/ を追加
                    // image_list が "img/model/hokuto4go.jpg" ならそのまま使用
                    if (strpos($imagePath, 'img/model/') === 0) {
                        $fullPath = __DIR__ . '/../' . $imagePath;
                    } else {
                        $fullPath = __DIR__ . '/../img/model/' . $imagePath;
                    }
                    $fileExists = file_exists($fullPath);
                }

                echo '<tr>';
                echo '<td>' . htmlspecialchars($model['model_no']) . '</td>';
                echo '<td>' . htmlspecialchars($model['model_cd']) . '</td>';
                echo '<td>' . htmlspecialchars($model['model_name']) . '</td>';
                echo '<td>' . htmlspecialchars($imagePath ?: '（未登録）') . '</td>';

                if (empty($imagePath)) {
                    echo '<td style="color: #94a3b8;">-</td>';
                    echo '<td>-</td>';
                } else {
                    if ($fileExists) {
                        echo '<td class="status-ok">✓ 存在</td>';
                        // 画像表示用のURLを生成
                        if (strpos($imagePath, 'img/model/') === 0) {
                            $displayPath = '/data/' . $imagePath;
                        } else {
                            $displayPath = '/data/img/model/' . $imagePath;
                        }
                        echo '<td><img src="' . htmlspecialchars($displayPath) . '" alt="' . htmlspecialchars($model['model_name']) . '"></td>';
                    } else {
                        echo '<td class="status-error">✗ 不在</td>';
                        echo '<td class="status-error">ファイルなし</td>';
                    }
                }
                echo '</tr>';
            }

            echo '</table>';

            // 修正が必要なパスをチェック
            $needsFix = false;
            $fixNeededCount = 0;
            foreach ($models as $model) {
                if (!empty($model['image_list']) && strpos($model['image_list'], 'img/model/') === 0) {
                    $needsFix = true;
                    $fixNeededCount++;
                }
            }

            // 修正ボタン表示
            if ($needsFix && !isset($_POST['fix_paths'])) {
                echo '<div style="background: #fee; color: #c00; padding: 15px; border-radius: 5px; margin: 20px 0;">';
                echo '<strong>⚠️ 警告：画像パスに問題があります</strong><br>';
                echo '修正が必要な機種: ' . $fixNeededCount . '件<br>';
                echo '画像パスに <code>img/model/</code> プレフィックスが含まれているため、トップページで画像が重複パスになっています。<br>';
                echo '以下のボタンをクリックして、ファイル名のみに修正してください。';
                echo '<form method="POST" style="margin-top: 10px;">';
                echo '<button type="submit" name="fix_paths" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">🔧 画像パスを修正する</button>';
                echo '</form>';
                echo '</div>';
            } else if (!$needsFix) {
                // ファイル名のみのパスがあるか確認
                $hasFilenameOnly = false;
                foreach ($models as $model) {
                    if (!empty($model['image_list']) && strpos($model['image_list'], '/') === false) {
                        $hasFilenameOnly = true;
                        break;
                    }
                }

                if ($hasFilenameOnly) {
                    echo '<div style="background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 20px 0;">';
                    echo '<strong>ℹ️ 情報</strong><br>';
                    echo '一部の画像パスがファイル名のみになっています。<br>';
                    echo '元の状態に戻す場合は、以下のボタンをクリックしてください。';
                    echo '<form method="POST" style="margin-top: 10px;">';
                    echo '<button type="submit" name="restore_paths" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">↩️ img/model/ を追加して復元</button>';
                    echo '</form>';
                    echo '</div>';
                } else {
                    echo '<div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 5px; margin: 20px 0;">';
                    echo '<strong>✅ 画像パスは正常です</strong><br>';
                    echo 'すべての画像パスが正しく設定されています。';
                    echo '</div>';
                }
            }

            echo '</div>';

            // ローカルファイル確認
            echo '<div class="section">';
            echo '<h2>📁 ローカルファイル確認</h2>';
            $imageDir = __DIR__ . '/../img/model/';
            if (is_dir($imageDir)) {
                $files = glob($imageDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                echo '<p>ディレクトリ: ' . realpath($imageDir) . '</p>';
                echo '<p>ファイル数: ' . count($files) . '個</p>';

                if (count($files) > 0) {
                    echo '<ul>';
                    foreach ($files as $file) {
                        $filename = basename($file);
                        $filesize = filesize($file);
                        echo '<li>' . htmlspecialchars($filename) . ' (' . number_format($filesize / 1024, 2) . ' KB)</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="status-error">画像ファイルが見つかりません</p>';
                }
            } else {
                echo '<p class="status-error">ディレクトリが存在しません: ' . htmlspecialchars($imageDir) . '</p>';
            }
            echo '</div>';

            // 環境情報
            echo '<div class="section">';
            echo '<h2>🔧 環境情報</h2>';
            echo '<table>';
            echo '<tr><th>項目</th><th>値</th></tr>';
            echo '<tr><td>現在のディレクトリ</td><td>' . htmlspecialchars(getcwd()) . '</td></tr>';
            echo '<tr><td>スクリプトパス</td><td>' . htmlspecialchars(__DIR__) . '</td></tr>';
            echo '<tr><td>画像ディレクトリ</td><td>' . htmlspecialchars(realpath($imageDir) ?: $imageDir) . '</td></tr>';
            echo '<tr><td>DB_HOST</td><td>' . htmlspecialchars($db_host) . '</td></tr>';
            echo '<tr><td>DB_NAME</td><td>' . htmlspecialchars($db_name) . '</td></tr>';
            echo '</table>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="status-error">❌ エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <div style="margin-top: 30px;">
            <a href="image_upload.php" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">
                画像アップロード画面へ
            </a>
            <a href="machine_control.php" style="padding: 10px 20px; background: #64748b; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                台管理画面へ
            </a>
        </div>
    </div>
</body>
</html>
