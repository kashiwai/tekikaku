<?php
/*
 * Logger.php
 * 
 * (C)SmartRams Corp. 2008 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * Logger
 * 
 * ログを出力
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since	 2019/02/27 初版作成 村上俊行
 * @info
 */

define( "LOGGER_DEBUG",    "10" );
define( "LOGGER_INFO",     "20" );
define( "LOGGER_WARNING",  "30" );
define( "LOGGER_ERROR",    "40" );
define( "LOGGER_CRITICAL", "50" );

define( "LOGGER_ECHO",     "@echo" );						//標準出力
define( "LOGGER_HTML",     "@html" );						//html画面への出力

class Logger {
	private $_handle;										//fpなどの接続情報
	private $_options = array();

	public $LEVEL = array(
		 "10" => "DEBUG"
		,"20" => "INFO"
		,"30" => "WARNING"
		,"40" => "ERROR"
		,"50" => "CRITICAL"
	);

	/**
	 * コンストラクタ
	 * @access  public
	 * @param	string	$handle			出力先のpath名（または '@html' '@echo'）
	 * @return  						インスタンス
	 */
	public function __construct( $handle, $options="" ) {
		$this->init( $handle, $options );
//		$this->start();
	}

	/**
	 * 初期化処理
	 * @access  public
	 * @return  						インスタンス
	 */
	public function init( $handle, $options="" ) {
		if ( is_array($options) ){
			$this->options = $options;
			$this->options["handle"] = $handle;
		} else {
			$this->_options = array(
				 "nowDate"    => Date("Y-m-d H:i:s")
				,"level"      => "20"
				,"format"     => "{%date%} [{%level%}] {%filename%} {%function%} ({%line%}) {%message%}"
				,"handle"     => $handle
				,"critical"   => null
				,"error"      => null
			);
		}
		
		return($this);
	}

	/**
	 * デストラクタ
	 * @access  public
	 * @return  						なし
	 */
	public function __destruct(){
//		$this->end();
	}

	public function start() {
		if ( $this->_options["handle"] == LOGGER_ECHO || $this->_options["handle"] == LOGGER_HTML ){
			$this->_handle = $this->_options["handle"];
		} else if ( $this->_options["handle"] == "" ){
			$this->_handle = null;
		} else {
			if ( ($this->_handle = @fopen( $this->_options["handle"], "a" )) == false ){
				print "handle error {$handle}";
			}
		}
		
		return( $this );
	}

	public function end() {
		if ( $this->_handle != LOGGER_ECHO && $this->_handle != LOGGER_HTML ) {
			@fclose( $this->_log );
		}
		return( $this );
	}

	/**
	 * level設定
	 * @access  public
	 * @param	string	$level				ログ出力レベルの設定
	 * @return  							インスタンス
	 */
	public function level( $level="" ) {
		if ( $level == "" ) return( $this->_options["level"] );
		$this->_options["level"] = $level;
		return( $this );
	}

	/**
	 * format設定
	 * @access  public
	 * @param	string	$format				ログフォーマット
	 * @return  							インスタンス
	 */
	public function format( $format="" ) {
		if ( $format == "" ) return( $this->_options["format"] );
		$this->_options["format"] = $format;
		return( $this );
	}

	/* 機能させていない */
	public function callback( $name, $callback ) {
		$this->_options[$name] = $callback;
		return( $this );
	}


	/**
	 * debugメッセージ出力
	 * @access  public
	 * @param	可変	$value				出力する値、配列の場合はprint_rで出力される
	 * @return  							インスタンス
	 */
	public function debug( $value="" ) {
		$this->outputMessage( LOGGER_DEBUG, $value );
		return( $this );
	}
	/**
	 * infoメッセージ出力
	 * @access  public
	 * @param	可変	$value				出力する値、配列の場合はprint_rで出力される
	 * @return  							インスタンス
	 */
	public function info( $value="" ) {
		$this->outputMessage( LOGGER_INFO, $value );
		return( $this );
	}
	/**
	 * warningメッセージ出力
	 * @access  public
	 * @param	可変	$value				出力する値、配列の場合はprint_rで出力される
	 * @return  							インスタンス
	 */
	public function warning( $value="" ) {
		$this->outputMessage( LOGGER_WARNING, $value );
		return( $this );
	}
	/**
	 * errorメッセージ出力
	 * @access  public
	 * @param	可変	$value				出力する値、配列の場合はprint_rで出力される
	 * @return  							インスタンス
	 */
	public function error( $value="" ) {
		$this->outputMessage( LOGGER_ERROR, $value );
		return( $this );
	}
	/**
	 * criticalメッセージ出力
	 * @access  public
	 * @param	可変	$value				出力する値、配列の場合はprint_rで出力される
	 * @return  							インスタンス
	 */
	public function critical( $value="" ) {
		$this->outputMessage( LOGGER_CRITICAL, $value );
		return( $this );
	}


