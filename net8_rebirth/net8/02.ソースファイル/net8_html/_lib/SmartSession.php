<?php
/*
 * SmartSession.php
 * 
 * (C)SmartRams Corp. 2008 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * セッションを扱うクラス
 * 
 * セッションクラス(携帯対応)
 * 
 * @package	
 * @author	金光 峰範
 * @version	PHP5.x.x
 * @since	2008/06/24 初版作成 金光 峰範 新規作成
 * @since	2009/09/16 全面改修 岡本 静子 セッションの扱いに論理的不具合があったためcls_sessionを破棄し新規作成
 * @since	2011/05/10 一部回収 岡本 静子 セッションスタート関連を修正
 */

class SmartSession {

	// メンバ変数定義
	private $m_url = "";		// セッション消失時表示URL
	private $m_sec;				// セッション継続時間(秒)
	private $m_sid;				// セッションID名
	private $m_return;			// セッションID名が存在しない場合にリダイレクトさせるか
	private $m_domain;			// セッションチェックドメイン名

	/**
	 * コンストラクタ
	 * @access  public
	 * @param   string  $url		  	セッション消失時表示URL
	 * @param   intger  $sec = 1440		セッション継続時間(秒)
	 * @param   string  $sid = "SID"	セッションID名
	 * @param   string  $domain			セッションチェックドメイン名
	 * @param   boolean $isReturn		セッションID名が存在しない場合にリダイレクトさせるか
	 * @return  						インスタンス
	 */
	public function __construct($url, $sec = 1440, $sid = "SID", $domain = "", $isReturn = true) {
		// 初期処理
		if(mb_strlen($domain) == 0) $domain = $_SERVER["SERVER_NAME"];

		// メンバ変数へ格納
		$this->m_url = $url;
		$this->m_sec = $sec;
		$this->m_sid = $sid;
		$this->m_domain = $domain;
		$this->m_return = $isReturn;
		
	}

	/**
	 * セッションスタート
	 * @access  public
	 * @param	boolean		$isNew		セッションを強制的に新規発行するか否か
	 * @return  bool	ture
	 */
	public function start($isNew = false) {
		$ret = false;
		$nowTime = time(); // 現時間
		$isStart = false;

		if(session_status() !== PHP_SESSION_ACTIVE) {
			ini_set("session.gc_maxlifetime", $this->m_sec);   // セッションの生存時間
			session_name($this->m_sid);  // セッション名を設定
		}

		if (!$this->isSessionExist()) session_start();		// セッション開始

		// セッションが存在する場合
		if ($this->isSessionExist() && !$isNew) {
			// 複数同時(別ウインドウ等)に再発行する場合、古いセッション情報を削除するよう指定しているため
			// 片方でセッション消失になる場合があるので注意
			// NOTE: 毎回session_regenerate_id()を呼ぶとセッションが維持できないためコメントアウト
			// session_regenerate_id();					// セッションIDを変更
		} else {
			$ret = true;
			// セッションが存在しない場合、セッションを発行する
			// セッション変数に値を格納
			$this->session_initial = true;
			$this->session_start_time = $nowTime;			// セッション開始時間を保持
			$this->session_reload_time = $nowTime;			// 最後にセッションがリロードされた時間
			$this->session_domain = $this->m_domain;		// ドメイン名を格納
		}

		return $ret;
	}

