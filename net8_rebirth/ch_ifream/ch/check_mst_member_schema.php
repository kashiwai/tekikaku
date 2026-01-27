<?php
/**
 * mst_memberテーブル構造確認 + ユーザー認証確認
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "mst_member テーブル構造確認\n";
    echo "========================================\n\n";

    $cols = $pdo->query("SHOW COLUMNS FROM mst_member")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cols as $col) {
        echo sprintf("%-30s: %s %s %s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'],
            $col['Key']
        );
    }

    // テスターユーザー一覧
    echo "\n========================================\n";
    echo "テスターユーザー一覧 (tester_flg=1)\n";
    echo "========================================\n\n";

    $sql = "SELECT member_no, nickname, mail, state, tester_flg FROM mst_member WHERE tester_flg = 1 ORDER BY member_no";
    $stmt = $pdo->query($sql);
    $testers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($testers)) {
        echo "テスターユーザーが見つかりません\n";
    } else {
        echo "テスター数: " . count($testers) . "件\n\n";
        foreach ($testers as $t) {
            echo sprintf("ID:%d | %s | %s | state:%d\n",
                $t['member_no'], $t['nickname'], $t['mail'], $t['state']);
        }
    }

    // 全ユーザー一覧（最新10件）
    echo "\n========================================\n";
    echo "全ユーザー一覧（最新10件）\n";
    echo "========================================\n\n";

    $sql2 = "SELECT member_no, nickname, mail, state, tester_flg FROM mst_member ORDER BY member_no DESC LIMIT 10";
    $stmt2 = $pdo->query($sql2);
    $users = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $u) {
        echo sprintf("ID:%d | %s | %s | state:%d | tester:%d\n",
            $u['member_no'], $u['nickname'], $u['mail'], $u['state'], $u['tester_flg']);
    }

    // ユーザー状態確認
    echo "\n========================================\n";
    echo "ユーザー確認: ko.kashiwai@gmail.com\n";
    echo "========================================\n\n";

    $testEmail = 'ko.kashiwai@gmail.com';
    $testPass = 'nene11091108';

    $sql3 = "SELECT member_no, state, pass FROM mst_member WHERE mail = :mail";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute(['mail' => $testEmail]);
    $row = $stmt3->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "state: {$row['state']} " . ($row['state'] == 1 ? "✅ 有効" : "❌ 無効") . "\n";
        $passMatch = password_verify($testPass, $row['pass']);
        echo "パスワード: " . ($passMatch ? "✅ 正しい" : "❌ 不一致") . "\n";
    }

    // セッション設定確認
    echo "\n========================================\n";
    echo "セッション設定確認\n";
    echo "========================================\n\n";

    echo "SESSION_SID: " . (defined('SESSION_SID') ? SESSION_SID : '未定義') . "\n";
    echo "SESSION_SEC: " . (defined('SESSION_SEC') ? SESSION_SEC : '未定義') . "\n";
    echo "DOMAIN: " . (defined('DOMAIN') ? DOMAIN : '未定義') . "\n";
    echo "URL_SSL_SITE: " . (defined('URL_SSL_SITE') ? URL_SSL_SITE : '未定義') . "\n";
    echo "URL_SITE: " . (defined('URL_SITE') ? URL_SITE : '未定義') . "\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? '不明') . "\n";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? '不明') . "\n";
    echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
    echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
    echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
    echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
