<?php
/**
 * メンテナンスモード解除ツール
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// メンテナンス解除処理
if (isset($_GET['fix']) && $_GET['fix'] == '1') {
    require_once(__DIR__ . '/../_etc/setting.php');

    try {
        $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "<h1>メンテナンスモード解除</h1>\n";

        // 修正前
        echo "<h2>修正前</h2>\n";
        $stmt = $pdo->query("SELECT dm.machine_no, dm.machine_status, lm.assign_flg, lm.member_no
                             FROM dat_machine dm
                             LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                             WHERE dm.machine_no = 1");
        $before = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        echo "machine_status: {$before['machine_status']} (0=準備中, 1=通常, 2=メンテ)\n";
        echo "assign_flg: {$before['assign_flg']} (0=利用可能, 1=使用中, 9=メンテ)\n";
        echo "</pre>\n";

        // 修正実行
        echo "<h2>修正実行中...</h2>\n";
        $pdo->beginTransaction();

        $pdo->exec("UPDATE dat_machine SET machine_status = 1 WHERE machine_no = 1");
        echo "<p>✅ machine_status → 1</p>\n";

        $pdo->exec("UPDATE lnk_machine SET assign_flg = 0, member_no = NULL WHERE machine_no = 1");
        echo "<p>✅ assign_flg → 0</p>\n";

        $pdo->commit();

        // 修正後
        echo "<h2>修正後</h2>\n";
        $stmt = $pdo->query("SELECT dm.machine_no, dm.machine_status, lm.assign_flg
                             FROM dat_machine dm
                             LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                             WHERE dm.machine_no = 1");
        $after = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        echo "machine_status: {$after['machine_status']}\n";
        echo "assign_flg: {$after['assign_flg']}\n";
        echo "</pre>\n";

        echo "<h2 style='color:green;'>✅ 完了！</h2>\n";
        echo "<p><a href='/data/'>トップページで確認</a></p>\n";
        echo "<p><strong>Windows PCのslotserver.exeを再起動してください</strong></p>\n";

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<h2 style='color:red;'>❌ エラー</h2>\n";
        echo "<pre>" . $e->getMessage() . "</pre>\n";
    }
    exit;
}

// 通常表示
require_once(__DIR__ . '/../_etc/setting.php');

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    echo "<h1>メンテナンスモード確認</h1>\n";

    $stmt = $pdo->query("SELECT dm.machine_no, dm.machine_status, lm.assign_flg
                         FROM dat_machine dm
                         LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                         WHERE dm.machine_no = 1");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>現在の状態</h2>\n";
    echo "<pre>";
    echo "machine_status: {$status['machine_status']} (0=準備中, 1=通常, 2=メンテ)\n";
    echo "assign_flg: {$status['assign_flg']} (0=利用可能, 1=使用中, 9=メンテ)\n";
    echo "</pre>\n";

    if ($status['machine_status'] != 1 || $status['assign_flg'] != 0) {
        echo "<p style='color:red; font-size:18px;'>⚠️ メンテナンスモードが有効です</p>\n";
        echo "<p><a href='?fix=1' style='display:inline-block; padding:15px 30px; background:#007bff; color:white; text-decoration:none; border-radius:5px; font-size:18px;'>メンテナンスモードを解除する</a></p>\n";
    } else {
        echo "<p style='color:green; font-size:18px;'>✅ 正常モード（プレイ可能）</p>\n";
        echo "<p><a href='/data/'>トップページへ</a></p>\n";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ エラー</h2>\n";
    echo "<pre>" . $e->getMessage() . "</pre>\n";
}
?>
