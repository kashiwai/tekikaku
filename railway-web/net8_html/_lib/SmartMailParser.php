<?php
/*
 * SmartMailParser.php
 * 
 * (C)SmartRams Corp. 2009- All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * 動画・画像添付(マルチパート)対応 メール分析処理クラス
 * 
 * 動画・画像添付(マルチパート)対応 メール受信内容取得/分析処理を行う
 *
 * @package
 * @author	岡本 順子
 * @version	PHP5.x.x
 * @since	2009/04/24 初版作成 岡本 順子 顧客向けサンプルとしてPHP4版の同等クラスから一部移植し、MailParseとして作成
 * @since	2009/04/26 一部加筆 岡本 順子 エラーReturnメールの場合のUnknownアドレス取得関数追加(ここだけeregではなくpregなので気持ち悪い？)
 * @since	2010/04/06 移植改定 須増 圭介 PHP4版へ再移植し、SmartMailParseとして作成
 * @since	201X/XX/XX 移植改定 岡本 静子 PHP5版として再々移植
 * @since	2013/07/09 全面改修 岡本 順子 各種切り出し用の正規表現の改修(ヘッダ内の改行への対応)
 *       	                              multipart/alternative([text/plain]と[text/html]の複合)への対応
 *       	                              デコード元エンコーディングのISO-2022-JP(JIS)以外への対応
 *       	                              文字エンコーディング変換にcharset及びPHP処理エンコードを考慮 等々...
 *       	                              上記に伴い、全面的な構造改定と共にSmartMailParserとして改修
 *       	                              ■ ・・・汚いベタベタのコードになってしまったが、力尽きたのでこの辺で妥協 orz   ■
 *       	                              ■ 本当はマルチパートの分割処理を再帰化してシンプルにし、                       ■
 *       	                              ■ Bエンコーディング(base64)だけではなくQエンコーディング(Quated-Printable)にも ■
 *       	                              ■ 対応しないとダメなのだが・・・                                               ■
 *       	
 * @info	multipart/alternativeへの対応は行うが、
 *      	基本的にマルチパートのツリー(入れ子)構造は考慮していない
 */
 
require_once 'Mail/mimeDecode.php';

// 入力ファイル
define("STD_IN", "php://stdin");

// デバッグ出力
define("DBG_MAIL_LOGOUT" , false);				// true:ログ書き出しあり / false:ログ書き出しなし
define("DBG_MAIL_LOGTEMP", "/log/log.txt");				// ログファイルテンポラリパス ※書き込み権限を付与しておくこと
define("DBG_MAIL_LOGFILE", "/log/{%Message-ID%}.txt");	// ログファイル確定パス       ※書き込み権限を付与しておくこと

// エラー判定対象本文抽出(※送信MTAのフォーマットによって切り出しワードを変えてください)
/*
	要するに、以下の２単語に囲まれた文字列を受信MTAからのReturn内容として判定対象とする
	MAILER-DAEMONからのエラーReturnメールの場合は、
	「送信MTAのテンプレート文＋受信MTAからのReturn内容(※1)＋(場合によっては)送信メール内容(※2)」
		※1:受信MTAからのReturnコードとメッセージを元に送信MTAによって生成
		※2:送信MTAがqmailの場合に付与されてくるはず
	に整形された内容が本文として送信されてくる
*/
//--- postfix用
define("UNKNOWN_TARGET_FROM", " The mail system\n");	// MTAが先頭に付与するテンプレート文の末尾単語
define("UNKNOWN_TARGET_TO"  , "");						// MTAが末尾に付与するテンプレート文の先頭単語
//--- qmail用
//define("UNKNOWN_TARGET_FROM", " Sorry it didn't work out.\n");
//define("UNKNOWN_TARGET_TO"  , "\n--- Below this line is a copy of the message.");
//--- sendmail用
//define("UNKNOWN_TARGET_FROM", "");
//define("UNKNOWN_TARGET_TO"  , "");

// 550 unknown にてフィルタによりエラーとなったと判定されるメッセージ文字列
define("UNKNOWN_FILTER_MSG", "(in reply to end of DATA command)");


class SmartMailParser {

	// メンバ変数定義
	var $_internalEncoding = "UTF-8";		// PHP処理エンコーディングデフォルト値
	var $_rawHeader = "";			// 生メールHeader
	var $_rawBody   = "";			// 生メールBody
	var $_headArray = array();		// メールヘッダ内容配列(連想配列 Keyは大文字)
	var $_body = "";				// メール本文
	var $_isAttach = false;			// 添付ありフラグ
	var $_attachArray = array();	// メールの添付パート生データ(配列)
	var $_isFilterUnknown = false;	// エラーReturnメールがフィルタによるものか否か
	var $_messageUnknown = "";		// エラーReturnメール補足メッセージ

