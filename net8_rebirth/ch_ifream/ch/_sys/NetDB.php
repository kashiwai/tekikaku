<?php
/*
 * NetDB.php
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
 * DB処理拡張クラス
 *
 * DB処理を基準に拡張処理を行う
 *
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since	 2016/08/04 初版作成 岡本静子
 * @info
 */

class NetDB extends SmartDB {

	/**
	 * コンストラクタ
	 * @access	public
	 * @param	なし
	 * @return	インスタンス
	 */
	public function __construct() {
		// DB接続
		parent::__construct(DB_DSN);
		$this->query("SET NAMES utf8mb4");
		$this->query("SET CHARACTER SET utf8mb4");
	}

	/**
	 * 管理者チェック
	 * @access	public
	 * @param	string		$id		ID
	 * @param	string		$pass	パスワード
	 * @return	boolean		true:チェックOK / false:チェックNG
	 */
	public function checkAdmin($id, $pass) {
		$ret = false;

		$sql = "select admin_pass from mst_admin" . "\n"
			 . " where admin_id = " . $this->conv_sql($id, FD_STR)
			 . " and del_flg = " . $this->conv_sql("0", FD_NUM);
		$dbPass = $this->getOne($sql);
		if (mb_strlen($dbPass) > 0 && $dbPass == $pass) $ret = true;
		return $ret;
	}

	/**
	 * ユーザチェック
	 * @access	public
	 * @param	string		$mail	メールアドレス
	 * @param	string		$pass	パスワード
	 * @return	boolean		true:チェックOK / false:チェックNG
	 */
	public function checkUser($mail, $pass) {
		$ret = false;

		$sql = "select pass from mst_member" . "\n"
			 . " where mail = " . $this->conv_sql($mail, FD_STR)
			 . " and pass = "   . $this->conv_sql($pass, FD_STR)
			 . " and state = "  . $this->conv_sql("1", FD_NUM) . "\n";

		$dbPass = $this->getOne($sql);
		if (mb_strlen($dbPass) > 0 && $dbPass == $pass) $ret = true;
		return $ret;
	}

	/**
	 * ユーザチェック（ポイント系を返却する）
	 * @access	public
	 * @param	string		$mail	メールアドレス
	 * @param	string		$pass	パスワード
	 * @return	array		レコード（ポイント系）
	 */
	public function checkUserReturnPoint($mail, $pass) {
		$ret = array();

		$sql = "select pass, point, draw_point from mst_member" . "\n"
			 . " where mail = " . $this->conv_sql($mail, FD_STR)
			 . " and pass = "   . $this->conv_sql($pass, FD_STR)
			 . " and state = "  . $this->conv_sql("1", FD_NUM) . "\n";
		$row = $this->getRow($sql, PDO::FETCH_ASSOC);

		if( !empty($row) ){
			if (mb_strlen($row["pass"]) > 0 && $row["pass"] == $pass) {
				$ret["point"] = $row["point"];
				$ret["draw_point"] = $row["draw_point"];
			}
		}
		return $ret;
	}

