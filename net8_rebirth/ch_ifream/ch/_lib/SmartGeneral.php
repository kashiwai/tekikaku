<?php
/*
 * SmartGeneral.php
 *
 * (C)SmartRams Corp. 2009 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * 汎用処理関数モジュール
 *
 * 汎用処理関数群
 *
 * @package
 * @author  須増 圭介
 * @version PHP5.x.x
 * @since   2009/09/08 初版作成 須増 圭介 初版作成
 * @since	2009/09/16 全面改修 岡本 静子 命名規約見直しに伴いfnc_basicを破棄しチェック関数以外をまとめて新規作成
 * @since	2009/10/26 正規表現改修 須増 圭介 PHP5.3時点でereg系関数が廃止されるので変更
 * @info
 */

//@@@@@@@@@@ データ作成・加工関連関数
/**
 * タイムスタンプ間作成
 * @access	public
 * @param	int		$p_year			年
 * @param	int		$p_month		月
 * @param	int		$p_day			日
 * @param	int		$p_hour			時
 * @param	int		$p_minutes		分
 * @param	int		$p_second		秒
 * @return	date
 * @info
 */
function make_TimeStamp($p_year = 1900, $p_month = 1, $p_day = 1, $p_hour = 0, $p_minutes = 0, $p_second = 0) {
	$ret = 0;

	// データがおかしい場合、強制的に数値代入
	if((int)$p_year < 1900) $p_year  = 1900;
	if((int)$p_month < 0)   $p_month = 0;
	if((int)$p_day < 0)     $p_day   = 0;

	// タイムスタンプ作成
	$ret = mktime((int)$p_hour, (int)$p_minutes, (int)$p_second, (int)$p_month, (int)$p_day, (int)$p_year);

	return $ret;
}
/**
 * メールヘッダ用時間作成
 * @access	public
 * @param	int		$p_year			年
 * @param	int		$p_month		月
 * @param	int		$p_day			日
 * @param	int		$p_hour			時
 * @param	int		$p_minutes		分
 * @param	int		$p_second		秒
 * @return	date
 * @info
 */
function make_MailHeaderDate($p_year = 1900, $p_month = 1, $p_day = 1, $p_hour = 0, $p_minutes = 0, $p_second = 0) {
	$ret = 0;

	// データがおかしい場合、強制的に数値代入
	if((int)$p_year < 1900) $p_year  = 1900;
	if((int)$p_month < 0)   $p_month = 0;
	if((int)$p_day < 0)     $p_day   = 0;

	// タイムスタンプ作成
	$timestamp = make_TimeStamp($p_year, $p_month, $p_day, $p_hour, $p_minutes, $p_second);

	// 時間加工
	$ret = date("r", $timestamp);

	return $ret;
}
/**
 * 文字列中のURLのリンクタグ編集
 * @access	public
 * @param	string	$p_target		編集対象文字列
 * @return	string					編集後文字列
 * @info
 */
function link_url($p_target) {
	$ret = $p_target;
//	$ret = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]"
	$ret = preg_replace("/[[:alpha:]]+:\/\/[^<>[:space:]]+[[:alnum:]\/]"
											, "<a href=\"\\0\">\\0<\/a>/", $ret);
	return $ret;
}

/**
 * 文字列中のメールアドレス編集
 * @access	public
 * @param	string	$p_target		編集対象文字列
 * @return	string					編集後文字列
 * @info
 */
function mailto_address($p_target) {
	$ret = $p_target;
	$tmp = array();
	// マッチする全てのパターンを取得
	preg_match_all("/[[:alnum:]\_\.\-]+\@+[^<>[:space:]]+[[:alnum:]\_\-\.]/", $p_target, $m, PREG_SET_ORDER);
	// 重複を取り除く
	foreach ($m as $val) {
		if (!in_array($val[0], $tmp)) $tmp[] = $val[0];
	}
	// 置換
	foreach ($tmp as $val2) {
		$ret = preg_replace("/" . $val2 . "/", "<a href=\"mailto:\\0\">\\0</a>", $ret);
	}
	return $ret;
}

/**
 * 日付のフォーマット
 * @access	public
 * @param	string		$p_target	変換対象文字列(yyyy/mm/dd hh:nn:ss)
 * @param	boolean		$p_jp 		日本語表記を行う否か(省略時はfalse)
 * @param	boolean		$p_extday 	日を含むか否か(省略時はtrue)
 * @param	boolean		$p_extyear 	年を含むか否か(省略時はtrue)
 * @return	string					変換後文字列
 * @info
 */
