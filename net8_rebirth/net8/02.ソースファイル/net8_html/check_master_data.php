<?php
/**
 * マスタテーブルデータ確認スクリプト（Railway用）
 */

// インクルード
require_once('_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<pre>";
echo "=== マスタデータ確認 ===\n\n";

try {
    // データベース接続
    $DB = new SmartDB();

    // mst_owner テーブル
    echo "=== mst_owner (オーナーマスタ) ===\n";
    $sql = "SELECT owner_no, owner_nickname, del_flg FROM mst_owner WHERE del_flg != 1 ORDER BY owner_no";
    $rs = $DB->query($sql);
    $count = 0;
    while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
        echo "ID: {$row['owner_no']}, Name: {$row['owner_nickname']}\n";
        $count++;
    }
    if ($count == 0) {
        echo "❌ データなし\n";
    } else {
        echo "✅ {$count}件\n";
    }
    echo "\n";

    // mst_camera テーブル
    echo "=== mst_camera (カメラマスタ) ===\n";
    $sql = "SELECT camera_no, del_flg FROM mst_camera WHERE del_flg != 1 ORDER BY camera_no LIMIT 10";
    $rs = $DB->query($sql);
    $count = 0;
    while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
        echo "Camera No: {$row['camera_no']}\n";
        $count++;
    }
    if ($count == 0) {
        echo "❌ データなし\n";
    } else {
        echo "✅ {$count}件（最初の10件表示）\n";
    }
    echo "\n";

    // mst_convertPoint テーブル
    echo "=== mst_convertPoint (変換レートマスタ) ===\n";
    $sql = "SELECT convert_no, convert_name, del_flg FROM mst_convertPoint WHERE del_flg != 1 ORDER BY convert_no";
    $rs = $DB->query($sql);
    $count = 0;
    while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
        echo "ID: {$row['convert_no']}, Name: {$row['convert_name']}\n";
        $count++;
    }
    if ($count == 0) {
        echo "❌ データなし\n";
    } else {
        echo "✅ {$count}件\n";
    }
    echo "\n";

    // Signaling Servers
    echo "=== RTC_Signaling_Servers (設定) ===\n";
    if (isset($GLOBALS["RTC_Signaling_Servers"])) {
        foreach ($GLOBALS["RTC_Signaling_Servers"] as $key => $val) {
            echo "Signaling ID: {$key}\n";
        }
    } else {
        echo "❌ RTC_Signaling_Servers 未定義\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}

echo "</pre>";
