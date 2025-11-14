<?php
/**
 * 画像パス修正スクリプト
 * image_listフィールドから重複したパスプレフィックスを削除
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__ . '/../../_etc/require_files_admin.php');

// 管理者認証チェック
if (!isset($_GET['auth']) || $_GET['auth'] !== 'net8_admin_2025') {
    die('認証が必要です');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>画像パス修正 - NET8</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #fee; color: #c00; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 画像パス修正</h1>

        <?php
        try {
            $db = new SmartDB(DB_DSN);

            // 現在の状態を確認
            $sql = "SELECT model_no, model_cd, model_name, image_list
                    FROM mst_model
                    WHERE del_flg = 0
                    AND image_list IS NOT NULL
                    AND image_list != ''
                    ORDER BY model_no";
            $result = $db->query($sql);
            $models = [];
            while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                $models[] = $row;
            }

            echo '<div class="info">';
            echo '<strong>📊 データベース画像パス確認</strong><br>';
            echo '登録されている機種数: ' . count($models) . '件';
            echo '</div>';

            if (!empty($models)) {
                echo '<table>';
                echo '<tr><th>機種コード</th><th>機種名</th><th>現在のパス</th><th>状態</th></tr>';

                foreach ($models as $model) {
                    $needsFix = (strpos($model['image_list'], 'img/model/') === 0);
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($model['model_cd']) . '</td>';
                    echo '<td>' . htmlspecialchars($model['model_name']) . '</td>';
                    echo '<td><code>' . htmlspecialchars($model['image_list']) . '</code></td>';
                    echo '<td>' . ($needsFix ? '❌ 要修正' : '✅ 正常') . '</td>';
                    echo '</tr>';
                }

                echo '</table>';
            }

            // 修正実行
            if (isset($_POST['fix'])) {
                echo '<div class="info"><strong>🔄 画像パス修正を実行中...</strong></div>';

                $fixedCount = 0;
                foreach ($models as $model) {
                    if (strpos($model['image_list'], 'img/model/') === 0) {
                        // img/model/ プレフィックスを削除してファイル名のみにする
                        $newPath = str_replace('img/model/', '', $model['image_list']);

                        $updateSql = "UPDATE mst_model
                                     SET image_list = " . $db->quote($newPath, 'text') . "
                                     WHERE model_no = " . $db->quote($model['model_no'], 'integer');

                        $db->exec($updateSql);
                        $fixedCount++;

                        echo '<div class="success">';
                        echo '✅ ' . htmlspecialchars($model['model_name']) . '<br>';
                        echo '変更前: <code>' . htmlspecialchars($model['image_list']) . '</code><br>';
                        echo '変更後: <code>' . htmlspecialchars($newPath) . '</code>';
                        echo '</div>';
                    }
                }

                if ($fixedCount > 0) {
                    echo '<div class="success">';
                    echo '<strong>✅ 修正完了！</strong><br>';
                    echo '修正した機種数: ' . $fixedCount . '件<br>';
                    echo '<a href="/" class="btn">トップページで確認</a>';
                    echo '</div>';
                } else {
                    echo '<div class="info">すべてのパスは正常です。修正不要です。</div>';
                }

            } else {
                // 修正が必要な場合のみボタンを表示
                $needsFixCount = 0;
                foreach ($models as $model) {
                    if (strpos($model['image_list'], 'img/model/') === 0) {
                        $needsFixCount++;
                    }
                }

                if ($needsFixCount > 0) {
                    echo '<div class="error">';
                    echo '<strong>⚠️ 問題検出</strong><br>';
                    echo '修正が必要な機種: ' . $needsFixCount . '件<br>';
                    echo '画像パスに <code>img/model/</code> プレフィックスが含まれているため、';
                    echo 'テンプレートとの結合時にパスが重複しています。';
                    echo '</div>';

                    echo '<form method="POST">';
                    echo '<button type="submit" name="fix" class="btn">🔧 画像パスを修正する</button>';
                    echo '</form>';
                } else {
                    echo '<div class="success">✅ すべての画像パスは正常です</div>';
                }
            }

        } catch (Exception $e) {
            echo '<div class="error">❌ エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

    </div>
</body>
</html>
