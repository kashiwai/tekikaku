<?php
/*
 * PlayPoint.php
 * 
 * (C)SmartRams Corp. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * PlayPoint class
 * 
 * PplayPointの処理に関する各種命令
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since	 2019/03/06 初版作成 村上俊行
 *           2020/06/03 修正     村上俊行  区分によって精算時のポイント計算方法を切り替えれるように変更
 *           2020/06/24 修正     村上俊行  proc_dtの型変更による修正
 *           2021/02/17 修正     村上俊行  浮動小数点バグによるpoint誤差がでる問題の修正
 *           2022/09/21 修正     村上俊行  ポイント消化必須対応
 *           2022/09/26 修正     村上俊行  ポイント消化ログ出力対応
 * @info
 */


class PlayPoint {

	// メンバ変数定義
	private $_DB;												//DBインスタンス
	private $_commit = true;									//メソッド終了時にcommitする
	private $_nowDate;											//設定で使う本日日時
	//2020-06-24 proc_dt ms 対応
	private $_nowDatems;										//設定で使う本日日時(ms単位)
	private $_nowPoint;
	private $_error = array();
	//2022-09-26
	public $_deadlinePoint = 0;
	
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
		//2020-06-24 proc_dt ms 対応
		$this->_nowDatems = (new DateTime())->format("Y-m-d H:i:s.v");
		$this->_resetError();									//エラーの初期化
		$this->_nowPoint = 0;									//pointの初期化
	}

	/**
	 * draw_pointの計算
	 * @access  public
	 * @param	str		$mode				計算方法（"default" or "base2floor" :creditBase切り捨て "calc2floor":全て計算後切り捨て)
	 * @param	int		$credit				計算対象クレジット
	 * @param	int		$creditBase			母数クレジット
	 * @param	int		$pointBase			母数ポイント
	 * @return  int							drawpoint
	 */
	public function calcPoint($mode, $credit, $creditBase, $pointBase){
		// 今後顧客ごとに計算式が違う場合はここに条件と計算式を記載して対応
		if ($mode == 'calc2floor' ){
			//calc2floor
			//$draw_point = floor(($credit / $creditBase) * $pointBase);
			//2021-02-17 浮動小数点バグ回避
			//$draw_point = floor(bcmul(($credit / $creditBase),$pointBase,3));
			//2021-06-18 計算順序変更
			$draw_point = floor(bcmul($credit,($pointBase / $creditBase),3));
		} else {
			//default or base2floor
			$draw_point = floor($credit / $creditBase) * $pointBase;
		}
		return $draw_point;
	}

	/**
	 * ポイントの追加（amountから）
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @param	int		$purchase_type		購入方法
	 * @param	int		$amount				購入金額
	 * @param	int		$keyno				参照番号
	 * @param	str		$limit_dt			ポイント有効期限
	 * @param	string	$reason				加減算理由
	 * @param	int		$add_no				登録者
	 * @return  							true: 成功 false: 失敗
	 */
	public function addAmount2Point( $member_no, $purchase_type, $amount, $keyno="", $limit_dt="", $reason="", $add_no="" ){
	
		//エラーの初期化
		$this->_resetError();

		$addPoint      = 0;
		$removeLP      = 0;
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("purchase_type,amount,point")
				->from("mst_purchasePoint")
				->where()
					->and("purchase_type = ",    $purchase_type, FD_NUM)
					->and("amount = ",           $amount, FD_NUM)
			->createSQL("\n");
		$row = $this->_DB->getRow($sql);
		if ( $row["point"] == "" ){
			$this->_setError("not purchase");
			return( false );
		}
		$addPoint = $row["point"];
		$this->_nowPoint = $addPoint;
		
		if ( !$this->addPoint( $member_no, $purchase_type, $addPoint, $keyno="", $limit_dt="", $reason="", $add_no="" ) ){
			return( false );
		}
		
		return( true );
	}

	/**
	 * ポイントの追加（pointから）
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @param	int		$purchase_type		購入方法
	 * @param	int		$addPoint			追加ポイント
	 * @param	int		$keyno				参照番号
	 * @param	str		$limit_dt			ポイント有効期限
	 * @param	string	$reason				加減算理由
	 * @param	int		$add_no				登録者
	 * @return  							true: 成功 false: 失敗
	 */
	public function addPoint( $member_no, $purchase_type, $addPoint, $keyno="", $limit_dt="", $reason="", $add_no="" ){
	
		//エラーの初期化
		$this->_resetError();

		//加算が0の場合は処理しない
		if ( $addPoint == 0 ) return( true );


		$this->_nowPoint = $addPoint;

		$typeMode = "1";
		$pointFunc = sprintf("point+%d", $addPoint );
		if ( $addPoint <  0 ) {
			$typeMode = "2";
			$pointFunc = sprintf("point-%d",abs($addPoint) );
		}

		// トランザクション開始（トランザクションが開始されていなければ開始する）
		if ( !$this->_DB->inTransaction() ) $this->_DB->autoCommit(false);

		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("point,draw_point")
				->from("mst_member")
				->where()
					->and( "member_no =", $member_no, FD_NUM)
			->createSQL("\n");
		$memberRow = $this->_DB->getRow($sql);
		
		/* 2020-06-24 関数分離時に処理が不要になった
		//LPが対象であれば
		if ( $purchase_type == "11" ) {
			$removeLP = $amount;
			if ( $amount > $memberRow["draw_point"] ){
				$this->_DB->rollBack();
				$this->_setError("draw_point is low");
				return( false );
			}
		}
		*/

		//ポイント履歴を追加
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->insert()
				->into("his_point")
					->value("member_no",       $member_no, FD_NUM)
					//2020-06-24 ミリ秒対応
					->value("proc_dt",         $this->_nowDatems, FD_DATE)
					->value("type",            $typeMode, FD_NUM)
					->value("proc_cd",         $purchase_type, FD_STR)
					->value("key_no",          $keyno, FD_NUM)
					->value("limit_dt",        $limit_dt, FD_DATE)
					->value("before_point",    $memberRow["point"], FD_NUM)
					->value("point",           abs($addPoint), FD_NUM)
					->value("after_point",     $memberRow["point"]+$addPoint, FD_NUM)
					->value("reason",          $reason, FD_STR)
					->value("add_no",          $add_no, FD_NUM)
			->createSQL("\n");
			
//		print($this->_nowDatems);
//		print($sql);

		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("his_point insert error");
			return( false );
		}
		//期限付きポイント履歴を追加
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->insert()
				->into("his_pointLimit")
					->value("member_no",       $member_no, FD_NUM)
					->value("proc_dt",         $this->_nowDate, FD_DATE)
					->value("proc_cd",         $purchase_type, FD_NUM)
					->value("limit_dt",        $limit_dt, FD_DATE)
					->value("point",           $addPoint, FD_NUM)
					->value("valid_point",     $addPoint, FD_NUM)
			->createSQL("\n");
			
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("his_pointLimit insert error");
			return( false );
		}


		//会員情報のポイントを加算
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->update("mst_member")
				->set()
					->value("point",      $pointFunc, FD_FUNCTION)
					->value("upd_no",     API_PLAYPOINT_UPD_NO, FD_NUM)
					->value("upd_dt",     $this->_nowDate, FD_DATE)
				->where()
					->and( "member_no =", $member_no, FD_NUM)
			->createSQL("\n");
			
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("mst_member update error");
			return( false );
		}
		
		//コミット（_commitがtrueならコミット処理する）
		if ( $this->_commit == true ) $this->_DB->autoCommit(true);
		
		//抽選ポイント更新
		/* 
		if ( $removeLP > 0 ){
			if ( !$this->addDraw( $member_no, "52", $removeLP*-1, $keyno="" ) ){
				return( false );
			}
		}
		*/
		
		return( true );
	}

	/**
	 * ポイント使用
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @param	int		$usePoint			使用するポイント
	 * @param	int		$purchase_type		処理No
	 * @param	int		$keyno				参照番号
	 * @param	string	$reason				加減算理由
	 * @param	int		$add_no				登録者
	 * @return  							true: 成功 false: 失敗
	 */
	public function usePoint( $member_no, $usePoint, $purchase_type, $keyno="", $reason="", $add_no="", $limitFlg=true ){

		//ポイント履歴を有効期限順に取得する
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("point_no,limit_dt,point,valid_point")
				->from("his_pointLimit")
				->where()
					->and( "member_no =",   $member_no, FD_NUM)
					->and( "valid_point >", "0", FD_NUM )
				->orderby("limit_dt is null asc,limit_dt asc,point_no asc")			//nullを後ろにするのでis nullとないものを2回実行している
			->createSQL("\n");
		$records = $this->_DB->getAll($sql);
		
		$idx = 0;
		$leftPoint = $usePoint;
		$deadlinePoint = 0;
		$pointLog = array();
		//ポイントを有効期限順に消費させる
		while( $leftPoint > 0 && count($records) > $idx ){
			if ( $limitFlg == false && $records[$idx]["limit_dt"] != "" ){
				$idx++;
				continue;
			}
			if ( $records[$idx]["valid_point"] < $leftPoint ) {
				// 2022-09-21 ポイント消化必須対応 追加
				if ( $records[$idx]["limit_dt"] != "" ) {
					$deadlinePoint += $records[$idx]["valid_point"];
				}
				//履歴の残りポイントが消費ポイントより小さい時
				$pointLog[] = array( "point" => $records[$idx]["valid_point"], "point_no" => $records[$idx]["point_no"] );
				$leftPoint -= $records[$idx]["valid_point"];
				$records[$idx]["valid_point"] = 0;
				
				$idx++;
			} else {
				// 2022-09-21 ポイント消化必須対応 追加
				if ( $records[$idx]["limit_dt"] != "" ) {
					$deadlinePoint += $leftPoint;
				}
				//消費ポイントが全て賄える時
				$pointLog[] = array( "point" => $leftPoint, "point_no" => $records[$idx]["point_no"] );
				$records[$idx]["valid_point"] -= $leftPoint;
				$leftPoint = 0;
			}
		}

		if ( $leftPoint > 0 ){
			//保有ポイントが足りない
			$this->_setError("not enough point" );
			return( false );
		}

		// トランザクション開始（トランザクションが開始されていなければ開始する）
		if ( !$this->_DB->inTransaction() ) $this->_DB->autoCommit(false);

		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("member_no,point,draw_point,deadline_point")
				->from("mst_member")
				->where()
					->and( "member_no =", $member_no, FD_NUM)
				->forUpdate()
			->createSQL("\n");
		$memberRow = $this->_DB->getRow($sql);
		if ( $memberRow["member_no"] == "" ){
			$this->_DB->rollBack();
			$this->_setError("mst_member read error");
			return( false );
		}

		// 2022-09-26 有効期限付きポイントをプロパティに保存
		if ($deadlinePoint > 0) {
			$this->_deadlinePoint = $deadlinePoint + $memberRow["deadline_point"];
		}

		//idxまでの期限付きポイント履歴有効ポイント更新
		for( $i=0;$i<=$idx;$i++ ){
			$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
				->update("his_pointLimit")
					->set()
						->value("valid_point",   $records[$i]["valid_point"], FD_NUM)
					->where()
						->and( "point_no =",  $records[$i]["point_no"], FD_NUM)
				->createSQL("\n");
				
			$result = $this->_DB->query($sql);
			if ( $result == false ){
				$this->_DB->rollBack();
				$this->_setError("his_pointLimit update error");
				return( false );
			}
		}
		//log_pointLimitを追加
		foreach( $pointLog as $logrecord ){
			$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
				->insert()
					->into("log_pointLimit")
						->value("point_no",        $logrecord["point_no"], FD_NUM)
						//2020-06-24 ミリ秒対応
						->value("proc_dt",         $this->_nowDatems, FD_DATE)
						->value("point",           $logrecord["point"], FD_NUM)
				->createSQL("\n");
			$result = $this->_DB->query($sql);
			if ( $result == false ){
				$this->_DB->rollBack();
				$this->_setError("his_pointLimit update error");
				return( false );
			}
		}
		//ポイント履歴に追加
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->insert()
				->into("his_point")
					->value("member_no",       $member_no, FD_NUM)
					//2020-06-24 ミリ秒対応
					->value("proc_dt",         $this->_nowDatems, FD_DATE)
					->value("type",            "2", FD_NUM)
					->value("proc_cd",         $purchase_type, FD_NUM)
					->value("key_no",          $keyno, FD_NUM)
					->value("limit_dt",        "", FD_DATE)
					->value("before_point",    $memberRow["point"], FD_NUM)
					->value("point",           $usePoint, FD_NUM)
					->value("after_point",     $memberRow["point"]-$usePoint, FD_NUM)
					->value("reason",          $reason, FD_STR)
					->value("add_no",          $add_no, FD_NUM)
			->createSQL("\n");
			
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("his_point insert error");
			return( false );
		}

		//memberを更新
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->update("mst_member")
				->set()
					->value("point",      $memberRow["point"]-$usePoint, FD_NUM)
					->value("upd_no",     API_PLAYPOINT_UPD_NO, FD_NUM)
					->value("upd_dt",     $this->_nowDate, FD_DATE)
					// 2022-09-21 ポイント消化必須対応 追加
					->value("deadline_point", $deadlinePoint + $memberRow["deadline_point"], FD_NUM)
				->where()
					->and( "member_no =", $member_no, FD_NUM)
			->createSQL("\n");
			
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("mst_member update error");
			return( false );
		}

		//コミット（_commitがtrueならコミット処理する）
		if ( $this->_commit == true ) $this->_DB->autoCommit(true);

		return( true );
	}

	public function checkExpired( $expiredTime ){
		//ポイント削除をnoremoveモードで実行
		return( $this->removeExpired( $expiredTime, "0", true ) );
	}
	/**
	 * 有効期限切れポイントの削除
	 * @access  public
	 * @param	str		$expiredTime		有効期限（この日時より前のデータが対象。この日時を含まない）
	 * @param	int		$add_no				登録者
	 * @param	int		$noremove			削除しない default:false
	 * @return  array						対象のmember_no, point, limit_dtを連想配列で返す
	 */
	public function removeExpired( $expiredTime, $add_no, $noremove=false){
		
		// トランザクション開始（トランザクションが開始されていなければ開始する）
		if ( !$this->_DB->inTransaction() ) $this->_DB->autoCommit(false);

		//指定有効期限が切れたデータを探す
		$sql = (new SqlString($this->_DB))
			->select()
				->field("pl.point_no,pl.member_no,pl.limit_dt,pl.point,pl.valid_point")
				->field("hp.reason")
				->field("mm.point as mmpoint")
				->from("his_pointLimit pl ")
				->join("left", "his_point hp", "pl.member_no = hp.member_no and pl.proc_dt = hp.proc_dt and pl.proc_cd = hp.proc_cd")
				->join("left", "mst_member mm", "pl.member_no = mm.member_no")
				->where()
					->and( "pl.limit_dt is not null" )
					->and( "pl.limit_dt < ",     $expiredTime, FD_DATE)
					->and( "pl.valid_point > ",  "0",          FD_NUM)
				->orderby("pl.member_no, pl.limit_dt")
			->createSQL("\n");
		$targetRows = $this->_DB->getAll($sql, MDB2_FETCHMODE_ASSOC);

		//noremoveモードならここで検索結果のみ返す
		if ( $noremove == true ) return( $targetRows );

		$savemember_no = ((!empty($targetRows[0]["member_no"])) ? $targetRows[0]["member_no"] : 0);
		$idx = 0;
		$sum_point = 0;
		foreach( $targetRows as $rec ){
			$idx++;
			//print_r( $rec );
			
			//point消込処理
			$sql = (new SqlString($this->_DB))
				->update("his_pointLimit")
					->set()
						->value("valid_point",   "0", FD_NUM)
					->where()
						->and( "point_no =",     $rec["point_no"], FD_NUM)
				->createSQL("\n");

			//print $sql;
			$result = $this->_DB->query($sql);
			if ( $result == false ){
				$this->_DB->rollBack();
				$this->_setError("his_pointLimit update error");
				return( false );
			}
			//log_pointLimitを追加(0にするのでvalid_pointをpointに指定）
			$sql = (new SqlString($this->_DB))
				->insert()
					->into("log_pointLimit")
						->value("point_no",        $rec["point_no"], FD_NUM)
						//2020-06-24 ミリ秒対応
						->value("proc_dt",         $this->_nowDatems, FD_DATE)
						->value("point",           $rec["valid_point"], FD_NUM)
				->createSQL("\n");
			//print $sql;
			$result = $this->_DB->query($sql);
			if ( $result == false ){
				$this->_DB->rollBack();
				$this->_setError("his_pointLimit update error");
				return( false );
			}

			//member_noが変わるまでポイント集計しながらレコードを処理
			if ( $savemember_no != $rec["member_no"] ){
				if ( !$this->_updateExpiredMember( $savemember_no, $mm_point, $sum_point, $add_no ) ) return( false );
				$sum_point = 0;
			}
			//キー更新
			$savemember_no  = $rec["member_no"];
			//集計
			$sum_point     += intval($rec["valid_point"]);
			$mm_point       = $rec["mmpoint"];
		}
		if ( $idx > 0 ){
			if ( !$this->_updateExpiredMember( $savemember_no, $mm_point, $sum_point, $add_no ) ) return( false );
		}

		//コミット（_commitがtrueならコミット処理する）
		if ( $this->_commit == true ) $this->_DB->autoCommit(true);

		//処理した一覧を返す
		return( $targetRows );
	}

	/**
	 * 有効期限切れポイント処理のメンバー更新
	 * @access  public
	 * @param	int		$savemember_no		会員番号
	 * @param	int		$mm_point			減算前ポイント
	 * @param	int		$sum_point			今回失効ポイント
	 * @param	int		$add_no				登録者
	 * @return  boolean						DB処理の結果 true:成功 false:失敗
	 */
	private function _updateExpiredMember( $savemember_no, $mm_point, $sum_point, $add_no ){
	
		if ( $mm_point-$sum_point < 0 ){
			$afterPoint = 0;
			$point = $mm_point;
		} else {
			$afterPoint = $mm_point-$sum_point;
			$point = $sum_point;
		}
	
		//ポイント履歴に追加
		$sql = (new SqlString($this->_DB))
			->insert()
				->into("his_point")
					->value("member_no",       $savemember_no, FD_NUM)
					//2020-06-24 ミリ秒対応
					->value("proc_dt",         $this->_nowDatems, FD_DATE)
					->value("type",            "2",  FD_NUM)
					->value("proc_cd",         "92", FD_NUM)
					->value("key_no",          "",   FD_NUM)
					->value("limit_dt",        "",   FD_DATE)
					->value("before_point",    $mm_point, FD_NUM)
					->value("point",           $point, FD_NUM)
					->value("after_point",     $afterPoint, FD_NUM)
					->value("reason",          $GLOBALS["pointHistoryProcessCode"]["92"], FD_STR)
					->value("add_no",          $add_no, FD_NUM)
			->createSQL("\n");
			
		//print $sql;
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("his_point insert error");
			return( false );
		}

		//memberを更新
		$sql = (new SqlString($this->_DB))
			->update("mst_member")
				->set()
					->value("point",      $afterPoint, FD_NUM)
					->value("upd_no",     API_PLAYPOINT_UPD_NO, FD_NUM)
					->value("upd_dt",     $this->_nowDate, FD_DATE)
				->where()
					->and( "member_no =", $savemember_no, FD_NUM)
			->createSQL("\n");
			
		//print $sql;
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("mst_member update error");
			return( false );
		}
		
		return( true );

	}

	/**
	 * 抽選ポイントの追加
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @param	int		$purchase_type		購入方法
	 * @param	int		$amount				購入金額
	 * @param	int		$keyno				参照番号
	 * @param	int		$limit_dt			ポイント有効期限
	 * @param	string	$reason				加減算理由
	 * @param	int		$add_no				登録者
	 * @return  							true: 成功 false: 失敗
	 */
	public function addDrawPoint( $member_no, $purchase_type, $drawpoint, $keyno="", $reason="", $add_no="", $deadlinePoint=-1){
	
		//エラーの初期化
		$this->_resetError();

		//加算が0の場合は処理しない
		if ( $drawpoint == 0 ) return( true );

		$typeMode = "1";
		$pointFunc = "draw_point+{$drawpoint}";
		if ( $drawpoint <  0 ) {
			$typeMode = "2";
			$pointFunc = "draw_point-".abs($drawpoint);
		}

		// トランザクション開始（トランザクションが開始されていなければ開始する）
		if ( !$this->_DB->inTransaction() ) $this->_DB->autoCommit(false);

		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("point,draw_point")
				->from("mst_member")
				->where()
					->and( "member_no =", $member_no, FD_NUM)
			->createSQL("\n");
		$memberRow = $this->_DB->getRow($sql);
		
		//抽選ポイント履歴を追加
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->insert()
				->into("his_drawPoint")
					->value("member_no",         $member_no, FD_NUM)
					//2020-06-24 ミリ秒対応
					->value("proc_dt",           $this->_nowDatems, FD_DATE)
					->value("type",              $typeMode, FD_NUM)
					->value("proc_cd",           $purchase_type, FD_NUM)
					->value("key_no",            $keyno, FD_NUM)
					->value("before_draw_point", $memberRow["draw_point"], FD_NUM)
					->value("draw_point",        Abs($drawpoint), FD_NUM)
					->value("after_draw_point",  $memberRow["draw_point"]+$drawpoint, FD_NUM)
					->value("reason",            $reason, FD_STR)
					->value("add_no",            $add_no, FD_NUM)
			->createSQL("\n");
			
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("his_drawPoint insert error");
			return( false );
		}

		//会員情報のポイントを加算
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->update("mst_member")
				->set()
					->value("draw_point", $pointFunc, FD_FUNCTION)
					->value("upd_no",     API_PLAYPOINT_UPD_NO, FD_NUM)
					->value("upd_dt",     $this->_nowDate, FD_DATE)
				->where()
					->and( "member_no =", $member_no, FD_NUM)
			->createSQL("\n");
			
		$result = $this->_DB->query($sql);
		if ( $result == false ){
			$this->_DB->rollBack();
			$this->_setError("mst_member update error");
			return( false );
		}
		
		//コミット（_commitがtrueならコミット処理する）
		if ( $this->_commit == true ) $this->_DB->autoCommit(true);

		return( true );
	}

	/** ポイント再取得
	*/
	public function pointReSession(){
		//2020-06-24 パラメータ廃止でデフォルト値設定に変更
		$url = URL_SSL_SITE . "login.php";
		//$url = (mb_strlen($redirectUrl) > 0) ? $redirectUrl : URL_SSL_SITE . "login.php";
		// セッションインスタンス生成
		//2020-06-24 パラメータ廃止でデフォルト値設定に変更
		$this->Session = new SmartSession($url, SESSION_SEC, SESSION_SID, DOMAIN, false);
		//$this->Session = new SmartSession($url, SESSION_SEC, SESSION_SID, DOMAIN, $isReturn);
		// DB認証チェック
		$sql = (new SqlString())
				->setAutoConvert( [$this->_DB,"conv_sql"] )
				->select()
					->field("point")
					->from("mst_member")
					->where()
						->and("mail = ", $this->Session->UserInfo["mail"], FD_STR)
						->and("pass = ", $this->Session->UserInfo["pass"], FD_STR)
						->and("state = ", "1", FD_NUM)
				->createSQL();
		$row = $this->_DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		$this->Session->UserInfo["point"] = $row["point"];
	}

	/**
	 * エラーの取得
	 * @access  public
	 * @param	string		$del			区切り文字 "\n"
	 * @return  							エラー文字列
	 */
	public function getError( $del="\n" ) {
		return( implode( $del, $this->error ) );
	}

	/**
	 * エラーの設定
	 * @access  public
	 * @param	string		$message		エラーメッセージ
	 * @return  							なし
	 */
	private function _resetError() {
		$this->error = array();
	}

	/**
	 * エラーの設定
	 * @access  public
	 * @param	string		$message		エラーメッセージ
	 * @return  							なし
	 */
	private function _setError( $message ) {
		$this->error[] = $message;
	}

}

?>