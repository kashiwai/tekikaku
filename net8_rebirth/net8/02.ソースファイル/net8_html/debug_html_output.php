<?php
/**
 * HTML出力デバッグ - 画像パスがどう生成されているか確認
 * URL: https://mgg-webservice-production.up.railway.app/debug_html_output.php
 */

require_once(__DIR__ . '/_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

try {
    $template = new TemplateUser(false);
    $db = new NetDB();

    // 機種データを1件取得
    $sql = "SELECT
                mm.model_cd,
                mm.model_name,
                mu.unit_name,
                mu.unit_roman,
                mm.image_list
            FROM mst_model mm
            LEFT JOIN mst_unit mu ON mu.unit_no = mm.unit_no AND mu.del_flg = 0
            WHERE mm.del_flg = 0
              AND mm.image_list IS NOT NULL
              AND mm.image_list != ''
            ORDER BY mm.disp_order ASC
            LIMIT 1";

    $row = $db->getRow($sql, PDO::FETCH_ASSOC);

    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>HTML出力デバッグ</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 3px solid #0376C9; padding-bottom: 10px; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #0376C9; color: white; }
        .code { background: #eee; padding: 10px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; word-break: break-all; }
        .status { padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .status.ok { background: #4CAF50; color: white; }
        .status.error { background: #f44336; color: white; }
        img { max-width: 300px; border: 2px solid #ddd; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🔍 HTML出力デバッグ</h1>

<div class="section">
<h2>1️⃣ PHP定数の確認</h2>
<table>
    <tr>
        <th>定数名</th>
        <th>値</th>
        <th>ステータス</th>
    </tr>
    <tr>
        <td>DIR_IMG_MODEL</td>
        <td><code><?php echo defined('DIR_IMG_MODEL') ? htmlspecialchars(DIR_IMG_MODEL) : 'undefined'; ?></code></td>
        <td><?php echo defined('DIR_IMG_MODEL') ? '<span class="status ok">✓ 定義済み</span>' : '<span class="status error">✗ 未定義</span>'; ?></td>
    </tr>
    <tr>
        <td>DIR_IMG_MODEL_DIR</td>
        <td><code><?php echo defined('DIR_IMG_MODEL_DIR') ? htmlspecialchars(DIR_IMG_MODEL_DIR) : 'undefined'; ?></code></td>
        <td><?php echo defined('DIR_IMG_MODEL_DIR') ? '<span class="status ok">✓ 定義済み</span>' : '<span class="status error">✗ 未定義（DIR_IMG_MODELを使用）</span>'; ?></td>
    </tr>
</table>
</div>

<div class="section">
<h2>2️⃣ データベースから取得した機種データ</h2>
<?php if ($row): ?>
<table>
    <tr><th>項目</th><th>値</th></tr>
    <tr><td>機種コード</td><td><?php echo htmlspecialchars($row['model_cd']); ?></td></tr>
    <tr><td>機種名</td><td><?php echo htmlspecialchars($row['model_name']); ?></td></tr>
    <tr><td>世代（unit_name）</td><td><?php echo htmlspecialchars($row['unit_name']); ?></td></tr>
    <tr><td>世代（unit_roman）</td><td><?php echo htmlspecialchars($row['unit_roman']); ?></td></tr>
    <tr><td>画像ファイル名（image_list）</td><td><code><?php echo htmlspecialchars($row['image_list']); ?></code></td></tr>
</table>
<?php else: ?>
<p class="status error">✗ データが見つかりませんでした</p>
<?php endif; ?>
</div>

<div class="section">
<h2>3️⃣ TemplateUserで生成される変数</h2>
<?php
// TemplateUserのAssignMachineListメソッドと同じロジック
$imgPath = defined('DIR_IMG_MODEL_DIR') ? DIR_IMG_MODEL_DIR : (defined('DIR_IMG_MODEL') ? DIR_IMG_MODEL : '');
$generation = (FOLDER_LANG == DEFAULT_LANG) ? $row['unit_name'] : $row['unit_roman'];
$fullImagePath = $imgPath . $row['image_list'];
?>
<table>
    <tr><th>テンプレート変数名</th><th>生成される値</th></tr>
    <tr>
        <td>{%DIR_IMG_MODEL_DIR%}</td>
        <td><code><?php echo htmlspecialchars($imgPath); ?></code></td>
    </tr>
    <tr>
        <td>{%IMAGE_LIST%}</td>
        <td><code><?php echo htmlspecialchars($row['image_list']); ?></code></td>
    </tr>
    <tr>
        <td>{%GENERATION%}</td>
        <td><code><?php echo htmlspecialchars($generation); ?></code></td>
    </tr>
    <tr>
        <td>{%MODEL_NAME%}</td>
        <td><code><?php echo htmlspecialchars($row['model_name']); ?></code></td>
    </tr>
</table>
</div>

<div class="section">
<h2>4️⃣ 生成されるHTML（予想）</h2>
<p><strong>HTMLテンプレート：</strong></p>
<div class="code">&lt;img src="{%DIR_IMG_MODEL_DIR%}{%IMAGE_LIST%}" alt="{%MODEL_NAME%}"&gt;</div>

<p><strong>展開後のHTML：</strong></p>
<div class="code">&lt;img src="<?php echo htmlspecialchars($fullImagePath); ?>" alt="<?php echo htmlspecialchars($row['model_name']); ?>"&gt;</div>

<p><strong>完全なURL：</strong></p>
<div class="code">https://mgg-webservice-production.up.railway.app<?php echo htmlspecialchars($fullImagePath); ?></div>
</div>

<div class="section">
<h2>5️⃣ 実際の画像表示テスト</h2>
<p><strong>方法1: 完全パス</strong></p>
<img src="<?php echo htmlspecialchars($fullImagePath); ?>" alt="<?php echo htmlspecialchars($row['model_name']); ?>">
<p>パス: <code><?php echo htmlspecialchars($fullImagePath); ?></code></p>

<p><strong>方法2: 直接指定</strong></p>
<img src="/data/img/model/<?php echo htmlspecialchars($row['image_list']); ?>" alt="Test">
<p>パス: <code>/data/img/model/<?php echo htmlspecialchars($row['image_list']); ?></code></p>

<?php
$testImages = ['hokuto4go.jpg', 'milliongod_gaisen.jpg', 'zenigata.jpg'];
foreach ($testImages as $testImg) {
    $testPath = '/data/img/model/' . $testImg;
    $localPath = __DIR__ . $testPath;
    echo '<p><strong>テスト: ' . htmlspecialchars($testImg) . '</strong></p>';
    echo '<img src="' . htmlspecialchars($testPath) . '" alt="' . htmlspecialchars($testImg) . '">';
    echo '<p>パス: <code>' . htmlspecialchars($testPath) . '</code> | ';
    echo 'ファイル存在: ' . (file_exists($localPath) ? '<span class="status ok">✓ YES</span>' : '<span class="status error">✗ NO</span>') . '</p>';
}
?>
</div>

<div class="section">
<h2>6️⃣ 考えられる問題</h2>
<ol>
    <li><strong>DIR_IMG_MODEL_DIR が空</strong>: <?php echo empty($imgPath) ? '<span class="status error">✗ YES - これが原因の可能性</span>' : '<span class="status ok">✓ NO</span>'; ?></li>
    <li><strong>image_list が空</strong>: <?php echo empty($row['image_list']) ? '<span class="status error">✗ YES</span>' : '<span class="status ok">✓ NO</span>'; ?></li>
    <li><strong>ファイルが存在しない</strong>: <?php echo !file_exists(__DIR__ . $fullImagePath) ? '<span class="status error">✗ YES</span>' : '<span class="status ok">✓ NO</span>'; ?></li>
    <li><strong>Apacheのrewrite設定問題</strong>: テスト必要</li>
</ol>
</div>

</body>
</html>
<?php
} catch (Exception $e) {
    echo '<div style="background:white;padding:20px;margin:20px;border:2px solid red;">';
    echo '<h2 style="color:red;">エラーが発生しました</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}
?>
