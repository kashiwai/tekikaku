<?php
/**
 * BLOB画像表示スクリプト
 *
 * Railway環境では画像ファイルが永続化されないため、
 * データベースのBLOBから画像を直接出力する
 *
 * パラメータ:
 *   - model_no: 機種番号
 *   - type: 画像タイプ（list/detail/reel）
 */

// 設定ファイル読み込み
require_once(__DIR__ . '/../_etc/setting.php');
require_once(__DIR__ . '/../_etc/setting_base.php');

// エラー表示を無効化（画像出力のため）
ini_set('display_errors', '0');
error_reporting(0);

// パラメータチェック
if (!isset($_GET['model_no']) || !isset($_GET['type'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$model_no = intval($_GET['model_no']);
$type = $_GET['type'];

// タイプチェック
$allowed_types = ['list', 'detail', 'reel'];
if (!in_array($type, $allowed_types)) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

// カラム名決定
$data_column = "image_{$type}_data";
$mime_column = "image_{$type}_mime";

try {
    // データベース接続
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, DB_OPTIONS);

    // 画像データ取得
    $stmt = $pdo->prepare("SELECT {$data_column}, {$mime_column} FROM mst_model WHERE model_no = :model_no AND del_flg = 0");
    $stmt->bindParam(':model_no', $model_no, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row[$data_column])) {
        // Content-Typeヘッダー設定
        $mime = $row[$mime_column] ?: 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($row[$data_column]));

        // キャッシュ設定（1日間）
        header('Cache-Control: public, max-age=86400');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

        // 画像データ出力
        echo $row[$data_column];
        exit;
    } else {
        // 画像が見つからない場合はデフォルト画像を表示
        // または404エラー
        header('HTTP/1.1 404 Not Found');

        // 1x1ピクセルの透明PNG画像を出力（代替画像）
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        exit;
    }

} catch (PDOException $e) {
    // エラーログに記録
    error_log('Image BLOB fetch error: ' . $e->getMessage());

    header('HTTP/1.1 500 Internal Server Error');

    // 1x1ピクセルの透明PNG画像を出力（代替画像）
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    exit;
}