	/**
	 * 対象者取得(メルマガ、クーポン)
	 * @access	private
	 * @param	array	$post		取得条件
	 * @param	boolean	$trgMag		取得対象がメルマガか否か
	 * @return	array				対象会員データ
	 */
	function getMemberRows($post, $trgMag = true) {

		//-- プレイ条件生成
		$playSql = "";
		if ((mb_strlen($post["COND_PLAY_COUNT_FROM"]) + mb_strlen($post["COND_PLAY_COUNT_TO"])
			 + mb_strlen($post["COND_PLAY_DT_FROM"]) + mb_strlen($post["COND_PLAY_DT_TO"])) > 0) {
			$fromDt = ((mb_strlen($post["COND_PLAY_DT_FROM"]) > 0) ? GetRefTimeStart($post["COND_PLAY_DT_FROM"]) : "");
			$toDt = ((mb_strlen($post["COND_PLAY_DT_TO"]) > 0) ? GetRefTimeEnd($post["COND_PLAY_DT_TO"]) : "");

			$playSql = (new SqlString($this))
						->select()
							->field("hpl.member_no")
						->from("his_play hpl")
						->where()
							->and("hpl.in_credit >= "      , "1", FD_NUM)
							->and(SQL_CUT, "hpl.start_dt <= ", $toDt, FD_DATE)
							->and(SQL_CUT, "hpl.end_dt >= "  , $fromDt, FD_DATE)
						->groupby("hpl.member_no")
						->having()
							->and("count(*)", "between", $post["COND_PLAY_COUNT_FROM"], FD_NUM, $post["COND_PLAY_COUNT_TO"], FD_NUM)
						->createSQL("\n");
		}

		//-- 購入条件生成
		$purchaseSql = "";
		$purchaseType = (empty($post["COND_PURCHASE_TYPE"]) ? array() : (is_array($post["COND_PURCHASE_TYPE"]) ? $post["COND_PURCHASE_TYPE"] : explode(",", $post["COND_PURCHASE_TYPE"])));

		if ((count($purchaseType)
			 + mb_strlen($post["COND_PURCHASE_COUNT_FROM"]) + mb_strlen($post["COND_PURCHASE_COUNT_TO"])
			 + mb_strlen($post["COND_PURCHASE_AMOUNT_FROM"]) + mb_strlen($post["COND_PURCHASE_AMOUNT_TO"])
			 + mb_strlen($post["COND_PURCHASE_DT_FROM"])+ mb_strlen($post["COND_PURCHASE_DT_TO"])) > 0) {

			$fromDt = ((mb_strlen($post["COND_PURCHASE_DT_FROM"]) > 0) ? GetRefTimeStart($post["COND_PURCHASE_DT_FROM"]) : "");
			$toDt = ((mb_strlen($post["COND_PURCHASE_DT_TO"]) > 0) ? GetRefTimeEnd($post["COND_PURCHASE_DT_TO"]) : "");

			$purchase_sql = (new SqlString($this))
						->select()
							->field("hpu.member_no")
						->from("his_purchase hpu")
						->where()
							->and("hpu.result_status = ", 1, FD_NUM)
							->and("hpu.purchase_dt", "between", $fromDt, FD_DATEEX, $toDt, FD_DATEEX)
						->groupby("hpu.member_no")
						->having()
							->and("count(*)", "between", $post["COND_PURCHASE_COUNT_FROM"], FD_NUM, $post["COND_PURCHASE_COUNT_TO"], FD_NUM)
							->and("sum(amount)", "between", $post["COND_PURCHASE_AMOUNT_FROM"], FD_NUM, $post["COND_PURCHASE_AMOUNT_TO"], FD_NUM);
			if (count($purchaseType) > 0) {
				$purchase_sql
						->where()
							->and("hpu.purchase_type in ", $purchaseType, FD_NUM);
			}
			$purchaseSql = $purchase_sql->createSQL("\n");
		}
		// 検索用日時
		$joinFromDt = ((mb_strlen($post["COND_JOIN_FROM"]) > 0) ? GetRefTimeStart($post["COND_JOIN_FROM"]) : "");
		$joinToDt = ((mb_strlen($post["COND_JOIN_TO"]) > 0) ? GetRefTimeEnd($post["COND_JOIN_TO"]) : "");
		$loginFromDt = ((mb_strlen($post["COND_LOGIN_FROM"]) > 0) ? GetRefTimeStart($post["COND_LOGIN_FROM"]) : "");
		$loginToDt = ((mb_strlen($post["COND_LOGIN_TO"]) > 0) ? GetRefTimeEnd($post["COND_LOGIN_TO"]) : "");
		//SQL実行
		$sqls = new SqlString();
		$sqls->setAutoConvert( [$this,"conv_sql"] )
				->select()
				->field( "mm.*" )
				->from("mst_member mm")
				->where()
					// 固定条件
					->and("mm.state = "     , 1, FD_NUM)
					->and("mm.tester_flg = ", 0, FD_NUM)
					->and("mm.black_flg = " , 0, FD_NUM)
					// メルマガ時の条件
					->and(SQL_CUT, "mm.mail_error_count < ", (($trgMag) ? MAGAZINE_MAIL_ERR_COUNT : ""), FD_NUM)	// 送信エラー件数
					->and(SQL_CUT, "mm.mail_magazine = "   , (($trgMag && $post["SEND_TARGET"] == "1") ? $post["SEND_TARGET"] : ""), FD_NUM)	// メルマガ購読会員
					// 会員情報
					->and(SQL_CUT, "mm.member_no = "      , $post["COND_MEMBER_NO"], FD_NUM)		// 会員NO
					->and(SQL_CUT, "mm.sex = "            , $post["COND_SEX"], FD_NUM)				// 性別
					->and(SQL_CUT, "MONTH(mm.birthday) = ", $post["COND_BMONTH"], FD_NUM)			// 誕生月
					->and("mm.point"     , "between"      , $post["COND_POINT_FROM"], FD_NUM, $post["COND_POINT_TO"], FD_NUM)			// プレイポイント
					->and("mm.draw_point", "between"      , $post["COND_DRAW_POINT_FROM"], FD_NUM, $post["COND_DRAW_POINT_TO"], FD_NUM)	// 抽選ポイント
					->and(SQL_CUT, "mm.join_dt >= "       , $joinFromDt, FD_DATEEX)		// 本登録From
					->and(SQL_CUT, "mm.join_dt <= "       , $joinToDt, FD_DATEEX)		// 本登録To
					->and(SQL_CUT, "mm.login_dt >= "      , $loginFromDt, FD_DATEEX)	// 最終ログインFrom
					->and(SQL_CUT, "mm.login_dt <= "      , $loginToDt, FD_DATEEX);		// 最終ログインTo
		// プレイ条件
		if(mb_strlen($playSql) > 0) $sqls->where()->subQuery("mm.member_no", $playSql);

		// 購入条件
		if(mb_strlen($purchaseSql) > 0) $sqls->where()->subQuery("mm.member_no", $purchaseSql);

		$sql = $sqls->createSql("\n");

		$rows = $this->getAll( $sql);

		return $rows;
	}

