<?php
/*
 * KcfCheckAuth.php
 * 
 * (C)SmartRams Co.,Ltd. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *------------------------------------------------------------------
 * 
 * KCFinder認証処理
 * 
 * KCFinder使用時のアップロード先準備
 * 
 * @package
 * @author   岡本 静子
 * @version  1.0
 * @since    2018/09/10 初版作成 岡本静子
 */

// インクルード
require_once('../../../../_etc/require_files_admin.php');	// requireファイル
// /var/www/xxx/xxx/data/sytemjs/kcfinder/ からの相対位置になります

class KcfCheckAuth {
	// メンバ変数定義
	public $UploadURL;		// KCFinderアップロードURL
	public $UploadDir;		// KCFinderアップロードディレクトリ

	/**
	 * コンストラクタ
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	public function __construct() {
	}

	/**
	 * 認証処理
	 * @access	public
	 * @param	なし
	 * @return	true : 正 / false : 否
	 * @info	
	 */
	public function CheckAuth() {
		try {

			// 2020/04/27 [ADD Start] lang定義の配列が取得できないので枠のみ追加
			// 本来メニュー権限リストだがメニュー外なので空配列とする
			global $AuthMenuID;
			$AuthMenuID = array();
			// 2020/04/27 [ADD End] lang定義の配列が取得できないので枠のみ追加

			// 管理系表示コントロールのインスタンス生成
			$template = new TemplateAdmin();	// 2020/04/27 [ADD]

			$this->UploadDir = DIR_IMG_NOTICE;
			$this->UploadURL = DIR_IMG_NOTICE_DIR;
			// フォルダが無ければ作成
			if (!is_dir($this->UploadDir)) {
				mkdir($this->UploadDir);
				chmod($this->UploadDir, PERMS_IMAGE_SITE);
			}
			return true;
			
		} catch (Exception $e) {
			return false;
		}
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