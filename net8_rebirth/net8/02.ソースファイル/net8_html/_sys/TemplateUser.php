<?php
/*
 * TemplateUser.php
 * 
 * (C)SmartRams Corp. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * ユーザ系表示コントロール処理クラス
 * 
 * ユーザ系表示コントロールの処理を行なう
 * 
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since	 2016/08/30 初版作成 岡本静子
 * @since	 2020/06/24 修正     村上俊行 Notice対応
 * @since	 2021/04/30 修正     岡本静子 機種カテゴリ表示制御追加
 * @info
 */

// 定数定義
define("HTML_ENCODING" , "UTF-8");		// テンプレート記述基準エンコーディング

class TemplateUser extends SmartTemplate {

	// メンバ変数定義
	public $DB;					// DBクラス
	public $Session;			// セッションクラス
	public $Self;				// 自身のURL
	private $_basedir = "";		// テンプレートファイル配置基本ディレクトリ
	private $_enc = "";			// テンプレートファイル記述文字エンコード
	private $_count = 0;		// 置換回数

	/**
	 * コンストラクタ
	 * @access	public
	 * @param   boolean		$isReturn		セッションID名が存在しない場合にリダイレクトさせるか
	 * @param   string		$redirectUrl	セッション消失時表示URL(指定無し時はログイン画面)
	 * @param	boolean		$isRegenerate	セッションIDを新しく生成したものと置き換えるか否か(セッション存在時のみ対象)
	 * @param	string		$encode			テンプレート記述文字エンコーディング
	 * @return	インスタンス
	 */
	public function __construct($isReturn = true, $redirectUrl = "", $isRegenerate = false, $encode = HTML_ENCODING) {

		// キャッシュコントロール
		header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
		header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// 画面テンプレートインスタンス生成
		$this->_enc = $encode;
		parent::__construct(DIR_HTML, $encode, true);
		parent::setAccessTypeDir(TYPE_PC           , DIR_HTML);
		parent::setAccessTypeDir(TYPE_SMART_PHONE  , DIR_HTML);
		$this->_basedir = parent::getBaseDir();					// 基本ディレクトリパス取得

		// DB接続
		$this->DB = new NetDB();

		// 自スクリプト名取得
		$this->Self = get_self();

		// ログイン時の遷移先を保持
		// 2020-08-03 chatplugin.php を追加
		if ($this->Self != "login.php" && $this->Self != "logout.php" && $this->Self != "authMemberMoble.php" && $this->Self != "sendGift.php" && $this->Self != "chatplugin.php") {
			//-- cookieのドメインを指定するとWindows Safariで上手く動かないので注意
			setcookie("login_transfer", substr($_SERVER["REQUEST_URI"], 1), 0, "/");

			// 遷移元がLogin動作であるか判定
			if (isset($_COOKIE["source_transfer"])) 
					$this->_trans = ($_COOKIE["source_transfer"] == "*****");
			setcookie("source_transfer", "", 0, "/");
		}

		// 多言語設定 2020-01-08 T.Murakami
		if ( isset($_COOKIE["LANG"]) && isset($GLOBALS["langList"]) && array_key_exists($_COOKIE["LANG"], $GLOBALS["langList"]) ){
			setcookie("LANG", FOLDER_LANG, 0, "/");
		} else {
			setcookie("LANG", FOLDER_LANG, 0, "/");
		}

		$url = (mb_strlen($redirectUrl) > 0) ? $redirectUrl : URL_SSL_SITE . "login.php";
		// セッションインスタンス生成
		$this->Session = new SmartSession($url, SESSION_SEC, SESSION_SID, DOMAIN, $isReturn);
		$firstSession = $this->Session->start();

		if (!$firstSession) {
			// セッション時間のチェック
			$ret = $this->Session->check();
			// セッション時間エラー時はセッションを全て破棄する
			if (!$ret) $this->Session->clear($isReturn);
		}
	}

	/**
	 * テンプレートファイル読込み（404ページ対応）
	 * @access	public
	 * @param	string	$p_filename		テンプレートファイル名
	 * @param	bool	$is404			ファイルがない場合404ページを表示するか否か
	 * @return	なし
	 * @info
	 */
	public function open($p_filename, $is404 = false) {
		if( $is404){
			try {
				parent::open( $p_filename);
			} catch (Exception $e) {
				header("HTTP/1.1 404 Not Found");
				parent::open( "error404.html");
			}
		}else{
			parent::open( $p_filename);
		}
	}

