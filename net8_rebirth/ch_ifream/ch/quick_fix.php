<?php
// クイック修正スクリプト
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../_etc/setting.php');

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>データベース修正ツール</h1>\n";

    // 修正前
    echo "<h2>修正前の状態</h2>\n";
    $stmt = $pdo->query("SELECT dm.machine_no, dm.machine_status, lm.assign_flg, lm.member_no
                         FROM dat_machine dm
                         LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                         WHERE dm.machine_no = 1");
    $before = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    echo "machine_no: {$before['machine_no']}\n";
    echo "machine_status: {$before['machine_status']} (0=準備中, 1=通常, 2=メンテ)\n";
    echo "assign_flg: {$before['assign_flg']} (0=利用可能, 1=使用中, 9=メンテ)\n";
    echo "member_no: " . ($before['member_no'] ?? 'NULL') . "\n";
    echo "</pre>\n";

    // 修正実行
    echo "<h2>修正実行</h2>\n";
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE dat_machine SET machine_status = 1 WHERE machine_no = 1");
    $stmt->execute();
    echo "<p>✅ machine_status を 1 (通常) に設定</p>\n";

    $stmt = $pdo->prepare("UPDATE lnk_machine SET assign_flg = 0, member_no = NULL WHERE machine_no = 1");
    $stmt->execute();
    echo "<p>✅ assign_flg を 0 (利用可能) に設定</p>\n";

    $pdo->commit();

    // 修正後
    echo "<h2>修正後の状態</h2>\n";
    $stmt = $pdo->query("SELECT dm.machine_no, dm.machine_status, lm.assign_flg, lm.member_no
                         FROM dat_machine dm
                         LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                         WHERE dm.machine_no = 1");
    $after = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    echo "machine_no: {$after['machine_no']}\n";
    echo "machine_status: {$after['machine_status']} (0=準備中, 1=通常, 2=メンテ)\n";
    echo "assign_flg: {$after['assign_flg']} (0=利用可能, 1=使用中, 9=メンテ)\n";
    echo "member_no: " . ($after['member_no'] ?? 'NULL') . "\n";
    echo "</pre>\n";

    echo "<h2 style='color:green;'>✅ 修正完了！</h2>\n";
    echo "<p><a href='/data/' target='_blank'>トップページで確認</a></p>\n";
    echo "<p>Windows PCのslotserver.exeを再起動してください</p>\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2 style='color:red;'>❌ エラー</h2>\n";
    echo "<pre>" . $e->getMessage() . "</pre>\n";
}
?>
