<?php
/**
 * ローカルテスト用: GCSマイグレーションテスト
 *
 * 実行方法: php test_gcs_migration_local.php
 */

// 環境変数設定（.env.localから読み込む代わりに直接設定）
putenv('GCS_ENABLED=true');
putenv('GCS_PROJECT_ID=avamodb');
putenv('GCS_BUCKET_NAME=avamodb-net8-images');

// GCS_KEY_JSONを設定（ファイルから読み込み）
$gcsKeyPath = getenv('HOME') . '/gcs-key.json';
if (file_exists($gcsKeyPath)) {
    $gcsKeyJson = file_get_contents($gcsKeyPath);
    // 改行を削除
    $gcsKeyJson = str_replace(["\n", "\r"], '', $gcsKeyJson);
    putenv("GCS_KEY_JSON=$gcsKeyJson");
    echo "✓ GCS_KEY_JSON loaded from ~/gcs-key.json\n";
} else {
    die("❌ ~/gcs-key.json が見つかりません\n");
}

// 設定ファイル読み込み
require_once(__DIR__ . '/_etc/setting.php');

// 管理画面用関数読み込み（get_db_connection含む）
require_once(__DIR__ . '/_etc/require_files_admin.php');

// Composer autoload
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("❌ vendor/autoload.php が見つかりません。composer install を実行してください\n");
}

// CloudStorageHelper
if (!file_exists(__DIR__ . '/_sys/CloudStorageHelper.php')) {
    die('❌ CloudStorageHelper.php が見つかりません');
}
require_once __DIR__ . '/_sys/CloudStorageHelper.php';

echo "\n";
echo "==============================================\n";
echo "  GCS マイグレーション ローカルテスト\n";
echo "==============================================\n\n";

echo "📋 環境変数確認:\n";
echo "  GCS_ENABLED: " . (getenv('GCS_ENABLED') === 'true' ? 'true' : 'false') . "\n";
echo "  GCS_PROJECT_ID: " . getenv('GCS_PROJECT_ID') . "\n";
echo "  GCS_BUCKET_NAME: " . getenv('GCS_BUCKET_NAME') . "\n";
echo "  GCS_KEY_JSON: " . (strlen(getenv('GCS_KEY_JSON')) > 0 ? '設定済み (' . strlen(getenv('GCS_KEY_JSON')) . ' bytes)' : '未設定') . "\n";
echo "\n";

echo "📋 定数確認:\n";
echo "  GCS_ENABLED (定数): " . (defined('GCS_ENABLED') && GCS_ENABLED ? 'true' : 'false') . "\n";
echo "  GCS_PROJECT_ID (定数): " . (defined('GCS_PROJECT_ID') ? GCS_PROJECT_ID : '未定義') . "\n";
echo "  GCS_BUCKET_NAME (定数): " . (defined('GCS_BUCKET_NAME') ? GCS_BUCKET_NAME : '未定義') . "\n";
echo "\n";

try {
    // CloudStorageHelper初期化テスト
    echo "🔧 CloudStorageHelper初期化テスト...\n";
    $gcs = new CloudStorageHelper();

    if ($gcs->isEnabled()) {
        echo "✓ CloudStorageHelper初期化成功\n\n";
    } else {
        throw new Exception('CloudStorageHelperが無効です');
    }

    // ローカル画像ディレクトリ確認
    $localDir = realpath(__DIR__ . '/data/img/model/');

    if (!$localDir || !is_dir($localDir)) {
        throw new Exception("画像ディレクトリが見つかりません: " . __DIR__ . '/data/img/model/');
    }

    echo "📁 ローカル画像ディレクトリ: $localDir\n";

    // 画像ファイル取得
    $files = glob($localDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

    if (empty($files)) {
        echo "⚠️ アップロードする画像がありません\n";
        exit(0);
    }

    echo "📷 検出された画像: " . count($files) . "ファイル\n\n";
    echo str_repeat('=', 70) . "\n\n";

    // テスト: 最初の1ファイルのみアップロード
    $testFile = $files[0];
    $filename = basename($testFile);

    echo "🧪 テストアップロード (1ファイルのみ):\n";
    echo "  ファイル: $filename\n";
    echo "  サイズ: " . filesize($testFile) . " bytes\n\n";

    echo "  GCSにアップロード中...\n";
    $gcsUrl = $gcs->upload($testFile, 'models', $filename);

    if ($gcsUrl) {
        echo "  ✓ アップロード成功\n";
        echo "  URL: $gcsUrl\n\n";

        // 存在確認
        echo "  存在確認中...\n";
        $exists = $gcs->exists($gcsUrl);
        echo "  " . ($exists ? "✓ 存在確認OK" : "✗ 存在確認NG") . "\n\n";

        // DB接続テスト
        echo "  DB接続テスト...\n";
        $pdo = get_db_connection();
        echo "  ✓ DB接続成功\n\n";

        // 該当機種検索
        $modelCd = pathinfo($filename, PATHINFO_FILENAME);
        echo "  機種検索 (model_cd: $modelCd)...\n";

        $stmt = $pdo->prepare("SELECT model_no, model_cd, model_name FROM mst_model WHERE model_cd = :model_cd AND del_flg = 0");
        $stmt->execute(['model_cd' => $modelCd]);
        $model = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($model) {
            echo "  ✓ 機種発見: {$model['model_name']} (CD: {$model['model_cd']})\n\n";

            echo "  ⚠️ 実際のDB更新はスキップします（テストモード）\n";
            echo "  実行予定のSQL:\n";
            echo "    UPDATE mst_model SET image_list = '$gcsUrl' WHERE model_no = {$model['model_no']}\n\n";
        } else {
            echo "  ⚠️ 該当する機種が見つかりません（model_cd: $modelCd）\n\n";
        }

    } else {
        echo "  ✗ アップロード失敗\n\n";
    }

    echo str_repeat('=', 70) . "\n";
    echo "\n✅ テスト完了\n\n";
    echo "📝 次のステップ:\n";
    echo "  1. 上記が成功していることを確認\n";
    echo "  2. ブラウザで migrate_images_to_gcs.php にアクセスして全画像を処理\n";
    echo "     URL: http://localhost:8000/data/xxxadmin/migrate_images_to_gcs.php\n";
    echo "  3. 全て成功したら本番環境にデプロイ\n\n";

} catch (Exception $e) {
    echo "\n❌ エラー: " . $e->getMessage() . "\n";
    echo "\n📋 スタックトレース:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
