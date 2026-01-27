<?php
/**
 * model_sort_settings.php
 *
 * 機種リスト並び順設定画面
 *
 * @package NET8
 * @author  System
 * @version 1.0
 * @since   2025/11/13
 */

// インクルード
require_once('../../_etc/require_files_admin.php');

// 設定ファイルのパス
define('SORT_CONFIG_FILE', '../../_etc/model_sort_config.json');

// メイン処理
main();

function main() {
    try {
        $template = new TemplateAdmin();

        // POSTリクエストの場合は設定保存
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            SaveSettings($template);
        } else {
            // 設定画面表示
            DispForm($template);
        }

    } catch (Exception $e) {
        echo '<h1>エラーが発生しました</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
}

/**
 * 設定画面表示
 */
function DispForm($template) {
    // 現在の設定を読み込み
    $currentSort = getSortSetting();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>機種リスト並び順設定 - NET8 管理画面</title>
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
            max-width: 800px;
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

        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #1e40af;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-option {
            display: flex;
            align-items: flex-start;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .radio-option:hover {
            border-color: #667eea;
            background: #f8fafc;
        }

        .radio-option input[type="radio"] {
            margin-right: 12px;
            margin-top: 4px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .radio-option.selected {
            border-color: #667eea;
            background: #f0f9ff;
        }

        .option-content {
            flex: 1;
        }

        .option-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .option-description {
            font-size: 13px;
            color: #64748b;
        }

        .option-example {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
            padding-left: 12px;
            border-left: 2px solid #e2e8f0;
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
            <h1>⚙️ 機種リスト並び順設定</h1>
            <p class="subtitle">機種選択画面での表示順を設定します</p>

            <div class="info-box">
                💡 <strong>ヒント:</strong> この設定は機種変更画面、台追加画面などの機種選択ドロップダウンに適用されます。
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label>並び順を選択してください</label>
                    <div class="radio-group">
                        <label class="radio-option <?= $currentSort === 'model_no' ? 'selected' : '' ?>">
                            <input type="radio" name="sort_order" value="model_no" <?= $currentSort === 'model_no' ? 'checked' : '' ?>>
                            <div class="option-content">
                                <div class="option-title">機種番号順</div>
                                <div class="option-description">機種番号の若い順に表示します</div>
                                <div class="option-example">例: 001, 002, 003...</div>
                            </div>
                        </label>

                        <label class="radio-option <?= $currentSort === 'model_name' ? 'selected' : '' ?>">
                            <input type="radio" name="sort_order" value="model_name" <?= $currentSort === 'model_name' ? 'checked' : '' ?>>
                            <div class="option-content">
                                <div class="option-title">機種名順</div>
                                <div class="option-description">機種名の五十音順に表示します</div>
                                <div class="option-example">例: あ→か→さ→た...</div>
                            </div>
                        </label>

                        <label class="radio-option <?= $currentSort === 'category_model_no' ? 'selected' : '' ?>">
                            <input type="radio" name="sort_order" value="category_model_no" <?= $currentSort === 'category_model_no' ? 'checked' : '' ?>>
                            <div class="option-content">
                                <div class="option-title">カテゴリー別 → 機種番号順（推奨）</div>
                                <div class="option-description">パチンコ/スロットを分けて、それぞれ機種番号順に表示します</div>
                                <div class="option-example">例: [パチンコ] 001, 003 / [スロット] 002, 004</div>
                            </div>
                        </label>

                        <label class="radio-option <?= $currentSort === 'category_model_name' ? 'selected' : '' ?>">
                            <input type="radio" name="sort_order" value="category_model_name" <?= $currentSort === 'category_model_name' ? 'checked' : '' ?>>
                            <div class="option-content">
                                <div class="option-title">カテゴリー別 → 機種名順</div>
                                <div class="option-description">パチンコ/スロットを分けて、それぞれ機種名の五十音順に表示します</div>
                                <div class="option-example">例: [パチンコ] あ, か, さ / [スロット] あ, か, さ</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="machine_control.php" class="btn btn-secondary">
                        ← 戻る
                    </a>
                    <button type="submit" class="btn-primary">
                        ✅ 設定を保存
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ラジオボタンのハイライト
        document.querySelectorAll('.radio-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.radio-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>
<?php
}

/**
 * 設定保存処理
 */
function SaveSettings($template) {
    $sortOrder = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'model_no';

    // バリデーション
    $allowedValues = ['model_no', 'model_name', 'category_model_no', 'category_model_name'];
    if (!in_array($sortOrder, $allowedValues)) {
        throw new Exception('不正な並び順が指定されました');
    }

    // 設定を保存
    $config = ['sort_order' => $sortOrder];
    $configDir = dirname(SORT_CONFIG_FILE);

    // ディレクトリが存在しない場合は作成
    if (!is_dir($configDir)) {
        mkdir($configDir, 0777, true);
    }

    file_put_contents(SORT_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));

    // 完了画面表示
    DispComplete($sortOrder);
}

/**
 * 完了画面表示
 */
function DispComplete($sortOrder) {
    $sortNames = [
        'model_no' => '機種番号順',
        'model_name' => '機種名順',
        'category_model_no' => 'カテゴリー別 → 機種番号順',
        'category_model_name' => 'カテゴリー別 → 機種名順'
    ];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>設定完了 - NET8 管理画面</title>
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
        <h1>設定が完了しました</h1>

        <div class="info-box">
            <div class="info-item">
                <strong>並び順:</strong> <?= htmlspecialchars($sortNames[$sortOrder]) ?>
            </div>
        </div>

        <button onclick="location.href='model_sort_settings.php'">
            ⚙️ 設定画面へ戻る
        </button>
    </div>
</body>
</html>
<?php
}

/**
 * 現在の並び順設定を取得
 */
function getSortSetting() {
    if (file_exists(SORT_CONFIG_FILE)) {
        $config = json_decode(file_get_contents(SORT_CONFIG_FILE), true);
        return $config['sort_order'] ?? 'model_no';
    }
    return 'model_no'; // デフォルト
}

/**
 * 並び順設定に基づいてORDER BY句を取得（他のファイルから呼び出し用）
 */
function getModelSortOrderBy() {
    $sortOrder = getSortSetting();

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