function format_date($p_target, $p_jp = false, $p_extday = true, $p_extyear = true) {
	$p_target = substr($p_target, 0, 10);

	$sep_format = "-\/ .";
//	if (!ereg("^([0-9]+)[$sep_format]([0-9]+)[$sep_format]([0-9]+)$", $p_target, $m)) return "";
	if (!preg_match("/^([0-9]+)[$sep_format]([0-9]+)[$sep_format]([0-9]+)$/", $p_target, $m)) return "";
	if (!checkdate($m[2], $m[3], $m[1])) return "";

	if ($p_jp) {
		/* 文字列比較する際の保険の為に月日を2桁フォーマットで統一
		$ret = (string)((int)$m[2]) . "月";
		if ($p_extday) $ret .= $m[3] . "日";
		if ($p_extyear) $ret = $m[1] . "年" . $ret;
		*/
		$ret = str_pad($m[2], 2, "0", STR_PAD_LEFT) . "月";
		if ($p_extday) $ret .= str_pad($m[3], 2, "0", STR_PAD_LEFT) . "日";
		if ($p_extyear) $ret = $m[1] . "年" . $ret;

	} else {
		/* 文字列比較する際の保険の為に月日を2桁フォーマットで統一
		$ret = $m[2];
		if ($p_extday) $ret .= "/" . $m[3];
		if ($p_extyear) $ret = $m[1] . "/" . $ret;
		*/
		$ret = str_pad($m[2], 2, "0", STR_PAD_LEFT);
		if ($p_extday) $ret .= "/" . str_pad($m[3], 2, "0", STR_PAD_LEFT);
		if ($p_extyear) $ret = $m[1] . "/" . $ret;
	}
	return $ret;
}
/**
 * 時間のフォーマット
 * @access	public
 * @param	string		$p_target	変換対象文字列(yyyy/mm/dd hh:nn:ss)
 * @param	boolean		$p_jp 		日本語表記を行う否か(省略時はfalse)
 * @param	boolean		$p_extsec 	秒を含むか否か(省略時はfalse)
 * @return	string					変換後文字列
 * @info
 */
function format_time($p_target, $p_jp = false, $p_extsec = false) {
	$p_target = substr($p_target, 11, 8);

	$ret_time = "00:00";
	if ($p_extsec) $ret_time .= ":00";
	if ($p_target == "") return $ret_time;

	$m = explode(":", $p_target);
	if (!is_array($m)) return $ret_time;
	if (count($m) < 3) return $ret_time;

	if ($p_jp) {
		/* 文字列比較する際の保険の為に月日を2桁フォーマットで統一
		$ret_time = $m[0] . "時" . $m[1] . "分";
		if ($p_extsec) $ret_time .= $m[2] . "秒";
		*/
		$ret_time = str_pad($m[0], 2, "0", STR_PAD_LEFT) . "時" . str_pad($m[1], 2, "0", STR_PAD_LEFT) . "分";
		if ($p_extsec) $ret_time .= str_pad($m[2], 2, "0", STR_PAD_LEFT) . "秒";
	} else {
		/* 文字列比較する際の保険の為に月日を2桁フォーマットで統一
		$ret_time = $m[0] . ":" . $m[1];
		if ($p_extsec) $ret_time .= ":" . $m[2];
		*/
		$ret_time = str_pad($m[0], 2, "0", STR_PAD_LEFT) . ":" . str_pad($m[1], 2, "0", STR_PAD_LEFT);
		if ($p_extsec) $ret_time .= ":" . str_pad($m[2], 2, "0", STR_PAD_LEFT);
	}

	return $ret_time;
}
/**
 * 日時のフォーマット
 * @access	public
 * @param	string		$p_target	変換対象文字列(yyyy/mm/dd hh:nn:ss)
 * @param	boolean		$p_jp 		日本語表記を行う否か(省略時はfalse)
 * @param	boolean		$p_extsec 	秒を含むか否か(省略時はfalse)
 * @return	string					変換後文字列
 * @info
 */
function format_datetime($p_target, $p_jp = false, $p_extsec = false) {
	$ret_day = format_date($p_target, $p_jp);
	if ($ret_day == "") return "";

	$ret_time = format_time($p_target, $p_jp, $p_extsec);
	return $ret_day . " " . $ret_time;
}
/**
 * 日時(yyyy/mm/dd hh:nn:ss)から時刻の取得
 * @access	public
 * @param	string		$p_target	取得対象文字列
 * @param	boolean		$p_cls 		0-時間 1-分
 * @return	string					時間(hh)もしくは分(nn)
 * @info
 */
function get_time($p_target, $p_cls) {
	$s_time = substr($p_target, 11, 8);
	if ($s_time == "") return "";

	$m = explode(":", $s_time);
	if (!is_array($m)) "";
	if (count($m) < 3) "";

	if ($p_cls == 0) {
		$ret = $m[0];
	} else {
		$ret = $m[1];
	}

	return $ret;
}
/**
 * 曜日の取得
 * @access	public
 * @param	string		$p_target	変換対象文字列(yyyy/mm/dd)
 * @return	string					曜日表記
 * @info	2016/10/14 英語表記対応
 */
function get_week($p_target, $p_lang = "ja") {
	$i_week = date("w", strtotime($p_target));
	if ($i_week == "") return "";

	switch ($i_week) {
		case 0:
			$ret = ($p_lang == "ja") ? "日" : "Sun";
			break;
		case 1:
			$ret = ($p_lang == "ja") ? "月" : "Mon";
			break;
		case 2:
			$ret = ($p_lang == "ja") ? "火" : "Tue";
			break;
		case 3:
			$ret = ($p_lang == "ja") ? "水" : "Wed";
			break;
		case 4:
			$ret = ($p_lang == "ja") ? "木" : "Thu";
			break;
		case 5:
			$ret = ($p_lang == "ja") ? "金" : "Fri";
			break;
		case 6:
			$ret = ($p_lang == "ja") ? "土" : "Sat";
			break;
	}

	return $ret;
}
/**
 * NULL・空白 → － 変換
 * @access	private
 * @param	string	$p_val	変換対象文字列
 * @return	string			変換後文字列
 */
