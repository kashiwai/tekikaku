<?php
/**
 * NET8 AI Control System - Machine Management
 * マシン管理画面（40台のWindows PC管理）
 *
 * @package NET8
 * @author  AI Control System
 * @version 1.0
 * @since   2025/11/05
 */

require_once('../../_etc/require_files_admin.php');

// メイン処理
main();

function main() {
    try {
        // 管理系表示コントロールのインスタンス生成
        $template = new TemplateAdmin();

        // データ取得
        getData($_POST, array("M", "machine_no"));

        // 実処理
        switch ($_POST["M"]) {
            case "update":          // マシン情報更新
                ProcUpdate($template);
                break;

            case "bulk_register":   // 40台一括登録
                ProcBulkRegister($template);
                break;

            case "delete":          // マシン削除
                ProcDelete($template);
                break;

            default:                // マシン一覧表示
                DispMachineList($template);
        }

    } catch (Exception $e) {
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>";
        echo "<h1>エラーが発生しました</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</body></html>";
    }
}

/**
 * マシン一覧表示
 */
function DispMachineList($template, $message = "") {
    // マシン一覧取得
    $sql = (new SqlString())
            ->setAutoConvert( [$template->DB,"conv_sql"] )
            ->select()
                ->field("machine_no, name, camera_no, signaling_id, ip_address, mac_address,
                         chrome_rd_session_id, token, status, last_heartbeat")
                ->from("dat_machine")
                ->orderby("machine_no ASC")
            ->createSQL();

    $machines = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

    // 統計情報
    $total_machines = count($machines);
    $online_count = 0;
    $offline_count = 0;
    $error_count = 0;

    foreach ($machines as $machine) {
        switch ($machine['status']) {
            case 'online':
                $online_count++;
                break;
            case 'offline':
                $offline_count++;
                break;
            case 'error':
                $error_count++;
                break;
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 マシン管理 - AI Control System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(245, 87, 108, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            font-size: 48px;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-icon.online {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        }

        .stat-icon.offline {
            background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%);
        }

        .stat-icon.error {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }

        .stat-content h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .stat-content p {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
        }

        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .machine-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .machine-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .machine-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .machine-name {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-online {
            background: #dcfce7;
            color: #166534;
        }

        .status-offline {
            background: #f1f5f9;
            color: #475569;
        }

        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .machine-info {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
        }

        .info-label {
            font-weight: 600;
            min-width: 100px;
        }

        .info-value {
            color: #0f172a;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .machine-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .message.success {
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
        }

        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 64px 32px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h2 {
            font-size: 24px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
            }

            .machines-grid {
                grid-template-columns: 1fr;
            }

            .machine-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <h1>🎮 マシン管理 - AI Control System</h1>
            <p style="color: #64748b; margin-top: 8px;">40台のWindows PCをリモート管理</p>

            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">← ダッシュボードに戻る</a>
                <form method="POST" action="machine_control.php" style="display: inline;">
                    <input type="hidden" name="M" value="bulk_register">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('40台のマシンを一括登録しますか？\n既存のデータは保持されます。');">
                        📦 40台一括登録
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="message <?= strpos($message, '❌') !== false ? 'error' : 'success' ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <!-- 統計情報 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">🎮</div>
                <div class="stat-content">
                    <h3>総マシン数</h3>
                    <p><?= $total_machines ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon online">✅</div>
                <div class="stat-content">
                    <h3>オンライン</h3>
                    <p><?= $online_count ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon offline">⏸️</div>
                <div class="stat-content">
                    <h3>オフライン</h3>
                    <p><?= $offline_count ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon error">❌</div>
                <div class="stat-content">
                    <h3>エラー</h3>
                    <p><?= $error_count ?></p>
                </div>
            </div>
        </div>

        <!-- マシン一覧 -->
        <?php if (count($machines) > 0): ?>
        <div class="machines-grid">
            <?php foreach ($machines as $machine): ?>
            <div class="machine-card">
                <div class="machine-header">
                    <div class="machine-name">
                        <?= htmlspecialchars($machine['name'] ?: 'MACHINE-' . str_pad($machine['machine_no'], 2, '0', STR_PAD_LEFT)) ?>
                    </div>
                    <span class="status-badge status-<?= $machine['status'] ?>">
                        <?= strtoupper($machine['status']) ?>
                    </span>
                </div>

                <div class="machine-info">
                    <div class="info-row">
                        <span class="info-label">🔢 マシン番号:</span>
                        <span class="info-value"><?= $machine['machine_no'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📹 カメラ番号:</span>
                        <span class="info-value"><?= $machine['camera_no'] ?: '-' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📡 Signaling ID:</span>
                        <span class="info-value"><?= htmlspecialchars($machine['signaling_id']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">🌐 IPアドレス:</span>
                        <span class="info-value"><?= htmlspecialchars($machine['ip_address'] ?: '未設定') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">💻 MACアドレス:</span>
                        <span class="info-value"><?= htmlspecialchars($machine['mac_address'] ?: '未設定') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">🔐 トークン:</span>
                        <span class="info-value"><?= substr($machine['token'], 0, 20) ?>...</span>
                    </div>
                    <?php if ($machine['last_heartbeat']): ?>
                    <div class="info-row">
                        <span class="info-label">💓 最終接続:</span>
                        <span class="info-value"><?= htmlspecialchars($machine['last_heartbeat']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- カメラNo編集フォーム -->
                <form method="POST" action="machine_control.php" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                    <input type="hidden" name="M" value="update">
                    <input type="hidden" name="machine_no" value="<?= $machine['machine_no'] ?>">

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px;">
                            📹 新しいカメラNo:
                        </label>
                        <input type="number" name="camera_no" value="<?= $machine['camera_no'] ?>"
                               style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;"
                               min="1" max="999" required>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px;">
                            💻 MACアドレス (任意):
                        </label>
                        <input type="text" name="mac_address" value="<?= htmlspecialchars($machine['mac_address'] ?: '') ?>"
                               style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: 'Courier New', monospace;"
                               placeholder="e0:51:d8:16:13:3d"
                               pattern="^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$">
                        <small style="color: #64748b; font-size: 11px;">※MACアドレスを入力すると、mst_camera/mst_cameralistも連携更新されます</small>
                    </div>

                    <button type="submit" style="width: 100%; padding: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        💾 カメラNo更新
                    </button>
                </form>

                <div class="machine-actions" style="margin-top: 12px;">
                    <a href="machine_edit.php?machine_no=<?= $machine['machine_no'] ?>" class="btn-action btn-edit">
                        ✏️ 詳細編集
                    </a>
                    <form method="POST" action="machine_control.php" style="display: inline;">
                        <input type="hidden" name="M" value="delete">
                        <input type="hidden" name="machine_no" value="<?= $machine['machine_no'] ?>">
                        <button type="submit" class="btn-action btn-delete"
                                onclick="return confirm('マシン<?= $machine['machine_no'] ?>を削除しますか？');">
                            🗑️ 削除
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🎮</div>
            <h2>マシンが登録されていません</h2>
            <p>「40台一括登録」ボタンをクリックしてマシンを登録してください</p>
            <form method="POST" action="machine_control.php">
                <input type="hidden" name="M" value="bulk_register">
                <button type="submit" class="btn btn-primary">
                    📦 40台一括登録
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
}

/**
 * 40台一括登録処理
 */
function ProcBulkRegister($template) {
    $template->DB->autoCommit(false);

    try {
        $registered_count = 0;

        for ($i = 1; $i <= 40; $i++) {
            $machine_no = $i;
            $name = 'MACHINE-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $camera_no = $i;
            $signaling_id = 'PEER' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $token = 'net8_m' . str_pad($i, 3, '0', STR_PAD_LEFT) . '_' . bin2hex(random_bytes(16));

            // 既存チェック
            $check_sql = (new SqlString())
                    ->setAutoConvert( [$template->DB,"conv_sql"] )
                    ->select()
                        ->field("machine_no")
                        ->from("dat_machine")
                        ->where()
                            ->and("machine_no = ", $machine_no, FD_NUM)
                    ->createSQL();

            $exists = $template->DB->getRow($check_sql, PDO::FETCH_ASSOC);

            if (!$exists) {
                // 新規登録
                $insert_sql = (new SqlString())
                        ->setAutoConvert( [$template->DB,"conv_sql"] )
                        ->insert("dat_machine")
                            ->value("machine_no", $machine_no, FD_NUM)
                            ->value("name", $name, FD_STR)
                            ->value("camera_no", $camera_no, FD_NUM)
                            ->value("signaling_id", $signaling_id, FD_STR)
                            ->value("token", $token, FD_STR)
                            ->value("status", "offline", FD_STR)
                            ->value("model_no", 1, FD_NUM)
                            ->value("convert_no", 0, FD_NUM)
                            ->value("release_date", date("Y-m-d"), FD_STR)
                            ->value("machine_status", 0, FD_NUM)
                            ->value("del_flg", 0, FD_NUM)
                        ->createSQL();

                $template->DB->query($insert_sql);
                $registered_count++;
            }
        }

        $template->DB->autoCommit(true);

        $message = "✅ {$registered_count}台のマシンを新規登録しました。";
        DispMachineList($template, $message);

    } catch (Exception $e) {
        $template->DB->autoCommit(true);
        throw $e;
    }
}

/**
 * マシン更新処理（カメラNo変更）
 */
function ProcUpdate($template) {
    getData($_POST, array("machine_no", "camera_no", "mac_address"));

    $machine_no = intval($_POST["machine_no"]);
    $new_camera_no = intval($_POST["camera_no"]);
    $mac_address = isset($_POST["mac_address"]) ? strtolower(trim($_POST["mac_address"])) : '';

    $template->DB->autoCommit(false);

    try {
        // 1. dat_machineのcamera_noを更新
        $update_machine_sql = (new SqlString())
                ->setAutoConvert( [$template->DB,"conv_sql"] )
                ->update("dat_machine")
                    ->set()
                        ->value("camera_no", $new_camera_no, FD_NUM)
                    ->where()
                        ->and("machine_no = ", $machine_no, FD_NUM)
                ->createSQL();

        $template->DB->query($update_machine_sql);

        // 2. MACアドレスが入力されている場合、mst_cameraとmst_cameralistを連携
        if (!empty($mac_address)) {
            // mst_cameraにレコードが存在するかチェック
            $check_camera_sql = (new SqlString())
                    ->setAutoConvert( [$template->DB,"conv_sql"] )
                    ->select()
                        ->field("camera_no, camera_mac")
                        ->from("mst_camera")
                        ->where()
                            ->and("camera_no = ", $new_camera_no, FD_NUM)
                            ->and("del_flg = ", 0, FD_NUM)
                    ->createSQL();

            $existing_camera = $template->DB->getRow($check_camera_sql, PDO::FETCH_ASSOC);

            if ($existing_camera) {
                // 既存のcamera_noレコードのMAC addressを更新
                $update_camera_sql = (new SqlString())
                        ->setAutoConvert( [$template->DB,"conv_sql"] )
                        ->update("mst_camera")
                            ->set()
                                ->value("camera_mac", $mac_address, FD_STR)
                            ->where()
                                ->and("camera_no = ", $new_camera_no, FD_NUM)
                        ->createSQL();

                $template->DB->query($update_camera_sql);
            } else {
                // 新規にmst_cameraにレコードを作成
                $insert_camera_sql = (new SqlString())
                        ->setAutoConvert( [$template->DB,"conv_sql"] )
                        ->insert("mst_camera")
                            ->value("camera_no", $new_camera_no, FD_NUM)
                            ->value("camera_mac", $mac_address, FD_STR)
                            ->value("camera_name", "CAMERA-" . str_pad($new_camera_no, 2, '0', STR_PAD_LEFT), FD_STR)
                            ->value("camera_status", 1, FD_NUM)
                            ->value("del_flg", 0, FD_NUM)
                            ->value("add_no", 1, FD_NUM)
                            ->value("add_dt", "current_timestamp", FD_FUNCTION)
                        ->createSQL();

                $template->DB->query($insert_camera_sql);
            }

            // 3. mst_cameralistのcamera_noを更新
            $update_cameralist_sql = (new SqlString())
                    ->setAutoConvert( [$template->DB,"conv_sql"] )
                    ->update("mst_cameralist")
                        ->set()
                            ->value("camera_no", $new_camera_no, FD_NUM)
                        ->where()
                            ->and("mac_address = ", $mac_address, FD_STR)
                    ->createSQL();

            $template->DB->query($update_cameralist_sql);
        }

        $template->DB->autoCommit(true);

        $message = "✅ マシン{$machine_no}のカメラNoを {$new_camera_no} に更新しました。";
        DispMachineList($template, $message);

    } catch (Exception $e) {
        $template->DB->rollBack();
        $message = "❌ エラー: " . $e->getMessage();
        DispMachineList($template, $message);
    }
}

/**
 * マシン削除処理
 */
function ProcDelete($template) {
    getData($_POST, array("machine_no"));

    $sql = (new SqlString())
            ->setAutoConvert( [$template->DB,"conv_sql"] )
            ->delete("dat_machine")
                ->where()
                    ->and("machine_no = ", $_POST["machine_no"], FD_NUM)
            ->createSQL();

    $template->DB->query($sql);

    $message = "✅ マシン{$_POST['machine_no']}を削除しました。";
    DispMachineList($template, $message);
}

?>
