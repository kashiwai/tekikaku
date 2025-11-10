<?php
/**
 * NET8 AI Control System - Machine Edit
 * マシン情報編集画面
 *
 * @package NET8
 * @author  AI Control System
 * @version 1.0
 * @since   2025/11/05
 */

require_once('../../_etc/require_files_admin.php');
require_once('../../_sys/APItool.php');  // APItool追加

// メイン処理
main();

function main() {
    try {
        // 管理系表示コントロールのインスタンス生成
        $template = new TemplateAdmin();

        // データ取得
        getData($_GET, array("machine_no"));
        getData($_POST, array("M"));

        // 実処理
        switch ($_POST["M"]) {
            case "update":      // 更新処理
                ProcUpdate($template);
                break;

            default:            // 編集画面表示
                DispEdit($template);
        }

    } catch (Exception $e) {
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>";
        echo "<h1>エラーが発生しました</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</body></html>";
    }
}

/**
 * 編集画面表示
 */
function DispEdit($template, $message = "") {
    $machine_no = isset($_GET['machine_no']) ? intval($_GET['machine_no']) : 0;

    if ($machine_no == 0) {
        header("Location: machine_control.php");
        exit;
    }

    // マシン情報取得（カメラ情報 + ライセンス情報も含む）
    $sql = (new SqlString())
            ->setAutoConvert( [$template->DB,"conv_sql"] )
            ->select()
                ->field("dm.*")
                ->field("mc.camera_mac")
                ->field("mc.camera_name")
                ->field("mcl.license_id")
                ->field("mcl.identifing_number")
                ->field("mcl.system_name")
                ->field("mcl.product_name")
                ->field("mcl.cpu_name")
                ->field("mcl.core")
                ->field("mcl.uuid")
                ->field("mcl.state")
                ->field("mcl.ip_address AS cameralist_ip")
                ->field("mcl.add_dt")
                ->field("mcl.upd_dt")
                ->from("dat_machine dm")
                ->join("left", "mst_camera mc", "dm.camera_no = mc.camera_no")
                ->join("left", "mst_cameralist mcl", "mc.camera_mac = mcl.mac_address")
                ->where()
                    ->and("dm.machine_no = ", $machine_no, FD_NUM)
            ->createSQL();

    $machine = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

    if (!$machine) {
        echo "マシンが見つかりません";
        exit;
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マシン編集 - NET8 AI Control</title>
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
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 32px;
        }

        .header {
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
        }

        .message {
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            color: #166534;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 4px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            background: #f8fafc;
            font-family: 'Courier New', monospace;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-input:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .form-help {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
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
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .info-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .info-section h3 {
            font-size: 16px;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .info-grid {
            display: grid;
            gap: 12px;
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
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            font-size: 13px;
            color: #0f172a;
            font-family: 'Courier New', monospace;
        }

        /* ライセンス情報セクション */
        .license-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .license-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .license-title {
            font-size: 18px;
            font-weight: 700;
            color: #0c4a6e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #16a34a;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .license-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .license-warning strong {
            display: block;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .license-warning code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .license-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .license-item {
            background: white;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #e0f2fe;
        }

        .license-item-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .license-item-value {
            font-size: 14px;
            color: #0f172a;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }

        .license-item-value.empty {
            color: #94a3b8;
            font-style: italic;
            font-family: inherit;
        }

        .state-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .state-online {
            background: #dcfce7;
            color: #166534;
        }

        .state-offline {
            background: #fee2e2;
            color: #991b1b;
        }

        .state-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .state-dot.online {
            background: #16a34a;
        }

        .state-dot.offline {
            background: #dc2626;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>🎮 マシン情報編集</h1>
                <p>MACHINE-<?= str_pad($machine['machine_no'], 2, '0', STR_PAD_LEFT) ?></p>
            </div>

            <?php if (!empty($message)): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <!-- 基本情報（読み取り専用） -->
            <div class="info-section">
                <h3>📋 基本情報</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">マシン番号</span>
                        <span class="info-value"><?= $machine['machine_no'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">カメラ番号</span>
                        <span class="info-value"><?= $machine['camera_no'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Signaling ID</span>
                        <span class="info-value"><?= htmlspecialchars($machine['signaling_id']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">認証トークン</span>
                        <span class="info-value"><?= substr($machine['token'], 0, 30) ?>...</span>
                    </div>
                </div>
            </div>

            <!-- ライセンス情報セクション -->
            <div class="license-section">
                <div class="license-header">
                    <div class="license-title">
                        🔐 Windows PC ライセンス情報
                    </div>
                    <?php if (!empty($machine['license_id'])): ?>
                        <span class="badge badge-success">✓ 登録済み</span>
                    <?php else: ?>
                        <span class="badge badge-warning">⚠ 未登録</span>
                    <?php endif; ?>
                </div>

                <?php if (empty($machine['license_id'])): ?>
                    <!-- 未登録の場合の警告 -->
                    <div class="license-warning">
                        <strong>⚠️ NET8License.pyが未実行です</strong>
                        Windows PC側で以下のコマンドを実行してください:<br>
                        <code>python NET8License.py</code><br>
                        <small>実行すると、ハードウェア情報とライセンスIDが自動登録されます。</small>
                    </div>
                <?php else: ?>
                    <!-- ライセンス情報グリッド -->
                    <div class="license-grid">
                        <!-- License ID -->
                        <div class="license-item" style="grid-column: span 2;">
                            <div class="license-item-label">🔑 License ID</div>
                            <div class="license-item-value">
                                <?= htmlspecialchars($machine['license_id']) ?>
                            </div>
                        </div>

                        <!-- MACアドレス -->
                        <div class="license-item">
                            <div class="license-item-label">📡 MAC Address</div>
                            <div class="license-item-value">
                                <?= htmlspecialchars($machine['camera_mac'] ?: '未設定') ?>
                            </div>
                        </div>

                        <!-- 状態 -->
                        <div class="license-item">
                            <div class="license-item-label">📊 接続状態</div>
                            <div class="license-item-value">
                                <?php if ($machine['state'] == 1): ?>
                                    <span class="state-indicator state-online">
                                        <span class="state-dot online"></span>
                                        オンライン
                                    </span>
                                <?php else: ?>
                                    <span class="state-indicator state-offline">
                                        <span class="state-dot offline"></span>
                                        オフライン
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- IdentifyingNumber -->
                        <div class="license-item">
                            <div class="license-item-label">🆔 Serial Number</div>
                            <div class="license-item-value <?= empty($machine['identifing_number']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['identifing_number'] ?: '未取得') ?>
                            </div>
                        </div>

                        <!-- System Name -->
                        <div class="license-item">
                            <div class="license-item-label">💻 System Name</div>
                            <div class="license-item-value <?= empty($machine['system_name']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['system_name'] ?: '未取得') ?>
                            </div>
                        </div>

                        <!-- Product Name -->
                        <div class="license-item">
                            <div class="license-item-label">🖥️ Product Name</div>
                            <div class="license-item-value <?= empty($machine['product_name']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['product_name'] ?: '未取得') ?>
                            </div>
                        </div>

                        <!-- IP Address -->
                        <div class="license-item">
                            <div class="license-item-label">🌐 IP Address</div>
                            <div class="license-item-value <?= empty($machine['cameralist_ip']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['cameralist_ip'] ?: '未取得') ?>
                            </div>
                        </div>

                        <!-- CPU -->
                        <div class="license-item">
                            <div class="license-item-label">⚙️ CPU</div>
                            <div class="license-item-value <?= empty($machine['cpu_name']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['cpu_name'] ?: '未取得') ?>
                                <?php if (!empty($machine['core'])): ?>
                                    (<?= $machine['core'] ?> cores)
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- UUID -->
                        <div class="license-item">
                            <div class="license-item-label">🔢 UUID</div>
                            <div class="license-item-value <?= empty($machine['uuid']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['uuid'] ?: '未取得') ?>
                            </div>
                        </div>

                        <!-- 登録日時 -->
                        <div class="license-item">
                            <div class="license-item-label">📅 登録日時</div>
                            <div class="license-item-value <?= empty($machine['add_dt']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['add_dt'] ?: '未取得') ?>
                            </div>
                        </div>

                        <!-- 最終更新 -->
                        <div class="license-item">
                            <div class="license-item-label">🔄 最終更新</div>
                            <div class="license-item-value <?= empty($machine['upd_dt']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($machine['upd_dt'] ?: '未更新') ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 編集フォーム -->
            <form method="POST" action="machine_edit.php">
                <input type="hidden" name="M" value="update">
                <input type="hidden" name="machine_no" value="<?= $machine['machine_no'] ?>">

                <div class="form-group">
                    <label class="form-label" for="name">
                        マシン名 <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-input"
                        value="<?= htmlspecialchars($machine['name'] ?: 'MACHINE-' . str_pad($machine['machine_no'], 2, '0', STR_PAD_LEFT)) ?>"
                        required
                    >
                    <div class="form-help">例: MACHINE-01, PC-HOKUTO-01</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">
                        ステータス
                    </label>
                    <select id="status" name="status" class="form-select">
                        <option value="online" <?= $machine['status'] == 'online' ? 'selected' : '' ?>>オンライン</option>
                        <option value="offline" <?= $machine['status'] == 'offline' ? 'selected' : '' ?>>オフライン</option>
                        <option value="error" <?= $machine['status'] == 'error' ? 'selected' : '' ?>>エラー</option>
                    </select>
                    <div class="form-help">通常は自動更新されます</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ip_address">
                        IPアドレス
                    </label>
                    <input
                        type="text"
                        id="ip_address"
                        name="ip_address"
                        class="form-input"
                        value="<?= htmlspecialchars($machine['ip_address'] ?: '') ?>"
                        placeholder="192.168.1.2"
                    >
                    <div class="form-help">Windows PCの固定IPアドレス</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mac_address">
                        MACアドレス <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="mac_address"
                        name="mac_address"
                        class="form-input"
                        value="<?= htmlspecialchars($machine['camera_mac'] ?: $machine['mac_address'] ?: '') ?>"
                        placeholder="00:00:00:00:00:01 または 00-00-00-00-00-01"
                        pattern="^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$"
                        required
                    >
                    <div class="form-help">
                        Win側の認証に使用されます（コロン区切り or ハイフン区切り）<br>
                        例: 00:11:22:33:44:55 または 00-11-22-33-44-55
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="license_id">
                        License ID（自動生成）
                    </label>
                    <input
                        type="text"
                        id="license_id"
                        name="license_id"
                        class="form-input"
                        value="<?= htmlspecialchars($machine['license_id'] ?: '（MACアドレス保存時に自動生成されます）') ?>"
                        readonly
                        style="background: #f1f5f9; cursor: not-allowed;"
                    >
                    <div class="form-help">
                        MACアドレスから自動生成されます（手動入力不要）<br>
                        Win側の認証に必要です
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="chrome_rd_session_id">
                        Chrome Remote Desktop ID
                    </label>
                    <input
                        type="text"
                        id="chrome_rd_session_id"
                        name="chrome_rd_session_id"
                        class="form-input"
                        value="<?= htmlspecialchars($machine['chrome_rd_session_id'] ?: '') ?>"
                        placeholder="abc123def456..."
                    >
                    <div class="form-help">リモートアクセス用のセッションID</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 保存
                    </button>
                    <a href="machine_control.php" class="btn btn-secondary">
                        キャンセル
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
}

/**
 * ライセンスIDの生成（cameraListAPI.phpと同じロジック）
 */
function getLicenseID($mac_address) {
    $api = new APItool();
    $encdata = $api->pyEncrypt($mac_address, LICENSE_CODE);
    return $encdata;
}

/**
 * 更新処理
 */
function ProcUpdate($template) {
    getData($_POST, array("machine_no", "name", "status", "ip_address", "mac_address", "chrome_rd_session_id"));

    // MACアドレスの正規化（コロン→ハイフン、小文字化）
    $mac_address = strtolower(str_replace(':', '-', trim($_POST["mac_address"])));

    // MACアドレスのバリデーション
    if (!preg_match('/^([0-9a-f]{2}-){5}[0-9a-f]{2}$/', $mac_address)) {
        $_GET['machine_no'] = $_POST['machine_no'];
        $message = "❌ MACアドレスのフォーマットが不正です。正しい形式: 00:11:22:33:44:55";
        DispEdit($template, $message);
        return;
    }

    try {
        // トランザクション開始
        $template->DB->autoCommit(false);

        // ❶ dat_machine を更新
        $sql = (new SqlString())
                ->setAutoConvert( [$template->DB,"conv_sql"] )
                ->update("dat_machine")
                    ->set()
                        ->value("name", $_POST["name"], FD_STR)
                        ->value("status", $_POST["status"], FD_STR)
                        ->value("ip_address", $_POST["ip_address"], FD_STR)
                        ->value("mac_address", $mac_address, FD_STR)
                        ->value("chrome_rd_session_id", $_POST["chrome_rd_session_id"], FD_STR)
                    ->where()
                        ->and("machine_no = ", $_POST["machine_no"], FD_NUM)
                ->createSQL();

        $template->DB->query($sql);

        // camera_no を取得
        $sql = (new SqlString())
                ->setAutoConvert( [$template->DB,"conv_sql"] )
                ->select()
                    ->field("camera_no")
                    ->from("dat_machine")
                    ->where()
                        ->and("machine_no = ", $_POST["machine_no"], FD_NUM)
                ->createSQL();

        $machine_row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
        $camera_no = $machine_row['camera_no'];

        if ($camera_no) {
            // ❷ mst_camera の camera_mac を更新
            $sql = (new SqlString())
                    ->setAutoConvert( [$template->DB,"conv_sql"] )
                    ->update("mst_camera")
                        ->set()
                            ->value("camera_mac", $mac_address, FD_STR)
                        ->where()
                            ->and("camera_no = ", $camera_no, FD_NUM)
                    ->createSQL();

            $template->DB->query($sql);

            // ❸ mst_cameralist を UPDATE（既存レコードがある場合のみ）
            // 既存レコードをチェック
            $sql = (new SqlString())
                    ->setAutoConvert( [$template->DB,"conv_sql"] )
                    ->select()
                        ->field("COUNT(*) as cnt")
                        ->from("mst_cameralist")
                        ->where()
                            ->and("mac_address = ", $mac_address, FD_STR)
                    ->createSQL();

            $count_row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

            if ($count_row['cnt'] > 0) {
                // 既存レコードがある場合はcamera_noのみ更新（License IDは再生成しない）
                $sql = (new SqlString())
                        ->setAutoConvert( [$template->DB,"conv_sql"] )
                        ->update("mst_cameralist")
                            ->set()
                                ->value("camera_no", $camera_no, FD_NUM)
                                ->value("del_flg", 0, FD_NUM)
                                ->value("upd_dt", "current_timestamp", FD_FUNCTION)
                            ->where()
                                ->and("mac_address = ", $mac_address, FD_STR)
                        ->createSQL();

                $template->DB->query($sql);
                $license_id_message = "✅ 既存のLicense IDを保持（再生成しない）";
            } else {
                // 新規の場合はINSERTしない（NET8License.pyに任せる）
                $license_id_message = "⚠️ 新しいMACアドレスです。NET8License.pyを実行してLicense IDを生成してください。";
            }
        }

        // コミット
        $template->DB->autoCommit(true);

        // 編集画面に戻る（成功メッセージ付き）
        $_GET['machine_no'] = $_POST['machine_no'];
        $message = "✅ マシン情報を更新しました<br>";
        if (isset($license_id_message)) {
            $message .= $license_id_message . "<br>";
        }
        $message .= "🔐 MACアドレスが更新されました。";
        DispEdit($template, $message);

    } catch (Exception $e) {
        // ロールバック
        $template->DB->autoCommit(true);

        $_GET['machine_no'] = $_POST['machine_no'];
        $message = "❌ 更新に失敗しました: " . htmlspecialchars($e->getMessage());
        DispEdit($template, $message);
    }
}

?>
