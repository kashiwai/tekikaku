<?php
/*
 * SmartDB7.php
 *
 * (C)SmartRams Corp. 2007- All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * DB I/O処理クラス
 *
 * DBへのI/O処理全般を行う
 *
 * @package
 * @author	須増 圭介
 * @version PHP5.x.x
 * @since	2007/05/09 初版作成 須増 圭介 cls_dbとして初版作成
 * @since	2007/05/15 追加     岡本 順子 SQL生成補助としてconv_sql関数を追加
 * @since	2007/05/23 追加     須増 圭介 トランザクション使用時にエラーが発生した際、
 *       	                              ロールバック処理を行うよう追加
 * @since   2008/03/07 移植改定 金光 峰範 PHP4からそれっぽく移植
 * @since	2009/09/16 全面改修 岡本 静子 命名規約見直しに伴いcls_dbを破棄しSmartDBとして新規作成
 * @since	2011/07/28 追加     須増 圭介 conv_sqlの日付処理へインジェクション対策追加
 * @since	2016/08/16 改修     岡本 静子 pearのDBからMDB2への変更
 * @since	2017/04/12 改修     岡本 静子 DBフィールド型(FD_DATEEX)追加
 * @since	2017/06/16 改修     岡本 静子 エラーログ出力の関数名変更(get_log → write_log)
 *										  全行取得、1項目のみ取得、1行取得、1列取得にエラーログ出力処理追加
 *       	
 * @since	2018/01/29 改修     村上 俊行 php7対応によるMDB2からPDOへの移行
 * @info	PearDBクラスをオーバーロードし、独自ハンドラを実装する
 */

// 2019/01/29 php7対応により廃止
//require_once 'MDB2.php';

// DBフィールド型
define("FD_NUM" , 0);
define("FD_STR" , 1);
define("FD_DATE", 2);
define("FD_NUMEX", 3);
define("FD_DATEEX", 4);
define("FD_FUNCTION", 5);		// ----- 2019/01/29 SqlString使用時のconv_sqlのquot対策
define("DBG_FLG", false);		// true:SQL文表示 / false:getMessageのみ
define("DBG_LOG", true);		// true:ログ書き出し / false:エラー表示のみ
define("DBG_FILE", "error_log.txt");	// ログファイル名 ※

// ----- 2019/01/29 Toshiyuki Murakami
// MDB2廃止に伴ってdefineを再定義
define("MDB2_FETCHMODE_DEFAULT", PDO::FETCH_BOTH );
// define("PDO::FETCH_ASSOC", PDO::FETCH_ASSOC ); // ← 誤った定義のためコメントアウト（PDO::FETCH_ASSOCは既にPHPで定義済み）

class SmartDB {

	private $_db = false;						// DBオブジェクト
	private $_overloadFunctions = array();		// オーバーロード
	private $_transaction = false;				// トランザクション判定

	/**
	 * コンストラクタ
	 * @access  public
	 * @param   string  $p_dsn  DB接続文字列
	 * @return  インスタンス
	 */
	public function __construct($p_dsn) {
		$_db = $this->connect($p_dsn);
	}

	/**
	 * データベースに接続する
	 * @access  private
	 * @param   string  $p_dsn		DB接続文字列
	 * @return  object				DBオブジェクト(失敗時はDB_Errorオブジェクト)
	 */
	private function connect($p_dsn) {
		if ($this->_db === false) {

			//MDB2のdnsをPDO用に変換する
			if ( !preg_match( "/^(.+):\/\/(.+):(.+)@(.+)\/(.+)$/", $p_dsn, $argv ) ){
				die($this->_db->getMessage());
			}
			$pdo_dsn = "{$argv[1]}:host={$argv[4]};dbname={$argv[5]};charset=utf8;";
			try {
				// PDOインスタンスを生成 (URLデコードして渡す)
				$this->_db = new PDO($pdo_dsn, urldecode($argv[2]), urldecode($argv[3]),
					array( 
					            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
					            PDO::ATTR_EMULATE_PREPARES => false, 
					) 			// エラー（例外）が発生した時の処理を記述
				);
			} catch (PDOException $e) {
				// エラーメッセージを表示させる
				die($e->getMessage());
			}
			$this->_overloadFunctions = get_class_methods(get_class($this->_db));

/*
			// サーバーに接続
			$this->_db =& MDB2::connect($p_dsn);
			if (PEAR::isError($this->_db)) {
				die($this->_db->getMessage());
			} else {
				$this->_overloadFunctions = get_class_methods(get_class($this->_db));
			}
*/
		}

		//コネクションハンドルを返す
		return $this->_db;
	}


