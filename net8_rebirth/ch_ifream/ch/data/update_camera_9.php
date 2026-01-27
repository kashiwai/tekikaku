<?php
/**
 * Update camera 9 with latest peerID
 *
 * カメラが再起動された時に新しいpeerIDに更新するスクリプト
 */

require_once('../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 現在のcamera_nameを確認
    $stmt = $pdo->prepare("SELECT camera_no, camera_name FROM mst_camera WHERE camera_no = 9");
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>Camera 9 Update Script</h2>";
    echo "<p><strong>Current camera_name:</strong> " . htmlspecialchars($current['camera_name'] ?? 'NULL') . "</p>";

    // 新しいcamera_nameに更新
    $newCameraName = 'camera_9_1769472099';

    $updateStmt = $pdo->prepare("UPDATE mst_camera SET camera_name = :camera_name WHERE camera_no = 9");
    $updateStmt->execute(['camera_name' => $newCameraName]);

    echo "<p style='color: green;'><strong>✅ Updated successfully!</strong></p>";
    echo "<p><strong>New camera_name:</strong> " . htmlspecialchars($newCameraName) . "</p>";

    // 確認
    $stmt->execute();
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Verified camera_name:</strong> " . htmlspecialchars($updated['camera_name']) . "</p>";

    echo "<hr>";
    echo "<p>Now you can test the game again:</p>";
    echo "<a href='/data/xxxadmin/test_china_embed.html' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>🎮 Go to Test Page</a>";

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
