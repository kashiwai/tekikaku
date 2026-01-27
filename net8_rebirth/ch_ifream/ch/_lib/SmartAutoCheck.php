<?php
/*
 * SmartAutoCheck.php
 * 
 * (C)SmartRams Co.,Ltd. 2016 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * 会員管理画面表示
 * 
 * 会員管理画面の表示を行う
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since	 2019/01/30 初版作成 村上 俊行
 * @since	 2020/03/09 追加     村上 俊行 reportで返すメッセージをsetReportErrorCodeMode(true)でコード出力に変更できるように修正
 * @since	 2020/04/30 修正     SR        countSQLとnoCountSQLに$this->_nopの判定が無いので追加
 * @info
 */


class SmartAutoCheck
{

	private $_template;						//SmartTempleteClass
	private $_methods = array();

	private $_log = array();				//デバッグログ
	private $_errormsg = array();			//エラーメッセージ
	private $_nop = true;					//チェックフラグ
	private $_break = false;				//breakチェックフラグ
	private $_target = "";					//チェック対象の値
	private $_updatemode = false;			//update時検査モード
	private $_reportmode = false;			//reportコード出力モード false:エラー文字列 true:エラーコード

	/*
	 * コンストラクタ
	 * @access	public
	 * @param	string	$dbtype		SQLのDBタイプ指定
	 * @return	インスタンス
	*/
	public function __construct( $template) {
		$this->_template = $template;
		
		//初期拡張メソッドの登録
		$this->_initMethods();
		
	}

	private function _initMethods() {
		//初期拡張メソッド登録

		//SmartCheckerの別名定義
		$this->addMethod( "number",  					"chk_number", 1 );
		$this->addMethod( "numberEx",					"chk_numberEx", 1 );
		$this->addMethod( "numeric",					"chk_numeric", 1 );
		$this->addMethod( "mail",						"chk_mail", 1 );
		$this->addMethod( "url",						"chk_url", 1 );
		$this->addMethod( "isSyll",						"chk_syll", 2 );
		$this->addMethod( "alnum"	,					"chk_alnum", 2 );
		$this->addMethod( "date",						"chk_date", 1 );
		$this->addMethod( "tel",						"chk_tel", 2 );
		$this->addMethod( "postal",						"chk_postal", 2 );
		$this->addMethod( "bytelength",					"chk_byte", 2 );
		$this->addMethod( "allFullWidthCharacter",		"chkAllFullWidthCharacter", 1 );
		$this->addMethod( "allHalfWidthCharacter",		"chkAllHalfWidthCharacter", 1 );
		//SmartCheckerの関数名どおりの設定（引数は 先頭がエラーメッセージ, パラメータ２の順になるので注意）
		$this->addMethod( "chk_number",  				"chk_number", 1 );
		$this->addMethod( "chk_numberEx",				"chk_numberEx", 1 );
		$this->addMethod( "chk_numeric",				"chk_numeric", 1 );
		$this->addMethod( "chk_mail",					"chk_mail", 1 );
		$this->addMethod( "chk_url",					"chk_url", 1 );
		$this->addMethod( "chk_syll",					"chk_syll", 2 );
		$this->addMethod( "chk_alnum",					"chk_alnum", 2 );
		$this->addMethod( "chk_date",					"chk_date", 1 );
		$this->addMethod( "chk_tel",					"chk_tel", 2 );
		$this->addMethod( "chk_postal",					"chk_postal", 2 );
		$this->addMethod( "chk_byte",					"chk_byte", 2 );
		$this->addMethod( "chkAllFullWidthCharacter",	"chkAllFullWidthCharacter", 1 );
		$this->addMethod( "chkAllHalfWidthCharacter",	"chkAllHalfWidthCharacter", 1 );
		$this->addMethod( "chkNumberAndAlpha",			"chkNumberAndAlpha", 1);
		$this->addMethod( "chk_alpha",					"chk_alpha", 1);
		
		$this->addMethod( "chk_mobile",					"chk_mobile", 2 );
		
		return;
	}

	public function addMethod( $name, $callary, $argcnt ) {
		$this->_methods[$name] = ["func"=>$callary,"argc"=>$argcnt];
		return( $this );
	}

