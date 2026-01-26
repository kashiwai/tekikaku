<?php
/*
 * SmartTemplate.php
 * 
 * (C)SmartRams Corp. 2004- All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * HTMLテンプレート処理クラス
 * 
 * HTMLテンプレートの読込み・置換処理を行う
 * 
 * @package	
 * @author	岡本 順子
 * @version	PHP5.x.x
 * @since	2004/01/27 初版作成 岡本 順子 cls_template(PHP4版)として初版作成
 * @since	2008/03/07 移植改定 金光 峰範 PHP4版から移植
 * @since	2009/09/16 全面改修 岡本 静子 命名規約見直しに伴いcls_templateを破棄しSmartTemplateとして新規作成
 * @since	2011/03/17 一部修正 岡本 順子 ループ出力及び条件出力において空行の出力を抑制
 * @since	2011/06/21 一部修正 岡本 静子 ブロックタグの追加
 * @since	2012/01/16 一部加筆 岡本 順子 スマートフォン対応 (PC,スマートフォン,ガラケーの判定及びそれに応じた出力対応)
 * @since	2012/06/20 一部加筆 岡本 順子 PC,スマートフォン,ガラケー向けページの任意表示切替対応
 *       	                              (cookie保持値によるアクセスクライアントタイプの強制すり替え)
 * @since	2012/10/02 一部加筆 岡本 順子 テンプレートファイル読込み時のバッファクリア処理追加
 *       	
 * @info	テンプレートHTMLの１行は4K(4096Byte)以内にすること
 */

// 定数定義
define("BASE_ENCODING"   , "Shift-JIS");				// テンプレート記述基準エンコーディング
define("TAG_ASSIGN_START", "{%");						// 置換タグ開始
define("TAG_ASSIGN_END"  , "%}");						// 置換タグ終了
define("TAG_LOOP_START"  , "<!--loop:\{KEY\}-->");		// ループタグ開始
define("TAG_LOOP_END"    , "<!--\/loop:\{KEY\}-->");	// ループタグ終了
define("TAG_IF_START"    , "<!--if:\{KEY\}-->");		// 条件出力タグ開始
define("TAG_IF_END"      , "<!--\/if:\{KEY\}-->");		// 条件出力タグ終了
define("TAG_CMT_START"   , "<!--cmt:\{");				// コメントタグ開始
define("TAG_CMT_END"     , "\}-->");					// コメントタグ終了
define("TAG_BLOCK_START" , "<!--block:\{KEY\}-->");		// ブロックタグ開始
define("TAG_BLOCK_END"   , "<!--\/block:\{KEY\}-->");	// ブロックタグ終了

//--- 2012/01/16 J.Okamoto Add Start ---
// スマートフォン対応
//--- アクセスクライアントタイプ 列挙体もどき
if (!defined("TYPE_PC"))           define("TYPE_PC"           , 0);	// PC
if (!defined("TYPE_SMART_PHONE"))  define("TYPE_SMART_PHONE"  , 1);	// スマートフォン
if (!defined("TYPE_FEATURE_PHONE")) define("TYPE_FEATURE_PHONE", 2);	// ガラケー(フィーチャーフォン)
//--- アクセスキャリア 列挙体もどき
if (!defined("CARRIER_NAN"))       define("CARRIER_NAN"       , 0);	// PC他 特定キャリアなし
if (!defined("CARRIER_DOCOMO"))    define("CARRIER_DOCOMO"    , 1);	// DoCoMo
if (!defined("CARRIER_AU"))        define("CARRIER_AU"        , 2);	// AU
if (!defined("CARRIER_SOFTBANK"))  define("CARRIER_SOFTBANK"  , 3);	// Softbank
if (!defined("CARRIER_WILLCOM"))   define("CARRIER_WILLCOM"   , 4);	// Willcom
if (!defined("CARRIER_EMOBILE"))   define("CARRIER_EMOBILE"   , 5);	// EMOBILE
//--- 2012/01/16 J.Okamoto Add End   ---

class SmartTemplate {
	// メンバ変数定義
	private $m_basedir = "";	// テンプレートファイル配置基本ディレクトリ
	private $m_enc = "";		// テンプレートファイル記述文字エンコード
	private $m_index = 0;		// カレント階層
	private $m_current;			// カレントソース情報(階層を１次元、内容を連想２次元とした配列)
								// base - テンプレートソース, stack - 出力用ソース, buffer - ループ作業用ソース
	//--- 2012/01/16 J.Okamoto Add Start ---
	// スマートフォン対応
	private $m_typeAnalysis = false;	// アクセス元判定を行うか否か
	private $m_ua = "";					// 生ユーザエージェント
	private $m_type    = TYPE_PC;		// アクセスクライアントタイプ(PC, SmartPhone, FeaturePhone)
	private $m_typeInf = "";			// アクセスクライアント詳細
										// 		PC           - 空白
										// 		SmartPhone   - iPhone, iPodTouch, iPad, Android, WindowsPhone, BlackBerry, PalmEx, iPhoneOther, AndroidOther
										// 		FeaturePhone - DoCoMoの場合はFOMA/MOVA, AUの場合はNEW/OLD, Softbankの場合はSoftBank/Vodafone/J-PHONE
	private $m_typeVer = "";			// アクセスクライアントバージョン
										// 		PC           - 空白
										// 		SmartPhone   - iPhone/iPodTouch/iPadの場合はiOSバージョン, Android系の場合はAndroidバージョン, WindowsPhoneの場合はMobileIEバージョン
										// 		FeaturePhone - ブラウザバージョン
	private $m_model   = "";			// 機種名(SmartPhone,FeaturePhoneの場合で取得可能時のみ)
	private $m_carrier = CARRIER_NAN;	// アクセスキャリア
										// 		PC           - CARRIER_NAN
										// 		SmartPhone   - CARRIER_NAN
										// 		FeaturePhone - CARRIER_DOCOMO, CARRIER_AU, CARRIER_SOFTBANK, CARRIER_WILLCOM, CARRIER_EMOBILE
	private $m_typedir;					// アクセスクライアントタイプ毎のテンプレートファイル配置基本ディレクトリ
	//--- 2012/01/16 J.Okamoto Add End   ---

