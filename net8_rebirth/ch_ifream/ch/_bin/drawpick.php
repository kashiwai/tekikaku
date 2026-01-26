#!/usr/bin/php -q
<?php
/*
 * drawpick.php
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
 * 自動当選処理
 * 
 * 自動当選処理を行う
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
	$filepath = DIR_BIN . "drawpick.txt";
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

	// DBのインスタンス生成
	$db = new NetDB();

	// トランザクション開始
	$db->autoCommit(false);

	$now = date("Y-m-d H:i:s");

	// 抽選対象商品・申込データ(応募者数)を取得
	// ※この段階では会員状態に関わらず全ての申込数をカウントする
	$ssql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
		->select()
			->field("goods_no, count(*) as draw_count")
			->from("dat_request")
			->where()
				->and(false, "result = "  , 0, FD_NUM)
			->groupby("goods_no")
		->createSql("\n");
	$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
			->select()
				->field( "mg.goods_no, mg.draw_min_count, mg.win_count, IFNULL(dc.draw_count, 0) as draw_count" )
				->from("mst_goods mg")
				->from("left join (" . $ssql . ") dc on dc.goods_no = mg.goods_no")
				->where()
					->and( false, "mg.del_flg = "    , 0, FD_NUM)
					->and( false, "mg.draw_type = "  , 1, FD_NUM)
					->and( false, "mg.draw_dt <= "   , $now, FD_DATEEX )
					->and( false, "mg.draw_state = " , 0, FD_NUM)
		->createSql("\n");
	$rs = $db->query( $sql);

	while ($goodsRow = $rs->fetch(PDO::FETCH_ASSOC)) {

		$draw_count = (mb_strlen($goodsRow["draw_count"]) > 0) ? (int)$goodsRow["draw_count"] : 0;

		//抽選実施最小数を下回っている場合、応募者数が0の場合はポイントを返還処理後に抽選中止にする
		if ((int)$goodsRow["draw_min_count"] > $draw_count || $draw_count == 0) {
			//--- 抽選中止

			// ポイント返還(同一会員の複数応募は1つにまとめて返却)
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->select()
					->field("hd.member_no, hd.key_no, sum(hd.draw_point) as draw_point")
					->from("his_drawPoint hd")
					->from("inner join mst_member mm on mm.member_no = hd.member_no")
					->where()
						->and(false, "hd.key_no = ", $goodsRow["goods_no"], FD_NUM)
						->and(false, "hd.type = ", 2, FD_NUM)				// 2：減算
						->and(false, "hd.proc_cd = ", "52", FD_STR)			// 52：抽選応募
						->and(false, "mm.state = ", 1, FD_NUM)				// 1：本会員
						->and(false, "mm.black_flg = ", 0, FD_NUM)			// ブラックではない
					->groupby("hd.member_no")
				->createSql("\n");
			$rsDraw = $db->query($sql);
			if ($rsDraw->rowCount() > 0) {
				$point = new PlayPoint($db, false);
				while ($rowDraw = $rsDraw->fetch(PDO::FETCH_ASSOC)) {
					// 処理コード「12：抽選応募(中止による返還)」でポイントを返却する
					$point->addDrawPoint($rowDraw["member_no"], "12", $rowDraw["draw_point"], $rowDraw["key_no"]);
				}
			}
			
			// 応募データ更新
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->update("dat_request")
					->set()
						->value( "result", 9, FD_NUM)
						->value( "upd_no", BATCH_UPD_NO, FD_NUM)
						->value( "upd_dt", "current_timestamp", FD_FUNCTION)
					->where()
						->and(false, "goods_no =" , $goodsRow["goods_no"], FD_NUM)
				->createSQL("\n");
			$db->query($sql);

			//商品マスタ更新
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->update("mst_goods")
					->set()
						->value( false, "draw_state"   , 9, FD_NUM)
						->value( false, "upd_no"       , BATCH_UPD_NO, FD_NUM)
						->value( false, "upd_dt"       , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "goods_no =" , $goodsRow["goods_no"], FD_NUM)
				->createSQL("\n");
			$db->query($sql);

		}else{
			//--- 抽選実施

			// 当選確率算出用
			$rate = ceil(($goodsRow["win_count"] / $draw_count) * 100);

			//抽選対象者 応募者をmember_no毎にまとめる
			// ※退会者・ブラック・テスター会員は当選対象から除外
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->select()
					->field("dr.goods_no, dr.member_no, max(dr.request_dt) as request_dt")
					->field("count(dr.member_no) as request_count")
					->field("mm.state, mm.black_flg, mm.tester_flg, mm.nickname, mm.mail, mm.loss_count")
					->field("(" . $rate . " * count(dr.member_no) + mm.loss_count) as win_rate")
					->from("dat_request dr")
					->from("inner join mst_member mm on mm.member_no = dr.member_no and mm.black_flg = 0 and  mm.state = 1 and  mm.tester_flg = 0")
					->where()
						->and( false, "dr.goods_no = ", $goodsRow["goods_no"], FD_NUM)
						->and( false, "dr.result = "  , 0, FD_NUM)
					->groupby("dr.member_no")
					->orderby("request_dt asc")
				->createSql("\n");
			$picks = $db->getAll( $sql);

			// 有効会員数
			// 同一会員の複数応募は1として処理する為、会員数を判定に使用
			$active_count = count($picks);
			// 応募者数が当選数より少ない場合のリミット制御
			$limit = ((int)$goodsRow["win_count"] > $active_count) ? $active_count : (int)$goodsRow["win_count"];

			if ($limit > 0) {
				// 当選処理

				$weight = array_column($picks, "win_rate", "member_no");
				$sum = array_sum($weight);

				// 当選数がリミットに達するまで抽選
				$winCnt = 0;
				$win = array();
				while ($winCnt < $limit) {
					$rand = rand(1, $sum);
					foreach ($weight as $key => $w) {
						if (($sum -= $w) < $rand) {
							$winCnt++;
							$win[] = $key;
							// break;
							unset($weight[$key]);
						}
						if ($winCnt >= $limit) break;
					}
					if ($winCnt >= $limit) break;
				}

				// 応募データ更新（一旦該当レコードをハズレにする）
				$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->update("dat_request")
						->set()
							->value("result", 2, FD_NUM)
							->value("upd_no", BATCH_UPD_NO, FD_NUM)
							->value("upd_dt", "current_timestamp", FD_FUNCTION)
						->where()
							->and( false, "goods_no =" , $goodsRow["goods_no"], FD_NUM)
					->createSQL("\n");
				$db->query( $sql);
				
				// 当選データ更新
				$updstr = "case member_no";
				foreach($win as $mno){
					$updstr .= " when ". $mno ." then 1 ";
				}
				$updstr .= " else 2 end";
				$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->update( "dat_request dr, ( select MAX(seq) as seq from dat_request where goods_no = ". $goodsRow["goods_no"] ." group by member_no ) drs" )
						->set()
							->value("dr.result", $updstr, FD_FUNCTION)
							->value("dr.upd_no", BATCH_UPD_NO, FD_NUM)
							->value("dr.upd_dt", "current_timestamp", FD_FUNCTION)
						->where()
							->and( false, "dr.goods_no =" , $goodsRow["goods_no"], FD_NUM)
							->and( false, "dr.seq =" , "drs.seq", FD_FUNCTION)
					->createSQL("\n");
				$db->query( $sql);

				// ハズレ回数更新
				$ssql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->select()
						->field("member_no, count(*) as loss_count, min(result) as result")
						->from("dat_request")
						->where()
							->and("goods_no = ", $goodsRow["goods_no"], FD_NUM)
						->groupby("member_no")
					->createSql("\n");
				$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->update( "mst_member mm, (" . $ssql . ") as dr" )
						->set()
							->value("mm.loss_count", "case when dr.result = 2 then mm.loss_count + dr.loss_count when dr.result = 1 then 0 else mm.loss_count end", FD_FUNCTION)
							->value("mm.upd_no", BATCH_UPD_NO, FD_NUM)
							->value("mm.upd_dt", "current_timestamp", FD_FUNCTION)
						->where()
							->and(false, "mm.member_no =", "dr.member_no", FD_FUNCTION)
							->and(false, "mm.state = ", 1, FD_NUM)				// 1：本会員
							->and(false, "mm.black_flg = ", 0, FD_NUM)			// ブラックではない
					->createSQL("\n");
				$db->query($sql);

				// 連絡Box用商品名
				$goodsName = array();
				$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->select()
						->field("lng.lang, lng.goods_name")
					->from("mst_goods_lang lng")
					->where()
						->and( "lng.goods_no = ", $goodsRow["goods_no"], FD_NUM)
					->createSql("\n");
				$rsGozName = $db->query($sql);
				while ($row = $rsGozName->fetch(PDO::FETCH_ASSOC)) {
					$goodsName[$row["lang"]] = $row["goods_name"];
				}
				unset($rsGozName);

				// dat_win 登録
				$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->select()
						->field( "dr.goods_no, dr.member_no, dr.seq")
						//発送先自動埋め用データ
						->field( "da.syll, da.name, da.postal, da.address1, da.address2, da.address3, da.address4, da.tel, da.use_flg")
						->from("dat_request dr")
						//発送先自動埋め
						->from("left join dat_address da on da.member_no = dr.member_no and da.use_flg = 1 and da.del_flg = 0")
						->from("left join mst_goods mg on mg.goods_no = dr.goods_no")
						->where()
							->and( "dr.goods_no = ", $goodsRow["goods_no"], FD_NUM)
							->and( "dr.result = "  , 1, FD_NUM)
					->createSql("\n");
				$rsWin = $db->query($sql);

				$insstr = array();
				$ins_contact = array();
				while ($row = $rsWin->fetch(PDO::FETCH_ASSOC)) {
					$insstr[] = "("
							. $db->conv_sql( $goodsRow["goods_no"], FD_NUM)
						.",". $db->conv_sql( $row["member_no"], FD_NUM)
						.",". $db->conv_sql( $row["seq"], FD_NUM)
						.",". $db->conv_sql( ($row["use_flg"] == 1) ? 1 : 0, FD_NUM)
						.",". $db->conv_sql( BATCH_UPD_NO, FD_NUM)
						.",". $db->conv_sql( "current_timestamp", FD_FUNCTION)
						.",". $db->conv_sql( BATCH_UPD_NO, FD_NUM)
						.",". $db->conv_sql( "current_timestamp", FD_FUNCTION)
						//発送先
						.",". $db->conv_sql( $row["syll"], FD_STR)
						.",". $db->conv_sql( $row["name"], FD_STR)
						.",". $db->conv_sql( $row["postal"], FD_STR)
						.",". $db->conv_sql( $row["address1"], FD_STR)
						.",". $db->conv_sql( $row["address2"], FD_STR)
						.",". $db->conv_sql( $row["address3"], FD_STR)
						.",". $db->conv_sql( $row["address4"], FD_STR)
						.",". $db->conv_sql( $row["tel"], FD_STR)
					.")";
					//連絡Box用
					$ins_contact[] = $row;
				}
				
				if (!empty($insstr)) {
					$sql = "insert into dat_win (goods_no, member_no, seq, state, add_no, add_dt, upd_no, upd_dt, "
						 . "syll, name, postal, address1, address2, address3, address4, tel"
						 . ") values " . implode(',', $insstr);
					$db->query($sql);
				}

				//連絡Box登録
				if (!empty($ins_contact)) {
					$contact_message = array();
					foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
						$goods_name = ((mb_strlen($goodsName[$k]) > 0) ? $goodsName[$k] : $goodsName[DEFAULT_LANG]);
						$contact_message[$k] = str_replace( "%ITEM_NAME%", $goods_name, $v["02"]);
					}
					$contact = new ContactBox( $db, false);
					$contact->addRecords( $ins_contact, "02", $goodsRow["goods_no"], $contact_message, "", BATCH_UPD_NO);
				}

			}else{
				// 有効な応募が無かった場合
				
				// 申込データを全てハズレで更新
				// ※申込後にブラックになったり退会したりしたデータの後始末
				$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
					->update("dat_request")
						->set()
							->value("result", 2, FD_NUM)
							->value("upd_no", BATCH_UPD_NO, FD_NUM)
							->value("upd_dt", "current_timestamp", FD_FUNCTION)
						->where()
							->and(false, "goods_no =", $goodsRow["goods_no"], FD_NUM)
					->createSQL("\n");
				$db->query($sql);
			}

			//商品マスタ更新
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->update("mst_goods")
					->set()
						->value( false, "draw_state"   , 1, FD_NUM)
						->value( false, "upd_no"       , BATCH_UPD_NO, FD_NUM)
						->value( false, "upd_dt"       , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "goods_no =" , $goodsRow["goods_no"], FD_NUM)
				->createSQL();
			$db->query($sql);
		}
	}
	unset($rs);
	// コミット(トランザクション終了)
	$db->autoCommit(true);

	$db->disconnect();	// DB解放

	// 多重起動回避 処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}

?>
