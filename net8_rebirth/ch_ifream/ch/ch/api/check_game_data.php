<?php
/**
 * ゲームデータ確認スクリプト
 *
 * log_play, lnk_machine, dat_machineのデータを確認
 * Windows機械への連携が正しく行われているかチェック
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><meta charset='UTF-8'><title>ゲームデータ確認</title></head><body>";
echo "<h1>ゲームデータ確認</h1>";
echo "<pre>\n";

try {
    $db = new NetDB();

    echo "========================================\n";
    echo "ゲームデータ確認開始\n";
    echo "========================================\n\n";

    // 1. log_playテーブルのデータ確認
    echo "【1. log_playテーブル - 最新10件】\n";
    echo "（プレイログ：ゲーム数、大当たり数などが記録される）\n\n";

    $sql1 = "SELECT
                play_dt,
                machine_no,
                member_no,
                start_point,
                in_point,
                out_point,
                play_count,
                bb_count,
                rb_count,
                renchan_count,
                max_credit
            FROM log_play
            ORDER BY play_dt DESC
            LIMIT 10";

    $result1 = $db->query($sql1);
    $count1 = 0;

    while ($row = $result1->fetch(PDO::FETCH_ASSOC)) {
        $count1++;
        echo "----------------------------------------\n";
        echo "プレイログ #{$count1}\n";
        echo "----------------------------------------\n";
        echo "play_dt:       {$row['play_dt']}\n";
        echo "machine_no:    {$row['machine_no']}\n";
        echo "member_no:     {$row['member_no']}\n";
        echo "start_point:   {$row['start_point']}\n";
        echo "in_point:      {$row['in_point']}\n";
        echo "out_point:     {$row['out_point']}\n";
        echo "play_count:    {$row['play_count']} (ゲーム数)\n";
        echo "bb_count:      {$row['bb_count']} (ビッグボーナス)\n";
        echo "rb_count:      {$row['rb_count']} (レギュラーボーナス)\n";
        echo "renchan_count: {$row['renchan_count']} (連チャン)\n";
        echo "max_credit:    {$row['max_credit']}\n\n";
    }

    if ($count1 == 0) {
        echo "⚠️ log_playテーブルにデータがありません\n\n";
    } else {
        echo "合計: {$count1}件のプレイログが見つかりました\n\n";
    }

    // 2. lnk_machineテーブルのデータ確認
    echo "【2. lnk_machineテーブル - 機械別データ】\n";
    echo "（機械ごとのトータル数、大当たり数が記録される）\n\n";

    $sql2 = "SELECT
                machine_no,
                member_no,
                assign_flg,
                day_count,
                total_count,
                count,
                bb_count,
                rb_count,
                mc_in_credit,
                mc_out_credit,
                maxrenchan_count,
                past_max_credit
            FROM lnk_machine
            WHERE machine_no <= 5
            ORDER BY machine_no";

    $result2 = $db->query($sql2);
    $count2 = 0;

    while ($row = $result2->fetch(PDO::FETCH_ASSOC)) {
        $count2++;
        echo "----------------------------------------\n";
        echo "機械 #{$row['machine_no']}\n";
        echo "----------------------------------------\n";
        echo "member_no:        {$row['member_no']}\n";
        echo "assign_flg:       {$row['assign_flg']} (0=空き, 1=使用中)\n";
        echo "day_count:        {$row['day_count']} (本日ゲーム数)\n";
        echo "total_count:      {$row['total_count']} (トータルゲーム数)\n";
        echo "count:            {$row['count']} (現在ゲーム数)\n";
        echo "bb_count:         {$row['bb_count']} (BB回数)\n";
        echo "rb_count:         {$row['rb_count']} (RB回数)\n";
        echo "mc_in_credit:     {$row['mc_in_credit']} (投入クレジット)\n";
        echo "mc_out_credit:    {$row['mc_out_credit']} (払出クレジット)\n";
        echo "maxrenchan_count: {$row['maxrenchan_count']} (最大連チャン)\n";
        echo "past_max_credit:  {$row['past_max_credit']} (過去最大クレジット)\n\n";
    }

    if ($count2 == 0) {
        echo "⚠️ lnk_machineテーブルにデータがありません\n\n";
    }

    // 3. userAuthAPIレスポンスのシミュレーション
    echo "【3. userAuthAPI レスポンス確認】\n";
    echo "（Windows機械が認証時に受け取るデータ）\n\n";

    $sql3 = "SELECT
                lm.machine_no,
                lm.member_no,
                lm.day_count,
                lm.total_count,
                lm.count,
                lm.bb_count,
                lm.rb_count,
                lm.mc_in_credit,
                lm.mc_out_credit,
                lm.maxrenchan_count,
                lm.past_max_credit,
                lm.past_max_bb,
                lm.past_max_rb,
                mm.playpoint,
                mm.drawpoint
            FROM lnk_machine lm
            LEFT JOIN mst_member mm ON lm.member_no = mm.member_no
            WHERE lm.machine_no = 1
              AND lm.assign_flg = 1";

    $result3 = $db->query($sql3);
    $row3 = $result3->fetch(PDO::FETCH_ASSOC);

    if ($row3) {
        echo "機械No.1の認証データ:\n";
        echo "----------------------------------------\n";
        echo "{\n";
        echo "  \"member_no\": \"{$row3['member_no']}\",\n";
        echo "  \"playpoint\": \"{$row3['playpoint']}\",\n";
        echo "  \"drawpoint\": \"{$row3['drawpoint']}\",\n";
        echo "  \"day_count\": \"{$row3['day_count']}\",      // 本日ゲーム数\n";
        echo "  \"total_count\": \"{$row3['total_count']}\",  // トータルゲーム数\n";
        echo "  \"count\": \"{$row3['count']}\",              // 現在ゲーム数\n";
        echo "  \"bb_count\": \"{$row3['bb_count']}\",        // BB回数\n";
        echo "  \"rb_count\": \"{$row3['rb_count']}\",        // RB回数\n";
        echo "  \"mc_in_credit\": \"{$row3['mc_in_credit']}\",\n";
        echo "  \"mc_out_credit\": \"{$row3['mc_out_credit']}\",\n";
        echo "  \"maxrenchan_count\": \"{$row3['maxrenchan_count']}\",\n";
        echo "  \"past_max_credit\": \"{$row3['past_max_credit']}\",\n";
        echo "  \"past_max_bb\": \"{$row3['past_max_bb']}\",\n";
        echo "  \"past_max_rb\": \"{$row3['past_max_rb']}\"\n";
        echo "}\n\n";

        // データ妥当性チェック
        $hasData = ($row3['day_count'] > 0 || $row3['total_count'] > 0 || $row3['bb_count'] > 0);

        if ($hasData) {
            echo "✅ ゲームデータが正しく記録されています\n";
        } else {
            echo "⚠️ ゲームデータがすべて0です（まだプレイされていない可能性）\n";
        }
    } else {
        echo "⚠️ 機械No.1が使用中ではないか、データがありません\n";
    }

    echo "\n";

    // 4. データ保存APIの確認
    echo "【4. データ保存処理の確認】\n";
    echo "（Windows機械からのデータ送信を受け取るAPI）\n\n";

    $apiFiles = [
        'data/api/playDataSaveAPI.php',
        'data/api/playDataUpdateAPI.php',
        'data/api/gameResultSaveAPI.php'
    ];

    foreach ($apiFiles as $file) {
        $fullPath = __DIR__ . '/../../' . $file;
        if (file_exists($fullPath)) {
            echo "✅ {$file} が存在します\n";
        } else {
            echo "❌ {$file} が見つかりません\n";
        }
    }

    echo "\n========================================\n";
    echo "確認完了\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo $e->getMessage() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre></body></html>";
?>
