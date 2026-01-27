<?php
/*
 * MailSendCommon.php
 * 
 * (C)SmartRams Co.,Ltd. 2016 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * メール送信処理拡張クラス
 * 
 * メール送信処理を基準に拡張処理を行う
 * 
 * @package	
 * @author   吉田 誠
 * @version  1.1
 * @since    2016/10/25 初版作成 吉田誠
 * @since    2017/06/19 v1.1更新 岡本静子	マルチパート送信対応
 * @info	
 */

class MailSendCommon extends SmartMailSend {

	/**
	 * コンストラクタ
	 * @access	public
	 * @param	なし
	 * @return	インスタンス
	 */

	public function __construct() {
		global $MAIL_PARAM;
		// メール送信処理インスタンス生成
		parent::__construct(MAIL_PROTOCOL, $MAIL_PARAM);
	}

	/**
	 * 基本データセット
	 * @access	public
	 * @param	string		$p_from_key		送信元データ取得設定キー(設定マスタ)
	 * @param	string		$p_cc			CCアドレス
	 * @param	string		$p_bcc			Bccアドレス
	 * @param	string		$p_messageid	メッセージID
	 * @return	なし
	 */
	function set_base_header($p_from = "", $p_cc = "", $p_bcc = "", $p_messageid = "") {

		$from = $p_from;																			// From
		$cc = (mb_strlen($p_cc) > 0) ? $p_cc : "";													// Cc
		$bcc = (mb_strlen($p_bcc) > 0) ? $p_bcc : "";												// Bcc
		$return_path = MAIL_ERROR;																	// Return-Path
		$messageid = (mb_strlen($p_messageid) > 0) ? "<" . $p_messageid . ">" : "";					// MessageId
		// 初期データセット(From/Cc/Bcc/Return-Path/option_header/MessageId)
		$this->setMailSendData($from, "", $cc, $bcc, $return_path, array(), $messageid);

	}

	/**
	 * メール情報置換処理
	 * @access	public
	 * @param	string		$p_target		対象文字列
	 * @param	string		$p_repTag		対象文字列
	 * @param	array		$p_row			置換データ
	 * @return	string						変換後文字列
	 */
	function replace($p_target, $p_repTag, $p_row = "") {
		$ret = $p_target;

		$temp_mail = new TemplateMail();		// メール情報置換インスタンス生成
		$ret = $temp_mail->replace($ret, $p_repTag, $p_row);		// メール情報置換処理
		$ret = mb_convert_kana($ret, "KV");				// 半角カナを全角カナに変換

		return $ret;
	}

	//--- 2017/06/19 Upd S by S.Okamoto マルチパート送信対応
	/**
	 * 送信処理
	 * @access	public
	 * @param   string  $p_subject				メールタイトル
	 * @param   string  $p_body					本文 (TEXT)
	 * @param   string  $p_body_html			本文 (HTML)
	 * @param	boolean	$p_isMultipart			送信タイプがマルチパートか否か (true:マルチパート / false:シングルパート)
	 * @return  なし
	 */
	function send($p_subject = "", $p_body = "", $p_body_html = "", $p_isMultipart = false) {

		// メール情報置換処理
		$subject  = mb_convert_kana($p_subject, "KV");
		$body     = mb_convert_kana($p_body, "KV");
		$bodyHtml = mb_convert_kana($p_body_html, "KV");

		if ($p_isMultipart) {
			// マルチパート
			if (mb_strlen($body) == 0 || mb_strlen($bodyHtml) == 0) return false;
			parent::makeMultipart($subject, $p_body_html, $body);		// 親クラスのメールの作成関数をCall
		} else {
			// シングルパート
			parent::make($subject, $body);		// 親クラスのメールの作成関数をCall
		}
		return parent::send();		// 親クラスの同名関数をCall
	}

	/**
	 * 送信処理(旧)
	 * @access	public
	 * @param   string  $p_subject				メールタイトル
	 * @param   string  $p_body					本文
	 * @param   string  $p_body_type			本文種別(0:text / 1:html)
	 * @param   array   $p_add_attachment		添付ファイル配列
	 * @return  なし
	 */
	function _send($p_subject = "", $p_body = "", $p_body_type = 0, $p_add_attachment = array()) {

		// メール情報置換処理
		$subject = mb_convert_kana($p_subject, "KV");
		$body    = mb_convert_kana($p_body, "KV");

		parent::make($subject, $body, $p_body_type, $p_add_attachment);		// 親クラスのメールの作成関数をCall
		return parent::send();			// 親クラスの同名関数をCall
	}
	//--- 2017/06/19 Upd E

	/**
	 * デストラクタ
	 * @access  public
	 * @param   なし
	 * @return  なし
	 */
	public function __destruct() {
	}

}
?>