function conv_brank($p_val) {
	$ret = "-";
	if (mb_strlen($p_val) > 0) $ret = $p_val;
	return $ret;
}
/**
 * 数字を千位毎にグループ化してフォーマットする(number_formatのラッパー)
 * @access	public
 * @param	string		$number			フォーマットする数値
 * @param	boolean		$decimals 		小数点以下の桁数
 * @param	boolean		$dec_point	 	小数点を表す区切り文字
 * @param	boolean		$thousands_sep 	千位毎の区切り文字
 * @return	string						number をフォーマットした結果
 * @info	number_formatの挙動で、フォーマット対象の数値文字列が空白であった場合は
 *      	フォーマットを行わないためのラッパー
 */
function number_formatEx($number, $decimals = 0, $dec_point = ".", $thousands_sep = ",") {
	if (mb_strlen(trim($number)) == 0) return $number;
	return number_format($number, $decimals, $dec_point, $thousands_sep);
}

/**
 * 数値桁揃えフォーマット
 *   カンマ編集後に桁揃えを行う(桁あふれはそのまま返却)
 * @access	public
 * @param	number		$trgVal			対象値
 * @param	int			$padLength		整形桁数
 * @param	string		$thousandsSep	千単位の区切り文字
 * @param	string		$padStr			埋め込み文字
 * @param	int			$padType		埋め込みタイプ(str_padのタイプ)
 * @return	編集文字列
*/
function padNumberFormat($trgVal, $padLength = 0, $thousandsSep = ",", $padStr = " ", $padType = STR_PAD_LEFT) {
	if (mb_strlen(trim($trgVal)) == 0) $trgVal = 0;
	return str_pad(number_format($trgVal, 0, "", $thousandsSep), $padLength, $padStr, $padType);
}

/**
 * 配列の値をキーと値に設定した配列を生成し返却
 * @access	public
 * @param	array	$valueAry	生成元配列
 * @return	array	値をキーと値に設定した配列
 * @info	$valueAryが配列以外の場合は空配列を返却
 */
function makeValueKeyArray($valueAry) {
	$ret = array();
	if (is_array($valueAry)) {
		foreach ($valueAry as $val) {
			$ret[$val] = $val;
		}
	}
	return $ret;
}

//@@@@@ タグ生成関連
/**
 * 年のSelectBox Option文字列生成
 * @access	public
 * @param	integer	$p_start		開始年
 * @param	integer	$p_end			終了年
 * @param	string	$p_def			デフォルト選択コード
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @return	string					SelectBox Option文字列
 * @author  岡本 静子
 * @info
 */
function makeSelectYearTag($p_start, $p_end, $p_def = "", $p_nullvalue = true, $p_nulldisp = "") {

	$ret = "";
	if (is_bool($p_nullvalue) && $p_nullvalue) $ret .= "<option value=\"\">" . $p_nulldisp . "</option>\n";

	for($i=$p_start;$i<=$p_end;$i++) {
		$ret .= "<option value=\"" . $i . "\"";
		if ((string)$p_def == (string)$i) $ret .= " selected";
		$ret .= ">";
		$ret .= sprintf("%04d", $i) . "</option>\n";
	}
	return $ret;
}

/**
 * 月のSelectBox Option文字列生成
 * @access	public
 * @param	string	$p_def			デフォルト選択コード
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @return	string					SelectBox Option文字列
 * @author  須増 圭介
 * @info
 */
function makeSelectMonthTag($p_def = "", $p_nullvalue = true, $p_nulldisp = "") {

	$ret = "";
	if (is_bool($p_nullvalue) && $p_nullvalue) $ret .= "<option value=\"\">" . $p_nulldisp . "</option>\n";

	for($i=1;$i<=12;$i++) {
		$ret .= "<option value=\"" . $i . "\"";
		if ((string)$p_def == (string)$i) $ret .= " selected";
		$ret .= ">";
		$ret .= sprintf("%02d", $i) . "</option>\n";
	}
	return $ret;
}

/**
 * 日のSelectBox Option文字列生成
 * @access	public
 * @param	string	$p_def			デフォルト選択コード
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @return	string					SelectBox Option文字列
 * @author  須増 圭介
 * @info
 */
function makeSelectDayTag($p_def = "", $p_nullvalue = true, $p_nulldisp = "") {

	$ret = "";
	if (is_bool($p_nullvalue) && $p_nullvalue) $ret .= "<option value=\"\">" . $p_nulldisp . "</option>\n";

	for($i=1;$i<=31;$i++) {
		$ret .= "<option value=\"" . $i . "\"";
		if ((string)$p_def == (string)$i) $ret .= " selected";
		$ret .= ">";
		$ret .= sprintf("%02d", $i) . "</option>\n";
	}
	return $ret;
}

/**
 * 時のSelectBox Option文字列生成
 * @access	public
 * @param	string	$p_def			デフォルト選択コード
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @return	string					SelectBox Option文字列
 * @author  須増 圭介
 * @info
 */
function makeSelectHourTag($p_def = "", $p_nullvalue = true, $p_nulldisp = "") {

	$ret = "";
	if (is_bool($p_nullvalue) && $p_nullvalue) $ret .= "<option value=\"\">" . $p_nulldisp . "</option>\n";

	for($i=0;$i<=23;$i++) {
		$ret .= "<option value=\"" . $i . "\"";
		if ((string)$p_def == (string)$i) $ret .= " selected";
		$ret .= ">";
		$ret .= sprintf("%02d", $i) . "</option>\n";
	}
	return $ret;
}