	/**
	 * セッションユーザチェック
	 * @access	public
	 * @param	boolean		$isClear		エラー時にセッションをクリアするか否か
	 * @param   boolean		$isReturn		セッションID名が存在しない場合にリダイレクトさせるか
	 * @return	boolean		true:チェックOK / false:チェックNG
	 */
	public function checkSessionUser($isClear = false, $isReturn = true) {

		$ret = false;
		if (isset($this->Session->UserInfo)) {
			//$ret = $this->DB->checkUser($this->Session->UserInfo["mail"], $this->Session->UserInfo["pass"]);
			// 20200423 - ポイント関係の更新の為にセッションを再取得処理を追加 by m.kataoka
			if( !empty( $this->Session->UserInfo)){
				//2020-06-24 Notice対応
				$wmail = $this->Session->UserInfo["mail"] ?? "";
				$wpass = $this->Session->UserInfo["pass"] ?? "";
				$row = $this->DB->checkUserReturnPoint($wmail, $wpass);
//				$row = $this->DB->checkUserReturnPoint($this->Session->UserInfo["mail"], $this->Session->UserInfo["pass"]);
				if( !empty($row) ){
					$ret = true;
					$this->Session->UserInfo["point"]      = $row["point"];
					$this->Session->UserInfo["draw_point"] = $row["draw_point"];
				}
				// 表示していない連絡Box件数設定
				$this->SetNotdispContactBox();
			}
		}
		// ユーザチェックNGの場合はユーザのセッションクリア
		if ($isClear && !$ret) $this->Session->clear($isReturn, "UserInfo");

		return $ret;
	}

	/**
	 * システムエラー画面の表示
	 * @access	public
	 * @param	string		$message	メッセージ
	 * @return	なし
	 */
	public function dispProcError($message) {
		$this->open("error.html");
		$this->assignCommon();		// 共通置換
		$this->assign("MESSAGE", $message);
		$this->flush();
	}

	/**
	 * 共通項目の置換
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	public function assignCommon() {
		/*
		頁毎スクリプトパス
			インクルードさせたいので先に置換しています
			インクルードファイル内では使用しないで下さい
		*/
		$this->assign("PAGESCRIPT", DIR_HTML_SCRIPT);
		// インクルードファイル置換
		$this->replaceInclude();

		// サイト定数置換
		$this->assign("SITE_DOMAIN"  , DOMAIN);						// ドメイン
		$this->assign("SITE_URL"     , URL_SITE);					// サイトURL
		$this->assign("SITE_SSL_URL" , URL_SSL_SITE);				// サイトURL(SSL付与)
		$this->assign("SITE_TITLE"   , SITE_TITLE);					// サイト名
		$this->assign("COPYRIGHT"    , COPYRIGHT);					// コピーライト
		$this->assign("DEFAULT_LANG" , DEFAULT_LANG);				// デフォルト言語
		$this->assign("CLIENT_CODE"  , CLIENT_CODE);				// 顧客コード

		// インクルード日時
		if ( DOMAIN == DOMAIN_DEVELOPMENT ) {	// 開発環境
			$sysDate = date("Ymd");
		} else {
			$sysDate = date("YmdHi");
		}
		$this->assign("SYS_VERDATE", $sysDate, false);

		// 共通情報
		$cssPath = DIR_BASE . "data/css/styles_v" . CLIENT_CODE . ".css";
		$this->assign("CSS_MTIME", file_exists($cssPath) ? filemtime($cssPath) : time());	// styles.cssの更新日時
		$this->assign("SELF"     , $this->Self);									// 自スクリプト名
		$this->assign("SSL_SELF" , URL_SSL_SITE . $this->Self);						// 自スクリプト名(SSL付与)
		$queryString = $_SERVER["QUERY_STRING"] ?? "";
		$this->assign("ACTION"   , $this->Self . ((mb_strlen($queryString) > 0)
												? "?" . htmlspecialchars($queryString) : ""));		// 自URL
		if( isset($this->Session->UserInfo)){
			$this->assign("LOGIN_USER"  , $this->Session->UserInfo["nickname"], true);
			$this->assign("USER_PT"     , number_format( $this->Session->UserInfo["point"]), true);
			$this->assign("USER_DRAW_PT", number_format( $this->Session->UserInfo["draw_point"]), true);
			// 連絡Box表示していない件数
			$dspCnt = (isset($this->Session->UserInfo["NotdispContactCnt"]) ? $this->Session->UserInfo["NotdispContactCnt"] : 0);
			$this->assign("NOTDISP_CONTACTCNT", $dspCnt, true);
			$this->if_enable("NOTDISP_CONTACT", $dspCnt > 0);
		}
		
