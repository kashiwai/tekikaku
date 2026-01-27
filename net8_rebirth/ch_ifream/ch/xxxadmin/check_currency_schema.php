<?php
/**
 * 通貨対応のためのテーブル構造確認スクリプト
 *
 * アクセスURL:
 * https://mgg-webservice-production.up.railway.app/data/xxxadmin/check_currency_schema.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('../../_etc/setting.php');

$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

header('Content-Type: text/html; charset=UTF-8');
echo "<html><head><meta charset='UTF-8'><title>通貨対応スキーマ確認</title></head><body>";
echo "<h1>通貨対応のためのテーブル構造確認</h1>";
echo "<hr>";

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<div style='background: #e8f5e9; padding: 10px; margin: 10px 0;'>✅ データベース接続成功</div>";

    // 確認するテーブル
    $tables = ['his_play', 'sdk_users', 'user_balances', 'game_sessions'];

    foreach ($tables as $table) {
        echo "<h2>【{$table}】</h2>";

        try {
            // テーブル構造を取得
            $stmt = $pdo->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
            echo "<tr style='background: #f5f5f5;'>";
            echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
            echo "</tr>";

            $hasCurrency = false;
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "<td>{$col['Extra']}</td>";
                echo "</tr>";

                if ($col['Field'] === 'currency') {
                    $hasCurrency = true;
                }
            }

            echo "</table>";

            // 通貨カラムの有無を表示
            if ($hasCurrency) {
                echo "<div style='background: #e8f5e9; padding: 10px; margin: 10px 0;'>";
                echo "✅ <strong>currency</strong> カラムは存在します";
                echo "</div>";
            } else {
                echo "<div style='background: #fff3e0; padding: 10px; margin: 10px 0;'>";
                echo "❌ <strong>currency</strong> カラムは存在しません（追加が必要）";
                echo "<br><br>";
                echo "<strong>追加するSQL:</strong><br>";
                echo "<code style='background: #f5f5f5; padding: 5px; display: block; margin-top: 5px;'>";

                // テーブルごとに適切な位置を提案
                switch ($table) {
                    case 'sdk_users':
                        echo "ALTER TABLE sdk_users ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER balance;";
                        break;
                    case 'user_balances':
                        echo "ALTER TABLE user_balances ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER balance;";
                        break;
                    case 'game_sessions':
                        echo "ALTER TABLE game_sessions ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'セッション時の通貨' AFTER points_consumed;";
                        break;
                    case 'his_play':
                        // his_playは構造に応じて
                        echo "ALTER TABLE his_play ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'プレイ時の通貨';";
                        break;
                }

                echo "</code>";
                echo "</div>";
            }

        } catch (PDOException $e) {
            echo "<div style='background: #ffebee; padding: 10px; margin: 10px 0;'>";
            echo "⚠️ テーブル <strong>{$table}</strong> が存在しません";
            echo "</div>";
        }
    }

    // 通貨マスタテーブルの確認
    echo "<h2>【mst_currency】通貨マスタ</h2>";
    try {
        $stmt = $pdo->query("SELECT * FROM mst_currency");
        $currencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($currencies) > 0) {
            echo "<div style='background: #e8f5e9; padding: 10px; margin: 10px 0;'>✅ mst_currency テーブルは存在します</div>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr style='background: #f5f5f5;'><th>通貨コード</th><th>通貨名</th><th>記号</th><th>小数点以下桁数</th><th>有効</th></tr>";
            foreach ($currencies as $cur) {
                echo "<tr>";
                echo "<td>{$cur['currency_code']}</td>";
                echo "<td>{$cur['currency_name']}</td>";
                echo "<td>{$cur['currency_symbol']}</td>";
                echo "<td>{$cur['decimal_places']}</td>";
                echo "<td>" . ($cur['is_active'] ? '✓' : '✗') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div style='background: #fff3e0; padding: 10px; margin: 10px 0;'>⚠️ mst_currency テーブルは存在しますが、データがありません</div>";
        }
    } catch (PDOException $e) {
        echo "<div style='background: #fff3e0; padding: 10px; margin: 10px 0;'>";
        echo "❌ mst_currency テーブルが存在しません（作成が必要）";
        echo "<br><br><strong>作成するSQL:</strong><br>";
        echo "<code style='background: #f5f5f5; padding: 5px; display: block; margin-top: 5px; white-space: pre-wrap;'>";
        echo htmlspecialchars("CREATE TABLE IF NOT EXISTS mst_currency (
  currency_code VARCHAR(3) PRIMARY KEY COMMENT '通貨コード (ISO 4217)',
  currency_name VARCHAR(100) NOT NULL COMMENT '通貨名',
  currency_symbol VARCHAR(10) COMMENT '通貨記号',
  decimal_places TINYINT DEFAULT 0 COMMENT '小数点以下桁数',
  is_active TINYINT DEFAULT 1 COMMENT '有効フラグ',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO mst_currency (currency_code, currency_name, currency_symbol, decimal_places) VALUES
('JPY', '日本円', '¥', 0),
('CNY', '人民元', '元', 2),
('KRW', '韓国ウォン', '₩', 0),
('USD', '米ドル', '$', 2);");
        echo "</code>";
        echo "</div>";
    }

    echo "<hr>";
    echo "<h2>📋 まとめ</h2>";
    echo "<p>上記の結果を確認して、必要なテーブルに <strong>currency</strong> カラムを追加してください。</p>";
    echo "<p><strong>次のステップ:</strong> マイグレーションスクリプト（migrate_currency_support.php）を実行</p>";

} catch (PDOException $e) {
    echo "<div style='background: #ffebee; padding: 20px; border-left: 4px solid #f44336; margin: 20px 0;'>";
    echo "<h2 style='color: #c62828; margin-top: 0;'>❌ データベースエラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
