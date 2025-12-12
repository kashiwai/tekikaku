<?php
/**
 * 簡単ダッシュボード（ログイン後確認用）
 */

// セッション開始
session_name("NET8ADMIN");
session_start();

// ログイン確認
if (empty($_SESSION["AdminInfo"])) {
    header("Location: login_simple_fix.php");
    exit();
}

$adminInfo = $_SESSION["AdminInfo"];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 ダッシュボード（テスト版）</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .title { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .welcome { color: #64748b; }
        .content { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .menu { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .menu-item { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-decoration: none; color: #374151; }
        .menu-item:hover { background: #f1f5f9; }
        .logout { margin-top: 20px; }
        .logout a { padding: 10px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">🎮 NET8 管理システム</div>
        <div class="welcome">ようこそ、<?= htmlspecialchars($adminInfo['admin_name']) ?> さん (ID: <?= htmlspecialchars($adminInfo['admin_id']) ?>)</div>
    </div>

    <div class="success">
        ✅ <strong>ログイン成功！</strong> セッションが正常に維持されています。
    </div>

    <div class="info">
        📊 <strong>セッション情報:</strong><br>
        • セッションID: <?= session_id() ?><br>
        • ログイン時刻: <?= date('Y-m-d H:i:s', $_SESSION['login_time']) ?><br>
        • 最終アクセス: <?= date('Y-m-d H:i:s', $_SESSION['last_access']) ?>
    </div>

    <div class="content">
        <h3>🔧 テストメニュー</h3>
        <div class="menu">
            <a href="session_test.php" class="menu-item">
                📊 セッション詳細確認
            </a>
            <a href="index.php" class="menu-item">
                🏠 元のダッシュボード
            </a>
            <a href="member.php" class="menu-item">
                👥 会員管理
            </a>
            <a href="machines.php" class="menu-item">
                🎰 マシン管理
            </a>
        </div>
        
        <div class="logout">
            <a href="logout.php">🚪 ログアウト</a>
        </div>
    </div>

    <div class="info" style="margin-top: 20px;">
        <h4>🧪 テスト確認ポイント</h4>
        <ul>
            <li>✅ ログイン1回でこのページが表示された</li>
            <li>✅ 上記のメニューリンクをクリックして他ページにアクセス</li>
            <li>✅ 再度ログインを求められないことを確認</li>
        </ul>
    </div>
</body>
</html>