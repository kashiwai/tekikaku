<?php
// セッション確認
if (session_status() == PHP_SESSION_NONE) {
    session_name("NET8ADMIN");
    session_start();
}

// ログイン確認
if (!isset($_SESSION["AdminInfo"])) {
    header("Location: /xxxadmin/login.php");
    exit();
}

$adminInfo = $_SESSION["AdminInfo"];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>NET8 管理画面 - ダッシュボード</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
        .header { background: #667eea; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .welcome { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .menu { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .menu-item {
            background: white; padding: 20px; border-radius: 8px; text-decoration: none;
            color: #333; border: 1px solid #ddd; transition: all 0.3s;
        }
        .menu-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .logout { color: #dc3545; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎮 NET8 管理画面</h1>
        <p>ログイン中: <?= htmlspecialchars($adminInfo["admin_name"]) ?> (<?= htmlspecialchars($adminInfo["admin_id"]) ?>)</p>
        <p><a href="logout.php" class="logout">ログアウト</a></p>
    </div>

    <div class="welcome">
        <h2>✅ ログイン成功！</h2>
        <p>セッションが正常に維持されています。</p>
        <p>最終アクセス: <?= date("Y-m-d H:i:s") ?></p>
    </div>

    <div class="menu">
        <a href="member.php" class="menu-item">
            <h3>👥 会員管理</h3>
            <p>会員の登録・編集・削除</p>
        </a>
        
        <a href="machines.php" class="menu-item">
            <h3>🎰 マシン管理</h3>
            <p>パチンコ台の管理</p>
        </a>
        
        <a href="search.php" class="menu-item">
            <h3>🔍 検索</h3>
            <p>データの検索・絞り込み</p>
        </a>
        
        <a href="api_keys_manage.php" class="menu-item">
            <h3>🔑 APIキー管理</h3>
            <p>API認証キーの管理</p>
        </a>
        
        <a href="pointgrant.php" class="menu-item">
            <h3>🎁 ポイント付与</h3>
            <p>ユーザーポイントの付与</p>
        </a>
        
        <a href="sales.php" class="menu-item">
            <h3>💰 売上管理</h3>
            <p>売上データの確認</p>
        </a>
    </div>
</body>
</html>