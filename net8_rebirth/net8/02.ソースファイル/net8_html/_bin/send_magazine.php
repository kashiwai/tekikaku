#!/usr/bin/php -q
<?php
/*
 * send_magazine.php
 * 
 * (C)SmartRams Co.,Ltd. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * メルマガ配信処理
 * 
 * メルマガの配信処理を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/01/30 初版作成 片岡 充
 */

// インクルード
require_once('../_etc/require_files_batch.php');			// requireファイル

// メイン処理
main();

/**
 * メイン処理
 * @access	public
 * @param	なし
 * @return	なし
 * @info	
 */
function main() {
	// 多重起動回避
	$filepath = DIR_BIN . "send_magazine.txt";
	// ファイル内容取得
	if (file_exists($filepath)) {
		$old_pid = file_get_contents($filepath);
		if (mb_strlen($old_pid) > 0) {
			$check = exec("ps ax | awk '$1==" . trim($old_pid) . " {print $5}'");
			// 前回のプロセスが起動中なら、処理を抜ける
			if (isset($check) && mb_strlen($check) > 0) exit;
		}
	}
	// プロセスID更新(start)
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, getmypid());
		fclose($fp);
	}

	// DBクラスのインスタンス生成
	$db = new NetDB();
	// メルマガ配信処理
	SendMagazine($db);
	$db->disconnect();	// DB解放

	// 多重起動回避　処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}