	/**
	 * コンストラクタ
	 * @access	public
	 * @param	string	$p_basedir		テンプレートファイル配置基本ディレクトリ(省略時は指定なし)
	 * @param	string	$p_enc			テンプレート記述文字エンコーディング
	 * @param	string	$p_onMobile		アクセスタイプ判定有無(省略時は判定なし)  --- 2012/01/16 J.Okamoto Add
	 * @return	インスタンス
	 */
	public function __construct($p_basedir = "", $p_enc = BASE_ENCODING, $m_typeAnalysis = false) {
		// パラメータの内部変数への保管
		$this->m_basedir = $p_basedir;
		$this->m_enc     = $p_enc;
		//--- 2012/01/16 J.Okamoto Add Start ---
		// 生ユーザエージェントの保管
		//$this->m_ua = $_SERVER["HTTP_USER_AGENT"];
		$this->m_ua = getenv("HTTP_USER_AGENT");
		// スマートフォン対応
		$this->m_typeAnalysis = $m_typeAnalysis;
		if ($this->m_typeAnalysis) {
			$this->accesstypeAnalysis();
			//--- 2012/06/20 J.Okamoto Add Start ---
			// PC,スマートフォン,ガラケー向けページの任意表示切替対応のため、
			// cookie保持値によってアクセスクライアントタイプを強制的にすり替え
			$displayType = "";
			if (isset($_COOKIE["_TemplateDisplayType"])) $displayType = trim($_COOKIE["_TemplateDisplayType"]);
			if (isset($_GET["_TemplateDisplayType"]))    $displayType = trim($_GET["_TemplateDisplayType"]);
			if (isset($_POST["_TemplateDisplayType"]))   $displayType = trim($_POST["_TemplateDisplayType"]);
			if ($displayType != "") {
				if ($displayType == $this->m_type) {
					setcookie("_TemplateDisplayType", "", time() - 3600, "/");
				} else {
					$this->m_type = $displayType;
					setcookie("_TemplateDisplayType", $displayType, 0, "/");
				}
			}
			//--- 2012/06/20 J.Okamoto Add End   ---
		}
		//--- 2012/01/16 J.Okamoto Add End   ---
	}

	/**
	 * テンプレートファイル読込み
	 * @access	public
	 * @param	string	$p_filename		テンプレートファイル名
	 * @return	なし
	 * @info
	 */
	public function open($p_filename) {
		// 指定ディレクトリとファイル名から取得ファイルを確定
		$file = $this->m_basedir . $p_filename;
		//--- 2012/01/16 J.Okamoto Add Start ---
		// スマートフォン対応
		if ($this->m_typeAnalysis) {
			// アクセスクライアント毎に、別途指定されたテンプレートディレクトリを基準とする
			if (mb_strlen($this->m_typedir[$this->m_type]) > 0) {
				$file = $this->m_typedir[$this->m_type] . $p_filename;
			}
		}
		//--- 2012/01/16 J.Okamoto Add End   ---
		// カレント階層を最上位に設定
		$this->m_index = 0;
		// ファイル内容を読込み、ソース情報をセット
		$fp = @fopen($file, "r");
		if ($fp === false) throw new Exception(__METHOD__ . ": error open(" . $file . ")");

		//--- 2012/10/02 J.Okamoto Add Start ---
		// エラー処理時に同じインスタンスの本クラスを使用した場合、
		// 既にOpenされているバッファに加算して処理されるため
		// 明示的にクリア処理を行う
		$this->m_current[$this->m_index]["base"] = "";
		//--- 2012/10/02 J.Okamoto Add End   ---

		while(!feof($fp)) {
			@$this->m_current[$this->m_index]["base"] .= fgets($fp, 4096);
		}
		fclose($fp);

		// テンプレート記述文字エンコードがPHP処理エンコードと違う場合は文字エンコードを変換
		// PHP 8.1+ 対応: mb_internal_encoding()を使用
		$internal_encoding = mb_internal_encoding() ?: 'UTF-8';
		if ($this->m_enc && $this->m_enc != $internal_encoding) {
			// 記述エンコード→PHP処理エンコード
			$this->m_current[$this->m_index]["base"]
				 = mb_convert_encoding($this->m_current[$this->m_index]["base"], $internal_encoding, $this->m_enc);
		}

		// 取得内容の編集用領域への展開
		$this->m_current[$this->m_index]["stack"] = $this->m_current[$this->m_index]["base"];
		$this->m_current[$this->m_index]["buffer"] = "";
	}

	/**
	 * キーワードの置換
	 * @access	public
	 * @param	string	$p_key			置換キーワード名
	 * @param	string	$p_value		置換内容
	 * @return	なし
	 * @info	カレント階層に対して置換を行う
	 */
	public function assign($p_key, $p_value) {
		// カレント階層が最上位階層の場合は置換対象は[出力用ソース]、ループ階層の場合は[ループ作業用ソース]
		$target = ($this->m_index < 1) ? "stack" : "buffer";
		// 指定キーワードの置換
		$p_value = str_replace("\0", "", $p_value ?? "");	// 変換内容に「\0」が入っているとHTMLがおかしくなることを回避
		// $this->m_current[$this->m_index][$target]がnullの場合に備えて、空文字にフォールバック
		$currentTarget = $this->m_current[$this->m_index][$target] ?? "";
		$this->m_current[$this->m_index][$target]
			 = str_replace(TAG_ASSIGN_START . $p_key . TAG_ASSIGN_END, $p_value, $currentTarget);
	}

