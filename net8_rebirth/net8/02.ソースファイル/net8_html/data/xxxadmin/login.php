<?php
/**
 * NET8 管理画面 - ログイン（SmartSession統合版）
 * Updated: 2025-12-16 - セッション継続問題解決
 */

// 基本設定ファイル読み込み
require_once('../../_etc/require_files_admin.php');

// 定数設定（未定義の場合のみ）
if (!defined("URL_ADMIN")) {
    define("URL_ADMIN", "/xxxadmin/");
}

// メイン処理
main();

function main() {
    // POST処理
    $action = $_POST["M"] ?? "";

    if ($action === "proc") {
        // ログイン処理
        ProcLogin();
    } else {
        // ログイン画面表示
        DispLogin();
    }
}

function DispLogin($message = "") {
    $id = htmlspecialchars($_POST["ID"] ?? "");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面 - ログイン</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .login-container {
            background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%; max-width: 420px; padding: 48px 40px; animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo-icon { font-size: 64px; margin-bottom: 16px; }
        .logo-text {
            font-size: 28px; font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .login-title { text-align: center; color: #0f172a; font-size: 20px; font-weight: 600; margin-bottom: 32px; }
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px;
            margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 8px;
        }
        .form-group { margin-bottom: 24px; }
        .form-label {
            display: block; color: #475569; font-size: 14px; font-weight: 600; margin-bottom: 8px;
        }
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            font-size: 18px; color: #94a3b8;
        }
        .form-input {
            width: 100%; padding: 12px 16px 12px 48px; border: 2px solid #e2e8f0;
            border-radius: 8px; font-size: 15px; transition: all 0.2s; background: #f8fafc;
        }
        .form-input:focus {
            outline: none; border-color: #667eea; background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .login-button {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .login-button:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4); }
        .footer-text { text-align: center; margin-top: 24px; color: #64748b; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🎮</div>
            <div class="logo-text">NET8 Admin</div>
        </div>
        <h1 class="login-title">管理画面ログイン</h1>

        <?php if (!empty($message)): ?>
        <div class="error-message">
            <span>⚠️</span>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="M" value="proc">

            <div class="form-group">
                <label class="form-label" for="admin_id">管理者ID</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="admin_id" name="ID" class="form-input"
                           placeholder="管理者IDを入力" value="<?= $id ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="admin_pass">パスワード</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="admin_pass" name="PASS" class="form-input"
                           placeholder="パスワードを入力" required>
                </div>
            </div>

            <button type="submit" class="login-button">ログイン</button>
        </form>

        <p class="footer-text">© 2025 NET8 Management System</p>
    </div>
</body>
</html>
<?php
}

function ProcLogin() {
    $admin_id = $_POST["ID"] ?? "";
    $admin_pass = $_POST["PASS"] ?? "";

    // 入力チェック
    if (empty($admin_id) || empty($admin_pass)) {
        DispLogin("IDとパスワードを入力してください");
        return;
    }

    // DB接続
    $host = defined("DB_HOST") ? DB_HOST : "136.116.70.86";
    $user = defined("DB_USER") ? DB_USER : "net8tech001";
    $pass = defined("DB_PASS") ? DB_PASS : "Nene11091108!!";
    $name = defined("DB_NAME") ? DB_NAME : "net8_dev";

    $mysqli = new mysqli($host, $user, $pass, $name);
    if ($mysqli->connect_error) {
        DispLogin("システムエラーが発生しました");
        return;
    }

    // 管理者認証
    $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg
            FROM mst_admin
            WHERE admin_id = ? AND del_flg = 0 LIMIT 1";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // パスワード検証
        if (password_verify($admin_pass, $row["admin_pass"])) {
            // ログイン成功 - SmartSessionを使用してセッション作成
            $sessionSec = defined('SESSION_SEC_ADMIN') ? SESSION_SEC_ADMIN : 3600;
            $sessionSid = defined('SESSION_SID_ADMIN') ? SESSION_SID_ADMIN : 'NET8ADMIN';
            $domain = defined('DOMAIN') ? DOMAIN : $_SERVER["SERVER_NAME"];
            $loginUrl = URL_ADMIN . 'login.php';

            // SmartSessionインスタンス作成
            $session = new SmartSession(
                $loginUrl,
                $sessionSec,
                $sessionSid,
                $domain,
                true
            );

            // 新しいセッションを強制発行
            $session->start(true);

            // 管理者情報をセッションに保存（SmartSessionのマジックメソッド経由）
            $session->AdminInfo = $row;
            $session->login_time = time();
            $session->last_access = time();
            $session->authenticated = true;

            // 最終ログイン更新
            $updateSql = "UPDATE mst_admin SET login_dt = NOW() WHERE admin_no = ?";
            $updateStmt = $mysqli->prepare($updateSql);
            $updateStmt->bind_param("i", $row["admin_no"]);
            $updateStmt->execute();
            $updateStmt->close();

            $stmt->close();
            $mysqli->close();

            // ダッシュボードへリダイレクト
            header("Location: " . URL_ADMIN . "index.php");
            exit();
        }
    }

    $stmt->close();
    $mysqli->close();

    DispLogin("ログインIDまたはパスワードが正しくありません");
}
?>