/**
 * メルマガ配信処理
 * @access	private
 * @param	object	$db		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function SendMagazine($db, $message = "") {
	// 初期処理
	$currentDatetime = date("Y-m-d H:i:s");
	
	// 対象未作成メルマガを取得
	$sqls = new SqlString();
	$sql = $sqls->setAutoConvert( [$db,"conv_sql"] )
				->select()
					->field( "dm.*" )
					->from("dat_magazine dm")
					->where()
						->and( "dm.del_flg <> ", 1, FD_NUM)
						//検索
						->and( false, "dm.plan_dt <= "       , $currentDatetime, FD_DATEEX )
						->and( false, "dm.magazine_state = " , 0   , FD_NUM)
			->createSql();
	$rs = $db->query( $sql);

	// トランザクション開始
	$db->autoCommit(false);
	while ($magazineRow = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// 送信対象会員取得
		$rows = $db->getMemberRows( array_change_key_case( $magazineRow, CASE_UPPER), true);
		if( count( $rows) > 0){
			// メルマガ更新
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->update("dat_magazine")
					->set()
						->value("magazine_state", 1, FD_NUM)							//送信中
						->value("make_dt"       , "current_timestamp", FD_FUNCTION)
						->value("send_start_dt" , "current_timestamp", FD_FUNCTION)
						->value("upd_no"        , BATCH_UPD_NO, FD_NUM)
						->value("upd_dt"        , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "magazine_no =" , $magazineRow["magazine_no"], FD_NUM)
				->createSQL();
			$db->query($sql);

			//メルマガ送信対象
			$params = "(magazine_no, member_no, state, add_dt)";
			$values = array();
			foreach( $rows as $row){
				$str =   "(" . $db->conv_sql( $magazineRow["magazine_no"], FD_NUM)
						."," . $db->conv_sql( $row["member_no"], FD_NUM)
						."," . $db->conv_sql( 0, FD_NUM)
						.", current_timestamp)";
				$values[] = $str;
			}
			$sql = "insert into dat_magazineTarget ". $params ." values ". implode(',', $values) .";";
			$db->query($sql);
		}else{
			//対象者0
			//メルマガ更新
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->update("dat_magazine")
					->set()
						->value("magazine_state", 2, FD_NUM)							//送信済
						->value("send_count"    , 0, FD_NUM)
						->value("send_start_dt" , "current_timestamp", FD_FUNCTION)
						->value("send_end_dt"   , "current_timestamp", FD_FUNCTION)
						->value("upd_no"        , BATCH_UPD_NO, FD_NUM)
						->value("upd_dt"        , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "magazine_no =" , $magazineRow["magazine_no"], FD_NUM)
				->createSQL();
			$db->query($sql);
		}
	}
	// コミット(トランザクション終了)
	$db->autoCommit(true);
	unset($rs);
	
	//対象者にメール送信処理
	require_once(DIR_LIB . "SmartMailSend.php");	// メール送信クラスライブラリ
	$mailSend = new SmartMailSend(MAIL_PROTOCOL, $GLOBALS["MailParam"]);
	
	// 送信間隔取得
	$interval = MAGAZINE_SEND_INTERVAL;

	// 送信制限数取得
	$sendLimit = MAGAZINE_SEND_LIMIT;

	// 送信終了フラグ
	$sendFinish = false;

	// 送信数カウント
	$sendCount = 0;
	// 送信開始日時
	$sendStart = $currentDatetime;
	// 初期送信フラグ
	$sendFirst = false;
	
	// トランザクション開始
	$db->autoCommit(false);

	// メルマガ送信状態取得(データが存在しない場合は新規作成)
	$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->select()
					->field( "dmc.no, dmc.send_start_dt, dmc.send_count" )
					->from("dat_magazineControl dmc")
					->where()
						->and( "dmc.no = ", 1, FD_NUM)
			->createSql();
	$rowState = $db->getRow($sql);
	if (mb_strlen($rowState["no"]) > 0) {
		$sendCount = (int)$rowState["send_count"];
		$sendStart = $rowState["send_start_dt"];
	} else {
		// 送信履歴無し
		$sendFirst = true;
		$sendCount = 0;
		$sendStart = $currentDatetime;
		// レコードが存在しない場合新規追加
		$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
			->insert()
				->into( "dat_magazineControl" )
					->value( "no"    , 1, FD_NUM)
					->value( "send_start_dt" , $sendStart, FD_DATE)
			->createSQL();
		$db->query($sql);
	}
	
	// 前回送信開始時間から制限時間以内で、送信件数が制限数に達した場合は処理終了
	$baseDatetime = date("Y-m-d H:i:s", strtotime("-". $interval ." minute"));
	if (!$sendFirst && format_datetime($sendStart) >= format_datetime($baseDatetime)) {
		if ($sendLimit > 0) {
			if (($sendLimit - $sendCount) <= 0) $sendFinish = true;
		} else {
			$sendFinish = true;
		}
	} else {
		$sendFirst = true;
		$sendCount = 0;
	}
	
	// 対象配信データ取得(配信データが作成済みで配信が完了していないデータ)
	$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->select()
					->field( "dm.*" )
					->from("dat_magazine dm")
					->where()
						->and("dm.del_flg = ", 0, FD_NUM)
						->and("dm.magazine_state = ", 1, FD_NUM)
						->and("dm.make_dt is not null")
						->and("dm.send_end_dt is null")
					->orderby("dm.plan_dt asc, dm.magazine_no asc")
			->createSql();
	$rs = $db->query($sql);
	$procRows = $rs->rowCount();
	
	// 処理件数が存在する場合
	if ($procRows > 0 && !$sendFinish) {
		if ($sendFirst) {
			// メルマガ送信状態開始日時更新
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->update( "dat_magazineControl" )
					->set()
						->value("send_start_dt", "current_timestamp", FD_FUNCTION)
						->value("send_end_dt"  , "null", FD_FUNCTION)
					->where()
						->and("no =", 1, FD_NUM)
				->createSQL();
			$db->query($sql);
		}
	}
	
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// Send Magazine NO
		// 送信終了フラグがtrueの場合は送信終了
		if ($sendFinish) break;
		// 送信処理
		mod_proc($db, $mailSend, $row, $sendLimit, $sendFinish, $sendCount);
	}
	unset($rs);

	// コミット(トランザクション終了)
	$db->autoCommit(true);
	
}



/**
 * 送信処理
 * @access	public
 * @param	object	$db				DBオブジェクト
 * @param	object	$mailSend		MailSendオブジェクト
 * @param	array	$data			配信対象データ
 * @param	integer	$sendLimit		配信制限数
 * @param	boolean	$sendFinish		配信終了フラグ
 * @param	integer	$sendCount		配信数
 * @return	なし
 * @info	
 */
