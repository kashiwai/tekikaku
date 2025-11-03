<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1>ステップバイステップテスト</h1>\n";
try {
    echo "<h2>Step 1: require_files.php</h2>\n";
    require_once(__DIR__ . '/../_etc/require_files.php');
    echo "✅ OK<br>\n";
    echo "<h2>Step 2: TemplateUser</h2>\n";
    $template = new TemplateUser(false);
    echo "✅ OK<br>\n";
    echo "<h2>Step 3: GetRefTimeTodayExt()</h2>\n";
    $refToDay = GetRefTimeTodayExt();
    echo "✅ OK: " . $refToDay . "<br>\n";
    echo "<h2>Step 4: SearchMachineBase()</h2>\n";
    $sqls = new SqlString($template->DB);
    $template->SearchMachineBase($sqls, false);
    echo "✅ OK<br>\n";
    echo "<h2>Step 5: SQL作成</h2>\n";
    $sqls->orderby('dm.release_date desc');
    $count_sql = $sqls->resetField()->field("count(*)")->createSQL();
    echo "✅ SQL: <pre>" . htmlspecialchars($count_sql) . "</pre>\n";
    echo "<h2>Step 6: SQL実行</h2>\n";
    $allrows = $template->DB->getOne($count_sql);
    echo "✅ OK: 件数 = " . $allrows . "<br>\n";

    // Windows側との接続確認
    echo "<h2>Step 7: Windows接続確認</h2>\n";
    require_once(__DIR__ . '/../_etc/setting.php');
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    // 台情報
    echo "<h3>台情報 (dat_machine + lnk_machine)</h3>\n";
    $stmt = $pdo->query("SELECT dm.*, lm.assign_flg, lm.member_no
                         FROM dat_machine dm
                         LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                         WHERE dm.machine_no = 1");
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($machine, true) . "</pre>\n";

    // カメラ情報
    echo "<h3>カメラ情報 (mst_camera)</h3>\n";
    $stmt = $pdo->query("SELECT * FROM mst_camera WHERE camera_no = " . $machine['camera_no']);
    $camera = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($camera, true) . "</pre>\n";

    // カメラリスト情報
    echo "<h3>カメラリスト情報 (mst_cameralist)</h3>\n";
    if ($camera && isset($camera['cameralist_no'])) {
        $stmt = $pdo->query("SELECT * FROM mst_cameralist WHERE cameralist_no = " . $camera['cameralist_no']);
        $cameralist = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($cameralist, true) . "</pre>\n";
    }

    // 問題チェック
    echo "<h3>問題チェック</h3>\n";
    if ($machine['machine_status'] != 1) {
        echo "<p style='color:red;'>❌ machine_status = {$machine['machine_status']} (1=通常が必要)</p>\n";
    } else {
        echo "<p style='color:green;'>✅ machine_status = 1 (通常)</p>\n";
    }

    if ($machine['assign_flg'] != 0) {
        echo "<p style='color:red;'>❌ assign_flg = {$machine['assign_flg']} (0=利用可能が必要)</p>\n";
    } else {
        echo "<p style='color:green;'>✅ assign_flg = 0 (利用可能)</p>\n";
    }

    if (empty($camera)) {
        echo "<p style='color:red;'>❌ カメラ情報が見つかりません (camera_no={$machine['camera_no']})</p>\n";
    } else {
        echo "<p style='color:green;'>✅ カメラ情報あり</p>\n";
    }

    echo "<h2>✅ 全ステップ成功！</h2>\n";
} catch (Exception $e) {
    echo "<h2>❌ Exception</h2><pre style='background:#ffcccc;padding:20px'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
} catch (Error $e) {
    echo "<h2>❌ Error</h2><pre style='background:#ffcccc;padding:20px'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>
