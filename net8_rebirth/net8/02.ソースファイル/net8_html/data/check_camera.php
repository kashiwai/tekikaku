<?php
/*
 * check_camera.php
 * カメラ情報確認用デバッグページ
 */

// インクルード
require_once('../_etc/require_files.php');

$template = new TemplateUser(false);

echo "<h1>カメラ情報確認</h1>";
echo "<pre>";

// mst_cameraテーブルの構造確認
echo "=== mst_cameraテーブル構造 ===\n";
$sql = "DESCRIBE mst_camera";
$result = $template->DB->queryAll($sql, MDB2_FETCHMODE_ASSOC);
foreach($result as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

// camera_no=10000023の情報を取得
echo "\n=== camera_no=10000023 の情報 ===\n";
$sql = "SELECT * FROM mst_camera WHERE camera_no = 10000023";
$cameraRow = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

if (empty($cameraRow)) {
    echo "❌ カメラ情報が見つかりません\n";
} else {
    echo "✅ カメラ情報:\n";
    print_r($cameraRow);
}

// 全カメラ一覧
echo "\n=== 全カメラ一覧 ===\n";
$sql = "SELECT * FROM mst_camera";
$cameras = $template->DB->queryAll($sql, MDB2_FETCHMODE_ASSOC);
foreach($cameras as $cam) {
    echo "camera_no: " . $cam['camera_no'] . " | camera_name: " . $cam['camera_name'] . "\n";
}

echo "</pre>";
