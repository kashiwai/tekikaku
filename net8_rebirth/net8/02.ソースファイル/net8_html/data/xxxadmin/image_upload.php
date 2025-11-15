<?php
/**
 * 機種画像アップロード管理画面
 *
 * 管理画面から機種画像をアップロードし、mst_model.image_listに設定
 * Google Cloud Storage統合対応
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

// セッション開始
session_start();

// 設定ファイル読み込み
require_once('../../_etc/setting.php');

// Composer autoload
if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../../vendor/autoload.php';
}

// Cloud Storage Helper読み込み
if (file_exists(__DIR__ . '/../../_sys/CloudStorageHelper.php')) {
    require_once __DIR__ . '/../../_sys/CloudStorageHelper.php';
}

// DB接続
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

$message = '';
$messageType = '';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 画像アップロード処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['model_image'])) {
        $model_cd = $_POST['model_cd'] ?? '';

        if (empty($model_cd)) {
            throw new Exception('機種コードを選択してください');
        }

        $file = $_FILES['model_image'];

        // ファイルチェック
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ファイルアップロードエラー: ' . $file['error']);
        }

        // 画像ファイルかチェック
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('許可されていないファイル形式です（JPEG, PNG, GIF, WebPのみ）');
        }

        // ファイルサイズチェック（5MB以下）
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('ファイルサイズが大きすぎます（5MB以下にしてください）');
        }

        // ファイル名生成（拡張子を保持）
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = strtolower($model_cd) . '.' . $ext;
        $uploadDir = realpath(__DIR__ . '/../img/model/');

        if (!$uploadDir) {
            mkdir(__DIR__ . '/../img/model/', 0755, true);
            $uploadDir = realpath(__DIR__ . '/../img/model/');
        }

        $uploadPath = $uploadDir . '/' . $filename;

        // ファイル移動（ローカル保存）
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('ファイルの保存に失敗しました');
        }

        // 画像パス（ファイル名のみ、テンプレート側で /data/img/model/ が追加される）
        $imagePath = $filename;

        // Cloud Storage統合が有効な場合はGCSにもアップロード
        if (defined('GCS_ENABLED') && GCS_ENABLED && class_exists('CloudStorageHelper')) {
            try {
                $gcs = new CloudStorageHelper();
                if ($gcs->isEnabled()) {
                    $gcsUrl = $gcs->upload($uploadPath, 'models', $filename);
                    if ($gcsUrl) {
                        // GCS URLを優先して使用（フルURLなのでそのまま保存）
                        $imagePath = $gcsUrl;
                        $message = "画像アップロード成功（Cloud Storage）: {$filename}";
                    } else {
                        $message = "画像アップロード成功（ローカル）: {$filename}<br>※ Cloud Storageへのアップロードは失敗しました";
                    }
                } else {
                    $message = "画像アップロード成功（ローカル）: {$filename}<br>※ Cloud Storageは無効です";
                }
            } catch (Exception $e) {
                error_log('GCSアップロードエラー: ' . $e->getMessage());
                $message = "画像アップロード成功（ローカル）: {$filename}<br>※ Cloud Storageエラー: " . $e->getMessage();
            }
        } else {
            $message = "画像アップロード成功（ローカル）: {$filename}";
        }

        // DBに画像パスを登録
        $stmt = $pdo->prepare("
            UPDATE mst_model SET
                image_list = :image_list,
                upd_no = 1,
                upd_dt = NOW()
            WHERE model_cd = :model_cd
        ");
        $stmt->execute([
            'image_list' => $imagePath,
            'model_cd' => $model_cd
        ]);

        $messageType = 'success';
    }

    // 機種一覧取得
    $models = $pdo->query("
        SELECT model_no, model_cd, model_name, image_list
        FROM mst_model
        WHERE del_flg = 0
        ORDER BY model_no
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = 'エラー: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>機種画像アップロード - 管理画面</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,144C960,149,1056,139,1152,128C1248,117,1344,107,1392,101.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            z-index: 0;
            pointer-events: none;
        }

        .header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 30px 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 300;
        }

        .nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav a {
            color: #667eea;
            text-decoration: none;
            margin-right: 25px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .nav a:hover {
            color: #764ba2;
        }

        .nav a:hover::after {
            width: 100%;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px 40px;
            position: relative;
            z-index: 1;
        }

        .alert {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
        }

        .alert.error {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            border: none;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
        }

        .card-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
        }

        .card-header h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-body { padding: 30px; }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group select:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .preview {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #667eea;
        }

        .preview img {
            max-width: 100%;
            max-height: 500px;
            width: auto;
            height: auto;
            border-radius: 12px;
            display: block;
            margin: 0 auto;
            object-fit: contain;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .model-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .model-item {
            background: white;
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .model-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .model-item h3 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            padding: 15px 20px 10px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .model-item .model-cd {
            font-size: 13px;
            color: #667eea;
            padding: 0 20px 15px;
            font-weight: 600;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .model-item .image-preview {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .model-item .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .model-item:hover .image-preview img {
            transform: scale(1.05);
        }

        .model-item .no-image {
            color: #999;
            font-size: 14px;
            font-weight: 500;
        }

        .model-item .image-path {
            font-size: 12px;
            color: #11998e;
            padding: 12px 20px;
            word-break: break-all;
            font-weight: 500;
            background: rgba(17, 153, 142, 0.05);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>機種画像アップロード</h1>
        <p>機種のリスト用画像（トップページ表示用）をアップロード</p>
    </div>

    <div class="nav">
        <a href="/xxxadmin/">管理画面TOP</a>
        <a href="/xxxadmin/auto_setup.php">ワンクリックセットアップ</a>
        <a href="/xxxadmin/model.php">機種管理</a>
        <a href="/">トップページ</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
        <div class="alert <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>画像アップロード</h2>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="form-group">
                        <label for="model_cd">機種を選択:</label>
                        <select name="model_cd" id="model_cd" required>
                            <option value="">選択してください</option>
                            <?php foreach ($models as $model): ?>
                            <option value="<?php echo htmlspecialchars($model['model_cd']); ?>">
                                <?php echo htmlspecialchars($model['model_name']); ?> (<?php echo htmlspecialchars($model['model_cd']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="model_image">画像ファイルを選択:</label>
                        <input type="file" name="model_image" id="model_image" accept="image/*" required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            対応形式: JPEG, PNG, GIF, WebP / 最大サイズ: 5MB
                        </small>
                    </div>

                    <div class="preview" id="preview" style="display: none;">
                        <label>プレビュー:</label>
                        <div>
                            <img id="previewImage" src="" alt="プレビュー">
                        </div>
                    </div>

                    <button type="submit" class="btn" id="submitBtn">アップロード</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>登録済み機種一覧</h2>
            </div>
            <div class="card-body">
                <div class="model-list">
                    <?php foreach ($models as $model): ?>
                    <div class="model-item">
                        <h3><?php echo htmlspecialchars($model['model_name']); ?></h3>
                        <div class="model-cd">CD: <?php echo htmlspecialchars($model['model_cd']); ?></div>
                        <div class="image-preview">
                            <?php if ($model['image_list']): ?>
                                <img src="/data/<?php echo htmlspecialchars($model['image_list']); ?>" alt="<?php echo htmlspecialchars($model['model_name']); ?>">
                            <?php else: ?>
                                <span class="no-image">画像未設定</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($model['image_list']): ?>
                        <div class="image-path">
                            ✓ <?php echo htmlspecialchars($model['image_list']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 画像プレビュー
        document.getElementById('model_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('preview').style.display = 'none';
            }
        });

        // フォーム送信時の処理
        document.getElementById('uploadForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = 'アップロード中...';
        });
    </script>
</body>
</html>
