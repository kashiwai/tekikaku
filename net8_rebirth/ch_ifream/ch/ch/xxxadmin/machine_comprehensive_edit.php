<?php
/**
 * machine_comprehensive_edit.php
 *
 * マシン包括編集画面（全項目編集可能）
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
            UpdateMachine($template);
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
        header("Location: machines.php");
        exit;
    }

    // マシン情報取得（全項目）
    $sql = "SELECT dm.*,
                   mm.model_name, mm.category,
                   mo.owner_nickname,
                   mc.camera_mac,
                   lm.assign_flg as connection_status,
                   lm.last_heartbeat
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            LEFT JOIN mst_owner mo ON dm.owner_no = mo.owner_no
            LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
            LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
            WHERE dm.machine_no = :machine_no";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute(['machine_no' => $machine_no]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        echo "マシンが見つかりません";
        exit;
    }

    // 機種リスト取得
    $sql = "SELECT model_no, model_name, category FROM mst_model WHERE del_flg = 0 ORDER BY category, model_no";
    $models = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // オーナーリスト取得
    $sql = "SELECT owner_no, owner_nickname FROM mst_owner WHERE del_flg = 0 ORDER BY owner_no";
    $owners = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // カメラリスト取得
    $sql = "SELECT camera_no, camera_mac, camera_name FROM mst_camera WHERE del_flg = 0 ORDER BY camera_no";
    $cameras = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // ステータスリスト
    $statusList = [
        '0' => '停止中',
        '1' => '稼働中',
        '2' => 'メンテナンス',
        '3' => 'エラー',
        '4' => '撤去予定',
        '9' => '削除済み'
    ];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マシン包括編集 - NET8 管理画面</title>
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
            max-width: 1200px;
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

        .section {
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-left: auto;
        }

        .status-0 { background: #fee; color: #c00; }
        .status-1 { background: #efe; color: #060; }
        .status-2 { background: #ffe; color: #880; }
        .status-3 { background: #fee; color: #c00; }
        .status-4 { background: #eef; color: #008; }
        .status-9 { background: #eee; color: #666; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .required {
            color: #ef4444;
            margin-left: 4px;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        input:disabled,
        select:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
        }

        .radio-group {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
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
            width: auto;
            cursor: pointer;
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

        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #92400e;
        }

        .critical-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #991b1b;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        button, .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
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

        .readonly-value {
            padding: 12px;
            background: #f1f5f9;
            border-radius: 8px;
            color: #64748b;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🎮 マシン包括編集</h1>
            <p class="subtitle">マシンNo.<?= $machine['machine_no'] ?> の全情報を編集</p>

            <div class="critical-box">
                ⚠️ <strong>重要:</strong> ステータス変更は全システムに即座に反映されます。変更前に必ず確認してください。
            </div>

            <form method="POST" action="" onsubmit="return confirm('マシン情報を更新してよろしいですか？\nステータス変更は即座に全システムに反映されます。');">
                <input type="hidden" name="machine_no" value="<?= $machine['machine_no'] ?>">

                <!-- ステータスセクション（最重要） -->
                <div class="section">
                    <div class="section-title">
                        🚦 ステータス（最重要）
                        <span class="status-badge status-<?= $machine['machine_status'] ?>">
                            <?= $statusList[$machine['machine_status']] ?? '不明' ?>
                        </span>
                    </div>

                    <div class="warning-box">
                        💡 ステータス変更は以下に影響します：<br>
                        ・WebSocket接続の制御<br>
                        ・API応答の変更<br>
                        ・クライアント画面の表示<br>
                        ・データ収集の開始/停止
                    </div>

                    <div class="form-group">
                        <label>マシンステータス <span class="required">*</span></label>
                        <div class="radio-group">
                            <?php foreach ($statusList as $val => $label): ?>
                            <label class="radio-option">
                                <input type="radio" name="machine_status" value="<?= $val ?>"
                                       <?= $machine['machine_status'] == $val ? 'checked' : '' ?> required>
                                <span><?= $label ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 基本情報セクション -->
                <div class="section">
                    <div class="section-title">📋 基本情報</div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>マシン番号</label>
                            <div class="readonly-value"><?= $machine['machine_no'] ?></div>
                        </div>

                        <div class="form-group">
                            <label>マシンコード <span class="required">*</span></label>
                            <input type="text" name="machine_cd" value="<?= htmlspecialchars($machine['machine_cd']) ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>マシン名</label>
                            <input type="text" name="machine_name" value="<?= htmlspecialchars($machine['machine_name'] ?? '') ?>"
                                   placeholder="例: メインフロア-01">
                        </div>
                    </div>
                </div>

                <!-- 機種・オーナー情報セクション -->
                <div class="section">
                    <div class="section-title">🎯 機種・オーナー情報</div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>機種 <span class="required">*</span></label>
                            <select name="model_no" required>
                                <option value="">-- 選択してください --</option>
                                <?php
                                $currentCat = null;
                                foreach ($models as $model):
                                    if ($currentCat !== $model['category']) {
                                        if ($currentCat !== null) echo '</optgroup>';
                                        $catName = $model['category'] == 1 ? 'パチンコ' : ($model['category'] == 2 ? 'スロット' : '不明');
                                        echo '<optgroup label="' . $catName . '">';
                                        $currentCat = $model['category'];
                                    }
                                ?>
                                <option value="<?= $model['model_no'] ?>"
                                        <?= $model['model_no'] == $machine['model_no'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($model['model_name']) ?>
                                </option>
                                <?php
                                endforeach;
                                if ($currentCat !== null) echo '</optgroup>';
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>オーナー <span class="required">*</span></label>
                            <select name="owner_no" required>
                                <option value="">-- 選択してください --</option>
                                <?php foreach ($owners as $owner): ?>
                                <option value="<?= $owner['owner_no'] ?>"
                                        <?= $owner['owner_no'] == $machine['owner_no'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($owner['owner_nickname']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- カメラ・接続情報セクション -->
                <div class="section">
                    <div class="section-title">📹 カメラ・接続情報</div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>カメラ番号</label>
                            <select name="camera_no">
                                <option value="">-- 未割り当て --</option>
                                <?php foreach ($cameras as $camera): ?>
                                <option value="<?= $camera['camera_no'] ?>"
                                        <?= $camera['camera_no'] == $machine['camera_no'] ? 'selected' : '' ?>>
                                    <?= $camera['camera_no'] ?> - <?= htmlspecialchars($camera['camera_mac']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Signaling ID <span class="required">*</span></label>
                            <input type="number" name="signaling_id" value="<?= htmlspecialchars($machine['signaling_id']) ?>"
                                   min="1" max="99999" required>
                        </div>

                        <div class="form-group">
                            <label>IPアドレス</label>
                            <input type="text" name="ip_address" value="<?= htmlspecialchars($machine['ip_address'] ?? '') ?>"
                                   placeholder="例: 192.168.1.100">
                        </div>

                        <div class="form-group">
                            <label>MACアドレス</label>
                            <input type="text" name="mac_address" value="<?= htmlspecialchars($machine['mac_address'] ?? '') ?>"
                                   placeholder="例: 00:11:22:33:44:55">
                        </div>

                        <div class="form-group full-width">
                            <label>認証トークン</label>
                            <div class="readonly-value"><?= htmlspecialchars($machine['token']) ?></div>
                        </div>

                        <?php if ($machine['last_heartbeat']): ?>
                        <div class="form-group full-width">
                            <div class="info-box">
                                💓 最終接続: <?= htmlspecialchars($machine['last_heartbeat']) ?>
                                (ステータス: <?= $machine['connection_status'] === 'online' ? '✅ オンライン' : '⏸️ オフライン' ?>)
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 運用日程セクション -->
                <div class="section">
                    <div class="section-title">📅 運用日程</div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>稼働開始日</label>
                            <input type="date" name="release_date" value="<?= $machine['release_date'] ?>">
                        </div>

                        <div class="form-group">
                            <label>稼働終了日</label>
                            <input type="date" name="end_date" value="<?= $machine['end_date'] ?>">
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="machines.php" class="btn btn-secondary">
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
 * マシン情報更新処理
 */
