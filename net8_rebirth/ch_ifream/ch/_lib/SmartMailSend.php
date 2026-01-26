<?php
/*
 * SmartMailSend.php
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
 * メールの送信処理クラス
 *
 * メールの送信処理を行う
 *
 * @package
 * @author  金光 峰範
 * @version PHP5.x.x
 * @since   2008/03/10 初版作成 金光 峰範 新規作成
 * @since	2009/09/16 全面改修 岡本 静子 命名規約見直しに伴いcls_mail_sendを破棄し新規作成
 * @since	2017/01/27 追加改修 鶴野 美香 メールの作成(マルチパート対応)追加
 * @since	2017/06/19 追加改修 岡本 静子 SmartMailDecomailクラス改修(Mail_mimeのバージョンアップ対応)
 * @since	2020/05/21 追加改修 岡本 静子 多言語向け送信のためエンコードを変更
 *											ISO-2022-JP / JIS → UTF-8
  *											7bit → 8bit
 * @info    予備知識として http://pear.php.net/manual/ja/package.mail.mail.factory.php
 *          http://pear.php.net/manual/ja/package.mail.mail.send.php を読むこと
 */

require_once __DIR__ . '/Mail.php';
require_once __DIR__ . '/Mail/mime.php';

class SmartMailSend {

	private $_mail_object = false;			// メール送信プロトコルオブジェクト
	private $_recipients;					// 受取人アドレス(配列かカンマ区切り)
	private $_headers = array();			// ヘッダー配列
	private $_hostname = "localhost";		// ホスト名
	private $_body = "";					// メール本文
	private $_From = "";					// メールFrom
	private $_To = "";						// メールTo
	private $_Cc = "";						// メールCc
	private $_Bcc = "";						// メールBcc
	private $_enFrom = "";					// メールFrom エンコード
	private $_enTo = "";					// メールTo   エンコード
	private $_enCc = "";					// メールCc   エンコード
	private $_enBcc = "";					// メールBcc  エンコード
	private $_enReturnPath = "";			// メールリターンアドレス(カンマ指定不可)
	private $_option_headerAry = array();	// オプションヘッダー配列

	/**
	 * コンストラクタ
	 * @access  public
	 * @param   string  $p_backend		バックエンド名(mail|sendmail|smtp)
	 * @param   array   $p_params		バックエンド固有のパラメータ配列
	 * @return  インスタンス
	 */
	public function __construct($p_backend, $p_params = array()) {

		if (PEAR::isError($mail_object = Mail::factory($p_backend, $p_params))) {
			print $mail_object->getMessage();
		} else {
			$this->_mail_object = $mail_object;
		}
	}

	/**
	 * メールの作成に使用する初期データ
	 * @access  public
	 * @param   string  $p_from					メールFROM
	 * @param   string  $p_to					メールTO
	 * @param   string  $p_cc					CCアドレス
	 * @param   string  $p_bcc					Bccアドレス
	 * @param   string  $p_return_path			戻り先アドレス
	 * @param   array   $p_option_headerAry		オプションヘッダー配列
	 * @return  なし
	 */
	function setMailSendData($p_from, $p_to = "", $p_cc = "",  $p_bcc = "", $p_return_path = "", $p_option_headerAry = array()) {
		
		if (mb_strlen($p_from) > 0) $this->setFromData($p_from);
		if (mb_strlen($p_to) > 0)   $this->setToData($p_to);
		if (mb_strlen($p_cc) > 0)   $this->setCcData($p_cc);
		if (mb_strlen($p_bcc) > 0)  $this->setBccData($p_bcc);
		if (mb_strlen($p_return_path) > 0) $this->setReturnData($p_return_path);
		$this->setOpHeaderData();
	}

