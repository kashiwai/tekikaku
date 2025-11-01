<?php
/**
 * サンプル会員データ投入
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>📝 サンプル会員データ投入</h1>";
echo "<hr>";

// 環境変数から直接データベース接続情報を取得
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>✅ データベース接続成功</h2>";
    echo "<p>Host: $db_host / DB: $db_name</p>";
    echo "<hr>";

    // 既存の会員数を確認
    $existing_count = $pdo->query("SELECT COUNT(*) FROM mst_member WHERE del_flg = 0")->fetchColumn();
    echo "<h2>📊 現在の会員数: {$existing_count}人</h2>";

    // サンプル会員データ（パスワードは "admin123" のbcryptハッシュ）
    $sample_members = [
        ['やまちゃん', 'test1@example.com', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', 1000],
        ['はなちゃん', 'test2@example.com', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', 2500],
        ['いっちゃん', 'test3@example.com', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', 500]
    ];

    echo "<h2>👥 サンプル会員を追加中...</h2>";
    echo "<ul>";

    $stmt = $pdo->prepare("
        INSERT INTO mst_member (nickname, mail, pass, point, draw_point, loss_count, mail_magazine, del_flg, add_no, add_dt)
        VALUES (:nickname, :mail, :pass, :point, 0, 0, 0, 0, 1, NOW())
        ON DUPLICATE KEY UPDATE point = VALUES(point), upd_no = 1, upd_dt = NOW()
    ");

    $inserted = 0;
    foreach ($sample_members as $member) {
        // 既存チェック
        $check_stmt = $pdo->prepare("SELECT member_no FROM mst_member WHERE mail = :mail");
        $check_stmt->execute(['mail' => $member[1]]);
        $exists = $check_stmt->fetch();

        if ($exists) {
            echo "<li>⚠️ {$member[0]} ({$member[1]}) は既に登録済み（会員No: {$exists['member_no']}）</li>";
        } else {
            $stmt->execute([
                'nickname' => $member[0],
                'mail' => $member[1],
                'pass' => $member[2],
                'point' => $member[3]
            ]);
            $inserted++;
            echo "<li>✅ {$member[0]} ({$member[1]}) - {$member[3]}ポイント</li>";
        }
    }
    echo "</ul>";

    // 最終的な会員数
    $final_count = $pdo->query("SELECT COUNT(*) FROM mst_member WHERE del_flg = 0")->fetchColumn();

    echo "<hr>";
    echo "<h2>🎉 完了</h2>";
    echo "<p>新規追加: {$inserted}人</p>";
    echo "<p>合計会員数: {$final_count}人</p>";

    // 全会員リストを表示
    echo "<hr>";
    echo "<h3>📋 登録済み会員一覧</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>会員No</th><th>ニックネーム</th><th>メール</th><th>ポイント</th></tr>";

    $members = $pdo->query("
        SELECT member_no, nickname, mail, point
        FROM mst_member
        WHERE del_flg = 0
        ORDER BY member_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($members as $member) {
        echo "<tr>";
        echo "<td>{$member['member_no']}</td>";
        echo "<td>{$member['nickname']}</td>";
        echo "<td>{$member['mail']}</td>";
        echo "<td>{$member['point']}pt</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<h3>📝 ログイン情報</h3>";
    echo "<p>パスワード（全員共通）: <strong>admin123</strong></p>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