function mod_proc(&$db, &$mailSend, $data, $sendLimit, &$sendFinish, &$sendCount) {
	
	//*** 送信前に状態の再確認
	$sql = (new SqlString($db))
				->select()
					->field( "dm.del_flg, dm.magazine_state" )
					->from("dat_magazine dm")
					->where()
						->and("dm.magazine_no = ", $data["magazine_no"], FD_NUM)
			->createSql();
	$chkflg = $db->getRow($sql);
	if ($chkflg["del_flg"] == "1" || $chkflg["magazine_state"] > "1") return;
	
	//*** 実送信処理
	$currentDatetime = date("Y-m-d H:i:s");
	
	// メルマガ送信データ取得
	$sql = (new SqlString($db))
				->select()
					->field( "dmt.*, mm.nickname, mm.mail" )
					->field( "mm.state as member_state, mm.mail_error_count" )
					->from("dat_magazineTarget dmt")
					->from("inner join mst_member mm on mm.member_no = dmt.member_no")
					->where()
						->and("dmt.state = ", 0, FD_NUM)
						->and("dmt.magazine_no = ", $data["magazine_no"], FD_NUM)
					->orderby("dmt.member_no asc")
			->createSql();
	// 一斉送信数を決めている場合
	if($sendLimit > 0) $sql .= " limit " . ($sendLimit - $sendCount);
	$rs = $db->query($sql);
	
	$mailFrom  = MAIL_MAGAZINE_FROM;		// メルマガ配信元メールアドレス
	$mailErrror = MAIL_MAGAZINE_ERROR;		// メルマガ配信エラーメールアドレス
	
	// メルマガ送信用の基本設定をセット
	$mailSend->setMailSendData($mailFrom, "", "", "", $mailErrror, array());
	$lastMail = "";
	$lastNo   = "";
	$ins_contact = array();		// 連絡Box登録用会員No
	
	// 全対象会員分Loop
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		
		// 配信終了フラグがtrueの場合は送信終了
		if ($sendFinish) break;
		

		if (mb_strlen($row["mail"]) > 0 && $row["member_state"] == 1) {	// mail設定 且 本会員
			// アドレス種別により本文取得
			$body = $data["contents"];
			$body = str_replace( "{%NAME%}", $row["nickname"], $body);

			// メルマガ送信
			if ((int)$row["mail_error_count"] < MAGAZINE_MAIL_ERR_COUNT) {	// 送信エラ回数未満
				$lastMail = $row["mail"];
				$lastNo   = $row["member_no"];
				
				// 送信実行
				$mailSend->setToData( $row["mail"]);
				$mailSend->make( $data["title"], $body);
				$mailSend->send();
				
				// メルマガデータの更新
				$sql = (new SqlString($db))
					->update( "dat_magazine" )
						->set()
							->value("send_count", "coalesce(send_count,0) + 1", FD_FUNCTION)
							->value("upd_dt"    , "current_timestamp", FD_FUNCTION)
							->value("upd_no"    , BATCH_UPD_NO, FD_NUM)
						->where()
							->and( false, "magazine_no =" , $data["magazine_no"], FD_NUM)
					->createSQL();
				$db->query($sql);
			}
			// メルマガ内容連絡Box登録
			if (MAGAZINE_TO_CONTACTBOX) $ins_contact[] = $row;
		}
		
		// メルマガ送信データの更新
		$sql = (new SqlString($db))
			->update( "dat_magazineTarget" )
				->set()
					->value("state" , "1", FD_NUM)
					->value("upd_dt", "current_timestamp", FD_FUNCTION)
				->where()
					->and("magazine_no =", $data["magazine_no"], FD_NUM)
					->and("member_no ="  , $row["member_no"], FD_NUM)
			->createSQL();
		$db->query($sql);
		
		// 送信数のカウント
		if ($sendLimit > 0) {
			$sendCount++;
			if($sendCount >= $sendLimit) $sendFinish = true;
		}
		
	}
	unset($rs);

	// メルマガ内容連絡Box登録
	if (MAGAZINE_TO_CONTACTBOX) {
		$contact_message = array();
		$search  = array( "%MAGAZINE_TITLE%");
		foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
			$replace = array($data["title"]);
			$contact_message[$k] = str_replace( $search, $replace, $v["09"]);
		}
		$contact = new ContactBox($db, false);		// ContactBox classインスタンス生成
		$contact->addRecords($ins_contact, "09", $data["magazine_no"], $contact_message, "", BATCH_UPD_NO);
	}

	// メルマガ送信状態更新
	$sql = (new SqlString($db))
		->update( "dat_magazineControl" )
			->set()
				->value("send_end_dt", "current_timestamp", FD_FUNCTION)
				->value("magazine_no", $data["magazine_no"], FD_NUM)
				->value("member_no"  , $lastNo, FD_NUM)
				->value("send_count" , $sendCount, FD_NUM)
			->where()
				->and( false, "no =" , 1, FD_NUM)
		->createSQL();
	$db->query($sql);
	
	// 最後の会員までメールが送信されていた場合に終了とする
	$sql = (new SqlString($db))
				->select()
					->field( "count(*)" )
					->from("dat_magazineTarget")
					->where()
						->and("magazine_no = ", $data["magazine_no"], FD_NUM)
						->and("state = ", 0, FD_NUM)
			->createSql();
	$count = $db->getOne( $sql);
	
	// 送信終了日時の更新 dat_magazine
	if ($count == 0) {
		$sql = (new SqlString($db))
			->update( "dat_magazine" )
				->set()
					->value("send_end_dt"   , "current_timestamp", FD_FUNCTION)
					->value("magazine_state", 2, FD_NUM)
					->value("upd_dt"        , "current_timestamp", FD_FUNCTION)
					->value("upd_no"        , BATCH_UPD_NO, FD_NUM)
				->where()
					->and("magazine_no =" , $data["magazine_no"], FD_NUM)
			->createSQL();
		$db->query($sql);
	}
	
	return;
}



?>
