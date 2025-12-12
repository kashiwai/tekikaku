<?php
/*
 * index.php
 *
 * (C)SmartRams Co.,Ltd. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * 管理画面TOP表示（モダンデザイン）
 *
 * 管理画面TOPの表示を行う
 *
 * @package
 * @author   片岡 充
 * @version  2.0 (Modern UI)
 * @since    2019/06/21 初版作成 片岡 充
 * @updated  2025/11/05 モダンデザイン適用
 */

// インクルード
require_once('../../_etc/require_files_admin.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));	// テンプレートHTMLプレフィックス

// メイン処理
main();

/**
 * メイン処理
 * @access	public
 * @param	なし
 * @return	なし
 * @info
 */
function main() {

	try {
		// 管理系表示コントロールのインスタンス生成
		$template = new TemplateAdmin();

		// トップ画面
		DispTop($template);

	} catch (Exception $e) {
		echo '<h1>エラーが発生しました</h1>';
		echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
		exit;
	}
}

/**
 * TOP画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispTop($template) {

	// 各項目の期間定義
	// 日付設定
	$now = date("Y/m/d H:i:s");
	$today = GetRefTimeOffsetStart(0);		// 当日開始
	$todayEnd = GetRefTimeOffsetStart(1);	// 当日終了(含まないので翌日の開始)
	$yestaday = GetRefTimeOffsetStart(-1);	// 前日開始
	$this_month = date("Y/m/01 H:i:s", strtotime($today));	// 当月開始
	$last_month = date("Y/m/01 H:i:s", strtotime($this_month . " -1 months"));	// 前月開始
	// パラメータ等の加工用にタイムスタンプも設定
	$nowTimestamp = strtotime($now);
	$tdTimestamp = strtotime($today);		// 当日
	$ydTimestamp = strtotime($yestaday);	// 昨日
	$lmTimestamp = strtotime($last_month);	// 前月
	//
	$memberRegistDate = array(
		//昨日
		 array( "name"=>"_y", "s"=>$yestaday, "e"=>$today)
		//当月
		,array( "name"=>"_m", "s"=>$this_month, "e"=>$todayEnd)
		//先月
		,array( "name"=>"_l", "s"=>$last_month, "e"=>$this_month)
	);

	$mem_join_count_sql  = array();
	$mem_leave_count_sql = array();
	$his_purchase_count_sql  = array();
	$his_purchase_amount_sql = array();
	$his_play_count_sql  = array();
	$his_play_credit_sql = array();
	$goods_blocks_sql = array();
	$win_blocks_sql = array();

	//
	foreach( $memberRegistDate as $check_date){
		// 登録会員数
		$mem_join_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("mst_member mm")
			->where()
				->and( false, "mm.join_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "mm.join_dt <  ", $check_date["e"], FD_DATE)
			->createSql().") as join" . $check_date["name"];
		// 退会会員数
		$mem_leave_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("mst_member mm")
			->where()
				->and( false, "mm.quit_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "mm.quit_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "mm.state = ", "9", FD_NUM)
			->createSql().") as leave" . $check_date["name"];
		// 売上件数
		$his_purchase_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("his_purchase hp")
			->where()
				->and( false, "hp.purchase_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.purchase_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hp.result_status = ", "1", FD_NUM)
				->and( false, "hp.purchase_type != ", "11", FD_STR)		// 抽選ポイントを除く
			->createSql().") as amount_count" . $check_date["name"];
		// 売上金額
		$his_purchase_amount_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hp.amount)" )
			->from("his_purchase hp")
			->where()
				->and( false, "hp.purchase_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.purchase_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hp.result_status = ", "1", FD_NUM)
				->and( false, "hp.purchase_type != ", "11", FD_STR)		// 抽選ポイントを除く
			->createSql().") as amount_value" . $check_date["name"];
		// 総ゲーム数
		$his_play_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hp.play_count)" )
			->from("his_play hp")
			->where()
				->and( false, "hp.end_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.end_dt <  ", $check_date["e"], FD_DATE)
			->createSql().") as play_count" . $check_date["name"];
		// 差枚数
		$his_play_credit_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hp.in_credit) - sum(hp.out_credit)" )
			->from("his_play hp")
			->where()
				->and( false, "hp.end_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.end_dt <  ", $check_date["e"], FD_DATE)
			->createSql().") as credit" . $check_date["name"];
	}

	//-- 商品はリアル日時で判定
	// 応募期間中の商品
	$goods_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_goods mg")
		->where()
			->and( false, "mg.recept_start_dt <= ", $now, FD_DATE)
			->and( false, "mg.recept_end_dt >= "  , $now, FD_DATE)
			->and( false, "mg.del_flg = ", "0", FD_NUM)
		->createSQL().") as in_goods";
	// 自動抽選待ちの商品		応募終了～ and draw_state = 0 and draw_type = 1
	$goods_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_goods mg")
		->where()
			->and( false, "mg.draw_dt <= "   , $now, FD_DATE)
			->and( false, "mg.draw_state = " , 0, FD_NUM)
			->and( false, "mg.draw_type = "  , 1, FD_NUM)
			->and( false, "mg.del_flg = ", "0", FD_NUM)
		->createSQL().") as wait_goods";
	// 手動抽選待ちの商品	抽選日～ and draw_state = 0 and draw_type = 2
	$goods_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_goods mg")
		->where()
			->and( false, "mg.draw_dt <= "   , $now, FD_DATE)
			->and( false, "mg.draw_state = " , 0, FD_NUM)
			->and( false, "mg.draw_type = "  , 2, FD_NUM)
			->and( false, "mg.del_flg = ", "0", FD_NUM)
		->createSQL().") as wait_manual_goods";

	// 発送先入力待ち
	$win_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_win dw")
		->where()
			->and( false, "dw.state = " , 0, FD_NUM)
		->createSQL().") as wait_input";
	// 発送待ち
	$win_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_win dw")
		->where()
			->and( false, "dw.state in " , ["1", "2"], FD_NUM)
		->createSQL().") as wait_send";

	// 会員登録件数
	$sql = "select "
		.     implode(",", $mem_join_count_sql);
	$joinCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 会員退会件数
	$sql = "select "
		.     implode(",", $mem_leave_count_sql);
	$leaveCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 売上件数
	$sql = "select "
		.     implode(",", $his_purchase_count_sql);
	$purchaseCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 売上金額
	$sql = "select "
		.     implode(",", $his_purchase_amount_sql);
	$amountCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// ゲーム数
	$sql = "select "
		.     implode(",", $his_play_count_sql);
	$playCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 差枚数
	$sql = "select "
		.     implode(",", $his_play_credit_sql);
	$creditCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 抽選
	$sql = "select "
		.     implode(",", $goods_blocks_sql);
	$goodsCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 発送
	$sql = "select "
		.     implode(",", $win_blocks_sql);
	$winCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);

	// === 追加データ取得（モダンUI用） ===
	// マシン稼働状況
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_machine")
		->where()
			->and( false, "del_flg = ", "0", FD_NUM)
		->createSQL();
	$totalMachines = $template->DB->getOne($sql);

	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_machine")
		->where()
			->and( false, "machine_status = ", "0", FD_NUM)
			->and( false, "del_flg = ", "0", FD_NUM)
		->createSQL();
	$activeMachines = $template->DB->getOne($sql);

	// 会員総数
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_member")
		->where()
			->and( false, "state != ", "9", FD_NUM)
		->createSQL();
	$totalMembers = $template->DB->getOne($sql);

	// === HTML出力開始（モダンデザイン） ===
	?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 ダッシュボード</title>
    <link rel="stylesheet" href="assets/admin_modern.css">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; display: flex; }
        .sidebar { width: 260px; background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); height: 100vh; position: fixed; left: 0; top: 0; color: white; overflow-y: auto; }
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo { font-size: 20px; font-weight: 700; }
        .sidebar-nav { padding: 16px 0; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { font-size: 12px; font-weight: 600; color: #94a3b8; padding: 8px 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #cbd5e1; text-decoration: none; transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-icon { font-size: 18px; }
        .main-content { margin-left: 260px; flex: 1; }
        .header { background: white; padding: 24px 32px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .header-title { font-size: 28px; font-weight: 700; color: #0f172a; margin: 0; }
        .header-actions { display: flex; gap: 12px; }
        .header-btn { padding: 10px 20px; border-radius: 8px; background: #667eea; color: white; text-decoration: none; font-weight: 600; transition: all 0.2s; }
        .header-btn:hover { background: #5568d3; transform: translateY(-2px); }
        .content-wrapper { padding: 32px; }
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
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.2s; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-outline { background: white; color: #667eea; border: 1px solid #667eea; }
        .grid { display: grid; gap: 24px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; pointer-events: auto !important; }
        
        /* クリック問題修正CSS */
        body * {
            pointer-events: auto !important;
        }
        
        .sidebar {
            z-index: 100 !important;
            pointer-events: auto !important;
        }
        
        .main-content {
            pointer-events: auto !important;
            position: relative;
            z-index: 1;
        }
        
        /* 全てのクリック可能要素を強制有効化 */
        a, button, .btn, .nav-item, .stat-card, .info-card {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 10;
        }
        
        /* オーバーレイ要素があれば無効化 */
        .overlay, .backdrop, .modal-backdrop {
            display: none !important;
            pointer-events: none !important;
        }
        @media (max-width: 1200px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .stat-grid, .grid-2 { grid-template-columns: 1fr; } }
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
                <div class="nav-section-title">会員管理</div>
                <a href="member.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>会員一覧</span>
                </a>
                <a href="memberplayhistory.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>会員別プレイ履歴</span>
                </a>
                <a href="owner.php" class="nav-item">
                    <span class="nav-icon">👔</span>
                    <span>オーナー管理</span>
                </a>
                <a href="admin.php" class="nav-item">
                    <span class="nav-icon">🔐</span>
                    <span>管理者設定</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">マシン管理</div>
                <a href="machines.php" class="nav-item">
                    <span class="nav-icon">🎰</span>
                    <span>台管理</span>
                </a>
                <a href="model.php" class="nav-item">
                    <span class="nav-icon">📱</span>
                    <span>機種管理</span>
                </a>
                <a href="maker.php" class="nav-item">
                    <span class="nav-icon">🏢</span>
                    <span>メーカー管理</span>
                </a>
                <a href="corner.php" class="nav-item">
                    <span class="nav-icon">🏪</span>
                    <span>コーナー管理</span>
                </a>
                <a href="machine_control.php" class="nav-item">
                    <span class="nav-icon">🎮</span>
                    <span>台制御</span>
                </a>
                <a href="moniter.php" class="nav-item">
                    <span class="nav-icon">📺</span>
                    <span>モニター</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">カメラ・配信</div>
                <a href="camera.php" class="nav-item">
                    <span class="nav-icon">📹</span>
                    <span>カメラ管理</span>
                </a>
                <a href="camera_settings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>カメラ割当</span>
                </a>
                <a href="signaling.php" class="nav-item">
                    <span class="nav-icon">📡</span>
                    <span>シグナリング</span>
                </a>
                <a href="streaming.php" class="nav-item">
                    <span class="nav-icon">📺</span>
                    <span>ストリーミング</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">ポイント管理</div>
                <a href="pointgrant.php" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>ポイント付与</span>
                </a>
                <a href="pointhistory.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>ポイント履歴</span>
                </a>
                <a href="pointconvert.php" class="nav-item">
                    <span class="nav-icon">🔄</span>
                    <span>ポイント変換</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">売上・購入</div>
                <a href="sales.php" class="nav-item">
                    <span class="nav-icon">💵</span>
                    <span>売上管理</span>
                </a>
                <a href="purchase.php" class="nav-item">
                    <span class="nav-icon">🛒</span>
                    <span>購入管理</span>
                </a>
                <a href="purchasehistory.php" class="nav-item">
                    <span class="nav-icon">📜</span>
                    <span>購入履歴</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">プレイ履歴</div>
                <a href="playhistory.php" class="nav-item">
                    <span class="nav-icon">🎮</span>
                    <span>プレイ履歴</span>
                </a>
                <a href="search.php" class="nav-item">
                    <span class="nav-icon">🔍</span>
                    <span>検索</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">商品・発送</div>
                <a href="goods.php" class="nav-item">
                    <span class="nav-icon">🎁</span>
                    <span>商品管理</span>
                </a>
                <a href="goods_status.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>商品ステータス</span>
                </a>
                <a href="goods_drawpick.php" class="nav-item">
                    <span class="nav-icon">🎯</span>
                    <span>抽選ピック</span>
                </a>
                <a href="drawhistory.php" class="nav-item">
                    <span class="nav-icon">🎲</span>
                    <span>抽選履歴</span>
                </a>
                <a href="gift.php" class="nav-item">
                    <span class="nav-icon">🎀</span>
                    <span>ギフト管理</span>
                </a>
                <a href="gifthistory.php" class="nav-item">
                    <span class="nav-icon">📖</span>
                    <span>ギフト履歴</span>
                </a>
                <a href="giftaddset.php" class="nav-item">
                    <span class="nav-icon">➕</span>
                    <span>ギフト追加設定</span>
                </a>
                <a href="giftlimit.php" class="nav-item">
                    <span class="nav-icon">⏱️</span>
                    <span>ギフト制限</span>
                </a>
                <a href="shipping.php" class="nav-item">
                    <span class="nav-icon">🚚</span>
                    <span>発送管理</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">コンテンツ</div>
                <a href="notice.php" class="nav-item">
                    <span class="nav-icon">📢</span>
                    <span>お知らせ</span>
                </a>
                <a href="magazine.php" class="nav-item">
                    <span class="nav-icon">📰</span>
                    <span>マガジン</span>
                </a>
                <a href="coupon.php" class="nav-item">
                    <span class="nav-icon">🎫</span>
                    <span>クーポン</span>
                </a>
                <a href="benefits.php" class="nav-item">
                    <span class="nav-icon">🌟</span>
                    <span>特典</span>
                </a>
                <a href="address.php" class="nav-item">
                    <span class="nav-icon">📍</span>
                    <span>住所管理</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">システム</div>
                <a href="system.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>システム設定</span>
                </a>
                <a href="image_upload.php" class="nav-item">
                    <span class="nav-icon">🖼️</span>
                    <span>画像アップロード</span>
                </a>
                <a href="api_keys_manage.php" class="nav-item">
                    <span class="nav-icon">🔑</span>
                    <span>APIキー管理</span>
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
                <a href="menu.php" class="header-btn" style="background: #10b981;">📋 全メニュー</a>
            </div>
        </header>

        <!-- コンテンツ -->
        <div class="content-wrapper">
            <!-- アラート -->
            <?php if ($goodsCounts["wait_goods"] > 0): ?>
            <div class="alert-card warning">
                <span style="font-size: 24px;">⚠️</span>
                <div>
                    <strong>抽選待ちの商品があります</strong>
                    <div style="font-size: 14px; opacity: 0.8;">抽選対象: <?= number_format($goodsCounts["wait_goods"]) ?>件</div>
                </div>
                <a href="goods.php" class="btn btn-danger" style="margin-left: auto;">確認する</a>
            </div>
            <?php endif; ?>

            <?php if ($winCounts["wait_send"] > 0): ?>
            <div class="alert-card">
                <span style="font-size: 24px;">📦</span>
                <div>
                    <strong>発送待ちの商品があります</strong>
                    <div style="font-size: 14px; opacity: 0.8;">発送対象: <?= number_format($winCounts["wait_send"]) ?>件</div>
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
                    <div class="stat-sublabel">昨日: +<?= number_format($joinCounts["join_y"]) ?>名</div>
                </div>

                <div class="stat-card orange fade-in" style="animation-delay: 0.2s;">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">¥<?= number_format($amountCounts["amount_value_y"]) ?></div>
                    <div class="stat-label">昨日の売上</div>
                    <div class="stat-sublabel">当月: ¥<?= number_format($amountCounts["amount_value_m"]) ?></div>
                </div>

                <div class="stat-card red fade-in" style="animation-delay: 0.3s;">
                    <div class="stat-icon">🎮</div>
                    <div class="stat-value"><?= number_format($playCounts["play_count_y"]) ?></div>
                    <div class="stat-label">昨日のゲーム数</div>
                    <div class="stat-sublabel">当月: <?= number_format($playCounts["play_count_m"]) ?>回</div>
                </div>
            </div>

            <!-- 詳細情報 -->
            <div class="grid grid-2">
                <!-- 会員情報 -->
                <div class="info-card fade-in" style="animation-delay: 0.4s;">
                    <h3>📊 会員登録状況</h3>
                    <div class="info-row">
                        <span class="info-label"><?= date("m/d", $ydTimestamp) ?> (<?= $GLOBALS["weekList"][date('w', $ydTimestamp)] ?>)</span>
                        <span class="info-value">+<?= number_format($joinCounts["join_y"]) ?>名</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">当月 (<?= date("Y/m", strtotime($this_month)) ?>)</span>
                        <span class="info-value">+<?= number_format($joinCounts["join_m"]) ?>名</span>
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
                        <span class="info-value">¥<?= number_format($amountCounts["amount_value_y"]) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">当月 (<?= date("Y/m", strtotime($this_month)) ?>)</span>
                        <span class="info-value">¥<?= number_format($amountCounts["amount_value_m"]) ?></span>
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
                        <span class="info-value"><?= number_format($playCounts["play_count_y"]) ?>回</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">当月 (<?= date("Y/m", strtotime($this_month)) ?>)</span>
                        <span class="info-value"><?= number_format($playCounts["play_count_m"]) ?>回</span>
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
                        <span class="info-value"><?= number_format($goodsCounts["in_goods"]) ?>件</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">抽選待ち</span>
                        <span class="info-value" style="color: #ef4444;"><?= number_format($goodsCounts["wait_goods"]) ?>件</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">発送待ち</span>
                        <span class="info-value" style="color: #f59e0b;"><?= number_format($winCounts["wait_send"]) ?>件</span>
                    </div>
                    <div style="margin-top: 16px; display: flex; gap: 8px;">
                        <a href="goods.php" class="btn btn-outline" style="flex: 1;">商品管理</a>
                        <a href="shipping.php" class="btn btn-outline" style="flex: 1;">配送管理</a>
                    </div>
                </div>
            </div>

            <!-- クイックアクション -->
            <div class="info-card fade-in" style="animation-delay: 0.8s; margin-top: 24px;">
                <h3><span>⚡</span> クイックアクション</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 16px;">
                    <a href="machines.php" class="btn btn-outline">🎰 マシン管理</a>
                    <a href="member.php" class="btn btn-outline">👥 会員管理</a>
                    <a href="pointgrant.php" class="btn btn-outline">💰 ポイント付与</a>
                    <a href="goods.php" class="btn btn-outline">🎁 商品管理</a>
                    <a href="playhistory.php" class="btn btn-outline">📊 プレイ履歴</a>
                    <a href="shipping.php" class="btn btn-outline">📦 配送管理</a>
                    <a href="sales.php" class="btn btn-outline">💵 売上管理</a>
                    <a href="menu.php" class="btn btn-outline">🗂️ 全メニュー</a>
                </div>
            </div>

            <!-- フッター情報 -->
            <div style="margin-top: 32px; padding: 16px; text-align: center; color: #64748b; font-size: 14px;">
                <p>最終更新: <?= date("Y/m/d H:i:s") ?> | NET8 Management System v2.0</p>
            </div>
        </div>
    </main>
    <script>
        // クリック問題修正スクリプト
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔧 管理画面クリック問題修正スクリプト開始');
            
            function fixClickIssues() {
                // 1. 全ての要素のpointer-eventsを確認・修正
                const elements = document.querySelectorAll('a, button, .btn, .nav-item, .stat-card, .info-card');
                elements.forEach(el => {
                    el.style.pointerEvents = 'auto';
                    el.style.cursor = 'pointer';
                    if (window.getComputedStyle(el).position === 'static') {
                        el.style.position = 'relative';
                    }
                    el.style.zIndex = '10';
                });
                
                // 2. オーバーレイ要素の検索・削除
                const overlays = document.querySelectorAll('.overlay, .backdrop, .modal-backdrop');
                overlays.forEach(overlay => {
                    overlay.style.display = 'none';
                    overlay.style.pointerEvents = 'none';
                });
                
                // 3. 画面全体を覆う可能性のある要素をチェック
                const fullScreenElements = Array.from(document.querySelectorAll('*')).filter(el => {
                    const rect = el.getBoundingClientRect();
                    const style = window.getComputedStyle(el);
                    return (
                        style.position === 'fixed' && 
                        (rect.width >= window.innerWidth * 0.8 || rect.height >= window.innerHeight * 0.8) &&
                        style.zIndex > 1000
                    );
                });
                
                fullScreenElements.forEach(el => {
                    if (!el.classList.contains('sidebar')) {
                        console.warn('⚠️ 全画面要素を発見、z-indexを調整:', el);
                        el.style.zIndex = '1';
                    }
                });
                
                console.log('✅ クリック問題修正完了:', elements.length + '個の要素を修正');
            }
            
            // 即座に実行
            fixClickIssues();
            
            // アニメーション完了後に再実行
            setTimeout(fixClickIssues, 1000);
            
            // 定期的に監視・修正
            setInterval(fixClickIssues, 5000);
        });
    </script>
</body>
</html>
<?php
}
?>
