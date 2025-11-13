<?php
/**
 * machine_model_edit.php
 *
 * マシンの機種変更画面
 *
 * @package NET8
 * @author  System
 * @version 1.0
 * @since   2025/11/13
 */

// インクルード
require_once('../../_etc/require_files_admin.php');

// メイン処理
main();

function main() {
    try {
        $template = new TemplateAdmin();

        // POSTリクエストの場合は更新処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            UpdateModel($template);
        } else {
            // 編集フォーム表示
            DispForm($template);
        }

    } catch (Exception $e) {
        echo '<h1>エラーが発生しました</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
}

/**
 * 編集フォーム表示
 */
function DispForm($template) {
    // machine_no取得
    $machine_no = isset($_GET['machine_no']) ? intval($_GET['machine_no']) : 0;

    if ($machine_no == 0) {
        header("Location: machine_control.php");
        exit;
    }

    // マシン情報取得
    $sql = "SELECT dm.*, mm.model_name
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            WHERE dm.machine_no = :machine_no";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute(['machine_no' => $machine_no]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        echo "マシンが見つかりません";
        exit;
    }

    // 機種リスト取得（設定に基づいた並び順）
    $orderBy = getModelSortOrderBy();
    $sql = "SELECT model_no, model_name, category FROM mst_model WHERE del_flg = 0 {$orderBy}";
    $models = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    /**
     * 並び順設定を取得
     */
    function getModelSortOrderBy() {
        $configFile = '../../_etc/model_sort_config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            $sortOrder = $config['sort_order'] ?? 'model_no';
        } else {
            $sortOrder = 'model_no';
        }

        switch ($sortOrder) {
            case 'model_name':
                return 'ORDER BY model_name';
            case 'category_model_no':
                return 'ORDER BY category, model_no';
            case 'category_model_name':
                return 'ORDER BY category, model_name';
            case 'model_no':
            default:
                return 'ORDER BY model_no';
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>機種変更 - NET8 管理画面</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 32px;
        }

        h1 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .current-info {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        .current-info h3 {
            font-size: 16px;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            font-size: 14px;
            color: #0f172a;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }

        select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: border-color 0.3s;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .model-option {
            padding: 10px;
        }

        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #92400e;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        button, .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🎮 機種変更</h1>
            <p class="subtitle">マシンNo.<?= $machine['machine_no'] ?> の機種を変更します</p>

            <div class="current-info">
                <h3>📋 現在の設定</h3>
                <div class="info-row">
                    <span class="info-label">台番号</span>
                    <span class="info-value"><?= $machine['machine_no'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">機械コード</span>
                    <span class="info-value"><?= htmlspecialchars($machine['machine_cd']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">現在の機種</span>
                    <span class="info-value" style="color: #ef4444;">
                        <?= htmlspecialchars($machine['model_name'] ?: '未設定') ?>
                    </span>
                </div>
            </div>

            <div class="warning-box">
                ⚠️ <strong>注意:</strong> 機種を変更すると、台の設定やデータ収集に影響する場合があります。
            </div>

            <form method="POST" action="" onsubmit="return confirm('機種を変更してよろしいですか？');">
                <input type="hidden" name="machine_no" value="<?= $machine['machine_no'] ?>">

                <div class="form-group">
                    <label for="model_no">🎯 新しい機種を選択</label>
                    <select name="model_no" id="model_no" required>
                        <option value="">-- 機種を選択してください --</option>
                        <?php foreach ($models as $model): ?>
                            <option value="<?= $model['model_no'] ?>"
                                    <?= $model['model_no'] == $machine['model_no'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($model['model_name']) ?>
                                (<?= $model['category'] == 1 ? 'パチンコ' : ($model['category'] == 2 ? 'スロット' : '不明') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="btn-group">
                    <a href="machine_control.php" class="btn btn-secondary">
                        ← 戻る
                    </a>
                    <button type="submit" class="btn-primary">
                        ✅ 変更を保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
}

/**
 * 機種更新処理
 */
function UpdateModel($template) {
    // データ取得
    $machine_no = isset($_POST['machine_no']) ? intval($_POST['machine_no']) : 0;
    $model_no = isset($_POST['model_no']) ? intval($_POST['model_no']) : 0;

    // バリデーション
    if ($machine_no <= 0) {
        throw new Exception('マシン番号が不正です');
    }
    if ($model_no <= 0) {
        throw new Exception('機種を選択してください');
    }

    // 機種存在確認
    $sql = "SELECT model_no, model_name FROM mst_model WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute(['model_no' => $model_no]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        throw new Exception('指定された機種が見つかりません');
    }

    // マシン存在確認
    $sql = "SELECT machine_no FROM dat_machine WHERE machine_no = :machine_no";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute(['machine_no' => $machine_no]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        throw new Exception('マシンが見つかりません');
    }

    // 更新処理
    $sql = "UPDATE dat_machine SET
                model_no = :model_no,
                upd_no = 1,
                upd_dt = NOW()
            WHERE machine_no = :machine_no";

    $stmt = $template->DB->prepare($sql);
    $stmt->execute([
        'model_no' => $model_no,
        'machine_no' => $machine_no
    ]);

    // 完了画面表示
    DispComplete($template, $machine_no, $model['model_name']);
}

/**
 * 完了画面表示
 */
function DispComplete($template, $machine_no, $model_name) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>変更完了 - NET8 管理画面</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            text-align: center;
            max-width: 500px;
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 24px;
        }
        h1 {
            font-size: 28px;
            color: #16a34a;
            margin-bottom: 16px;
        }
        .info-box {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        .info-item {
            font-size: 16px;
            color: #166534;
            margin: 8px 0;
        }
        button {
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 24px;
            transition: transform 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="success-icon">✅</div>
        <h1>機種変更が完了しました</h1>

        <div class="info-box">
            <div class="info-item">
                <strong>マシン番号:</strong> <?= $machine_no ?>
            </div>
            <div class="info-item">
                <strong>新しい機種:</strong> <?= htmlspecialchars($model_name) ?>
            </div>
        </div>

        <button onclick="location.href='machine_control.php'">
            台管理画面へ →
        </button>
    </div>
</body>
</html>
<?php
}
?>