	/**
	 * メッセージ出力
	 * @access  public
	 * @param	int		$level				ログレベル
	 * @param	可変	$value				出力する値、配列の場合はprint_rで出力される
	 * @return  							インスタンス
	 */
	public function outputMessage( $level, $value="" ) {
		//ログレベルが低い場合は記録しない
		if ( $this->_options["level"] > $level ) return( $this );
		if ( $this->_handle == null ) return( $this );
		$trace = $this->_getOptions( debug_backtrace() );
		$rec = $this->_buildMessage( $level, $value, $trace["file"], $trace["line"], $trace["function"] );
		if ( $this->_options["handle"] == LOGGER_ECHO ){
			echo $rec."\n";
		} else if ( $this->_options["handle"] == LOGGER_HTML ) {
			echo "<pre>{$rec}</pre>";
		} else {
			$this->_handle = @fopen( $this->_options["handle"], "a" );
			if ( $this->_handle ){
				fputs($this->_handle, $rec."\n" );
				fclose($this->_handle );
			}
		}
		return( $this );
	}

	/**
	 * trace情報から必要な情報を取得
	 * @access  public
	 * @param	array	$trace			debug_backtraceの戻り値
	 * @return  array					ログに必要なfile,function,行番号,classなどの情報を連想配列で返す
	 */
	private function _getOptions( $trace ) {
		$ary = array();
		$saveTrace = array();
		foreach( $trace as $idx => $traceArray ) {
			if ( array_key_exists("class", $traceArray) ) {
				if ( $traceArray["class"] == "Logger" || $traceArray["class"] == "SystemLogger") continue;
			}
			$backArray = $trace[$idx-1];
			$ary["file"] = $traceArray["file"];
			$ary["function"] = $traceArray["function"];
			$ary["line"] = $backArray["line"];
			$ary["class"] = $backArray["class"];
			break;
		}
		
		return( $ary );
	}

	/**
	 * ログメッセージの作成
	 * @access  public
	 * @param	int		$level			ログレベル
	 * @param	可変	$value			出力する値、配列の場合はprint_rで出力される
	 * @param	string	$filename		実行ファイル名
	 * @param	string	$line			行番号
	 * @param	string	$func			ファンクション名
	 * @return  array					ログに必要なfile,function,行番号,classなどの情報を連想配列で返す
	 */
	private function _buildMessage( $level, $value="", $filename="", $line="", $func="" ) {
		$rec = $this->_options["format"];
		if ( is_array( $value ) ){
			$message = print_r( $value, true );
		} else {
			$message = $value;
		}
		$rec = str_replace( "{%date%}",        $this->_options["nowDate"], $rec );
		$rec = str_replace( "{%level%}",       $this->LEVEL[$level], $rec );
		$rec = str_replace( "{%filename%}",    $filename, $rec );
		$rec = str_replace( "{%line%}",        $line, $rec );
		$rec = str_replace( "{%function%}",    $func, $rec );
		//$rec = str_replace( "{%classname%}",   $classname, $rec );
		$rec = str_replace( "{%classname%}",   "", $rec );
		$rec = str_replace( "{%message%}",     $message, $rec );
		
		return( $rec );
	}

}




class SystemLogger {

	public  $handler = array();				//handlerリスト
	private $_options = array();				//optionsリスト
	
	private $selectHandler = array();				//選択中のハンドラー

	/**
	 * コンストラクタ
	 * @access  public
	 * @return  						インスタンス
	 */
	public function __construct( $setting="" ) {
		//初期化
		if ( $setting == "" ) {
			
		} else {
		
		}
		$this->init();
	}

	/**
	 * デストラクタ
	 * @access  public
	 * @return  						なし
	 */
	public function __destruct(){

	}

	/**
	 * 初期化処理
	 * @access  public
	 * @return  						インスタンス
	 */
	public function init() {
		$this->_options = array(
			 "nowDate"    => Date("Y-m-d H:i:s")
			,"level"      => "20"
			,"handle"     => array("@html")
			,"critical"   => null
			,"error"      => null
		);
		
		return($this);
	}

	/**
	 * 出力ハンドルの追加
	 * @access	private
	 * @param	number	$level		ログレベル： LOGGER_INFO
	 * @return	なし
	 */
	public function addHandle( $handleName, $handle ) {
		$this->handler[$handleName] = new Logger($handle);
		$this->handler[$handleName]->start();
		
		return( $this );
	}

	public function select( $handleName ) {
		if ( is_array($handleName) ) {
			$this->selectHandler = $handleName;
		} else {
			$this->selectHandler = array( $handleName );
		}
		return( $this );
	}

	public function debug( $value="" ) {
		$this->outputMessage( LOGGER_DEBUG, $value );
		return( $this );
	}
	public function info( $value="" ) {
		$this->outputMessage( LOGGER_INFO, $value );
		return( $this );
	}
	public function warning( $value="" ) {
		$this->outputMessage( LOGGER_WARNING, $value );
		return( $this );
	}
	public function error( $value="" ) {
		$this->outputMessage( LOGGER_ERROR, $value );
		return( $this );
	}
	public function critical( $value="" ) {
		$this->outputMessage( LOGGER_CRITICAL, $value );
		return( $this );
	}

	public function outputMessage( $level, $value="" ) {
		if ( count($this->selectHandler) > 0 ){
			foreach( $this->selectHandler as $handleName ) {
				$this->handler[$handleName]->outputMessage( $level, $value );
			}
			$this->selectHandler = array();
		} else {
			foreach( $this->handler as $handler ) {
				$handler->outputMessage( $level, $value );
			}
		}
		return( $this );
	}


}

?>