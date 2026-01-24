<?php
/**
 * License ID更新ツール
 * 既存のmst_cameralistのLicense IDを新しい値に更新
 *
 * 使い方:
 * https://your-domain.com/data/api/update_license_id.php?MAC=e0-51-d8-16-7d-e1&LICENSE_ID=新しいLicenseID
 */

require_once('../../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔐 License ID更新ツール</h1>";
echo "<pre>";

// パラメータ取得
$mac = isset($_GET['MAC']) ? $_GET['MAC'] : '';
$new_license_id = isset($_GET['LICENSE_ID']) ? $_GET['LICENSE_ID'] : '';

echo "【受信パラメータ】\n";
echo "MAC: " . htmlspecialchars($mac) . "\n";
echo "新しいLicense ID: " . htmlspecialchars(substr($new_license_id, 0, 50)) . "...\n\n";

if (empty($mac) || empty($new_license_id)) {
    echo "❌ MACアドレスとLicense IDの両方を指定してください\n";
    echo "使い方: ?MAC=e0-51-d8-16-7d-e1&LICENSE_ID=your-license-id\n";
    exit;
}

try {
    $DB = new NetDB();

    // MACアドレスを小文字に正規化
    $mac_normalized = strtolower($mac);

    echo "【ステップ1】 現在のLicense IDを確認\n";
    $sql = (new SqlString($DB))
        ->select()
            ->field("mac_address")
            ->field("license_id")
            ->field("camera_no")
            ->field("del_flg")
            ->field("add_dt")
        ->from("mst_cameralist")
        ->where()
            ->and("mac_address =", $mac_normalized, FD_STR)
        ->createSQL();

    $before = $DB->getRow($sql, PDO::FETCH_ASSOC);

    if (!$before || empty($before['mac_address'])) {
        echo "❌ 指定されたMACアドレスがmst_cameralistに見つかりません\n";
        echo "MAC: " . $mac_normalized . "\n";
        exit;
    }

    echo "✅ レコードが見つかりました\n";
    echo "mac_address: " . $before['mac_address'] . "\n";
    echo "古いLicense ID: " . substr($before['license_id'], 0, 50) . "...\n";
    echo "camera_no: " . $before['camera_no'] . "\n";
    echo "del_flg: " . $before['del_flg'] . "\n";
    echo "add_dt: " . $before['add_dt'] . "\n\n";

    echo "【ステップ2】 License IDを更新\n";

    // トランザクション開始
    $DB->autoCommit(false);

    $sql = (new SqlString($DB))
        ->update("mst_cameralist")
            ->set()
                ->value("license_id", $new_license_id, FD_STR)
                ->value("upd_dt", "current_timestamp", FD_FUNCTION)
            ->where()
                ->and("mac_address =", $mac_normalized, FD_STR)
        ->createSQL();

    $ret = $DB->query($sql);

    if (!$ret) {
        $DB->autoCommit(true); // ロールバック
        echo "❌ 更新に失敗しました\n";
        exit;
    }

    // コミット
    $DB->autoCommit(true);

    echo "✅ License IDの更新に成功しました\n\n";

    echo "【ステップ3】 更新後の確認\n";
    $sql = (new SqlString($DB))
        ->select()
            ->field("mac_address")
            ->field("license_id")
            ->field("camera_no")
            ->field("del_flg")
            ->field("upd_dt")
        ->from("mst_cameralist")
        ->where()
            ->and("mac_address =", $mac_normalized, FD_STR)
        ->createSQL();

    $after = $DB->getRow($sql, PDO::FETCH_ASSOC);

    echo "mac_address: " . $after['mac_address'] . "\n";
    echo "新しいLicense ID: " . substr($after['license_id'], 0, 50) . "...\n";
    echo "camera_no: " . $after['camera_no'] . "\n";
    echo "upd_dt: " . $after['upd_dt'] . "\n\n";

    // 完全なLicense IDの比較
    echo "【完全なLicense ID】\n";
    echo "古い: " . $before['license_id'] . "\n\n";
    echo "新しい: " . $after['license_id'] . "\n\n";

    if ($after['license_id'] === $new_license_id) {
        echo "✅ License IDが正しく更新されました！\n";
        echo "これでslotserver.pyからの認証が通るはずです。\n";
    } else {
        echo "⚠️ License IDが一致しません。再度確認してください。\n";
    }

} catch (Exception $e) {
    if (isset($DB)) {
        $DB->autoCommit(true); // ロールバック
    }
    echo "\n❌ エラー: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
