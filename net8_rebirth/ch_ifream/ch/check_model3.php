<?php
/**
 * 機種No.3の登録確認スクリプト
 */

require_once('_etc/setting_base.php');
require_once('_etc/setting.php');
require_once('_lib/smartDB.php');

try {
    // DSN形式で接続
    $DB = new SmartDB(DB_DSN);

    echo "機種No.3の登録状況を確認します...\n\n";

    $sql = "SELECT
        model_no, model_cd, model_name, model_roman, category,
        type_no, unit_no, maker_no, renchan_games, tenjo_games,
        image_list, image_detail, image_reel,
        add_dt, upd_dt
    FROM mst_model
    WHERE model_no = 3";

    $result = $DB->query($sql);
    $row = $result->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "✅ 機種No.3が登録されています！\n\n";
        echo "【登録情報】\n";
        echo "  機種No: {$row['model_no']}\n";
        echo "  機種CD: {$row['model_cd']}\n";
        echo "  機種名: {$row['model_name']}\n";
        echo "  英語名: {$row['model_roman']}\n";
        echo "  カテゴリ: " . ($row['category'] == 2 ? 'スロット' : 'パチンコ') . "\n";
        echo "  タイプNo: {$row['type_no']}\n";
        echo "  号機: {$row['unit_no']}\n";
        echo "  メーカーNo: {$row['maker_no']}\n";
        echo "  連チャンゲーム数: {$row['renchan_games']}\n";
        echo "  天井ゲーム数: {$row['tenjo_games']}\n";
        echo "\n【画像情報】\n";
        echo "  一覧画像: " . ($row['image_list'] ? $row['image_list'] : '未設定') . "\n";
        echo "  詳細画像: " . ($row['image_detail'] ? $row['image_detail'] : '未設定') . "\n";
        echo "  リール画像: " . ($row['image_reel'] ? $row['image_reel'] : '未設定') . "\n";
        echo "\n【日時】\n";
        echo "  登録日時: {$row['add_dt']}\n";
        echo "  更新日時: {$row['upd_dt']}\n";
    } else {
        echo "❌ 機種No.3は登録されていません。\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>
