#!/usr/bin/php -q
<?php
/*
 * error_mail.php
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
 * エラーメール解析処理
 * 
 * エラーメールの解析処理を行う
 * 
 * @package
 * @author   岡本 静子
 * @version  1.0
 * @since    2011/08/10 初版作成 岡本静子
 */

// インクルード
require_once(__DIR__ . '/../_etc/require_files_batch.php');	// requireファイル
require_once(DIR_LIB . 'SmartMailParser.php');	// メール解析クラス
require_once(DIR_LIB . 'SmartMailSend.php');	// メール送信処理クラス


// メイン処理
main();

/**
 * メイン処理
 * @access	public
 * @param	なし
 * @return	なし
 */
function main() {
	
	try {
		
		//##### エラーメールは1通毎の処理なので処理ログは不要とする
		
		// エラーメール情報分解
		$mailParse = new SmartMailParser();
		$body = $mailParse->getBody();
		
		// 分析する情報がなければ終了
		if(mb_strlen($body) == 0) return;
		
		// エラーメールアドレス取得
		$mail = $mailParse->getUnknownAddress();
		if ($mail === false) return;
		if ($mailParse->isUnknownFilter) return;
		
		// DB接続
		$db = new NetDB();
		
		//トランザクション開始
		$db->autoCommit(false);
		
		// 会員マスタのエラー回数更新
		$sql = (new SqlString($db))
			->update("mst_member")
				->set()
					->value("mail_error_count", "mail_error_count + 1", FD_FUNCTION)
					->value("mail_error_dt"   , "current_timestamp", FD_FUNCTION)
					->value("upd_no"          , BATCH_UPD_NO, FD_NUM)
					->value("upd_dt"          , "current_timestamp", FD_FUNCTION)
				->where()
					->and("mail =", $mail, FD_STR)
					->and("state =", 1, FD_NUM)
					->and("mail_error_count < ", MAGAZINE_MAIL_ERR_COUNT, FD_NUM)
			->createSQL();
		$db->query($sql);
		
		// 処理結果
		$retData["status"] = 1;
		$retData["msg"] = "";
		
		// コミット(トランザクション終了)
		$db->autoCommit(true);
		$db->disconnect();		// DB解放
		
	} catch (Exception $e) {
		// エラー時の処理結果
		$retData["status"] = 9;
		$retData["msg"] = $e->getMessage();
	}
	
	if ($retData["status"] == 9 && mb_strlen(MAIL_BATCH_ERROR) > 0) {
		// エラーメール送信
		$smartMailSend = new SmartMailSend(MAIL_PROTOCOL, $GLOBALS["MailParam"]);
		$smartMailSend->setMailSendData(MAIL_INFO, MAIL_BATCH_ERROR);
		$smartMailSend->make( "バッチ処理エラー(" . basename(get_self(). ".php") . ")", $retData["msg"]);
		$smartMailSend->send();
	}
	
}

?>
