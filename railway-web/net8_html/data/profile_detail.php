<?php
/*
 * profile_detail.php
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
 * プロフィール設定画面表示
 * 
 * プロフィール設定画面の表示を行う
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
		getData($_GET, array("M"));
		
		switch ($_GET["M"]) {
			case "regist":			// 登録処理
				ProcData($template);
				break;
			default:				// 入力画面
				DispInput($template);
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}


/**
 * 登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcData($template) {
	
	// データ取得
	getData($_POST , array("LASTNAME", "FIRSTNAME", "BIRTHYEAR", "BIRTHMONTH", "BIRTHDAY", "SEX", "MAGAZINE"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	
	$birth = "";
	if (mb_strlen($_POST["BIRTHYEAR"]) > 0) $birth = $_POST["BIRTHYEAR"]."-".$_POST["BIRTHMONTH"]."-".$_POST["BIRTHDAY"];
	
	//	プロフ変更処理
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_member" )
			->set()
				->value( "last_name"         , $_POST["LASTNAME"], FD_STR)
				->value( "first_name"        , $_POST["FIRSTNAME"], FD_STR)
				->value( "birthday"          , $birth, FD_STR)
				->value( "sex"               , $_POST["SEX"], FD_NUM)
				->value( "mail_magazine"     , $_POST["MAGAZINE"], FD_NUM)
				->value( "upd_no"            , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
			->where()
				->and(false, "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and(false, "mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and(false, "pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and(false, "state = ", "1", FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	//プロフ確認画面に移動
	header("Location: profile.php");
	
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
	getData($_POST , array("LASTNAME", "FIRSTNAME", "BIRTHYEAR", "BIRTHMONTH", "BIRTHDAY", "SEX", "MAGAZINE" /* ,"BIRTH" */));
	
	if( mb_strlen($message) == 0){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("mm.last_name, mm.first_name, mm.birthday, mm.sex, mm.mail_magazine")
			->from("mst_member mm")
			->where()
				->and(false, "mm.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and(false, "mm.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and(false, "mm.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and(false, "mm.state = ", "1", FD_NUM)
			->createSQL();
		$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		
		if( mb_strlen($row["birthday"]) > 0){
			$birth = new DateTime( $row["birthday"]);
			$_POST["BIRTHYEAR"]  = (int)$birth->format('Y');
			$_POST["BIRTHMONTH"] = (int)$birth->format('n');
			$_POST["BIRTHDAY"]   = (int)$birth->format('j');
		}
		
		$_POST["LASTNAME"]   = $row["last_name"];
		$_POST["FIRSTNAME"]  = $row["first_name"];
		$_POST["SEX"]        = $row["sex"];
		$_POST["MAGAZINE"]   = $row["mail_magazine"];
	}
	
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->assign("PROFILE_SYLL_LIMIT", PROFILE_SYLL_LIMIT, true);
	$template->assign("PROFILE_NAME_LIMIT", PROFILE_NAME_LIMIT, true);
	$template->assign("LASTNAME"         , $_POST["LASTNAME"], true);
	$template->assign("FIRSTNAME"        , $_POST["FIRSTNAME"], true);
	$template->assign("BIRTHYEAR"        , $_POST["BIRTHYEAR"], true);
	$template->assign("SEL_BIRTHMONTH"   , makeSelectMonthTag( $_POST["BIRTHMONTH"], true, SELECT_VALUE_NONE));
	$template->assign("SEL_BIRTHDAY"     , makeSelectDayTag( $_POST["BIRTHDAY"], true, SELECT_VALUE_NONE));
	$template->assign("RDO_SEX"          , makeRadioArray($GLOBALS["SexList"], "SEX", $_POST["SEX"]));
	$template->assign("RDO_MAGAZINE"     , makeRadioArray($GLOBALS["MagazineReadStatus"], "MAGAZINE", $_POST["MAGAZINE"]));
	
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
	
	$errMessage = (new SmartAutoCheck($template))
		//名前
		->item($_POST["LASTNAME"])
			->required("U1501")
			->maxLength("U1510", PROFILE_SYLL_LIMIT)
		//苗字
		->item($_POST["FIRSTNAME"])
			->required("U1502")
			->maxLength("U1511", PROFILE_NAME_LIMIT)
		//誕生年
		->item($_POST["BIRTHYEAR"])
			->case( mb_strlen( trim($_POST["BIRTHYEAR"])) > 0)
				->number("U1504")
				->minLength("U1504", 4)
				->maxLength("U1504", 4)
			->break()
		//誕生月
		->item($_POST["BIRTHMONTH"])
			->case( mb_strlen( trim($_POST["BIRTHYEAR"])) > 0)
				->required("U1505")
			->break()
		//誕生日
		->item($_POST["BIRTHDAY"])
			->case( mb_strlen( trim($_POST["BIRTHYEAR"])) > 0)
				->required("U1506")
			->break()
		
		// 性別
		->item($_POST["SEX"])
			->required("U1507")
	->report();
	
	if (count($errMessage) <= 0 && mb_strlen( trim($_POST["BIRTHYEAR"])) > 0) {
		// 暦日チェック
		if (!chk_date($_POST["BIRTHYEAR"] . "/" . $_POST["BIRTHMONTH"] . "/" . $_POST["BIRTHDAY"])) $errMessage[] = $template->message("U0435");
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