	/**
	 * コンストラクタ
	 * @access	public
	 * @param	int		$p_in		入力ファイル
	 * @return	なし
	 * @info	入力ファイルに STD_IN("php://stdin") を指定した場合、
	 *      	標準入力からメール内容の読出しを行い内部展開を行う
	 */
	function SmartMailParser($p_in = STD_IN) {

		// 文字エンコーディング変換用にPHP処理エンコーディング保管
		if (ini_get("mbstring.internal_encoding") != "") {
			$this->_internalEncoding = strtoupper(ini_get("mbstring.internal_encoding"));
		}

		//--- 標準入力をオープンし、内容をバッファへ保存
		$stdin = fopen($p_in, "r");
		$mail = "";
		while (!feof($stdin)) {
			$buf = fread($stdin, 10240);
			$mail .= $buf;
		}
		fclose($stdin);

		//--- テンポラリログ出力
		$documentpath = dirname(__FILE__);
		if (DBG_MAIL_LOGOUT && (strtolower($p_in) == strtolower(STD_IN))) {
			$logfile = $documentpath . DBG_MAIL_LOGTEMP;
			$fp = @fopen($logfile, "w");
			if ($fp !== false) {
				flock($fp, LOCK_EX);
				fputs($fp, $mail);
				flock($fp, LOCK_UN);
				fclose($fp);
			}
		}


		//--- 一次分解
		$pos = mb_strpos($mail, "\n\n");
		$this->_rawHeader = mb_substr($mail, 0, $pos);		// Header部切り出し
		$this->_rawBody   = mb_substr($mail, $pos + 2);		// Body部切り出し

		//--- ヘッダー内容配列展開(Keyは大文字)
		$str = "";
		foreach (explode("\n", trim($this->_rawHeader)) as $value) {
			//@ if (eregi("^([a-z\-]+): (.+)", $value, $reg)) {
			if (preg_match("/^([a-z\-]+):\s*(.+)/i", $value, $reg)) {
				$str = strtoupper($reg[1]);
				if (mb_strlen($str) == 0) continue;
				$this->_headArray[$str] = $reg[2];
			} else {
				if (mb_strlen($str) == 0) continue;
				$this->_headArray[$str] .= "\n" . $value;
			}
		}

		//--- Content-Type確認
		$contentType = @$this->_headArray["CONTENT-TYPE"];
		$this->_isAttach = false;		// デフォルト:添付なし

		//@ if (eregi("multipart/mixed;[[:space:]\n\t]*boundary=\"?([^\"]+)\"?", $contentType, $reg)) {
		if (preg_match("/multipart\/mixed;\s*boundary=\"?([^\"]+)\"?/i", $contentType, $reg)) {
			//=== 【 添付ファイルあり 】
			$this->_isAttach = true;
			$partArray = explode("--{$reg[1]}", $this->_rawBody);	// Bodyをパートごとに分解
			for ($i=1; $i<count($partArray)-1; $i++) {
				if (trim($partArray[$i]) == "") continue;
				$isBody = false;	// メール本文の一部であるか
				// multipart/alternativeパートである
				//@ if (eregi("Content-Type:[[:space:]]*multipart/alternative;[[:space:]\n\t]*boundary=\"?([^\"]+)\"?\n", $partArray[$i], $reg)) {
				if (preg_match("/Content-Type:\s*multipart\/alternative;\s*boundary=\"?([^\"]+)\"?\n/i", $partArray[$i], $reg)) {
					// text/plainパートの抜き出し
					$textPart = $this->getPlainFromAlternative($partArray[$i], $reg[1]);
					// デコード
					$buf = "";
					if ($textPart !== false) {
						$buf = $textPart["BODY"];
						if ($textPart["ENCODE"] == "base64") $buf = base64_decode($buf);
						if ($textPart["CHAR"] != "" && $textPart["CHAR"] != $this->_internalEncoding) {
							$buf = mb_convert_encoding($buf, $this->_internalEncoding, $textPart["CHAR"]);
						}
					}
					// メール本文の一部として追加
					$this->_body .= $buf;
					$isBody = true;
				// text/plainパートである
				//@ } else if (eregi("Content-Type:[[:space:]]*text/plain;([[:space:]\n\t]*charset=\"?([^\"\n]+)\"?)?\n", $partArray[$i], $reg)) {
				} else if (preg_match("/Content-Type:\s*text\/plain;(\s*charset=\"?([^\"\n]+)\"?)?\n/i", $partArray[$i], $reg)) {
					$charset = strtoupper($reg[2]);		// charset取得
					// ファイル名の指定がない
					//@ if (!eregi("(file)?name=[\"]?([^\"]+)[\"]?\n", $partArray[$i])) {
					if (!preg_match("/(file)?name=[\"]?([^\"]+)[\"]?\n/i", $partArray[$i])) {
						// エンコーディング取得
						$encode = "";
						//@ if (eregi("Content-Transfer-Encoding:[[:space:]]*([:alnum:]+)", $partArray[$i], $reg)) $encode = strtolower($reg[1]);
						if (preg_match("/Content-Transfer-Encoding:\s*([a-zA-Z0-9]+)/i", $partArray[$i], $reg)) $encode = strtolower($reg[1]);
						// デコード
						$buf = mb_substr($partArray[$i], mb_strpos($partArray[$i], "\n\n") + 2);
						if ($encode == "base64") $buf = base64_decode($buf);
						if ($charset!= "" && $charset != $this->_internalEncoding) {
							$buf = mb_convert_encoding($buf, $this->_internalEncoding, $charset);
						}
						// メール本文の一部として追加
						$this->_body .= $buf;
						$isBody = true;
					}
				}
				// メール本文の一部ではない場合は添付パートとして保管
				if (!$isBody) array_push($this->_attachArray, $partArray[$i]);
			}

		//@ } else if (eregi("multipart/alternative;[[:space:]\n\t]*boundary=\"?([^\"]+)\"?", $contentType, $reg)) {
		} else if (preg_match("/multipart\/alternative;\s*boundary=\"?([^\"]+)\"?/i", $contentType, $reg)) {
			//=== 【 [text/plain]と[text/html]の混合 】
			// text/plainパートの抜き出し
			$textPart = $this->getPlainFromAlternative($this->_rawBody, $reg[1]);
			// デコード
			$buf = "";
			if ($textPart !== false) {
				$buf = $textPart["BODY"];
				if ($textPart["ENCODE"] == "base64") $buf = base64_decode($buf);
				if ($textPart["CHAR"] != "" && $textPart["CHAR"] != $this->_internalEncoding) {
					$buf = mb_convert_encoding($buf, $this->_internalEncoding, $textPart["CHAR"]);
				}
			}
			// メール本文保管
			$this->_body = $buf;

		} else {
			//=== 【 [text/plain]のみ・・・のハズ 】
			// エンコーディング取得
			$encode = strtolower(@$this->_headArray["CONTENT-TRANSFER-ENCODING"]);
			// charset取得
			//@ if (eregi("[^;]+;([[:space:]\n\t]*charset=\"?([^\"\n]+)\"?)?", @$this->_headArray["CONTENT-TYPE"], $reg)) {
			if (preg_match("/[^;]+;(\s*charset=\"?([^\"\n]+)\"?)?/i", @$this->_headArray["CONTENT-TYPE"], $reg)) {
				$charset = strtoupper($reg[2]);
			}
			// デコード
			$buf = $this->_rawBody;
			if ($encode == "base64") $buf = base64_decode($buf);
			if ($charset!= "" && $charset != $this->_internalEncoding) {
				$buf = mb_convert_encoding($buf, $this->_internalEncoding, $charset);
			}
			// メール本文保管
			$this->_body = $buf;
		}


		//--- 確定ログ出力
		if (DBG_MAIL_LOGOUT && (strtolower($p_in) == strtolower(STD_IN))) {
			$msgid = $this->getMessageID();
			if (mb_strlen($msgid) > 0) {
				$logfile = str_replace("{%Message-ID%}", $msgid, DBG_MAIL_LOGFILE);
				$logfile = $documentpath . $logfile;
				$fp = @fopen($logfile, "w");
				if ($fp !== false) {
					flock($fp, LOCK_EX);
					fputs($fp, $mail);
					flock($fp, LOCK_UN);
					fclose($fp);
				}
			}
		}
	}

