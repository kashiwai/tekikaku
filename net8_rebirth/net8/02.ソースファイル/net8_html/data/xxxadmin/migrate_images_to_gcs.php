<?php
/**
 * ローカル画像をGCSに一括アップロード + DB更新
 *
 * 実行方法: ブラウザで直接アクセス
 * https://your-domain.com/data/xxxadmin/migrate_images_to_gcs.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300); // 5分

// 設定ファイル読み込み
require_once('../../_etc/setting.php');

// 管理画面用関数読み込み（get_db_connection含む）
require_once('../../_etc/require_files_admin.php');

// Cloud Storage Helper読み込み
if (!file_exists(__DIR__ . '/../../_sys/CloudStorageHelper.php')) {
    die('❌ CloudStorageHelper.php が見つかりません');
}
require_once __DIR__ . '/../../_sys/CloudStorageHelper.php';

// Composer autoload
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../../vendor/autoload.php';
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCS画像マイグレーション</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 10px 0;
        }
        .error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 10px 0;
        }
        .log {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-danger:hover {
            background: #da190b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .status-ok {
            color: #4CAF50;
            font-weight: bold;
        }
        .status-error {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 GCS画像マイグレーション</h1>

        <div class="info">
            <strong>📌 このスクリプトの処理内容:</strong><br>
            1. data/img/model/ 内の全画像をGoogle Cloud Storageにアップロード<br>
            2. mst_modelテーブルのimage_listを完全GCS URLに更新<br>
            3. 処理結果をログ表示
        </div>

<?php

// 実行確認
if (!isset($_GET['execute'])) {
    ?>
        <div class="warning">
            <strong>⚠️ 実行前の確認事項:</strong><br>
            - GCS_ENABLED=true が環境変数に設定されていること<br>
            - GCS_KEY_JSON が正しく設定されていること<br>
            - バケット名: avamodb-net8-images<br>
            <br>
            <strong>準備ができたら実行ボタンをクリックしてください</strong>
        </div>

        <a href="?execute=1" class="btn" onclick="return confirm('実行してよろしいですか？')">実行する</a>
        <a href="/data/xxxadmin/" class="btn" style="background: #757575;">戻る</a>
    <?php
    exit;
}

// 実行開始
echo '<div class="log">';
echo "📋 マイグレーション開始: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // DB接続
    $pdo = get_db_connection();

    // GCS初期化確認
    if (!defined('GCS_ENABLED')) {
        throw new Exception('GCS_ENABLEDが定義されていません');
    }

    echo "✓ GCS_ENABLED: " . (GCS_ENABLED ? 'true' : 'false') . "\n";

    if (!GCS_ENABLED) {
        throw new Exception('GCS_ENABLEDがfalseです。Railway環境変数を確認してください。');
    }

    // CloudStorageHelper初期化
    $gcs = new CloudStorageHelper();

    if (!$gcs->isEnabled()) {
        throw new Exception('CloudStorageHelperの初期化に失敗しました。GCS_KEY_JSONを確認してください。');
    }

    echo "✓ CloudStorageHelper初期化成功\n\n";

    // ローカル画像ディレクトリ
    $localDir = realpath(__DIR__ . '/../img/model/');

    if (!$localDir || !is_dir($localDir)) {
        throw new Exception("画像ディレクトリが見つかりません: $localDir");
    }

    echo "📁 ローカル画像ディレクトリ: $localDir\n\n";

    // 画像ファイル取得
    $files = glob($localDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

    if (empty($files)) {
        echo "⚠️ アップロードする画像がありません\n";
        exit;
    }

    echo "📷 検出された画像: " . count($files) . "ファイル\n\n";
    echo str_repeat('-', 80) . "\n";

    $successCount = 0;
    $errorCount = 0;
    $updates = [];

    // 各ファイルを処理
    foreach ($files as $filePath) {
        $filename = basename($filePath);
        echo "\n処理中: $filename\n";

        // GCSにアップロード
        $gcsUrl = $gcs->upload($filePath, 'models', $filename);

        if ($gcsUrl) {
            echo "  ✓ GCSアップロード成功\n";
            echo "  URL: $gcsUrl\n";

            // ファイル名からmodel_cdを推測（拡張子を除く）
            $modelCd = pathinfo($filename, PATHINFO_FILENAME);

            // DBに該当する機種があるか確認
            $stmt = $pdo->prepare("SELECT model_no, model_cd, model_name FROM mst_model WHERE model_cd = :model_cd AND del_flg = 0");
            $stmt->execute(['model_cd' => $modelCd]);
            $model = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($model) {
                echo "  ✓ 機種発見: {$model['model_name']} (CD: {$model['model_cd']})\n";

                // DB更新
                $updateStmt = $pdo->prepare("
                    UPDATE mst_model
                    SET image_list = :image_list,
                        upd_no = 1,
                        upd_dt = NOW()
                    WHERE model_no = :model_no
                ");
                $updateStmt->execute([
                    'image_list' => $gcsUrl,
                    'model_no' => $model['model_no']
                ]);

                echo "  ✓ DB更新完了\n";

                $updates[] = [
                    'model_cd' => $model['model_cd'],
                    'model_name' => $model['model_name'],
                    'old_path' => $filename,
                    'new_url' => $gcsUrl
                ];

                $successCount++;
            } else {
                echo "  ⚠️ 該当する機種が見つかりません（model_cd: $modelCd）\n";
                echo "  ℹ️ GCSにはアップロード済み、手動でDBを更新してください\n";
            }
        } else {
            echo "  ✗ GCSアップロード失敗\n";
            $errorCount++;
        }

        echo str_repeat('-', 80) . "\n";
    }

    echo "\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 処理結果サマリー\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "成功: {$successCount}件\n";
    echo "エラー: {$errorCount}件\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    if (!empty($updates)) {
        echo "✅ 更新された機種:\n\n";
        foreach ($updates as $update) {
            echo "  • {$update['model_name']} (CD: {$update['model_cd']})\n";
            echo "    旧: {$update['old_path']}\n";
            echo "    新: {$update['new_url']}\n\n";
        }
    }

    echo "\n✅ マイグレーション完了: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "\n❌ エラー: " . $e->getMessage() . "\n";
    echo "\n📋 スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo '</div>';

?>

        <div style="margin-top: 20px;">
            <a href="/data/xxxadmin/image_upload.php" class="btn">画像アップロード画面へ</a>
            <a href="/data/" class="btn">トップページで確認</a>
            <a href="?execute=1" class="btn btn-danger" onclick="return confirm('再実行してよろしいですか？')">再実行</a>
        </div>
    </div>
</body>
</html>