	/**
	 * メールの作成(通常作成)
	 * @access  public
	 * @param   string  $p_subject				メールタイトル
	 * @param   string  $p_body					本文
	 * @param   string  $p_body_type			本文種別(0:text / 1:html)
	 * @param   array   $p_add_attachment		添付ファイル配列
	 * @return  なし
	 */
	public function make($p_subject = "", $p_body = "", $p_body_type = 0, $p_add_attachment = array()) {
		// 初期化
		$this->_recipients = "";
		$this->_headers = array(  "From" => $this->_enFrom
//								, "Subject" => mb_encode_mimeheader($p_subject, "JIS", ini_get("mbstring.internal_encoding"))
								, "Subject" => mb_encode_mimeheader($p_subject, "UTF-8", ini_get("mbstring.internal_encoding"))
								, "Date"	=> date("r")
								, "Message-Id"   => "<".uniqid("")."@".  $this->getHostname() .">"
							   );
		$this->_headers = array_merge($this->_headers, $this->_option_headerAry);  // オプションヘッダーのマージ

		// HTMLメールの場合
		if ($p_body_type == 1) {
			
			//Toは空でもヘッダ指定が必要なのでコメントアウト
			//if (mb_strlen($this->_enTo) > 0) {
				$this->_recipients .= $this->_To;
				$this->_headers["To"] = $this->_enTo;
			//}
			if (mb_strlen($this->_enCc) > 0) {
				$this->_recipients .= (mb_strlen($this->_recipients) >  0) ? "," . $this->_Cc : $this->_Cc;
				$this->_headers["Cc"] = $this->_enCc;
			}
			if (mb_strlen($this->_enBcc) > 0) {
				$this->_recipients .= (mb_strlen($this->_recipients) >  0) ? "," . $this->_Bcc : $this->_Bcc;
				//##### qmailの場合、ヘッダーにメアドが残るのでコメントアウト
				//$this->_headers["Bcc"] = $this->_enBcc;
			}
			if (mb_strlen($this->_enReturnPath) > 0) $this->_headers["Return-Path"] = $this->_enReturnPath;

			$html_params["head_charset"] = "UTF-8";
			//$html_params["html_encoding"] = "ISO-2022-JP";	// softbankで受け取れなくなる
			$html_params["html_encoding"] = "8bit";
			$html_params["html_charset"] = "UTF-8";
			//$html_params["text_encoding"] = "ISO-2022-JP";
			$html_params["text_charset"] = "UTF-8";

			$mime = new SmartMailDecomail();
			$mime->setTxtBody('');
//			$mime->setHTMLBody(mb_convert_encoding($p_body, "JIS", ini_get("mbstring.internal_encoding")));
			$mime->setHTMLBody(mb_convert_encoding($p_body, "UTF-8", mb_internal_encoding()));

			// インライン画像
			foreach ($p_add_attachment as $key => $value) {
				if (mb_strlen($value) > 0) {
					$info = getimagesize($value);
					$content_type = $info["mime"];
					$cid = $key . "@" . $this->getHostname();
					$mime->addHTMLImage($value, $content_type, basename($value), true, $cid);
				}
			}

			$this->_body = $mime->get($html_params, $p_body_type);
			$this->_headers = $mime->headers($this->_headers);
		} else {
//			$this->_recipients = $this->getAddressOnly($p_recipients);
			// メールヘッダー項目の定義
			$this->_headers["Content-Type"] = "text/plain; charset=\"UTF-8\"";
			$this->_headers["Content-Transfer-Encoding"] = "8bit";
			
			// オプションヘッダーのマージ
			$this->_headers = array_merge($this->_headers, $this->_option_headerAry);
			
			//Toは空でもヘッダ指定が必要なのでコメントアウト
			//if (mb_strlen($this->_enTo) > 0) {
				$this->_recipients .= $this->_To;
				$this->_headers["To"] = $this->_enTo;
			//}
			if (mb_strlen($this->_enCc) > 0) {
				$this->_recipients .= (mb_strlen($this->_recipients) >  0) ? "," . $this->_Cc : $this->_Cc;
				$this->_headers["Cc"] = $this->_enCc;
			}
			if (mb_strlen($this->_enBcc) > 0) {
				$this->_recipients .= (mb_strlen($this->_recipients) >  0) ? "," . $this->_Bcc : $this->_Bcc;
				//##### qmailの場合、Bccで送信時ヘッダーにメアドが残るのでコメントアウト
				//$this->_headers["Bcc"] = $this->_enBcc;
			}
			if (mb_strlen($this->_enReturnPath) > 0) $this->_headers["Return-Path"] = $this->_enReturnPath;

//			$this->_body = mb_convert_encoding($p_body, "JIS", ini_get("mbstring.internal_encoding"));
//print "#".ini_get("mbstring.internal_encoding")."#";
//print "#".mb_internal_encoding()."#";
			$this->_body = mb_convert_encoding($p_body, "UTF-8", mb_internal_encoding());
/*
			//##### 2009/12/15 Upd S by S.Okamoto メール添付考慮 #####
			$body_params["head_charset"] = "ISO-2022-JP";
			$body_params["text_charset"] = "ISO-2022-JP";

			$mime = new Mail_Mime("\n");
			$mime->setTxtBody($this->_body);
			// 添付ファイル
			foreach ($p_add_attachment as $key => $value) {
				if (mb_strlen($value) > 0) $mime->addAttachment($value, 'application/octet-stream', mb_convert_encoding(basename($value), 'ISO-2022-JP', FILENAME_ENCODING));
			}
			$this->_body = $mime->get($body_params);
			$this->_headers = $mime->headers($this->_headers);
			//##### 2009/12/15 Upd E
*/
		}
	}

