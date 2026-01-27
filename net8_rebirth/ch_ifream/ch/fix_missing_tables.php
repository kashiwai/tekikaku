<?php
/**
 * 不足テーブル修正スクリプト
 * 既存データを保持したまま、不足しているテーブルだけを作成
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__ . '/_etc/setting.php');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>不足テーブル修正</h1>";

    // 現在のテーブル一覧を取得
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>現在のテーブル数: " . count($existing_tables) . "</p>";

    // 01_create.sqlを読み込み
    $sql_file = __DIR__ . '/01_create.sql';
    if (!file_exists($sql_file)) {
        die("❌ 01_create.sql が見つかりません");
    }

    $sql_content = file_get_contents($sql_file);

    // CREATE TABLE文を抽出
    preg_match_all('/CREATE TABLE `([^`]+)`[^;]+;/is', $sql_content, $matches);
    $all_tables = $matches[1];

    echo "<p>01_create.sqlのテーブル数: " . count($all_tables) . "</p>";

    // 不足しているテーブルを特定
    $missing_tables = array_diff($all_tables, $existing_tables);

    if (empty($missing_tables)) {
        echo "<h2>✅ すべてのテーブルが存在します</h2>";
    } else {
        echo "<h2>⚠️ 不足しているテーブル (" . count($missing_tables) . "個)</h2>";
        echo "<ul>";
        foreach ($missing_tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";

        // 不足しているテーブルを作成
        echo "<h2>📝 テーブル作成開始...</h2>";

        foreach ($missing_tables as $table_name) {
            // テーブル作成SQLを抽出
            $pattern = '/CREATE TABLE `' . preg_quote($table_name, '/') . '`[^;]+;/is';
            if (preg_match($pattern, $sql_content, $create_match)) {
                $create_sql = $create_match[0];

                try {
                    $pdo->exec($create_sql);
                    echo "<p>✅ {$table_name} を作成しました</p>";
                } catch (PDOException $e) {
                    echo "<p>❌ {$table_name} の作成に失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }

    // 最終確認
    $stmt = $pdo->query("SHOW TABLES");
    $final_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>✅ 完了</h2>";
    echo "<p>最終テーブル数: " . count($final_tables) . "</p>";

    // 重要なテーブルの確認
    $important_tables = ['mst_admin', 'dat_notice_lang', 'dat_notice', 'dat_machine'];
    echo "<h3>重要テーブル確認:</h3><ul>";
    foreach ($important_tables as $table) {
        $exists = in_array($table, $final_tables) ? "✅" : "❌";
        echo "<li>{$exists} {$table}</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