	/**
	 * ループ処理開始
	 * @access	public
	 * @param	string	$p_loopname		ループ名
	 * @return	なし
	 * @info	ネスト可
	 */
	public function loop_start($p_loopname) {
		//--- 2011/03/17 J.Okamoto Update Start ---
		//--- 出力時に開始タグ後の改行及び終了タグ前のインデントタブを排除するよう正規表現を変更
		/*
		// 指定ループ名に該当するループタグ正規表現フォーマット生成
		$format = str_replace("KEY", $p_loopname, TAG_LOOP_START)
				 . "(.*?)"
				 . str_replace("KEY", $p_loopname, TAG_LOOP_END);
		*/
		$format = str_replace("KEY", $p_loopname, TAG_LOOP_START) . "\n?"
				 . "(.*?)"
				 . "\t*" . str_replace("KEY", $p_loopname, TAG_LOOP_END);
		//--- 2011/03/17 J.Okamoto Update End   ---

		// カレント階層が最上位階層の場合は切出し対象は[出力用ソース]、ループ階層の場合は[ループ作業用ソース]
		$target = ($this->m_index < 1) ? "stack" : "buffer";

		// 指定ループに該当する個所を切出し
		if (!preg_match("/" . $format . "/s", $this->m_current[$this->m_index][$target], $regs))
											throw new Exception(__METHOD__ . ": error loopstart(" . $p_loopname . ") - TagNotFound");

		// カレント階層を１階層下げる
		$this->m_index++;

		// ソース情報をセット
		$this->m_current[$this->m_index]["base"] = $regs[1];
		$this->m_current[$this->m_index]["stack"] = "";
		$this->m_current[$this->m_index]["buffer"] = $this->m_current[$this->m_index]["base"];

	}

	/**
	 * 次ループ処理
	 * @access	public
	 * @param	boolean	$p_apend		現在の[ループ作業用ソース]を有効にするか否か(省略時は有効)
	 * @return	なし
	 * @info	ネスト可
	 */
	public function loop_next($p_apend = true) {
		// [ループ作業用ソース]を[出力用ソース]へ追加
		if ($p_apend) $this->m_current[$this->m_index]["stack"] .= $this->m_current[$this->m_index]["buffer"];
		// [ループ作業用ソース]を初期化
		$this->m_current[$this->m_index]["buffer"] = $this->m_current[$this->m_index]["base"];
	}

	/**
	 * ループ終了開始
	 * @access	public
	 * @param	string	$p_loopname		ループ名
	 * @return	なし
	 */
	public function loop_end($p_loopname) {
		// 階層不正(ループ処理が開始されていない)
		if ($this->m_index < 1) throw new Exception(__METHOD__ . ": error loopend(" . $p_loopname . ") - ClassInjustice");

		// 上位階層が最上位階層の場合は反映対象は[出力用ソース]、ループ階層の場合は[ループ作業用ソース]
		$target = ($this->m_index - 1 < 1) ? "stack" : "buffer";

		// 現在の階層の[出力用ソース]を上位階層に反映
		$this->m_current[$this->m_index - 1][$target] = 
			str_replace(
				$this->m_current[$this->m_index]["base"], 
				$this->m_current[$this->m_index]["stack"], 
				$this->m_current[$this->m_index - 1][$target]
			);

		// カレントソース情報を破棄
		$this->m_current[$this->m_index]["base"] = "";
		$this->m_current[$this->m_index]["stack"] = "";
		$this->m_current[$this->m_index]["buffer"] = "";

		// カレント階層を１階層上げる
		$this->m_index--;
	}

	/**
	 * ブロック処理開始
	 * @access	public
	 * @param	string	$p_blockname		ブロック名
	 * @return	なし
	 * @info	ネスト不可
	 */
	public function block_start($p_blockname) {
		//--- 出力時に開始タグ後の改行及び終了タグ前のインデントタブを排除するよう正規表現を変更
		// 指定ブロック名に該当するブロックタグ正規表現フォーマット生成
		$format = str_replace("KEY", $p_blockname, TAG_BLOCK_START) . "\n?"
				 . "(.*?)"
				 . "\t*" . str_replace("KEY", $p_blockname, TAG_BLOCK_END);

		// カレント階層が最上位階層の場合は切出し対象は[出力用ソース]、ループ階層の場合は[ループ作業用ソース]
		$target = ($this->m_index < 1) ? "stack" : "buffer";

		// 指定ブロックに該当する個所を切出し
		if (!preg_match("/" . $format . "/s", $this->m_current[$this->m_index][$target], $regs))
											  die("Error: blockstart(" . $p_blockname . ") - TagNotFound");

		// カレント階層を１階層下げる
		$this->m_index++;

		// ソース情報をセット
		$this->m_current[$this->m_index]["base"] = $regs[1];
		$this->m_current[$this->m_index]["stack"] = "";
		$this->m_current[$this->m_index]["buffer"] = $this->m_current[$this->m_index]["base"];

	}

	/**
	 * ブロック終了開始
	 * @access	public
	 * @param	string	$p_blockname		ブロック名
	 * @return	なし
	 */
	public function block_end($p_blockname) {
		// 階層不正(ループ処理が開始されていない)
		if ($this->m_index < 1) die("Error: blockend(" . $p_blockname . ") - ClassInjustice");

		// [ループ作業用ソース]を[出力用ソース]へ追加
		$this->m_current[$this->m_index]["stack"] .= $this->m_current[$this->m_index]["buffer"];

		// 上位階層が最上位階層の場合は反映対象は[出力用ソース]、ループ階層の場合は[ループ作業用ソース]
		$target = ($this->m_index - 1 < 1) ? "stack" : "buffer";

		// 現在の階層の[出力用ソース]を上位階層に反映
		$this->m_current[$this->m_index - 1][$target] = 
			str_replace(
				$this->m_current[$this->m_index]["base"], 
				$this->m_current[$this->m_index]["stack"], 
				$this->m_current[$this->m_index - 1][$target]
			);

		// カレントソース情報を破棄
		$this->m_current[$this->m_index]["base"] = "";
		$this->m_current[$this->m_index]["stack"] = "";
		$this->m_current[$this->m_index]["buffer"] = "";

		// カレント階層を１階層上げる
		$this->m_index--;
	}