	/**
	 * 生メールHeaderの取得
	 * @access	public
	 * @param	なし
	 * @return	string				生メールHeader文字列
	 * @info	
	 */
	function getRowHeader() {
		return $this->_rawHeader;
	}

	/**
	 * 生メールBodyの取得
	 * @access	public
	 * @param	なし
	 * @return	string				生メールBody文字列
	 * @info	
	 */
	function getRowBody() {
		return $this->_rawBody;
	}

	/**
	 * ヘッダーの配列取得
	 * @access	public
	 * @param	なし
	 * @return	array				ヘッダー格納連想配列(Keyは大文字)
	 * @info	
	 */
	function getHeaderArray() {
		return $this->_headArray;
	}

	/**
	 * 取得アドレスの配列取得
	 * @access	public
	 * @param	なし
	 * @return	array				取得アドレス格納連想配列(Keyは大文字)
	 * 									 FROM
	 * 									 RETURN-PATH
	 * 									 Delivered-TO
	 * 									 TO
	 * 									 CC
	 * @info	取得した連想配列の値はRFC2822書式のアドレスになっているはずのため、
	 *      	getAddressRFC2822() 等を使用して分解する必要がある
	 *      	RFC2822を満たす書式のアドレスは以下の通り
	 *      		user@example.com
	 *      		user@example.com, anotheruser@example.com
	 *      		User <user@example.com>
	 *      		User <user@example.com>, Another User <anotheruser@example.com>
	 */
	function getAddressArray() {
		$ret = array( 
			 "FROM"         => $this->getAddress("FROM"        , false)
			,"RETURN-PATH"  => $this->getAddress("RETURN-PATH" , false)
			,"DELIVERED-TO" => $this->getAddress("DELIVERED-TO", false)
			,"TO"           => $this->getAddress("TO"          , false)
			,"CC"           => $this->getAddress("CC"          , false)
		);

		return $ret;
	}