		$this->assign("GLOBAL_OPEN_TIME" , GLOBAL_OPEN_TIME, true);
		$this->assign("GLOBAL_CLOSE_TIME", GLOBAL_CLOSE_TIME, true);
		
		// 表示ポイント系単位
		foreach($GLOBALS["viewUnitList"] as $key => $val ){
			$this->assign("CURRENCY_" . $key, $val, true);
		}
		
		// 表示制御
		$this->if_enable("LOGIN"         , isset($this->Session->UserInfo));
		$this->if_enable("NON_LOGIN"     , !isset($this->Session->UserInfo));
		$this->if_enable("IS_PRODUCTION" , DOMAIN == DOMAIN_PRODUCTION);	// 本番環境
		$this->if_enable("IS_DEVELOPMENT", DOMAIN != DOMAIN_PRODUCTION);	// 開発環境
		
		// ナビゲーション表示制御（Coin交換）
		$nv_all_hide = true;
		$navListCoinTrade = $GLOBALS["navigationViewListCoinTrade"] ?? [];
		foreach( $navListCoinTrade as $key => $val ){
			$this->if_enable("NAVIGATION_".trim( $key), $val);
			if( $val ) $nv_all_hide = false;
		}
		$this->if_enable("COIN_TRADE" , !$nv_all_hide);

		// ナビゲーション表示制御（マイページ）
		$nv_all_hide = true;
		$navListMyPage = $GLOBALS["navigationViewListMyPage"] ?? [];
		foreach( $navListMyPage as $key => $val ){
			$this->if_enable("NAVIGATION_".trim( $key), $val);
			if( $val ) $nv_all_hide = false;
		}
		$this->if_enable("NAVIGATION_MYPAGE" , !$nv_all_hide);
		
