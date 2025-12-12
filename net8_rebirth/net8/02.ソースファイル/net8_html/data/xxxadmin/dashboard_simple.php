<?php
/**
 * 最もシンプルなダッシュボード
 */

// エラー表示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッション設定
ini_set("session.save_path", "/tmp");
session_name("NET8ADMIN");
session_start();

// ログインチェック
if (!isset($_SESSION['NET8_ADMIN'])) {
    header("Location: login_simple.php");
    exit();
}

$adminInfo = $_SESSION['NET8_ADMIN'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 シンプルダッシュボード</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f5f5f5; 
            padding: 20px;
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;
        }
        .welcome { 
            background: white; padding: 20px; border-radius: 10px; 
            margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .menu { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 15px; 
        }
        .menu-item { 
            background: white; padding: 20px; border-radius: 10px; 
            text-decoration: none; color: #333; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s; border: 2px solid transparent;
        }
        .menu-item:hover { 
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
            transform: translateY(-3px);
            border-color: #667eea;
        }
        .menu-item h3 { color: #667eea; margin-bottom: 10px; }
        .menu-item p { color: #666; font-size: 14px; }
        .status { 
            background: #e3f2fd; padding: 15px; border-radius: 10px; 
            margin: 20px 0; font-size: 14px;
        }
        .logout { 
            background: #dc3545; color: white; padding: 10px 20px; 
            border-radius: 5px; text-decoration: none; display: inline-block;
            margin-top: 10px;
        }
        .logout:hover { background: #c82333; color: white; text-decoration: none; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎮 NET8 管理画面</h1>
        <p>シンプルダッシュボード</p>
        <a href="login_simple.php?logout=1" class="logout">ログアウト</a>
    </div>

    <div class="welcome">
        <div class="success">
            ✅ ログイン成功！セッションが正常に維持されています
        </div>
        <h2>ようこそ、<?= htmlspecialchars($adminInfo['admin_name']) ?> さん</h2>
        <p><strong>管理者ID:</strong> <?= htmlspecialchars($adminInfo['admin_id']) ?></p>
        <p><strong>ログイン時刻:</strong> <?= date('Y-m-d H:i:s', $_SESSION['LOGIN_TIME']) ?></p>
        <p><strong>現在時刻:</strong> <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div class="status">
        <h3>🔧 セッション状態</h3>
        <p><strong>セッションID:</strong> <?= session_id() ?></p>
        <p><strong>セッション保存パス:</strong> <?= ini_get('session.save_path') ?></p>
        <p><strong>セッション生存時間:</strong> <?= ini_get('session.gc_maxlifetime') ?> 秒</p>
        <p><strong>認証状態:</strong> ✅ 認証済み</p>
    </div>

    <h2>📋 管理メニュー</h2>
    <div class="menu">
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
        
        <a href="index.php" class="menu-item">
            <h3>🏠 元のダッシュボード</h3>
            <p>通常の管理画面に戻る</p>
        </a>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 10px;">
        <h3>🧪 テスト結果</h3>
        <p>✅ ログイン認証成功</p>
        <p>✅ セッション維持成功</p>
        <p>✅ ダッシュボード表示成功</p>
        <p><strong>問題:</strong> セッション管理と認証フローが正常に動作しています</p>
    </div>
</body>
</html>