<?php
/*
 * debug_auth_flow.php
 *
 * 認証フローのデバッグ用スクリプト
 */

header('Content-Type: text/plain; charset=utf-8');

// インクルード
require_once('_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔍 WebRTC認証フロー デバッグ\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Machine 1の状態確認
    $sql = "SELECT machine_no, member_no, assign_flg, exit_flg, onetime_id, start_dt
            FROM lnk_machine
            WHERE machine_no = 1";

    $row = $DB->getRow($sql);

    if ($row) {
        echo "【1】lnk_machine テーブルの現在状態:\n";
        echo "────────────────────────────────────────\n";
        echo "machine_no   : " . $row["machine_no"] . "\n";
        echo "member_no    : " . $row["member_no"] . "\n";
        echo "assign_flg   : " . $row["assign_flg"] . "\n";
        echo "exit_flg     : " . $row["exit_flg"] . "\n";
        echo "onetime_id   : " . (empty($row["onetime_id"]) ? "(空)" : substr($row["onetime_id"], 0, 40) . "...") . "\n";
        echo "start_dt     : " . $row["start_dt"] . "\n\n";

        echo "【2】ハッシュ値の検証:\n";
        echo "────────────────────────────────────────\n";

        // 現在のmember_noから計算
        $currentHash = sha1(sprintf("%06d", $row["member_no"]));
        echo "現在のmember_no={$row["member_no"]}のハッシュ:\n";
        echo "  → {$currentHash}\n\n";

        // member_no=0の場合（期待値）
        $expectedHash = sha1(sprintf("%06d", 0));
        echo "視聴専用（member_no=0）の期待ハッシュ:\n";
        echo "  → {$expectedHash}\n\n";

        // 一致チェック
        if ($row["member_no"] == 0) {
            echo "✅ member_no = 0（正常）\n";
        } else {
            echo "❌ member_no = {$row["member_no"]}（異常！0である必要があります）\n";
        }

        if ($currentHash === $expectedHash) {
            echo "✅ ハッシュ値が一致\n\n";
        } else {
            echo "❌ ハッシュ値が不一致！\n\n";
        }

        echo "【3】userAuthAPI.phpの動作シミュレーション:\n";
        echo "────────────────────────────────────────\n";

        if (empty($row["onetime_id"])) {
            echo "❌ onetime_idが空のため、認証失敗します\n";
        } else {
            echo "プレイヤーが送信する値:\n";
            echo "  MEMBERNO        = {$currentHash}\n";
            echo "  ONETIMEAUTHID   = " . substr($row["onetime_id"], 0, 40) . "...\n\n";

            echo "userAuthAPI.phpでの検証:\n";
            echo "  1. lnk_machineからmember_no取得 → {$row["member_no"]}\n";
            echo "  2. 期待ハッシュ計算 → {$currentHash}\n";
            echo "  3. 送信されたMEMBERNOと比較\n";

            if ($row["member_no"] == 0 && !empty($row["onetime_id"])) {
                echo "  → ✅ 認証成功 [OK]\n";
            } else {
                echo "  → ❌ 認証失敗 [NG]\n";

                if ($row["member_no"] != 0) {
                    echo "     理由: member_noが0ではない\n";
                }
                if (empty($row["onetime_id"])) {
                    echo "     理由: onetime_idが空\n";
                }
            }
        }

        echo "\n【4】修正方法:\n";
        echo "────────────────────────────────────────\n";
        if ($row["member_no"] != 0) {
            echo "次のSQLを実行してください:\n\n";
            echo "UPDATE lnk_machine\n";
            echo "SET member_no = 0,\n";
            echo "    assign_flg = 9\n";
            echo "WHERE machine_no = 1;\n\n";

            echo "または play_public/?NO=1 を再読み込みしてください。\n";
        } else {
            echo "✅ 設定は正常です。\n";
            echo "Windows PCを再起動してください。\n";
        }

    } else {
        echo "❌ Machine 1のレコードが見つかりません\n";
    }

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>
