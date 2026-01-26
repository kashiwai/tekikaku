<?php
/**
 * カメラマッピング ロールバックスクリプト
 *
 * 削除フラグを元に戻して、実カメラを復元します
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔄 カメラマッピング ロールバック</h1>";
    echo "<hr>";

    $db->beginTransaction();

    // すべてのカメラの削除フラグを0に戻す
    $sql = "UPDATE mst_camera SET del_flg = 0 WHERE del_flg = 1";
    $result = $db->exec($sql);

    echo "<p>✅ カメラの削除フラグをリセット: {$result}件</p>";

    $db->commit();

    echo "<hr>";
    echo "<h2>✅ ロールバック完了</h2>";
    echo "<p><a href='diagnose_camera_mapping.php'>📊 マッピング診断を確認</a></p>";
    echo "<p><a href='machine_control_v2.php'>🖥️ マシン管理画面へ</a></p>";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<h2>❌ エラー発生</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
