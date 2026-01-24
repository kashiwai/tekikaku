<?php
/**
 * machine_add.php
 *
 * マシン（台）一括追加画面
 *
 * @package NET8
 * @author  System
 * @version 1.0
 * @since   2025/11/13
 */

// インクルード
require_once('../../_etc/require_files_admin.php');

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

// メイン処理
main();

function main() {
    try {
        $template = new TemplateAdmin();

        // POSTリクエストの場合は登録処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AddMachines($template);
        } else {
            // 登録フォーム表示
            DispForm($template);
        }

    } catch (Exception $e) {
        echo '<h1>エラーが発生しました</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
}

/**
 * 登録フォーム表示
 */
function DispForm($template) {
    // 現在の最大machine_no取得
    $sql = "SELECT MAX(machine_no) as max_no FROM dat_machine";
    $result = $template->DB->query($sql);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $current_max = $row['max_no'] ?? 0;
    $next_no = $current_max + 1;

    // 機種リスト取得（設定に基づいた並び順）
    $orderBy = getModelSortOrderBy();
    $sql = "SELECT model_no, model_name FROM mst_model WHERE del_flg = 0 {$orderBy}";
    $models = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // オーナーリスト取得
    $sql = "SELECT owner_no, owner_nickname FROM mst_owner WHERE del_flg = 0 ORDER BY owner_no";
    $owners = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>台一括追加 - NET8 管理画面</title>
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
            margin-bottom: 20px;
        }

        h1 {
            font-size: 32px;
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
            background: #f1f5f9;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .info-box strong {
            color: #1e40af;
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

        input[type="number"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .input-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        button {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
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

        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🎰 台一括追加</h1>
            <p class="subtitle">新しい台を一括で登録します</p>

            <div class="info-box">
                <strong>📊 現在の登録状況</strong><br>
                最大台番号: <strong><?= $current_max ?></strong>台<br>
                次の台番号: <strong><?= $next_no ?></strong>番から
            </div>

            <div class="warning">
                ⚠️ <strong>注意:</strong> 登録後は削除できません。台番号は連番で自動採番されます。
            </div>

            <form method="POST" action="" onsubmit="return confirm('台を追加してよろしいですか？');">
                <div class="form-group">
                    <label for="count">🔢 追加する台数</label>
                    <input type="number" name="count" id="count" min="1" max="100" value="1" required>
                    <div class="input-hint">
                        例: 27 と入力すると、<?= $next_no ?>番～<?= $next_no + 26 ?>番まで27台追加されます
                    </div>
                </div>

                <div class="form-group">
                    <label for="model_no">🎮 機種</label>
                    <select name="model_no" id="model_no" required>
                        <option value="">-- 機種を選択 --</option>
                        <?php foreach ($models as $model): ?>
                            <option value="<?= $model['model_no'] ?>">
                                <?= htmlspecialchars($model['model_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="signaling_id">📡 Signaling ID</label>
                    <input type="number" name="signaling_id" id="signaling_id" value="1" min="1" required>
                    <div class="input-hint">
                        使用するSignaling Serverの番号（通常は1）
                    </div>
                </div>

                <div class="form-group">
                    <label for="owner_no">👤 オーナー</label>
                    <select name="owner_no" id="owner_no" required>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?= $owner['owner_no'] ?>">
                                <?= htmlspecialchars($owner['owner_nickname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn-secondary" onclick="location.href='machine_control.php'">
                        ← 戻る
                    </button>
                    <button type="submit" class="btn-primary">
                        ✅ 追加実行
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
 * 台追加処理
 */
function AddMachines($template) {
    // データ取得
    $count = isset($_POST['count']) ? intval($_POST['count']) : 0;
    $model_no = isset($_POST['model_no']) ? intval($_POST['model_no']) : 0;
    $signaling_id = isset($_POST['signaling_id']) ? intval($_POST['signaling_id']) : 1;
    $owner_no = isset($_POST['owner_no']) ? intval($_POST['owner_no']) : 1;

    // バリデーション
    if ($count <= 0 || $count > 100) {
        throw new Exception('台数は1～100の範囲で指定してください');
    }
    if ($model_no <= 0) {
        throw new Exception('機種を選択してください');
    }

    // 現在の最大machine_no取得
    $sql = "SELECT MAX(machine_no) as max_no FROM dat_machine";
    $result = $template->DB->query($sql);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $start_no = ($row['max_no'] ?? 0) + 1;

    // トランザクション開始
    $template->DB->autoCommit(false);

    try {
        $added_machines = [];

        for ($i = 0; $i < $count; $i++) {
            $machine_no = $start_no + $i;
            $machine_cd = 'M' . str_pad($machine_no, 3, '0', STR_PAD_LEFT);

            // トークン生成
            $token = 'net8_m' . str_pad($machine_no, 3, '0', STR_PAD_LEFT) . '_' . strtolower(md5('machine_' . $machine_no . time()));

            // dat_machineに挿入
            $sql = "INSERT INTO dat_machine (
                machine_no, machine_cd, model_no, signaling_id, machine_status,
                del_flg, release_date, end_date, owner_no, corner_no,
                camera_no, token, add_no, add_dt
            ) VALUES (
                :machine_no, :machine_cd, :model_no, :signaling_id, 0,
                0, CURDATE(), '2099-12-31', :owner_no, 1,
                0, :token, 1, NOW()
            )";

            $stmt = $template->DB->prepare($sql);
            $stmt->execute([
                'machine_no' => $machine_no,
                'machine_cd' => $machine_cd,
                'model_no' => $model_no,
                'signaling_id' => $signaling_id,
                'owner_no' => $owner_no,
                'token' => $token
            ]);

            // lnk_machineにも追加
            $sql = "INSERT INTO lnk_machine (machine_no, member_no, assign_flg, onetime_id, start_dt, exit_flg)
                    VALUES (:machine_no, 0, 0, '', NOW(), 0)";
            $stmt = $template->DB->prepare($sql);
            $stmt->execute(['machine_no' => $machine_no]);

            $added_machines[] = $machine_no;
        }

        // コミット
        $template->DB->autoCommit(true);

        // 完了画面表示
        DispComplete($template, $added_machines, $count);

    } catch (Exception $e) {
        // ロールバック
        $template->DB->rollBack();
        throw new Exception('台追加に失敗しました: ' . $e->getMessage());
    }
}

/**
 * 完了画面表示
 */
function DispComplete($template, $added_machines, $count) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>追加完了 - NET8 管理画面</title>
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
            max-width: 600px;
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
        .info {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        .info-item {
            font-size: 18px;
            color: #166534;
            margin: 8px 0;
        }
        .machine-list {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            max-height: 200px;
            overflow-y: auto;
            text-align: left;
        }
        .machine-list div {
            padding: 4px 0;
            color: #475569;
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
        <h1>台追加が完了しました</h1>

        <div class="info">
            <div class="info-item">
                <strong>追加台数:</strong> <?= $count ?>台
            </div>
            <div class="info-item">
                <strong>台番号:</strong> <?= min($added_machines) ?>番 ～ <?= max($added_machines) ?>番
            </div>
        </div>

        <div class="machine-list">
            <strong>追加された台:</strong>
            <?php foreach ($added_machines as $no): ?>
                <div>台番号 <?= $no ?> - トークン自動生成済み</div>
            <?php endforeach; ?>
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
