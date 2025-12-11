<?php
/**
 * 北斗の拳 緊急診断スクリプト
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 100) . "\n";
echo "  🚨 北斗の拳 緊急診断\n";
echo "=" . str_repeat("=", 100) . "\n\n";

// 1. マシン1の完全な状態確認
echo "📊 マシン1（北斗の拳）の現在の状態:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT * FROM dat_machine WHERE machine_no = 1";
$machine = $template->DB->getRow($sql);

if (empty($machine)) {
    echo "❌❌❌ 致命的エラー: マシン1が dat_machine から消えています！\n\n";
} else {
    echo "✅ dat_machine にマシン1が存在します\n";
    echo "  camera_no: {$machine['camera_no']}\n";
    echo "  model_no: {$machine['model_no']}\n";
    echo "  signaling_id: {$machine['signaling_id']}\n";
    echo "  del_flg: {$machine['del_flg']}\n\n";
}

// 2. lnk_machine の確認
$sql = "SELECT * FROM lnk_machine WHERE machine_no = 1";
$link = $template->DB->getRow($sql);

if (empty($link)) {
    echo "❌❌❌ 致命的エラー: lnk_machine にマシン1のレコードがありません！\n\n";
} else {
    echo "✅ lnk_machine にマシン1が存在します\n";
    echo "  assign_flg: {$link['assign_flg']} " . ($link['assign_flg'] == 9 ? '✅' : '❌ 異常') . "\n";
    echo "  member_no: {$link['member_no']} " . ($link['member_no'] == 0 ? '✅' : '❌ 異常') . "\n";
    echo "  start_dt: {$link['start_dt']}\n\n";
}

// 3. カメラの確認
if (!empty($machine)) {
    $camera_no = $machine['camera_no'];
    $sql = "SELECT * FROM mst_camera WHERE camera_no = $camera_no AND del_flg = 0";
    $camera = $template->DB->getRow($sql);

    if (empty($camera)) {
        echo "❌❌❌ 致命的エラー: カメラ{$camera_no}が mst_camera から消えています！\n\n";
    } else {
        echo "✅ カメラ{$camera_no} が存在します\n";
        echo "  camera_mac: {$camera['camera_mac']}\n";
        echo "  camera_name: {$camera['camera_name']}\n\n";
    }
}

// 4. 機種情報の確認
if (!empty($machine)) {
    $model_no = $machine['model_no'];
    $sql = "SELECT * FROM mst_model WHERE model_no = $model_no";
    $model = $template->DB->getRow($sql);

    if (empty($model)) {
        echo "❌ 警告: 機種{$model_no}が mst_model から消えています！\n\n";
    } else {
        echo "✅ 機種情報が存在します\n";
        echo "  model_name: {$model['model_name']}\n";
        echo "  category: {$model['category']}\n\n";
    }
}

echo str_repeat("-", 100) . "\n\n";

// 5. 全体の整合性チェック
echo "🔍 整合性チェック:\n";
echo str_repeat("-", 100) . "\n";

$issues = [];

if (empty($machine)) {
    $issues[] = "CRITICAL: マシン1が dat_machine に存在しない";
}

if (empty($link)) {
    $issues[] = "CRITICAL: マシン1が lnk_machine に存在しない";
}

if (!empty($machine) && empty($camera)) {
    $issues[] = "CRITICAL: マシン1に割り当てられたカメラが存在しない";
}

if (!empty($link) && $link['assign_flg'] != 9) {
    $issues[] = "HIGH: assign_flg が {$link['assign_flg']} (正しくは 9)";
}

if (!empty($link) && $link['member_no'] != 0) {
    $issues[] = "MEDIUM: member_no が {$link['member_no']} (正しくは 0)";
}

if (empty($issues)) {
    echo "✅ データベースに問題はありません\n\n";
    echo "Windows PC側の問題の可能性があります:\n";
    echo "  1. slotserver.exe が停止している\n";
    echo "  2. Chromeが閉じてしまった\n";
    echo "  3. ネットワーク接続が切れた\n";
    echo "  4. カメラデバイスが認識されていない\n\n";
} else {
    echo "❌ 以下の問題が見つかりました:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
    echo "\n";
}

echo str_repeat("-", 100) . "\n\n";

// 6. 修正スクリプト生成
if (!empty($issues)) {
    echo "🔧 自動修正を実行します:\n";
    echo str_repeat("-", 100) . "\n";

    $template->DB->beginTransaction();

    try {
        // lnk_machine が存在しない場合は作成
        if (empty($link)) {
            echo "lnk_machine にマシン1のレコードを作成します...\n";
            $sql = "INSERT INTO lnk_machine (machine_no, assign_flg, member_no, onetime_id, exit_flg, start_dt, end_dt)
                    VALUES (1, 9, 0, NULL, 0, NULL, NULL)";
            $result = $template->DB->query($sql);
            if ($result) {
                echo "✅ lnk_machine にマシン1を追加しました\n";
            }
        } else {
            // assign_flg または member_no を修正
            $sql = (new SqlString($template->DB))
                ->update('lnk_machine')
                ->set()
                    ->value('assign_flg', '9', FD_NUM)
                    ->value('member_no', '0', FD_NUM)
                    ->value('onetime_id', '', FD_STR)
                    ->value('start_dt', '', FD_DATE)
                ->where()
                    ->and('machine_no =', '1', FD_NUM)
                ->createSQL();

            $result = $template->DB->query($sql);
            if ($result) {
                echo "✅ マシン1の lnk_machine を修正しました\n";
            }
        }

        $template->DB->commit();
        echo "\n✅ 修正完了！\n";

    } catch (Exception $e) {
        $template->DB->rollback();
        echo "\n❌ エラー: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "📋 次のアクション:\n";
echo str_repeat("=", 100) . "\n\n";

if (empty($issues) || isset($result)) {
    echo "Windows PC側で以下を確認してください:\n\n";
    echo "1. slotserver.exe が実行中か確認:\n";
    echo "   タスクマネージャーを開いて slotserver.exe を探す\n\n";

    echo "2. slotserver.exe を再起動:\n";
    echo "   cd C:\\serverset\n";
    echo "   slotserver.exe\n\n";

    echo "3. コンソール出力を確認:\n";
    echo "   - MAC:34-a6-ef-35-73-73 が表示されるか\n";
    echo "   - Machine No: 1 が表示されるか\n";
    echo "   - status:ok が表示されるか\n\n";

    echo "4. Chromeが自動的に開くか確認\n\n";

    echo "5. プレイヤー画面でテスト:\n";
    echo "   https://mgg-webservice-production.up.railway.app/play_v2/?NO=1\n\n";
}

echo str_repeat("=", 100) . "\n";
?>