	/**
	 * 条件出力項目の表示制御
	 * @access	public
	 * @param	string	$p_ifname		条件出力項目名
	 * @param	boolean	$p_enable		有効か否か
	 * @param	string	$p_falsepart	無効の場合に代替する文字列(省略時は空白)
	 * @return	なし
	 * @info
	 */
	public function if_enable($p_ifname, $p_enable, $p_falsepart = "") {
		//--- 2011/03/17 J.Okamoto Update Start ---
		//--- 未出力時にタブと改行のみになる行を残さないよう正規表現を変更
		/*
		// 有効指定時はタグのみを除去
		if ($p_enable) $p_falsepart = "\\1";

		// 指定項目名に該当する条件出力タグ正規表現フォーマット生成
		$format = str_replace("KEY", $p_ifname, TAG_IF_START)
				 . "(.*?)"
				 . str_replace("KEY", $p_ifname, TAG_IF_END);
		*/
		// 有効指定時はタグのみを除去
		if ($p_enable) $p_falsepart = "\\1\\2\\3";

		// 指定項目名に該当する条件出力タグ正規表現フォーマット生成
		$format = "(\t*)" . str_replace("KEY", $p_ifname, TAG_IF_START)
				 . "(.*?)"
				 . str_replace("KEY", $p_ifname, TAG_IF_END) . "(\n?)";
		//--- 2011/03/17 J.Okamoto Update End   ---

		// カレント階層が最上位階層の場合は置換対象は[出力用ソース]、ループ階層の場合は[ループ作業用ソース]
		$target = ($this->m_index < 1) ? "stack" : "buffer";

		// 指定項目内容を置換
		//$this->m_current[$this->m_index][$target] = ereg_replace($format, $p_falsepart, $this->m_current[$this->m_index][$target]);
		$this->m_current[$this->m_index][$target]
			 = preg_replace("/" . $format . "/s", $p_falsepart, $this->m_current[$this->m_index][$target]);

	}

	/**
	 * 現在編集中内容へ任意内容追加
	 * @access	public
	 * @param	string	$p_value		追加内容
	 * @param	boolean	$p_stack_apend	ターゲット指定(true：stack固定)
	 * @return	なし
	 * @info
	 */
	public function apend($p_value, $p_stack_apend = true) {
		$target = ($this->m_index < 1 || $p_stack_apend) ? "stack" : "buffer";
		$this->m_current[$this->m_index][$target] .= $p_value;
	}

	/**
	 * 現在編集中内容の取得
	 * @access	public
	 * @param	なし
	 * @return	string	編集中内容
	 * @info
	 */
	public function get() {
		$target = ($this->m_index < 1) ? "stack" : "buffer";
		return $this->m_current[$this->m_index][$target];
	}

	/**
	 * 現在編集中内容の差替え
	 * @access	public
	 * @param	string	$p_value		変換内容
	 * @return	なし
	 * @info
	 */
	public function replace($p_value) {
		$target = ($this->m_index < 1) ? "stack" : "buffer";
		$this->m_current[$this->m_index][$target] = $p_value;
	}

	/**
	 * 指定の置換キーワード以前の内容のみ書出し
	 * @access	public
	 * @param	string	$p_key		指定対象タグ
	 * @return	なし
	 * @info
	 */
	public function part_flush($p_key) {
		$target = ($this->m_index < 1) ? "stack" : "buffer";
		$this->m_current[$this->m_index][$target] = $p_value;
		
		$bufAry = explode(TAG_ASSIGN_START . $p_key . TAG_ASSIGN_END, $this->get(), 2);
		if( count( $bufAry ) == 2 ) {
			print $bufAry[0];
			$this->replace(TAG_ASSIGN_START . $p_key . TAG_ASSIGN_END . $bufAry[1]);
		}
	}

	/**
	 * 出力
	 * @access	public
	 * @param	なし
	 * @return	なし
	 * @info
	 */
	public function flush() {
		$buf = $this->get();

		// コメント表記の削除
		$format = TAG_CMT_START . "([^(" . TAG_CMT_START . ")]*)" . TAG_CMT_END;
		//$buf = ereg_replace($format, "", $buf);
		$buf = preg_replace("/" . $format . "/s", "", $buf);

		//--- 2012/01/16 J.Okamoto Add Start ---
		// スマートフォン対応
		if ($this->m_typeAnalysis) {
			// ガラケー対応 形式変換
			if ($this->m_type == TYPE_FEATURE_PHONE) {
				$buf = mb_convert_kana($buf, "k");
				$buf = $this->convertAccesskey($buf);
				$buf = $this->convertImputkey($buf);

				//*** iモード Unicode形式(&#x\\\\;) 絵文字 を他キャリア向けに変換 ***
				require_once("SmartEmojiConverter.php");		// 絵文字変換クラスライブラリ
				$smartEmojiConv = new SmartEmojiConverter(CARRIER_DOCOMO, $this->m_carrier);
				$buf = $smartEmojiConv->Convert($buf);
				//*******************************************************************
			}
		}
		//--- 2012/01/16 J.Okamoto Add End   ---

		print $buf;
	}

	/**
	 * 基本ディレクトリパスの設定
	 * @access	public
	 * @param	string	$p_value		テンプレートファイル配置基本ディレクトリパス
	 * @return	なし
	 * @info
	 */
	public function setBaseDir($p_value) {
		$this->m_basedir = $p_value;
	}

