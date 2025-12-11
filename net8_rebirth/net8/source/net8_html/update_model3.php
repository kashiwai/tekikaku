<?php
/**
 * 機種No.3を「ミリオンゴッド4号機」に変更
 */

require_once('_etc/setting_base.php');
require_once('_etc/setting.php');
require_once('_lib/smartDB.php');

try {
    $DB = new SmartDB(DB_DSN);
    $DB->autoCommit(false);

    echo "機種No.3を「ミリオンゴッド4号機」に変更します...\n\n";

    // 変更前の情報を表示
    $result = $DB->query("SELECT model_cd, model_name, model_roman FROM mst_model WHERE model_no = 3");
    $before = $result->fetch(PDO::FETCH_ASSOC);
    echo "【変更前】\n";
    echo "  機種CD: {$before['model_cd']}\n";
    echo "  機種名: {$before['model_name']}\n";
    echo "  英語名: {$before['model_roman']}\n\n";

    // 更新SQL
    $sql = "UPDATE mst_model SET
        model_cd = 'MILLIONGOD01',
        model_name = 'ミリオンゴッド4号機',
        model_roman = 'MILLIONGOD',
        category = 2,
        type_no = 5,
        unit_no = 4,
        maker_no = 1,
        renchan_games = 0,
        tenjo_games = 9999,
        layout_data = '{\"video_portrait\":0,\"video_mode\":4,\"drum\":0,\"bonus_push\":[{\"label\":\"select\",\"path\":\"noselect_bonus.png\"}],\"version\":1,\"hide\":[\"changePanel\"]}',
        upd_no = 1,
        upd_dt = NOW()
    WHERE model_no = 3";

    $DB->query($sql);
    $DB->autoCommit(true);

    echo "✅ 変更完了！\n\n";

    // 変更後の情報を表示
    $result = $DB->query("SELECT model_no, model_cd, model_name, model_roman, category, type_no, unit_no, maker_no, renchan_games, tenjo_games FROM mst_model WHERE model_no = 3");
    $after = $result->fetch(PDO::FETCH_ASSOC);

    echo "【変更後】\n";
    echo "  機種No: {$after['model_no']}\n";
    echo "  機種CD: {$after['model_cd']}\n";
    echo "  機種名: {$after['model_name']}\n";
    echo "  英語名: {$after['model_roman']}\n";
    echo "  カテゴリ: " . ($after['category'] == 2 ? 'スロット' : 'パチンコ') . "\n";
    echo "  タイプNo: {$after['type_no']}\n";
    echo "  号機: {$after['unit_no']}\n";
    echo "  メーカーNo: {$after['maker_no']}\n";
    echo "  連チャンゲーム数: {$after['renchan_games']}\n";
    echo "  天井ゲーム数: {$after['tenjo_games']}\n";

} catch (Exception $e) {
    if (isset($DB)) {
        $DB->autoCommit(true); // rollback
    }
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>