/**
 * 分のSelectBox Option文字列生成
 * @access	public
 * @param	string	$p_def			デフォルト選択コード
 * @param	integer	$p_span			分スパン(指定された分ごと増加)
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @return	string					SelectBox Option文字列
 * @author  鶴野 美香
 * @info
 */
function makeSelectMinuteTag($p_def = "", $p_span = 1, $p_nullvalue = true, $p_nulldisp = "") {

	$ret = "";
	if (is_bool($p_nullvalue) && $p_nullvalue) $ret .= "<option value=\"\">" . $p_nulldisp . "</option>\n";

	for ($i = 0; $i <= 59; $i++) {
		$i = ($i > 0) ? ($i + $p_span - 1) : $i;
		// 60に達していたらループ終了
		if ($i > 59) break;
		$ret .= "<option value=\"" . $i . "\"";
		if ((string)$p_def == (string)$i) $ret .= " selected";
		$ret .= ">";
		$ret .= sprintf("%02d", $i) . "</option>\n";
	}
	return $ret;
}

/**
 * テーブル内容のSelectBox Option文字列生成
 * @access	public
 * @param	string	$p_rs			RecordSet
 * @param	string	$p_val			値
 * @param	string	$p_disp			表示値
 * @param	string	$p_def			デフォルト選択コード
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @param	boolean	$p_entity		HTMLエンティティ変換するか否か
 * @return	string					SelectBox Option文字列
 * @info
 */
function makeOptionTable($p_rs, $p_val, $p_disp, $p_def, $p_nullvalue = true, $p_nulldisp = "", $p_entity = true) {

	//$rs = $p_rs;
	if (!$p_rs) return "";

	$ret = "";

	//--- 2012/10/02 J.Okamoto
	//--- 誰がいつ入れたのか不明(怒)だが、無意味なコードなので削除
	//if (!is_bool($p_nullvalue) && $p_nullvalue) $buf = $p_nullvalue;
	if ($p_nullvalue) $ret .= "<option value=\"\""
						   . ((mb_strlen($p_def) == 0) ? " selected" : "")
						   . ">" . $p_nulldisp . "</option>\n";
	while ($row = $p_rs->fetchRow(DB_FETCHMODE_ASSOC)) {
		$str = $row[$p_disp];
		$ret .= "<option value=\"" . $row[$p_val] . "\"";
		if (is_array($p_def)) {
			//--- 2012/10/02 J.Okamoto 誰がいつ入れたのか不明(怒)だが、
			//--- foreachで全対象を舐めるより、配列に値があるかどうかを配列関数で確認する方が効率的
			/*
			foreach ($p_def as $def) {
				if ((string)$def == (string)$row[$p_val]) $ret .= " selected";
			}
			*/
			if (in_array($row[$p_val], $p_def, false)) $ret .= " selected";
			//--- 2011/06/08 S.Okamoto htmlspecialchars対応
			//$ret .= ">" . $str . "</option>\n";
			$ret .= ">" . (($p_entity) ? htmlspecialchars($str) : $str) . "</option>\n";
		} else {
			if ((string)$p_def == (string)$row[$p_val]) $ret .= " selected";
			//--- 2011/06/08 S.Okamoto htmlspecialchars対応
			//$ret .= ">" . $str . "</option>\n";
			$ret .= ">" . (($p_entity) ? htmlspecialchars($str) : $str) . "</option>\n";
		}
	}
	$p_rs->free();

	return $ret;
}

/**
 * 配列データのSelectBox Option文字列生成
 * @access	public
 * @param	string	$p_ary_item		コード配列
 * @param	string	$p_def			デフォルト選択コード
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @param	boolean	$p_addcode		コードも表示するか否か
 * @param	string	$p_dispadd		表示文字列付加
 * @param	boolean	$p_entity		HTMLエンティティ変換するか否か
 * @param	string	$p_attr			追加属性(※配列の場合、keyをコード配列に揃えること)
 * @return	string					SelectBox Option文字列
 * @info							2020/05/08 tsuru 任意の属性を追加できるよう修正
 */
