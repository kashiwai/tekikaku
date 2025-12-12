<?php
/*
 * index.php（オリジナル構造維持・セッション管理のみ修正）
 *
 * (C)SmartRams Co.,Ltd. 2019 All Rights Reserved．
 */

// インクルード
require_once('../../_etc/require_files_admin.php');
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));

// メイン処理
main();

function main() {
	try {
		// 管理系表示コントロールのインスタンス生成（セッション管理修正版）
		$template = new TemplateAdmin();
		
		// セッション確認を追加（オリジナルにない部分）
		if (!isset($_SESSION["AdminInfo"])) {
			// セッションが無い場合はログイン画面へ
			header("Location: " . URL_ADMIN . "login.php");
			exit();
		}
		
		// セッション更新
		$_SESSION["last_access"] = time();

		// トップ画面
		DispTop($template);

	} catch (Exception $e) {
		// エラー画面表示
		echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
		echo "<h1>エラーが発生しました</h1>";
		echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
		echo "<p><a href='login.php'>ログイン画面に戻る</a></p>";
		echo "</body></html>";
	}
}

/**
 * トップ画面表示（オリジナルデザイン維持）
 */
function DispTop($template) {
	// 管理者情報の取得
	$adminInfo = $_SESSION["AdminInfo"] ?? null;
	if (!$adminInfo) {
		header("Location: " . URL_ADMIN . "login.php");
		exit();
	}
	
	// HTMLテンプレートの開始
	echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面 - ダッシュボード</title>
    <link rel="stylesheet" href="assets/admin_modern.css">
    <style>
        /* オリジナルCSS（admin_modern.cssと同等） */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --surface-3: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--surface-2);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .admin-layout {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            font-size: 2rem;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: var(--error);
            color: white;
            border: none;
            border-radius: 0.375rem;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1.125rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .stat-title {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-icon {
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .menu-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .menu-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }

        .menu-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .menu-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .menu-icon {
            font-size: 1.5rem;
        }

        .menu-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .menu-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .header-content {
                padding: 0 1rem;
            }
            
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <header class="admin-header">
            <div class="header-content">
                <div class="logo">
                    <span class="logo-icon">🎮</span>
                    <span class="logo-text">NET8 Admin</span>
                </div>
                <div class="user-menu">
                    <div class="user-info">
                        <span>👤</span>
                        <span>' . htmlspecialchars($adminInfo['admin_name']) . ' (' . htmlspecialchars($adminInfo['admin_id']) . ')</span>
                    </div>
                    <a href="logout.php" class="logout-btn">ログアウト</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="welcome-card">
                <h1 class="welcome-title">NET8 管理システム</h1>
                <p class="welcome-subtitle">ようこそ、' . htmlspecialchars($adminInfo['admin_name']) . ' さん</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">システム状態</span>
                        <span class="stat-icon">📊</span>
                    </div>
                    <div class="stat-value">正常稼働</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">最終ログイン</span>
                        <span class="stat-icon">🕐</span>
                    </div>
                    <div class="stat-value">' . date('H:i') . '</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">セッション</span>
                        <span class="stat-icon">🔒</span>
                    </div>
                    <div class="stat-value">アクティブ</div>
                </div>
            </div>

            <div class="menu-section">
                <h2 class="section-title">管理メニュー</h2>
                <div class="menu-grid">
                    <a href="member.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">👥</span>
                            <span class="menu-title">会員管理</span>
                        </div>
                        <p class="menu-description">会員の登録・編集・削除を行います</p>
                    </a>
                    
                    <a href="machines.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">🎰</span>
                            <span class="menu-title">マシン管理</span>
                        </div>
                        <p class="menu-description">パチンコ台の設定・管理を行います</p>
                    </a>
                    
                    <a href="model.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">🎮</span>
                            <span class="menu-title">モデル管理</span>
                        </div>
                        <p class="menu-description">機種・モデルの管理を行います</p>
                    </a>
                    
                    <a href="camera.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">📹</span>
                            <span class="menu-title">カメラ管理</span>
                        </div>
                        <p class="menu-description">ライブ配信カメラの設定を行います</p>
                    </a>
                    
                    <a href="search.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">🔍</span>
                            <span class="menu-title">検索</span>
                        </div>
                        <p class="menu-description">データの検索・絞り込みを行います</p>
                    </a>
                    
                    <a href="sales.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">💰</span>
                            <span class="menu-title">売上管理</span>
                        </div>
                        <p class="menu-description">売上データの確認・分析を行います</p>
                    </a>
                    
                    <a href="pointgrant.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">🎁</span>
                            <span class="menu-title">ポイント付与</span>
                        </div>
                        <p class="menu-description">ユーザーポイントの付与を行います</p>
                    </a>
                    
                    <a href="api_keys_manage.php" class="menu-card">
                        <div class="menu-header">
                            <span class="menu-icon">🔑</span>
                            <span class="menu-title">APIキー管理</span>
                        </div>
                        <p class="menu-description">API認証キーの管理を行います</p>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>';
}
?>