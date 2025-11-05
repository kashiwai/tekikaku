<?php
/**
 * 機種No.3「ミリオンゴッド4号機」を直接DBに登録
 */

require_once('_etc/setting_base.php');
require_once('_lib/smartDB.php');

try {
    $DB = new SmartDB(DB_TYPE, DB_SERVER, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, DB_CHARSET);
    $DB->autoCommit(false);

    echo "機種No.3「ミリオンゴッド4号機」を登録します...\n\n";

    // 機種登録SQL
    $sql = "INSERT INTO mst_model (
        model_no, category, model_cd, model_name, model_roman,
        type_no, unit_no, maker_no, renchan_games, tenjo_games,
        setting_list, image_list, image_detail, image_reel,
        prizeball_data, layout_data, remarks,
        add_no, add_dt, upd_no, upd_dt, del_flg
    ) VALUES (
        3, 2, 'MILLIONGOD01', 'ミリオンゴッド4号機', 'MILLIONGOD',
        5, 4, 1, 0, 9999,
        '', '', '', '',
        '', '{\"video_portrait\":0,\"video_mode\":4,\"drum\":0,\"bonus_push\":[{\"label\":\"select\",\"path\":\"noselect_bonus.png\"}],\"version\":1,\"hide\":[\"changePanel\"]}', '',
        1, NOW(), 1, NOW(), 0
    ) ON DUPLICATE KEY UPDATE
        model_cd = 'MILLIONGOD01',
        model_name = 'ミリオンゴッド4号機',
        model_roman = 'MILLIONGOD',
        category = 2,
        type_no = 5,
        unit_no = 4,
        maker_no = 1,
        upd_dt = NOW()";

    $DB->query($sql);
    $DB->autoCommit(true);

    echo "✅ 成功！機種No.3が登録されました。\n\n";

    // 確認
    $result = $DB->query("SELECT model_no, model_cd, model_name, category, type_no, unit_no, maker_no FROM mst_model WHERE model_no = 3");
    $row = $result->fetch(PDO::FETCH_ASSOC);

    echo "登録内容:\n";
    echo "  機種No: {$row['model_no']}\n";
    echo "  機種CD: {$row['model_cd']}\n";
    echo "  機種名: {$row['model_name']}\n";
    echo "  カテゴリ: " . ($row['category'] == 2 ? 'スロット' : 'パチンコ') . "\n";
    echo "  タイプNo: {$row['type_no']}\n";
    echo "  号機: {$row['unit_no']}\n";
    echo "  メーカーNo: {$row['maker_no']}\n";
    echo "\n画像は管理画面からアップロードしてください。\n";

} catch (Exception $e) {
    if (isset($DB)) {
        $DB->autoCommit(true); // rollback
    }
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>