	/**
	 * メールの作成(マルチパート対応)
	 * @access  public
	 * @param   string  $p_subject				メールタイトル
	 * @param   string  $p_bodyHtml				HTML本文
	 * @param   string  $p_bodyText				TEXT本文
	 * @param   array   $p_add_attachment		添付ファイル配列
	 * @return  なし
	 */
	public function makeMultipart($p_subject = "", $p_bodyHtml = "", $p_bodyText = "", $p_add_attachment = array()) {
		$internalEncoding = ini_get("mbstring.internal_encoding");

		// 初期化
		$this->_recipients = "";
		$this->_headers = array(  "From"    => $this->_enFrom
								, "Subject" => mb_encode_mimeheader($p_subject, "UTF-8", $internalEncoding)
								, "Date"    => date("r")
								, "Message-Id" => "<".uniqid("")."@".  $this->getHostname() .">"
							   );
		$this->_headers = array_merge($this->_headers, $this->_option_headerAry);  // オプションヘッダーのマージ

		$this->_recipients .= $this->_To;
		$this->_headers["To"] = $this->_enTo;

		if (mb_strlen($this->_enCc) > 0) {
			$this->_recipients .= (mb_strlen($this->_recipients) >  0) ? "," . $this->_Cc : $this->_Cc;
			$this->_headers["Cc"] = $this->_enCc;
		}
		if (mb_strlen($this->_enBcc) > 0) {
			$this->_recipients .= (mb_strlen($this->_recipients) >  0) ? "," . $this->_Bcc : $this->_Bcc;
		}
		if (mb_strlen($this->_enReturnPath) > 0) $this->_headers["Return-Path"] = $this->_enReturnPath;

		$html_params["head_charset"] = "UTF-8";
		$html_params["html_encoding"] = "8bit";
		$html_params["html_charset"] = "UTF-8";
		$html_params["text_charset"] = "UTF-8";

		// HTML文字列から画像パスを取得
		preg_match_all('/<img(?:.*?)src=[\"\'](.*?)[\"\'](?:.*?)>/e', $p_bodyHtml, $p_add_attachment);

		$mime = new SmartMailDecomail("\n");
		$mime->setTxtBody(mb_convert_encoding($p_bodyText, "UTF-8", $internalEncoding));
		$mime->setHTMLBody(mb_convert_encoding($p_bodyHtml, "UTF-8", $internalEncoding));

		// インライン画像
		foreach ($p_add_attachment[1] as $key => $value) {
			if (mb_strlen($value) > 0) {
				$info = getimagesize($value);
				$content_type = $info["mime"];
				$cid = $key . "@" . $this->getHostname();
				$mime->addHTMLImage($value, $content_type, basename($value), true, $cid);
			}
		}

		$this->_body = $mime->get($html_params, 1);
		$this->_headers = $mime->headers($this->_headers);

	}


