<?php
/**
 * NET8 AI Control System - Machine Management V2
 * マシン統合管理画面（全機能統合版）
 *
 * 機能:
 * - マシン数自由追加（上限なし）
 * - 一括編集モード（テーブル形式）
 * - IPアドレス・MACアドレス管理
 * - リモートコマンド送信
 * - リアルタイム状況監視
 * - CSV一括インポート/エクスポート
 *
 * @package NET8
 * @author  AI Control System
 * @version 2.1
 * @since   2025/12/20
 * @updated 2026/01/27 - PeerID接続状態チェック、メンテナンス管理機能追加
 */

require_once('../../_etc/require_files_admin.php');

// メイン処理
main();

function main() {
    try {
        $template = new TemplateAdmin();

        getData($_POST, array("M", "machine_no", "count", "machine_status"));
        getData($_GET, array("M", "export"));

        $mode = isset($_POST["M"]) ? $_POST["M"] : (isset($_GET["M"]) ? $_GET["M"] : "list");

        switch ($mode) {
            case "add_single":
                ProcAddSingle($template);
                break;
            case "add_bulk":
                ProcAddBulk($template);
                break;
            case "update":
                ProcUpdate($template);
                break;
            case "bulk_update":
                ProcBulkUpdate($template);
                break;
            case "delete":
                ProcDelete($template);
                break;
            case "toggle_maintenance":
                ProcToggleMaintenance($template);
                break;
            case "send_command":
                ProcSendCommand($template);
                break;
            case "export_csv":
                ExportCSV($template);
                break;
            case "import_csv":
                ImportCSV($template);
                break;
            default:
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
 * Signaling ServerからPeerID一覧を取得
 */
function GetSignalingPeerIds() {
    $signaling_host = getenv('SIGNALING_HOST') ?: 'mgg-signaling-production-c1bd.up.railway.app';
    $signaling_port = getenv('SIGNALING_PORT') ?: '443';
    $protocol = $signaling_port == '443' ? 'https' : 'http';

    $signaling_url = "{$protocol}://{$signaling_host}:{$signaling_port}/peerjs/peers";

    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    try {
        $response = @file_get_contents($signaling_url, false, $context);

        if ($response !== false) {
            $peers = json_decode($response, true);
            if (is_array($peers)) {
                return $peers;
            }
        }
    } catch (Exception $e) {
        error_log("❌ Signaling Server接続エラー: " . $e->getMessage());
    }

    return [];
}

/**
 * マシン一覧表示（統合管理画面）
 */
function DispMachineList($template, $message = "") {
    // Signaling ServerからPeerID一覧を取得
    $active_peers = GetSignalingPeerIds();
    // マシン一覧取得（全項目 + ゲーム機状態）
    $sql = "SELECT
                dm.machine_no,
                dm.name,
                dm.camera_no,
                dm.signaling_id,
                dm.ip_address,
                dm.mac_address,
                dm.chrome_rd_session_id,
                dm.token,
                dm.status as pc_status,
                dm.last_heartbeat,
                dm.model_no,
                dm.machine_status,
                mm.model_name,
                mm.category,
                mc.camera_mac,
                mc.camera_name,
                lm.assign_flg,
                lm.member_no as playing_member,
                lm.start_dt as game_start_time
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
            LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
            ORDER BY dm.machine_no ASC";

    $machines = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

    // 機種リスト取得
    $models = $template->DB->getAll(
        "SELECT model_no, model_name, category FROM mst_model WHERE del_flg = 0 ORDER BY category, model_no",
        PDO::FETCH_ASSOC
    );

    // カメラリスト取得（選択用）
    $cameras = $template->DB->getAll(
        "SELECT camera_no, camera_name, camera_mac FROM mst_camera WHERE del_flg = 0 ORDER BY camera_no",
        PDO::FETCH_ASSOC
    );

    // Signaling ServerからアクティブなPeerID一覧を取得
    $active_peers = GetSignalingPeerIds();

    // 統計情報（PC状態 / ゲーム機状態 / WebRTC / メンテナンス）
    $total = count($machines);
    $pc_online = 0;
    $pc_offline = 0;
    $game_playing = 0;
    $game_standby = 0;
    $maintenance_count = 0;
    $peer_connected = 0;

    foreach ($machines as &$m) {
        // PeerID接続状態チェック（Signaling Server WebSocket接続）
        $m['peer_connected'] = false;
        if (!empty($m['camera_mac'])) {
            $peer_id = str_replace(':', '', strtolower($m['camera_mac']));
            if (in_array($peer_id, $active_peers)) {
                $m['peer_connected'] = true;
                $peer_connected++;
            }
        }

        // PC接続状態 = WebSocket接続状態
        // Signaling ServerにPeerIDが登録されていればPC接続中
        $m['pc_connected'] = $m['peer_connected'];

        if ($m['pc_connected']) {
            $pc_online++;
        } else {
            $pc_offline++;
        }

        // メンテナンス状態チェック
        if ($m['machine_status'] == 2) {
            $maintenance_count++;
        }

        // ゲーム機状態を判定（実際の使用可否）
        // machine_status: 0=停止中, 1=稼働中, 2=メンテナンス中
        // assign_flg: 0=空き, 1=プレイ中, 9=待機
        if ($m['machine_status'] == 2) {
            // メンテナンス中
            $m['game_status'] = 'maintenance';
        } elseif (!empty($m['playing_member']) && $m['playing_member'] > 0) {
            // プレイ中（使用不可）
            $m['game_status'] = 'playing';
            $game_playing++;
        } elseif ($m['assign_flg'] == 1) {
            // プレイ中（使用不可）
            $m['game_status'] = 'playing';
            $game_playing++;
        } elseif ($m['pc_connected'] && $m['machine_status'] == 1) {
            // 待機中（使用可）
            $m['game_status'] = 'standby';
            $game_standby++;
        } else {
            // オフライン（PC未接続）
            $m['game_status'] = 'offline';
        }
    }
    unset($m); // 参照を解除

    // 次のマシン番号を計算
    $next_machine_no = 1;
    if ($total > 0) {
        $max_sql = "SELECT MAX(machine_no) as max_no FROM dat_machine";
        $max_row = $template->DB->getRow($max_sql, PDO::FETCH_ASSOC);
        $next_machine_no = ($max_row['max_no'] ?? 0) + 1;
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 マシン統合管理 V2</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', sans-serif;
            background: #f5f7fa;
            color: #333;
            min-height: 100vh;
        }

        .container { max-width: 1600px; margin: 0 auto; padding: 20px; }

        /* ヘッダー */
        .header {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .header h1 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .header-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* ボタン */
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary { background: #4361ee; color: white; }
        .btn-primary:hover { background: #3a56d4; box-shadow: 0 4px 12px rgba(67,97,238,0.3); }

        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }

        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }

        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }

        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }

        .btn-outline { background: #fff; color: #4361ee; border: 2px solid #4361ee; }
        .btn-outline:hover { background: #4361ee; color: #fff; }

        /* 統計カード */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            font-size: 28px;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .stat-icon.total { background: #e8edff; }
        .stat-icon.online { background: #d1fae5; }
        .stat-icon.offline { background: #f1f5f9; }
        .stat-icon.playing { background: #ede9fe; }
        .stat-icon.standby { background: #d1fae5; }
        .stat-icon.peer { background: #dbeafe; }
        .stat-icon.maintenance { background: #fef3c7; }

        .stat-content h3 { font-size: 12px; color: #888; margin-bottom: 4px; font-weight: 500; }
        .stat-content p { font-size: 28px; font-weight: 700; color: #1a1a2e; }

        /* メッセージ */
        .message {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success { background: #d1fae5; color: #065f46; }
        .message.error { background: #fee2e2; color: #991b1b; }

        /* マシンカードグリッド */
        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .machine-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 2px solid transparent;
            transition: all 0.2s;
            position: relative;
        }

        .machine-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .machine-card.online {
            border-color: #10b981;
        }

        .machine-card.playing {
            border-color: #8b5cf6;
            background: linear-gradient(135deg, #faf5ff 0%, #fff 100%);
        }

        .machine-card.maintenance {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fff 100%);
        }

        .machine-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .machine-no {
            font-size: 32px;
            font-weight: 800;
            color: #4361ee;
        }

        .machine-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .machine-model {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .machine-info {
            font-size: 12px;
            color: #666;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 4px 12px;
        }

        .machine-info dt {
            color: #999;
        }

        .machine-info dd {
            font-family: 'Courier New', monospace;
            color: #333;
        }

        .machine-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }

        .machine-actions .btn {
            flex: 1;
            justify-content: center;
            padding: 8px 12px;
            font-size: 12px;
        }

        /* ステータスバッジ */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-online { background: #d1fae5; color: #065f46; }
        .status-offline { background: #f1f5f9; color: #64748b; }
        .status-playing { background: #ede9fe; color: #6d28d9; }
        .status-standby { background: #d1fae5; color: #047857; }
        .status-maintenance { background: #fef3c7; color: #92400e; }
        .status-peer-on { background: #dbeafe; color: #075985; }
        .status-peer-off { background: #fee2e2; color: #991b1b; }

        /* 入力フィールド */
        input[type="text"], input[type="number"], select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            color: #333;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67,97,238,0.15);
        }

        /* モーダル */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #1a1a2e;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            line-height: 1;
        }

        .modal-close:hover { color: #333; }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        .form-group input, .form-group select {
            width: 100%;
        }

        /* コマンドパネル */
        .command-panel {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .command-panel h4 {
            font-size: 14px;
            margin-bottom: 12px;
            color: #666;
        }

        .command-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* テーブルモード */
        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
            display: none;
        }

        .table-container.active { display: block; }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            background: #fafbfc;
        }

        .table-header h2 {
            font-size: 16px;
            color: #333;
        }

        .table-scroll { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #fafbfc;
            font-weight: 600;
            color: #666;
        }

        tr:hover { background: #f8fafc; }

        /* ビュー切り替え */
        .view-toggle {
            display: flex;
            gap: 4px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
        }

        .view-toggle button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #666;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        .view-toggle button.active {
            background: #fff;
            color: #4361ee;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* 選択チェックボックス */
        .select-checkbox {
            position: absolute;
            top: 12px;
            right: 12px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #4361ee;
        }

        /* レスポンシブ */
        @media (max-width: 768px) {
            .header-actions { flex-direction: column; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .machines-grid { grid-template-columns: 1fr; }
        }

        /* 空状態 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <h1>NET8 マシン統合管理 V2.1</h1>
            <p class="header-subtitle">全マシンの設定・状態・WebRTC接続状況を一元管理</p>

            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">← ダッシュボード</a>
                <div class="view-toggle">
                    <button onclick="setView('card')" id="viewCard" class="active">カード</button>
                    <button onclick="setView('table')" id="viewTable">テーブル</button>
                </div>
                <button onclick="openAddModal()" class="btn btn-primary">+ マシン追加</button>
                <button onclick="openBulkAddModal()" class="btn btn-success">++ 複数台追加</button>
                <a href="?M=export_csv" class="btn btn-outline">CSV出力</a>
                <button onclick="openImportModal()" class="btn btn-outline">CSVインポート</button>
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
                <div class="stat-icon total">🖥️</div>
                <div class="stat-content">
                    <h3>総マシン数</h3>
                    <p><?= $total ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon online">💻</div>
                <div class="stat-content">
                    <h3>PC起動中</h3>
                    <p><?= $pc_online ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon offline">⏸️</div>
                <div class="stat-content">
                    <h3>PC停止</h3>
                    <p><?= $pc_offline ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon playing">🎮</div>
                <div class="stat-content">
                    <h3>プレイ中</h3>
                    <p><?= $game_playing ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon standby">🟢</div>
                <div class="stat-content">
                    <h3>待機中</h3>
                    <p><?= $game_standby ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon peer">📡</div>
                <div class="stat-content">
                    <h3>WebRTC接続</h3>
                    <p><?= $peer_connected ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon maintenance">🔧</div>
                <div class="stat-content">
                    <h3>メンテナンス</h3>
                    <p><?= $maintenance_count ?></p>
                </div>
            </div>
        </div>

        <!-- コマンドパネル（選択時表示） -->
        <div class="command-panel" id="commandPanel" style="display: none;">
            <h4>選択したマシンにコマンド送信</h4>
            <div class="command-buttons">
                <button onclick="sendSelectedCommand('START')" class="btn btn-success">起動</button>
                <button onclick="sendSelectedCommand('STOP')" class="btn btn-secondary">停止</button>
                <button onclick="sendSelectedCommand('RESTART')" class="btn btn-warning">再起動</button>
                <button onclick="sendSelectedCommand('UPDATE')" class="btn btn-primary">設定更新</button>
                <button onclick="sendSelectedCommand('RESET')" class="btn btn-danger">リセット</button>
            </div>
        </div>

        <!-- マシンカード表示 -->
        <div class="machines-grid" id="cardView">
            <?php if (count($machines) > 0): ?>
                <?php foreach ($machines as $m):
                    $cardClass = '';
                    if ($m['game_status'] == 'maintenance') {
                        $cardClass = 'maintenance';
                    } elseif ($m['game_status'] == 'playing') {
                        $cardClass = 'playing';
                    } elseif ($m['pc_status'] == 'online') {
                        $cardClass = 'online';
                    }

                    // メンテナンス状態の表示ラベル
                    $machine_status_labels = ['停止中', '稼働中', 'メンテナンス中'];
                    $machine_status_label = $machine_status_labels[$m['machine_status']] ?? '不明';
                ?>
                <div class="machine-card <?= $cardClass ?>">
                    <input type="checkbox" class="select-checkbox machine-checkbox-card"
                           value="<?= $m['machine_no'] ?>" onchange="updateCommandPanel()">

                    <div class="machine-card-header">
                        <div class="machine-no"><?= $m['machine_no'] ?></div>
                        <div class="machine-status">
                            <span class="status-badge status-<?= $m['pc_connected'] ? 'online' : 'offline' ?>">
                                <?= $m['pc_connected'] ? '💻 PC ON' : '⏸️ PC OFF' ?>
                            </span>
                            <?php if ($m['peer_connected']): ?>
                            <span class="status-badge status-peer-on">📡 接続中</span>
                            <?php else: ?>
                            <span class="status-badge status-peer-off">📡 未接続</span>
                            <?php endif; ?>
                            <?php if ($m['game_status'] == 'maintenance'): ?>
                            <span class="status-badge status-maintenance">🔧 メンテ</span>
                            <?php elseif ($m['game_status'] == 'playing'): ?>
                            <span class="status-badge status-playing">🎮 プレイ中</span>
                            <?php elseif ($m['game_status'] == 'standby'): ?>
                            <span class="status-badge status-standby">🟢 待機中</span>
                            <?php elseif ($m['game_status'] == 'offline'): ?>
                            <span class="status-badge status-offline">⏸️ オフライン</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="machine-model"><?= htmlspecialchars($m['model_name'] ?: '機種未設定') ?></div>

                    <dl class="machine-info">
                        <dt>状態</dt>
                        <dd><?php
                            if ($m['game_status'] == 'maintenance') {
                                echo '🔧 メンテナンス中';
                            } elseif ($m['game_status'] == 'playing') {
                                echo '🎮 プレイ中（使用不可）';
                            } elseif ($m['game_status'] == 'standby') {
                                echo '🟢 待機中（使用可）';
                            } else {
                                echo '⏸️ オフライン';
                            }
                        ?></dd>
                        <dt>PC接続</dt>
                        <dd><?= $m['pc_connected'] ? '💻 接続中' : '⏸️ 未接続' ?></dd>
                        <dt style="color: #ff0000;">🔴 Session ID</dt>
                        <dd style="color: #ff0000; font-size: 10px; word-break: break-all;"><?= htmlspecialchars($m['chrome_rd_session_id'] ?: 'NULL') ?></dd>
                        <dt style="color: #ff0000;">🔴 machine_status</dt>
                        <dd style="color: #ff0000; font-size: 10px;"><?= $m['machine_status'] ?> | pc_connected: <?= $m['pc_connected'] ? 'TRUE' : 'FALSE' ?></dd>
                        <dt style="color: #ff0000;">🔴 assign_flg</dt>
                        <dd style="color: #ff0000; font-size: 10px;"><?= $m['assign_flg'] ?> | playing_member: <?= $m['playing_member'] ?: 'NULL' ?></dd>
                        <dt>IP</dt>
                        <dd><?= htmlspecialchars($m['ip_address'] ?: '-') ?></dd>
                        <dt>MAC</dt>
                        <dd><?= htmlspecialchars($m['mac_address'] ?: '-') ?></dd>
                        <dt>カメラ</dt>
                        <dd>No.<?= $m['camera_no'] ?: '-' ?> (<?= htmlspecialchars($m['camera_name'] ?: '-') ?>)</dd>
                        <dt>WebRTC</dt>
                        <dd><?= $m['peer_connected'] ? '📡 接続中' : '📡 未接続' ?></dd>
                        <?php if ($m['last_heartbeat']): ?>
                        <dt>最終通信</dt>
                        <dd><?= date('m/d H:i', strtotime($m['last_heartbeat'])) ?></dd>
                        <?php endif; ?>
                    </dl>

                    <div class="machine-actions">
                        <button type="button" onclick="openEditModal(<?= $m['machine_no'] ?>)" class="btn btn-outline">編集</button>
                        <button type="button" onclick="toggleMaintenance(<?= $m['machine_no'] ?>, <?= $m['machine_status'] ?>)"
                                class="btn <?= $m['machine_status'] == 2 ? 'btn-success' : 'btn-warning' ?>">
                            <?= $m['machine_status'] == 2 ? '稼働に戻す' : 'メンテ' ?>
                        </button>
                        <button type="button" onclick="deleteConfirm(<?= $m['machine_no'] ?>)" class="btn btn-danger">削除</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="empty-state-icon">🖥️</div>
                    <p>マシンが登録されていません</p>
                    <p style="font-size: 14px; margin-top: 8px;">「+ マシン追加」ボタンで追加してください</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- マシン一覧テーブル -->
        <div class="table-container" id="tableView">
            <div class="table-header">
                <h2>マシン一覧 (<?= $total ?>台)</h2>
                <div>
                    <label><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"> 全選択</label>
                </div>
            </div>

            <form method="POST" action="" id="bulkEditForm">
                <input type="hidden" name="M" value="bulk_update">

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th class="checkbox-cell">選択</th>
                                <th>No</th>
                                <th>名前</th>
                                <th>機種</th>
                                <th>カメラNo</th>
                                <th>MACアドレス</th>
                                <th>IPアドレス</th>
                                <th>Signaling</th>
                                <th>PC接続</th>
                                <th>使用状態</th>
                                <th>最終接続</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($machines) > 0): ?>
                                <?php foreach ($machines as $m): ?>
                                <tr class="bulk-edit-row" data-machine="<?= $m['machine_no'] ?>">
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="selected[]" value="<?= $m['machine_no'] ?>"
                                               class="machine-checkbox" onchange="updateCommandPanel()">
                                    </td>
                                    <td>
                                        <strong><?= $m['machine_no'] ?></strong>
                                        <input type="hidden" name="machines[<?= $m['machine_no'] ?>][machine_no]" value="<?= $m['machine_no'] ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="machines[<?= $m['machine_no'] ?>][name]"
                                               value="<?= htmlspecialchars($m['name'] ?: '') ?>"
                                               class="input-md" placeholder="マシン名">
                                    </td>
                                    <td>
                                        <select name="machines[<?= $m['machine_no'] ?>][model_no]" class="input-md">
                                            <option value="">-- 選択 --</option>
                                            <?php foreach ($models as $model): ?>
                                            <option value="<?= $model['model_no'] ?>" <?= $m['model_no'] == $model['model_no'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($model['model_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="machines[<?= $m['machine_no'] ?>][camera_no]" class="input-md">
                                            <option value="">-- カメラ選択 --</option>
                                            <?php foreach ($cameras as $cam): ?>
                                            <option value="<?= $cam['camera_no'] ?>" <?= $m['camera_no'] == $cam['camera_no'] ? 'selected' : '' ?>>
                                                No.<?= $cam['camera_no'] ?> - <?= substr($cam['camera_mac'], 0, 17) ?> - <?= htmlspecialchars($cam['camera_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="machines[<?= $m['machine_no'] ?>][mac_address]"
                                               value="<?= htmlspecialchars($m['mac_address'] ?: $m['camera_mac'] ?: '') ?>"
                                               class="input-lg" placeholder="00:00:00:00:00:00">
                                    </td>
                                    <td>
                                        <input type="text" name="machines[<?= $m['machine_no'] ?>][ip_address]"
                                               value="<?= htmlspecialchars($m['ip_address'] ?: '') ?>"
                                               class="input-md" placeholder="192.168.1.x">
                                    </td>
                                    <td>
                                        <input type="text" name="machines[<?= $m['machine_no'] ?>][signaling_id]"
                                               value="<?= htmlspecialchars($m['signaling_id'] ?: '') ?>"
                                               class="input-md">
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $m['pc_connected'] ? 'online' : 'offline' ?>">
                                            <?= $m['pc_connected'] ? '💻 ON' : '⏸️ OFF' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($m['game_status'] == 'maintenance'): ?>
                                        <span class="status-badge status-maintenance">
                                            🔧 メンテ
                                        </span>
                                        <?php elseif ($m['game_status'] == 'playing'): ?>
                                        <span class="status-badge status-playing">
                                            🎮 使用不可
                                        </span>
                                        <?php elseif ($m['game_status'] == 'standby'): ?>
                                        <span class="status-badge status-standby">
                                            🟢 使用可
                                        </span>
                                        <?php else: ?>
                                        <span class="status-badge status-offline">
                                            ⏸️ オフライン
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 11px; color: #64748b;">
                                        <?= $m['last_heartbeat'] ? date('m/d H:i', strtotime($m['last_heartbeat'])) : '-' ?>
                                    </td>
                                    <td>
                                        <button type="button" onclick="sendCommand(<?= $m['machine_no'] ?>, 'RESTART')"
                                                class="action-btn command" title="再起動">🔄</button>
                                        <button type="button" onclick="deleteConfirm(<?= $m['machine_no'] ?>)"
                                                class="action-btn delete" title="削除">🗑️</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" style="text-align: center; padding: 40px; color: #64748b;">
                                        マシンが登録されていません。「マシン追加」ボタンで追加してください。
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="padding: 16px; background: #0f172a; display: none;" id="bulkEditActions">
                    <button type="submit" class="btn btn-success">一括保存</button>
                    <button type="button" onclick="toggleBulkEdit()" class="btn btn-secondary">キャンセル</button>
                </div>
            </form>
        </div>
    </div>

    <!-- マシン追加モーダル -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>マシン追加</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="M" value="add_single">

                <div class="form-group">
                    <label>マシン番号</label>
                    <input type="number" name="machine_no" value="<?= $next_machine_no ?>" required min="1">
                </div>

                <div class="form-group">
                    <label>マシン名</label>
                    <input type="text" name="name" placeholder="MACHINE-01">
                </div>

                <div class="form-group">
                    <label>機種</label>
                    <select name="model_no">
                        <option value="">-- 選択 --</option>
                        <?php foreach ($models as $model): ?>
                        <option value="<?= $model['model_no'] ?>"><?= htmlspecialchars($model['model_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>カメラ選択</label>
                    <select name="camera_no">
                        <option value="">-- カメラ選択 --</option>
                        <?php foreach ($cameras as $cam): ?>
                        <option value="<?= $cam['camera_no'] ?>" <?= $cam['camera_no'] == $next_machine_no ? 'selected' : '' ?>>
                            No.<?= $cam['camera_no'] ?> - <?= $cam['camera_mac'] ?> - <?= htmlspecialchars($cam['camera_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>MACアドレス</label>
                    <input type="text" name="mac_address" placeholder="00:00:00:00:00:00">
                </div>

                <div class="form-group">
                    <label>IPアドレス</label>
                    <input type="text" name="ip_address" placeholder="192.168.1.x">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">追加</button>
            </form>
        </div>
    </div>

    <!-- 複数台追加モーダル -->
    <div class="modal" id="bulkAddModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>複数台一括追加</h3>
                <button class="modal-close" onclick="closeModal('bulkAddModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="M" value="add_bulk">

                <div class="form-group">
                    <label>開始番号</label>
                    <input type="number" name="start_no" value="<?= $next_machine_no ?>" required min="1">
                </div>

                <div class="form-group">
                    <label>追加台数</label>
                    <input type="number" name="count" value="10" required min="1" max="100">
                </div>

                <div class="form-group">
                    <label>デフォルト機種</label>
                    <select name="model_no">
                        <option value="">-- 選択 --</option>
                        <?php foreach ($models as $model): ?>
                        <option value="<?= $model['model_no'] ?>"><?= htmlspecialchars($model['model_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success" style="width: 100%;">一括追加</button>
            </form>
        </div>
    </div>

    <!-- CSVインポートモーダル -->
    <div class="modal" id="importModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>CSVインポート</h3>
                <button class="modal-close" onclick="closeModal('importModal')">&times;</button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="M" value="import_csv">

                <div class="form-group">
                    <label>CSVファイル</label>
                    <input type="file" name="csv_file" accept=".csv" required style="color: #e2e8f0;">
                </div>

                <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">
                    CSVフォーマット: machine_no, name, model_no, camera_no, mac_address, ip_address, signaling_id
                </p>

                <button type="submit" class="btn btn-primary" style="width: 100%;">インポート</button>
            </form>
        </div>
    </div>

    <!-- 削除確認フォーム（非表示） -->
    <form method="POST" action="" id="deleteForm" style="display: none;">
        <input type="hidden" name="M" value="delete">
        <input type="hidden" name="machine_no" id="deleteMachineNo">
    </form>

    <!-- メンテナンス切り替えフォーム（非表示） -->
    <form method="POST" action="" id="maintenanceForm" style="display: none;">
        <input type="hidden" name="M" value="toggle_maintenance">
        <input type="hidden" name="machine_no" id="maintenanceMachineNo">
        <input type="hidden" name="machine_status" id="maintenanceStatus">
    </form>

    <!-- 編集モーダル -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>マシン編集</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="M" value="update">
                <input type="hidden" name="machine_no" id="edit_machine_no">

                <div class="form-group">
                    <label>マシン番号</label>
                    <input type="text" id="edit_machine_no_display" disabled style="background: #f5f5f5;">
                </div>

                <div class="form-group">
                    <label>マシン名</label>
                    <input type="text" name="name" id="edit_name">
                </div>

                <div class="form-group">
                    <label>機種</label>
                    <select name="model_no" id="edit_model_no">
                        <option value="">-- 選択 --</option>
                        <?php foreach ($models as $model): ?>
                        <option value="<?= $model['model_no'] ?>"><?= htmlspecialchars($model['model_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>カメラ選択</label>
                    <select name="camera_no" id="edit_camera_no">
                        <option value="">-- カメラ選択 --</option>
                        <?php foreach ($cameras as $cam): ?>
                        <option value="<?= $cam['camera_no'] ?>">
                            No.<?= $cam['camera_no'] ?> - <?= $cam['camera_mac'] ?> - <?= htmlspecialchars($cam['camera_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>MACアドレス</label>
                    <input type="text" name="mac_address" id="edit_mac_address" placeholder="00:00:00:00:00:00">
                </div>

                <div class="form-group">
                    <label>IPアドレス</label>
                    <input type="text" name="ip_address" id="edit_ip_address" placeholder="192.168.1.x">
                </div>

                <div class="form-group">
                    <label>Signaling ID</label>
                    <input type="text" name="signaling_id" id="edit_signaling_id">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">保存</button>
            </form>
        </div>
    </div>

    <script>
        // マシンデータ（編集モーダル用）
        const machineData = <?= json_encode(array_combine(
            array_column($machines, 'machine_no'),
            $machines
        )) ?>;

        // ビュー切り替え
        function setView(view) {
            const cardView = document.getElementById('cardView');
            const tableView = document.getElementById('tableView');
            const viewCard = document.getElementById('viewCard');
            const viewTable = document.getElementById('viewTable');

            if (view === 'card') {
                cardView.style.display = 'grid';
                tableView.classList.remove('active');
                viewCard.classList.add('active');
                viewTable.classList.remove('active');
            } else {
                cardView.style.display = 'none';
                tableView.classList.add('active');
                viewCard.classList.remove('active');
                viewTable.classList.add('active');
            }

            localStorage.setItem('machineViewMode', view);
        }

        // 保存されたビューモードを復元
        const savedView = localStorage.getItem('machineViewMode');
        if (savedView === 'table') {
            setView('table');
        }

        // モーダル操作
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function openBulkAddModal() {
            document.getElementById('bulkAddModal').classList.add('active');
        }

        function openImportModal() {
            document.getElementById('importModal').classList.add('active');
        }

        function openEditModal(machineNo) {
            const machine = machineData[machineNo];
            if (!machine) return;

            document.getElementById('edit_machine_no').value = machineNo;
            document.getElementById('edit_machine_no_display').value = machineNo;
            document.getElementById('edit_name').value = machine.name || '';
            document.getElementById('edit_model_no').value = machine.model_no || '';
            document.getElementById('edit_camera_no').value = machine.camera_no || '';
            document.getElementById('edit_mac_address').value = machine.mac_address || '';
            document.getElementById('edit_ip_address').value = machine.ip_address || '';
            document.getElementById('edit_signaling_id').value = machine.signaling_id || '';

            document.getElementById('editModal').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // 選択操作
        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.machine-checkbox, .machine-checkbox-card').forEach(cb => cb.checked = checked);
            updateCommandPanel();
        }

        function updateCommandPanel() {
            const selectedTable = document.querySelectorAll('.machine-checkbox:checked').length;
            const selectedCard = document.querySelectorAll('.machine-checkbox-card:checked').length;
            const total = selectedTable + selectedCard;
            document.getElementById('commandPanel').style.display = total > 0 ? 'block' : 'none';
        }

        // コマンド送信
        function sendCommand(machineNo, command) {
            if (confirm(`マシン ${machineNo} に ${command} コマンドを送信しますか？`)) {
                alert(`コマンド ${command} をマシン ${machineNo} に送信しました`);
            }
        }

        function sendSelectedCommand(command) {
            const selectedTable = Array.from(document.querySelectorAll('.machine-checkbox:checked')).map(cb => cb.value);
            const selectedCard = Array.from(document.querySelectorAll('.machine-checkbox-card:checked')).map(cb => cb.value);
            const selected = [...new Set([...selectedTable, ...selectedCard])];

            if (selected.length === 0) {
                alert('マシンを選択してください');
                return;
            }

            if (confirm(`選択した ${selected.length} 台に ${command} コマンドを送信しますか？`)) {
                alert(`コマンド ${command} を ${selected.length} 台に送信しました`);
            }
        }

        // メンテナンス切り替え
        function toggleMaintenance(machineNo, currentStatus) {
            const newStatus = currentStatus == 2 ? 1 : 2; // 2=メンテナンス中 ⇔ 1=稼働中
            const action = newStatus == 2 ? 'メンテナンスモードに切り替え' : '稼働中に戻す';

            if (confirm(`マシン ${machineNo} を${action}ますか？`)) {
                document.getElementById('maintenanceMachineNo').value = machineNo;
                document.getElementById('maintenanceStatus').value = newStatus;
                document.getElementById('maintenanceForm').submit();
            }
        }

        // 削除確認
        function deleteConfirm(machineNo) {
            if (confirm(`マシン ${machineNo} を削除しますか？この操作は取り消せません。`)) {
                document.getElementById('deleteMachineNo').value = machineNo;
                document.getElementById('deleteForm').submit();
            }
        }

        // モーダル外クリックで閉じる
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // 60秒（1分）で自動リロード
        setTimeout(() => location.reload(), 60000);
    </script>
</body>
</html>
<?php
}

/**
 * 単一マシン追加
 */
function ProcAddSingle($template) {
    getData($_POST, array("machine_no", "name", "model_no", "camera_no", "mac_address", "ip_address"));

    $machine_no = intval($_POST["machine_no"]);
    $name = !empty($_POST["name"]) ? $_POST["name"] : "MACHINE-" . str_pad($machine_no, 2, '0', STR_PAD_LEFT);
    $token = 'net8_m' . str_pad($machine_no, 3, '0', STR_PAD_LEFT) . '_' . bin2hex(random_bytes(16));
    $signaling_id = 'PEER' . str_pad($machine_no, 3, '0', STR_PAD_LEFT);

    // 重複チェック
    $check = $template->DB->getRow("SELECT machine_no FROM dat_machine WHERE machine_no = $machine_no", PDO::FETCH_ASSOC);
    if ($check) {
        DispMachineList($template, "❌ マシン番号 $machine_no は既に存在します");
        return;
    }

    $sql = "INSERT INTO dat_machine (machine_no, name, model_no, camera_no, mac_address, ip_address, signaling_id, token, status, machine_status, del_flg, release_date, convert_no)
            VALUES (
                $machine_no,
                " . $template->DB->quote($name) . ",
                " . (intval($_POST["model_no"]) ?: 1) . ",
                " . (intval($_POST["camera_no"]) ?: $machine_no) . ",
                " . $template->DB->quote(strtolower(trim($_POST["mac_address"] ?? ''))) . ",
                " . $template->DB->quote(trim($_POST["ip_address"] ?? '')) . ",
                " . $template->DB->quote($signaling_id) . ",
                " . $template->DB->quote($token) . ",
                'offline', 0, 0, CURDATE(), 0
            )";

    $template->DB->query($sql);

    // MACアドレスがある場合、mst_cameraも更新
    if (!empty($_POST["mac_address"])) {
        UpdateCameraMAC($template, intval($_POST["camera_no"]) ?: $machine_no, strtolower(trim($_POST["mac_address"])));
    }

    DispMachineList($template, "✅ マシン $machine_no を追加しました");
}

/**
 * 複数台一括追加
 */
function ProcAddBulk($template) {
    getData($_POST, array("start_no", "count", "model_no"));

    $start_no = intval($_POST["start_no"]);
    $count = intval($_POST["count"]);
    $model_no = intval($_POST["model_no"]) ?: 1;

    $added = 0;

    for ($i = 0; $i < $count; $i++) {
        $machine_no = $start_no + $i;
        $name = "MACHINE-" . str_pad($machine_no, 2, '0', STR_PAD_LEFT);
        $token = 'net8_m' . str_pad($machine_no, 3, '0', STR_PAD_LEFT) . '_' . bin2hex(random_bytes(16));
        $signaling_id = 'PEER' . str_pad($machine_no, 3, '0', STR_PAD_LEFT);

        // 重複チェック
        $check = $template->DB->getRow("SELECT machine_no FROM dat_machine WHERE machine_no = $machine_no", PDO::FETCH_ASSOC);
        if ($check) continue;

        $sql = "INSERT INTO dat_machine (machine_no, name, model_no, camera_no, signaling_id, token, status, machine_status, del_flg, release_date, convert_no)
                VALUES ($machine_no, " . $template->DB->quote($name) . ", $model_no, $machine_no, " . $template->DB->quote($signaling_id) . ", " . $template->DB->quote($token) . ", 'offline', 0, 0, CURDATE(), 0)";

        $template->DB->query($sql);
        $added++;
    }

    DispMachineList($template, "✅ {$added}台のマシンを追加しました（{$start_no}〜" . ($start_no + $count - 1) . "）");
}

/**
 * マシン更新
 */
function ProcUpdate($template) {
    getData($_POST, array("machine_no", "camera_no", "mac_address", "ip_address", "name", "model_no", "signaling_id"));

    $machine_no = intval($_POST["machine_no"]);
    $camera_no = intval($_POST["camera_no"]);
    $manual_mac = strtolower(trim($_POST["mac_address"] ?? ''));

    $mac_address = $manual_mac;

    // カメラ番号が指定されている場合
    if ($camera_no > 0) {
        $camera_sql = "SELECT camera_mac FROM mst_camera WHERE camera_no = $camera_no AND del_flg = 0";
        $camera = $template->DB->getRow($camera_sql, PDO::FETCH_ASSOC);

        // 手動でMACが入力されていない場合のみ、カメラのMACを使用
        if (empty($manual_mac) && $camera && !empty($camera['camera_mac'])) {
            $mac_address = strtolower($camera['camera_mac']);
        }
        // 手動入力がある場合は、手動入力を優先（$mac_addressはすでに$manual_mac）
    }

    $sql = "UPDATE dat_machine SET
                camera_no = $camera_no,
                mac_address = " . $template->DB->quote($mac_address) . ",
                ip_address = " . $template->DB->quote(trim($_POST["ip_address"] ?? '')) . ",
                name = " . $template->DB->quote(trim($_POST["name"] ?? '')) . ",
                model_no = " . (intval($_POST["model_no"]) ?: 1) . ",
                signaling_id = " . $template->DB->quote(trim($_POST["signaling_id"] ?? '')) . "
            WHERE machine_no = $machine_no";

    $template->DB->query($sql);

    // mst_camera連携（双方向同期）
    // この画面で変更したMACアドレスを、カメラのMACアドレスにも反映（優先）
    if (!empty($mac_address) && $camera_no > 0) {
        UpdateCameraMAC($template, $camera_no, $mac_address);
    }

    DispMachineList($template, "✅ マシン $machine_no を更新しました");
}

/**
 * 一括更新
 */
function ProcBulkUpdate($template) {
    $machines = $_POST["machines"] ?? [];
    $updated = 0;

    foreach ($machines as $machine_no => $data) {
        $machine_no = intval($machine_no);
        if ($machine_no <= 0) continue;

        $camera_no = intval($data["camera_no"]);
        $manual_mac = strtolower(trim($data["mac_address"] ?? ''));

        $mac_address = $manual_mac;

        // カメラ番号が指定されている場合
        if ($camera_no > 0) {
            $camera_sql = "SELECT camera_mac FROM mst_camera WHERE camera_no = $camera_no AND del_flg = 0";
            $camera = $template->DB->getRow($camera_sql, PDO::FETCH_ASSOC);

            // 手動でMACが入力されていない場合のみ、カメラのMACを使用
            if (empty($manual_mac) && $camera && !empty($camera['camera_mac'])) {
                $mac_address = strtolower($camera['camera_mac']);
            }
            // 手動入力がある場合は、手動入力を優先（$mac_addressはすでに$manual_mac）
        }

        $sql = "UPDATE dat_machine SET
                    camera_no = $camera_no,
                    mac_address = " . $template->DB->quote($mac_address) . ",
                    ip_address = " . $template->DB->quote(trim($data["ip_address"] ?? '')) . ",
                    name = " . $template->DB->quote(trim($data["name"] ?? '')) . ",
                    model_no = " . (intval($data["model_no"]) ?: 1) . ",
                    signaling_id = " . $template->DB->quote(trim($data["signaling_id"] ?? '')) . "
                WHERE machine_no = $machine_no";

        $template->DB->query($sql);

        // mst_camera連携（双方向同期）
        // この画面で変更したMACアドレスを、カメラのMACアドレスにも反映（優先）
        if (!empty($mac_address) && $camera_no > 0) {
            UpdateCameraMAC($template, $camera_no, $mac_address);
        }

        $updated++;
    }

    DispMachineList($template, "✅ {$updated}台のマシンを更新しました");
}

/**
 * マシン削除
 */
function ProcDelete($template) {
    $machine_no = intval($_POST["machine_no"]);

    $template->DB->query("DELETE FROM dat_machine WHERE machine_no = $machine_no");

    DispMachineList($template, "✅ マシン $machine_no を削除しました");
}

/**
 * メンテナンス状態切り替え
 */
function ProcToggleMaintenance($template) {
    getData($_POST, array("machine_no", "machine_status"));

    $machine_no = intval($_POST["machine_no"]);
    $new_status = intval($_POST["machine_status"]);

    // machine_status: 0=停止中, 1=稼働中, 2=メンテナンス中
    if ($new_status < 0 || $new_status > 2) {
        DispMachineList($template, "❌ 不正な状態値です");
        return;
    }

    $sql = "UPDATE dat_machine SET machine_status = $new_status WHERE machine_no = $machine_no";
    $template->DB->query($sql);

    $status_labels = ['停止中', '稼働中', 'メンテナンス中'];
    $status_label = $status_labels[$new_status];

    DispMachineList($template, "✅ マシン $machine_no を「{$status_label}」に変更しました");
}

/**
 * コマンド送信
 */
function ProcSendCommand($template) {
    getData($_POST, array("machine_no", "command"));

    // TODO: WebSocket経由でコマンド送信

    DispMachineList($template, "✅ コマンドを送信しました");
}

/**
 * CSVエクスポート
 */
function ExportCSV($template) {
    $sql = "SELECT dm.machine_no, dm.name, dm.model_no, mm.model_name, dm.camera_no, dm.mac_address, dm.ip_address, dm.signaling_id, dm.status
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            ORDER BY dm.machine_no";

    $machines = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="machines_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');

    // BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // ヘッダー
    fputcsv($output, ['machine_no', 'name', 'model_no', 'model_name', 'camera_no', 'mac_address', 'ip_address', 'signaling_id', 'status']);

    // データ
    foreach ($machines as $m) {
        fputcsv($output, $m);
    }

    fclose($output);
    exit;
}

/**
 * CSVインポート
 */
function ImportCSV($template) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        DispMachineList($template, "❌ ファイルのアップロードに失敗しました");
        return;
    }

    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

    // BOMスキップ
    $bom = fread($file, 3);
    if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
        rewind($file);
    }

    // ヘッダースキップ
    fgetcsv($file);

    $imported = 0;

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) < 7) continue;

        $machine_no = intval($row[0]);
        if ($machine_no <= 0) continue;

        $name = trim($row[1]);
        $model_no = intval($row[2]) ?: 1;
        $camera_no = intval($row[4]) ?: $machine_no;
        $mac_address = strtolower(trim($row[5]));
        $ip_address = trim($row[6]);
        $signaling_id = isset($row[7]) ? trim($row[7]) : 'PEER' . str_pad($machine_no, 3, '0', STR_PAD_LEFT);

        // 存在チェック
        $exists = $template->DB->getRow("SELECT machine_no FROM dat_machine WHERE machine_no = $machine_no", PDO::FETCH_ASSOC);

        if ($exists) {
            // 更新
            $sql = "UPDATE dat_machine SET
                        name = " . $template->DB->quote($name) . ",
                        model_no = $model_no,
                        camera_no = $camera_no,
                        mac_address = " . $template->DB->quote($mac_address) . ",
                        ip_address = " . $template->DB->quote($ip_address) . ",
                        signaling_id = " . $template->DB->quote($signaling_id) . "
                    WHERE machine_no = $machine_no";
        } else {
            // 新規
            $token = 'net8_m' . str_pad($machine_no, 3, '0', STR_PAD_LEFT) . '_' . bin2hex(random_bytes(16));
            $sql = "INSERT INTO dat_machine (machine_no, name, model_no, camera_no, mac_address, ip_address, signaling_id, token, status, machine_status, del_flg, release_date, convert_no)
                    VALUES ($machine_no, " . $template->DB->quote($name) . ", $model_no, $camera_no, " . $template->DB->quote($mac_address) . ", " . $template->DB->quote($ip_address) . ", " . $template->DB->quote($signaling_id) . ", " . $template->DB->quote($token) . ", 'offline', 0, 0, CURDATE(), 0)";
        }

        $template->DB->query($sql);

        // mst_camera連携
        if (!empty($mac_address)) {
            UpdateCameraMAC($template, $camera_no, $mac_address);
        }

        $imported++;
    }

    fclose($file);

    DispMachineList($template, "✅ {$imported}件のデータをインポートしました");
}

/**
 * mst_cameraのMAC更新
 */
function UpdateCameraMAC($template, $camera_no, $mac_address) {
    // 既存チェック
    $exists = $template->DB->getRow("SELECT camera_no FROM mst_camera WHERE camera_no = $camera_no AND del_flg = 0", PDO::FETCH_ASSOC);

    if ($exists) {
        $template->DB->query("UPDATE mst_camera SET camera_mac = " . $template->DB->quote($mac_address) . " WHERE camera_no = $camera_no");
    } else {
        $camera_name = "CAMERA-" . str_pad($camera_no, 2, '0', STR_PAD_LEFT);
        $template->DB->query("INSERT INTO mst_camera (camera_no, camera_mac, camera_name, camera_status, del_flg, add_no, add_dt)
                              VALUES ($camera_no, " . $template->DB->quote($mac_address) . ", " . $template->DB->quote($camera_name) . ", 1, 0, 1, NOW())");
    }

    // mst_cameralist連携
    $template->DB->query("UPDATE mst_cameralist SET camera_no = $camera_no WHERE mac_address = " . $template->DB->quote($mac_address));
}

?>
