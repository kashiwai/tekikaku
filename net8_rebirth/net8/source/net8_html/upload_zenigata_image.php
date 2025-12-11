<?php
/**
 * 銭形（機種No.2）の画像を直接DBに登録するスクリプト
 */

require_once('_etc/setting_base.php');
require_once('_lib/smartDB.php');
require_once('_lib/SqlString.php');

// 画像ファイルのパス（一時的にこのスクリプトと同じ場所に配置する想定）
$imageFile = __DIR__ . '/zenigata_image.jpg';

if (!file_exists($imageFile)) {
    die("Error: Image file not found at {$imageFile}\n");
}

// 画像データを読み込み
$imageData = file_get_contents($imageFile);
$imageMime = mime_content_type($imageFile);
$imageExt = pathinfo($imageFile, PATHINFO_EXTENSION);

// ファイル名を生成（既存の形式に合わせる）
$fileName = sha1(mt_rand() . time()) . '.' . $imageExt;

echo "Reading image: {$imageFile}\n";
echo "Image size: " . strlen($imageData) . " bytes\n";
echo "MIME type: {$imageMime}\n";
echo "Generated filename: {$fileName}\n";

// DB接続
try {
    $DB = new SmartDB(DB_TYPE, DB_SERVER, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, DB_CHARSET);
    $DB->autoCommit(false);

    // 機種No.2の銭形を更新
    $sql = "UPDATE mst_model SET
        image_list = :filename,
        image_list_data = :image_data,
        image_list_mime = :mime_type,
        image_detail = :filename2,
        image_detail_data = :image_data2,
        image_detail_mime = :mime_type2,
        image_reel = :filename3,
        image_reel_data = :image_data3,
        image_reel_mime = :mime_type3,
        upd_dt = NOW()
        WHERE model_no = 2";

    $stmt = $DB->prepare($sql);

    // 3つ全て同じ画像を使用
    $stmt->bindValue(':filename', $fileName, PDO::PARAM_STR);
    $stmt->bindValue(':image_data', $imageData, PDO::PARAM_LOB);
    $stmt->bindValue(':mime_type', $imageMime, PDO::PARAM_STR);

    $stmt->bindValue(':filename2', $fileName, PDO::PARAM_STR);
    $stmt->bindValue(':image_data2', $imageData, PDO::PARAM_LOB);
    $stmt->bindValue(':mime_type2', $imageMime, PDO::PARAM_STR);

    $stmt->bindValue(':filename3', $fileName, PDO::PARAM_STR);
    $stmt->bindValue(':image_data3', $imageData, PDO::PARAM_LOB);
    $stmt->bindValue(':mime_type3', $imageMime, PDO::PARAM_STR);

    $stmt->execute();

    $DB->autoCommit(true);

    echo "\n✅ Success! Zenigata image uploaded to database.\n";
    echo "Image registered for model_no = 2 (銭形)\n";
    echo "- image_list: {$fileName}\n";
    echo "- image_detail: {$fileName}\n";
    echo "- image_reel: {$fileName}\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (isset($DB)) {
        $DB->autoCommit(true); // rollback
    }
}
?>