		// ギフト送信制御
		if ($this->exist_block("NAVIGATION_gift_CONTROL")) {
			$this->block_start("NAVIGATION_gift_CONTROL");
			$isPossible = false;	// 不可
			$minPoint = 0;			// 最小ポイント ※0は無制限
			if (isset($this->Session->UserInfo)) {		// 表示制御"LOGIN"で消えている筈ですが念の為
				// ギフト送信可能な最小の累計ポイント取得
				$sql = (new SqlString($this->DB))
					->select()
						->field("total_gift_point, gift_limit")
					->from("mst_gift_limit")
					->where()
						->and("del_flg = "   , 0, FD_NUM)
					->orderby("gift_limit asc, total_gift_point asc")
					->createSQL("\n");
				$rs = $this->DB->query($sql);
				while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
					if ($row["gift_limit"] <= 0) $minPoint = $row["total_gift_point"];
					if ($row["gift_limit"] > 0) break;
				}
				unset($rs);
				if ($minPoint > 0) {	// ポイント制限有
					// 会員ギフト関連情報取得
					$sql = (new SqlString($this->DB))
						->select()
							->field("member_no, agent_flg, total_gift_point")
						->from("mst_member")
						->where()
							->and("member_no = ", $this->Session->UserInfo["member_no"], FD_NUM)
						->createSQL("\n");
					$memRow = $this->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
					// 可能判定
					if (!empty($memRow["member_no"])) {
						if ((GIFT_AGENT && $memRow["agent_flg"] == 1) ||	// ギフトエージェントのエージェント 若しくは 
							($memRow["total_gift_point"] > $minPoint)) {	// 累積ポイントが最小ポイントを超えている
							$isPossible = true;
						}
					}
				} else {	// ポイント制限無
					$isPossible = true;
				}
			}
			// 可能ポイント
			if ($minPoint > 0) $minPoint += 1;
			$this->assign("POSSIBLE_POINT"    , number_formatEx($minPoint), true);
			// 表示制御
			$this->if_enable("gift_POSSIBLE"  , $isPossible);	// 可能
			$this->if_enable("gift_IMPOSSIBLE", !$isPossible);	// 不可能
			$this->block_end("NAVIGATION_gift_CONTROL");
		}
		// ギフト送信制御
		if ($this->exist_block("NAVIGATION_DISP_total_gift_point")) {
			$this->block_start("NAVIGATION_DISP_total_gift_point");
			$totalPoint = 0;
			if (isset($this->Session->UserInfo)) {		// 表示制御"LOGIN"で消えている筈ですが念の為
				// 会員ギフト関連情報取得
				$sql = (new SqlString($this->DB))
					->select()
						->field("member_no, agent_flg, total_gift_point")
					->from("mst_member")
					->where()
						->and("member_no = ", $this->Session->UserInfo["member_no"], FD_NUM)
					->createSQL("\n");
				$memRow = $this->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
				if (!empty($memRow["member_no"])) {
					$totalPoint = $memRow["total_gift_point"];
				}
			}
			$this->assign("NAVI_TOTAL_GIFT_POINT", number_formatEx($totalPoint), true);
			$this->block_end("NAVIGATION_DISP_total_gift_point");
		}
		
		// 言語対応 2020-01-08 T.Murakami
		$lnghtml = "";
		// 【修正】langListが配列でない場合はスキップ
		if (isset($GLOBALS["langList"][FOLDER_LANG]["names"]) && is_array($GLOBALS["langList"][FOLDER_LANG]["names"])) {
			foreach( $GLOBALS["langList"][FOLDER_LANG]["names"] as $lng ){
			if ( strpos($_SERVER['REQUEST_URI'], "?") ){
				if ( strpos($_SERVER['REQUEST_URI'], "LANG=") ){
					$u = preg_replace("/LANG=[a-z]+/", "LANG={$lng["lang"]}", $_SERVER['REQUEST_URI']);
					$lnghtml .= "<a class=\"dropdown-item\" href=\"{$u}\">{$lng["name"]}</a>";
				} else {
					$lnghtml .= "<a class=\"dropdown-item\" href=\"{$_SERVER['REQUEST_URI']}&LANG={$lng["lang"]}\">{$lng["name"]}</a>";
				}
			} else {
				$lnghtml .= "<a class=\"dropdown-item\" href=\"{$_SERVER['REQUEST_URI']}?LANG={$lng["lang"]}\">{$lng["name"]}</a>";
			}
		}
		} // end if langList check
		$this->assign("LANGLIST"  , $lnghtml);
		
		// Navigation 言語表示制御
		$langCount = isset($GLOBALS["langList"][FOLDER_LANG]["names"]) && is_array($GLOBALS["langList"][FOLDER_LANG]["names"])
		             ? count($GLOBALS["langList"][FOLDER_LANG]["names"]) : 0;
		$this->if_enable( "DISP_LANGLIST", $langCount > 1);
		
		// Footer表示制御
		foreach( $GLOBALS["footerviews"] as $k => $flg ){
			$this->if_enable( "FOOTER_DISP_".$k, $flg == 1);
		}
		
		// メッセージ置換
		$this->replaceMessage();

	}

	/**
	 * 台一覧取得基本部分生成
	 * @access	public
	 * @param	SqlString	$sqls		SqlStringクラスオブジェクト
	 * @param	boolean		$isTester	テスターか否か
	 * @return	なし
	 */
	public function SearchMachineBase(&$sqls, $isTester = false) {
		$toDay = GetRefTimeTodayExt();	// 基準時間＞使用開始時間の当日
		$yesterday = new DateTime($toDay);
		$yesterday->modify("-1 day");

		$sqls->select()
				->from("dat_machine dm")
				->from("inner join dat_machinePlay dmp on dmp.machine_no = dm.machine_no" )							//実機プレイデータ
				->from("inner join lnk_machine lm on lm.machine_no = dm.machine_no" )								//実機接続状況
				->from("inner join mst_model mm on mm.model_no = dm.model_no and mm.del_flg <>'1'" )				//機種マスタ
				->from("inner join mst_maker ma on ma.maker_no = mm.maker_no and ma.del_flg <>'1'" )				//メーカーマスタ
				->from("left join mst_unit  mu on mu.unit_no  = mm.unit_no and mu.del_flg <>'1'" )					//号機マスタ
				->from("inner join mst_type mt on mt.type_no = mm.type_no and mt.del_flg <>'1'" )					//タイプマスタ
				->from("inner join mst_convertPoint mcp on mcp.convert_no = dm.convert_no and mcp.del_flg <>'1'" )	//ポイント返還マスタ
				->from("left join his_machinePlay hmp on hmp.machine_no = dm.machine_no and hmp.play_dt = "
								 . $this->DB->conv_sql($yesterday->format("Y/m/d"), FD_DATE))							//実機プレイ履歴
			->where()
				/* フラグ系 */
				->and("dm.camera_no IS NOT NULL")
				->and("dm.del_flg <> ", "1", FD_NUM)
				// 公開開始日
				->and("dm.release_date <= ", $toDay, FD_DATE)
				// 公開終了日
				->and("dm.end_date >= ", $toDay, FD_DATE);
		if (!$isTester) {
			$sqls->where()->and(false, "dm.machine_status <> ", "0", FD_NUM);
		}
	}

	/**
	 * 台一覧取得フィールド
	 * @access	public
	 * @param	SqlString	$sqls		SqlStringクラスオブジェクト
	 * @return	なし
	 */
	public function SearchMachineField(&$sqls) {
		$sqls->resetField()
			->field("dm.machine_no, dm.machine_cd, dm.model_no, dm.release_date, dm.machine_status")									//実機
			->field("mm.category, mm.model_name, mm.model_roman, mm.model_cd, mm.maker_no, mm.type_no, mm.unit_no, mm.prizeball_data")	//機種マスタ
			->field("mm.image_list, mm.image_detail, mm.image_reel")																	//機種マスタ（画像）
			->field("mu.unit_name, mu.unit_roman,ma.maker_no, ma.maker_name, ma.maker_roman, mcp.point, mcp.credit, mcp.draw_point, mt.type_name, mt.type_roman")	//それ以外のマスタ
			->field("lm.assign_flg, lm.member_no")																						//状況系
			->field("dmp.total_count, dmp.count, dmp.bb_count, dmp.rb_count, dmp.hit_data")												//情報系
			->field("(IFNULL(hmp.bb_count, 0) + IFNULL(hmp.rb_count, 0)) as his_bonus");												//前日bonus
	}

	/**
	 * 台一覧置換
	 * @access	public
	 * @param	object		$rs			結果セット
	 * @param	boolean		$isOpen		営業時間か否か
	 * @param	boolean		$isLogin	ログイン状態か否か
	 * @param	boolean		$isTester	テスターか否か
	 * @return	なし
	 */
	public function AssignMachineList($rs, $isOpen, $isLogin, $isTester) {
		// 台リスト
		$this->loop_start("LIST");
		while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
			// 実機基本データ
			$this->assign("NO"               , $row["machine_no"], true);
			$this->assign("MACHINE_CD"       , $row["machine_cd"], true);
			$this->assign("MAKER_NO"         , $row["maker_no"], true);
			$this->assign("MAKER_NAME"       , (FOLDER_LANG==DEFAULT_LANG)? $row["maker_name"]:$row["maker_roman"], true);
			$this->assign("MODEL_NO"         , $row["model_no"], true);
			$this->assign("MODEL_NAME"       , (FOLDER_LANG==DEFAULT_LANG)? $row["model_name"]:$row["model_roman"], true);
			$this->assign("GENERATION"       , (FOLDER_LANG==DEFAULT_LANG)? $row["unit_name"]:$row["unit_roman"], true);
			//2020-06-24 Notice対応
			$this->assign("CATEGORY"         , (in_array($row['category'],$GLOBALS["categoryList"])) ? $GLOBALS["categoryList"][ $row['category']] : "", true);
			$this->assign("TYPELABEL"        , (FOLDER_LANG==DEFAULT_LANG)? $row["type_name"]:$row["type_roman"], true);
			//
			if( $row['category'] == 1){
				//jsonデータ分解
				$_json = json_decode( $row['prizeball_data'], true);
				if( array_key_exists( FOLDER_LANG, $GLOBALS["unitLangList"])){
					$_navel_lang = FOLDER_LANG;
				}else{
					$_navel_lang = DEFAULT_LANG;
				}
				$this->assign("NAVEL_LABEL", "1". $GLOBALS["unitLangList"][$_navel_lang]["5"] ." ". abs( $_json['NAVEL']) . $GLOBALS["unitLangList"][$_navel_lang]["4"], true);
				// 2021/04/30 Add S by S.Okamoto 機種カテゴリ表示制御追加
				$this->if_enable("IS_PACHI" , true);	// パチンコ
				$this->if_enable("IS_SLOT"  , false);	// スロット
				// 2021/04/30 Add E
			}else{
				$this->assign("NAVEL_LABEL", "");
				// 2021/04/30 Add S by S.Okamoto 機種カテゴリ表示制御追加
				$this->if_enable("IS_PACHI" , false);	// パチンコ
				$this->if_enable("IS_SLOT"  , true);	// スロット
				// 2021/04/30 Add E
			}
			
			// 画像
			$this->assign("DIR_IMG_MODEL_DIR", defined('DIR_IMG_MODEL_DIR') ? DIR_IMG_MODEL_DIR : (defined('DIR_IMG_MODEL') ? DIR_IMG_MODEL : ''), true);		// 機材画像表示用パス
			$this->assign("IMAGE_LIST"       , $row["image_list"], true);
			$this->assign("IMAGE_DETAIL"     , $row["image_detail"], true);
			// 単位
			$this->assign("LABEL_1"          , $GLOBALS["viewUnitList"]["1"], true);
			$this->assign("LABEL_2"          , ($row['category']==1)? $GLOBALS["viewUnitList"]["4"]:$GLOBALS["viewUnitList"]["2"], true);
			$this->assign("LABEL_3"          , $GLOBALS["viewUnitList"]["3"], true);
			// 各種情報
			$this->assign("CATEGORY_NO"      , $row['category'], true);
			$this->assign("HITDATA"          , $row['hit_data'], true);
			$this->assign("TOTAL_GAME_TIMES" , $row['total_count'], true);
			$this->assign("GAME_TIMES"       , $row['count'], true);
			$this->assign("BIG_TIMES"        , $row['bb_count'], true);
			$this->assign("REG_TIMES"        , $row['rb_count'], true);
			$this->assign("PLAYPOINT"        , number_format( $row["point"]), true);
			$this->assign("CREDIT"           , number_format( $row["credit"]), true);
			$this->assign("DRAW_POINT"       , number_format( $row["draw_point"]), true);
			$this->assign("HIS_BONUS"        , $row['his_bonus'], true);		// 前日bonus

			// プレイ状況
			$assignFlg = $row['assign_flg'];	// 実機接続状況
			if ($isLogin && $assignFlg == 1 && $row["member_no"] == $this->Session->UserInfo["member_no"]) $assignFlg = 0;	// 自身への割当は未割り当て扱い
			$isLinkMainte = ($assignFlg == 9);	// 実機接続状況：メンテ中
			$this->if_enable("CLOSED" , !$isOpen);	// 営業時間外
			$this->if_enable("IS_OPEN", $isOpen);	// 営業時間内
			$this->if_enable("AVAIABLE"        , ($assignFlg == 0));	// 実機接続状況：未割当
			$this->if_enable("NOAVAIABLE"      , ($assignFlg == 1));	// 実機接続状況：割当済
			$this->if_enable("LINK_MAINTENANCE", $isLinkMainte);		// 実機接続状況：メンテ中
			$this->if_enable("IN_PREPARATION"  , ($row["machine_status"] == 0 && !$isLinkMainte));	// 実機：準備中
			$this->if_enable("IS_NORMAL"       , ($row["machine_status"] == 1 && !$isLinkMainte));	// 実機：通常
			$this->if_enable("MAINTENANCE"     , ($row["machine_status"] == 2 && !$isLinkMainte));	// 実機：メンテ中
			$playStatus = 2;
			if ($isTester) {	// テスター
				// 2：メンテ中(接続状況)、1：時間外、0：通常
				$playStatus = (($isLinkMainte) ? 2 : ((!$isOpen) ? 1 : 0));
			} else {	// 一般
				// 1：時間外、2：メンテ中(実機、接続状況)、0：通常
				$playStatus = ((!$isOpen) ? 1 : (($isLinkMainte || $row["machine_status"] != "1") ? 2 : 0));
			}
			$this->assign("ASSIGN"        , $assignFlg, true);				// 実機割当状況
			$this->assign("PLAY_STATUS"   , $playStatus, true);
			$this->assign("MACHINE_STATUS", $row["machine_status"], true);	// 実機状態
			$this->assign("TESTER"        , ($isTester) ? 1 : 0, true);	// テスター

			//
			$this->loop_next();
		}
		$this->loop_end("LIST");
		//--- 2020/06/25 Add S by S.Okamoto テスターか否かの表示制御(テストは実機がどの状態でもプレイ可能にする)
		$this->if_enable("TESTER"    , $isTester);
		$this->if_enable("NON_TESTER", !$isTester);
		//--- 2020/06/25 Add E
	}

	/**
	 * 表示していない連絡Box件数設定
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	public function SetNotdispContactBox() {
		// セッション未設定は何もしない
		if (!isset($this->Session->UserInfo) || !isset($this->Session->UserInfo["member_no"])) return;
		// 対象件数取得
		$getCnt = $this->DB->getNotdispContactBox($this->Session->UserInfo["member_no"]);
		// セッションに保持
		$this->Session->UserInfo["NotdispContactCnt"] = $getCnt;
	}

	/**
	 * インクルードファイルの置換
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	function replaceInclude() {

		$str = $this->get();
		preg_match_all("/(<!--#include file=\")([a-z0-9\_\.\/^\"]+)(\"-->)/i",
															$str, $array, PREG_SET_ORDER);
		foreach ($array as $match) {
			if (file_exists($this->_basedir . $match[2])) {
				$file = file_get_contents($this->_basedir . $match[2]);		// ファイル内容取得
			} else {
				$file = "";
			}
			// テンプレート記述文字エンコードがPHP処理エンコードと違う場合は文字エンコードを変換
			// PHP 8.1+ 対応: mb_internal_encoding()を使用
			$internal_encoding = mb_internal_encoding() ?: 'UTF-8';
			if ($this->_enc && $this->_enc != $internal_encoding) {
				// 記述エンコード→PHP処理エンコード
				$file = mb_convert_encoding($file, $internal_encoding, $this->_enc);
			}
			$str = preg_replace("/(<!--#include file=\"".preg_quote($match[2], "/")."\"-->)/", $file ,$str);
		}
		$this->replace($str);
	}

	/**
	 * メッセージの置換
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	function replaceMessage() {
		// メッセージ置換
		foreach ($GLOBALS["setMmessage"] as $key => $value) {
			if (mb_strpos($key, "U") !== false) $this->assign($key , $value);
		}
	}

	/**
	 * メッセージ取得処理
	 * @access	private
	 * @param	string	$code		// メッセージコード
	 * @param	string	$param		// パラメータ(複数指定の場合は配列渡し)
	 * @param	string	$add		// メッセージ付加文字列
	 * @return	string	メッセージ文字列
	 */
	function message($code, $param = "", $add = "") {
		global $setMmessage;

		$ret = ((array_key_exists($code, $setMmessage)) ? $setMmessage[$code] : $setMmessage["DEFAULT"]) . $add;
		if (!is_array($param)) {
			$ret = str_replace("{0}", $param, $ret);
		} else {
			foreach ($param as $key => $value) {
				if (mb_strpos($ret, "{" . $key . "}") !== false) 
					$ret = str_replace("{" . $key . "}", $value, $ret);
			}
		}

		return $ret;
	}

	/**
	 * キーワードの置換
	 * @access	public
	 * @param	string	$key			置換キーワード名
	 * @param	string	$value			置換内容
	 * @param	boolean	$html_entity	置換内容の特殊文字をHTMLエンティティに変換するかどうか
	 * @param	boolean	$nl2br			置換内容の改行タグを挿入するかどうか
	 * @return	なし
	 */
	function assign($key, $value, $html_entity = false, $nl2br = false) {

		$newValue = ($html_entity) ? htmlspecialchars($value) : $value;
		if ($nl2br) $newValue = nl2br($newValue);

		// 親クラスの同名関数をCall
		parent::assign($key, $newValue);
	}


	/**
	 * 配列値の取得
	 * @access	public
	 * @param	array	$target		対象配列
	 * @param	mixed	$key		配列キー
	 * @return	string	異常：空文字 / 正常：配列値
	 */
	function getArrayValue($target, $key) {
		if (!array_key_exists($key, $target)) return "";
		return $target[$key];
	}

	/**
	 * ランダム文字列生成 (英数字)
	 * @access	public
	 * @param	int		$length		生成する文字数
	 * @return	string	生成した文字列
	 */
	function makeRandStr($length = 8) {
		static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
		$str = '';
		for ($i = 0; $i < $length; ++$i) {
			$str .= $chars[mt_rand(0, 61)];
		}
		return $str;
	}

	/**
	 * デストラクタ
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	public function __destruct() {
	}
}
?>