function makeOptionArray($p_ary_item, $p_def, $p_nullvalue = true, $p_nulldisp = "", $p_addcode = false, $p_dispadd = "", $p_entity = true, $p_attr = "") {

	$ret = "";

	if (!is_array($p_ary_item)) return $ret;

	//--- 2012/10/02 J.Okamoto
	//--- 誰がいつ入れたのか不明(怒)だが、無意味なコードなので削除
	//if (!is_bool($p_nullvalue) && $p_nullvalue) $buf = $p_nullvalue;
	if ($p_nullvalue) $ret .= "<option value=\"\""
						   . ((mb_strlen($p_def) == 0) ? " selected" : "")
						   . ">" . $p_nulldisp . "</option>\n";
	foreach ($p_ary_item as $key=>$value) {
		$str = ($p_addcode) ? $key . " | " . $value : $value;
		if (mb_strlen($p_dispadd) > 0) $str .= $p_dispadd;
		$ret .= "<option value=\"" . $key . "\"";
		//--- 2020/05/08 tsuru 属性を追加
		if (is_array($p_attr)) {
			if (isset($p_attr[$key]) && mb_strlen($p_attr[$key]) > 0) $ret .= " " . $p_attr[$key];
		} else {
			if (mb_strlen($p_attr) > 0) $ret .= " " . $p_attr;
		}
		
		if (is_array($p_def)) {
			//--- 2012/10/02 J.Okamoto 誰がいつ入れたのか不明(怒)だが、
			//--- foreachで全対象を舐めるより、配列に値があるかどうかを配列関数で確認する方が効率的
			/*
			foreach ($p_def as $def) {
				if ((string)$def == (string)$key) $ret .= " selected";
			}
			*/
			if (in_array($row[$p_val], $p_def, false)) $ret .= " selected";
			//--- 2011/06/08 S.Okamoto htmlspecialchars対応
			//$ret .= ">" . $str . "</option>\n";
			$ret .= ">" . (($p_entity) ? htmlspecialchars($str) : $str) . "</option>\n";
		} else {
			if ((string)$p_def == (string)$key) $ret .= " selected";
			//--- 2011/06/08 S.Okamoto htmlspecialchars対応
			//$ret .= ">" . $str . "</option>\n";
			$ret .= ">" . (($p_entity) ? htmlspecialchars($str) : $str) . "</option>\n";
		}
	}

	return $ret;
}

/**
 * 配列データのRadio List文字列生成
 * @access	public
 * @param	string	$p_ary_item		コード配列
 * @param	string	$p_name			項目名
 * @param	string	$p_def			デフォルト選択コード
 * @param	string	$p_add			追記文字列(イベント処理等)
 * @param	bool	$p_br			true：改行表示 / false：空白連結表示
 * @param	boolean	$p_entity		HTMLエンティティ変換するか否か
 * @return	string					Radio List文字列
 * @info
 */
function makeRadioArray($p_ary_item, $p_name, $p_def = "", $p_add = "", $p_br = false, $p_entity = true) {

	$ret = "";

	if (!is_array($p_ary_item)) return $ret;

	$idx = 0;
	foreach ($p_ary_item as $key=>$value) {
		if (mb_strlen($ret) > 0 && $p_br) {
			$ret .= "<br />";
		} else if (mb_strlen($ret) > 0) {
			$ret .= "　";
		}
		// 2019/02/06 片岡 同一ページ上に、グループ以上Radioボタンがある場合のバグを修正
		// 2019/02/01 デザイン改修に伴いradioの記述方法を変更
		// $ret .= "<label class=\"radio-inline\"><input type=\"radio\" name=\"" . $p_name . "\" value=\"" . $key . "\"";
		// 2019/02/01 Bootstrap4に準拠した記述
		$ret .= "<div class=\"custom-control custom-radio custom-control-inline\">" . "\n"
			 .  "<input type=\"radio\" class=\"custom-control-input\" id=\"customRadio_" . $p_name . "_" . $idx
			 .  "\" name=\"" . $p_name . "\" value=\"" . $key . "\"";
		if (mb_strlen($p_def) > 0) {
			if ((string)$p_def == (string)$key) $ret .= " checked";
		} else{
			if ($idx == 0) $ret .= " checked";
		}
		if (mb_strlen($p_add) > 0) $ret .= $p_add;

		//--- 2011/06/08 S.Okamoto htmlspecialchars対応
		//$ret .= ">" . $value . "\n";
		$ret .= ">" . "\n"
			 .  "<label class=\"custom-control-label\" for=\"customRadio_" . $p_name . "_" . $idx . "\">"
			 . (($p_entity) ? htmlspecialchars($value) : $value) . "</label>" . "\n"
			 . "</div>" . "\n";
		$idx += 1;
	}

	return $ret;
}

/**
 * 配列データのCheckBox List文字列生成
 * @access	public
 * @param	string	$p_ary_item		コード配列
 * @param	string	$p_name			項目名
 * @param	string	$p_def			デフォルト選択コード(複数対象時は配列)
 * @param	integer	$p_aryflg		項目名作成方法 0：全て同じ項目名 / 1:連番配列で作成 / 2：KEYを配列にして作成
 * @param	string	$p_header		inputタグ毎の頭に付ける文字列
 * @param	string	$p_footer		inputタグ毎の文字列後に付ける文字列
 * @param	string	$p_between		checkboxと項目名の間に挟む文字列
 * @param	boolean	$p_entity		HTMLエンティティ変換するか否か
 * @param	integer	$p_line_break	強制改行する項目数
 * @return	string					CheckBox List文字列
 * @info
 */
