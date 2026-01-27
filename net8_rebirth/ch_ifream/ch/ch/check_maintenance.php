<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../_etc/setting.php');

echo "<h1>メンテナンスモード確認</h1>\n";

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    echo "<h2>lnk_machine テーブル</h2>\n";
    $stmt = $pdo->query("SELECT * FROM lnk_machine WHERE machine_no = 1");
    $lnk = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($lnk, true) . "</pre>\n";

    echo "<h2>dat_machine テーブル</h2>\n";
    $stmt = $pdo->query("SELECT * FROM dat_machine WHERE machine_no = 1");
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($machine, true) . "</pre>\n";

    echo "<h2>dat_machinePlay テーブル</h2>\n";
    $stmt = $pdo->query("SELECT * FROM dat_machinePlay WHERE machine_no = 1");
    $play = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($play, true) . "</pre>\n";

    echo "<h2>テーブル構造確認</h2>\n";
    $stmt = $pdo->query("DESCRIBE lnk_machine");
    echo "<h3>lnk_machine</h3><pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
    }
    echo "</pre>\n";

    $stmt = $pdo->query("DESCRIBE dat_machine");
    echo "<h3>dat_machine</h3><pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Default'] . "\n";
    }
    echo "</pre>\n";

} catch (PDOException $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>