	/**
	 * MessageID取得
	 * @access	public
	 * @param	なし
	 * @return	string				MessageID
	 * @info	
	 */
	function getMessageID() {
		return $this->getAddress("MESSAGE-ID");
	}

	/**
	 * From取得
	 * @access	public
	 * @param	boolean	$p_trim		純アドレスにするか否か
	 * @return	string				Fromアドレス
	 * @info	
	 */
	function getFrom($p_trim = true) {
		return $this->getAddress("FROM", $p_trim);
	}

	/**
	 * To取得
	 * @access	public
	 * @param	boolean	$p_trim		純アドレスにするか否か
	 * @return	string				Toアドレス
	 * @info	
	 */
	function getTo($p_trim = true) {
		return $this->getAddress("TO", $p_trim);
	}

	/**
	 * Cc取得
	 * @access	public
	 * @param	boolean	$p_trim		純アドレスにするか否か
	 * @return	string				Ccアドレス
	 * @info	
	 */
	function getCc($p_trim = true) {
		return $this->getAddress("CC", $p_trim);
	}

	/**
	 * Subject取得
	 * @access	public
	 * @param	なし
	 * @return	string				Subject
	 * @info	
	 */
	function getSubject() {
		return $this->headerDecode($this->_headArray["SUBJECT"]);
	}

	/**
	 * Body(メール本文テキスト)取得
	 * @access	public
	 * @return	string				Body(メール本文テキスト)
	 * @info	
	 */
	function getBody() {
		return $this->_body;
	}

