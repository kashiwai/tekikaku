<?php
/**
 * データベース詳細診断ツール
 *
 * 北斗の拳・銭形のデータベース状態を徹底的に調査
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 100) . "\n";
echo "  データベース詳細診断 - 北斗の拳・銭形\n";
echo "=" . str_repeat("=", 100) . "\n\n";

// 1. dat_machine テーブルの詳細確認
echo "📊 dat_machine テーブル（マシン1, 2のみ）:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT * FROM dat_machine WHERE machine_no IN (1, 2) ORDER BY machine_no";
$machines = $template->DB->getAll($sql);

foreach ($machines as $machine) {
    echo "\n【マシン {$machine['machine_no']}】\n";
    foreach ($machine as $key => $value) {
        echo sprintf("  %-20s : %s\n", $key, $value ?? 'NULL');
    }
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 2. lnk_machine テーブルの詳細確認
echo "🔗 lnk_machine テーブル（マシン1, 2のみ）:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT * FROM lnk_machine WHERE machine_no IN (1, 2) ORDER BY machine_no";
$links = $template->DB->getAll($sql);

if (empty($links)) {
    echo "⚠️ lnk_machine にマシン1, 2のレコードが存在しません！\n";
    echo "   これが原因でプレイヤーが動かない可能性があります。\n\n";
} else {
    foreach ($links as $link) {
        echo "\n【マシン {$link['machine_no']}】\n";
        foreach ($link as $key => $value) {
            $display_value = $value ?? 'NULL';
            if ($key === 'assign_flg') {
                $status_text = match($value) {
                    '0' => '未割り当て',
                    '1' => '割り当て中',
                    '9' => 'カメラ待機中 ✅',
                    default => '不明'
                };
                $display_value .= " ({$status_text})";
            }
            echo sprintf("  %-20s : %s\n", $key, $display_value);
        }
    }
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 3. mst_camera テーブルの詳細確認（マシン1, 2に紐づくカメラのみ）
echo "📷 mst_camera テーブル（マシン1, 2に紐づくカメラのみ）:\n";
echo str_repeat("-", 100) . "\n";

if (!empty($machines)) {
    $camera_nos = array_unique(array_column($machines, 'camera_no'));
    $camera_nos_str = implode(',', $camera_nos);

    $sql = "SELECT * FROM mst_camera WHERE camera_no IN ($camera_nos_str) AND del_flg = 0";
    $cameras = $template->DB->getAll($sql);

    foreach ($cameras as $camera) {
        echo "\n【カメラ {$camera['camera_no']}】\n";
        foreach ($camera as $key => $value) {
            echo sprintf("  %-20s : %s\n", $key, $value ?? 'NULL');
        }
    }
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 4. mst_model テーブルの詳細確認（北斗・銭形のみ）
echo "🎰 mst_model テーブル（北斗・銭形のみ）:\n";
echo str_repeat("-", 100) . "\n";

if (!empty($machines)) {
    $model_nos = array_unique(array_column($machines, 'model_no'));
    $model_nos_str = implode(',', $model_nos);

    $sql = "SELECT * FROM mst_model WHERE model_no IN ($model_nos_str)";
    $models = $template->DB->getAll($sql);

    foreach ($models as $model) {
        echo "\n【機種 {$model['model_no']}】\n";
        foreach ($model as $key => $value) {
            if ($key === 'layout_data' || $key === 'prizeball_data') {
                echo sprintf("  %-20s : %s\n", $key, mb_strlen($value) > 100 ? '[JSON: ' . mb_strlen($value) . ' bytes]' : $value);
            } else {
                echo sprintf("  %-20s : %s\n", $key, $value ?? 'NULL');
            }
        }
    }
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 5. 問題診断
echo "🔍 問題診断:\n";
echo str_repeat("=", 100) . "\n\n";

$issues = [];

// チェック1: lnk_machineレコードの存在
if (empty($links)) {
    $issues[] = [
        'severity' => 'CRITICAL',
        'issue' => 'lnk_machine テーブルにマシン1, 2のレコードが存在しません',
        'solution' => 'INSERT INTO lnk_machine (machine_no, assign_flg, member_no, onetime_id, start_dt) VALUES (1, 9, 0, \'\', \'\')'
    ];
} else {
    // チェック2: assign_flgの値
    foreach ($links as $link) {
        if ($link['assign_flg'] != '9') {
            $issues[] = [
                'severity' => 'HIGH',
                'issue' => "マシン{$link['machine_no']}: assign_flg が {$link['assign_flg']} です（正しくは 9 であるべき）",
                'solution' => "UPDATE lnk_machine SET assign_flg = 9, member_no = 0, onetime_id = '', start_dt = '' WHERE machine_no = {$link['machine_no']}"
            ];
        }

        // チェック3: member_noの値
        if ($link['member_no'] != '0' && $link['member_no'] != '') {
            $issues[] = [
                'severity' => 'MEDIUM',
                'issue' => "マシン{$link['machine_no']}: member_no が {$link['member_no']} です（カメラ待機中は 0 であるべき）",
                'solution' => "UPDATE lnk_machine SET member_no = 0 WHERE machine_no = {$link['machine_no']}"
            ];
        }
    }
}

// チェック4: camera_noの重複
$camera_usage = [];
foreach ($machines as $machine) {
    $camera_no = $machine['camera_no'];
    if (!isset($camera_usage[$camera_no])) {
        $camera_usage[$camera_no] = [];
    }
    $camera_usage[$camera_no][] = $machine['machine_no'];
}

foreach ($camera_usage as $camera_no => $machine_nos) {
    if (count($machine_nos) > 1) {
        $issues[] = [
            'severity' => 'HIGH',
            'issue' => "カメラ{$camera_no} が複数のマシン（" . implode(', ', $machine_nos) . "）に割り当てられています",
            'solution' => '各マシンに異なるカメラを割り当ててください'
        ];
    }
}

// チェック5: signaling_idの確認
foreach ($machines as $machine) {
    if (empty($machine['signaling_id'])) {
        $issues[] = [
            'severity' => 'MEDIUM',
            'issue' => "マシン{$machine['machine_no']}: signaling_id が未設定です",
            'solution' => "UPDATE dat_machine SET signaling_id = 'default' WHERE machine_no = {$machine['machine_no']}"
        ];
    }
}

// 問題の表示
if (empty($issues)) {
    echo "✅ データベースに問題は見つかりませんでした。\n\n";
} else {
    foreach ($issues as $idx => $issue) {
        $num = $idx + 1;
        $severity_color = match($issue['severity']) {
            'CRITICAL' => '🔴',
            'HIGH' => '🟠',
            'MEDIUM' => '🟡',
            default => '⚪'
        };

        echo "{$severity_color} 問題 {$num}: [{$issue['severity']}]\n";
        echo "   内容: {$issue['issue']}\n";
        echo "   解決策: {$issue['solution']}\n\n";
    }
}

// 6. 自動修正スクリプト生成
if (!empty($issues)) {
    echo str_repeat("=", 100) . "\n";
    echo "🔧 自動修正SQLスクリプト:\n";
    echo str_repeat("=", 100) . "\n\n";

    echo "-- 以下のSQLを実行すると問題が修正されます\n";
    echo "-- ⚠️ 実行前に必ずバックアップを取ってください\n\n";

    foreach ($issues as $issue) {
        if (strpos($issue['solution'], 'INSERT') === 0 ||
            strpos($issue['solution'], 'UPDATE') === 0 ||
            strpos($issue['solution'], 'DELETE') === 0) {
            echo $issue['solution'] . ";\n";
        }
    }

    echo "\n-- 修正完了後、以下で確認してください:\n";
    echo "SELECT dm.machine_no, dm.camera_no, lm.assign_flg, lm.member_no\n";
    echo "FROM dat_machine dm\n";
    echo "LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no\n";
    echo "WHERE dm.machine_no IN (1, 2);\n\n";
}

echo str_repeat("=", 100) . "\n";
echo "📋 次のステップ:\n";
echo str_repeat("=", 100) . "\n\n";

if (empty($issues)) {
    echo "1. Windows PC側で slotserver.exe を起動してください\n";
    echo "2. プレイヤー画面を開いてテストしてください:\n";
    echo "   https://mgg-webservice-production.up.railway.app/play_v2/?NO=1\n";
    echo "3. 診断ツールで接続確認:\n";
    echo "   https://mgg-webservice-production.up.railway.app/data/debug_player.php?NO=1\n";
} else {
    echo "1. 上記の自動修正SQLスクリプトを実行してください\n";
    echo "2. 再度このスクリプトを実行して問題が解決したか確認してください\n";
    echo "3. 問題が解決したら、Windows PC側で slotserver.exe を起動してください\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
?>
