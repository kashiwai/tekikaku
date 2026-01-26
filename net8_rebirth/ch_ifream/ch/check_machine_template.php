<?php
/**
 * マシン1のテンプレート設定確認
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=== マシン1（北斗の拳）のテンプレート設定確認 ===\n\n";

$sql = "SELECT
    dm.machine_no,
    dm.model_no,
    mm.model_name,
    mm.category,
    mm.layout_data
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
WHERE dm.machine_no = 1";

$machine = $template->DB->getRow($sql);

if (empty($machine)) {
    echo "❌ マシン1が見つかりません\n";
    exit;
}

echo "マシン番号: {$machine['machine_no']}\n";
echo "機種名: {$machine['model_name']}\n";
echo "カテゴリ: {$machine['category']} ";

if ($machine['category'] == 1) {
    echo "(パチンコ)\n";
} else if ($machine['category'] == 2) {
    echo "(スロット)\n";
} else {
    echo "(不明)\n";
}

$layout_data = json_decode($machine['layout_data'], true);

echo "\nlayout_data:\n";
print_r($layout_data);

echo "\n使用されるテンプレート:\n";

if ($machine['category'] == 1) {
    if (isset($layout_data['video_portrait']) && $layout_data['video_portrait'] == 1) {
        echo "  -> _html/ja/play/index_pachi.html (パチンコ縦画面)\n";
    } else {
        echo "  -> _html/ja/play/index_pachi_ls_v2.html (パチンコ横画面)\n";
    }
} else {
    if (isset($layout_data['video_portrait']) && $layout_data['video_portrait'] == 1) {
        echo "  -> _html/ja/play/index_slot.html (スロット縦画面)\n";
    } else {
        echo "  -> _html/ja/play/index_slot_ls_v2.html (スロット横画面)\n";
    }
}

echo "\nCSS:\n";
echo "  -> /css/play.css\n";

echo "\n===========================================\n";
?>
