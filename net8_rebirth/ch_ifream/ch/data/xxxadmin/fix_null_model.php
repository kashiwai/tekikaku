<?php
/**
 * 緊急修正: model_no が NULL のマシンを 0 に修正
 */
require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>model_no NULL 修正</h1>";
    echo "<pre>";

    // 1. NULLのマシンを確認
    $sql = "SELECT machine_no, name, model_no FROM dat_machine WHERE model_no IS NULL";
    $null_machines = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "=== model_no が NULL のマシン ===\n";
    echo "対象件数: " . count($null_machines) . "台\n\n";

    foreach ($null_machines as $m) {
        echo "  No.{$m['machine_no']} - {$m['name']}\n";
    }

    // 2. NULLを0に修正
    if (count($null_machines) > 0) {
        $sql = "UPDATE dat_machine SET model_no = 0 WHERE model_no IS NULL";
        $db->query($sql);
        echo "\n=== 修正完了 ===\n";
        echo count($null_machines) . "台のmodel_noを0に設定しました。\n";
    } else {
        echo "\n修正対象のマシンはありませんでした。\n";
    }

    // 3. 確認
    echo "\n=== 修正後の確認 ===\n";
    $sql = "SELECT machine_no, name, model_no FROM dat_machine WHERE model_no = 0 OR model_no IS NULL ORDER BY machine_no";
    $fixed = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "model_no = 0 のマシン: " . count($fixed) . "台\n";
    foreach ($fixed as $m) {
        echo "  No.{$m['machine_no']} - model_no=" . ($m['model_no'] ?? 'NULL') . "\n";
    }

    echo "</pre>";
    echo "<p><a href='machine_control_v2.php'>マシン管理画面へ</a></p>";
    echo "<p style='color:green;font-weight:bold;'>修正完了しました。Windowsカメラサーバーを再起動してください。</p>";

} catch (Exception $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage());
}
?>
