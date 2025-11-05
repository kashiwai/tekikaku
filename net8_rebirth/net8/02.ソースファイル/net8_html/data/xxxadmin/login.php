<?php
/**
 * NET8 管理画面 - ログイン（モダンデザイン）
 * 認証処理とログイン画面表示
 */

require_once('../../_etc/require_files_admin.php');

// メイン処理
main();

function main() {
    try {
        // ユーザ系表示コントロールのインスタンス生成
        $template = new TemplateAdmin(false);

        // データ取得
        getData($_POST, array("M"));

        // 実処理
        switch ($_POST["M"]) {
            case "proc":			// ログイン認証処理
                ProcLogin($template);
                break;

            default:				// ログイン画面
                DispLogin($template);
        }

    } catch (Exception $e) {
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>";
        echo "<h1>エラーが発生しました</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</body></html>";
    }
}

/**
 * ログイン画面表示（モダンデザイン）
 */
function DispLogin($template, $message = "") {
    // データ取得
    getData($_POST, array("ID", "PASS"));
    $id = isset($_POST["ID"]) ? htmlspecialchars($_POST["ID"]) : "";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面 - ログイン</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 48px 40px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-title {
            text-align: center;
            color: #0f172a;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 32px;
        }

        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #94a3b8;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: #64748b;
            font-size: 13px;
        }

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 12px;
        }

        .feature-icon {
            font-size: 16px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }
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
                    <input
                        type="text"
                        id="admin_id"
                        name="ID"
                        class="form-input"
                        placeholder="管理者IDを入力"
                        value="<?= $id ?>"
                        required
                        autocomplete="username"
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="admin_pass">パスワード</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input
                        type="password"
                        id="admin_pass"
                        name="PASS"
                        class="form-input"
                        placeholder="パスワードを入力"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button type="submit" class="login-button">
                ログイン
            </button>
        </form>

        <div class="features">
            <div class="feature-item">
                <span class="feature-icon">🛡️</span>
                <span>安全な接続</span>
            </div>
            <div class="feature-item">
                <span class="feature-icon">⚡</span>
                <span>高速アクセス</span>
            </div>
            <div class="feature-item">
                <span class="feature-icon">📊</span>
                <span>リアルタイム統計</span>
            </div>
            <div class="feature-item">
                <span class="feature-icon">🎨</span>
                <span>モダンUI</span>
            </div>
        </div>

        <p class="footer-text">
            © 2025 NET8 Management System v2.0
        </p>
    </div>
</body>
</html>
<?php
}

/**
 * ログイン認証処理
 */
function ProcLogin($template) {
    // データ取得
    getData($_POST, array("ID", "PASS"));

    // 必須入力チェック
    $errMessage = (new SmartAutoCheck($template))
            // ID
            ->item($_POST["ID"])
                ->required("A0101")
                ->alnum("A0104", 3)
                ->break()
            // パスワード
            ->item($_POST["PASS"])
                ->required("A0102")
        ->report(false);
    //エラーがある場合はLoginに戻す
    if (mb_strlen($errMessage) != 0 ){
        DispLogin($template, $errMessage);
        return;
    }

    // DB認証チェック
    $sql = (new SqlString())
            ->setAutoConvert( [$template->DB,"conv_sql"] )
            ->select()
                ->field("admin_no, admin_name, admin_id, admin_pass, auth_flg, deny_menu")
                ->from("mst_admin")
                ->where()
                    ->and("admin_id = ", $_POST["ID"], FD_STR)
                    ->and("del_flg = ", "0", FD_NUM)
            ->createSQL();
    $row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

    $errMessage = (new SmartAutoCheck($template))
                    // データ未存在
                    ->item($row["admin_no"])
                        ->required("A0103")
                        ->break()
                    // パスワード
                    ->item($_POST["PASS"])
                        ->password_verify("A0103", $row["admin_pass"] )
                    ->report(false);

    //エラーがある場合はLoginに戻す
    if (mb_strlen($errMessage) != 0 ){
        DispLogin($template, $errMessage);
        return;
    }

    // セッションインスタンス生成
    $template->Session = new SmartSession(URL_ADMIN . "login.php", SESSION_SEC_ADMIN, SESSION_SID_ADMIN, DOMAIN, true);
    $template->Session->start();
    $template->Session->AdminInfo = $row;

    // トランザクション開始
    $template->DB->autoCommit(false);

    // ログイン成功時、最終ログインUAを更新
    $sql = (new SqlString())
            ->setAutoConvert( [$template->DB,"conv_sql"] )
            ->update("mst_admin")
                ->set()
                    ->value("login_dt", "current_timestamp", FD_FUNCTION )
                    ->value("login_ua", $_SERVER["HTTP_USER_AGENT"] . " [" . $_SERVER["REMOTE_ADDR"] . "]", FD_STR )
                ->where()
                    ->and("admin_no=",$row["admin_no"],FD_NUM)
            ->createSQL();

    $template->DB->query($sql);

    // コミット(トランザクション終了)
    $template->DB->autoCommit(true);

    // TOP画面へ遷移
    header("Location: " . URL_ADMIN . "index.php");
}

?>
