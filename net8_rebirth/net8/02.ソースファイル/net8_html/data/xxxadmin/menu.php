<?php
/**
 * NET8 管理画面 - モダンメニュー
 * 全機能へのアクセス
 */

require_once('../../_etc/require_files_admin.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面</title>
    <link rel="stylesheet" href="assets/admin_modern.css">
</head>
<body>
    <!-- サイドバー -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🎮 NET8 Admin</div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">ダッシュボード</div>
                <a href="index.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>ホーム</span>
                </a>
                <a href="menu.php" class="nav-item active">
                    <span class="nav-icon">🗂️</span>
                    <span>全メニュー</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">マシン管理</div>
                <a href="machines.php" class="nav-item">
                    <span class="nav-icon">🎰</span>
                    <span>マシン一覧</span>
                </a>
                <a href="machine_control.php" class="nav-item">
                    <span class="nav-icon">🎮</span>
                    <span>マシンコントロール</span>
                </a>
                <a href="model.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>機種管理</span>
                </a>
                <a href="maker.php" class="nav-item">
                    <span class="nav-icon">🏢</span>
                    <span>メーカー</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">エージェント管理</div>
                <a href="machine_control.php" class="nav-item">
                    <span class="nav-icon">🖥️</span>
                    <span>マシンコントロール</span>
                </a>
                <a href="agent_control.php" class="nav-item">
                    <span class="nav-icon">🤖</span>
                    <span>Agentコントロール</span>
                </a>
                <a href="machine_monitor.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>マシンモニター</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">カメラ・配信</div>
                <a href="camera.php" class="nav-item">
                    <span class="nav-icon">📹</span>
                    <span>カメラ管理</span>
                </a>
                <a href="streaming.php" class="nav-item">
                    <span class="nav-icon">📡</span>
                    <span>ストリーミング</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">会員・ポイント</div>
                <a href="member.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>会員管理</span>
                </a>
                <a href="pointgrant.php" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>ポイント付与</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">システム</div>
                <a href="system.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>設定</span>
                </a>
                <a href="signaling.php" class="nav-item">
                    <span class="nav-icon">🔧</span>
                    <span>シグナリング</span>
                </a>
                <a href="api_keys_manage.php" class="nav-item">
                    <span class="nav-icon">🔑</span>
                    <span>API管理</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span>ログアウト</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- メインコンテンツ -->
    <main class="main-content">
        <!-- ヘッダー -->
        <header class="header">
            <h1 class="header-title">全メニュー</h1>
            <div class="header-actions">
                <a href="/register_machine3.php" class="header-btn">🔧 マシン#3登録</a>
            </div>
        </header>

        <!-- コンテンツ -->
        <div class="content-wrapper">
            <!-- 統計ウィジェット -->
            <div class="grid grid-4" style="margin-bottom: 32px;">
                <div class="stat-widget fade-in">
                    <div class="stat-value">40</div>
                    <div class="stat-label">マシン総数</div>
                </div>
                <div class="stat-widget fade-in" style="animation-delay: 0.1s; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="stat-value">3</div>
                    <div class="stat-label">稼働中</div>
                </div>
                <div class="stat-widget fade-in" style="animation-delay: 0.2s; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="stat-value">1,234</div>
                    <div class="stat-label">会員数</div>
                </div>
                <div class="stat-widget fade-in" style="animation-delay: 0.3s; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <div class="stat-value">¥125K</div>
                    <div class="stat-label">本日売上</div>
                </div>
            </div>

            <!-- メニューカード -->
            <div class="grid grid-3">
                <!-- マシン・機種管理 -->
                <div class="card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🎮</span>
                            マシン・機種管理
                        </h2>
                        <span class="card-badge">6</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="machines.php" class="btn btn-outline">マシン管理</a>
                        <a href="model.php" class="btn btn-outline">機種管理</a>
                        <a href="maker.php" class="btn btn-outline">メーカー管理</a>
                        <a href="corner.php" class="btn btn-outline">コーナー管理</a>
                        <a href="auto_setup.php" class="btn btn-outline">自動セットアップ</a>
                        <a href="/register_machine3.php" class="btn btn-primary">🔧 マシン#3登録</a>
                    </nav>
                </div>

                <!-- 会員管理 -->
                <div class="card fade-in" style="animation-delay: 0.1s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>👥</span>
                            会員管理
                        </h2>
                        <span class="card-badge">4</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="member.php" class="btn btn-outline">会員管理</a>
                        <a href="owner.php" class="btn btn-outline">オーナー管理</a>
                        <a href="admin.php" class="btn btn-outline">管理者管理</a>
                        <a href="address.php" class="btn btn-outline">住所管理</a>
                    </nav>
                </div>

                <!-- ポイント・特典 -->
                <div class="card fade-in" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🎁</span>
                            ポイント・特典
                        </h2>
                        <span class="card-badge">5</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="pointgrant.php" class="btn btn-outline">ポイント付与</a>
                        <a href="pointconvert.php" class="btn btn-outline">ポイント交換</a>
                        <a href="pointhistory.php" class="btn btn-outline">ポイント履歴</a>
                        <a href="benefits.php" class="btn btn-outline">会員特典</a>
                        <a href="coupon.php" class="btn btn-outline">クーポン管理</a>
                    </nav>
                </div>

                <!-- ギフト・商品 -->
                <div class="card fade-in" style="animation-delay: 0.3s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🛍️</span>
                            ギフト・商品
                        </h2>
                        <span class="card-badge">7</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="gift.php" class="btn btn-outline">ギフト管理</a>
                        <a href="gifthistory.php" class="btn btn-outline">ギフト履歴</a>
                        <a href="giftaddset.php" class="btn btn-outline">ギフト追加設定</a>
                        <a href="giftlimit.php" class="btn btn-outline">ギフト制限</a>
                        <a href="goods.php" class="btn btn-outline">商品管理</a>
                        <a href="goods_status.php" class="btn btn-outline">商品ステータス</a>
                        <a href="goods_drawpick.php" class="btn btn-outline">抽選商品</a>
                    </nav>
                </div>

                <!-- プレイ・購入履歴 -->
                <div class="card fade-in" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>📊</span>
                            プレイ・購入履歴
                        </h2>
                        <span class="card-badge">5</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="playhistory.php" class="btn btn-outline">プレイ履歴</a>
                        <a href="memberplayhistory.php" class="btn btn-outline">会員プレイ履歴</a>
                        <a href="drawhistory.php" class="btn btn-outline">抽選履歴</a>
                        <a href="purchase.php" class="btn btn-outline">購入管理</a>
                        <a href="purchasehistory.php" class="btn btn-outline">購入履歴</a>
                    </nav>
                </div>

                <!-- コンテンツ管理 -->
                <div class="card fade-in" style="animation-delay: 0.5s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>📰</span>
                            コンテンツ管理
                        </h2>
                        <span class="card-badge">3</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="notice.php" class="btn btn-outline">お知らせ管理</a>
                        <a href="magazine.php" class="btn btn-outline">マガジン管理</a>
                        <a href="image_upload.php" class="btn btn-outline">画像アップロード</a>
                    </nav>
                </div>

                <!-- 配送・売上 -->
                <div class="card fade-in" style="animation-delay: 0.6s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>📦</span>
                            配送・売上
                        </h2>
                        <span class="card-badge">2</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="shipping.php" class="btn btn-outline">配送管理</a>
                        <a href="sales.php" class="btn btn-outline">売上管理</a>
                    </nav>
                </div>

                <!-- エージェント管理 -->
                <div class="card fade-in" style="animation-delay: 0.7s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🤖</span>
                            エージェント管理
                        </h2>
                        <span class="card-badge">4</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="machine_control.php" class="btn btn-primary">🖥️ マシンコントロール</a>
                        <a href="agent_control.php" class="btn btn-primary">🤖 Agentコントロール</a>
                        <a href="machine_monitor.php" class="btn btn-outline">📊 マシンモニター</a>
                        <a href="machine_setup_list.php" class="btn btn-outline">📋 セットアップ一覧</a>
                    </nav>
                </div>

                <!-- カメラ・配信管理 -->
                <div class="card fade-in" style="animation-delay: 0.8s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>📹</span>
                            カメラ・配信管理
                        </h2>
                        <span class="card-badge">3</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="camera.php" class="btn btn-outline">カメラ管理</a>
                        <a href="camera_settings.php" class="btn btn-outline">カメラ設定</a>
                        <a href="streaming.php" class="btn btn-outline">ストリーミング設定</a>
                    </nav>
                </div>

                <!-- マシン詳細管理 -->
                <div class="card fade-in" style="animation-delay: 0.8s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🎮</span>
                            マシン詳細管理
                        </h2>
                        <span class="card-badge">2</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="machine_control.php" class="btn btn-outline">マシンコントロール</a>
                        <a href="machine_edit.php" class="btn btn-outline">マシン編集</a>
                    </nav>
                </div>

                <!-- 検索・モニター -->
                <div class="card fade-in" style="animation-delay: 0.9s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🔍</span>
                            検索・モニター
                        </h2>
                        <span class="card-badge">2</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="search.php" class="btn btn-outline">検索機能</a>
                        <a href="moniter.php" class="btn btn-outline">モニター</a>
                    </nav>
                </div>

                <!-- 技術設定 -->
                <div class="card fade-in" style="animation-delay: 1.0s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🔧</span>
                            技術設定
                        </h2>
                        <span class="card-badge">2</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="signaling.php" class="btn btn-outline">シグナリングサーバー</a>
                        <a href="api_keys_manage.php" class="btn btn-outline">API管理</a>
                    </nav>
                </div>

                <!-- システム設定 -->
                <div class="card fade-in" style="animation-delay: 1.1s;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>⚙️</span>
                            システム設定
                        </h2>
                        <span class="card-badge">4</span>
                    </div>
                    <nav style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="system.php" class="btn btn-outline">システム設定</a>
                        <a href="debug_session.php" class="btn btn-outline">セッションデバッグ</a>
                        <a href="test_db.php" class="btn btn-outline">DB接続テスト</a>
                        <a href="test_check.php" class="btn btn-outline">システムチェック</a>
                    </nav>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