function makeCheckBoxArray($p_ary_item, $p_name, $p_def = "", $p_aryflg = 0, $p_header = "", $p_footer = "", $p_between = "", $p_entity = true, $p_line_break = 0) {

	$ret = "";

	if (!is_array($p_ary_item)) return $ret;

	$idx = 0;

	foreach ($p_ary_item as $key => $value) {
		//--- 2016/09/20 by S.Okamoto 強制改行処理追加
		if ($p_line_break > 0 && $idx > 0 && ($idx % $p_line_break) == 0) $ret .= "<br />\n";
		//if (mb_strlen($ret) > 0) $ret .= "　";
		if(mb_strlen($p_header) > 0) $ret .= $p_header;
		// 2019/02/01 デザイン改修に伴いcheckboxの記述方法を変更
		// $ret .= "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"" . $p_name;
		// 2019/02/01 Bootstrap4に準拠した記述
		$ret .= "<div class=\"custom-control custom-checkbox custom-control-inline\">" . "\n"
			 .  "<input type=\"checkbox\" class=\"custom-control-input\" id=\"customCheck" . $idx.  "\""
			 .  " name=\"" . $p_name;

		if($p_aryflg == 1) {
			$ret .= "[" . $idx . "]";
		} else if($p_aryflg == 2) {
			$ret .= "[" . $key . "]";
		}
		$ret .= "\" value=\"" . $key . "\"";
		if (is_array($p_def)) {
			foreach ($p_def as $def) {
				if ((string)$def == (string)$key) $ret .= " checked";
			}
			//--- 2011/06/08 S.Okamoto htmlspecialchars対応
			//$ret .= ">" . $p_between . $value;
			$ret .= ">" . "\n"
				 .  "<label class=\"custom-control-label\" for=\"customCheck" . $idx . "\">"
				 .  $p_between . (($p_entity) ? htmlspecialchars($value) : $value);
		} else {
			if ((string)$p_def == (string)$key) $ret .= " checked";
			//--- 2011/06/08 S.Okamoto htmlspecialchars対応
			//$ret .= ">" . $p_between . $value;
			$ret .= ">" . "\n"
				 .  "<label class=\"custom-control-label\" for=\"customCheck" . $idx . "\">"
				 .  $p_between . (($p_entity) ? htmlspecialchars($value) : $value);
		}
		$ret .= "</label>" . "\n"
			 .  "</div>";
		if(mb_strlen($p_footer) > 0) $ret .= $p_footer;
		$ret .= "\n";
		$idx += 1;
	}

	return $ret;
}

//--- 2019/02/14 片岡 Add   ---
/**
 * ページングHTMLタグ生成関数
 * @access	public
 * @param	string	$link		リンクパス（パラメータがある場合はそれも記述、ない場合 ? まで記述する必要あり）
 * @param	num		$page		現在のページ
 * @param	num		$allpage	総ページ数
 * @param	string	$active		選択ページに与えるクラス名
 * @param	string	$addclass	LIタグに与えるクラス名
 * @param	string	$addclass2	LIタグの中のAタグに与えるクラス名
 * @return	string	HTMLタグ文章
 */
function HtmlPagingTag( $link, $page, $allpage, $active="active", $addclass="page-item", $addclass2="page-link"){
	if( $allpage == 1) return "";
	$ret = "";
	//$linkにページ指定がすでにある場合削除
	//$link = preg_replace("/([\&\?])(P=((\d)+))[\&]?/s", "$1", $link);
	$link = preg_replace("/(P=.+?)\&/s", "", $link);
	// 現在ページ番号を基準にMAXで前後N件ずつ表示
	$min = ($page > PAGE_SPAN) ? $page - PAGE_SPAN : 1;
	$max = ($allpage < $page + PAGE_SPAN) ? $allpage : $page + PAGE_SPAN;
	
	for( $i=$min; $i<=$max; $i++){
		$act  = ($i==$page)? " ". $active:"";
		$cstr = ($addclass!="")? ' class="' . $addclass . $act . '"': (($act!="")? ' class="' .trim($act) . '"':"");
		$ret .= '<li' . $cstr . '><a class="'.$addclass2.'" href="'. $link .'P='. $i .'">' . $i . '</a></li>';
	}
	
	if( $ret != ""){
		// 1ページ戻る
		$act  = ($page > 1)? "": " disabled";
		$cstr = ($addclass!="")? ' class="' . $addclass . $act . '"': (($act!="")? ' class="' .trim($act) . '"':"");
		$ret = '<li' . $cstr . '><a class="'.$addclass2.'" href="'. $link .'P='. ($page-1) .'">«</a></li>' .$ret;
		
		// 1ページ進む
		$act  = ($page < $allpage)? "": " disabled";
		$cstr = ($addclass!="")? ' class="' . $addclass . $act . '"': (($act!="")? ' class="' .trim($act) . '"':"");
		$ret .= '<li' . $cstr . '><a class="'.$addclass2.'" href="'. $link .'P='. ($page+1) .'">»</a></li>';
	}
	
	return $ret;
}
//--- 2019/02/14 片岡 Add End  ---
//--- 2019/03/14 片岡 Add ---
/**
 * ページングHTMLタグ生成関数用のクエリ生成関数
 * @access	public
 * @param	array	$ques		$_GET等のクエリ配列
 * @param	array	$deny		$quelistからJavaScript除外するクエリ名の配列
 * @param	bool	$del		値がない場合に除外するかどうか
 * @return	string	クエリ文字列（先頭に?は付かない）
 */
function HtmlPagingQueryString( $que, $deny, $add = "", $del = true){
	$ret = "";
	$_que_array = array();
	if( !$add=="") $_que_array[] = $add;
	foreach ( $que as $key => $value) {
		if ( !in_array( $key , $deny, true)) {
			if ( $del) {
				if ( $value != "") {
					$_que_array[] = $key ."=" .$value;
				}
			} else {
				$_que_array[] = $key ."=" .$value;
			}
		}
	}
	if( !empty($_que_array)) $ret = implode( "&", $_que_array)."&";
	return $ret;
}



