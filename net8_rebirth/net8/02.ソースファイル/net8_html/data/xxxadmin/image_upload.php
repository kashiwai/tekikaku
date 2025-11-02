<?php
/**
 * 機種画像アップロード管理画面
 *
 * 管理画面から機種画像をアップロードし、mst_model.image_listに設定
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

// セッション開始
session_start();

// DB接続
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

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

        // ファイル移動
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // DBに画像パスを登録
            $imagePath = 'img/model/' . $filename;
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

            $message = "画像アップロード成功: {$filename}";
            $messageType = 'success';
        } else {
            throw new Exception('ファイルの保存に失敗しました');
        }
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.9; }
        .nav { background: white; padding: 15px 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .nav a { color: #667eea; text-decoration: none; margin-right: 20px; font-weight: 500; }
        .nav a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 20px; border-bottom: 1px solid #e0e0e0; }
        .card-header h2 { font-size: 20px; color: #333; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-group select,
        .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group select:focus,
        .form-group input[type="file"]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 14px; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .preview { margin-top: 15px; }
        .preview img { max-width: 300px; max-height: 300px; border: 2px solid #ddd; border-radius: 8px; }
        .model-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .model-item { border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #f8f9fa; }
        .model-item h3 { font-size: 16px; margin-bottom: 10px; color: #333; }
        .model-item .model-cd { font-size: 12px; color: #666; margin-bottom: 10px; }
        .model-item .image-preview { width: 100%; height: 150px; background: #fff; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 10px; }
        .model-item .image-preview img { max-width: 100%; max-height: 100%; object-fit: cover; }
        .model-item .no-image { color: #999; font-size: 12px; }
        .model-item .image-path { font-size: 11px; color: #28a745; word-break: break-all; }
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
