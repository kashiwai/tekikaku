<?php
/**
 * NET8 SDK - E2E Test Tables Setup
 * E2Eテスト用テーブル作成スクリプト
 * Version: 1.0.0
 */

require_once(__DIR__ . '/../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

// 簡易認証
$admin_password = $_GET['auth'] ?? '';
if ($admin_password !== 'net8_admin_2025') {
    http_response_code(403);
    die('Access Denied');
}

echo "<!DOCTYPE html>\n";
echo "<html><head><meta charset='UTF-8'><title>SDK E2E Test Tables Setup</title></head><body>\n";
echo "<h1>🔧 SDK E2E Test Tables Setup</h1>\n";
echo "<pre>\n";

try {
    $pdo = get_db_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    echo "✅ データベース接続成功\n\n";

    // SQLファイルを読み込んで実行
    $sql_file = __DIR__ . '/setup_sdk_e2e_test_table.sql';

    if (!file_exists($sql_file)) {
        throw new Exception("SQLファイルが見つかりません: $sql_file");
    }

    $sql_content = file_get_contents($sql_file);

    // コメント行を除去
    $lines = explode("\n", $sql_content);
    $cleaned_lines = array_filter($lines, function($line) {
        $line = trim($line);
        return !empty($line) &&
               substr($line, 0, 2) !== '--' &&
               substr($line, 0, 2) !== '/*';
    });
    $sql_content_cleaned = implode("\n", $cleaned_lines);

    // セミコロンで分割してSQL文を抽出
    $sql_statements = array_filter(
        array_map('trim', explode(';', $sql_content_cleaned)),
        function($stmt) {
            return !empty(trim($stmt));
        }
    );

    echo "📝 パースされたSQL文の数: " . count($sql_statements) . "\n\n";

    $success_count = 0;
    $error_count = 0;

    foreach ($sql_statements as $sql) {
        try {
            // ステートメントの種類を判定
            if (stripos($sql, 'SELECT') !== false) {
                // SELECT文の場合はquery()を使用
                $stmt = $pdo->query($sql);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    echo "✅ 確認クエリ実行: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
                }
                $stmt->closeCursor();
                $stmt = null;
                $success_count++;
            } elseif (stripos($sql, 'CREATE TABLE') !== false) {
                // CREATE TABLE文の場合
                $pdo->exec($sql);
                preg_match('/CREATE TABLE.*?`(\w+)`/i', $sql, $matches);
                $table_name = $matches[1] ?? 'unknown';
                echo "✅ テーブル作成/確認: {$table_name}\n";
                $success_count++;
            } elseif (stripos($sql, 'INSERT INTO') !== false) {
                // INSERT文の場合
                $pdo->exec($sql);
                preg_match('/INSERT INTO.*?`(\w+)`/i', $sql, $matches);
                $table_name = $matches[1] ?? 'unknown';
                echo "✅ データ挿入: {$table_name}\n";
                $success_count++;
            } else {
                // その他のSQL文
                $pdo->exec($sql);
                $success_count++;
            }
        } catch (PDOException $e) {
            $error_count++;
            echo "⚠️ エラー: " . $e->getMessage() . "\n";
        }
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ セットアップ完了\n";
    echo "成功: {$success_count} 件\n";
    echo "エラー: {$error_count} 件\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // テストAPIキー確認
    $stmt = $pdo->query("SELECT * FROM api_keys WHERE key_value = 'pk_test_dummy_partner_2025'");
    $test_key = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    $stmt = null;

    if ($test_key) {
        echo "🔑 テスト用APIキー情報:\n";
        echo "  ID: {$test_key['id']}\n";
        echo "  キー: {$test_key['key_value']}\n";
        echo "  企業名: {$test_key['name']}\n";
        echo "  環境: {$test_key['environment']}\n";
        echo "  レート制限: {$test_key['rate_limit']}/日\n";
        echo "  有効期限: {$test_key['expires_at']}\n\n";
    }

    // テーブル確認
    echo "📊 作成されたテーブル:\n";
    $tables = ['sdk_e2e_test_history', 'sdk_e2e_test_steps'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ {$table}\n";
        } else {
            echo "  ❌ {$table} (未作成)\n";
        }
        $stmt->closeCursor();
        $stmt = null;
    }

    echo "\n🎉 セットアップが正常に完了しました！\n";
    echo "\n次のステップ:\n";
    echo "1. E2Eテストスクリプトを実行\n";
    echo "   → /api/admin/sdk_e2e_test.php?auth=net8_admin_2025\n";
    echo "2. テストダッシュボードを確認\n";
    echo "   → /api/admin/sdk_test_dashboard.php?auth=net8_admin_2025\n";

} catch (Exception $e) {
    echo "❌ エラーが発生しました:\n";
    echo $e->getMessage() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "</body></html>\n";
?>
