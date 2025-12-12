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

    // ユーザー認証確認
    echo "\n========================================\n";
    echo "テスターユーザー認証確認\n";
    echo "========================================\n\n";

    $testEmail = 'kousuke@restill.biz';
    $testPass = 'kousuke0122';

    $sql = "SELECT member_no, nickname, mail, pass, state, tester_flg FROM mst_member WHERE mail = :mail";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mail' => $testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ ユーザーが見つかりません: {$testEmail}\n";
    } else {
        echo "✅ ユーザー情報:\n";
        echo "  member_no: {$user['member_no']}\n";
        echo "  nickname: {$user['nickname']}\n";
        echo "  mail: {$user['mail']}\n";
        echo "  state: {$user['state']}\n";
        echo "  tester_flg: {$user['tester_flg']}\n";
        echo "  pass_hash: " . substr($user['pass'], 0, 20) . "...\n\n";

        $passMatch = password_verify($testPass, $user['pass']);
        echo "パスワード検証結果: " . ($passMatch ? "✅ 正しい" : "❌ 不一致") . "\n";

        if (!$passMatch) {
            // プレーンテキスト比較も試す
            $plainMatch = ($user['pass'] === $testPass);
            echo "プレーンテキスト比較: " . ($plainMatch ? "✅ 一致" : "❌ 不一致") . "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
