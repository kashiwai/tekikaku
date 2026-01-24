<?php
/**
 * 営業時間設定マイグレーション実行スクリプト
 *
 * mst_settingテーブルに営業時間設定を追加
 *
 * アクセス: https://mgg-webservice-production.up.railway.app/data/api/migrate_business_hours.php
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><meta charset='UTF-8'><title>営業時間設定マイグレーション</title></head><body>";
echo "<h1>営業時間設定マイグレーション</h1>";
echo "<pre>\n";

try {
    $db = new NetDB();

    echo "========================================\n";
    echo "営業時間設定マイグレーション開始\n";
    echo "========================================\n\n";

    // 1. 既存の営業時間設定を確認
    echo "【1. 既存の設定確認】\n";
    $sql = "SELECT setting_no, setting_key, setting_val
            FROM mst_setting
            WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
              AND del_flg = 0";

    $result = $db->query($sql);
    $existingSettings = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existingSettings[$row['setting_key']] = $row;
        echo "  既存: {$row['setting_key']} = {$row['setting_val']}\n";
    }

    if (count($existingSettings) > 0) {
        echo "\n⚠️ 既に営業時間設定が存在します。\n";
        echo "スキップします。\n\n";
    } else {
        echo "  既存設定なし。新規追加を実行します。\n\n";

        // 2. 営業開始時刻
        echo "【2. 営業開始時刻 (GLOBAL_OPEN_TIME) 追加】\n";
        $sql2 = "INSERT INTO `mst_setting` (
                  `setting_no`,
                  `setting_type`,
                  `setting_name`,
                  `setting_key`,
                  `setting_format`,
                  `setting_val`,
                  `remarks`,
                  `del_flg`,
                  `del_no`,
                  `del_dt`,
                  `add_no`,
                  `add_dt`,
                  `upd_no`,
                  `upd_dt`
                ) VALUES (
                  2,
                  3,
                  '営業開始時刻',
                  'GLOBAL_OPEN_TIME',
                  1,
                  '10:00',
                  '営業開始時刻を HH:MM 形式で設定します。\r\nこの時刻以降、ユーザーは台でプレイ可能になります。\r\n例: 10:00',
                  0,
                  NULL,
                  NULL,
                  1,
                  NOW(),
                  1,
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                  setting_val = VALUES(setting_val),
                  upd_no = 1,
                  upd_dt = NOW()";

        $db->query($sql2);
        echo "  ✅ 営業開始時刻を追加しました (デフォルト: 10:00)\n\n";

        // 3. 営業終了時刻
        echo "【3. 営業終了時刻 (GLOBAL_CLOSE_TIME) 追加】\n";
        $sql3 = "INSERT INTO `mst_setting` (
                  `setting_no`,
                  `setting_type`,
                  `setting_name`,
                  `setting_key`,
                  `setting_format`,
                  `setting_val`,
                  `remarks`,
                  `del_flg`,
                  `del_no`,
                  `del_dt`,
                  `add_no`,
                  `add_dt`,
                  `upd_no`,
                  `upd_dt`
                ) VALUES (
                  3,
                  3,
                  '営業終了時刻',
                  'GLOBAL_CLOSE_TIME',
                  1,
                  '22:00',
                  '営業終了時刻を HH:MM 形式で設定します。\r\nこの時刻以降、新規プレイは開始できません。\r\n例: 22:00',
                  0,
                  NULL,
                  NULL,
                  1,
                  NOW(),
                  1,
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                  setting_val = VALUES(setting_val),
                  upd_no = 1,
                  upd_dt = NOW()";

        $db->query($sql3);
        echo "  ✅ 営業終了時刻を追加しました (デフォルト: 22:00)\n\n";

        // 4. 基準時刻
        echo "【4. 基準時刻 (REFERENCE_TIME) 追加】\n";
        $sql4 = "INSERT INTO `mst_setting` (
                  `setting_no`,
                  `setting_type`,
                  `setting_name`,
                  `setting_key`,
                  `setting_format`,
                  `setting_val`,
                  `remarks`,
                  `del_flg`,
                  `del_no`,
                  `del_dt`,
                  `add_no`,
                  `add_dt`,
                  `upd_no`,
                  `upd_dt`
                ) VALUES (
                  4,
                  3,
                  '基準時刻（日跨ぎ判定）',
                  'REFERENCE_TIME',
                  1,
                  '04:00',
                  '日付の基準時刻を HH:MM 形式で設定します。\r\nこの時刻より前は前日として扱われます。\r\n例: 04:00 (午前4時より前は前日扱い)',
                  0,
                  NULL,
                  NULL,
                  1,
                  NOW(),
                  1,
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                  setting_val = VALUES(setting_val),
                  upd_no = 1,
                  upd_dt = NOW()";

        $db->query($sql4);
        echo "  ✅ 基準時刻を追加しました (デフォルト: 04:00)\n\n";
    }

    // 5. 追加された設定を確認
    echo "【5. 最終確認】\n";
    $sql5 = "SELECT
              setting_no,
              setting_type,
              setting_name,
              setting_key,
              setting_val,
              remarks,
              add_dt,
              upd_dt
            FROM mst_setting
            WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
              AND del_flg = 0
            ORDER BY setting_no";

    $result5 = $db->query($sql5);
    $count = 0;
    while ($row = $result5->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "----------------------------------------\n";
        echo "設定 #{$count}\n";
        echo "----------------------------------------\n";
        echo "setting_no:   {$row['setting_no']}\n";
        echo "setting_key:  {$row['setting_key']}\n";
        echo "setting_name: {$row['setting_name']}\n";
        echo "setting_val:  {$row['setting_val']}\n";
        echo "remarks:      " . str_replace("\r\n", " / ", $row['remarks']) . "\n";
        echo "add_dt:       {$row['add_dt']}\n";
        echo "upd_dt:       {$row['upd_dt']}\n";
        echo "\n";
    }

    if ($count == 3) {
        echo "✅ マイグレーション成功！営業時間設定が3件追加されました。\n";
    } else {
        echo "⚠️ 予期しない結果: {$count}件の設定が見つかりました（期待値: 3件）\n";
    }

    // 6. 全設定件数を確認
    $sql6 = "SELECT COUNT(*) as total FROM mst_setting WHERE del_flg = 0";
    $result6 = $db->query($sql6);
    $row6 = $result6->fetch(PDO::FETCH_ASSOC);
    echo "\nmst_setting総数: {$row6['total']}件\n";

    echo "\n========================================\n";
    echo "マイグレーション完了\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo $e->getMessage() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre></body></html>";
?>
