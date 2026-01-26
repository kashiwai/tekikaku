<?php
/*
 * reset_machine1_to_play_mode.php
 *
 * Machine 1 を1:1プレイモードに戻すスクリプト
 */

// インクルード
require_once('_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h2>🔧 Machine 1 を1:1プレイモードに変更</h2>\n";
    echo "<pre>\n";

    // 現在の状態確認
    $sql = "SELECT machine_no, member_no, assign_flg, exit_flg, onetime_id
            FROM lnk_machine
            WHERE machine_no = 1";
    $row = $DB->getRow($sql);

    echo "変更前の状態:\n";
    echo "─────────────────────────────────\n";
    echo "machine_no  : " . $row["machine_no"] . "\n";
    echo "member_no   : " . $row["member_no"] . "\n";
    echo "assign_flg  : " . $row["assign_flg"] . " (9=視聴専用, 0=空き)\n";
    echo "exit_flg    : " . $row["exit_flg"] . "\n";
    echo "onetime_id  : " . (empty($row["onetime_id"]) ? "(空)" : "設定済み") . "\n\n";

    // 1:1プレイモードに変更
    $sql = "UPDATE lnk_machine
            SET assign_flg = 0,
                member_no = 0,
                exit_flg = 0,
                onetime_id = NULL,
                start_dt = NULL
            WHERE machine_no = 1";

    $result = $DB->query($sql);

    echo "✅ 変更実行しました\n\n";

    // 変更後の状態確認
    $sql = "SELECT machine_no, member_no, assign_flg, exit_flg, onetime_id
            FROM lnk_machine
            WHERE machine_no = 1";
    $row = $DB->getRow($sql);

    echo "変更後の状態:\n";
    echo "─────────────────────────────────\n";
    echo "machine_no  : " . $row["machine_no"] . "\n";
    echo "member_no   : " . $row["member_no"] . "\n";
    echo "assign_flg  : " . $row["assign_flg"] . " (0=空き - 1:1プレイ可能)\n";
    echo "exit_flg    : " . $row["exit_flg"] . "\n";
    echo "onetime_id  : " . (empty($row["onetime_id"]) ? "(空)" : "設定済み") . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Machine 1 が1:1プレイモードになりました\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "次の手順:\n";
    echo "1. ログインしてください\n";
    echo "2. /data/play_v2/?NO=1 にアクセス\n";
    echo "3. プレイ画面が表示されます\n\n";

    echo "</pre>\n";

} catch (Exception $e) {
    echo "<h3>エラー:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