	/**
	 * 実機一覧配列取得
	 * @param	なし
	 * @return	array	key = machine_no 、value = [machine_no] モデル名
	**/
	function getMachines() {
		$sql = "select dm.machine_no, mm.model_name from dat_machine dm" . "\n"
			 . " left join mst_model mm on mm.model_no = dm.model_no"
			 . " where dm.del_flg = " . $this->conv_sql(0, FD_NUM);
		$rs = $this->query($sql);

		$ret = array();
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$ret[$row["machine_no"]] = "[" . sprintf('%0' . FORMAT_NO_DIGIT . 'd', $row["machine_no"]) . "] " . $row["model_name"];
		}
		unset($rs);
		return $ret;
	}

	/**
	 * メーカー一覧配列取得
	 * @param	int		$dispflg		表示フラグ (0:表示しない / 1:表示する)
	 * @param	bool	$withclass		パチ・スロ判定付与 (true:付与する / false:付与しない)
	 * @return	array	key = maker_no 、value = [maker_name]
	 * 					$withclassがtrueの場合は下記
	 *					key = [maker_no]["value"] 、value = [maker_name]
	 *					key = [maker_no]["class"] 、value = ["pachi " or "slot " or ""]
	 */
	function getMakerList($dispflg = "", $withclass = false) {
		$sql = (new SqlString())->setAutoConvert([$this,"conv_sql"])
				->select()
					->field("maker_no, maker_name, maker_roman, pachi_flg, slot_flg")
					->from("mst_maker")
					->where()
						->and(false, "del_flg = ", 0, FD_NUM)
						->and(true, "disp_flg = ", $dispflg, FD_NUM)
					->orderby("maker_no asc")
				->createSQL();
		$rs = $this->query($sql);

		$ret = array();
		$retWith = array();
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$ret[$row['maker_no']] = $row['maker_name'];
			$retWith[$row['maker_no']]["value"] = $row['maker_name'];
			$retWith[$row['maker_no']]["class"] = "";

			if ($row['pachi_flg'] == "1" && $row['slot_flg']  == "0") {
				$retWith[$row['maker_no']]["class"] = "pachi ";
			}
			if ($row['pachi_flg'] == "0" && $row['slot_flg']  == "1") {
				$retWith[$row['maker_no']]["class"] = "slot ";
			}
		}
		unset($rs);
		if ($withclass) {
			return $retWith;
		} else {
			return $ret;
		}
	}

	/**
	 * タイプ一覧配列取得
	 * @param	bool	$withclass		パチ・スロ判定付与 (true:付与する / false:付与しない)
	 * @return	array	key = type_no 、value = [type_name]
	 * 					$withclassがtrueの場合は下記
	 *					key = [type_no]["value"] 、value = [type_name]
	 *					key = [type_no]["class"] 、value = ["pachi" or "slot" or ""]
	 */
	function getTypeList($withclass = false){
		$sql = (new SqlString())->setAutoConvert([$this,"conv_sql"])
				->select()
					->field("type_no, type_name, type_roman, category")
					->from("mst_type")
					->where()
						->and(false, "del_flg = ", 0, FD_NUM)
					->orderby("sort_no asc")
				->createSQL();
		$rs = $this->query($sql);

		$ret = array();
		$retWith = array();
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$ret[$row['type_no']] = $row['type_name'];
			$retWith[$row['type_no']]["value"] = $row['type_name'];
			$retWith[$row['type_no']]["class"] = "";

			if ($row['category'] == 1) $retWith[$row['type_no']]["class"] = "pachi";
			if ($row['category'] == 2) $retWith[$row['type_no']]["class"] = "slot";
		}
		unset($rs);
		if ($withclass) {
			return $retWith;
		} else {
			return $ret;
		}
	}

	/**
	 * 号機一覧配列取得
	 * @param	なし
	 * @return	array	key = type_no 、value = [type_name]
	 */
	function getUnitList() {
		$sql = (new SqlString())->setAutoConvert([$this,"conv_sql"])
				->select()
					->field("unit_no, unit_name, unit_roman")
					->from("mst_unit")
					->where()
						->and(false, "del_flg = ", 0, FD_NUM)
					->orderby("sort_no asc")
				->createSQL();
		$rs = $this->query($sql);

		$ret = array();
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$ret[$row['unit_no']] = $row['unit_name'];
		}
		unset($rs);
		return $ret;
	}

	/**
	 * 表示していない連絡Box件数取得(Max：99)
	 * @param	int		$memberNo		表示フラグ (0:表示しない / 1:表示する)
	 * @return	int	key = type_no 、value = [type_name]
	 */
	function getNotdispContactBox($memberNo) {
		$sql = (new SqlString())->setAutoConvert([$this,"conv_sql"])
				->select()
					->field("count(*)")
					->from("dat_contactBox")
					->where()
						->and("member_no = ", $memberNo, FD_NUM)
						->and("dsp_flg = "  , "0", FD_NUM)
				->createSQL("\n");
		$cnt = (int)$this->getOne($sql);
		if ($cnt >= 100) $cnt = 99;	// 100以上は99にする
		return $cnt;
	}

	/**
	 * システム設定値取得
	 * @param	string		$key		設定Key
	 * @return	設定値
	 */
	function getSystemSetting($key) {
		$sql = (new SqlString())->setAutoConvert([$this,"conv_sql"])
				->select()
					->field("setting_format, setting_val")
					->from("mst_setting")
					->where()
						->and("setting_key = ", $key, FD_STR)
						->and("del_flg = "  , "0", FD_NUM)
				->createSQL("\n");
		$row = $this->getRow($sql, PDO::FETCH_ASSOC);
		if (is_null($row) || empty($row)) return "";
		switch ($row['setting_format']) {
			case '2':
				$val = (int)$row['setting_val'];
				break;
			case '3':
				$val = (float)$row['setting_val'];
				break;
			default:
				$val = $row['setting_val'];
				break;
		}
		return $val;
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