	/**
	 * エラーReturnメールの場合のUnknownアドレス取得
	 * @access	public
	 * @param	なし
	 * @return	string				Unknownアドレス
	 * @info	エラーReturnメールでない、もしくはUnknownとするべきではない場合は boolean : false
	 */
	public function getUnknownAddress() {
		$this->_isFilterUnknown = false;
		$this->_messageUnknown = "";

		// Fromを「MAILER-DAEMON@～」に限定した方がよい？
		// Return-Path を「<>」に限定した方がよい？

		// Bodyからエラー判定対象本文(受信MTAからのReturn内容)切り出し
		$body = $this->getBody();
		$len = mb_strlen(UNKNOWN_TARGET_FROM);
		if ($len > 0) {
			$pos = mb_strpos($body, UNKNOWN_TARGET_FROM);
			if ($pos === false) return false;	// 切り出し開始の指定があり、かつそのワードが発見されなかった場合はエラーReturnではない
			$body = mb_substr($body, $pos + $len);
		}
		$len = mb_strlen(UNKNOWN_TARGET_TO);
		if ($len > 0) {
			$pos = mb_strpos($body, UNKNOWN_TARGET_TO);
			if ($pos === false) return false;	// 切り出し終了の指定があり、かつそのワードが発見されなかった場合はエラーReturnではない
			$body = mb_substr($body, 0, $pos - 1);
		}

		// 受信MTAからのReturn内容の整形
		$body = preg_replace("/\r\n/", " ", $body);		// 改行コードを空白に置き換え(qmailの場合、改行後の空白がtrimされているので空白1文字に置き換え)
		$body = preg_replace("/\n/"  , " ", $body);		// 念のために置き換え(qmailの場合、改行後の空白がtrimされているので空白1文字に置き換え)
		$body = preg_replace("/\s+/" , " ", $body);		// 複数空白が連結している場合1つにまとめる(postfixの場合、改行された際に挿入されたインデントを消去)
		$body = preg_replace("/\t/"  , "" , $body);		// Tabの消去
		$body = trim($body);

		// 受信MTAからのReturn内容を解析
		/*
		以下にフォーマットを示す
			■postfixっぽい奴
			<[メールアドレス(※1)]>: host [ホストドメイン][[ホストIP]] said: [Returnコード(※2)] [Returnメッセージ文(※3)]
			■qmailっぽい奴
			<[メールアドレス(※1)]>: [ホストIP] failed after I sent the message. Remote host said: [Returnコード(※2)] [Returnメッセージ文(※3)]
			■sendmailっぽい奴
			確認環境がありませんでしたので記載できませんでした・・・すみません・・・

				※1:受信MTAの設定によっては、アカウント部に通常使用されるべきでない文字(.等)がある場合、
				    アカウント部がダブルクォートで囲まれる場合がある
				※2:レスポンスコードは3桁で表わされ、1桁目は障害種類、2桁目は障害区分、3桁目は症状を表わす(RFC2822、RFC1132に規定)
					* 200番台：正常
					* 300番台：正常(コマンド受け入れ後さらに入力が必要)
					* 400番台：一時的なエラー(再度実行すれば成功する可能性がある)
					* 500番台：恒久的なエラー(原因を取り除かなければ成功しない)
					代表的な400/500番台のエラーコードを以下に示す
					【421 ～ Service not avaliable, closing connection】
						ドメインのSMTP利用不可。（メール転送中にサーバーがダウンした場合等）
					【450 Requested mail action not taken: mailbox unavaliavle】
						一時的にメールボックス利用不可。
					【451 Requested action aborted: local error in processing】
						ローカルエラー。
					【452 Requested action not taken: insufficient system storage 】
						ファイルシステムのメモリ不足。
					【500 Command unrecognised】
						コマンドの文法エラー。
					【501 Bad parameters】
						コマンドのパラメータエラー。
					【502 Command not implemented】
						コマンド未実装。
					【503 Bad command sequence】
						コマンド実行順序が不正。
					【504 Parameter not implement】
						コマンドパラメータ未実装。
					【550 User unknown】
						送信先のメールボックスが無い。※要するにユーザがいない
					【551 User not local; please try ～】
						ユーザーがローカルユーザーではない。
					【552 Requested mail action aborted: exceeded storage allocation】
						クライアント記憶域割り当て超過によるコマンド中止。※要するにメールボックスの容量オーバー
					【553 Requested action not taken: mailbox name not allowed】
						送信先メールボックス名が無効。
					【554 Transaction failed】
						トランザクション失敗。 
				※4:応答メッセージは、受信MTAによって異なる
		受信MTAの接続拒否(Connection refuse)の場合はReturnコードは入らず、
		感染に送信MTA側にて生成されるメッセージにて返却される
		以下にpostfixでのメッセージを示す
			<[メールアドレス]>: connect to [ホストドメイン][[ホストIP]]:[ポート]: Connection refused
		一時的に相手先MTAに接続できないだけなので、処理しないのが正解
		(先に提示した受信MTAからのReturn内容のフォーマットに当てはまらないので処理されない)
		*/

		// フォーマットパターンを追加・変更する場合は
		// $regs 1:メールアドレス / 2:ホストIP / 3:Returnコード / 4:Returnメッセージ になるようにしてね！
		//-- postfixっぽい奴
		$format[0] = "<([\"]?[[:alnum:]_\.\-]+[\"]?@[[:alnum:]_\.\-]+)>:\s"
				   . "host\s[[:alnum:]_\.\-]+\[([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\]\s"
				   . "said:\s([0-9]{3})[\s\-]"	// 通常は「550 5.1.1」のようになるが、「550-5.1.1」のパターンに対応するため判定に「-」も加える
				   . "(.*)";
		//-- qmailっぽい奴
		$format[1] = "<([\"]*[[:alnum:]_\.\-]+[\"]*@[[:alnum:]_\.\-]+)>:\s"
				   . "([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\s"
				   . ".*\s"
				   . "said:\s([0-9]{3})[\s\-]"	// 通常は「550 5.1.1」のようになるが、「550-5.1.1」のパターンに対応するため判定に「-」も加える
				   . "(.*)";
		//-- sendmailっぽい奴
		// 確認環境がありませんでしたので記載できませんでした・・・すみません・・・

		//--- Host/ドメイン無しパターン[postfixのみ確認]
		// $regs 1:メールアドレス / 2:ホストドメイン / 3:Returnメッセージ となる
		$format[2] = "<([\"]?[[:alnum:]_\.\-]+[\"]?@[[:alnum:]_\.\-]+)>:\s"
				   . "Host or domain name not found."
				   . ".*\s"
				   . "name=([[:alnum:]_\.\-]+)\s"
				   . "(.*)";

		// パターン一致確認
		$handle = false;
		for ($i=0; $i<count($format); $i++) {
			if (preg_match("/" . $format[$i] . "/i", $body, $regs)) {
				$handle = true;
				break;
			}
		}
		if (!$handle) return false;

		// Returnコード確認
		$address = str_replace("\"", "", $regs[1]);
		if (count($regs) < 5) {
			$this->_messageUnknown = $regs[3];
			return $address;
		}
		if ((int)$regs[3] == 550) {
			// もしホストIPやReturnメッセージも解析する必要がある場合はここで判定する
			$this->_messageUnknown = $regs[4];
			if (mb_strlen(UNKNOWN_FILTER_MSG) > 0) {
				if (preg_match("/" . UNKNOWN_FILTER_MSG . "/i", $regs[4]) !== false) {
					$this->_isUnknownFilter = true;
				}
			}
			return $address;
		}

		// Returnコードがハンドル対象でなかった場合はエラーReturnではない
		return false;
	}

