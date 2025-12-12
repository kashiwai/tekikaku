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

    // ユーザーstate更新（0→1でログイン可能に）
    echo "\n========================================\n";
    echo "ユーザー有効化: ko.kashiwai@gmail.com\n";
    echo "========================================\n\n";

    $testEmail = 'ko.kashiwai@gmail.com';

    // 現在の状態確認
    $sql3 = "SELECT member_no, state FROM mst_member WHERE mail = :mail";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute(['mail' => $testEmail]);
    $row = $stmt3->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "現在のstate: {$row['state']}\n";

        if ($row['state'] == 0) {
            // state を 1 に更新
            $sqlUpdate = "UPDATE mst_member SET state = 1 WHERE mail = :mail";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $result = $stmtUpdate->execute(['mail' => $testEmail]);

            if ($result) {
                echo "✅ state を 1 に更新しました（ログイン可能）\n";
            } else {
                echo "❌ state 更新失敗\n";
            }
        } else {
            echo "✅ すでにstate=1です（ログイン可能）\n";
        }
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
