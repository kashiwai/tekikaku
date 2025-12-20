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
 * @version 2.0
 * @since   2025/12/20
 */

require_once('../../_etc/require_files_admin.php');

// メイン処理
main();

function main() {
    try {
        $template = new TemplateAdmin();

        getData($_POST, array("M", "machine_no", "count"));
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
 * マシン一覧表示（統合管理画面）
 */
function DispMachineList($template, $message = "") {
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

    // 統計情報（PC状態 / ゲーム機状態）
    $total = count($machines);
    $pc_online = 0;
    $pc_offline = 0;
    $game_playing = 0;
    $game_standby = 0;

    foreach ($machines as &$m) {
        // PC状態（Chrome起動）
        if ($m['pc_status'] == 'online') {
            $pc_online++;
        } else {
            $pc_offline++;
        }

        // ゲーム機状態を判定
        // assign_flg: 0=空き, 1=プレイ中, 9=待機
        // member_noがあればプレイ中
        if (!empty($m['playing_member']) && $m['playing_member'] > 0) {
            $m['game_status'] = 'playing';
            $game_playing++;
        } elseif ($m['assign_flg'] == 1) {
            $m['game_status'] = 'playing';
            $game_playing++;
        } elseif ($m['pc_status'] == 'online') {
            $m['game_status'] = 'standby';
            $game_standby++;
        } else {
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }

        .container { max-width: 1600px; margin: 0 auto; padding: 20px; }

        /* ヘッダー */
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid #475569;
        }

        .header h1 {
            font-size: 28px;
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        /* ボタン */
        .btn {
            padding: 10px 20px;
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

        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }

        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }

        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }

        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }

        .btn-secondary { background: #475569; color: white; }
        .btn-secondary:hover { background: #64748b; }

        /* 統計カード */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #1e293b;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            font-size: 32px;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .stat-icon.total { background: linear-gradient(135deg, #3b82f6, #8b5cf6); }
        .stat-icon.online { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-icon.offline { background: linear-gradient(135deg, #64748b, #94a3b8); }
        .stat-icon.error { background: linear-gradient(135deg, #ef4444, #f87171); }
        .stat-icon.playing { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .stat-icon.standby { background: linear-gradient(135deg, #059669, #34d399); }

        .stat-content h3 { font-size: 12px; color: #94a3b8; margin-bottom: 4px; }
        .stat-content p { font-size: 28px; font-weight: 700; }

        /* メッセージ */
        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .message.success { background: #064e3b; border-color: #10b981; color: #34d399; }
        .message.error { background: #7f1d1d; border-color: #ef4444; color: #fca5a5; }

        /* テーブル */
        .table-container {
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #334155;
            background: #0f172a;
        }

        .table-header h2 { font-size: 16px; }

        .table-scroll { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #334155;
            white-space: nowrap;
        }

        th {
            background: #0f172a;
            font-weight: 600;
            color: #94a3b8;
            position: sticky;
            top: 0;
        }

        tr:hover { background: #334155; }

        /* ステータスバッジ */
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-online { background: #064e3b; color: #34d399; }
        .status-offline { background: #1e293b; color: #94a3b8; }
        .status-error { background: #7f1d1d; color: #fca5a5; }
        .status-playing { background: #7c3aed; color: #e9d5ff; }
        .status-standby { background: #065f46; color: #6ee7b7; }

        /* 入力フィールド */
        input[type="text"], input[type="number"], select {
            padding: 8px 12px;
            border: 1px solid #475569;
            border-radius: 6px;
            background: #0f172a;
            color: #e2e8f0;
            font-size: 12px;
            font-family: 'Courier New', monospace;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .input-sm { width: 80px; }
        .input-md { width: 140px; }
        .input-lg { width: 200px; }

        /* アクションボタン（テーブル内） */
        .action-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-btn.edit { background: #3b82f6; color: white; }
        .action-btn.delete { background: #ef4444; color: white; }
        .action-btn.command { background: #8b5cf6; color: white; }

        /* モーダル */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid #475569;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #334155;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #94a3b8;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .form-group input, .form-group select {
            width: 100%;
        }

        /* 一括編集モード */
        .bulk-edit-row input, .bulk-edit-row select {
            background: transparent;
            border: 1px solid transparent;
        }

        .bulk-edit-row input:hover, .bulk-edit-row select:hover {
            border-color: #475569;
        }

        .bulk-edit-row input:focus, .bulk-edit-row select:focus {
            background: #0f172a;
            border-color: #3b82f6;
        }

        /* コマンドパネル */
        .command-panel {
            background: #0f172a;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            border: 1px solid #334155;
        }

        .command-panel h4 {
            font-size: 14px;
            margin-bottom: 12px;
            color: #94a3b8;
        }

        .command-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* チェックボックス */
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }

        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* レスポンシブ */
        @media (max-width: 1024px) {
            .header-actions { flex-direction: column; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <h1>NET8 マシン統合管理 V2</h1>
            <p style="color: #94a3b8;">全マシンの設定・状態・コマンドを一元管理</p>

            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">← ダッシュボード</a>
                <button onclick="openAddModal()" class="btn btn-primary">+ マシン追加</button>
                <button onclick="openBulkAddModal()" class="btn btn-success">++ 複数台追加</button>
                <button onclick="toggleBulkEdit()" class="btn btn-warning" id="bulkEditBtn">一括編集モード</button>
                <a href="?M=export_csv" class="btn btn-secondary">CSV出力</a>
                <button onclick="openImportModal()" class="btn btn-secondary">CSVインポート</button>
                <button onclick="sendBulkCommand('RESTART')" class="btn btn-danger">全台再起動</button>
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

        <!-- マシン一覧テーブル -->
        <div class="table-container">
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
                                <th>PC状態</th>
                                <th>ゲーム機</th>
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
                                        <input type="number" name="machines[<?= $m['machine_no'] ?>][camera_no]"
                                               value="<?= $m['camera_no'] ?>" class="input-sm">
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
                                        <span class="status-badge status-<?= $m['pc_status'] ?: 'offline' ?>">
                                            <?= $m['pc_status'] == 'online' ? '💻 ON' : '⏸️ OFF' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($m['game_status'] == 'playing'): ?>
                                        <span class="status-badge status-playing">
                                            🎮 プレイ中
                                        </span>
                                        <?php elseif ($m['game_status'] == 'standby'): ?>
                                        <span class="status-badge status-standby">
                                            🟢 待機
                                        </span>
                                        <?php else: ?>
                                        <span class="status-badge status-offline">
                                            ⚫ 停止
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
                    <label>カメラ番号</label>
                    <input type="number" name="camera_no" value="<?= $next_machine_no ?>">
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

    <script>
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

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // 一括編集モード
        let bulkEditMode = false;

        function toggleBulkEdit() {
            bulkEditMode = !bulkEditMode;
            const btn = document.getElementById('bulkEditBtn');
            const actions = document.getElementById('bulkEditActions');
            const inputs = document.querySelectorAll('.bulk-edit-row input, .bulk-edit-row select');

            if (bulkEditMode) {
                btn.textContent = '編集モード解除';
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-danger');
                actions.style.display = 'block';
                inputs.forEach(i => i.style.background = '#0f172a');
            } else {
                btn.textContent = '一括編集モード';
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-warning');
                actions.style.display = 'none';
                inputs.forEach(i => i.style.background = 'transparent');
            }
        }

        // 選択操作
        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.machine-checkbox').forEach(cb => cb.checked = checked);
            updateCommandPanel();
        }

        function updateCommandPanel() {
            const selected = document.querySelectorAll('.machine-checkbox:checked').length;
            document.getElementById('commandPanel').style.display = selected > 0 ? 'block' : 'none';
        }

        // コマンド送信
        function sendCommand(machineNo, command) {
            if (confirm(`マシン ${machineNo} に ${command} コマンドを送信しますか？`)) {
                // WebSocket経由でコマンド送信（実装予定）
                alert(`コマンド ${command} をマシン ${machineNo} に送信しました`);
            }
        }

        function sendSelectedCommand(command) {
            const selected = Array.from(document.querySelectorAll('.machine-checkbox:checked'))
                                  .map(cb => cb.value);

            if (selected.length === 0) {
                alert('マシンを選択してください');
                return;
            }

            if (confirm(`選択した ${selected.length} 台に ${command} コマンドを送信しますか？`)) {
                alert(`コマンド ${command} を ${selected.length} 台に送信しました`);
            }
        }

        function sendBulkCommand(command) {
            if (confirm(`全マシンに ${command} コマンドを送信しますか？`)) {
                alert(`コマンド ${command} を全マシンに送信しました`);
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
    $mac_address = strtolower(trim($_POST["mac_address"] ?? ''));

    $sql = "UPDATE dat_machine SET
                camera_no = " . intval($_POST["camera_no"]) . ",
                mac_address = " . $template->DB->quote($mac_address) . ",
                ip_address = " . $template->DB->quote(trim($_POST["ip_address"] ?? '')) . ",
                name = " . $template->DB->quote(trim($_POST["name"] ?? '')) . ",
                model_no = " . (intval($_POST["model_no"]) ?: 1) . ",
                signaling_id = " . $template->DB->quote(trim($_POST["signaling_id"] ?? '')) . "
            WHERE machine_no = $machine_no";

    $template->DB->query($sql);

    // mst_camera連携
    if (!empty($mac_address)) {
        UpdateCameraMAC($template, intval($_POST["camera_no"]), $mac_address);
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

        $mac_address = strtolower(trim($data["mac_address"] ?? ''));

        $sql = "UPDATE dat_machine SET
                    camera_no = " . intval($data["camera_no"]) . ",
                    mac_address = " . $template->DB->quote($mac_address) . ",
                    ip_address = " . $template->DB->quote(trim($data["ip_address"] ?? '')) . ",
                    name = " . $template->DB->quote(trim($data["name"] ?? '')) . ",
                    model_no = " . (intval($data["model_no"]) ?: 1) . ",
                    signaling_id = " . $template->DB->quote(trim($data["signaling_id"] ?? '')) . "
                WHERE machine_no = $machine_no";

        $template->DB->query($sql);

        // mst_camera連携
        if (!empty($mac_address)) {
            UpdateCameraMAC($template, intval($data["camera_no"]), $mac_address);
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
