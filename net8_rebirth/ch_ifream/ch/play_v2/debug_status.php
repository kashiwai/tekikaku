<?php
/**
 * Debug Status Page
 * 本番環境のステータス確認用
 */

// インクルード
require_once('../../_etc/require_files.php');

// メイン処理
try {
    $template = new TemplateUser(false);
    $machineNo = $_GET["NO"] ?? 1;

    echo "<h1>Debug Status Check</h1>";
    echo "<h2>Machine NO: {$machineNo}</h2>";

    // 1. 営業時間チェック
    $nowTime = date("H:i");
    echo "<h3>1. 営業時間チェック</h3>";
    echo "現在時刻: {$nowTime}<br>";
    echo "営業開始: " . GLOBAL_OPEN_TIME . "<br>";
    echo "営業終了: " . GLOBAL_CLOSE_TIME . "<br>";

    if ( GLOBAL_CLOSE_TIME <= $nowTime && GLOBAL_OPEN_TIME > $nowTime){
        echo "<strong style='color:red;'>❌ 営業時間外です</strong><br>";
    } else {
        echo "<strong style='color:green;'>✅ 営業時間内です</strong><br>";
    }

    // 2. 台の状態チェック
    echo "<h3>2. 台の状態チェック</h3>";
    $sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
            ->field("machine_no, machine_status")
            ->from("dat_machine")
            ->where()
                ->and( "machine_no =", $machineNo, FD_NUM)
        ->createSQL("\n");
    $machineRow = $template->DB->getRow($sql);

    echo "台番号: {$machineRow['machine_no']}<br>";
    echo "ステータス: {$machineRow['machine_status']}<br>";

    if ($machineRow["machine_status"] != "1") {
        echo "<strong style='color:red;'>❌ この台は現在稼働していません</strong><br>";
    } else {
        echo "<strong style='color:green;'>✅ この台は稼働中です</strong><br>";
    }

    // 3. SDK セッションチェック
    echo "<h3>3. SDK セッションチェック</h3>";
    $sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
            ->field("member_no, partner_user_id, session_id, currency, status")
            ->from("game_sessions")
            ->where()
                ->and( "machine_no =", $machineNo, FD_NUM)
                ->and( "status IN ('playing', 'pending')")
            ->orderBy("started_at DESC")
            ->limit(1)
        ->createSQL("\n");

    $sdkSession = $template->DB->getRow($sql);

    if ($sdkSession && isset($sdkSession['member_no'])) {
        echo "<strong style='color:green;'>✅ SDKセッションが存在します</strong><br>";
        echo "Member NO: {$sdkSession['member_no']}<br>";
        echo "Partner User ID: {$sdkSession['partner_user_id']}<br>";
        echo "Session ID: {$sdkSession['session_id']}<br>";
        echo "Currency: {$sdkSession['currency']}<br>";
        echo "Status: {$sdkSession['status']}<br>";
    } else {
        echo "<strong style='color:red;'>❌ SDKセッションが見つかりません</strong><br>";
        echo "通常ログインが必要です<br>";
    }

    // 4. テスターフラグチェック
    if (isset($template->Session->UserInfo)) {
        echo "<h3>4. ユーザーセッション情報</h3>";
        echo "<pre>";
        print_r($template->Session->UserInfo);
        echo "</pre>";

        $sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
            ->select()
                ->field("member_no, tester_flg")
                ->from("mst_member")
                ->where()
                    ->and( "member_no =", $template->Session->UserInfo["member_no"], FD_NUM)
            ->createSQL("\n");
        $testerRow = $template->DB->getRow($sql);

        echo "テスターフラグ: {$testerRow['tester_flg']}<br>";

        if ($testerRow['tester_flg'] == "1") {
            echo "<strong style='color:green;'>✅ テスターです（営業時間チェックスキップ）</strong><br>";
        } else {
            echo "<strong style='color:orange;'>⚠️ 通常ユーザーです（営業時間チェック有効）</strong><br>";
        }
    } else {
        echo "<h3>4. ユーザーセッション情報</h3>";
        echo "<strong style='color:red;'>❌ ユーザーセッションが存在しません</strong><br>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red;'>エラー発生</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