	//--- 2012/06/15 S.Okamoto Add Start   ---
	/**
	 * 基本ディレクトリパス取得
	 * @access	public
	 * @param	なし
	 * @return	string		テンプレートファイル配置基本ディレクトリパス
	 */
	public function getBaseDir() {
		return (mb_strlen($this->m_typedir[$this->m_type]) > 0) ? $this->m_typedir[$this->m_type] : $this->m_basedir;
	}
	//--- 2012/06/15 S.Okamoto Add End   ---

	/**
	 * 置換タグ有無確認
	 * @access	public
	 * @param	string	$p_key			置換キーワード名
	 * @return	true : 置換可能 / false : 置換不可
	 */
	public function exist_tagkey($p_key) {
		// カレント階層が最上位階層の場合は置換対象は[出力用ソース]、ループ階層の場合は[ループ作業用ソース]
		$target = ($this->m_index < 1) ? "stack" : "buffer";
		// 指定キーワードの存在確認
		if (strpos($this->m_current[$this->m_index][$target], TAG_ASSIGN_START . $p_key . TAG_ASSIGN_END) === false) return false;

		return true;
	}

	/**
	 * ブロックタグ有無判定
	 * @access	public
	 * @param	string	$p_blockname		ブロック名
	 * @return	なし
	 * @info	ネスト可
	 */
	public function exist_block($p_blockname) {
		// 指定ブロック名に該当するブロックタグ正規表現フォーマット生成
		$format = str_replace("KEY", $p_blockname, TAG_BLOCK_START)
				 . "(.*?)"
				 . str_replace("KEY", $p_blockname, TAG_BLOCK_END);

		$target = ($this->m_index < 1) ? "stack" : "buffer";

		// 該当する個所を切出し
		if (!preg_match("/" . $format . "/s", $this->m_current[$this->m_index][$target], $regs)) return false;

		return true;

	}

	/**
	 * ループタグ有無判定
	 * @access	public
	 * @param	string	$p_loopname		ループ名
	 * @return	なし
	 * @info	ネスト可
	 */
	public function exist_loop($p_loopname) {
		// 指定ループ名に該当するループタグ正規表現フォーマット生成
		$format = str_replace("KEY", $p_loopname, TAG_LOOP_START)
				 . "(.*?)"
				 . str_replace("KEY", $p_loopname, TAG_LOOP_END);

		$target = ($this->m_index < 1) ? "stack" : "buffer";

		// 該当する個所を切出し
		if (!preg_match("/" . $format . "/s", $this->m_current[$this->m_index][$target], $regs)) return false;

		return true;

	}

