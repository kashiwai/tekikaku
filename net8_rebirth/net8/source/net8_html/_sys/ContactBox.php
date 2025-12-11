<?php
/*
 * ContactBox.php
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
 * ContactBox class
 * 
 * ContactBoxの処理に関する各種命令
 * 
 * @package
 * @author   片岡充
 * @version  1.0
 * @since	 2019/07/10 初版作成 片岡充
 * @info
 */


class ContactBox {

	// メンバ変数定義
	private $_DB;												//DBインスタンス
	private $_commit = true;									//メソッド終了時にcommitする
	private $_nowDate;											//設定で使う本日日時
	
	/**
	 * コンストラクタ
	 * @access  public
	 * @return  						インスタンス
	 */
	public function __construct( $DB, $commit=true ) {
		// メンバ変数へ格納
		$this->_DB = $DB;
		$this->_commit = $commit;
		$this->_nowDate = Date("Y-m-d H:i:s");
	}
	
	/**
	 * メンバー毎、次seq番号取得用SQL文作成
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @return	string						SQL文
	 */
	public function getNextRecordSeq( $member_no){
		$csql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("COALESCE(max(sub_dcb.seq),0) + 1")
				->from("dat_contactBox sub_dcb")
				->where()
					->and( "sub_dcb.member_no =", $member_no, FD_NUM)
			->createSQL();
		return $csql;
	}
	
	/**
	 * 連絡Boxに1レコード追加
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @param	string	$type				メッセージ種別 01：クーポン / 02：当選通知 / 03：自動精算 / 04：招待ポイント
	 * @param	int		$key_no				キーナンバー
	 * @param	array	$message			登録メッセージの連想配列
	 * @param	date	$deli_dt			配信日時
	 * @param	int		$add_no				登録者
	 * @return  							true: 成功 false: 失敗
	 */
	public function addOneRecord( $member_no, $type, $key_no, $message, $deli_dt = "", $add_no = ""){
		
		// トランザクション開始（トランザクションが開始されていなければ開始する）
		if ( !$this->_DB->inTransaction() ) $this->_DB->autoCommit(false);
		
		//レコード追加連番作成
		$csql = $this->getNextRecordSeq( $member_no);
		
		// seq取得でズレてしまうので lang側が先。
		$params = (mb_strlen($add_no)>0)? "(member_no,seq,lang,contents,add_no,add_dt)":"(member_no,seq,lang,contents,add_dt)";
		$values = array();
		foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
			$str =   "(" . $member_no
					."," . "(" . $csql . ")"
					."," . $this->_DB->conv_sql( $k, FD_STR)
					."," . $this->_DB->conv_sql( $message[ $k], FD_STR);
			if( mb_strlen($add_no) > 0) $str .= "," . $this->_DB->conv_sql( $add_no, FD_NUM);
			$str .= ", current_timestamp)";
			$values[] = $str;
		}
		$sql = "insert into dat_contactBox_lang ". $params ." values ". implode(',', $values) .";";
		$result = $this->_DB->query($sql);
		if ( $result == false ) return( false );
		
		//ポイント履歴を追加
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->insert()
				->into("dat_contactBox")
					->value("member_no"    , $member_no     , FD_NUM)
					->value("seq"          , "(".$csql.")"  , FD_FUNCTION)
					->value("contact_type" , $type          , FD_STR)
					->value("key_no"       , $key_no        , FD_NUM)
					->value("delivery_dt"  , ((mb_strlen($deli_dt)>0)? $deli_dt: $this->_nowDate), FD_STR)
					->value(true, "add_no" , $add_no, FD_NUM)
					->value("add_dt"       , "current_timestamp", FD_FUNCTION)
			->createSQL("\n");
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			return( false );
		}
		
		//コミット（_commitがtrueならコミット処理する）
		if ( $this->_commit == true ) $this->_DB->autoCommit(true);
		
		return( true );
	}
	
	
	/**
	 * 連絡Boxに複数会員のレコードを追加
	 * @access  public
	 * @param	array	$members			会員NOの連想配列（会員番号は member_no で取得する）
	 * @param	string	$type				メッセージ種別 01：クーポン / 02：当選通知 / 03：自動精算 / 04：招待ポイント
	 * @param	int		$key_no				キーナンバー
	 * @param	string	$message			登録メッセージ
	 * @param	date	$deli_dt			配信日時
	 * @param	int		$add_no				登録者
	 * @return  							true: 成功 false: 失敗
	 */
	public function addRecords( $members, $type, $key_no, $message, $deli_dt = "", $add_no = ""){
		
		$params  = (mb_strlen($add_no)>0)? "(member_no,seq,lang,contents,add_no,add_dt)":"(member_no,seq,lang,contents,add_dt)";
		$params2 = "(member_no,seq,contact_type,key_no,delivery_dt,". ((mb_strlen($add_no)>0)? "add_no,":"") ."add_dt)";
		$values  = array();
		$values2 = array();
		foreach( $members as $row){
			//レコード追加連番作成
			$csql = $this->getNextRecordSeq( $row["member_no"]);
			//lang用
			foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
				$str =   "(" . $row["member_no"]
						."," . "(" . $csql . ")"
						."," . $this->_DB->conv_sql( $k, FD_STR)
						."," . $this->_DB->conv_sql( $message[ $k], FD_STR);
				if( mb_strlen($add_no) > 0) $str .= "," . $this->_DB->conv_sql( $add_no, FD_NUM);
				$str .= ", current_timestamp)";
				$values[] = $str;
			}
			//box用
			$str =   "(" . $row["member_no"]
					."," . "(" . $csql . ")"
					."," . $this->_DB->conv_sql( $type, FD_STR)
					."," . $this->_DB->conv_sql( $key_no, FD_NUM)
					."," . $this->_DB->conv_sql( ((mb_strlen($deli_dt)>0)? $deli_dt: $this->_nowDate), FD_STR);
			if( mb_strlen($add_no)  > 0) $str .= "," . $this->_DB->conv_sql( $add_no, FD_NUM);
			$str .= ", current_timestamp)";
			$values2[] = $str;
		}
		
		$sql_lang = "insert into dat_contactBox_lang ". $params ." values ". implode(',', $values) .";";
		$sql      = "insert into dat_contactBox ". $params2 ." values ". implode(',', $values2) .";";
		
		// トランザクション開始（トランザクションが開始されていなければ開始する）
		if ( !$this->_DB->inTransaction() ) $this->_DB->autoCommit(false);
		
		$result = $this->_DB->query($sql_lang);
		if ( $result == false ) return( false );
		$result = $this->_DB->query($sql);
		if ( $result == false ) return( false );
		
		//コミット（_commitがtrueならコミット処理する）
		if ( $this->_commit == true ) $this->_DB->autoCommit(true);
		
		return( true );
		
	}
	

}

?>