	/**
	 * オーバーロードクラスの動的メソッドの処理を行う
	 * @access  public
	 * @param string $method		実行されたメソッド名
	 * @param string $argument		メソッドに付加された引数
	 */
	public function __call($method, $argument) {

		$result = false;
		if ($this->_db === false) return $result;
		// MDB2には「autocommit」メソッドがないため、「_overloadFunctions」とは別に処理を行う
		if (strtolower($method) === "autocommit") {
			if ($argument[0] === false) {
				// 強制的にトランザクションをかける
				$result = $this->_db->beginTransaction();
			} elseif ($argument[0] === true) {
				// 強制的にコミットをかける
				$result = $this->_db->commit();
			}
		}

		if (in_array($method, $this->_overloadFunctions)) {
			// SQL実行
			try {
				$return = call_user_func_array(array($this->_db, $method), $argument);
			} catch (PDOException $e) {
				//クエリ結果がエラー時のデバッグ表示
				$this->write_log($e->getMessage() . (DBG_FLG ? "\r\n" . $argument[0] : ""), $argument[0]);
				throw new Exception(__METHOD__ . ": " . $e->getMessage() . (DBG_FLG ? "<br>\r\n" . $argument[0] : ""));
			}
/*
			if (PEAR::isError($return)) {
				// トランザクションを開始していた場合、ロールバックさせる
				if ($this->_db->inTransaction()) {
					$this->_db->rollback();
				}
				$this->write_log($return->getMessage() . (DBG_FLG ? "\r\n" . $argument[0] : ""), $argument[0]);
				throw new Exception(__METHOD__ . ": " . $return->getMessage() . (DBG_FLG ? "<br>\r\n" . $argument[0] : ""));
			}
*/
			$result = $return;
		} else {
			$result = false;
		}

		return $result;
	}

	/**
	 * エラーログ出力処理
	 * @access  private
	 * @param string $buf			ログ文(画面表示用)
	 * @param string $errmsg		ログ文(エラー内容)
	 * @param string $log_file		ログを残すファイル
	 *								ファイル有無・ファイル権限に注意!!!
	 * @param mixed  $return 		文言
	 */
	private function write_log($buf, $errmsg = "", $log_file = "") {
		//絶対パス取得
		$documentpath = dirname(__FILE__) . "/log/";

		//ログファイル名
		$log_file = $documentpath . DBG_FILE;

		//ファイル判定
		if( !file_exists($log_file) || $log_file === "" || DBG_LOG === false ) return $buf;

		if( DBG_LOG ) $msg = $buf . "\r\n" . $errmsg;
		// 改行コード
		$buf .= "\r\n\r\n";

		//ログの取得方法(0:システム内(error_log設定されている)ファイルへ, 1:メール, 2:リモートデバッグ(PHP3のみ), 3:指定ファイルへ)
		$log_get_type = 3;

		$this->error_log($msg, $log_get_type, $log_file);

		return $buf;
	}

	/**
	 * エラー内容ファイル出力処理
	 * @access  private
	 * @param string $buf			ログ文
	 * @param string $log_get_type	ログの取得方法(とりあえず 3：指定ファイルへ のみ実装)
	 * @param string $log_file		ログを残すファイル
	 * 								ファイル有無・ファイル権限に注意!!!
	 * @param mixed  $return		文言
	 */
	private function error_log($buf, $log_get_type, $log_file = "") {

		//エラー内容
		$str = "";
		$str .= date('Y/m/d H:i:s') . "\n";
		$str .= $buf . "\r\n\r\n";

		$fp = fopen($log_file, "a") or die("Open Error:" . $log_file);
		flock($fp, LOCK_EX);
		fputs($fp, $str);
		flock($fp, LOCK_UN);
		fclose($fp);

		return $buf;
	}

	/**
	 * SQL文字列のコンバート
	 * @access  public
	 * @param   string		$target		置換対象文字列
	 * @param   int			$type		型フラグ(0-数値 / 1-文字列 / 2-日付 /3-数値[カンマ付き])
	 * @param   boolean		$nullconv	空白をNULLとして扱う(省略時はtrue)
	 *									true - 空白NULL変換 / false - 空白デフォルト変換(数値=0 文字列='' 日付=NULL)
	 * @return  string					置換後文字列
	 * @info
	 */
	public function conv_sql($target, $type, $nullconv = true) {
		$target = (string)$target;
		if ($nullconv == true && $target == "") return "NULL";
		switch ($type) {
			case 0:		// 数値
				$ret = $target;
				if ($ret == "") $ret = "0";
				//--- 2009/11/20 Add S by S.Okamoto SQLインジェクション対策
				//    in句のカンマ数値を聞いている箇所があるので、カンマ除去後が数値かどうかで判定
				if (!preg_match("/^([+-]?)[0-9\.]+$/", str_replace(",", "", $ret))) 
												throw new Exception(__METHOD__ . ": number error");
				//--- 2009/11/20 Add E
				break;
			case 1:		// 文字列
				$ret = str_replace("'", "''", $target);
				if(!ini_get("magic_quotes_gpc")) {
					$ret = str_replace("\\", "\\\\", $ret);
					$ret = str_replace("\\n", "\n", $ret);
				}
				$ret = "'" . $ret . "'";
				break;
			case 2:		// 日付
			case 4:		// 日付(形式変換含む)
				//--- 2011/07/28 Add S by sumasu SQLインジェクション対策
				$ret = trim($target);
				if ($ret == "") return "NULL";
				
				//    「数字」「-」「/」「スペース」「.」「:」の入力で判定
				if (!preg_match("/^[0-9-\/ .:]+$/", $ret)) 
												throw new Exception(__METHOD__ . ": date error");
				$ret = "'" . $target . "'";
				//--- 2011/07/28 Add E
				break;
			case 3:		// 数値(カンマ付)
				$ret = str_replace(",", "", trim($target));
				if ($ret == "") $ret = "0";
				if (!preg_match("/^([+-]?)[0-9\.]+$/", $ret)) 
												throw new Exception(__METHOD__ . ":  numberEx error");
				break;
			case 5:		//関数指定 --- 2019/01/29 Toshiyuki Murakami追加
				$ret = $target;
				break;
		}
		return $ret;
	}
	
