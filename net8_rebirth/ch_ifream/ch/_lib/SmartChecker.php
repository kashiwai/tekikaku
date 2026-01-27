<?php
/*
 * SmartChecker.php
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
 * 汎用チェック処理関数モジュール
 * 
 * 汎用チェック処理関数群
 * 
 * @package 
 * @author  須増 圭介
 * @version PHP5.x.x
 * @since   2009/09/08 初版作成 須増 圭介 初版作成
 * @since	2009/09/16 全面改修 岡本 静子 命名規約見直しに伴いfnc_basicを破棄しチェック関数をめとめて新規作成
 * @since	2009/10/26 正規表現改修 須増 圭介 PHP5.3時点でereg系関数が廃止されるので変更
 * @since	2010/03/15 改修 岡本 静子 電話番号/郵便番号のハイフン有無フラグ追加
 * @since	2011/07/27 変更 須増 圭介 メールアドレスチェックを「@○○.○○」でチェックされるように変更
 * @since	2015/10/15 修正 岡本 静子 alnum関数のReturn値が反転していたので修正
 * @since	2020/05/29 修正 岡本 静子 言語毎対応が必要な電話番号、郵便番号チェックを各言語用に切出し
 * @info	
 */

//@@@@@@@@@@ チェック関連関数
/**
 * 半角数字チェック
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	
 */
function chk_number($p_target) {
	return !preg_match("/[^[:digit:]]/", $p_target);
}
/**
 * 半角数字チェック(マイナス許可)
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info    マイナス許可
 */
function chk_numberEx($p_target) {
	return !preg_match("/[^(-?)[:digit:]]/", $p_target);
}
/**
 * 数値チェック
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @param	int		$p_integer		整数部最大桁数(1以上指定)
 * @param	int		$p_decimal		小数部最大桁数(0以上指定)
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	
 */
function chk_numeric($p_target, $p_integer, $p_decimal = 0) {
	if ($p_integer < 1) $p_integer = 1;
	if ($p_decimal < 0) $p_decimal = 0;

	$format = "^[[:digit:]]{1";
	if ($p_integer > 1) {
		$format .= "," . $p_integer . "}";
	}
	if ($p_decimal > 1) {
		$format .= "(\.[0-9]{1," . (string)$p_decimal . "})?"; 
	}
	$format .= "$";
	return preg_match("/" . $format . "/", $p_target);
}
/**
 * メールアドレスチェック
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	
 */
function chk_mail($p_target) {
//	return ereg("^[[:alnum:]_\.\-]+\@+[^<>[:space:]]+[[:alnum:]_\-\.]$", $p_target);
//	return preg_match("/^[[:alnum:]_\.\-]+\@+[^<>[:space:]]+[[:alnum:]_\-\.]$/", $p_target);
	return preg_match("/^[[:alnum:]_\.\-]+\@+[^<>[:space:]]+[[:alnum:]_\-\.]+\.+[[:alnum:]_\-]+$/", $p_target);
}
/**
 * URLチェック
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	
 */
function chk_url($p_target) {
//	return ereg("^(http|https|ftp):\/\/[^<>[:space:]]+[[:alnum:]\/]$", $p_target);
	return preg_match("/^(http|https|ftp):\/\/[^<>[:space:]]+[[:alnum:]\/]$/", $p_target);
}
/**
 * カナチェック
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @param	int		$p_flg			種別(省略時は0)
 *									0-全角半角区別なし / 1-全角のみ可 / 2-半角のみ可 / 3-半角のみ可+数値+「()-､<>･.」+「ABCOPSXYZ」可
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	ハイフン「\x2D」も許可しています
 */
function chk_syll($p_target, $p_flg = 0) {

	$data = "アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン"
		  . "ヴガギグゲゴザジズゼゾダヂヅデドパピプペポバビブベボッァィゥェォャュョヮヵヶー－ヰヱ　（）";
	$han_data = mb_convert_kana($data, "k", ini_get("mbstring.internal_encoding"));		// 半角に変換
	$enc = (ini_get("mbstring.internal_encoding") === "UTF-8") ? "u" : "";				// 文字コード指定

	if($p_flg == 1) {
		if (!preg_match("/^[$data]+$/$enc", $p_target)) {
			return false;
		}
	} else if($p_flg == 2) {
		if (!preg_match("/^[$han_data]+$/$enc", $p_target)) {
			return false;
		}
	} else if($p_flg == 3) {		// 郵便番号取込用特別仕様
		if (!preg_match("/^([$data]|[()\-､<>･\.]|[0-9ABCOPSXYZ])+$/$enc", $p_target)) {
			return false;
		}
	} else {
		if (!preg_match("/^[$data . $han_data]+$/$enc", $p_target)) {
			return false;
		}
	}
	return true;

/*
	// チェックコードがEUC-JPのため、対象文字列をEUC-JPにエンコードしてチェックを行う
	$target = (ini_get("mbstring.http_input") !== "EUC-JP") ? mb_convert_encoding($p_target, "EUC-JP") : $p_target;

	if($p_flg == 1) {
//		if (!ereg("^(\xA5[\xA1-\xF6]|\xA1[\xBC\xA6\xA1]|\x20|\x2D)+$", $p_target)) {
		if (!preg_match("/^(\xA5[\xA1-\xF6]|\xA1[\xBC\xA6\xA1]|\x20|\x2D)+$/", $p_target)) {
		//if (!ereg("^(\xA5[\xA1-\xF6]|\xA1\xBC|\xA1\xA6|\xA1\xA1|\x20)+$", $p_target)) {
			return false;
		}
	} else if($p_flg == 2) {
//		if (!ereg("^(\x8E[\xA6-\xDF]|\xA1[\xBC\xA6]|\x20|\x2D)+$", $p_target)) {
		if (!preg_match("/^(\x8E[\xA6-\xDF]|\xA1[\xBC\xA6]|\x20|\x2D)+$/", $p_target)) {
			return false;
		}
	} else if($p_flg == 3) {		// 郵便番号取込用特別仕様
//		if (!ereg("^(\xA5[\xA1-\xF6]|\xA1[\xBC\xA6\xA1]|\x20|\x8E[\xA6-\xDF]|\x2D|[()\-､<>･\.]|[0-9ABCOPSXYZ])+$", $p_target)) {
		if (!preg_match("/^(\xA5[\xA1-\xF6]|\xA1[\xBC\xA6\xA1]|\x20|\x8E[\xA6-\xDF]|\x2D|[()\-､<>･\.]|[0-9ABCOPSXYZ])+$/", $p_target)) {
			return false;
		}
	} else {
//		if (!ereg("^(\xA5[\xA1-\xF6]|\xA1[\xBC\xA6\xA1]|\x20|\x8E[\xA6-\xDF]|\x2D)+$", $p_target)) {
		if (!preg_match("/^(\xA5[\xA1-\xF6]|\xA1[\xBC\xA6\xA1]|\x20|\x8E[\xA6-\xDF]|\x2D)+$/", $p_target)) {
			return false;
		}
	}
	return true;
*/
}
/**
 * 半角英数字チェック
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @param	int		$p_flg			英字種別(省略時は0)
 *									0-大文字小文字区別なし / 1-小文字と数字のみ可 / 2-大文字と数字のみ可 / 3-[_][-]も可(大文字小文字区別なし) / 4-[_][-]も可(小文字と数字のみ)
 * @return	boolean					true-チェックOK / false-チェックNG
 * @info	2015/10/15 Upd by S.Okamoto	Return値が反転していたので修正
 */
