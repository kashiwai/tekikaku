<?php
/*
 * TemplateAdmin.php
 * 
 * (C)SmartRams Corp. 2016 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * 管理系表示コントロール処理クラス
 * 
 * 管理系表示コントロールの処理を行なう
 * 
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since	 2016/08/04 初版作成 岡本静子
 * @info
 */

// 定数定義
define("HTML_ENCODING" , "UTF-8");		// テンプレート記述基準エンコーディング

class TemplateAdmin extends SmartTemplate {

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
	 * @param	boolean		$makeSession	セッションインスタンスを生成するかどうか
	 * @param   boolean		$isReturn		セッションID名が存在しない場合にリダイレクトさせるか
	 * @param	boolean		$isRegenerate	セッションIDを新しく生成したものと置き換えるか否か(セッション存在時のみ対象)
	 * @param	string		$encode			テンプレート記述文字エンコーディング
	 * @return	インスタンス
	 */
	public function __construct($makeSession = true, $isReturn = true, $isRegenerate = false, $encode = HTML_ENCODING) {

		// キャッシュコントロール
		header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
		header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// 画面テンプレートインスタンス生成
		$this->_enc = $encode;
		parent::__construct(DIR_HTML_ADMIN, $encode, true);
		parent::setAccessTypeDir(TYPE_PC           , DIR_HTML_ADMIN);
		parent::setAccessTypeDir(TYPE_SMART_PHONE  , DIR_HTML_ADMIN);
		$this->_basedir = parent::getBaseDir();					// 基本ディレクトリパス取得

		// DB接続
		$this->DB = new NetDB();

		if ($makeSession) {
			// セッションインスタンス生成
			$this->Session = new SmartSession(URL_ADMIN . "login.php", SESSION_SEC_ADMIN, SESSION_SID_ADMIN, DOMAIN, true);
			$firstSession = $this->Session->start();			// セッションスタート
			if (!$firstSession) $this->Session->check(true);	// セッションチェック
			// 管理者チェック
			$checkRet = false;
			if (isset($this->Session->AdminInfo)) {
				// AdminInfoはArrayObjectなので、getArrayCopy()で配列に変換
				$adminInfoObj = $this->Session->AdminInfo;
				if (is_object($adminInfoObj) && method_exists($adminInfoObj, 'getArrayCopy')) {
					$adminInfo = $adminInfoObj->getArrayCopy();
				} else {
					$adminInfo = (array)$adminInfoObj;
				}
				if (isset($adminInfo['admin_id']) && isset($adminInfo['admin_pass'])) {
					$checkRet = $this->DB->checkAdmin($adminInfo['admin_id'], $adminInfo['admin_pass']);
				}
			}
			// 管理者チェックNGの場合はセッションクリア
			if (!$checkRet) $this->Session->clear(true);
		}

		// 自スクリプト名取得
		$this->Self = get_self();

		// メニュー権限のチェック(権限がない場合はトップページへ遷移させる)
		$baseName = basename($this->Self, ".php");
		if (isset($this->Session->AdminInfo) && array_key_exists($baseName, $GLOBALS["AuthMenuID"])) {
			$denyMenuList = explode(",", $this->Session->AdminInfo["deny_menu"]);
			if (in_array($baseName, $denyMenuList)) {
				// トップページへ
				header("Location: " . URL_ADMIN . "index.php");
				exit();
			}
		}
	}

	/**
	 * 完了画面の表示
	 * @access	public
	 * @param	string		$title			タイトル
	 * @param	string		$strong_message	強調メッセージ
	 * @param	string		$message		メッセージ
	 * @param	string		$nexturl		次URL
	 * @param	boolean		$reload			親画面をリロードするか否か
	 * @return	なし
	 */
	public function dispProcEnd($title, $strong_message, $message, $nexturl = "", $reload = true) {

		$this->open("end.html");
		$this->assignCommon();		// 共通置換

		$this->assign("TITLE"         , $title);
		$this->assign("STRONG_MESSAGE", $strong_message);
		$this->assign("MESSAGE"       , $message);
		$this->assign("NEXT_URL"      , $nexturl);
		$this->assign("RELOAD"        , (($reload) ? "true" : "false"));
		$this->if_enable("STRONG_MESSAGE", mb_strlen($strong_message) > 0);
		$this->if_enable("NEXT", mb_strlen($nexturl) > 0);

		$this->flush();
	}

	/**
	 * システムエラー画面の表示
	 * @access	public
	 * @param	string		$message	メッセージ
	 * @param	boolean  	$isMain     メインウィンドウでのエラー表示か否か
	 * @return	なし
	 */
	public function dispProcError($message, $isMain = true) {

		$this->open("error.html");
		$this->assignCommon();		// 共通置換

		$this->assign("MESSAGE", $message);
		$this->if_enable("MAIN", $isMain);
		$this->if_enable("SUB" , !$isMain);

		$this->flush();
	}

