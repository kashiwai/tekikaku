<?php
/*
 * goods_drawpick.php
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
 * 抽選選択画面表示
 * 
 * 抽選選択画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/01/30 初版作成 片岡 充
 */

// インクルード
require_once('../../_etc/require_files_admin.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));			// テンプレートHTMLプレフィックス

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
	try {
		// 管理系表示コントロールのインスタンス生成
		$template = new TemplateAdmin();
		
		// データ取得
		getData($_GET, array("M"));
		
		// 実処理(現状は全サブウィンドウ ※変更する場合はフラグを直すこと)
		$mainWin = true;
		switch ($_GET["M"]) {
			case "regist":			// 登録処理
				$mainWin = false;
				RegistData($template);
				break;
				
			case "end":				// 完了画面
				$mainWin = false;
				DispComplete($template);
				break;
				
			default:				// 一覧画面
				$mainWin = false;
				DispList($template);
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage(), $mainWin);
	}
}

/**
 * 一覧画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispList($template) {
	// データ取得
	getData($_GET , array("NO", "ACT"));
	
	// 商品データ
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("mg.goods_no, mg.goods_cd, mg.draw_type, mg.win_count, mg.draw_min_count")
			->field("lng.goods_name")
			->from("mst_goods mg")
			->join("inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
			->where()
				->and(false, "mg.goods_no = ", $_GET["NO"], FD_NUM )
				->and(false, "mg.del_flg = " , 0, FD_NUM )
		->createSql();
	$gdrow = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
	if ($gdrow == null) {
		$template->dispProcError($template->message("A0003"), false);
		return;
	}
	
	// 応募データ
	$rsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("dr.member_no, count(dr.member_no) as cnt, MAX(dr.request_dt) as request_dt")
			->field("mm.nickname, mm.black_flg, mm.tester_flg, mm.state, mm.loss_count")
			->from("dat_request dr")
			->from("inner join mst_member mm on mm.member_no = dr.member_no")
			->where()
				->and(false, "dr.goods_no = ", $_GET["NO"], FD_NUM)
			->groupby("dr.member_no")
		->createSql("\n");
	$rs = $template->DB->query($rsql);

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("ERRMSG"      , $_GET["ACT"] == "err");
	//
	$template->assign("NO"             , $gdrow["goods_no"], true);
	$template->assign("GOODS_NO_PAD"   , $template->formatNoBasic($gdrow["goods_no"]), true);
	$template->assign("GOODS_CD"       , $gdrow["goods_cd"], true);
	$template->assign("GOODS_NAME"     , $gdrow["goods_name"], true);
	$template->assign("WIN_COUNT"      , $gdrow["win_count"], true);
	$template->assign("WIN_COUNT_FMT"  , number_formatEx($gdrow["win_count"]), true);
	$template->assign("DRAW_WIN_COUNT" , number_formatEx($gdrow["draw_min_count"]), true);

	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->if_enable("IS_BLACK"    , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"   , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED"  , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["state"] == 9);
		$template->if_enable("PICK_TARGET" , $row["black_flg"] == 0 && $row["state"] == 1);
		$template->if_enable("UN_PICK"     , $row["black_flg"] == 1 || $row["state"] != 1);
		
		$template->assign("MEMBER_NO"      , $row["member_no"], true);
		$template->assign("MEMBER_NO_PAD"  , $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"       , $row["nickname"], true);
		$template->assign("REQUEST_DT"     , format_datetime($row["request_dt"]), true);
		$template->assign("DRAW_COUNT"     , $row["cnt"], true);
		$template->assign("LOST_COUNT"     , number_formatEx($row["loss_count"]), true);
		
		$template->loop_next();
	}
	$template->loop_end("LIST");

	// 表示
	$template->flush();
}


/**
 * 登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function RegistData($template) {
	// データ取得
	getData($_GET  , array("ACT", "NO"));
	getData($_POST , array("NO"));

	// トランザクション開始
	$template->DB->autoCommit(false);
	
	if ($_GET["ACT"] == "del") {
		// 抽選中止
		$mode = "del";

		// ポイント返還(同一会員の複数応募は1つにまとめて返却)
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
				->select()
				->field("hd.member_no, hd.key_no, sum(hd.draw_point) as draw_point")
				->from("his_drawPoint hd")
				->from("inner join mst_member mm on mm.member_no = hd.member_no")
				->where()
					->and(false, "hd.key_no = ", $_GET["NO"], FD_NUM)
					->and(false, "hd.type = ", 2, FD_NUM)				// 2：減算
					->and(false, "hd.proc_cd = ", "52", FD_STR)			// 52：抽選応募
					->and(false, "mm.state = ", 1, FD_NUM)				// 1：本会員
					->and(false, "mm.black_flg = ", 0, FD_NUM)			// ブラックではない
				->groupby("hd.member_no")
			->createSql("\n");
		$rs = $template->DB->query($sql);
		if ($rs->rowCount() > 0) {
			$point = new PlayPoint($template->DB, false);
			while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
				// 処理コード「12：抽選応募(中止による返還)」でポイントを返却する
				$point->addDrawPoint($row["member_no"], "12", $row["draw_point"], $row["key_no"]);
			}
		}

		// 応募データ更新
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update("dat_request")
				->set()
					->value( "result", 9, FD_NUM)
					->value( "upd_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "upd_dt", "current_timestamp", FD_FUNCTION)
				->where()
					->and(false, "goods_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);

		// mst_goods 更新
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update("mst_goods")
				->set()
					->value( "draw_state", 9, FD_NUM)
					->value( "upd_no"    , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "upd_dt"    , "current_timestamp", FD_FUNCTION)
				->where()
					->and(false, "goods_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);

	}else{
		// 抽選確定
		$mode = "draw";

		if (empty($_POST["PICK"])) {
			header("Location: " . URL_ADMIN . $template->Self . "?NO=" . $_POST["NO"] . "&ACT=err");
			return;
		}

		// mst_goods 更新
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_goods" )
				->set()
					->value( "draw_state", 1, FD_NUM)
					->value( "upd_no"    , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "upd_dt"    , "current_timestamp", FD_FUNCTION)
				->where()
					->and( false, "goods_no =" , $_POST["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);

		// 当選テーブルの更新
		if( count($_POST["PICK"]) > 0){
			// 一旦該当レコードをハズレにする
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update("dat_request")
					->set()
						->value("result", 2, FD_NUM)
						->value("upd_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("upd_dt", "current_timestamp", FD_FUNCTION)
					->where()
						->and(false, "goods_no =", $_POST["NO"], FD_NUM)
				->createSQL("\n");
			$template->DB->query($sql);
			
			// 当選処理
			$updstr = "case dr.member_no";
			foreach( $_POST["PICK"] as $v){
				$updstr .= " when ". $v ." then 1 ";
			}
			$updstr .= " else 2 end";
			$ssql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
				->select()
					->field("MAX(seq) as seq")
					->from("dat_request")
					->where()
						->and("goods_no = ", $_POST["NO"], FD_NUM)
					->groupby("member_no")
				->createSql("\n");
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_request dr, mst_member mm, (" . $ssql . ") drs" )
					->set()
						->value( "dr.result", $updstr, FD_FUNCTION)
						->value( "dr.upd_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "dr.upd_dt", "current_timestamp", FD_FUNCTION)
					->where()
						->and(false, "dr.goods_no =" , $_POST["NO"], FD_NUM)
						->and(false, "dr.seq =" , "drs.seq", FD_FUNCTION)
						->and(false, "dr.member_no = ", "mm.member_no", FD_FUNCTION)
						->and(false, "mm.state = ", 1, FD_NUM)				// 1：本会員
						->and(false, "mm.black_flg = ", 0, FD_NUM)			// ブラックではない
				->createSQL("\n");
			$template->DB->query( $sql);
			
			// ハズレ回数更新
			$ssql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
				->select()
					->field("member_no, count(*) as loss_count, min(result) as result")
					->from("dat_request")
					->where()
						->and("goods_no = ", $_POST["NO"], FD_NUM)
					->groupby("member_no")
				->createSql("\n");
			$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
				->update( "mst_member mm, (" . $ssql . ") as dr" )
					->set()
						->value("mm.loss_count", "case when dr.result = 2 then mm.loss_count + dr.loss_count when dr.result = 1 then 0 else mm.loss_count end", FD_FUNCTION)
						->value("mm.upd_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("mm.upd_dt", "current_timestamp", FD_FUNCTION)
					->where()
						->and(false, "mm.member_no =", "dr.member_no", FD_FUNCTION)
						->and(false, "mm.state = ", 1, FD_NUM)				// 1：本会員
						->and(false, "mm.black_flg = ", 0, FD_NUM)			// ブラックではない
				->createSQL("\n");
			$template->DB->query($sql);

			// 連絡Box用商品名
			$goodsName = array();
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("lng.lang, lng.goods_name")
				->from("mst_goods_lang lng")
				->where()
					->and( "lng.goods_no = ", $_POST["NO"], FD_NUM)
				->createSql("\n");
			$rs = $template->DB->query($sql);
			while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
				$goodsName[$row["lang"]] = $row["goods_name"];
			}
			unset($rs);

			// dat_win 登録
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "dr.goods_no, dr.member_no, dr.seq")
				//発送先自動埋め用データ
				->field( "da.syll, da.name, da.postal, da.address1, da.address2, da.address3, da.address4, da.tel, da.use_flg")
				->from("dat_request dr")
				//発送先自動埋め
				->from("left join dat_address da on da.member_no = dr.member_no and da.use_flg = 1 and da.del_flg = 0")
				->where()
					->and( "dr.goods_no = ", $_POST["NO"], FD_NUM)
					->and( "dr.result = "  , 1, FD_NUM)
				->createSql("\n");
			$rs = $template->DB->query($sql);
			
			$insstr = array();
			$ins_contact = array();
			while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
				$insstr[] = "("
						. $template->DB->conv_sql( $row["goods_no"], FD_NUM)
					.",". $template->DB->conv_sql( $row["member_no"], FD_NUM)
					.",". $template->DB->conv_sql( $row["seq"], FD_NUM)
					.",". $template->DB->conv_sql( ($row["use_flg"] == 1) ? 1 : 0, FD_NUM)
					.",". $template->DB->conv_sql( $template->Session->AdminInfo["admin_no"], FD_NUM)
					.",". $template->DB->conv_sql( "current_timestamp", FD_FUNCTION)
					.",". $template->DB->conv_sql( $template->Session->AdminInfo["admin_no"], FD_NUM)
					.",". $template->DB->conv_sql( "current_timestamp", FD_FUNCTION)
					//発送先
					.",". $template->DB->conv_sql( $row["syll"], FD_STR)
					.",". $template->DB->conv_sql( $row["name"], FD_STR)
					.",". $template->DB->conv_sql( $row["postal"], FD_STR)
					.",". $template->DB->conv_sql( $row["address1"], FD_STR)
					.",". $template->DB->conv_sql( $row["address2"], FD_STR)
					.",". $template->DB->conv_sql( $row["address3"], FD_STR)
					.",". $template->DB->conv_sql( $row["address4"], FD_STR)
					.",". $template->DB->conv_sql( $row["tel"], FD_STR)
				.")";
				//連絡Box用
				$ins_contact[] = $row;
			}
			$sql = "insert into dat_win (goods_no, member_no, seq, state, add_no, add_dt, upd_no, upd_dt, "
				 . "syll, name, postal, address1, address2, address3, address4, tel"
				 . ") values " . implode(',', $insstr);
			$template->DB->query( $sql);
			
			//連絡Box登録
			$contact_message = array();
			foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
				$goods_name = ((mb_strlen($goodsName[$k]) > 0) ? $goodsName[$k] : $goodsName[FOLDER_LANG]);
				$contact_message[$k] = str_replace( "%ITEM_NAME%", $goods_name, $v["02"]);
			}
			$contact = new ContactBox( $template->DB, false);
			$contact->addRecords( $ins_contact, "02", $_POST["NO"], $contact_message, "", $template->Session->AdminInfo["admin_no"]);
			
		}
	}
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	// 完了画面表示
	header("Location: " . URL_ADMIN . $template->Self . "?M=end&ACT=" . $mode);
}

/**
 * 完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComplete($template) {
	// データ取得
	getData($_GET , array("ACT"));
	
	switch ($_GET["ACT"]) {
		case "del":
			// 抽選中止
			$title = $template->message("A2292");
			$msg = $template->message("A2293");
			break;
		default:
			// 抽選確定
			$title = $template->message("A2290");
			$msg = $template->message("A2291");
			break;
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
}

?>