	public function item( $value="@notarget@" ) {
		//チェック対象の値を設定
		$this->_target = $value;
		//エラーフラグを戻す
		if ( !$this->_break ) $this->_nop = true;

		$this->_addLog( "item[{$value}]" );
		return( $this );
	}

	// 2020/03/09 追加 
	public function setReportErrorCodeMode($flg){
		$this->_reportmode = $flg;
		return( $this );
	}

	public function report( $arrayOutput=true ) {
		if ( !$arrayOutput ) {
			return( implode( " ", $this->_errormsg ) );
		} else {
			return( $this->_errormsg );
		}
	}

	private function _addErrorMessage( $no, $param="", $add="" ) {
		//エラーフラグを立てる
		$this->_nop = false;
		//メッセージを設定
		if ( $this->_reportmode == false ){
			$this->_errormsg[] = $this->_template->message( $no, $param, $add );
		} else {
			$this->_errormsg[] = $no;
		}
	}

	/*
	 * __callマジックメソッド 
	 * @access	public
	 * @param	string	$dbtype		SQLのDBタイプ指定
	 * @return	インスタンス
	*/
	public function __call($method, $argument) {
		if ( array_key_exists( $method, $this->_methods ) ){
			//既にエラーの場合はスキップ
			if ( !$this->_nop ) return( $this );
			//method登録されているものを実行
			$funcArgument = $argument;
			$errorno = array_shift( $funcArgument );
			$callargv = array( $this->_target );
//				print $this->_methods[$method]["argc"] . "\n";
			for($i=0;$i<$this->_methods[$method]["argc"]-1;$i++){
//				print "{$i} {$this->_methods[$method]["argc"]}";
				if ( @count($funcArgument) > 0 ){
					$callargv[] = array_shift( $funcArgument );
				}
			}
//			print "func:";
//			var_dump( $this->_methods[$method]["func"] );
//			var_dump( $callargv );
			if ( call_user_func_array($this->_methods[$method]["func"], $callargv) ){
				//正常
				$status = true;
			} else {
				//異常
				$this->_addErrorMessage( $errorno );
				$status = false;
			}
			$this->_addLog( $method, $status, $errorno, $callargv );
			return( $this );
		} else {
			print "no method:{$method}\n";
		}
		return ( $this );
	}

	public function debug() {
		$messages = implode( "<br>\n", $this->_log );
		print $messages;

		return( $this );
	}
	private function _addLog( $method, $status=true, $errorno="", $arg="" ) {
		if ( is_array( $arg ) ) {
			$strArg = print_r( $arg, true );
		} else {
			$strArg = $arg;
		}
		if ( $status ){
			$message = "->{$method}({$strArg}) ... OK";
		} else {
			$message = "->{$method}({$strArg}) ... NG";
			if ( $errorno != "" ) $message .= "[{$errorno}]";
		}
		$this->_log[] = $message;
	}

	public function setUpdateMode( $exp ) {
		if ( $exp ){
			$this->_updatemode = true;
		} else {
			$this->_updatemode = false;
		}
		$this->_addLog( "setUpdateMode", $this->_updatemode );

		return( $this );
	}


	//====== 制御系関数
	public function break() {
		if ( @count($this->_errormsg) > 0 ){
			$this->_nop = false;
			$this->_break = true;
		}
		$this->_addLog( "break", $this->_break );

		return( $this );
	}

	public function case( $exp ) {
		//チェック処理
		if ( !$exp ){
			$this->_nop = false;
		} else {
			$this->_nop = true;
		}
		$this->_addLog( "case", $this->_nop, "", $exp );

		//チェーンメソッド用return
		return( $this );
	}

