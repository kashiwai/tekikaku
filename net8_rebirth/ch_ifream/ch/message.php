<?php
/*
 * message.php
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
 * 連絡BOX画面表示
 * 
 * 連絡BOX画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/08 初版作成 片岡 充
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));	// テンプレートHTMLプレフィックス

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
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		$template->checkSessionUser(true, true);
		
		// データ取得
		getData($_GET, array("M"));

		// 実処理
		DispList($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
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
	getData($_GET , array("P"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;

	// 1頁目表示時に表示フラグを更新
	if ($_GET["P"] == 1) {
		$template->DB->autoCommit(false);	// トランザクション開始
		// 表示更新
		$sql = (new SqlString())
				->setAutoConvert([$template->DB,"conv_sql"])
				->update("dat_contactBox")
					->set()
						->value("dsp_flg", "1", FD_NUM)
						->value("dsp_dt" , "current_timestamp", FD_FUNCTION)
					->where()
						->and("member_no = ",$template->Session->UserInfo["member_no"], FD_NUM)
						->and("dsp_flg = ", "0", FD_NUM)
				->createSQL("\n");
		$template->DB->exec($sql);
		$template->DB->autoCommit(true);	// コミット(トランザクション終了)
		// 表示していない連絡Box件数設定
		$template->SetNotdispContactBox();
	}

	//----------------------------------------------------------
	$sqls = new SqlString();
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("dat_contactBox dcb")
			->from("inner join dat_contactBox_lang dcb_lang on dcb_lang.member_no = dcb.member_no and dcb_lang.seq = dcb.seq and dcb_lang.lang = ". $template->DB->conv_sql( FOLDER_LANG, FD_STR))
			->where()
				->and(false, "dcb.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
		->createSQL();
	
	// カウント取得
	$allrows = $template->DB->getOne($count_sql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / PLAY_HISTORY_VIEW);		// 総ページ数	PLAY_HISTORY_VIEW
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// データ取得
	$row_sql = $sqls->resetField()
			->field("dcb.seq, dcb.key_no, dcb.contact_type, dcb.delivery_dt")
			->field("dcb_lang.contents")
			->page( $_GET["P"], PLAY_HISTORY_VIEW)
			->orderby( "seq desc" )
		->createSql("\n");
	$rs = $template->DB->query($row_sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("NONE",  $allrows < 1);
	$template->if_enable("LISTS", $allrows > 0);
	
	$template->assign("PAGING" , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
	$template->assign("ALLROW", (string)$allrows, true);	// 総件数
	$template->assign("P", (string)$_GET["P"], true);		// 現在ページ番号
	$template->assign("ALLP", (string)$allpage, true);		// 総ページ数
	
	if( $allrows > 0){
		$template->loop_start("LIST");
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			// プレイ履歴リンク
			$isPlayList = ($row["contact_type"] == "03");		// 自動精算
			// プレイポイント履歴リンク
			$isPoint = ($row["contact_type"] == "01"			// クーポン
						 || $row["contact_type"] == "04"		// 招待ポイント
						 || $row["contact_type"] == "05"		// 有効期限切れ
						 || $row["contact_type"] == "06"		// 有効期限通知
						 || $row["contact_type"] == "10");		// 登録特典
			// 配送先確認リンク
			$isShipping = ($row["contact_type"] == "02");
			// 抽選ポイント履歴リンク
			$isDraw =  ($row["contact_type"] == "07"			// ギフト送信
						 || $row["contact_type"] == "08");		// ギフト受信

			$template->if_enable("LINK_PLAYLIST", $isPlayList);	// プレイ履歴
			$template->if_enable("LINK_POINT"   , $isPoint);	// プレイポイント履歴
			$template->if_enable("LINK_SHIPPING", $isShipping);	// 発送先確認画面
			$template->if_enable("LINK_DRAW"    , $isDraw);		// 抽選ポイント履歴
			$template->if_enable("NOLINK"       , !($isPlayList || $isPoint || $isShipping || $isDraw));	// リンク無し
			// モーダル用追加
			if($row["contact_type"] == "01" || $row["contact_type"] == "09"){	// クーポン 若しくは メルマガ
				$msqls = (new SqlString())
						->setAutoConvert([$template->DB,"conv_sql"])
						->select()
							->field("contents");
				if ($row["contact_type"] == "01") {		// クーポン
					$isCoupon = true;
					$msqls->from("dat_coupon_lang")
						->where()
						->and("coupon_no = ", $row["key_no"], FD_NUM)
						->and("lang = "     , FOLDER_LANG, FD_STR);
				} else {	// メルマガ
					$isCoupon = false;
					$msqls->from("dat_magazine")
						->where()
						->and("magazine_no = ", $row["key_no"], FD_NUM);
				}
				$msql = $msqls->createSQL("\n");
				$contents = $template->DB->getOne($msql);
				if (!$isCoupon) {	// メルマガ時は{%NAME%}→ニックネーム
					$contents = str_replace("{%NAME%}", $template->Session->UserInfo["nickname"], $contents);
				}
				$template->assign("MODAL_CONTENTS", $contents, true, true);
				$template->if_enable("LINK_MODAL" , mb_strlen(trim($contents)) > 0);
				$template->if_enable("IS_COUPON"  , $isCoupon);
				$template->if_enable("IS_MAGAZINE", !$isCoupon);
			}else{
				$template->if_enable("LINK_MODAL", false);
			}
			//
			$template->assign("SEQ_PAD"      , str_pad( $row["seq"], 7, 0, STR_PAD_LEFT), true);
			$template->assign("TYPE_LABEL"   , $GLOBALS["contactBoxTitle"][$row["contact_type"]], true);
			$template->assign("MESSAGE"      , $row["contents"], true);
			$template->assign("DELIVERY_DT"  , format_datetime($row["delivery_dt"]), true);
			//
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	
	// 表示
	$template->flush();
}


?>
