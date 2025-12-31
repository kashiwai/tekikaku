<?php
/**
 * 通貨対応データベースマイグレーション
 *
 * アクセスURL:
 * https://mgg-webservice-production.up.railway.app/data/xxxadmin/migrate_currency_support.php
 *
 * 対応通貨: JPY, CNY, USD, TWD
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('../../_etc/setting.php');

$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>通貨対応マイグレーション</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 10px 0; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 10px 0; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: 600; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .step { background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #2196f3; }
        .step-title { font-weight: 600; color: #2196f3; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🌍 通貨対応データベースマイグレーション</h1>

<?php
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<div class='success'>✅ データベース接続成功</div>";

    // マイグレーション実行フラグ
    $execute = isset($_GET['execute']) && $_GET['execute'] === 'yes';

    if (!$execute) {
        // プレビューモード
        echo "<div class='warning'>";
        echo "<h2>⚠️ プレビューモード</h2>";
        echo "<p>実行するには、URLに <code>?execute=yes</code> を追加してください。</p>";
        echo "<p><a href='?execute=yes' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>マイグレーション実行</a></p>";
        echo "</div>";
    }

    // ヘルパー関数: カラムが存在するかチェック
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
        $stmt->execute(['column' => $column]);
        return $stmt->rowCount() > 0;
    }

    // ヘルパー関数: テーブルが存在するかチェック
    function tableExists($pdo, $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $table]);
        return $stmt->rowCount() > 0;
    }

    echo "<h2>📋 マイグレーション計画</h2>";

    $migrations = [];

    // 1. sdk_users.currency
    if (!tableExists($pdo, 'sdk_users')) {
        echo "<div class='warning'>⚠️ sdk_users テーブルが存在しません（スキップ）</div>";
    } elseif (columnExists($pdo, 'sdk_users', 'currency')) {
        echo "<div class='info'>ℹ️ sdk_users.currency は既に存在します</div>";
    } else {
        $migrations[] = [
            'name' => 'sdk_users.currency カラム追加',
            'sql' => "ALTER TABLE sdk_users ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER member_no"
        ];
    }

    // 2. user_balances.currency
    if (!tableExists($pdo, 'user_balances')) {
        echo "<div class='warning'>⚠️ user_balances テーブルが存在しません（スキップ）</div>";
    } elseif (columnExists($pdo, 'user_balances', 'currency')) {
        echo "<div class='info'>ℹ️ user_balances.currency は既に存在します</div>";
    } else {
        $migrations[] = [
            'name' => 'user_balances.currency カラム追加',
            'sql' => "ALTER TABLE user_balances ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER balance"
        ];
    }

    // 3. game_sessions.currency
    if (!tableExists($pdo, 'game_sessions')) {
        echo "<div class='warning'>⚠️ game_sessions テーブルが存在しません（スキップ）</div>";
    } elseif (columnExists($pdo, 'game_sessions', 'currency')) {
        echo "<div class='info'>ℹ️ game_sessions.currency は既に存在します</div>";
    } else {
        $migrations[] = [
            'name' => 'game_sessions.currency カラム追加',
            'sql' => "ALTER TABLE game_sessions ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'セッション時の通貨' AFTER points_consumed"
        ];
    }

    // 4. his_play.currency
    if (!tableExists($pdo, 'his_play')) {
        echo "<div class='warning'>⚠️ his_play テーブルが存在しません（スキップ）</div>";
    } elseif (columnExists($pdo, 'his_play', 'currency')) {
        echo "<div class='info'>ℹ️ his_play.currency は既に存在します</div>";
    } else {
        $migrations[] = [
            'name' => 'his_play.currency カラム追加',
            'sql' => "ALTER TABLE his_play ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'プレイ時の通貨'"
        ];
    }

    // 5. mst_currency テーブル
    if (tableExists($pdo, 'mst_currency')) {
        echo "<div class='info'>ℹ️ mst_currency テーブルは既に存在します</div>";
    } else {
        $migrations[] = [
            'name' => 'mst_currency テーブル作成',
            'sql' => "CREATE TABLE IF NOT EXISTS mst_currency (
  currency_code VARCHAR(3) PRIMARY KEY COMMENT '通貨コード (ISO 4217)',
  currency_name VARCHAR(100) NOT NULL COMMENT '通貨名',
  currency_symbol VARCHAR(10) COMMENT '通貨記号',
  decimal_places TINYINT DEFAULT 0 COMMENT '小数点以下桁数',
  is_active TINYINT DEFAULT 1 COMMENT '有効フラグ',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通貨マスタ'"
        ];

        $migrations[] = [
            'name' => 'mst_currency 初期データ投入',
            'sql' => "INSERT INTO mst_currency (currency_code, currency_name, currency_symbol, decimal_places, is_active) VALUES
('JPY', '日本円', '¥', 0, 1),
('CNY', '人民元', '元', 2, 1),
('USD', '米ドル', '\$', 2, 1),
('TWD', '台湾ドル', 'NT\$', 2, 1)"
        ];
    }

    if (empty($migrations)) {
        echo "<div class='success'>";
        echo "<h2>✅ マイグレーション完了</h2>";
        echo "<p>すべての変更は既に適用されています。</p>";
        echo "</div>";
    } else {
        echo "<div class='step'>";
        echo "<div class='step-title'>実行予定のマイグレーション: " . count($migrations) . "件</div>";
        echo "<table>";
        echo "<tr><th>#</th><th>変更内容</th><th>SQL</th></tr>";
        foreach ($migrations as $index => $migration) {
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td><strong>{$migration['name']}</strong></td>";
            echo "<td><code>" . htmlspecialchars(substr($migration['sql'], 0, 100)) . "...</code></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";

        if ($execute) {
            echo "<h2>🚀 マイグレーション実行中...</h2>";

            $pdo->beginTransaction();
            $success = true;

            try {
                foreach ($migrations as $index => $migration) {
                    echo "<div class='step'>";
                    echo "<div class='step-title'>【" . ($index + 1) . "/" . count($migrations) . "】 {$migration['name']}</div>";

                    try {
                        $pdo->exec($migration['sql']);
                        echo "<div class='success'>✅ 成功</div>";
                        echo "<pre>" . htmlspecialchars($migration['sql']) . "</pre>";
                    } catch (PDOException $e) {
                        echo "<div class='error'>❌ 失敗: " . htmlspecialchars($e->getMessage()) . "</div>";
                        echo "<pre>" . htmlspecialchars($migration['sql']) . "</pre>";
                        $success = false;
                        break;
                    }

                    echo "</div>";
                }

                if ($success) {
                    $pdo->commit();
                    echo "<div class='success'>";
                    echo "<h2>✅ マイグレーション完了</h2>";
                    echo "<p>すべての変更が正常に適用されました。</p>";
                    echo "</div>";
                } else {
                    $pdo->rollBack();
                    echo "<div class='error'>";
                    echo "<h2>❌ マイグレーション失敗</h2>";
                    echo "<p>エラーが発生したため、すべての変更をロールバックしました。</p>";
                    echo "</div>";
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                echo "<div class='error'>";
                echo "<h2>❌ 予期しないエラー</h2>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }

            // 実行後の確認
            echo "<h2>📊 実行後の確認</h2>";

            $tables = ['sdk_users', 'user_balances', 'game_sessions', 'his_play'];
            foreach ($tables as $table) {
                if (tableExists($pdo, $table)) {
                    $hasCurrency = columnExists($pdo, $table, 'currency');
                    echo "<div class='" . ($hasCurrency ? 'success' : 'warning') . "'>";
                    echo "<strong>{$table}.currency</strong>: " . ($hasCurrency ? '✅ 存在' : '❌ 未作成');
                    echo "</div>";
                }
            }

            if (tableExists($pdo, 'mst_currency')) {
                echo "<div class='success'><strong>mst_currency</strong>: ✅ 存在</div>";

                // 通貨データ確認
                $stmt = $pdo->query("SELECT * FROM mst_currency ORDER BY currency_code");
                $currencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($currencies) > 0) {
                    echo "<table>";
                    echo "<tr><th>通貨コード</th><th>通貨名</th><th>記号</th><th>小数点桁数</th><th>有効</th></tr>";
                    foreach ($currencies as $cur) {
                        echo "<tr>";
                        echo "<td><strong>{$cur['currency_code']}</strong></td>";
                        echo "<td>{$cur['currency_name']}</td>";
                        echo "<td>{$cur['currency_symbol']}</td>";
                        echo "<td>{$cur['decimal_places']}</td>";
                        echo "<td>" . ($cur['is_active'] ? '✓' : '✗') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<div class='warning'><strong>mst_currency</strong>: ❌ 未作成</div>";
            }
        }
    }

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ データベースエラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

    <h2>📝 次のステップ</h2>
    <div class="info">
        <ol>
            <li>マイグレーションが成功したことを確認</li>
            <li><a href="check_currency_schema.php">check_currency_schema.php</a> でスキーマを再確認</li>
            <li>API実装（game_start.php, game_end.phpの修正）</li>
            <li><a href="test_iframe_embed.html">test_iframe_embed.html</a> で動作確認</li>
        </ol>
    </div>

    <div class="warning">
        <strong>⚠️ 注意</strong>
        <ul>
            <li>本番環境で実行する前に、必ずバックアップを取得してください</li>
            <li>マイグレーションは冪等性があるため、複数回実行しても安全です</li>
            <li>エラーが発生した場合、自動的にロールバックされます</li>
        </ul>
    </div>
</div>
</body>
</html>