	/**
	 * エラーReturnがフィルタによるものかどうかを取得
	 * @access	public
	 * @param	なし
	 * @return	int					添付ファイル数
	 * @info	getUnknownAddressにてエラーアドレス取得後、直近のメッセージを取得する
	 */
	function isUnknownFilter() {
		return $this->_isUnknownFilter;
	}

	/**
	 * エラーReturn補足メッセージ取得
	 * @access	public
	 * @param	なし
	 * @return	int					添付ファイル数
	 * @info	getUnknownAddressにてエラーアドレス取得後、直近のメッセージを取得する
	 */
	function getUnknownMessage() {
		return $this->_messageUnknown;
	}

	/**
	 * 添付ファイル数取得
	 * @access	public
	 * @param	なし
	 * @return	int					添付ファイル数
	 * @info	
	 */
	function getFileCount() {
		$ret = 0;
		if ($this->_isAttach) $ret = count($this->_attachArray);
		return $ret;
	}

	/**
	 * 添付ファイル取得
	 * @access	public
	 * @param	int		$p_idx		File Index(0～)
	 * @return	array				ファイル情報格納連想配列
	 * 									 MIME   : MIMEヘッダー
	 * 									 CHAR   : charset
	 * 									 FILE   : ファイル名
	 * 									 ENCODE : エンコード形式
	 * 									 BODY   : ファイル内容
	 * @info	添付ファイルが存在しない場合は boolean : false
	 *      	ファイル名が空白の場合はマルチパートのツリー(入れ子)構造であり
	 *      	それ自体が添付ファイル構造になっている可能性が高い
	 */
	function getFile($p_idx = 0) {
		// マルチパートでない場合(添付ファイル無し)は boolean : false
		if (!$this->_isAttach) return false;

		return ($this->getAttachPart($p_idx));
	}

	/**
	 * ヘッダー内のメールアドレス取得
	 * @access	public
	 * @param	string	$p_key		ヘッダーKey名
	 * @param	boolean	$p_trim		純アドレスにするか否か
	 * @return	string				メールアドレス
	 * @info	
	 */
	function getAddress($p_key , $p_trim = true) {
		if (!array_key_exists($p_key, $this->_headArray)) return "";
		$ret = $this->_headArray[$p_key];
		if ($p_trim) {
			$ret = $this->AddressTrim($ret);
		} else {
			$ret = $this->headerDecode($ret);
		}
		return $ret;
	}

