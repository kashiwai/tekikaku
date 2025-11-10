<?php
/**
 * 画像表示問題デバッグページ
 * URL: https://mgg-webservice-production.up.railway.app/debug_images.php
 */

require_once(__DIR__ . '/_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>画像表示デバッグ</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 3px solid #0376C9; padding-bottom: 10px; }
        h2 { color: #0376C9; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #0376C9; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .status { padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .status.ok { background: #4CAF50; color: white; }
        .status.error { background: #f44336; color: white; }
        .status.warning { background: #ff9800; color: white; }
        img { max-width: 200px; height: auto; border: 2px solid #ddd; }
        .path { font-family: monospace; background: #eee; padding: 2px 6px; border-radius: 3px; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<h1>🖼️ 画像表示デバッグページ</h1>

<?php
$db = new NetDB();

// ================================================================
// セクション1: 定数確認
// ================================================================
?>
<div class="section">
<h2>1️⃣ 画像パス定数の設定</h2>
<table>
    <tr>
        <th>定数名</th>
        <th>値</th>
        <th>ステータス</th>
    </tr>
    <tr>
        <td>DIR_IMG_MODEL</td>
        <td class="path"><?php echo defined('DIR_IMG_MODEL') ? DIR_IMG_MODEL : '未定義'; ?></td>
        <td><?php echo defined('DIR_IMG_MODEL') ? '<span class="status ok">✓ 定義済み</span>' : '<span class="status error">✗ 未定義</span>'; ?></td>
    </tr>
    <tr>
        <td>DIR_IMG_MODEL_DIR</td>
        <td class="path"><?php echo defined('DIR_IMG_MODEL_DIR') ? DIR_IMG_MODEL_DIR : '未定義'; ?></td>
        <td><?php echo defined('DIR_IMG_MODEL_DIR') ? '<span class="status ok">✓ 定義済み</span>' : '<span class="status warning">⚠ 未定義（DIR_IMG_MODELを使用）</span>'; ?></td>
    </tr>
</table>
</div>

<?php
// ================================================================
// セクション2: 画像ファイル存在確認
// ================================================================
?>
<div class="section">
<h2>2️⃣ サーバー上の画像ファイル</h2>
<?php
$imageDir = __DIR__ . '/data/img/model/';
if (is_dir($imageDir)) {
    $files = scandir($imageDir);
    echo '<p>ディレクトリ: <span class="path">' . htmlspecialchars($imageDir) . '</span></p>';
    echo '<table>';
    echo '<tr><th>ファイル名</th><th>サイズ</th><th>プレビュー</th></tr>';
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && $file != '.gitkeep') {
            $filePath = $imageDir . $file;
            $fileSize = is_file($filePath) ? filesize($filePath) : 0;
            $webPath = '/data/img/model/' . $file;
            echo '<tr>';
            echo '<td><span class="path">' . htmlspecialchars($file) . '</span></td>';
            echo '<td>' . number_format($fileSize) . ' bytes</td>';
            echo '<td><img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($file) . '"></td>';
            echo '</tr>';
        }
    }
    echo '</table>';
} else {
    echo '<p class="status error">✗ ディレクトリが存在しません: ' . htmlspecialchars($imageDir) . '</p>';
}
?>
</div>

<?php
// ================================================================
// セクション3: データベースの画像設定
// ================================================================
?>
<div class="section">
<h2>3️⃣ データベースの機種画像設定</h2>
<?php
try {
    $sql = "SELECT
                mm.model_cd,
                mm.model_name,
                mu.unit_name,
                mm.image_list,
                mm.disp_flg,
                mm.disp_order
            FROM mst_model mm
            LEFT JOIN mst_unit mu ON mu.unit_no = mm.unit_no AND mu.del_flg = 0
            WHERE mm.del_flg = 0
            ORDER BY mm.disp_order ASC
            LIMIT 20";

    $result = $db->getAll($sql, PDO::FETCH_ASSOC);

    if ($result) {
        $imgPath = defined('DIR_IMG_MODEL_DIR') ? DIR_IMG_MODEL_DIR : (defined('DIR_IMG_MODEL') ? DIR_IMG_MODEL : '/data/img/model/');

        echo '<table>';
        echo '<tr><th>機種コード</th><th>機種名</th><th>世代</th><th>画像ファイル名</th><th>完全パス</th><th>表示</th><th>ステータス</th><th>プレビュー</th></tr>';

        foreach ($result as $row) {
            $fullPath = $imgPath . $row['image_list'];
            $localPath = __DIR__ . '/data/img/model/' . $row['image_list'];
            $fileExists = file_exists($localPath);
            $isEmpty = empty($row['image_list']);

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['model_cd']) . '</td>';
            echo '<td>' . htmlspecialchars($row['model_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['unit_name']) . '</td>';
            echo '<td><span class="path">' . htmlspecialchars($row['image_list']) . '</span></td>';
            echo '<td><span class="path">' . htmlspecialchars($fullPath) . '</span></td>';
            echo '<td>' . ($row['disp_flg'] == 1 ? '表示' : '非表示') . '</td>';

            if ($isEmpty) {
                echo '<td><span class="status error">✗ 未設定</span></td>';
                echo '<td>-</td>';
            } elseif ($fileExists) {
                echo '<td><span class="status ok">✓ OK</span></td>';
                echo '<td><img src="' . htmlspecialchars($fullPath) . '" alt="' . htmlspecialchars($row['model_name']) . '"></td>';
            } else {
                echo '<td><span class="status error">✗ ファイル無</span></td>';
                echo '<td>-</td>';
            }

            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="status warning">⚠ データが見つかりませんでした</p>';
    }
} catch (Exception $e) {
    echo '<p class="status error">✗ エラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
</div>

<?php
// ================================================================
// セクション4: トップページ画像
// ================================================================
?>
<div class="section">
<h2>4️⃣ トップページの画像</h2>
<?php
$topImageDir = __DIR__ . '/content/images/index/';
if (is_dir($topImageDir)) {
    $files = scandir($topImageDir);
    echo '<table>';
    echo '<tr><th>ファイル名</th><th>サイズ</th><th>プレビュー</th></tr>';
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $topImageDir . $file;
            $fileSize = is_file($filePath) ? filesize($filePath) : 0;
            $webPath = '/content/images/index/' . $file;
            echo '<tr>';
            echo '<td><span class="path">' . htmlspecialchars($file) . '</span></td>';
            echo '<td>' . number_format($fileSize) . ' bytes</td>';
            echo '<td><img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($file) . '"></td>';
            echo '</tr>';
        }
    }
    echo '</table>';
}
?>
</div>

<?php
// ================================================================
// セクション5: 推奨される対応
// ================================================================
?>
<div class="section">
<h2>5️⃣ 推奨される対応</h2>
<ol>
    <li><strong>画像ファイルが未設定の機種</strong>: データベースの <code>mst_model.image_list</code> に正しいファイル名を登録してください</li>
    <li><strong>ファイルが存在しない</strong>: 画像ファイルを <code>/data/img/model/</code> にアップロードしてください</li>
    <li><strong>すべての機種に一時的に同じ画像を設定</strong>: テスト用に以下のSQLを実行できます：
        <pre style="background:#eee;padding:10px;border-radius:4px;">
UPDATE mst_model
SET image_list = 'hokuto4go.jpg'
WHERE del_flg = 0 AND (image_list IS NULL OR image_list = '');</pre>
    </li>
</ol>
</div>

<p style="text-align:center; margin-top:40px; color:#999;">
    デバッグ完了: <?php echo date('Y-m-d H:i:s'); ?>
</p>

</body>
</html>