/**
 * 配列データのSelectBox Option文字列生成  JavaScriptとの連携用にクラス付与機能を追加
 * @access	public
 * @param	string	$p_ary_item		コード配列（連想配列： "value" => 表示文字列 , "class" => クラスに追加する文字列
 * @param	string	$p_def			デフォルト選択コード
 * @param	boolean	$p_nullvalue	空白を選択肢として含むか否か
 * @param	string	$p_nulldisp		空白選択肢表示文字列
 * @param	boolean	$p_addcode		コードも表示するか否か
 * @param	string	$p_dispadd		表示文字列付加
 * @param	boolean	$p_entity		HTMLエンティティ変換するか否か
 * @return	string					SelectBox Option文字列
 * @info
 */
function makeOptionArrayAddClass($p_ary_item, $p_def, $p_nullvalue = true, $p_nulldisp = "", $p_addcode = false, $p_dispadd = "", $p_entity = true) {
	$ret = "";
	if (!is_array($p_ary_item)) return $ret;
	if ($p_nullvalue) $ret .= "<option value=\"\""
						   . ((mb_strlen($p_def) == 0) ? " selected" : "")
						   . ">" . $p_nulldisp . "</option>\n";
	foreach ($p_ary_item as $key=>$value) {
		$str = ($p_addcode) ? $key . " | " . $value["value"] : $value["value"];
		if (mb_strlen($p_dispadd) > 0) $str .= $p_dispadd;
		if ( $value["class"] != "") {
			$ret .= "<option class=\"". $value["class"] ."\" value=\"" . $key . "\"";
		} else {
			$ret .= "<option value=\"" . $key . "\"";
		}
		if (is_array($p_def)) {
			if (in_array($row[$p_val], $p_def, false)) $ret .= " selected";
			$ret .= ">" . (($p_entity) ? htmlspecialchars($str) : $str) . "</option>\n";
		} else {
			if ((string)$p_def == (string)$key) $ret .= " selected";
			$ret .= ">" . (($p_entity) ? htmlspecialchars($str) : $str) . "</option>\n";
		}
	}
	return $ret;
}

//--- 2019/03/14 片岡 Add End  ---







//@@@@@ 計算関連
/**
 * 税額の丸め
 * @access	public
 * @param	double	$p_value		丸め対象値
 * @param	integer	$p_type			丸めタイプ
 * @return	なし
 * @author
 * @info
 */
function calcTax($p_value, $p_type) {
	$ret = 0;

	// 丸め処理
	switch ($p_type) {
		case 1:				// 四捨五入
			$ret = round($p_value);
			break;
		case 2:				// 切捨て
			$ret = floor($p_value);
			break;
		case 3:				// 切り上げ
			$ret = ceil($p_value);
			break;
	}
	return $ret;
}

/**
 * 内税計算
 * @access	public
 * @param	double	$p_value		計算値
 * @param	integer	$p_rate			税率
 * @param	integer	$p_rule			丸めタイプ
 * @return	なし
 * @author
 * @info
 */
function calcInTax($p_value, $p_rate, $p_rule = "") {

	$ret = $p_value * ($p_rate / (100 + $p_rate));
	if (mb_strlen($p_rule) > 0) $ret = calcTax($ret, (int)$p_rule);

	return $ret;
}

/**
 * 年齢計算
 * @access	public
 * @param	string	$p_birth		誕生日(yyyy/mm/dd)
 * @return	int						年齢
 * @info
 */
function get_age($p_birth){
	if (!chk_date($p_birth)) return "";
	// 本日日付取得
	$t_year  = date("Y");
	$t_month = date("m");
	$t_day   = date("d");
	// 誕生日付を、年・月・日に分解
	list($year,$month,$day) = explode("/", $p_birth);
	// 年齢計算
	if ((int)($t_month . $t_day) < (int)(sprintf("%02d", $month) . sprintf("%02d", $day))) {
		$age = $t_year - $year -1;
	} else {
		$age = $t_year - $year;
	}
	return $age;
}

/**
 * 月末日取得
 * @access	public
 * @param	int		$p_year			年
 * @param	int		$p_month		月
 * @return	int						計算後UNIXタイムスタンプ
 * @info
 */
function getMonthLastDay($p_year, $p_month) {
	// mktime関数で日付を0にすると前月の末日を指定したことになる
	// $month + 1 をするが、結果13月のような値になっても自動で補正される
	$dt = mktime(0, 0, 0, $p_month + 1, 0, $p_year);
	return $dt;
}

/**
 * 日加減算
 * @access	public
 * @param	int		$p_timestamp	基準日UNIXタイムスタンプ
 * @param	int		$p_span			加減算日数
 * @return	int						計算後UNIXタイムスタンプ
 * @info
 */
function addDay($p_timestamp, $p_span) {
	$year  = (int)date("Y", $p_timestamp);
	$month = (int)date("n", $p_timestamp);
	$day   = (int)date("j", $p_timestamp);

	$sec = mktime(0, 0, 0, $month, $day, $year);	// 基準日を秒で取得
	$add = $p_span * (60 * 60 * 24);				// 日数*1日の秒数を加算
	$ret = $sec + $add;
	return $ret;
}

/**
 * 月加減算
 * @access	public
 * @param	int		$p_timestamp	基準日UNIXタイムスタンプ
 * @param	int		$p_span			加減算月数
 * @return	int						計算後UNIXタイムスタンプ
 * @info
 */
