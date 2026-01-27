<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1>環境変数チェック</h1>\n";

// setting.phpを読み込む前に環境変数を確認
echo "<h2>環境変数（読み込み前）</h2>\n";
echo "DB_HOST from getenv: " . (getenv('DB_HOST') ?: '(empty)') . "<br>\n";
echo "DB_HOST from \$_ENV: " . ($_ENV['DB_HOST'] ?? '(empty)') . "<br>\n";
echo "DB_HOST from \$_SERVER: " . ($_SERVER['DB_HOST'] ?? '(empty)') . "<br>\n";
echo "RAILWAY_ENVIRONMENT: " . (getenv('RAILWAY_ENVIRONMENT') ?: '(empty)') . "<br>\n";

// setting.phpを読み込む
require_once(__DIR__ . '/../_etc/setting.php');

echo "<h2>定数（読み込み後）</h2>\n";
echo "DB_HOST: " . DB_HOST . "<br>\n";
echo "DB_NAME: " . DB_NAME . "<br>\n";
echo "DB_USER: " . DB_USER . "<br>\n";
echo "DB_PASSWORD: " . (defined('DB_PASSWORD') ? '(set)' : '(not set)') . "<br>\n";
echo "DB_DSN: " . DB_DSN . "<br>\n";

echo "<h2>接続テスト</h2>\n";
try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);
    echo "✅ PDO接続成功!<br>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM dat_machine");
    $result = $stmt->fetch();
    echo "✅ クエリ成功! dat_machine件数: " . $result['cnt'] . "<br>\n";

    // メンテナンスモード修正
    if (isset($_GET['fix']) && $_GET['fix'] == '1') {
        echo "<h2 style='color: blue;'>🔧 メンテナンスモード修正実行</h2>\n";

        echo "<h3>修正前の状態</h3>\n";
        $stmt = $pdo->query("SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1");
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);
        $status_name = ['準備中', '通常', 'メンテナンス中'][$machine['machine_status']] ?? 'unknown';
        echo "<p>dat_machine.machine_status = {$machine['machine_status']} ({$status_name})</p>\n";

        $stmt = $pdo->query("SELECT machine_no, assign_flg FROM lnk_machine WHERE machine_no = 1");
        $lnk = $stmt->fetch(PDO::FETCH_ASSOC);
        $assign_names = [0 => '利用可能', 1 => '使用中', 9 => 'メンテナンス中'];
        $assign_name = $assign_names[$lnk['assign_flg']] ?? 'unknown';
        echo "<p>lnk_machine.assign_flg = {$lnk['assign_flg']} ({$assign_name})</p>\n";

        echo "<h3>修正実行</h3>\n";
        $stmt = $pdo->prepare("UPDATE dat_machine SET machine_status = 1 WHERE machine_no = 1");
        $result1 = $stmt->execute();
        echo "<p>" . ($result1 ? "✅" : "❌") . " dat_machine.machine_status を 1 (通常) に更新</p>\n";

        $stmt = $pdo->prepare("UPDATE lnk_machine SET assign_flg = 0, member_no = NULL WHERE machine_no = 1");
        $result2 = $stmt->execute();
        echo "<p>" . ($result2 ? "✅" : "❌") . " lnk_machine.assign_flg を 0 (利用可能) に更新</p>\n";

        echo "<h3>修正後の状態</h3>\n";
        $stmt = $pdo->query("SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1");
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);
        $status_name = ['準備中', '通常', 'メンテナンス中'][$machine['machine_status']] ?? 'unknown';
        echo "<p>dat_machine.machine_status = {$machine['machine_status']} ({$status_name})</p>\n";

        $stmt = $pdo->query("SELECT machine_no, assign_flg FROM lnk_machine WHERE machine_no = 1");
        $lnk = $stmt->fetch(PDO::FETCH_ASSOC);
        $assign_name = $assign_names[$lnk['assign_flg']] ?? 'unknown';
        echo "<p>lnk_machine.assign_flg = {$lnk['assign_flg']} ({$assign_name})</p>\n";

        echo "<h2 style='color: green;'>✅ 修正完了！</h2>\n";
        echo "<p><a href='/data/'>トップページで確認</a></p>\n";
    } else {
        echo "<h2>メンテナンスモード状態確認</h2>\n";
        $stmt = $pdo->query("SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1");
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);
        $status_name = ['準備中', '通常', 'メンテナンス中'][$machine['machine_status']] ?? 'unknown';
        echo "<p>dat_machine.machine_status = {$machine['machine_status']} ({$status_name})</p>\n";

        $stmt = $pdo->query("SELECT machine_no, assign_flg FROM lnk_machine WHERE machine_no = 1");
        $lnk = $stmt->fetch(PDO::FETCH_ASSOC);
        $assign_names = [0 => '利用可能', 1 => '使用中', 9 => 'メンテナンス中'];
        $assign_name = $assign_names[$lnk['assign_flg']] ?? 'unknown';
        echo "<p>lnk_machine.assign_flg = {$lnk['assign_flg']} ({$assign_name})</p>\n";

        if ($machine['machine_status'] != 1 || $lnk['assign_flg'] != 0) {
            echo "<p style='color: red;'>⚠️ メンテナンスモードが有効です</p>\n";
            echo "<p><a href='?fix=1' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>メンテナンスモードを解除する</a></p>\n";
        } else {
            echo "<p style='color: green;'>✅ 通常モード（利用可能）</p>\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ PDO接続失敗: " . $e->getMessage() . "<br>\n";
}
?>
