<?php
/**
 * 認証なし直接ゲームページ（ローカルテスト用）
 * URL: http://localhost:8888/data/game_direct.php?NO=1
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// セッション開始（出力前に必須）
session_start();

// マシン番号取得
$machine_no = isset($_GET['NO']) ? intval($_GET['NO']) : 1;
$action = isset($_GET['action']) ? $_GET['action'] : 'setup';

// Railway GCP Cloud SQL接続設定（setting.phpと同じ認証情報）
$db_host = '136.116.70.86';
$db_port = '3306';
$db_name = 'net8_dev';
$db_user = 'net8tech001';
$db_pass = 'CaD?7&Bi+_:`QKb*';

// データベース接続
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $db = new PDO($dsn, $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ユーザーデータ取得（テスト用: member_no=1）
try {
    $stmt = $db->prepare("SELECT * FROM mst_member WHERE member_no = 1 AND state = 1 LIMIT 1");
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        die("❌ Test user not found (member_no=1). Please create a test user first.");
    }
} catch (PDOException $e) {
    die("❌ Failed to get user data: " . $e->getMessage());
}

// マシンデータ取得
try {
    $stmt = $db->prepare("
        SELECT m.*, mo.model_name, mo.category, c.camera_mac
        FROM dat_machine m
        LEFT JOIN mst_model mo ON m.model_no = mo.model_no
        LEFT JOIN mst_camera c ON m.camera_no = c.camera_no
        WHERE m.machine_no = ?
    ");
    $stmt->execute([$machine_no]);
    $machineData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machineData) {
        die("❌ Machine not found (machine_no=$machine_no)");
    }
} catch (PDOException $e) {
    die("❌ Failed to get machine data: " . $e->getMessage());
}

// セッション設定（ゲームアクセスに必要）
if ($action === 'setup' || $action === 'go') {
    $_SESSION['UserInfo'] = [
        'member_no' => $userData['member_no'],
        'nickname' => $userData['nickname'],
        'mail' => $userData['mail'],
        'point' => $userData['point'],
        'state' => $userData['state']
    ];
    $_SESSION['lastplaytime'] = time() - 10;
}

// ゲームページへリダイレクト
if ($action === 'go') {
    header("Location: /data/play_v2/?NO=$machine_no");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 ローカルゲームテスト - Machine #<?php echo $machine_no; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #667eea;
        }
        .info-card h3 {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .info-card p { font-size: 18px; font-weight: 600; color: #212529; }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .warning-box p { color: #856404; font-size: 14px; line-height: 1.6; }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 NET8 ローカルゲームテスト</h1>
            <p>認証なし直接プレイ（開発・テスト専用）</p>
        </div>

        <div class="content">
            <div class="info-grid">
                <div class="info-card">
                    <h3>👤 User</h3>
                    <p><?php echo htmlspecialchars($userData['nickname']); ?></p>
                    <small style="color: #6c757d; font-size: 12px;">
                        <?php echo htmlspecialchars($userData['mail']); ?>
                    </small>
                </div>

                <div class="info-card">
                    <h3>💰 Points</h3>
                    <p><?php echo number_format($userData['point']); ?> pt</p>
                </div>

                <div class="info-card">
                    <h3>🎰 Machine</h3>
                    <p>#<?php echo $machineData['machine_no']; ?></p>
                    <small style="color: #6c757d; font-size: 12px;">
                        <?php echo htmlspecialchars($machineData['model_name']); ?>
                    </small>
                </div>

                <div class="info-card">
                    <h3>📹 Status</h3>
                    <p>
                        <span class="status-badge <?php echo $machineData['machine_status'] == 1 ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $machineData['machine_status'] == 1 ? 'Active' : 'Inactive'; ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="warning-box">
                <p>
                    ⚠️ <strong>注意:</strong> これはローカル開発・テスト用ページです。<br>
                    カメラ接続にはWindows PCが起動している必要があります。<br>
                    ゲームUIは表示されますが、カメラクライアントが起動していないとWebRTC映像は表示されません。
                </p>
            </div>

            <div class="btn-container">
                <a href="?action=go&NO=<?php echo $machine_no; ?>" class="btn">
                    🚀 ゲーム起動
                </a>
            </div>
        </div>
    </div>
</body>
</html>