function addMonth($p_timestamp, $p_span) {
	$year  = (int)date("Y", $p_timestamp);
	$month = (int)date("n", $p_timestamp);
	$day   = (int)date("j", $p_timestamp);

	$month += $p_span;
	$last  = (int)date("t", getMonthLastDay($year, $month));
	if ($day > $last) $day = $last;
	$dt = mktime(0, 0, 0, $month, $day, $year);
	return $dt;
}

//@@@@@@@@@@ エンコード処理関数
/*
 * ECMA-262準拠unicode→UTF-8
 * @access	public
 * @param	string		p_target	変換対象文字列
 * @return	string					変換後後文字列
 * @info    JavaScriptのescape()関数はブラウザによって挙動か異なり、
 * 			サーバサイドスクリプトのURLエンコード･デコードとは互換性がないので
 * 			IEのECMA-262準拠unicodeエンコードを基準に相互変換を可能にする
 */
function toUtf8($ar){
	$c = "";
	foreach($ar as $val) {
		$val = intval(substr($val,2),16);
		if ($val < 0x7F){			// 0000-007F
			$c .= chr($val);
		} elseif ($val < 0x800) {	// 0080-0800
			$c .= chr(0xC0 | ($val / 64));
			$c .= chr(0x80 | ($val % 64));
		} else {					// 0800-FFFF
			$c .= chr(0xE0 | (($val / 64) / 64));
			$c .= chr(0x80 | (($val / 64) % 64));
			$c .= chr(0x80 | ($val % 64));
		}
	}
	return $c;
}
/*
 * ECMA-262準拠unicode→指定エンコーディング
 * @access	public
 * @param	string		p_target	変換対象文字列
 * @return	string					変換後後文字列
 * @info    JavaScriptのescape()関数はブラウザによって挙動か異なり、
 * 			サーバサイドスクリプトのURLエンコード･デコードとは互換性がないので
 * 			IEのECMA-262準拠unicodeエンコードを基準に相互変換を可能にする
 */
function uniDecode($str, $charcode) {
	$text = preg_replace_callback("/%u[0-9A-Za-z]{4}/", "toUtf8", $str);
	return mb_convert_encoding($text, $charcode, 'utf-8');
}
/*
 * ECMA-262準拠unicode→EUC-JP
 * @access	public
 * @param	string		p_target	変換対象文字列
 * @return	string					変換後後文字列
 * @info    JavaScriptのescape()関数はブラウザによって挙動か異なり、
 * 			サーバサイドスクリプトのURLエンコード･デコードとは互換性がないので
 * 			IEのECMA-262準拠unicodeエンコードを基準に相互変換を可能にする
 */
function escuni2euc($escunistr) {
	return uniDecode($escunistr, 'euc-jp');
}
/*
 * ECMA-262準拠unicode→UTF-8
 * @access	public
 * @param	string		p_target	変換対象文字列
 * @return	string					変換後後文字列
 * @info    JavaScriptのescape()関数はブラウザによって挙動か異なり、
 * 			サーバサイドスクリプトのURLエンコード･デコードとは互換性がないので
 * 			IEのECMA-262準拠unicodeエンコードを基準に相互変換を可能にする
 */
function escuni2utf8($escunistr) {
	return uniDecode($escunistr, 'utf-8');
}

//@@@@@ 文字列操作関連
/**
 * 文字列文字数カット処理
 * @access	private
 * @param	string  $p_str			対象文字列
 * @param	integer $p_len			文字数
 * @param   string  $p_etc			非表示部分の表示文字
 * @return	結果文字列
 * @info
 */
function substr_etc($p_str, $p_len, $p_etc = "") {

	$ret = "";

	$ret = mb_substr($p_str, 0, $p_len);
	if ($ret != $p_str) $ret .= $p_etc;

	return $ret;
}

//@@@@@@@@@@ その他処理関数
/**
 * 自スクリプト名取得
 * @access	public
 * @param	なし
 * @return	string					自スクリプト名
 * @info
 */
function get_self() {
	$self = $_SERVER["SCRIPT_FILENAME"];
	$pos = strrpos($self, "/");
	if ($pos) $self = substr($self, $pos + 1);
	return $self;
}
/**
 * ホストURL取得
 * @access	public
 * @param	なし
 * @return	string					ホストURL
 * @info
 */
function get_hosturl($filename = "", $ssl = false) {
	$url  = ($ssl) ? "https://" : "http://" ;
	$url .= $_SERVER["SERVER_NAME"];
	$url .= (mb_strlen($filename) == 0) ? "/" : "/" . $filename;

	return $url;
}
/**
 * ホストIP取得
 * @access	public
 * @param	なし
 * @return	string					ホストIP
 * @info
 */
function get_hostip() {

	return $_SERVER["SERVER_ADDR"];
}

//@@@@@@@@@@ ブラウザデータの取得
/**
 * ブラウザデータの取得
 * @access	public
 * @param	object	$data			ブラウザデータ
 * @param	array	$getList		取得項目リスト
 * @param	boolean	$isTrim			Trim処理を行うか否か
 * @return
 */
function getData(&$data, $getList, $isTrim = true) {

	if (empty($getList)) return;
	foreach ($getList as $value) {
		$data[$value] = (isset($data[$value])) ? (($isTrim) ? trim($data[$value]) : $data[$value]) : "";
	}

}

?>