	/**
	 * メールの作成(生転送)
	 * @access  public
	 * @param   object  $p_MailParse			メール解析クラスオブジェクト
	 * @param   string  $p_transfer_address		転送者アドレス
	 * @param   string  $p_forward_address		転送先アドレス
	 * @param   array   $p_option_headerAry		オプションヘッダー配列
	 * @return  なし
	 */
//	public function fwmake($p_MailParse, $p_transfer_address, $p_forward_address, $p_option_headerAry = array()) {
	public function fwmake($p_MailParse, $p_transfer_address, $p_forward_address) {

		$this->_recipients = $this->getAddressOnly($p_forward_address);
		// 追加されるメールヘッダー項目の定義
		$this->_headers = array(  "Return-Path" => $this->encAddress($p_transfer_address)					// エラー時戻り先アドレス
								, "Resent-Date" => date("r")												// 転送時刻
								, "Resent-From" => $this->encAddress($p_transfer_address)					// 転送者アドレス
								, "Resent-To" => $this->encAddress($p_forward_address)						// 転送先アドレス
								, "Resent-Message-Id" => "<".uniqid("")."@".  $this->getHostname() .">"); 	// メッセージID
		//--- 2009/11/10 Upd by S.Okamoto
		//               $option_headerAryを$p_option_headerAryにすると転送したリターンメールが正常な内容で届かないため
		//               敢えて@で回避(要調査)
		// オプションヘッダーのマージ
//		$this->_headers = @array_merge($this->_headers, $option_headerAry);
		//--- 2009/11/10 Upd E

		// メールヘッダーのオブジェクトを取得
		$MailHeader = $p_MailParse->getHeaderArray();
		
		foreach ($MailHeader as $key => $value) {
			//if ($key == "received") continue;		// レシーブは不必要
			if ($key == "received" || $key == "Received") continue;		// レシーブは不必要
			// 以下の処理が必要かどうだか不明だが、気持ち悪いのでメールの生データに見えるようなヘッダー文字に変換
			$keyAry = explode("-", $key);
			foreach ($keyAry as $Hkey => $Hvalue) {
				if ($Hvalue == "mime") {
					$keyAry[$Hkey] = "MIME";
				} else {
					// content-transfer-encoding → Content-Transfer-Encoding に変換
					$keyAry[$Hkey] = strtoupper( substr( $Hvalue, 0, 1 ) ) . substr( $Hvalue, 1 );
				}
			}
			$this->_headers[implode("-", $keyAry)] = $value;  // ヘッダーに値追加
		}
		// メール本文代入
		$this->_body = str_replace("\r\n", "\n", $p_MailParse->getRawBody());
	}

	/**
	 * メールの送信
	 * @access  public
	 * @param   なし
	 * @return  boolean		送信の可否
	 */
	public function send() {
		if (PEAR::isError($this->_mail_object->send($this->_recipients
												  , $this->_headers
												  , $this->_body))) {
			return false;
		}
		return true;
	}

	/**
	 * 受取人アドレスリスト(カンマ区切り)作成
	 * @access  public
	 * @param   array   $p_addressAry	アドレスデータの配列
	 * @return  string					アドレスリスト(アドレス部のみ)
	 */
	public function makeRecipients($p_addressAry) {

		$ret = "";
		foreach ($p_addressAry as $address) {
			// 空文字以外の場合
			if (mb_strlen(trim($address)) > 0) $ret .= "," . $this->getAddressOnly($address);
		}
		if (mb_strlen($ret) > 0) $ret = substr( $ret, 1 );		// 頭一文字(カンマ)を削除

		return $ret;
	}

	/**
	 * メールアドレスのエスケープ
	 * @access  public
	 * @param   string  $p_addressCSV	エスケープ元メールアドレス
	 * @return  bool					送信の可否
	 */
	private function encAddress($p_addressCSV) {
		$ret = "";
		foreach (explode(",", $p_addressCSV) as $address) {
			if (($pos = strpos($address, "<")) !== false) {
				$elementAry = explode("<", $address);
				$name = trim($elementAry[0]);   // メールアドレス内にある名前取得
				if (preg_match("/=\?.*\?=/", $name) == 0) {
					// 未エンコードの文字列はエンコードをかける(英字オンリーだとかからないのはテスト済み)
					$name = mb_encode_mimeheader($name, "UTF-8", ini_get("mbstring.internal_encoding"));
				}
				$elementAry[0] = $name . " ";
				$ret .= "," . implode("<", $elementAry);
			} else {
				$ret .= "," . $address;
			}
		}
		if (mb_strlen($ret) > 0) $ret = substr( $ret, 1 );   // 頭一文字(カンマ)を削除

		return $ret;
	}

	/**
	 * メールアドレス部分のみの抽出
	 * @access  public
	 * @param   mixed   $p_Address		配列またはカンマ区切りのメールアドレスデータ
	 * @return  mixed					入力の形式にあわせて返す
	 */
	public function getAddressOnly($p_Address) {
		$bool_is_array = is_array($p_Address);

		// カンマ区切りの場合は配列にあわせる
		if (!$bool_is_array) {
			$AddressAry = explode(",", $p_Address);
		} else {
			$AddressAry = $p_Address;
		}

		$ret = array(); // 返却
		foreach ($AddressAry as $value) {
			if (preg_match("/<(.*)>/", $value, $matches) > 0) {
				$ret[] = trim($matches[1]);
			} else {
				$ret[] = trim($value);
			}
		}

		// 入力パラメータの形式にあわせて値を戻す
		if($bool_is_array) {
			return $ret;
		} else {
			return implode(",", $ret);
		}
	}

