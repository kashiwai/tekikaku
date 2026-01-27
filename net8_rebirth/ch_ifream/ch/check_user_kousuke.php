<?php
/**
 * 特定ユーザー認証確認スクリプト
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_etc/require_files.php';

$testEmail = 'kousuke@restill.biz';
$testPass = 'kousuke0122';

try {
    $pdo = get_db_connection();

    // ユーザー検索
    $sql = "SELECT member_no, nickname, mail, pass, state FROM mst_member WHERE mail = :mail";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mail' => $testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'ユーザーが見つかりません',
            'email' => $testEmail
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // パスワード検証
    $passMatch = password_verify($testPass, $user['pass']);

    echo json_encode([
        'success' => true,
        'user_found' => true,
        'member_no' => $user['member_no'],
        'nickname' => $user['nickname'],
        'email' => $user['mail'],
        'state' => $user['state'],
        'password_match' => $passMatch,
        'password_hash_preview' => substr($user['pass'], 0, 20) . '...'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