	//--- 2012/01/16 J.Okamoto Add Start ---
	// スマートフォン対応
	/**
	 * アクセスクライアントタイプ判定
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	private function accesstypeAnalysis() {

		// 内部変数の初期化
		$this->m_type    = TYPE_PC;			// アクセスクライアントタイプ     = PC
		$this->m_typeInf = "";				// アクセスクライアント詳細       = 特定情報なし
		$this->m_typeVer = "";				// アクセスクライアントバージョン = 特定バージョンなし
		$this->m_model   = "";				// 機種名                         = 特定機種なし
		$this->m_carrier = CARRIER_NAN;		// アクセスキャリア               = 特定キャリアなし
		$this->m_typedir = array(			// アクセスクライアントタイプ毎のテンプレートファイル配置基本ディレクトリ
							  TYPE_PC            => ""
							, TYPE_SMART_PHONE   => ""
							, TYPE_FEATURE_PHONE => ""
							);

		//*** スマートフォン判定
		// 
		// http://www.nttdocomo.co.jp/service/developer/smart_phone/technical_info/spec/
		// https://www.support.softbankmobile.co.jp/partner/home_tech1/index.cfm
		// https://www.support.softbankmobile.co.jp/partner/smp_info/smp_info_search_t.cfm
		/*
		*/
		// 解析用配列作成
		$smartphone = array(
			/*
				4S		Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3
				4		Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; ja-jp) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7
				3GS		Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; ja-jp) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7
				3G		Mozilla/5.0 (iPhone; U; CPU iPhone OS 2_0 like Mac OS X; ja-jp) AppleWebKit/525.18.1 (KHTML, like Gecko) Version/3.1.1 Mobile/5A345 Safari/525.20
			*/
			  "iPhone" => array(
					  "format"  => "Mozilla\/[0-9\.]+\s\(iPhone;(\sU;)?\sCPU\siPhone\sOS\s([0-9_]+)\s.+\).+"
					, "ver"     => "\\2"	// \\2 : iOSバージョン
					, "model"   => ""		// 機種名取得なし
					)
			/*
				4G		Mozilla/5.0 (iPod; U; CPU iPhone OS 4_1 like Mac OS X; ja-jp) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8B117 Safari/6531.22.7
			*/
			, "iPodTouch" => array(
					  "format"  => "Mozilla\/[0-9\.]+\s\(iPod;(\sU;)?\sCPU\siPhone\sOS\s([0-9_]+)\s.+\).+"
					, "ver"     => "\\2"	// \\2 : iOSバージョン
					, "model"   => ""		// 機種名取得なし
					)
			/*
				iPad2	Mozilla/5.0 (iPad; U; CPU OS 4_3_2 like Mac OS X; ja-jp) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8H7 Safari/6533.18.5
				iPad	Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; ja-jp) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B367 Safari/531.21.10
			*/
			//##### 2012/08/01 Del S by S.Okamoto iPadはPC判定とするためコメントアウト
			/*
			, "iPad" => array(
					  "format"  => "Mozilla\/[0-9\.]+\s\(iPad;(\sU;)?\sCPU\sOS\s([0-9_]+)\s.+\).+"
					, "ver"     => "\\2"	// \\2 : iOSバージョン
					, "model"   => ""		// 機種名取得なし
					)
			*/
			//##### 2012/08/01 Del E
			/*
				Mozilla/5.0 (Linux; U; Android [バージョン]; [言語コード]-[地域コード]; [デバイス名称] Build/[ビルドID]) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.
				DoCoMo F-05F		Mozilla/5.0 (Linux; U; Android 2.3.5; ja-jp; F-05D Build/F0001) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1
				DoCoMo GALAXYTab	Mozilla/5.0 (Linux; U; Android 2.3.3; ja-jp; SC-01C Build/GINGERBREAD) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1
				DoCoMo Xperia		Mozilla/5.0 (Linux; U; Android 2.1-update1; ja-jp; SonyEricssonSO-01B Build/2.0.B.0.138) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17
				AU     IS12F		Mozilla/5.0 (Linux; U; Android 2.3.5; ja-jp; IS12F Build/FGK600) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1
				AU     IS11T		Mozilla/5.0 (Linux; U; Android 2.3.4; ja-jp; IS11T Build/FGK400) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1
				AU     IS03			Mozilla/5.0 (Linux; U; Android 2.1-update1; ja-jp; IS03 Build/SB060) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.170
				SB     101P			Mozilla/5.0 (Linux; U; Android 2.3.5; ja-jp; 101P Build/GRJ90) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1
				SB     001DL		Mozilla/5.0 (Linux; U; Android 2.2; ja-jp; 001DL Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1
				SB     X06HTII		Mozilla/5.0 (Linux; U; Android 2.1-update1; ja-jp; HTCX06HT Build/ERE27) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17
			*/
			, "Android" => array(
					  "format"  => "Mozilla\/[0-9\.]+\s\(Linux;\sU;\sAndroid\s([^;]+);\s[^;]+;\s(.+)\sBuild\/[^\)]+\).+"
					, "ver"     => "\\1"	// \\1 : Androidバージョン
					, "model"   => "\\2"	// \\2 : 機種名
					)
			/*
				DoCoMo T-01B		Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 8.12; MSIEMobile 6.5) T-01B
				AU     IS12T		Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; FujitsuToshibaMobileCommun; IS12T; KDDI)
				SB     X01SC		Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 8.12; MSIEMobile 6.0) SAMSUNG/X01SC
			*/
			, "WindowsPhone" => array(
					  "format"  => "Mozilla\/[0-9\.]+\s\(.+\sIEMobile\s([^;\)]+).*\)\s?(.*)"
					, "ver"     => "\\1"	// \\1 : MobileIEバージョン
					, "model"   => "\\2"	// \\2 : 機種名(とりきらないけど・・・勘弁して)
					)
			/*
				DoCoMo BlackBerry Bold		BlackBerry9000/5.0.0.1036 Profile/MIDP-2.1 Configuration/CLDC-1.1 VendorID/220
				DoCoMo BlackBerry 8707h		BlackBerry8707/4.2.2 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/220
			*/
			, "BlackBerry" => array(
					  "format"  => "BlackBerry([0-9]+)\/[0-9\.]+\sProfile\/MIDP\-([0-9\.]+)\s.+"
					, "ver"     => "\\2"	// \\2 : MIDPバージョン
					, "model"   => "\\1"	// \\1 : 機種型番
					)
			/*
				参考UAなし
			*/
			, "PalmEx" => array(
					  "format"  => "webOS"
					, "ver"     => ""		// バージョン取得なし
					, "model"   => ""		// 機種取得なし
					)
			/*
				参考UAなし
			*/
			, "iPhoneOther" => array(
					  "format"  => "incognito|webmate"
					, "ver"     => ""		// バージョン取得なし
					, "model"   => ""		// 機種取得なし
					)
			/*
				参考UAなし
			*/
			, "AndroidOther" => array(
					  "format"  => "dream|CUPCAKE"
					, "ver"     => ""		// バージョン取得なし
					, "model"   => ""		// 機種取得なし
					)
		);
		foreach ($smartphone as $key => $pattern) {
			// UAがパターンマッチするかで判定
			if (preg_match("/" . $pattern["format"] . "/i", $this->m_ua, $regs)) {
				// 一致した場合はスマートフォンと特定
				$this->m_type    = TYPE_SMART_PHONE;
				// 詳細情報は解析用配列のKeyを採用
				$this->m_typeInf = $key;
				// バージョン取得
				$this->m_version = $pattern["ver"];
				if (preg_match("/\\\\([0-9]+)/i", $pattern["ver"], $ireg)) $this->m_version = $regs[(int)$ireg[1]];
				// 機種取得
				$this->m_model = $pattern["model"];
				if (preg_match("/\\\\([0-9]+)/i", $pattern["model"], $ireg)) $this->m_model = $regs[(int)$ireg[1]];
				// キャリアに関しては取得しない(初期値のまま)
				// 判定終了
				break;
			}
		}
		if ($this->m_type != TYPE_PC) return;