	/**
	 * RFC2822に準拠したアドレス取得
	 * @access	public
	 * @param	string	$address		取得対象文字列
	 * @return	array					アドレス格納二次元配列
	 *        	     					一次Index:数値Index
	 *        	     					二次Index:name=名称/address=純アドレス
	 * @info	取得できなかった場合は boolean:false
	 *      	RFC2822を満たす書式のアドレスは以下の通り
	 *      		user@example.com
	 *      		user@example.com, anotheruser@example.com
	 *      		User <user@example.com>
	 *      		User <user@example.com>, Another User <anotheruser@example.com>
	 */
	function getAddressRFC2822($address) {
		$ret = false;

		if ($address !== "") {
			$addresses = str_replace("\t", ",", $address);
			$i = 0;
			foreach (explode(",", $addresses) as $value) {
				if (preg_match("/(.*)?<(.*)>/i", $value, $m)) {
					$ret[$i]["name"]    = trim($m[1]);
					$ret[$i]["address"] = trim($m[2]);
				} else {
					$ret[$i]["name"]    = "";
					$ret[$i]["address"] = trim($value);
				}
				$i++;
			}
		}

		return $ret;
	}

	/**
	 * 純アドレス取得
	 * @access	private
	 * @param	string	$p_address		取得対象文字列
	 * @return	string					純アドレス
	 * @info	
	 */
	function AddressTrim($p_address) {
		$ret = "";

		if (mb_strlen($p_address) > 0) {
			$address = str_replace("\t", ",", $p_address);
			foreach (explode(",", $address) as $valuse) {
				//@ if (ereg("<(.*)>", $valuse, $reg)) {
				if (preg_match("/<(.*)>/", $valuse, $reg)) {
					$ret .= "," . trim($reg[1]);
				} else {
					$ret .= "," . trim($valuse);
				}
			}
			$ret = substr($ret, 1);
		}

		return $ret;
	}

	/**
	 * ヘッダーのデコード
	 * @access	private
	 * @param	string	$p_text		変換対象文字列
	 * @return	string				変換後文字列
	 * @info	
	 */
	function headerDecode($p_text) {
		$ret = $p_text;

		// base64エンコーディングの場合、"=?[文字エンコーディング]?B?[エンコード文字列]?=" のようなフォーマットとなる
		// cf.) "=?ISO-2022-JP?B?GyRCRTpJVUw1JDclRiU5JUgbKEI=?="
		// ヘッダが改行された上でエンコードされる場合があるので注意
		//@ while (eregi("=\?([^\?]+)\?B\?([^\?=]+)[=]*\?=[\n[:space:]]*", $ret, $reg)) {
		while (preg_match("/=\?([^\?]+)\?B\?([^\?=]+)[=]*\?=\s*/i", $ret, $reg)) {
			$ret = str_replace($reg[0]
					, mb_convert_encoding(base64_decode($reg[2]), $this->_internalEncoding, strtoupper($reg[1]))
					, $ret);
		}

		return $ret;
	}

