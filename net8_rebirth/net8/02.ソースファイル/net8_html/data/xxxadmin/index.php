<?php
/**
 * NET8 管理画面 - ダッシュボード（完全修正版）
 */

// セッション初期化
require_once(__DIR__ . "/session_init.php");

// ログインチェック
if (!checkAdminSession()) {
    exit(); // checkAdminSessionが既にリダイレクト済み
}

$adminInfo = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面 - ダッシュボード</title>
    <link rel="stylesheet" href="assets/admin_modern.css">
    <style>
        .dashboard { padding: 20px; }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px;
        }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .menu-item {
            background: white; padding: 20px; border-radius: 8px; text-decoration: none;
            color: #333; border: 1px solid #e0e0e0; transition: all 0.3s;
            display: block;
        }
        .menu-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            text-decoration: none;
        }
        .menu-item h3 { margin: 0 0 10px 0; color: #667eea; }
        .menu-item p { margin: 0; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="welcome-card">
            <h1>🎮 NET8 管理画面ダッシュボード</h1>
            <p>ようこそ、<?= htmlspecialchars($adminInfo["admin_name"]) ?> さん (<?= htmlspecialchars($adminInfo["admin_id"]) ?>)</p>
            <p>最終ログイン: <?= date("Y年m月d日 H:i:s") ?></p>
            <p style="margin-top: 15px;">
                <a href="logout.php" style="color: #fff; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                    ログアウト
                </a>
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 システム状態</h3>
                <p>✅ 正常稼働中</p>
                <p>セッション: <?= session_id() ?></p>
            </div>
            <div class="stat-card">
                <h3>🕐 稼働時間</h3>
                <p><?= date("Y-m-d H:i:s") ?></p>
                <p>タイムゾーン: Asia/Tokyo</p>
            </div>
            <div class="stat-card">
                <h3>🎯 管理権限</h3>
                <p>レベル: <?= $adminInfo["auth_flg"] ? "フル権限" : "制限付き" ?></p>
                <p>アカウント: <?= $adminInfo["admin_no"] ?></p>
            </div>
        </div>

        <h2>📋 管理メニュー</h2>
        <div class="menu-grid">
            <a href="member.php" class="menu-item">
                <h3>👥 会員管理</h3>
                <p>会員の登録・編集・削除</p>
            </a>
            
            <a href="machines.php" class="menu-item">
                <h3>🎰 マシン管理</h3>
                <p>パチンコ台の設定・管理</p>
            </a>
            
            <a href="model.php" class="menu-item">
                <h3>🎮 モデル管理</h3>
                <p>機種・モデルの管理</p>
            </a>
            
            <a href="camera.php" class="menu-item">
                <h3>📹 カメラ管理</h3>
                <p>ライブ配信カメラ設定</p>
            </a>
            
            <a href="search.php" class="menu-item">
                <h3>🔍 検索</h3>
                <p>データの検索・絞り込み</p>
            </a>
            
            <a href="sales.php" class="menu-item">
                <h3>💰 売上管理</h3>
                <p>売上データの確認・分析</p>
            </a>
            
            <a href="pointgrant.php" class="menu-item">
                <h3>🎁 ポイント付与</h3>
                <p>ユーザーポイントの付与</p>
            </a>
            
            <a href="api_keys_manage.php" class="menu-item">
                <h3>🔑 APIキー管理</h3>
                <p>API認証キーの管理</p>
            </a>
            
            <a href="system.php" class="menu-item">
                <h3>⚙️ システム設定</h3>
                <p>システム全体の設定</p>
            </a>
        </div>
        
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3>🔧 デバッグ情報</h3>
            <p><strong>セッションID:</strong> <?= session_id() ?></p>
            <p><strong>セッション開始時刻:</strong> <?= isset($_SESSION["login_time"]) ? date("Y-m-d H:i:s", $_SESSION["login_time"]) : "不明" ?></p>
            <p><strong>最終アクセス:</strong> <?= isset($_SESSION["last_access"]) ? date("Y-m-d H:i:s", $_SESSION["last_access"]) : "不明" ?></p>
            <p><strong>認証状態:</strong> <?= isset($_SESSION["authenticated"]) && $_SESSION["authenticated"] ? "✅ 認証済み" : "❌ 未認証" ?></p>
        </div>
    </div>
</body>
</html>