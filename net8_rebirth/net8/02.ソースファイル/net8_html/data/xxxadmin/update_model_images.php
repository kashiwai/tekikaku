<?php
/**
 * update_model_images.php
 *
 * 機種画像の一括登録・更新スクリプト
 *
 * 機能:
 * - 機種コード（model_cd）で検索して、image_list と image_detail を更新
 * - ローカル画像ファイル（/data/img/model/）のパスを設定
 * - 既存のGCS画像URLは上書きされます
 *
 * 使い方: ブラウザで直接アクセス
 * https://mgg-webservice-production.up.railway.app/data/xxxadmin/update_model_images.php
 *
 * 注意:
 * - 画像ファイルは事前に /data/img/model/ にアップロード必要
 * - 機種コードは mst_model.model_cd と完全一致
 */

require_once('../../_etc/require_files_admin.php');

// 画像マッピング（機種コードまたは機種名 → 画像ファイル名）
// 注意: 3種類の画像（list, detail, reel）すべてに同じ画像を設定
$imageMapping = [
    // 新規追加（機種名での完全一致）
    'SLOT-100' => [  // 吉宗(ピンク)
        'image_list' => 'yoshimune.png',
        'image_detail' => 'yoshimune.png',
        'image_reel' => 'yoshimune.png'
    ],
    'SLOT-101' => [  // 番長
        'image_list' => 'bancho.jpg',
        'image_detail' => 'bancho.jpg',
        'image_reel' => 'bancho.jpg'
    ],
    'SLOT-104' => [  // ジャグラー
        'image_list' => 'jagger01.jpg',
        'image_detail' => 'jagger01.jpg',
        'image_reel' => 'jagger01.jpg'
    ],
    'SLOT-106' => [  // 銭形
        'image_list' => 'zenigata.jpg',
        'image_detail' => 'zenigata.jpg',
        'image_reel' => 'zenigata.jpg'
    ],
    // GCS画像を使っている既存機種の更新（機種コードで検索）
    'HOKUTO4GO' => [
        'image_list' => 'hokuto4go.jpg',
        'image_detail' => 'hokuto4go.jpg',
        'image_reel' => 'hokuto4go.jpg'
    ],
    'NANGOKU01' => [  // 南国育ち
        'image_list' => 'nangoku.jpg',
        'image_detail' => 'nangoku.jpg',
        'image_reel' => 'nangoku.jpg'
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

    foreach ($imageMapping as $searchKey => $images) {
        // 機種コードで検索（完全一致）
        $sql = "SELECT model_no, model_cd, model_name, image_list
                FROM mst_model
                WHERE model_cd = " . $db->conv_sql($searchKey, FD_STR) . "
                  AND del_flg = 0
                LIMIT 1";

        $model = $db->getRow($sql, PDO::FETCH_ASSOC);

        if ($model && !empty($model)) {
            // 画像を更新（3種類すべて）
            $updateSql = "UPDATE mst_model
                         SET image_list = " . $db->conv_sql($images['image_list'], FD_STR) . ",
                             image_detail = " . $db->conv_sql($images['image_detail'], FD_STR) . ",
                             image_reel = " . $db->conv_sql($images['image_reel'], FD_STR) . "
                         WHERE model_no = " . $db->conv_sql($model['model_no'], FD_NUM);

            $db->query($updateSql);

            $results[] = [
                'status' => 'success',
                'model_name' => $model['model_name'],
                'model_cd' => $model['model_cd'],
                'model_no' => $model['model_no'],
                'image' => $images['image_list']
            ];
            $updatedCount++;

            echo "<div style='margin: 10px 0; padding: 10px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
            echo "<strong>✅ 更新成功</strong><br>";
            echo "機種コード: {$model['model_cd']}<br>";
            echo "機種名: {$model['model_name']}<br>";
            echo "機種番号: {$model['model_no']}<br>";
            echo "画像ファイル: {$images['image_list']}<br>";
            echo "URL: <a href='https://mgg-webservice-production.up.railway.app/data/img/model/{$images['image_list']}' target='_blank'>https://mgg-webservice-production.up.railway.app/data/img/model/{$images['image_list']}</a>";
            echo "</div>";

        } else {
            $results[] = [
                'status' => 'not_found',
                'search_code' => $searchKey
            ];
            $notFoundCount++;

            echo "<div style='margin: 10px 0; padding: 10px; background: #fff3e0; border-left: 4px solid #ff9800;'>";
            echo "<strong>⚠️ 機種が見つかりません</strong><br>";
            echo "検索コード: {$searchKey}";
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
    $allModelsSql = "SELECT model_no, model_cd, model_name, image_list, image_detail, image_reel
                     FROM mst_model
                     WHERE del_flg = 0
                     ORDER BY model_no";
    $allModels = $db->getAll($allModelsSql, PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'>
            <th>機種番号</th>
            <th>機種コード</th>
            <th>機種名</th>
            <th>リスト画像</th>
            <th>詳細画像</th>
            <th>リール画像</th>
            <th>プレビュー</th>
          </tr>";

    foreach ($allModels as $m) {
        echo "<tr>";
        echo "<td>{$m['model_no']}</td>";
        echo "<td>{$m['model_cd']}</td>";
        echo "<td>{$m['model_name']}</td>";
        echo "<td>" . ($m['image_list'] ?: '<span style="color: #999;">未設定</span>') . "</td>";
        echo "<td>" . ($m['image_detail'] ?: '<span style="color: #999;">未設定</span>') . "</td>";
        echo "<td>" . ($m['image_reel'] ?: '<span style="color: #999;">未設定</span>') . "</td>";
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
