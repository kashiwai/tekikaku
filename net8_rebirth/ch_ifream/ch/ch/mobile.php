<?php
/*
 * mobile.php
 * 
 * (C)SmartRams Co.,Ltd. 2020 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * 携帯番号再設定画面表示
 * 
 * 携帯番号再設定画面の表示を行う
 * 
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since	 2020/06/18 初版作成 岡本静子
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
			case "comp":			// 完了画面
				DispComp($template);
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
	getData($_POST , array("MOBILE", "PIN", "RMOBILE", "INTERNATIONAL_CD"));

	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	$_POST["MOBILE"] = (int)$_POST["MOBILE"];	// 先頭Zero除去

	// トランザクション開始
	$template->DB->autoCommit(false);

	// 携帯番号変更
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_member" )
			->set()
				->value("mobile"       , $_POST["MOBILE"], FD_STR)
				->value("mobile_upd_dt", "current_timestamp", FD_FUNCTION)
				->value("mobile_checked_dt", "current_timestamp", FD_FUNCTION)		// 2020/12/25 [ADD]
				->value("international_cd", $_POST["INTERNATIONAL_CD"], FD_STR)
				->value("upd_no"       , $template->Session->UserInfo["member_no"], FD_NUM)
				->value("upd_dt"       , "current_timestamp", FD_FUNCTION)
			->where()
				->and(false, "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and(false, "state = ", "1", FD_NUM)
		->createSQL();
	$template->DB->exec($sql);

	// 識別データ削除
	$delsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->delete()
			->from("dat_sms_identify")
			->where()
				->and( "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( "identify_kbn = ", "2", FD_NUM)
		->createSql();
	$template->DB->exec($delsql);

	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);

	header("Location: " . URL_SSL_SITE . $template->Self . "?M=comp");

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
	getData($_POST , array("MOBILE", "PIN", "RMOBILE", "INTERNATIONAL_CD"));

	if( mb_strlen($message) == 0){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("mm.mobile, mm.international_cd")
			->from("mst_member mm")
			->where()
				->and("mm.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and("mm.state = ", "1", FD_NUM)
			->createSQL();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		$_POST["RMOBILE"] = $row["international_cd"] . $row["mobile"];
		$_POST["INTERNATIONAL_CD"] = $row["international_cd"];
	}

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->assign("RMOBILE", $_POST["RMOBILE"], true);
	$template->assign("SEL_INTERNATIONAL_CD", makeOptionArray($GLOBALS["usableInternationalCode"], $_POST["INTERNATIONAL_CD"], false));
	$template->assign("MOBILE" , $_POST["MOBILE"], true);
	$template->assign("PIN"    , $_POST["PIN"], true);
	$template->assign("SMS_PINCODE_LENGTH", SMS_PINCODE_LENGTH, true);

	// 表示
	$template->flush();
}

/**
 * 完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComp($template) {
	
	// 画面表示開始
	$template->open(PRE_HTML . "_change_end.html");
	$template->assignCommon();
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

	// 携帯番号重複
	$sqlMobileDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "member_no != ", $template->Session->UserInfo["member_no"], FD_NUM)
			->and( "mobile = ", $_POST["MOBILE"], FD_STR)
			->and( "international_cd = ", $_POST["INTERNATIONAL_CD"], FD_STR)
			->and( "state = " , "1", FD_NUM)
	->createSql("\n");
	// ブラック携帯番号
	$sqlMobileBlack = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "mobile = "   , $_POST["MOBILE"], FD_STR)
			->and( "international_cd = ", $_POST["INTERNATIONAL_CD"], FD_STR)
			->and( "black_flg = ", "1", FD_NUM)
	->createSql("\n");
	// SMS識別データ
	$sqls = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(member_no)")
		->from("dat_sms_identify")
		->where()
			->and("member_no = "   , $template->Session->UserInfo["member_no"], FD_NUM)
			->and("identify_kbn = ", "2", FD_NUM);
	$sqlSms = $sqls->createSql("\n");

	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["PIN"])	//-- PIN
			->required("U0440")		// 必須
			->number("U0441")		// 半角数字
			->length("U0442", SMS_PINCODE_LENGTH)	// 桁数
			->noCountSQL("U0443", $sqlSms)	// 存在
		->item($_POST["INTERNATIONAL_CD"])	//-- 国際番号
			->required("U0451")			// 必須
			->if("U0451", array_key_exists($_POST["INTERNATIONAL_CD"], $GLOBALS["usableInternationalCode"]))	// 存在
		->item($_POST["MOBILE"])	//-- 携帯番号
			->required("U0436")			// 必須
			->number("U0437")			// 半角数字
			->if("U0452", (mb_strlen($_POST["INTERNATIONAL_CD"]) + mb_strlen((int)$_POST["MOBILE"])) <= 16)	// 16文字以内
			->not("U0450", $_POST["RMOBILE"])	// 変わって無い
			->countSQL("U0438", $sqlMobileDupli)	// 重複
			->countSQL("U0439", $sqlMobileBlack)	// ブラック
	->report();

	// PINコードチェック
	if (count($errMessage) <= 0) {
		$sql = $sqls
				->resetField()
				->field("pin, limit_dt")
				->createSql("\n");
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		if ($row["pin"] != $_POST["PIN"]) {		// PIN不一致
			$errMessage[] = $template->message("U0443");
		} else {
			// 有効期限チェック
			$nowDT = new DateTime("now");
			$limitDt = new DateTime( $row["limit_dt"]);
			if( $limitDt < $nowDT) $errMessage[] = $template->message("U0444");
		}
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