		//*** ガラケー判定
		$featurephone = array(
			//-- DoCoMo
			// http://www.nttdocomo.co.jp/service/developer/make/content/spec/useragent/index.html
			/* DoCoMo FOMA
				DoCoMo/2.0 N901iS(c100;TB;W24H12)
				DoCoMo/2.0 N901iS(c100;TB;W24H12;ser123456789012345;icc12345678901234567890) */
			  "DoCoMo-Foma" => array(
					  "format"  => "DoCoMo\/([0-9\.]+)\s([^\(]+)\([^\)]+\)"
					, "info"    => "FOMA"
					, "ver"     => "\\1"	// \\1 : ブラウザバージョン
					, "model"   => "\\2"	// \\2 : 機種名
					, "carrier" => CARRIER_DOCOMO
					)
			/* DoCoMo MOVA
				DoCoMo/1.0/N503i/c10
				DoCoMo/1.0/N503i/c10/ser12345678901 */
			, "DoCoMo-Mova" => array(
					  "format"  => "DoCoMo\/([0-9\.]+)\/([^\/]+)\/.+"
					, "info"    => "MOVA"
					, "ver"     => "\\1"	// \\1 : ブラウザバージョン
					, "model"   => "\\2"	// \\2 : 機種名
					, "carrier" => CARRIER_DOCOMO
					)
			//-- AU
			// http://www.au.kddi.com/ezfactory/tec/spec/4_4.html
			/* AU 新タイプ
				KDDI-HI21 UP.Browser/6.0.2.254 (GUI) MMP/1.1 */
			, "AU-New" => array(
					  "format"  => "KDDI\-([^\s]+)\sUP\.Browser\/.+"
					, "info"    => "NEW"
					, "ver"     => ""		// バージョン取得なし
					, "model"   => "\\1"	// \\1 : デバイスID
					, "carrier" => CARRIER_AU
					)
			/* AU 旧タイプ
				UP.Browser/3.04-SN12 UP.Link/3.4.4 */
			, "AU-Old" => array(
					  "format"  => "UP\.Browser\/[^\-]+\-([^\s]+)\sUP\.Link\/.+"
					, "info"    => "OLD"
					, "ver"     => ""		// バージョン取得なし
					, "model"   => "\\1"	// \\1 : デバイスID
					, "carrier" => CARRIER_AU
					)
			//-- Softbank
			// http://creation.mb.softbank.jp/mc/terminal/terminal_info/terminal_useragent.html
			// 基本的には [SoftBank(or Vodafone or J-PHONE)/ブラウザバージョン/機種名/その他] という構成
			/* SoftBank 3G Series（ソフトバンク時代のもの)
				SoftBank/1.0/910T/TJ001/SN123456789012345 Browser/NetFront/3.3 Profile/MIDP-2.0 Configuration/CLDC-1.1
			   SoftBank 3G Series (ボーダフォン時代のもの)
				Vodafone/1.0/V904SH/SHJ001/SN123456789012345 Browser/VF-NetFront/3.3 Profile/MIDP-2.0 Configuration/CLDC-1.1
			   SoftBank 6-5 Series (Jフォン時代のもの)
				J-PHONE/4.0/J-SH51/SN12345678901 SH/0001a Profile/MIDP-1.0 Configuration/CLDC-1.0
			   SoftBank 4-2 Series (Jフォン時代のもの)
				J-PHONE/3.0/J-SH07 */
			, "SoftBank" => array(
					  "format"  => "(SoftBank|Vodafone|J-PHONE)\/([0-9\.]+)\/([^\/]+).*"
					, "info"    => "\\1"
					, "ver"     => "\\2"	// \\2 : ブラウザバージョン
					, "model"   => "\\3"	// \\3 : 機種名
					, "carrier" => CARRIER_SOFTBANK
					)
			//-- Willcom
			// http://www.willcom-inc.com/ja/service/contents_service/create/lineup/index.html#03
			// Mozilla/バージョン([WILLCOM or DDIPOCKET];メーカー名/機種名/端末ver/ブラウザver/キャッシュサイズ)ブラウザ名
			/* Mozilla/3.0(WILLCOM;KYOCERA/WX331K/2;1.0.3.13.000000/0.1/C100)Opera 7.2 EX */
			, "Willcom" => array(
					  "format"  => "Mozilla\/[0-9\.]+\((WILLCOM|DDIPOCKET);[^\/]+\/([^\/]+)\/[^\/]+\/([^\/]+)\/[^\/]+\).*"
					, "info"    => ""
					, "ver"     => "\\3"	// \\3 : ブラウザバージョン
					, "model"   => "\\2"	// \\2 : 機種名
					, "carrier" => CARRIER_WILLCOM
					)
			//-- EMOBILE
			// http://developer.emnet.ne.jp/useragent.html
			// emobile/バージョン (機種名; like Gecko; Wireless) ブラウザ名/ブラウザver
			/* emobile/1.0.0 (H11T; like Gecko; Wireless) NetFront/3.4 */
			, "EMOBILE" => array(
					  "format"  => "emobile\/[0-9\.]+\s\(([^;]+);[^\)]*\)\s[^\/]+\/(.*)"
					, "info"    => ""
					, "ver"     => "\\2"	// \\2 : ブラウザバージョン
					, "model"   => "\\1"	// \\1 : 機種名
					, "carrier" => CARRIER_EMOBILE
					)
		);
		foreach ($featurephone as $key => $pattern) {
			// UAがパターンマッチするかで判定
			if (preg_match("/" . $pattern["format"] . "/i", $this->m_ua, $regs)) {
				// 一致した場合はガラケーと特定
				$this->m_type    = TYPE_FEATURE_PHONE;
				// 詳細情報取得
				$this->m_typeInf = $pattern["info"];
				if (preg_match("/\\\\([0-9]+)/i", $pattern["info"], $ireg)) $this->m_typeInf = $regs[(int)$ireg[1]];
				// バージョン取得
				$this->m_version = $pattern["ver"];
				if (preg_match("/\\\\([0-9]+)/i", $pattern["ver"], $ireg)) $this->m_version = $regs[(int)$ireg[1]];
				// 機種取得
				$this->m_model = $pattern["model"];
				if (preg_match("/\\\\([0-9]+)/i", $pattern["model"], $ireg)) $this->m_model = $regs[(int)$ireg[1]];
				// キャリア取得
				$this->m_carrier = $pattern["carrier"];
				// 判定終了
				break;
			}
		}
		if ($this->m_type != TYPE_PC) return;