	/**
	 * セッションチェック
	 * @access  public
	 * @param	boolean		$isClear		エラー時にセッションをクリアするか否か
	 * @return  boolean		チェック結果(true：正常 / false：異常)
	 *                      ※セッションをクリアしない場合はチェック結果を戻すので
	 *                        呼び元で対応するエラー処理を行うこと
	 */
	public function check($isClear = false) {
		$ret = true;
		$nowTime = time(); // 現時間
		// セッションIDの存在チェック（$_REQUESTまたは$_COOKIEまたはsession_id()）
		$hasSid = isset($_REQUEST[$this->m_sid]) || isset($_COOKIE[$this->m_sid]) || (session_id() != '');
		if ($hasSid) {
			// ドメインチェック
			if (!isset($this->session_domain)) {
				$ret = false;
			} else {
				if ($this->session_domain != $this->m_domain) {
					 $ret = false;
				}
			}
			// SIDリクエストがある場合
			if (!isset($this->session_start_time)) {
				// 開始日時消失 = セッション消失
				$ret = false;
			} else {
				// リロード時間チェック
				if ($nowTime - $this->session_reload_time <= $this->m_sec) {
					// 時間制限以内
					$this->session_reload_time = $nowTime;	// リロード時間更新
				} else {
					// タイムアウト
					$ret = false;
				}
			}
		} else {
			$ret = false;
		}

		// セッションエラー時にクリアする場合は、セッションを破棄する
		if ($isClear && !$ret) $this->clear($this->m_return);

		// セッションエラー時にクリアしない場合は、セッション開始/リロード時間を再セットする
		if (!$ret) {
			$this->session_start_time = $nowTime;			// セッション開始時間を保持
			$this->session_reload_time = $nowTime;			// 最後にセッションがリロードされた時間
		}

		return $ret;
	}

	/**
	 * セッション破棄
	 * @access  public
	 * @param   boolean	$return			セッション破棄後にリダイレクトさせるか否か
	 * @param   string	$sessionName	破棄対象セッション名(指定無し時は全て破棄)
	 * @return  bool	ture
	 */
	public function clear($return = true, $sessionName = "") {

		if (mb_strlen($sessionName) > 0) {
			// 指定されたセッションのみ破棄を行う
			unset($_SESSION[$sessionName]);

		} else {
			// セッションを切断するにはセッションクッキーも削除する。
			// Note: セッション情報だけでなくセッションを破壊する。
//			setcookie(session_name($this->m_sid), '', time()-42000, '/');
			// ----- 2019/01/30 この段階でsession_name切り替えできないようになってのでcookieだけ消す
//			setcookie($this->m_sid, '', time()-42000, '/');

			// セッション変数を全て解除する
			$_SESSION = array();

			// セッションの明示的破棄
			session_destroy();
		}

		//##### 2009/12/04 Upd S by S.Okamoto ロケーション後に処理を中断するように修正 #####
		//if ($return) header("Location: " . $this->m_url);
		if ($return) {
			header("Location: " . $this->m_url);
			exit();
		}
		//##### 2009/12/04 Upd E
	}

	/**
	 * セッション存在チェック
	 * @access	public
	 * @param	なし
	 * @return	bool	セッションが存在するかどうか
	 */
	public function isSessionExist() {
		return isset($this->session_initial);
	}

	/**
	 * プロパティ設定
	 * @access  public
	 * @param   string  $name		項目名
	 * @param   mixed   $value		値
	 * @return  bool	ture
	 */
	public function __set($name, $value) {
		if (is_array($value)) {
			$_SESSION[$name] = new ArrayObject($value, ArrayObject::ARRAY_AS_PROPS);
		} else {
			$_SESSION[$name] = $value;
		}
		return true;
	}

	/**
	 * プロパティ取得
	 * @access  public
	 * @param   mixed   $name		項目名
	 * @param   mixed   &$value		返却用の値
	 * @return  bool				存在の有無
	 */
	function __get($name) {
		if (isset($_SESSION[$name])) {
			return $_SESSION[$name];
		} else {
			return "";
		}
	}

	/**
	 * プロパティ存在チェック
	 * @access  public
	 * @param   string  $name		項目名
	 * @return  bool
	 * @info	PHP5系でしか使用不可
	 */
	public function __isset($name)
	{
		return isset($_SESSION[$name]);
	}

	/**
	 * プロパティ削除
	 * @access  public
	 * @param   string  $name		項目名
	 * @return  なし
	 * @info	PHP5系でしか使用不可
	 */
	public function __unset($name)
	{
		unset($_SESSION[$name]);
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