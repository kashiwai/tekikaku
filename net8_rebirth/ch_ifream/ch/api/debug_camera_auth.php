<?php
/**
 * カメラ認証デバッグツール
 * MACアドレスとLicense IDの認証状況を確認
 *
 * 使い方:
 * https://your-domain.com/data/api/debug_camera_auth.php?MAC=e0-51-d8-16-7d-e1&ID=your-license-id&IP=192.168.1.100
 */

require_once('../../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 カメラ認証デバッグ</h1>";
echo "<pre>";

// パラメータ取得
$mac = isset($_GET['MAC']) ? $_GET['MAC'] : '';
$license_id = isset($_GET['ID']) ? $_GET['ID'] : '';
$ip = isset($_GET['IP']) ? $_GET['IP'] : '';

echo "【受信パラメータ】\n";
echo "MAC: " . htmlspecialchars($mac) . "\n";
echo "ID: " . htmlspecialchars(substr($license_id, 0, 50)) . "...\n";
echo "IP: " . htmlspecialchars($ip) . "\n\n";

if (empty($mac)) {
    echo "❌ MACアドレスが指定されていません\n";
    echo "使い方: ?MAC=e0-51-d8-16-7d-e1&ID=your-license-id&IP=192.168.1.100\n";
    exit;
}

try {
    $DB = new NetDB();

    // 正規化（cameraListAPI.phpと同じ処理）
    $mac_normalized = strtolower($mac);

    echo "【MACアドレス正規化】\n";
    echo "元の値: " . htmlspecialchars($mac) . "\n";
    echo "正規化後: " . htmlspecialchars($mac_normalized) . "\n\n";

    // ❶ mst_cameralistでの検索（License IDなし）
    echo "【ステップ1】 mst_cameralistでMACアドレスのみで検索\n";
    $sql = (new SqlString($DB))
        ->select()
            ->field("mac_address")
            ->field("license_id")
            ->field("camera_no")
            ->field("state")
            ->field("del_flg")
            ->field("add_dt")
        ->from("mst_cameralist")
        ->where()
            ->and("mac_address =", $mac_normalized, FD_STR)
        ->createSQL();

    echo "SQL: " . $sql . "\n\n";

    $rows = $DB->getAll($sql, PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "❌ MACアドレスが見つかりません\n";
        echo "登録されているMACアドレス一覧:\n";

        $sql = (new SqlString($DB))
            ->select()
                ->field("mac_address")
                ->field("LEFT(license_id, 30) as license_preview")
                ->field("del_flg")
            ->from("mst_cameralist")
            ->limit(10)
            ->createSQL();

        $all_rows = $DB->getAll($sql, PDO::FETCH_ASSOC);
        foreach ($all_rows as $row) {
            echo "  - " . $row['mac_address'] . " (del_flg: " . $row['del_flg'] . ") License: " . $row['license_preview'] . "...\n";
        }
    } else {
        echo "✅ MACアドレスが見つかりました\n";
        foreach ($rows as $row) {
            echo "  mac_address: " . $row['mac_address'] . "\n";
            echo "  license_id: " . substr($row['license_id'], 0, 50) . "...\n";
            echo "  camera_no: " . $row['camera_no'] . "\n";
            echo "  state: " . ($row['state'] ?: 'NULL') . "\n";
            echo "  del_flg: " . $row['del_flg'] . "\n";
            echo "  add_dt: " . $row['add_dt'] . "\n\n";
        }
    }

    if (!empty($license_id) && !empty($rows)) {
        // ❷ License IDの一致確認
        echo "【ステップ2】 License IDの一致確認\n";
        echo "送信されたID: " . substr($license_id, 0, 50) . "...\n";
        echo "DBのID:      " . substr($rows[0]['license_id'], 0, 50) . "...\n";

        if ($rows[0]['license_id'] === $license_id) {
            echo "✅ License IDが一致します\n\n";
        } else {
            echo "❌ License IDが一致しません\n";
            echo "完全なDBのLicense ID:\n" . $rows[0]['license_id'] . "\n";
            echo "\n送信されたLicense ID:\n" . $license_id . "\n\n";

            // 文字数比較
            echo "長さ比較:\n";
            echo "  DB: " . strlen($rows[0]['license_id']) . " 文字\n";
            echo "  送信: " . strlen($license_id) . " 文字\n\n";
        }

        // ❸ 完全な認証チェック（cameraListAPI.phpと同じ条件）
        echo "【ステップ3】 完全な認証チェック（cameraListAPI.phpと同じ条件）\n";
        $sql = (new SqlString($DB))
            ->select()
                ->field("mac_address")
                ->field("ip_address")
                ->field("license_id")
                ->field("camera_no")
            ->from("mst_cameralist")
            ->where()
                ->and("mac_address =", $mac_normalized, FD_STR)
                ->and("license_id =", $license_id, FD_STR)
                ->and("del_flg =", "0", FD_NUM)
            ->createSQL();

        echo "SQL: " . $sql . "\n\n";
        $auth_row = $DB->getRow($sql, PDO::FETCH_ASSOC);

        if ($auth_row && !empty($auth_row['mac_address'])) {
            echo "✅ 認証成功！\n";
            print_r($auth_row);
        } else {
            echo "❌ 認証失敗（cameraListAPI.phpと同じ結果）\n";
            echo "考えられる原因:\n";
            echo "  1. License IDが不一致\n";
            echo "  2. del_flgが1（削除済み）\n";
            echo "  3. MACアドレスが不一致\n";
        }
    }

    // ❹ mst_cameraの確認
    echo "\n【ステップ4】 mst_cameraの確認\n";
    $sql = (new SqlString($DB))
        ->select()
            ->field("camera_no")
            ->field("camera_mac")
            ->field("camera_name")
            ->field("del_flg")
        ->from("mst_camera")
        ->where()
            ->and("camera_mac =", $mac_normalized, FD_STR)
        ->createSQL();

    $camera_row = $DB->getRow($sql, PDO::FETCH_ASSOC);

    if ($camera_row && !empty($camera_row['camera_mac'])) {
        echo "✅ mst_cameraに登録あり\n";
        print_r($camera_row);
    } else {
        echo "❌ mst_cameraに登録なし\n";
    }

} catch (Exception $e) {
    echo "\n❌ エラー: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