		//*** PCは何も考えなくていいよね・・・

	}

	/**
	 * アクセスクライアント生ユーザエージェント取得
	 * @access	public
	 * @param	なし
	 * @return	string	- 生ユーザエージェント
	 */
	public function getAccessUA() {
		return $this->m_ua;
	}

	/**
	 * アクセスクライアントタイプ取得
	 * @access	public
	 * @param	なし
	 * @return	integer	- TYPE_PC : PCその他 / TYPE_SMART_PHONE : スマートフォン / TYPE_FEATURE_PHONE : ガラケー
	 */
	public function getAccessType() {
		return $this->m_type;
	}

	/**
	 * アクセスクライアント詳細取得
	 * @access	public
	 * @param	なし
	 * @return	string	- PC           - 空白
	 *        	      	  SmartPhone   - iPhone, iPodTouch, iPad, Android, WindowsPhone, BlackBerry, PalmEx, iPhoneOther, AndroidOther
	 *        	      	  FeaturePhone - DoCoMoの場合はFOMA/MOVA, その他は空白
	 */
	public function getAccessTypeInfo() {
		return $this->m_typeInf;
	}

	/**
	 * アクセスクライアントバージョン取得
	 * @access	public
	 * @param	なし
	 * @return	string	- PC           - 空白
	 *        	      	  SmartPhone   - iPhone/iPodTouch/iPadの場合はiOSバージョン, Android系の場合はAndroidバージョン, WindowsPhoneの場合はMobileIEバージョン
	 *        	      	  FeaturePhone - ブラウザバージョン
	 */
	public function getAccessTypeVersion() {
		return $this->m_typeVer;
	}

	/**
	 * アクセスキャリア取得
	 * @access	public
	 * @param	なし
	 * @return	integer	- PC           - CARRIER_NAN
	 *        	       	  SmartPhone   - CARRIER_NAN
	 *        	       	  FeaturePhone - CARRIER_DOCOMO, CARRIER_AU, CARRIER_SOFTBANK, CARRIER_WILLCOM, CARRIER_EMOBILE
	 */
	public function getAccessCarrier() {
		return $this->m_carrier;
	}

	/**
	 * 機種名取得
	 * @access	public
	 * @param	なし
	 * @return	string	機種名(SmartPhone,FeaturePhoneの場合で取得可能時のみ)
	 */
	public function getAccessModel() {
		return $this->m_model;
	}

	/**
	 * アクセスクライアントタイプ毎の基本ディレクトリパスの設定
	 * @access	public
	 * @param	integer	$p_type			アクセスクライアントタイプ(TYPE_PC / TYPE_SMART_PHONE / TYPE_FEATURE_PHONE)
	 * @param	string	$p_value		テンプレートファイル配置基本ディレクトリパス
	 * @return	なし
	 * @info	この設定を利用して自動的にアクセスクライアント毎にテンプレート切替を行う場合は、
	 *      	openメソッドCall前に本メソッドをCallし、テンプレートディレクトリの設定を行うこと
	 */
	public function setAccessTypeDir($p_type, $p_value) {
		$this->m_typedir[$p_type] = $p_value;
	}

	/**
	 * ガラケー用 アクセスキーの形式変換
	 * @access	private
	 * @param	string	$p_target		対象HTML文字列
	 * @return	string	変換後HTML文字列
	 * @info	ｉモード準拠のアクセスキー(accesskey)を各キャリアに対応したアクセスキーに変換する
	 */
	private function convertAccesskey($p_target) {
		define("ACCESS_KEY", " accesskey=\"([0-9|*|#])\"");

		if ($this->m_type    != TYPE_FEATURE_PHONE) return $p_target;
		if ($this->m_carrier != CARRIER_SOFTBANK)   return $p_target;
		if ($this->m_typeInf != "J-PHONE")          return $p_target;

		$style = " directkey=\"\\1\" nonumber";
		$ret = preg_replace("/" . ACCESS_KEY . "/s", $style, $p_target);
		return $ret;
	}

	/**
	 * ガラケー用 入力属性の形式変換
	 * @access	private
	 * @param	string	$p_target		対象HTML文字列
	 * @return	string	変換後HTML文字列
	 * @info	ｉモード準拠の入力属性(istyle)を各キャリアに対応した入力属性に変換する
	 *				1：かな(全角)
	 *				2：カナ(DoCoMoの場合は半角、Softbankの場合は全角)
	 *				3：英字
	 *				4：数字
	 */
	private function convertImputkey($p_target) {
		define("INPUT_KEY", " istyle=\"([%%])\"");

		if ($this->m_type    != TYPE_FEATURE_PHONE) return $p_target;
		if ($this->m_carrier == CARRIER_NAN || $this->m_carrier == CARRIER_DOCOMO || $this->m_carrier == CARRIER_WILLCOM) return $p_target;

		switch ($this->m_carrier) {
			case CARRIER_AU:
				$style = " format=";
				//##### 2009/12/04 Upd S by S.Okamoto AUの仕様により数字のみのフォーマット指定の場合、初期文字種別ではなく #####
				//#####                               入力制御されている端末があるため、英数使用に変換                     #####
				$key = array(1=>"\"*M\"", 2=>"\"*M\"", 3=>"\"*m\"", 4=>"\"*N\"");			//@@@ 2012/01/23 Rev J.Okamoto 今回のみ元に戻す
				//@@@ $key = array(1=>"\"*M\"", 2=>"\"*M\"", 3=>"\"*m\"", 4=>"\"*m\"");
				//##### 2009/12/04 Upd E
				break;
			case CARRIER_SOFTBANK:
				$style = " mode=";
				//$key = array(1=>"\"hiragana\"", 2=>"\"katakana\"", 3=>"\"alphabet\"", 4=>"\"numeric\"");
				$key = array(1=>"\"hiragana\"", 2=>"\"hankakukana\"", 3=>"\"alphabet\"", 4=>"\"numeric\"");
				break;
			default:
				$style = "";
				$key = array(1=>"", 2=>"", 3=>"", 4=>"");
				break;
		}

		$ret = $p_target;
		for ($i=1; $i<=4; $i++) {
			$ret = preg_replace("/" . str_replace("%%", (string)$i, INPUT_KEY) . "/s", $style . $key[$i], $ret);
		}
		return $ret;
	}
	//--- 2012/01/16 J.Okamoto Add End   ---
	
	/**
	 * データクリア
	 * @access	public
	 * @param	なし
	 * @return	なし
	 */
	public function clear() {
		$this->m_index = 0;
		$this->m_current = array();
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
