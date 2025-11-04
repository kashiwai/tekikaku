<?php
/*
 * address_add.php
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
 * 宛先新規作成画面表示
 * 
 * 宛先新規作成画面の表示/登録を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/08 初版作成 片岡 充
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル
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
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		$template->checkSessionUser(true, true);

		// データ取得
		getData($_GET, array("M", "NO"));
		
		switch ($_GET["M"]) {
			case "change":			// 宛先変更処理
				ChangeData($template);
				break;
			case "modify":			// 登録編集処理
				ModData($template);
				break;
			case "new":				// 新規登録処理
				ProcData($template);
				break;
			case "del":				// 宛先削除処理
				DeleteData($template);
				break;
			default:				// 入力画面
				DispInput($template);
		}
		
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 宛先削除処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function DeleteData($template, $message = "") {
	// 入力チェック
	$errMessage = (new SmartAutoCheck($template))->item($_GET["NO"])->required("U0003")->number("U0003")->report();
	$message = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	if (mb_strlen($message) > 0) {
		header("Location: address.php");
		return;
	}
	// トランザクション開始
	$template->DB->autoCommit(false);
	//
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "dat_address" )
			->set()
				->value( "del_flg"           , 1, FD_NUM)
				->value( "del_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "del_dt"            , "current_timestamp", FD_FUNCTION)
				->value( "upd_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
			->where()
				->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "seq = "        , $_GET["NO"], FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	//既存のどれかを選択宛先にする？
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("dat_address da")
			->where()
				->and( false, "da.member_no = ",  $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "da.use_flg = ",   "1", FD_NUM)
				->and( false, "da.del_flg <> ",  "1", FD_NUM)
			->createSQL();
	$cnt = $template->DB->getOne($sql);
	
	if( $cnt == 0){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "dat_address" )
				->set()
					->value( "use_flg"           , 1, FD_NUM)
					->value( "upd_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
					->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
				->where()
					->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
					->and( false, "del_flg <> ",  "1", FD_NUM)
				->limit(1)
				->orderby('seq asc')
			->createSQL();
		$template->DB->query($sql);
	}
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	//
	// 完了画面がなかったので、宛先確認画面に移動
	header("Location: address.php");
}

/**
 * 宛先変更処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function ChangeData($template, $message = "") {
	// 入力チェック
	$errMessage = (new SmartAutoCheck($template))->item($_GET["NO"])->required("U0003")->number("U0003")->report();
	$message = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	// トランザクション開始
	$template->DB->autoCommit(false);
	//
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "dat_address" )
			->set()
				->value( "use_flg"      , 0, FD_NUM)
				->value( "upd_dt"       , "current_timestamp", FD_FUNCTION)
				->value( "upd_no"       , $template->Session->UserInfo["member_no"], FD_NUM)
			->where()
				->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "use_flg = "    , "1", FD_NUM)
				->and( false, "del_flg <> ",  "1", FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	//
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "dat_address" )
			->set()
				->value( "use_flg"           , 1, FD_NUM)
				->value( "upd_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
			->where()
				->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "seq = "        , $_GET["NO"], FD_NUM)
				->and( false, "del_flg <> ",  "1", FD_NUM)
		->createSQL();
	$template->DB->query($sql);	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	//
	// 完了画面がなかったので、宛先確認画面に移動
	header("Location: address.php");
	
}

/**
 * 登録編集処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function ModData($template, $message = "") {
	
	// データ取得
	getData($_POST , array("B", "T", "SEQ", "LASTNAME", "FIRSTNAME", "PREF", "POSTAL", "ADDRESS1", "ADDRESS2", "ADDRESS3", "ADDRESS4", "TEL", "USEADDRESS"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	//
	if( $_POST["USEADDRESS"] != 1) $_POST["USEADDRESS"] = 0;
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	if( $_POST["USEADDRESS"] == 1){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "dat_address" )
				->set()
					->value( "use_flg"      , 0, FD_NUM)
					->value( "upd_dt"       , "current_timestamp", FD_FUNCTION)
					->value( "upd_no"       , $template->Session->UserInfo["member_no"], FD_NUM)
				->where()
					->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
					->and( false, "use_flg = "    , "1", FD_NUM)
					->and( false, "del_flg <> ",  "1", FD_NUM)
			->createSQL();
		$template->DB->query($sql);
	}
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "dat_address" )
			->set()
				->value( "syll"              , $_POST["LASTNAME"], FD_STR)
				->value( "name"              , $_POST["FIRSTNAME"], FD_STR)
				->value( "postal"            , $_POST["POSTAL"], FD_STR)
				->value( "address1"          , $_POST["ADDRESS1"], FD_STR)
				->value( "address2"          , $_POST["ADDRESS2"], FD_STR)
				->value( "address3"          , $_POST["ADDRESS3"], FD_STR)
				->value( "address4"          , $_POST["ADDRESS4"], FD_STR)
				->value( "tel"               , $_POST["TEL"], FD_STR)
				->value( "use_flg"           , $_POST["USEADDRESS"], FD_NUM)
				->value( "upd_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
			->where()
				->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "seq = "        , $_POST["SEQ"], FD_NUM)
				->and( false, "del_flg <> ",  "1", FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	if( $_POST["B"] == "select"){
		$seq = explode("-", $_POST["T"])[0];
		$goods_no = explode("-", $_POST["T"])[1];
		header("Location: shipping.php?M=select&SEQ=". $seq ."&NO=". $goods_no);
	}else{
		//宛先確認画面に移動
		header("Location: address.php");
	}
	
}


/**
 * 新規登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function ProcData($template, $message = "") {
	
	// データ取得
	getData($_POST , array("B", "T", "LASTNAME", "FIRSTNAME", "PREF", "POSTAL", "ADDRESS1", "ADDRESS2", "ADDRESS3", "ADDRESS4", "TEL", "USEADDRESS"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	//
	if( $_POST["USEADDRESS"] != 1) $_POST["USEADDRESS"] = 0;
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	//シーケンス番号取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("dat_address da")
			->where()
				->and( false, "da.member_no = ",  $template->Session->UserInfo["member_no"], FD_NUM)
			->createSQL();
	$seqcnt = $template->DB->getOne($sql) + 1;
	
	//件数取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("dat_address da")
			->where()
				->and( false, "da.member_no = ",  $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "da.del_flg = ",  "0", FD_NUM)
			->createSQL();
	$livecnt = $template->DB->getOne($sql) + 1;
	
	// 既に規定件数登録されている場合エラー
	if( $livecnt > KEEP_ADDRESS_LIMIT){
		DispInput($template, $template->message("U1630"));
		return;
	}
	//	0件の場合は強制的に USEADDRESS をチェックした状態の登録にする
	//	既に１件以上登録されている場合、USEADDRESSをチェックしてきた場合、他のuse_flg が 1 のレコード（１件しかない）を 0 に更新する。
	if( $livecnt > 1){
		//既存アリ
		if( $_POST["USEADDRESS"] == 1){
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_address" )
					->set()
						->value( "use_flg"      , 0, FD_NUM)
						->value( "upd_dt"       , "current_timestamp", FD_FUNCTION)
						->value( "upd_no"       , $template->Session->UserInfo["member_no"], FD_NUM)
					->where()
						->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
						->and( false, "use_flg = "    , "1", FD_NUM)
						->and( false, "del_flg <> ",  "1", FD_NUM)
				->createSQL();
			$template->DB->query($sql);
		}
	}else{
		//新規１件目
		$_POST["USEADDRESS"] = 1;
	}
	
	//新規登録
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->insert()
			->into("dat_address" )
				->value( "member_no"         , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "seq"               , $seqcnt, FD_NUM)
				->value( "syll"              , $_POST["LASTNAME"], FD_STR)
				->value( "name"              , $_POST["FIRSTNAME"], FD_STR)
				->value( "postal"            , $_POST["POSTAL"], FD_STR)
				->value( "address1"          , $_POST["ADDRESS1"], FD_STR)
				->value( "address2"          , $_POST["ADDRESS2"], FD_STR)
				->value( "address3"          , $_POST["ADDRESS3"], FD_STR)
				->value( true, "address4"    , $_POST["ADDRESS4"], FD_STR)
				->value( "tel"               , $_POST["TEL"], FD_STR)
				->value( "use_flg"           , $_POST["USEADDRESS"], FD_NUM)
				->value( "upd_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
				->value( "add_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "add_dt"            , "current_timestamp", FD_FUNCTION)
		->createSQL();
	$template->DB->query($sql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	if( $_POST["B"] == "select"){
		$seq = explode("-", $_POST["T"])[0];
		$goods_no = explode("-", $_POST["T"])[1];
		header("Location: shipping.php?M=select&SEQ=". $seq ."&NO=". $goods_no);
	}else{
		//宛先確認画面に移動
		header("Location: address.php");
	}
	
}

/**
 * 入力画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function DispInput($template, $message = "") {
	
	// データ取得
	getData($_GET , array("B", "T"));
	getData($_POST , array("B", "T"));
	getData($_POST , array("SEQ", "LASTNAME", "FIRSTNAME", "POSTAL", "ADDRESS1"
						, "ADDRESS2", "ADDRESS3", "ADDRESS4", "TEL", "USEADDRESS"
							));
	if( mb_strlen($_GET["B"]) > 0){
		$_select_b = $_GET["B"];
		$_select_t = $_GET["T"];
	}else{
		$_select_b = $_POST["B"];
		$_select_t = $_POST["T"];
	}
	
	if (mb_strlen( $_GET["NO"]) > 0) {
		if ( !preg_match("/^[0-9]+$/", $_GET["NO"])) {
			$_GET["NO"] = "";
		}
	}
	
	if (mb_strlen($message) > 0) {
		//エラーの場合
		//	各データはPOST優先、$resist_modeも$_GET["M"]のものに合わせる
		$resist_mode = trim( $_GET["M"]);
	} else {
		//登録モードを確定
		$resist_mode = ($_GET["NO"] != "")? "modify":"new";
		//編集の場合、DBからデータを読み込む
		if ($_GET["NO"] != "") {
			//DB
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("da.seq, da.syll, da.name, da.postal, da.address1, da.address2, da.address3, da.address4, da.tel, da.use_flg")
					->from("dat_address da")
					->where()
						->and( false, "da.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
						->and( false, "da.seq = ",       $_GET["NO"], FD_NUM)
						->and( false, "da.del_flg <> ",  "1", FD_NUM)
				->createSql();
			$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);

			if (empty($row["seq"])) {
				$template->dispProcError($template->message("U0003"));
				return;
			}

			//
			$_POST["SEQ"]        = $row["seq"];
			$_POST["LASTNAME"]   = $row["syll"];
			$_POST["FIRSTNAME"]  = $row["name"];
			$_POST["POSTAL"]     = $row["postal"];
			$_POST["ADDRESS1"]   = $row["address1"];
			$_POST["ADDRESS2"]   = $row["address2"];
			$_POST["ADDRESS3"]   = $row["address3"];
			$_POST["ADDRESS4"]   = $row["address4"];
			$_POST["TEL"]        = $row["tel"];
			$_POST["USEADDRESS"] = $row["use_flg"];
		}
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	
	$template->if_enable("IN_NAV" , mb_strlen($_select_b) == 0);
	$template->if_enable("IN_SHIP", $_select_b == "select");
	if ( mb_strlen($_select_b) > 0){
		$goods_no = explode("-", $_select_t)[1];
		$template->assign("GOODSNO"  , $goods_no, true);
	}
	
	$template->assign("ADDRESS_SYLL_LIMIT"  , ADDRESS_SYLL_LIMIT, true);
	$template->assign("ADDRESS_NAME_LIMIT"  , ADDRESS_NAME_LIMIT, true);
	$template->assign("ADDRESS_POSTAL_LIMIT", ADDRESS_POSTAL_LIMIT, true);
	$template->assign("ADDRESS_1_LIMIT"     , ADDRESS_1_LIMIT, true);
	$template->assign("ADDRESS_2_LIMIT"     , ADDRESS_2_LIMIT, true);
	$template->assign("ADDRESS_3_LIMIT"     , ADDRESS_3_LIMIT, true);
	$template->assign("ADDRESS_4_LIMIT"     , ADDRESS_4_LIMIT, true);
	$template->assign("ADDRESS_TEL_LIMIT"   , ADDRESS_TEL_LIMIT, true);
	
	$template->assign("B"                , $_select_b, true);
	$template->assign("T"                , $_select_t, true);
	
	$template->assign("REGISTTYPE"       , $resist_mode);
	$template->assign("SEQ"              , $_POST["SEQ"], true);
	$template->assign("LASTNAME"         , $_POST["LASTNAME"], true);
	$template->assign("FIRSTNAME"        , $_POST["FIRSTNAME"], true);
	$template->assign("POSTAL"           , $_POST["POSTAL"], true);
	$template->assign("ADDRESS1"         , $_POST["ADDRESS1"], true);
	$template->assign("ADDRESS2"         , $_POST["ADDRESS2"], true);
	$template->assign("ADDRESS3"         , $_POST["ADDRESS3"], true);
	$template->assign("ADDRESS4"         , $_POST["ADDRESS4"], true);
	$template->assign("TEL"              , $_POST["TEL"], true);
	$template->assign("CHK_USEADDRESS"   , ($_POST["USEADDRESS"]==1)? "checked=\"checked\"":"" , true);
	
	// 表示
	$template->flush();
}



/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	
	//確認項目がわからないのでコメントアウト中
	$errMessage = (new SmartAutoCheck($template))
		//名前
		->item($_POST["LASTNAME"])
			->required("U1601")
			->maxLength("U1640", ADDRESS_SYLL_LIMIT)
		//苗字
		->item($_POST["FIRSTNAME"])
			->required("U1602")
			->maxLength("U1641", ADDRESS_NAME_LIMIT)
		//郵便番号
		->item($_POST["POSTAL"])
			->required("U1603")
			->maxLength("U1604", ADDRESS_POSTAL_LIMIT)
			->chk_postal("U1604", false)
		//住所1
		->item($_POST["ADDRESS1"])
			->required("U1611")
			->maxLength("U1642", ADDRESS_1_LIMIT)
		//住所2
		->item($_POST["ADDRESS2"])
			->required("U1612")
			->maxLength("U1643", ADDRESS_2_LIMIT)
		//住所3
		->item($_POST["ADDRESS3"])
			->required("U1613")
			->maxLength("U1644", ADDRESS_3_LIMIT)
		//住所4
		->item($_POST["ADDRESS4"])
			->any()
			->maxLength("U1645", ADDRESS_4_LIMIT)
		//電話番号
		->item($_POST["TEL"])
			->required("U1607")
			->chk_tel("U1608", false)
			->maxLength("U1608", ADDRESS_TEL_LIMIT)
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
