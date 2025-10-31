<?php
/**
 * Create Admin User Script
 *
 * このスクリプトは管理者ユーザーを作成します。
 * 警告: セキュリティ上、使用後は削除してください。
 */

header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/_etc/setting.php');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Create Admin User - Net8</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .success {
            color: #4ec9b0;
            background: #1a3a1a;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #f48771;
            background: #3a1a1a;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #569cd6;
            background: #1a2a3a;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background: #2d2d2d;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 管理者ユーザー作成</h1>

<?php

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    echo '<div class="success">✅ データベース接続成功</div>';

    // 既存の管理者を確認
    $stmt = $pdo->query("SELECT admin_no, admin_id, admin_name FROM mst_admin WHERE del_flg = 0");
    $existing_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($existing_admins) > 0) {
        echo '<div class="info"><strong>📋 既存の管理者ユーザー:</strong><br>';
        foreach ($existing_admins as $admin) {
            echo "- ID: {$admin['admin_id']} / 名前: {$admin['admin_name']}<br>";
        }
        echo '</div>';
    } else {
        echo '<div class="info">ℹ️ 既存の管理者ユーザーは存在しません。新規作成します。</div>';
    }

    // 新しい管理者ユーザーを作成
    $admin_id = 'admin';
    $admin_password = 'admin123'; // 初期パスワード
    $admin_name = 'システム管理者';
    $admin_auth = 9; // 最高権限

    // パスワードをMD5でハッシュ化（既存のシステムに合わせる）
    $password_hash = md5($admin_password);

    // 既に同じIDが存在するかチェック
    $stmt = $pdo->prepare("SELECT admin_no FROM mst_admin WHERE admin_id = :admin_id");
    $stmt->execute(['admin_id' => $admin_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        echo '<div class="info">ℹ️ 管理者ID "' . htmlspecialchars($admin_id) . '" は既に存在します。</div>';

        // パスワードを更新
        $stmt = $pdo->prepare("
            UPDATE mst_admin
            SET admin_pass = :password,
                admin_name = :name,
                admin_auth = :auth,
                upd_dt = NOW()
            WHERE admin_id = :admin_id
        ");

        $result = $stmt->execute([
            'password' => $password_hash,
            'name' => $admin_name,
            'auth' => $admin_auth,
            'admin_id' => $admin_id
        ]);

        if ($result) {
            echo '<div class="success">✅ 管理者ユーザーを更新しました</div>';
        }

    } else {
        // 新規作成
        $stmt = $pdo->prepare("
            INSERT INTO mst_admin (
                admin_id,
                admin_pass,
                admin_name,
                admin_auth,
                del_flg,
                add_no,
                add_dt
            ) VALUES (
                :admin_id,
                :password,
                :name,
                :auth,
                0,
                1,
                NOW()
            )
        ");

        $result = $stmt->execute([
            'admin_id' => $admin_id,
            'password' => $password_hash,
            'name' => $admin_name,
            'auth' => $admin_auth
        ]);

        if ($result) {
            echo '<div class="success">✅ 管理者ユーザーを作成しました</div>';
        }
    }

    // ログイン情報を表示
    echo '<div class="info">';
    echo '<h2>🔑 ログイン情報</h2>';
    echo '<pre>';
    echo 'URL: https://mgg-webservice-production.up.railway.app/xxxadmin/login.php' . "\n\n";
    echo 'ユーザーID: ' . htmlspecialchars($admin_id) . "\n";
    echo 'パスワード: ' . htmlspecialchars($admin_password) . "\n\n";
    echo '権限レベル: ' . $admin_auth . ' (最高権限)' . "\n";
    echo '</pre>';
    echo '<p><strong>⚠️ 重要:</strong> ログイン後、必ずパスワードを変更してください！</p>';
    echo '</div>';

    // 確認
    echo '<h2>📊 作成後の管理者一覧</h2>';
    $stmt = $pdo->query("SELECT admin_no, admin_id, admin_name, admin_auth, add_dt FROM mst_admin WHERE del_flg = 0");
    $all_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<div class="info">';
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<tr><th>No</th><th>ID</th><th>名前</th><th>権限</th><th>作成日時</th></tr>';
    foreach ($all_admins as $admin) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($admin['admin_no']) . '</td>';
        echo '<td>' . htmlspecialchars($admin['admin_id']) . '</td>';
        echo '<td>' . htmlspecialchars($admin['admin_name']) . '</td>';
        echo '<td>' . htmlspecialchars($admin['admin_auth']) . '</td>';
        echo '<td>' . htmlspecialchars($admin['add_dt']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';

    echo '<div class="success">';
    echo '<h2>✅ 完了</h2>';
    echo '<p>管理画面にログインできるようになりました。</p>';
    echo '<p><a href="/xxxadmin/login.php" style="color: #4ec9b0;">👉 ログインページへ</a></p>';
    echo '</div>';

    echo '<div class="error">';
    echo '<h2>🔒 セキュリティ警告</h2>';
    echo '<p>このスクリプトは使用後、必ず削除してください：</p>';
    echo '<pre>rm /var/www/html/create_admin_user.php</pre>';
    echo '</div>';

} catch (PDOException $e) {
    echo '<div class="error">';
    echo '<h2>❌ エラー</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

?>

    </div>
</body>
</html>
