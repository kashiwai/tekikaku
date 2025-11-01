<?php
/**
 * トップページSQL デバッグスクリプト
 *
 * index.phpで実際に実行されているSQLを出力して、
 * check_machine_status.phpとの違いを確認します
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 環境設定を読み込む
require_once dirname(__FILE__) . '/_sys/setting_base.php';
require_once dirname(__FILE__) . '/_sys/functions.php';
require_once dirname(__FILE__) . '/_sys/SqlString.php';
require_once dirname(__FILE__) . '/_sys/TemplateUser.php';

echo "<h1>🔍 トップページSQL デバッグ</h1>";
echo "<hr>";

$template = new TemplateUser();

echo "<h2>📋 1. データベース接続確認</h2>";
echo "<p>DB Host: " . $template->DB->dsn['hostspec'] . "</p>";
echo "<p>DB Name: " . $template->DB->dsn['database'] . "</p>";
echo "<hr>";

// テスターフラグ確認
$testerFlg = ($template->LOGIN_DATA['member_no'] == MEMBER_NO_TESTER);
echo "<h2>🔧 2. テスターフラグ</h2>";
echo "<p>テスターフラグ: " . ($testerFlg ? 'TRUE' : 'FALSE') . "</p>";
echo "<hr>";

// URLパラメータをシミュレート
$test_cases = [
    ['CN' => '', 'label' => 'CNパラメータなし'],
    ['CN' => 'new', 'label' => 'CN=new（新台）'],
    ['CN' => '1', 'label' => 'CN=1'],
    ['CN' => '2', 'label' => 'CN=2'],
];

echo "<h2>📊 3. URLパラメータ別SQL出力</h2>";

foreach ($test_cases as $test) {
    echo "<h3>テストケース: {$test['label']}</h3>";

    // SQLビルダーを初期化
    $sqls = new SqlString($template->DB);

    // SearchMachineBaseを実行
    $template->SearchMachineBase($sqls, $testerFlg);

    // index.phpと同じフィールド設定
    $sqls->field('dm.machine_no')
         ->field('dm.machine_cd')
         ->field('dm.machine_status')
         ->field('dm.camera_no')
         ->field('dm.signaling_id')
         ->field('mm.model_no')
         ->field('mm.model_name')
         ->field('mm.type_no')
         ->field('mm.category')
         ->field('dmp.count')
         ->field('dmp.total_count')
         ->field('dmp.bb_count')
         ->field('dmp.rb_count');

    // ソート順
    $sqls->orderby('dm.release_date desc');

    // CNパラメータ処理（index.phpと同じロジック）
    $refToDay = GetRefTimeTodayExt();

    if ($test['CN'] == "new") {
        // 新台フィルター
        if (defined('NEW_DAYS')) {
            $sqls->and("DATEDIFF(" . $template->DB->conv_sql($refToDay, FD_DATE) . ", dm.release_date) < ", NEW_DAYS, FD_NUM);
            echo "<p><b>新台フィルター適用:</b> リリースから" . NEW_DAYS . "日以内</p>";
        } else {
            echo "<p><b>警告:</b> NEW_DAYS が定義されていません</p>";
        }
    } else {
        if (mb_strlen($test['CN']) > 0) {
            // コーナーフィルター
            $sqls->and("FIND_IN_SET(" . $template->DB->conv_sql($test['CN'], FD_NUM) . ", dm.machine_corner)");
            echo "<p><b>コーナーフィルター適用:</b> machine_corner に {$test['CN']} が含まれる</p>";
        }
    }

    // 件数取得SQL
    $count_sql = $sqls->resetField()->field("count(*)")->createSQL();
    echo "<h4>件数取得SQL:</h4>";
    echo "<pre style='background:#f0f0f0; padding:10px; overflow:auto;'>" . htmlspecialchars($count_sql) . "</pre>";

    // 件数実行
    try {
        $count_result = $template->DB->getOne($count_sql);
        echo "<p><b>取得件数:</b> " . (int)$count_result . "件</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'><b>エラー:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // データ取得SQL（フィールド再設定）
    $sqls->resetField()
         ->field('dm.machine_no')
         ->field('dm.machine_cd')
         ->field('dm.machine_status')
         ->field('dm.camera_no')
         ->field('dm.signaling_id')
         ->field('mm.model_no')
         ->field('mm.model_name')
         ->field('mm.type_no')
         ->field('mm.category')
         ->field('dmp.count')
         ->field('dmp.total_count')
         ->field('dmp.bb_count')
         ->field('dmp.rb_count');

    $data_sql = $sqls->createSQL();
    echo "<h4>データ取得SQL:</h4>";
    echo "<pre style='background:#f0f0f0; padding:10px; overflow:auto;'>" . htmlspecialchars($data_sql) . "</pre>";

    // データ実行
    try {
        $data_result = $template->DB->getAll($data_sql);
        if (!empty($data_result)) {
            echo "<h4>取得データ:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>台No</th><th>台CD</th><th>機種名</th><th>カメラNo</th><th>シグナリングID</th><th>ステータス</th></tr>";
            foreach ($data_result as $row) {
                $status_label = ['停止中', '稼働中', 'メンテナンス中'][$row['machine_status']] ?? '不明';
                echo "<tr>";
                echo "<td>{$row['machine_no']}</td>";
                echo "<td>{$row['machine_cd']}</td>";
                echo "<td>{$row['model_name']}</td>";
                echo "<td>{$row['camera_no']}</td>";
                echo "<td>{$row['signaling_id']}</td>";
                echo "<td>{$status_label}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange;'><b>該当データなし</b></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'><b>エラー:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "<hr>";
}

// 実機のmachine_corner確認
echo "<h2>🏪 4. 実機のmachine_corner値確認</h2>";
try {
    $corner_check = $template->DB->getAll("
        SELECT
            machine_no,
            machine_cd,
            machine_corner,
            release_date,
            machine_status
        FROM dat_machine
        WHERE del_flg = 0
        ORDER BY machine_no
    ");

    if (!empty($corner_check)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>台No</th><th>台CD</th><th>コーナー</th><th>リリース日</th><th>ステータス</th></tr>";
        foreach ($corner_check as $row) {
            $corner_display = !empty($row['machine_corner']) ? $row['machine_corner'] : '<span style="color:red;">NULL</span>';
            echo "<tr>";
            echo "<td>{$row['machine_no']}</td>";
            echo "<td>{$row['machine_cd']}</td>";
            echo "<td>{$corner_display}</td>";
            echo "<td>{$row['release_date']}</td>";
            echo "<td>{$row['machine_status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ 実機データがありません</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'><b>エラー:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

// check_machine_status.phpのSQLと比較
echo "<h2>🔍 5. check_machine_status.php のSQL（参考）</h2>";
$today = date('Y-m-d');
$reference_sql = "
SELECT
    dm.machine_no,
    dm.machine_cd,
    dm.machine_status,
    dm.camera_no,
    mm.model_name
FROM dat_machine dm
INNER JOIN dat_machinePlay dmp ON dmp.machine_no = dm.machine_no
INNER JOIN lnk_machine lm ON lm.machine_no = dm.machine_no
INNER JOIN mst_model mm ON mm.model_no = dm.model_no AND mm.del_flg <> '1'
WHERE dm.camera_no IS NOT NULL
  AND dm.del_flg <> '1'
  AND dm.release_date <= '$today'
  AND dm.end_date >= '$today'
  AND dm.machine_status <> '0'
ORDER BY dm.machine_no
";

echo "<pre style='background:#e0ffe0; padding:10px; overflow:auto;'>" . htmlspecialchars($reference_sql) . "</pre>";

try {
    $reference_result = $template->DB->getAll($reference_sql);
    echo "<p><b>このSQLの取得件数:</b> " . count($reference_result) . "件</p>";

    if (!empty($reference_result)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>台No</th><th>台CD</th><th>機種名</th><th>カメラNo</th><th>ステータス</th></tr>";
        foreach ($reference_result as $row) {
            $status_label = ['停止中', '稼働中', 'メンテナンス中'][$row['machine_status']] ?? '不明';
            echo "<tr>";
            echo "<td>{$row['machine_no']}</td>";
            echo "<td>{$row['machine_cd']}</td>";
            echo "<td>{$row['model_name']}</td>";
            echo "<td>{$row['camera_no']}</td>";
            echo "<td>{$status_label}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'><b>エラー:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>💡 診断結果</h2>";
echo "<ul>";
echo "<li>SearchMachineBase() のSQL出力を確認</li>";
echo "<li>CNパラメータによるフィルター条件を確認</li>";
echo "<li>machine_corner の値を確認（NULL の場合はFIND_IN_SETでマッチしない）</li>";
echo "<li>check_machine_status.php のSQLと比較</li>";
echo "</ul>";

echo "<h3>🔗 次のアクション</h3>";
echo "<ol>";
echo "<li>CNパラメータなしのSQLで件数が取得できるか確認</li>";
echo "<li>machine_corner がNULLの場合、register_machine_complete.php を修正</li>";
echo "<li>必要に応じて、index.php のCNパラメータ処理を修正</li>";
echo "</ol>";
?>