function chk_alnum($p_target, $p_flg = 0) {
	switch ($p_flg) {
		case 0:
			$format = "[^[:alnum:]]";
			break;
		case 1:
			$format = "[^[:lower:]^[:digit:]]";
			break;
		case 2:
			$format = "[^[:upper:]^[:digit:]]";
			break;
		case 3:
			$format = "[^[:alnum:]^_^\-]";
			break;
		case 4:
			$format = "[^[:lower:]^[:digit:]^_^\-]";
			break;
		default:
			$format = "[^[:alnum:]]";
			break;
	}

	// 2015/10/15 Upd by S.Okamoto	Return値が反転していたので修正
	/*
	if (!preg_match("/" . $format . "/", $p_target)) {
		return false;
	}
	return true;
	*/
//	return !ereg($format, $p_target);

	return !preg_match("/" . $format . "/", $p_target);

}

/**
 * 暦日チェック(yyyy/mm/dd)
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 *									[yyyy-mm-dd] / [yyyy/mm/dd] / [yyyy mm dd] / [yyyy.mm.dd]
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	
 */
function chk_date($p_target) {
	$sep_format = "-\/ .";
//	if (ereg("^([0-9]+)[$sep_format]([0-9]+)[$sep_format]([0-9]+)$", $p_target, $m)) {
	if (preg_match("/^([0-9]+)[$sep_format]([0-9]+)[$sep_format]([0-9]+)$/", $p_target, $m)) {
		return checkdate($m[2], $m[3], $m[1]);
	}
	return false;
}

/*
 * バイト数チェック
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @param	integer	$p_byte			バイト数
 * @return	boolean					true - チェックOK / false - チェックNG
 * @info    なし
 */
function chk_byte($p_target, $p_byte) {

	// 対象文字列をSJISにしないと全角が2バイトにならないため、チェック対象文字列をSJISに変換
	$target = (ini_get("mbstring.internal_encoding") !== "SJIS") ? mb_convert_encoding($p_target, "SJIS") : $p_target;

	$ret = (strlen($target) > $p_byte) ? false : true;
	return $ret;
}

/**
 * 全て全角文字かチェックする
 * @access	public
 * @param	string	$target		チェック対象文字列
 * @return	boolean				ture-チェックOK / false-チェックNG
 */
function chkAllFullWidthCharacter($target) {
	// UTF-8に変換
	$chkStr = mb_convert_encoding($target, "UTF-8", mb_detect_encoding($target));
	return !preg_match("/(?:\xEF\xBD[\xA1-\xBF]|\xEF\xBE[\x80-\x9F])|[\x20-\x7E]/", $chkStr);
}

/**
 * 全て半角文字かチェックする
 * @access	public
 * @param	string	$target		チェック対象文字列
 * @return	boolean				ture-チェックOK / false-チェックNG
 */
function chkAllHalfWidthCharacter($target) {
	// SJISに変換
	$chkStr = mb_convert_encoding($target, "SJIS", mb_detect_encoding($target));
	return (mb_strwidth($chkStr,"SJIS") == mb_strlen($chkStr,"SJIS"));
}

/**
 * 半角英数が混在しているかをチェックする
 * @access	public
 * @param	string	$target		チェック対象文字列
 * @return	boolean				ture-チェックOK / false-チェックNG
 */
function chkNumberAndAlpha($target){
	return preg_match("/[0-9].*[a-zA-Z]|[a-zA-Z].*[0-9]/", $target);
}

/**
 * 半角英字かチェックする
 * @access	public
 * @param	string	$target		チェック対象文字列
 * @return	boolean				ture-チェックOK / false-チェックNG
 */
function chk_alpha($target){
	return preg_match("/^[a-zA-Z]+$/", $target);
}
?>