	/**
	 * 全行取得(getAll)
	 * @access  public
	 * @param   string	$sql			SQL文
	 * @param   string	$fetchmode		フェッチモード(指定しない場合はデフォルト)
	 * @return  なし
	 */
	public function getAll($sql, $fetchmode = PDO::FETCH_BOTH) {
		try {
			//Query発行
			$result = $this->_db->query($sql);
			$result->setFetchMode($fetchmode);
		} catch (PDOException $e) {
			//クエリ結果がエラー時のデバッグ表示
			$this->write_log($e->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $e->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}
		return $result->fetchAll();
/*
		$this->_db->setFetchMode($fetchmode);
		$ret =  $this->_db->query($sql);
		if (PEAR::isError($ret)) {
			//--- 2017/06/16 Add by S.Okamoto エラーログ出力処理追加
			$this->write_log($ret->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $ret->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}
		return $ret->fetchAll();
*/
	}
	
	/**
	 * 1項目のみ取得(getOne)
	 * @access  public
	 * @param   なし
	 * @return  なし
	 */
	public function getOne($sql) {
//print $sql;
		$ret = "";
		try {
			//Query発行
			$result = $this->_db->query($sql);
		} catch (PDOException $e) {
			//クエリ結果がエラー時のデバッグ表示
			$this->write_log($e->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $e->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}
		
		foreach( $result as $row ){
			$ret = $row[0];
			break;
		}
		return $ret;
		
/*
		$ret = $this->_db->queryOne($sql);
		if (PEAR::isError($ret)) {
			//--- 2017/06/16 Add by S.Okamoto エラーログ出力処理追加
			$this->write_log($ret->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $ret->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}
		return $ret;
*/

	}
	
	/**
	 * 1行取得(getRow)
	 * @access  public
	 * @param   string	$sql			SQL文
	 * @param   string	$fetchmode		フェッチモード(指定しない場合はデフォルト)
	 * @return  なし
	 */
	public function getRow($sql, $fetchmode = PDO::FETCH_BOTH) {
		try {
			//Query発行
			$result = $this->_db->query($sql);
			//FetchMode設定（以前のMDB2とは指定オブジェクトとタイミングが違うので注意）
			$result->setFetchMode($fetchmode);
		} catch (PDOException $e) {
			//クエリ結果がエラー時のデバッグ表示
			$this->write_log($e->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $e->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}

		$ret = array();
		foreach( $result as $row ){
//print_r ( $row );
			$ret = $row;
			break;
		}

		return $ret;
/*
		$this->_db->setFetchMode($fetchmode);
		$ret = $this->_db->queryRow($sql);
		if (PEAR::isError($ret)) {
			//--- 2017/06/16 Add by S.Okamoto エラーログ出力処理追加
			$this->write_log($ret->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $ret->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}

		return $ret;
*/
	}
	
	/**
	 * 1列取得(getCol)
	 * @access  public
	 * @param   string	$sql	SQL文
	 * @return  なし
	 */
	public function getCol($sql) {
		try {
			//Query発行
			$result = $this->_db->query($sql);
		} catch (PDOException $e) {
			//クエリ結果がエラー時のデバッグ表示
			$this->write_log($e->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $e->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}
		// 1カラムを1次元配列として返却
		return $result->fetchAll(PDO::FETCH_COLUMN);
/*
		$this->_db->setFetchMode($fetchmode);
		$ret = $this->_db->queryCol($sql);
		if (PEAR::isError($ret)) {
			//--- 2017/06/16 Add by S.Okamoto エラーログ出力処理追加
			$this->write_log($ret->getMessage() . (DBG_FLG ? "\r\n" . $sql : ""), $sql);
			throw new Exception(__METHOD__ . ": " . $ret->getMessage() . (DBG_FLG ? "<br>\r\n" . $sql : ""));
		}
		return $ret;
*/	}
	
	/**
	 * デストラクタ
	 * @access  public
	 * @param   なし
	 * @return  なし
	 */
	public function __destruct() {
		$this->_db = null;
/*
		if (PEAR::isError($this->_db) === false) {
			$this->_db->disconnect();
		}
*/
	}

}
?>