	/**
	 * ホスト名の取得
	 * @access  public
	 * @param   なし
	 * @return  string  ホスト名を返す
	 */
	public function getHostname() {
		return $this->_hostname;
	}

	/**
	 * ホスト名のセット
	 * @access  public
	 * @param   string  $p_hostname				ホスト名
	 * @return  なし
	 */
	public function setHostname($p_hostname) {
		$this->_hostname = $p_hostname;
	}

	/**
	 * Fromデータセット
	 * @access  public
	 * @param   string  $p_from					メールFROM
	 * @return  なし
	 */
	function setFromData($p_from) {
		$this->_From   = $this->getAddressOnly($p_from);
		$this->_enFrom = $this->encAddress($p_from);
	}

	/**
	 * Toデータセット
	 * @access  public
	 * @param   string  $p_to					メールTO
	 * @return  なし
	 */
	function setToData($p_to) {
		$this->_To   = $this->getAddressOnly($p_to);
		$this->_enTo = $this->encAddress($p_to);
	}
	
	/**
	 * Ccデータセット
	 * @access  public
	 * @param   string  $p_cc					CCアドレス
	 * @return  なし
	 */
	function setCcData($p_cc) {
		$this->_Cc   = $this->getAddressOnly($p_cc);
		$this->_enCc = $this->encAddress($p_cc);
	}
	
	/**
	 * Bccデータセット
	 * @access  public
	 * @param   string  $p_bcc					Bccアドレス
	 * @return  なし
	 */
	function setBccData($p_bcc) {
		$this->_Bcc   = $this->getAddressOnly($p_bcc);
		$this->_enBcc = $this->encAddress($p_bcc);
	}
	
	/**
	 * Returnデータセット
	 * @access  public
	 * @param   string  $p_return_path			戻り先アドレス
	 * @return  なし
	 */
	function setReturnData($p_return_path) {
		$this->_enReturnPath = $this->getAddressOnly($p_return_path);
	}
	
	/**
	 * オプションヘッダーデータセット
	 * @access  public
	 * @param   array   $p_option_headerAry		オプションヘッダー配列
	 * @return  なし
	 */
	function setOpHeaderData($p_option_headerAry = array()) {
		$this->_option_headerAry = $p_option_headerAry;
	}

	/**
	 * デストラクタ
	 * @access  public
	 * @param   なし
	 * @return  なし
	 */
	public function __destruct() {
	}
}


//--- 2017/06/19 Upd S by S.Okamoto SmartMailDecomailクラス改修(Mail_mimeのバージョンアップ対応)
//@@@@@@@@@@ デコメールのMIMEエンコード処理クラス
class SmartMailDecomail extends Mail_mime {

	/**
	 * コンストラクタ
	 * @access  public
	 * @param   string  $p_crlf		使用する改行文字
	 * @return  インスタンス
	 */
	public function __construct($p_crlf = "\r\n") {
		parent::__construct($p_crlf);			// MIME メールの生成と送信
	}

	/**
	 * イメージをメッセージに追加(Mail_mime::addHTMLImage のオーバーライド)
	 * @access  public
	 * @param   string	$p_file		画像ファイル名または画像データ自身
	 * @param   string	$p_type		ファイルの種類
	 * @param   string	$p_name		画像ファイル名(ファイルが画像データ時のみ使用)
	 * @param   boolean	$p_isfile	$p_fileがファイル名か否か
	 * @param   string	$p_cid		Content-ID
	 * @return  なし
	 */
	public function addHTMLImage($p_file, $p_type = 'application/octet-stream'
											,$p_name = '', $p_isfile = true, $p_cid = '') {
		$filedata = ($p_isfile) ? $this->file2str($p_file) : $p_file;
		if ($p_isfile) {
			$filename = (mb_strlen($p_name) > 0) ? $p_name : $p_file;
		} else {
			$filename = $p_name;
		}
		if (PEAR::isError($filedata)) return $filedata;
		$cid = (mb_strlen($p_cid) > 0) ? $p_cid : md5(uniqid(time()));

		$this->html_images[] = array(
										  'body'   => $filedata
										, 'name'   => $filename
										, 'c_type' => $p_type
										, 'cid'    => $cid
									);
		return true;
	}

