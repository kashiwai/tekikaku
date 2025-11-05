<?php
/**
 * NET8 管理画面 - ダッシュボード（モダンデザイン）
 * 統計情報とリアルタイムデータ表示
 */

require_once('../../_etc/require_files_admin.php');

// 日付設定
$now = date("Y/m/d H:i:s");
$today = GetRefTimeOffsetStart(0);
$todayEnd = GetRefTimeOffsetStart(1);
$yestaday = GetRefTimeOffsetStart(-1);
$this_month = date("Y/m/01 H:i:s", strtotime($today));
$last_month = date("Y/m/01 H:i:s", strtotime($this_month . " -1 months"));

$nowTimestamp = strtotime($now);
$tdTimestamp = strtotime($today);
$ydTimestamp = strtotime($yestaday);
$lmTimestamp = strtotime($last_month);

// データベース接続
$pdo = new PDO(
    "mysql:host={$GLOBALS['DB_HOST']};port={$GLOBALS['DB_PORT']};dbname={$GLOBALS['DB_NAME']};charset=utf8mb4",
    $GLOBALS['DB_USER'],
    $GLOBALS['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 統計データ取得
$memberRegistDate = array(
    array("name" => "_y", "s" => $yestaday, "e" => $today),
    array("name" => "_m", "s" => $this_month, "e" => $todayEnd),
    array("name" => "_l", "s" => $last_month, "e" => $this_month)
);

// 会員登録数（昨日）
$stmt = $pdo->prepare("SELECT count(*) as cnt FROM mst_member WHERE join_dt >= ? AND join_dt < ?");
$stmt->execute([$yestaday, $today]);
$joinCountY = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

// 会員登録数（当月）
$stmt->execute([$this_month, $todayEnd]);
$joinCountM = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

// 売上金額（昨日）
$stmt = $pdo->prepare("SELECT sum(amount) as amt FROM his_purchase WHERE purchase_dt >= ? AND purchase_dt < ? AND result_status = 1 AND purchase_type != '11'");
$stmt->execute([$yestaday, $today]);
$amountY = $stmt->fetch(PDO::FETCH_ASSOC)['amt'] ?? 0;

// 売上金額（当月）
$stmt->execute([$this_month, $todayEnd]);
$amountM = $stmt->fetch(PDO::FETCH_ASSOC)['amt'] ?? 0;

// ゲーム数（昨日）
$stmt = $pdo->prepare("SELECT sum(play_count) as cnt FROM his_play WHERE end_dt >= ? AND end_dt < ?");
$stmt->execute([$yestaday, $today]);
$playCountY = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

// ゲーム数（当月）
$stmt->execute([$this_month, $todayEnd]);
$playCountM = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

// 応募中の商品
$stmt = $pdo->prepare("SELECT count(*) as cnt FROM mst_goods WHERE recept_start_dt <= ? AND recept_end_dt >= ? AND del_flg = 0");
$stmt->execute([$now, $now]);
$inGoods = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

// 抽選待ちの商品
$stmt = $pdo->prepare("SELECT count(*) as cnt FROM mst_goods WHERE draw_dt <= ? AND draw_state = 0 AND draw_type = 1 AND del_flg = 0");
$stmt->execute([$now]);
$waitGoods = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

// 発送待ち
$stmt = $pdo->query("SELECT count(*) as cnt FROM dat_win WHERE state IN (1, 2)");
$waitSend = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

// マシン稼働状況
$stmt = $pdo->query("SELECT count(*) as total FROM dat_machine WHERE del_flg = 0");
$totalMachines = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT count(*) as active FROM dat_machine WHERE machine_status = 0 AND del_flg = 0");
$activeMachines = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

// 会員総数
$stmt = $pdo->query("SELECT count(*) as cnt FROM mst_member WHERE state != 9");
$totalMembers = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 ダッシュボード</title>
    <link rel="stylesheet" href="assets/admin_modern.css">
    <style>
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3); }
        .stat-card.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stat-icon { font-size: 32px; margin-bottom: 12px; opacity: 0.9; }
        .stat-value { font-size: 36px; font-weight: 700; margin-bottom: 8px; }
        .stat-label { font-size: 14px; opacity: 0.9; margin-bottom: 4px; }
        .stat-sublabel { font-size: 12px; opacity: 0.7; }
        .info-card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .info-card h3 { margin: 0 0 16px 0; font-size: 18px; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-size: 14px; }
        .info-value { color: #0f172a; font-weight: 600; font-size: 16px; }
        .alert-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-card.warning { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-left-color: #ef4444; }
        @media (max-width: 1200px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .stat-grid { grid-template-columns: 1fr; } }
    </style>
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
                <a href="index.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span>ホーム</span>
                </a>
                <a href="menu.php" class="nav-item">
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
            <h1 class="header-title">ダッシュボード</h1>
            <div class="header-actions">
                <a href="/register_machine3.php" class="header-btn">🔧 マシン#3登録</a>
                <a href="menu.php" class="header-btn" style="background: #10b981;">📋 全メニュー</a>
            </div>
        </header>

        <!-- コンテンツ -->
        <div class="content-wrapper">
            <!-- アラート -->
            <?php if ($waitGoods > 0): ?>
            <div class="alert-card warning">
                <span style="font-size: 24px;">⚠️</span>
                <div>
                    <strong>抽選待ちの商品があります</strong>
                    <div style="font-size: 14px; opacity: 0.8;">抽選対象: <?= number_format($waitGoods) ?>件</div>
                </div>
                <a href="goods.php" class="btn btn-danger" style="margin-left: auto;">確認する</a>
            </div>
            <?php endif; ?>

            <?php if ($waitSend > 0): ?>
            <div class="alert-card">
                <span style="font-size: 24px;">📦</span>
                <div>
                    <strong>発送待ちの商品があります</strong>
                    <div style="font-size: 14px; opacity: 0.8;">発送対象: <?= number_format($waitSend) ?>件</div>
                </div>
                <a href="shipping.php" class="btn btn-primary" style="margin-left: auto;">配送管理へ</a>
            </div>
            <?php endif; ?>

            <!-- 統計カード -->
            <div class="stat-grid">
                <div class="stat-card fade-in">
                    <div class="stat-icon">🎰</div>
                    <div class="stat-value"><?= number_format($activeMachines) ?> / <?= number_format($totalMachines) ?></div>
                    <div class="stat-label">稼働中マシン</div>
                    <div class="stat-sublabel">全<?= number_format($totalMachines) ?>台中</div>
                </div>

                <div class="stat-card green fade-in" style="animation-delay: 0.1s;">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?= number_format($totalMembers) ?></div>
                    <div class="stat-label">会員総数</div>
                    <div class="stat-sublabel">昨日: +<?= number_format($joinCountY) ?>名</div>
                </div>

                <div class="stat-card orange fade-in" style="animation-delay: 0.2s;">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">¥<?= number_format($amountY) ?></div>
                    <div class="stat-label">昨日の売上</div>
                    <div class="stat-sublabel">当月: ¥<?= number_format($amountM) ?></div>
                </div>

                <div class="stat-card red fade-in" style="animation-delay: 0.3s;">
                    <div class="stat-icon">🎮</div>
                    <div class="stat-value"><?= number_format($playCountY) ?></div>
                    <div class="stat-label">昨日のゲーム数</div>
                    <div class="stat-sublabel">当月: <?= number_format($playCountM) ?>回</div>
                </div>
            </div>

            <!-- 詳細情報 -->
            <div class="grid grid-2">
                <!-- 会員情報 -->
                <div class="info-card fade-in" style="animation-delay: 0.4s;">
                    <h3>📊 会員登録状況</h3>
                    <div class="info-row">
                        <span class="info-label"><?= date("m/d", $ydTimestamp) ?> (<?= $GLOBALS["weekList"][date('w', $ydTimestamp)] ?>)</span>
                        <span class="info-value">+<?= number_format($joinCountY) ?>名</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">当月 (<?= date("Y/m", strtotime($this_month)) ?>)</span>
                        <span class="info-value">+<?= number_format($joinCountM) ?>名</span>
                    </div>
                    <div style="margin-top: 16px;">
                        <a href="member.php" class="btn btn-outline" style="width: 100%;">会員管理へ</a>
                    </div>
                </div>

                <!-- 売上情報 -->
                <div class="info-card fade-in" style="animation-delay: 0.5s;">
                    <h3>💰 売上状況</h3>
                    <div class="info-row">
                        <span class="info-label"><?= date("m/d", $ydTimestamp) ?> (<?= $GLOBALS["weekList"][date('w', $ydTimestamp)] ?>)</span>
                        <span class="info-value">¥<?= number_format($amountY) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">当月 (<?= date("Y/m", strtotime($this_month)) ?>)</span>
                        <span class="info-value">¥<?= number_format($amountM) ?></span>
                    </div>
                    <div style="margin-top: 16px;">
                        <a href="sales.php" class="btn btn-outline" style="width: 100%;">売上管理へ</a>
                    </div>
                </div>

                <!-- ゲーム状況 -->
                <div class="info-card fade-in" style="animation-delay: 0.6s;">
                    <h3>🎮 プレイ状況</h3>
                    <div class="info-row">
                        <span class="info-label"><?= date("m/d", $ydTimestamp) ?> (<?= $GLOBALS["weekList"][date('w', $ydTimestamp)] ?>)</span>
                        <span class="info-value"><?= number_format($playCountY) ?>回</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">当月 (<?= date("Y/m", strtotime($this_month)) ?>)</span>
                        <span class="info-value"><?= number_format($playCountM) ?>回</span>
                    </div>
                    <div style="margin-top: 16px;">
                        <a href="playhistory.php" class="btn btn-outline" style="width: 100%;">プレイ履歴へ</a>
                    </div>
                </div>

                <!-- 商品・抽選状況 -->
                <div class="info-card fade-in" style="animation-delay: 0.7s;">
                    <h3>🎁 商品・抽選状況</h3>
                    <div class="info-row">
                        <span class="info-label">応募中の商品</span>
                        <span class="info-value"><?= number_format($inGoods) ?>件</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">抽選待ち</span>
                        <span class="info-value" style="color: #ef4444;"><?= number_format($waitGoods) ?>件</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">発送待ち</span>
                        <span class="info-value" style="color: #f59e0b;"><?= number_format($waitSend) ?>件</span>
                    </div>
                    <div style="margin-top: 16px; display: flex; gap: 8px;">
                        <a href="goods.php" class="btn btn-outline" style="flex: 1;">商品管理</a>
                        <a href="shipping.php" class="btn btn-outline" style="flex: 1;">配送管理</a>
                    </div>
                </div>
            </div>

            <!-- クイックアクション -->
            <div class="card fade-in" style="animation-delay: 0.8s; margin-top: 24px;">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>⚡</span>
                        クイックアクション
                    </h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                    <a href="/register_machine3.php" class="btn btn-primary">🔧 マシン#3登録</a>
                    <a href="machines.php" class="btn btn-outline">🎰 マシン管理</a>
                    <a href="member.php" class="btn btn-outline">👥 会員管理</a>
                    <a href="pointgrant.php" class="btn btn-outline">💰 ポイント付与</a>
                    <a href="goods.php" class="btn btn-outline">🎁 商品管理</a>
                    <a href="playhistory.php" class="btn btn-outline">📊 プレイ履歴</a>
                    <a href="shipping.php" class="btn btn-outline">📦 配送管理</a>
                    <a href="menu.php" class="btn btn-outline">🗂️ 全メニュー</a>
                </div>
            </div>

            <!-- フッター情報 -->
            <div style="margin-top: 32px; padding: 16px; text-align: center; color: #64748b; font-size: 14px;">
                <p>最終更新: <?= date("Y/m/d H:i:s") ?> | NET8 Management System v2.0</p>
            </div>
        </div>
    </main>
</body>
</html>