	/**
	 * 共通項目の置換
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	public function assignCommon() {
		// インクルードファイル置換
		$this->replaceInclude();

		// サイト定数置換
		$this->assign("SITE_DOMAIN" , DOMAIN);						// ドメイン
		$this->assign("SITE_URL"    , URL_SITE);					// サイトURL
		$this->assign("ADMIN_URL"   , URL_ADMIN);					// 管理画面URL
		$this->assign("SITE_TITLE"  , SITE_TITLE);					// サイト名
		$this->assign("COPYRIGHT"   , COPYRIGHT);					// コピーライト
		$this->assign("DEFAULT_LANG", DEFAULT_LANG);				// デフォルト言語

		// インクルード日時
		if ( DOMAIN == DOMAIN_DEVELOPMENT ) {	// 開発環境
			$sysDate = date("Ymd");
		} else {
			$sysDate = date("YmdHi");
		}
		$this->assign("SYS_VERDATE", $sysDate, false);

		// 共通情報
		$this->assign("SELF"   , $this->Self);					// 自スクリプト名
		$this->assign("ACTION" , $this->Self . ((mb_strlen($_SERVER["QUERY_STRING"]) > 0) 
												? "?" . htmlspecialchars($_SERVER["QUERY_STRING"]) : ""));		// 自URL
		$this->assign("RANDOM" , uniqid());						// ランダム値
		$this->assign("LOGIN_NAME", (isset($this->Session->AdminInfo)) ? $this->Session->AdminInfo["admin_name"] : "");		// 管理者名
		
		// 表示ポイント系単位
		foreach($GLOBALS["viewUnitList"] as $key => $val ){
			$this->assign("CURRENCY_" . $key, $val, true);
		}
		// ポイント購入（単位）
		foreach($GLOBALS["viewAmountType"] as $key => $val ){
			$this->assign("AMOUNTTYPE_" . $key, $val, true);
		}
		
		// 会員バッヂ(表示制御は各々で処理すること)
		$this->assign("MEMBER_BADGE_BLACK", MEMBER_STATE_BLACK, false);
		$this->assign("MEMBER_BADGE_RETIRED", MEMBER_STATE_RETIRED, false);
		$this->assign("MEMBER_BADGE_TESTER", MEMBER_STATE_TESTER, false);
		$this->assign("MEMBER_BADGE_AGENT", MEMBER_STATE_AGENT, false);
		
		// メニュー表示制御
		if (isset($this->Session->AdminInfo)) {
			$baseName = basename($this->Self, ".php");
			$denyMenuList = explode(",", $this->Session->AdminInfo["deny_menu"]);
			foreach ($GLOBALS["AdminAllMenu"] as $grp => $menu) {
				$grpView = false;
				foreach ($menu as $menuKey) {
					$isView = (array_key_exists($menuKey, $GLOBALS["AuthMenuID"]) && !in_array($menuKey, $denyMenuList));
					if ($isView) $grpView = True;
					$this->if_enable("MENU_" . $menuKey, $isView);
					$this->assign("ACT_" . $menuKey, ($menuKey == $baseName) ? " active" : "");
				}
				$this->if_enable("GROUP_" . $grp, $grpView);
			}
			$this->assign("ACT_index", ($baseName == "index") ? " active" : "");
		}

		//-- 基準時間
		$this->assign("REFERENCE_TIME" , REFERENCE_TIME);	// 基準時間
		// 表示制御
		$numReferenceTime = TimeToNum(REFERENCE_TIME);
		$this->if_enable("DATE_SWITCH_NORMAL"  , $numReferenceTime == 0);	// 通常の日付切替時間
		$this->if_enable("DATE_SWITCH_ABNORMAL", $numReferenceTime != 0);	// 変わった日付切替時間

		// カテゴリ表示制御 2020/04/23 [ADD]
		foreach($GLOBALS["CategoryUseList"] as $key => $val ){
			$this->if_enable("CATEGORY_USE_".trim( $key), $val);
		}

		// メッセージ置換
		$this->replaceMessage();
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
			$file = file_get_contents($this->_basedir . $match[2]);		// ファイル内容取得
			// テンプレート記述文字エンコードがPHP処理エンコードと違う場合は文字エンコードを変換
			if ($this->_enc != ini_get("mbstring.internal_encoding")) {
				// 記述エンコード→PHP処理エンコード
				$file = mb_convert_encoding($file, ini_get("mbstring.internal_encoding"), $this->_enc);
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
			if (mb_strpos($key, "A") !== false) $this->assign($key , $value);
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
	 * Noのフォーマット(会員No以外)
	 * @access	public
	 * @param	integer	$no		対象No
	 * @return	string	フォーマット値
	 */
	function formatNoBasic($no) {
		if (mb_strlen($no) == 0) return "";
		return sprintf('%0' . FORMAT_NO_DIGIT . 'd', $no);
	}

	/**
	 * 会員Noのフォーマット (0詰め7桁)
	 * @access	public
	 * @param	integer	$mno		会員No
	 * @return	string	フォーマット値
	 */
	function formatMemberNo($mno) {
		if (mb_strlen($mno) == 0) return "";
		return sprintf('%0' . MEMBER_NO_DIGIT . 'd', $mno);
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