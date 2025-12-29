<?php
/**
 * update_model_images.php
 *
 * 機種画像の一括登録・更新スクリプト
 *
 * 使い方: ブラウザで直接アクセス
 * https://mgg-webservice-production.up.railway.app/data/xxxadmin/update_model_images.php
 */

require_once('../../_etc/require_files_admin.php');

// 画像マッピング（機種名 → 画像ファイル名）
$imageMapping = [
    // 吉宗（ピンク）
    '吉宗' => [
        'image_list' => 'yoshimune.png',
        'image_detail' => 'yoshimune.png'
    ],
    // 南国物語
    '南国物語' => [
        'image_list' => 'nangoku.jpg',
        'image_detail' => 'nangoku.jpg'
    ],
    // 押忍！番長
    '押忍！番長' => [
        'image_list' => 'bancho.jpg',
        'image_detail' => 'bancho.jpg'
    ],
    '番長' => [
        'image_list' => 'bancho.jpg',
        'image_detail' => 'bancho.jpg'
    ],
    // 既存の画像も登録
    '北斗の拳' => [
        'image_list' => 'hokuto4go.jpg',
        'image_detail' => 'hokuto4go.jpg'
    ],
    '主役は銭形' => [
        'image_list' => 'zenigata.jpg',
        'image_detail' => 'zenigata.jpg'
    ],
    'ミリオンゴッド' => [
        'image_list' => 'milliongod_gaisen.jpg',
        'image_detail' => 'milliongod_gaisen.jpg'
    ],
    'ジャグラー' => [
        'image_list' => 'jagger01.jpg',
        'image_detail' => 'jagger01.jpg'
    ]
];

try {
    $db = new NetDB();

    echo "<html><head><meta charset='UTF-8'><title>画像登録結果</title></head><body>";
    echo "<h1>機種画像登録スクリプト</h1>";
    echo "<p>実行日時: " . date('Y-m-d H:i:s') . "</p>";
    echo "<hr>";

    $updatedCount = 0;
    $notFoundCount = 0;
    $results = [];

    foreach ($imageMapping as $modelName => $images) {
        // 機種名で検索（部分一致）
        $sql = "SELECT model_no, model_id, model_name, model_name_ja, image_list
                FROM mst_model
                WHERE model_name LIKE '%" . $modelName . "%'
                   OR model_name_ja LIKE '%" . $modelName . "%'
                LIMIT 1";

        $model = $db->getRow($sql, PDO::FETCH_ASSOC);

        if ($model && !empty($model)) {
            // 画像を更新
            $updateSql = "UPDATE mst_model
                         SET image_list = " . $db->conv_sql($images['image_list'], FD_STR) . ",
                             image_detail = " . $db->conv_sql($images['image_detail'], FD_STR) . "
                         WHERE model_no = " . $db->conv_sql($model['model_no'], FD_NUM);

            $db->query($updateSql);

            $results[] = [
                'status' => 'success',
                'model_name' => $model['model_name'],
                'model_id' => $model['model_id'],
                'model_no' => $model['model_no'],
                'image' => $images['image_list']
            ];
            $updatedCount++;

            echo "<div style='margin: 10px 0; padding: 10px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
            echo "<strong>✅ 更新成功</strong><br>";
            echo "機種名: {$model['model_name']} ({$model['model_id']})<br>";
            echo "機種番号: {$model['model_no']}<br>";
            echo "画像ファイル: {$images['image_list']}<br>";
            echo "URL: <a href='https://mgg-webservice-production.up.railway.app/data/img/model/{$images['image_list']}' target='_blank'>https://mgg-webservice-production.up.railway.app/data/img/model/{$images['image_list']}</a>";
            echo "</div>";

        } else {
            $results[] = [
                'status' => 'not_found',
                'search_name' => $modelName
            ];
            $notFoundCount++;

            echo "<div style='margin: 10px 0; padding: 10px; background: #fff3e0; border-left: 4px solid #ff9800;'>";
            echo "<strong>⚠️ 機種が見つかりません</strong><br>";
            echo "検索名: {$modelName}";
            echo "</div>";
        }
    }

    echo "<hr>";
    echo "<h2>実行結果サマリー</h2>";
    echo "<p>✅ 更新成功: <strong>{$updatedCount}</strong> 件</p>";
    echo "<p>⚠️ 機種未検出: <strong>{$notFoundCount}</strong> 件</p>";

    // 全機種リスト表示
    echo "<hr>";
    echo "<h2>データベース内の全機種</h2>";
    $allModelsSql = "SELECT model_no, model_id, model_name, model_name_ja, image_list
                     FROM mst_model
                     ORDER BY model_no";
    $allModels = $db->getAll($allModelsSql, PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'>
            <th>機種番号</th>
            <th>機種ID</th>
            <th>機種名</th>
            <th>機種名(日本語)</th>
            <th>画像ファイル</th>
            <th>プレビュー</th>
          </tr>";

    foreach ($allModels as $m) {
        echo "<tr>";
        echo "<td>{$m['model_no']}</td>";
        echo "<td>{$m['model_id']}</td>";
        echo "<td>{$m['model_name']}</td>";
        echo "<td>{$m['model_name_ja']}</td>";
        echo "<td>" . ($m['image_list'] ?: '<span style="color: #999;">未設定</span>') . "</td>";
        echo "<td>";
        if ($m['image_list']) {
            $imageUrl = "https://mgg-webservice-production.up.railway.app/data/img/model/{$m['image_list']}";
            echo "<a href='{$imageUrl}' target='_blank'><img src='{$imageUrl}' style='max-width: 50px; max-height: 50px;' /></a>";
        } else {
            echo "-";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "</body></html>";

} catch (Exception $e) {
    echo "<div style='margin: 10px 0; padding: 10px; background: #ffebee; border-left: 4px solid #f44336;'>";
    echo "<strong>❌ エラー発生</strong><br>";
    echo "エラー内容: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "</body></html>";
}
?>
