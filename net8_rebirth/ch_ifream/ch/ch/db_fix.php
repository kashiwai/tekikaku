<?php
// 直接データベースを修正するスクリプト
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../_etc/setting.php');

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    echo "<h1>メンテナンスモード修正</h1>\n";

    // 修正前の状態
    echo "<h2>修正前</h2>\n";
    $stmt = $pdo->query("SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1");
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>machine_status = " . $m['machine_status'] . "</p>\n";

    $stmt = $pdo->query("SELECT machine_no, assign_flg FROM lnk_machine WHERE machine_no = 1");
    $l = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>assign_flg = " . $l['assign_flg'] . "</p>\n";

    // 修正実行
    echo "<h2>修正実行</h2>\n";
    $pdo->exec("UPDATE dat_machine SET machine_status = 1 WHERE machine_no = 1");
    echo "<p>✅ machine_status → 1</p>\n";

    $pdo->exec("UPDATE lnk_machine SET assign_flg = 0, member_no = NULL WHERE machine_no = 1");
    echo "<p>✅ assign_flg → 0</p>\n";

    // 修正後の状態
    echo "<h2>修正後</h2>\n";
    $stmt = $pdo->query("SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1");
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>machine_status = " . $m['machine_status'] . "</p>\n";

    $stmt = $pdo->query("SELECT machine_no, assign_flg FROM lnk_machine WHERE machine_no = 1");
    $l = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>assign_flg = " . $l['assign_flg'] . "</p>\n";

    echo "<h2 style='color: green;'>✅ 完了！</h2>\n";
    echo "<p><a href='/data/'>トップページへ</a></p>\n";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>エラー</h2>\n";
    echo "<pre>" . $e->getMessage() . "</pre>\n";
}
?>
