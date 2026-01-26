<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../_etc/setting.php');

echo "<h1>メンテナンスモード修正</h1>\n";

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    echo "<h2>修正前の状態</h2>\n";

    // 現在の状態を確認
    $stmt = $pdo->query("SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1");
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>dat_machine.machine_status = " . $machine['machine_status'] . "</p>\n";

    $stmt = $pdo->query("SELECT machine_no, assign_flg FROM lnk_machine WHERE machine_no = 1");
    $lnk = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>lnk_machine.assign_flg = " . $lnk['assign_flg'] . "</p>\n";

    echo "<h2>修正実行</h2>\n";

    // dat_machine.machine_status を 1 (通常) に設定
    $stmt = $pdo->prepare("UPDATE dat_machine SET machine_status = 1 WHERE machine_no = 1");
    $result1 = $stmt->execute();
    echo "<p>" . ($result1 ? "✅" : "❌") . " dat_machine.machine_status を 1 (通常) に更新</p>\n";

    // lnk_machine.assign_flg を 0 (利用可能) に設定
    $stmt = $pdo->prepare("UPDATE lnk_machine SET assign_flg = 0 WHERE machine_no = 1");
    $result2 = $stmt->execute();
    echo "<p>" . ($result2 ? "✅" : "❌") . " lnk_machine.assign_flg を 0 (利用可能) に更新</p>\n";

    echo "<h2>修正後の状態</h2>\n";

    // 修正後の状態を確認
    $stmt = $pdo->query("SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1");
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>dat_machine.machine_status = " . $machine['machine_status'] . " (1=通常)</p>\n";

    $stmt = $pdo->query("SELECT machine_no, assign_flg FROM lnk_machine WHERE machine_no = 1");
    $lnk = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>lnk_machine.assign_flg = " . $lnk['assign_flg'] . " (0=利用可能)</p>\n";

    echo "<h2 style='color: green;'>✅ 修正完了！トップページをリロードしてください</h2>\n";
    echo "<p><a href='/data/'>トップページに戻る</a></p>\n";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ エラー</h2>\n";
    echo "<pre>" . $e->getMessage() . "</pre>\n";
}
?>