	public function if( $errorno, $exp ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( !$exp ){
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		//2020-06-24 Notice対応
		$this->_addLog( "if", $status, $errorno, $exp );

		//チェーンメソッド用return
		return( $this );
	}

	public function isUpdate() {
		//チェック処理
		if ( $this->_updatemode ){
			$this->_nop = true;
		} else {
			$this->_nop = false;
		}
		$this->_addLog( "isUpdate", $this->_nop );

		//チェーンメソッド用return
		return( $this );
	}

	public function isInsert() {
		//チェック処理
		if ( $this->_updatemode ){
			$this->_nop = false;
		} else {
			$this->_nop = true;
		}
		$this->_addLog( "isInsert", $this->_nop );

		//チェーンメソッド用return
		return( $this );
	}

	//====== エラーチェック関数群
	public function any() {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( mb_strlen( $this->_target ) == 0 ){
			$this->_nop = false;
			$status = false;
		}
		$this->_addLog( "any", $status );
		//チェーンメソッド用return
		return( $this );
	}
	public function required( $errorno ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( mb_strlen( $this->_target ) == 0 ){
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		$this->_addLog( "required", $status, $errorno );
		//チェーンメソッド用return
		return( $this );
	}
	public function eq( $errorno, $value ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( $this->_target != $value ){
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		$this->_addLog( "eq", $status, $errorno, $value );
		//チェーンメソッド用return
		return( $this );
	}

	public function not( $errorno, $value ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( $this->_target == $value ){
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		$this->_addLog( "not", $status, $errorno, $value );
		//チェーンメソッド用return
		return( $this );
	}


	public function inArray( $errorno, $ary ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( is_array($ary) ){
//			if ( !in_array("{$this->_target}", $ary ) ){
			if ( !array_key_exists( "{$this->_target}", $ary ) ){
				$this->_addErrorMessage( $errorno );
				$status = false;
			}
		} else {
			if ( $this->_target != $ary ){
				$this->_addErrorMessage( $errorno );
				$status = false;
			}
		}
		$this->_addLog( "inArray", $status, $errorno, $ary );
		
		//チェーンメソッド用return
		return( $this );
	}


	public function length( $errorno, $ary ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( is_array($ary) ){
			$length = mb_strlen( $this->_target );
			if ( $length < $ary[0] || $length > $ary[1] ) {
				$this->_addErrorMessage( $errorno );
				$status = false;
			}
		} else {
			if ( mb_strlen( $this->_target ) != $ary ){
				$this->_addErrorMessage( $errorno );
				$status = false;
			}
		}
		$this->_addLog( "length", $status, $errorno, $ary );

		//チェーンメソッド用return
		return( $this );
	}
	public function minLength( $errorno, $length ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( mb_strlen( $this->_target ) < $length ){
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		//2020-06-24 Notice対応
		$this->_addLog( "minLength", $status, $errorno, array() );
		//$this->_addLog( "minLength", $status, $errorno, $ary );

		//チェーンメソッド用return
		return( $this );
	}

	public function maxLength( $errorno, $length ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( mb_strlen( $this->_target ) > $length ){
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		//2020-06-24 Notice対応
		$this->_addLog( "maxLength", $status, $errorno, array() );
		//$this->_addLog( "maxLength", $status, $errorno, $ary );

		//チェーンメソッド用return
		return( $this );
	}

	public function password_verify( $errorno, $pass ) {
		if ( !$this->_nop ) return( $this );
		//チェック処理
		$status = true;
		if ( !password_verify( $this->_target, $pass ) ) {
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		$this->_addLog( "password_verify", $status, $errorno, $pass );

		//チェーンメソッド用return
		return( $this );
	}

	public function countSQL( $errorno, $sql ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );	// 2020/04/30 [ADD]
		$cnt = $this->_template->DB->getOne($sql);
		//チェック処理
		$status = true;
		if ( $cnt > 0 ) {
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		$this->_addLog( "countSQL", $status, $errorno, $sql );

		//チェーンメソッド用return
		return( $this );
	}

	public function noCountSQL( $errorno, $sql ) {
		//既にエラーがある場合はチェックしない
		if ( !$this->_nop ) return( $this );	// 2020/04/30 [ADD]
		$cnt = $this->_template->DB->getOne($sql);
		//チェック処理
		$status = true;
		if ( $cnt == 0 ) {
			$this->_addErrorMessage( $errorno );
			$status = false;
		}
		$this->_addLog( "noCountSQL", $status, $errorno, $sql );

		//チェーンメソッド用return
		return( $this );
	}


}
?>
