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


class Logger {

	private $_nowDate;										//現在の日時
	private $_level;										//ログの出力レベル
	private $_handle = array();							//logのfp
	private $_format = "{%date%} [{%level%}] {%filename%} {%function%} ({%line%}) {%message%}";
	
	private $_options = array(
		 "nowDate"    => null
		,"level"      => "20"
		,"handle"     => array("php://stdout")
		,"critical"   => null
		,"error"      => null
	);
	
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
	 * @return  						インスタンス
	 */
	public function __construct( $setting="" ) {
		//初期化
		if ( $setting == "" ) {
			
		} else {
		
		}
		$this->init();
		$this->start();
	}

	/**
	 * デストラクタ
	 * @access  public
	 * @return  						なし
	 */
	public function __destruct(){
		$this->end();
	}

	/**
	 * 初期化処理
	 * @access  public
	 * @return  						インスタンス
	 */
	public function init() {
		$this->_options["nowDate"] = Date("Y-m-d H:i:s");
		$this->_nowDate = Date("Y-m-d H:i:s");
		return($this);
	}

	/**
	 * ログレベルの設定
	 * @access	private
	 * @param	number	$level		ログレベル： $LOG->INFO
	 * @return	なし
	 */
	public function setLevel( $level ){
		$this->level = $level;
		return( $this );
	}

	public function start( $path ) {
		foreach( $this->_options["handle"] as $idx => $handle ){
			if ( ($this->_handle[$idx] = @fopen( $handle, "a" )) == false ){
				print "handle error {$handle}";
			}
		}
		
		return( $this );
	}

	public function end() {
		@fclose( $this->_log );
		return( $this );
	}

	public function debug( $value="", $filename="", $line="", $func="" ) {
		$this->outputMessage( "10", $value, $filename, $line, $func );
		return( $this );
	}
	public function info( $value="", $filename="", $line="", $func="" ) {
		$this->outputMessage( "20", $value, $filename, $line, $func );
		return( $this );
	}
	public function warning( $value="", $filename="", $line="", $func="" ) {
		$this->outputMessage( "30", $value, $filename, $line, $func );
		return( $this );
	}
	public function error( $value="", $filename="", $line="", $func="" ) {
		$this->outputMessage( "40", $value, $filename, $line, $func );
		return( $this );
	}
	public function critical( $value="", $filename="", $line="", $func="" ) {
		$this->outputMessage( "50", $value, $filename, $line, $func );
		return( $this );
	}


	public function outputMessage( $level, $value="", $filename="", $line="", $func="" ) {
		//ログレベルが低い場合は記録しない
		if ( $this->_options["level"] > $level ) return( $this );
		$rec = $this->_buildMessage( $level, $value, $filename, $line, $func );
		foreach( $this->_handle as $handle ){
			@fputs($handle, $rec );
		}
		return( $this );
	}

	private function _buildMessage( $level, $value="", $filename="", $line="", $func="" ) {
		$rec = $this->_format;
		$rec = str_replace( "{%date%}",  $this->_nowDate, $rec );
		$rec = str_replace( "{%level%}", $this->LEVEL[$level], $rec );
		$rec = str_replace( "{%filename%}", $filename, $rec );
		$rec = str_replace( "{%line%}", $line, $rec );
		$rec = str_replace( "{%function%}", $func, $rec );
		$rec = str_replace( "{%classname%}", $classname, $rec );
		$rec = str_replace( "{%message%}", $value, $rec );
		
		return( $rec );
	}

}

?>