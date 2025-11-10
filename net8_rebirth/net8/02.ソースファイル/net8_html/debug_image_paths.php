<?php
/**
 * 画像パスデバッグ用スクリプト
 * データベースから機種情報と画像パスを取得して表示
 */

require_once(__DIR__ . '/_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>画像パス デバッグ情報</h1>";
echo "<hr>";

// DIR_IMG_MODEL_DIR 定数の確認
echo "<h2>1. 画像パス定数</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>定数名</th><th>値</th></tr>";
echo "<tr><td>DIR_IMG_MODEL</td><td>" . (defined('DIR_IMG_MODEL') ? DIR_IMG_MODEL : '未定義') . "</td></tr>";
echo "<tr><td>DIR_IMG_MODEL_DIR</td><td>" . (defined('DIR_IMG_MODEL_DIR') ? DIR_IMG_MODEL_DIR : '未定義') . "</td></tr>";
echo "</table>";

echo "<h2>2. データベースから機種情報取得</h2>";

try {
    $db = new NetDB();

    // 機種マスタから画像リストを取得
    $sql = "SELECT
                model_cd,
                model_name,
                generation,
                image_list,
                disp_flg,
                del_flg
            FROM mst_model
            WHERE del_flg = 0
            ORDER BY disp_order ASC
            LIMIT 10";

    $result = $db->getAll($sql, PDO::FETCH_ASSOC);

    if ($result) {
        echo "<p>取得件数: " . count($result) . " 件</p>";
        echo "<table border='1' cellpadding='5' style='width:100%;'>";
        echo "<tr>";
        echo "<th>機種コード</th>";
        echo "<th>機種名</th>";
        echo "<th>世代</th>";
        echo "<th>画像ファイル名</th>";
        echo "<th>完全パス</th>";
        echo "<th>表示</th>";
        echo "<th>削除</th>";
        echo "</tr>";

        $imgPath = defined('DIR_IMG_MODEL_DIR') ? DIR_IMG_MODEL_DIR : (defined('DIR_IMG_MODEL') ? DIR_IMG_MODEL : '/data/img/model/');

        foreach ($result as $row) {
            $fullPath = $imgPath . $row['image_list'];
            $localPath = __DIR__ . '/data/img/model/' . $row['image_list'];
            $fileExists = file_exists($localPath) ? '✅' : '❌';

            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['model_cd']) . "</td>";
            echo "<td>" . htmlspecialchars($row['model_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['generation']) . "</td>";
            echo "<td>" . htmlspecialchars($row['image_list']) . "</td>";
            echo "<td>" . htmlspecialchars($fullPath) . "</td>";
            echo "<td>" . htmlspecialchars($row['disp_flg']) . "</td>";
            echo "<td>" . htmlspecialchars($row['del_flg']) . "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='7'>";
            echo "ローカルパス: " . htmlspecialchars($localPath) . " " . $fileExists;
            if (file_exists($localPath)) {
                echo " | ファイルサイズ: " . filesize($localPath) . " bytes";
            }
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p style='color:red;'>データが見つかりませんでした</p>";
    }

    echo "<h2>3. 画像ディレクトリの内容</h2>";
    $imgDir = __DIR__ . '/data/img/model/';
    if (is_dir($imgDir)) {
        $files = scandir($imgDir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $imgDir . $file;
                $fileSize = is_file($filePath) ? filesize($filePath) : 0;
                echo "<li>" . htmlspecialchars($file) . " (" . $fileSize . " bytes)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>ディレクトリが存在しません: " . htmlspecialchars($imgDir) . "</p>";
    }

    echo "<h2>4. content/images/index/ の内容</h2>";
    $contentDir = __DIR__ . '/content/images/index/';
    if (is_dir($contentDir)) {
        $files = scandir($contentDir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $contentDir . $file;
                $fileSize = is_file($filePath) ? filesize($filePath) : 0;
                echo "<li>" . htmlspecialchars($file) . " (" . $fileSize . " bytes)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>ディレクトリが存在しません: " . htmlspecialchars($contentDir) . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p>デバッグ完了: " . date('Y-m-d H:i:s') . "</p>";
?>
