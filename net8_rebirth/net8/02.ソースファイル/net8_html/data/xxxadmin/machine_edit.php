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

    // マシン情報取得（カメラ情報も含む）
    $sql = (new SqlString())
            ->setAutoConvert( [$template->DB,"conv_sql"] )
            ->select()
                ->field("dm.*")
                ->field("mc.camera_mac")
                ->field("mcl.license_id")
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

    // License IDを自動生成
    $license_id = getLicenseID($mac_address);

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

            // ❸ mst_cameralist を INSERT または UPDATE
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
                // UPDATE
                $sql = (new SqlString())
                        ->setAutoConvert( [$template->DB,"conv_sql"] )
                        ->update("mst_cameralist")
                            ->set()
                                ->value("license_id", $license_id, FD_STR)
                                ->value("camera_no", $camera_no, FD_NUM)
                                ->value("del_flg", 0, FD_NUM)
                                ->value("upd_dt", "current_timestamp", FD_FUNCTION)
                            ->where()
                                ->and("mac_address = ", $mac_address, FD_STR)
                        ->createSQL();
            } else {
                // INSERT
                $sql = (new SqlString())
                        ->setAutoConvert( [$template->DB,"conv_sql"] )
                        ->insert()
                            ->into("mst_cameralist")
                                ->value("mac_address", $mac_address, FD_STR)
                                ->value("license_id", $license_id, FD_STR)
                                ->value("camera_no", $camera_no, FD_NUM)
                                ->value("del_flg", 0, FD_NUM)
                                ->value("add_dt", "current_timestamp", FD_FUNCTION)
                        ->createSQL();
            }

            $template->DB->query($sql);
        }

        // コミット
        $template->DB->autoCommit(true);

        // 編集画面に戻る（成功メッセージ付き）
        $_GET['machine_no'] = $_POST['machine_no'];
        $message = "✅ マシン情報を更新しました（3テーブル同期完了）<br>";
        $message .= "📝 License ID: " . substr($license_id, 0, 30) . "...<br>";
        $message .= "🔐 Win側の認証に反映されます。slotserver.iniのIDと一致します。";
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