	/**
	 * イメージをメッセージに追加(Mail_mime::_addHtmlImagePart のオーバーライド)
	 * @access  public
	 * @param   object	$p_obj		オブジェクト
	 * @param   array	$p_value	パラメータの連想配列
	 * @return  なし
	 */
	public function _addHtmlImagePart($p_obj, $p_value) {
		// image/xxx; name=xxx の様に name=xxx を付けないと画像が表示されない
		$params['content_type'] = $p_value['c_type']. ";\r\n name=\"" . $p_value['name'] . "\"";
		$params['encoding'] = 'base64';
		$params['cid'] = $p_value['cid'];
		$p_obj->addSubpart($p_value['body'], $params);
	}

	/**
	 * メッセージの構築(Mail_mime::get のオーバーライド)
	 * @access  public
	 * @param   array	$p_build_params		パラメータの連想配列
	 * @param   integer	$p_type				メール種別(0:text / 1:html)
	 * @return  string	メッセージ本文
	 * @info	デコメのインライン画像の場合に対応
	 * 				multipart/related
	 *				├ multipart/alternative
	 * 				│ ├ text/plain
	 * 				│ └ text/html
	 * 				└ image/xxx (*n)
	 */
	 // 2019-04-17 p_skip_headを追加
	public function get($p_build_params = null, $p_type = 0, $p_skip_head = false) {

		if ($p_type == 0) {
			parent::get($p_build_params);
			return;
		}

		// パラメータ取得
		if (isset($p_build_params)) {
			while (list($key, $value) = each($p_build_params)) {
				$this->build_params[$key] = $value;
			}
		}

		// 本文のリンクをcid指定に置き換え
		if (!empty($this->html_images) && isset($this->htmlbody)) {
			foreach ($this->html_images as $value) {
				$regex = '#(\s)((?i)src|background|href(?-i))\s*=\s*(["\']?)' . preg_quote($value['name'], '#') .
						 '\3#';
				$rep = '\1\2=\3cid:' . $value['cid'] .'\3';
				$this->htmlbody = preg_replace($regex, $rep,
									   $this->htmlbody
								   );
			}
		}

		$null = null;
		$attachments = !empty($this->parts)        ? true : false;
		$html_images = !empty($this->html_images)  ? true : false;
		$html = !empty($this->htmlbody)            ? true : false;
		$text = (!$html && !empty($this->txtbody)) ? true : false;

		switch (true) {
			// デコメール本文のみ
			case $html && !$attachments && !$html_images:
				if (isset($this->txtbody)) {
					$message =& $this->addAlternativePart($null);
					$this->addTextPart($message, $this->txtbody);
					$this->addHtmlPart($message);
				} else {
					$message =& $this->addHtmlPart($null);
				}
				break;

			// デコメール本文とインライン画像
			case $html && !$attachments && $html_images:
				if (isset($this->txtbody)) {
					$message =& $this->addRelatedPart($null);
					$alt =& $this->addAlternativePart($message);
					$this->addTextPart($alt, $this->txtbody);
				} else {
					die("no text part");
				}
				$this->addHtmlPart($alt);
				for ($i = 0; $i < count($this->html_images); $i++) {
					$this->addHtmlImagePart($message, $this->html_images[$i]);
				}
				break;

			// デコメール本文と添付画像
			case $html && $attachments && !$html_images:
				$message =& $this->addMixedPart();
				if (isset($this->txtbody)) {
					$alt =& $this->addAlternativePart($message);
					$this->addTextPart($alt, $this->txtbody);
					$this->addHtmlPart($alt);
				} else {
					die("no text part");
				}
				for ($i = 0; $i < count($this->parts); $i++) {
					$this->addAttachmentPart($message, $this->parts[$i]);
				}
				break;

			// デコメール本文と添付画像とインライン画像
			case $html AND $attachments AND $html_images:
				$message =& $this->addMixedPart();
				if (isset($this->txtbody)) {
					$message =& $this->addRelatedPart($null);
					$alt =& $this->addAlternativePart($message);
					$this->addTextPart($alt, $this->txtbody);
				} else {
					die("no text part");
				}
				$this->addHtmlPart($alt);
				for ($i = 0; $i < count($this->html_images); $i++) {
					$this->addHtmlImagePart($message, $this->html_images[$i]);
				}
				for ($i = 0; $i < count($this->parts); $i++) {
					$this->addAttachmentPart($message, $this->parts[$i]);
				}
				break;

			default:
				// setHTMLBody されていないとデコメールとみなさずエラー
				die("not decomail.");
				break;
		}

		if (isset($message)) {
			$output = $message->encode();
			$this->headers = array_merge($this->headers,
										  $output['headers']);
			return $output['body'];
		} else {
			return false;
		}
	}

}
//--- 2017/06/19 Upd E

?>