	/**
	 * multipart/alternativeパートからtext/plainパート構成情報の抜き出し
	 * @access	private
	 * @param	string	$p_target	抜き出し対象文字列
	 * @param	string	$p_boundary	分割文字列(省略時は抜き出し対象文字列の中から自動取得)
	 * @return	array				パート内容格納連想配列
	 * 									 MIME   : MIMEヘッダー
	 * 									 CHAR   : charset
	 * 									 FILE   : ファイル名
	 * 									 ENCODE : エンコード形式
	 * 									 BODY   : ファイル内容
	 * @info	
	 */
	function getPlainFromAlternative($p_target, $p_boundary = "") {
		$ret = array(
		              "MIME"   => ""	// MIMEヘッダー
		            , "CHAR"   => ""	// charset
		            , "FILE"   => ""	// ファイル名
		            , "ENCODE" => ""	// エンコード形式
		            , "BODY"   => ""	// 内容
		            );

		// 分割文字列の確認
		if ($p_boundary == "") {
			//@ if (eregi("Content-Type:[[:space:]]*multipart/alternative;[[:space:]\n\t]*boundary=\"?([^\"]+)\"?\n", $p_target, $reg)) {
			if (preg_match("/Content-Type:\s*multipart\/alternative;\s*boundary=\"?([^\"]+)\"?\n/i", $p_target, $reg)) {
				$p_boundary = $reg[1];
			}
		}
		if ($p_boundary == "") return false;

		$partArray = explode("--{$p_boundary}", $p_target);		// パートごとに分解
		// テキストパート確認
		for ($i=1; $i<count($partArray)-1; $i++) {
			// Header部とBody部を分解
			$pos = mb_strpos($partArray[$i], "\n\n");
			$header = mb_substr($partArray[$i], 0, $pos);
			$body   = mb_substr($partArray[$i], $pos + 2);
			// text/plainである
			//@ if (eregi("Content-Type:[[:space:]]*text/plain;([[:space:]\n\t]*charset=\"?([^\"\n]+)\"?)?\n", $header, $reg)) {
			if (preg_match("/Content-Type:\s*text\/plain;(\s*charset=\"?([^\"\n]+)\"?)?\n/i", $header, $reg)) {
				// MIME
				$ret["MIME"] = "text/plain";
				// charset
				$ret["CHAR"] = strtoupper($reg[2]);
				// ファイル名
				//@ if (eregi("name=[\"]*([^\"]+)[\"]*\n", $header, $reg)) {
				if (preg_match("/name=[\"]*([^\"]+)[\"]*\n/i", $header, $reg)) {
					$ret["FILE"] = $this->headerDecode($reg[1]);
				} else {
					//@ if (eregi("filename=[\"]*([^\"]+)[\"]*\n", $header, $reg)) $ret["FILE"] = $this->headerDecode($reg[1]);
					if (preg_match("/filename=[\"]*([^\"]+)[\"]*\n/i", $header, $reg)) $ret["FILE"] = $this->headerDecode($reg[1]);
				}
				// エンコード形式
				//@ if (eregi("Content-Transfer-Encoding:[[:space:]]*([[:alnum:]]+)", $header, $reg)) $ret["ENCODE"] = strtolower($reg[1]);
				if (preg_match("/Content-Transfer-Encoding:\s*([a-zA-Z0-9]+)/i", $header, $reg)) $ret["ENCODE"] = strtolower($reg[1]);
				// 内容
				$ret["BODY"] = $body;
				// 結果返却
				return $ret;
			}
		}

		return false;
	}

	/**
	 * 添付パートの一つの構成情報取得
	 * @access	private
	 * @param	int		$p_idx		BODY Index
	 * @return	array				パート内容格納連想配列
	 * 									 MIME   : MIMEヘッダー
	 * 									 CHAR   : charset
	 * 									 FILE   : ファイル名
	 * 									 ENCODE : エンコード形式
	 * 									 BODY   : ファイル内容
	 * @info	
	 */
	function getAttachPart($p_idx) {
		$ret = array(
		              "MIME"   => ""	// MIMEヘッダー
		            , "CHAR"   => ""	// charset
		            , "FILE"   => ""	// ファイル名
		            , "ENCODE" => ""	// エンコード形式
		            , "BODY"   => ""	// 内容
		            );
		if ($p_idx < 0) return false;
		if ($p_idx >= count($this->_attachArray)) return false;
		$target = $this->_attachArray[$p_idx];

		// Header部とBody部を分解
		$pos = mb_strpos($target, "\n\n");
		$header = mb_substr($target, 0, $pos);
		$body   = mb_substr($target, $pos + 2);

		// MIME / charset
		//@ if (eregi("Content-Type:[[:space:]]*([^;]+);([[:space:]\n\t]*charset=\"?([^\"\n]+)\"?)?\n", $header, $reg)) {
		if (preg_match("/Content-Type:\s*([^;]+);(\s*charset=\"?([^\"\n]+)\"?)?\n/i", $header, $reg)) {
			$ret["MIME"] = $reg[1];
			$ret["CHAR"] = strtoupper(@$reg[3]);
		}
		// ファイル名
		//@ if (eregi("name=[\"]*([^\"]+)[\"]*\n", $header, $reg)) {
		if (preg_match("/name=[\"]*([^\"]+)[\"]*\n/i", $header, $reg)) {
			$ret["FILE"] = $this->headerDecode($reg[1]);
		} else {
			//@ if (eregi("filename=[\"]*([^\"]+)[\"]*\n", $header, $reg)) $ret["FILE"] = $this->headerDecode($reg[1]);
			if (preg_match("/filename=[\"]*([^\"]+)[\"]*\n/i", $header, $reg)) $ret["FILE"] = $this->headerDecode($reg[1]);
		}
		// エンコード形式
		//@ if (eregi("Content-Transfer-Encoding:[[:space:]]*([[:alnum:]]+)", $header, $reg)) $ret["ENCODE"] = strtoupper($reg[1]);
		if (preg_match("/Content-Transfer-Encoding:\s*([a-zA-Z0-9]+)/i", $header, $reg)) $ret["ENCODE"] = strtoupper($reg[1]);
		// 内容
		$ret["BODY"] = $body;

		return $ret;
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
?>