function UpdateMachine($template) {
    // データ取得
    $machine_no = isset($_POST['machine_no']) ? intval($_POST['machine_no']) : 0;
    $machine_status = $_POST['machine_status'] ?? '';
    $machine_cd = $_POST['machine_cd'] ?? '';
    $machine_name = $_POST['machine_name'] ?? '';
    $model_no = isset($_POST['model_no']) ? intval($_POST['model_no']) : 0;
    $owner_no = isset($_POST['owner_no']) ? intval($_POST['owner_no']) : 0;
    $camera_no = isset($_POST['camera_no']) && $_POST['camera_no'] !== '' ? intval($_POST['camera_no']) : null;
    $signaling_id = $_POST['signaling_id'] ?? '';
    $ip_address = $_POST['ip_address'] ?? '';
    $mac_address = $_POST['mac_address'] ?? '';
    $release_date = $_POST['release_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    // バリデーション
    if ($machine_no <= 0) {
        throw new Exception('マシン番号が不正です');
    }
    if (empty($machine_cd)) {
        throw new Exception('マシンコードを入力してください');
    }
    if ($model_no <= 0) {
        throw new Exception('機種を選択してください');
    }
    if ($owner_no <= 0) {
        throw new Exception('オーナーを選択してください');
    }

    // 前のステータスを取得（ステータス変更検知用）
    $sql = "SELECT machine_status FROM dat_machine WHERE machine_no = :machine_no";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute(['machine_no' => $machine_no]);
    $old_machine = $stmt->fetch(PDO::FETCH_ASSOC);
    $status_changed = ($old_machine && $old_machine['machine_status'] !== $machine_status);

    // トランザクション開始
    $template->DB->autoCommit(false);

    // dat_machine更新
    $sql = "UPDATE dat_machine SET
                machine_cd = :machine_cd,
                machine_name = :machine_name,
                model_no = :model_no,
                owner_no = :owner_no,
                camera_no = :camera_no,
                signaling_id = :signaling_id,
                ip_address = :ip_address,
                mac_address = :mac_address,
                machine_status = :machine_status,
                release_date = :release_date,
                end_date = :end_date,
                upd_no = 1,
                upd_dt = NOW()
            WHERE machine_no = :machine_no";

    $stmt = $template->DB->prepare($sql);
    $result = $stmt->execute([
        'machine_cd' => $machine_cd,
        'machine_name' => $machine_name,
        'model_no' => $model_no,
        'owner_no' => $owner_no,
        'camera_no' => $camera_no,
        'signaling_id' => $signaling_id,
        'ip_address' => $ip_address,
        'mac_address' => $mac_address,
        'machine_status' => $machine_status,
        'release_date' => $release_date ?: null,
        'end_date' => $end_date ?: null,
        'machine_no' => $machine_no
    ]);

    if (!$result) {
        $template->DB->rollBack();
        throw new Exception('マシン情報の更新に失敗しました');
    }

    // ステータスが変更された場合、lnk_machineも更新
    if ($status_changed) {
        $lnk_status = ($machine_status == '1') ? 'active' : 'inactive';

        $sql = "UPDATE lnk_machine SET
                    status = :status,
                    upd_dt = NOW()
                WHERE machine_no = :machine_no";

        $stmt = $template->DB->prepare($sql);
        $stmt->execute([
            'status' => $lnk_status,
            'machine_no' => $machine_no
        ]);

        // ステータス変更ログを記録
        $sql = "INSERT INTO dat_machine_status_log
                (machine_no, old_status, new_status, changed_by, changed_at)
                VALUES (:machine_no, :old_status, :new_status, 1, NOW())";

        $stmt = $template->DB->prepare($sql);
        $stmt->execute([
            'machine_no' => $machine_no,
            'old_status' => $old_machine['machine_status'],
            'new_status' => $machine_status
        ]);
    }

    // コミット
    $template->DB->autoCommit(true);

    // 完了画面表示
    DispComplete($template, $machine_no, $status_changed);
}

/**
 * 完了画面表示
 */
function DispComplete($template, $machine_no, $status_changed) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新完了 - NET8 管理画面</title>
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
            text-align: left;
        }
        .info-item {
            font-size: 16px;
            color: #166534;
            margin: 8px 0;
        }
        .warning-box {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            font-size: 14px;
            color: #92400e;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        button {
            flex: 1;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="success-icon">✅</div>
        <h1>マシン情報を更新しました</h1>

        <div class="info-box">
            <div class="info-item">
                <strong>マシン番号:</strong> <?= $machine_no ?>
            </div>
            <?php if ($status_changed): ?>
            <div class="warning-box">
                ⚡ <strong>ステータスが変更されました</strong><br>
                全システムに反映されています
            </div>
            <?php endif; ?>
        </div>

        <div class="btn-group">
            <button onclick="location.href='machine_comprehensive_edit.php?machine_no=<?= $machine_no ?>'" class="btn-secondary">
                ← 編集画面へ戻る
            </button>
            <button onclick="location.href='machines.php'">
                一覧へ →
            </button>
        </div>
    </div>
</body>
</html>
<?php
}
?>
