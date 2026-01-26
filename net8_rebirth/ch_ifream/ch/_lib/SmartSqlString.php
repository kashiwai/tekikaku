<?php
/*
 * SmartSqlString.php
 * 
 * (C)SmartRams Corp. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * SQL文字列編集クラス
 * 
 * SQLをメソッドチェーンで記述することで可読性を上げたSQLを記述できる
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since	 2019/01/29 初版作成 村上 俊行
 * @since	 2019/02/14 追加     村上 俊行 condition and or メソッドでのbetween対応
 * @since	 2019/02/15 追加     村上 俊行 排他用にforUpdateメソッドを作成
 * @since	 2019/03/05 追加     村上 俊行 having句の追加
 * @since	 2019/03/07 追加     村上 俊行 select2insert（select結果をinsertするモード）追加
 * @since	 2019/03/22 追加     村上 俊行 update句にlimitが設定できるように修正
 * @since	 2019/04/16 修正     村上 俊行 groupStart,groupEndメソッドの括弧処理のバグ修正
 * @since	 2019/06/27 修正     村上 俊行 コンストラクタに$DBを指定することでsetAutoConvertを省略可能に修正
 * 											selectでfrom句の指定がなくても作成ができるように修正
 * 											and,orメソッドなどの先頭true指定を定数：SQL_CUTを指定できるように変更
 * 											condition で and('fld in ', [1,2,3], FD_NUM)とするとin指定できるよう修正
 *											delete句が使えるように変更
 * @since	 2020/05/01 修正     村上 俊行 betweenのnull値の判定を修正
 * @since	 2020/06/25 修正     村上 俊行 Notice対応
 * @since	 2020/07/08 修正     村上 俊行 Notice対応
* @info
 */



//書き方
//-------------------------------------------------------------
/*

2019-06-27下記の記述を
$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )

このように省略できるようにしました
$sql = (new SqlString($template->DB))

※コンストラクタで->setAutoConvert( [$template->DB,"conv_sql"] )が自動で実行されています



 * 1回でSQL文を作成する場合(php7でないと記述できません）
$sql = (new SqlString($template->DB))
			->select()
				->field("id")
				->from("mst_member")
				->where()
					->and("id=1")
			->createSQL();

 * count処理などでwhere内容を使いまわしたい場合

//インスタンスを作成
$sqls = new SqlString($template->DB);
//カウントSQL
$_sql = $sqls
			->select()
				->field("count(*)")
				->from("mst_member")
				->where()
					->and("name=",[%,$_GET["NAME"],%], FD_STR)
				->orderby("id")
				->page($_GET["P"], ADMIN_LIST_ROWMAX)
			->createSQL();

//カウントSQLの設定からfield設定をresetしてfieldを再設定、orderbyとpage(limit)を追加して再作成
$sql = $sqls
			->resetField()
				->field("id,name")
				->orderby("id")
				->page($_GET["P"], ADMIN_LIST_ROWMAX)
			->createSQL();


//select文
	//select文を設定
	->select()

	//field設定（複数対応）
		->field( "id,name" )
		->field( "mail" )				//2回目以降も記述するとSQL作成時に,で繋がる
	//from設定
		->from( "mst_member m" )
		->join( "left", "dat_member d", "m.id = d.id")
		->from( "left join dat_member d on m.id=d.id" )		//上記の->joinと同じ意味です
	//where設定
		->where()									//where開始の宣言
			->and( "id=1234")						//固定の場合は引数1つでも記述できます
			->and( "id=", $_GET["ID"], FD_NUM )		//conv_sqlを通して id=??? という条件を作成します
			->and( SQL_CUT, "id=", $_GET["ID"], FD_NUM)	//$_GET["ID"]が空欄の場合にはこの行がスキップされます
			->and( SQL_CUT, "name like", ["%",$_GET["NAME"], "%"], FD_STR)
													//ワイルドカードなどは [ ]で配列を渡すことで処理します
													//※ [] は　php7での array()の短縮記述です
			->and( SQL_CUT, "name in", ["1","2","3"], FD_NUM)
													// name in (1,2,3)が作成されます
			->or("id=", $_GET["ID"], FD_NUM)		//or条件はメソッドを変えます
			->groupStart()							//括弧をつける場合はgroupStart()とgroupEnd()で囲みます
				->and( SQL_CUT, "age>=", $_GET["FROM"], FD_NUM)
				->and( SQL_CUT, "age<=", $_GET["TO"], FD_NUM)
			->groupEnd()
			
			//aa=1 or ( ... ) のような括弧にしたい場合は下記のようにします
			->groupStart("or")

			//start endの間の条件がスキップされた場合は ()もつかないです
			

			//--- 2019/02/14 between 対応 ->or も同様です。
			->and( SQL_CUT, fieldname, "between", min, min_type, max, max_type )
			//先頭を省略またはfalse指定にした場合にmin,maxどちらかの値が空欄の時は不等号の条件式が生成されます。
			//下記の場合は fieldname <= 50 と生成されます
			->and( fieldname, 'between', "", FD_NUM, 50, FD_NUM )
			//下記の場合は not between なので範囲外ということで fieldname > 50 と生成されます
			->and( fieldname, 'not between', "", FD_NUM, 50, FD_NUM )
			//fieldnameに値を使い、値にフィールド名を使う場合は下記のように記述します。
			->and( $template->DB->conv_sql($value, FD_STR), 'between', "field1", FD_FUNCTION, "field2", FD_FUNCTION )

			
			//subQuery条件式は下記のようになります。
			->subQuery("camera_no",
						(new SqlString($DB))
							->select()
								->field("camera_no")
								->from("mst_camera")
								->where()
									->and( "camera_mac =", $_GET["MAC"], FD_STR)
									->and( "del_flg =",    "0", FD_NUM)
							->createSQL()
			)

	//group by設定（複数対応）
		->groupby( "group" )

	//having句の設定
		->having("id=1234")							//単一の場合はこれでOK
		
		->having()									//複数記述する場合はwhereと同じ記述方法で記述できます
			->and("id=1234")

	//order by設定（複数対応）
		->orderby( "id asc" )						//orderbyに書く記述でそのまま書きます
	//limit設定
		->page( $_POST["P"], MAX_LINE )				//ページ数と行数を送ることで自動でlimitを作ります。

	//lock --- 2019/02/15 追加
		->forUpdate()								//排他ロック（必ずトランザクション下で実行するようにすること）

//select2insert
	//insert into A (...) select ... from B where .... のような記述をする場合
	->select2insert()
		->into("table1")
			->tofield("insertフィールド", "selectフィールド")
			->tofield("insertフィールド", "値", FD_STR)
			->tofield("insertフィールド", "関数", FD_FUNCTION)
		->from("table2")
		->where()
		以降はselectと同様

		※ ->select()は記述不要（記述するとselectとして実行されるがfield設定がないのでSQLとしては不十分である）


//insert文
	//insert文を設定
	->insert()
	
	//table設定
		->into("mst_member"							//insertするテーブルを指定)
	//値の設定（複数対応）
			->value("name", $_POST["NAME"], FD_STR)	//フィールド名,値,型を指定していきます
													//自動で (name) values (値)　に変換されます
			->value("upd_dt", "current_timestamp", FD_FUNCTION)
													//mysql独自関数やラベルの場合は直接記述して FD_FUNCTIONを指定して下さい


//update文
	//update宣言と更新するテーブルを指定
	->update("mst_member")

	//set句の開始宣言
		->set()
	//値の設定（複数対応）
			->value("name", $_POST["NAME"], FD_STR)	//フィールド名,値,型を指定していきます

	//where設定（selectと同じ）

//delete文
	->delete()
	->from("mst_member")
	->where()
		->and( "id=1234")



*/
//-------------------------------------------------------------


/*
	PHP7以上(PHP5不可）

*/

define( "SQL_CUT", true );						//valueがnullの時にその構文をカットするフラグ名

class SqlString 
{

	private $_addValue = "";
	// メンバ変数定義
	private $_dbtype = "";						//Databaseタイプ mysql or pgsql
	private $template = null;					//$template->DB->conv_sqlをのコールバック設定

	private $_sql;								//createSQLで生成したSQL
	private $_type;								//select insert update delete 
	private $_table;							//テーブル設定( insert )
	private $_fields = array();					//select のフィールド設定
	private $_from = array();					//select delete の from 句
	private $_exp = array();					//記述中のconditionを　and or のどちらで繋ぐかの設定
	private $_where = array();					//where設定
	private $_having = array();					//having設定
	private $_orderby = array();				//order by 設定
	private $_groupby = array();				//group by 設定
	private $_lock = array();					//lock設定
	private $_offset = -1;						//offset設定
	private $_limit = -1;						//limit設定
	private $_values = array();					//insert update で使用する値設定
	private $_havingFlg = false;				//Having句処理かwhere句処理かの判定
	/*
	 * コンストラクタ
	 * @access	public
	 * @param	object	$DB			DBオブジェクト
	 * @param	string	$dbtype		SQLのDBタイプ指定
	 * @return	インスタンス
	*/
	public function __construct( $DB=null, $dbtype="mysql") {
		$this->_dbtype = $dbtype;
		//$DBの指定がある場合はAutoConvertをデフォルトセットする
		if ( $DB != null ){
			$this->setAutoConvert([$DB,"conv_sql"]);
		}
	}
	/**
	 * 設定のリセット
	 * @access	public
	 * @return	なし
	 * @info	最初からやり直す為の設定リセット
	 */
	private function _reset() {
		$this->_sql;
		$this->_type;
		$this->_table;
		$this->_fields = array();
		$this->_from = array();
		$this->_exp = array();
		$this->_exp_hv = array();
		$this->_where = array();
		$this->_groupby = array();
		$this->_orderby = array();
		$this->_offset = -1;
		$this->_limit = -1;
		$this->_values = array();
		$this->_havingFlg = false;
	}

	/**
	 * フィールド設定のリセット
	 * @access	public
	 * @return	インスタンス
	 * @info	フィールド設定のみリセット
	 */
	public function resetField() {
		$this->_fields = array();
		return( $this );
	}
	/**
	 * group by句設定のリセット
	 * @access	public
	 * @return	インスタンス
	 * @info	group by句設定のみリセット
	 */
	public function resetGroupby() {
		$this->_groupby = array();
		return( $this );
	}

	/**
	 * order by句設定のリセット
	 * @access	public
	 * @return	インスタンス
	 * @info	order by句設定のみリセット
	 */
	public function resetOrderby() {
		$this->_orderby = array();
		return( $this );
	}
	
	/**
	 * limit offset句設定のリセット
	 * @access	public
	 * @return	インスタンス
	 * @info	limit offset句設定のみリセット
	 */
	public function resetLimit() {
		$this->limit = null;
		$this->offset = null;
		return( $this );
	}
	
	/**
	 * encode関数の設定
	 * @access	public
	 * @param	array	$func		encode関数 array(class,method)で記述
	 * @return	インスタンス
	 * @info	$template->DB->conv_sqlを自動で使うため array( $template->DB, "conv_sql" ) として設定する
	 */
	public function setAutoConvert( $func ) {
		$this->template = $func;
		return( $this );
	}

	/**
	 * select句の開始
	 * @access	public
	 * @return	インスタンス
	 * @info	select句の開始
	 */
	public function select( $option="" ) {
		$this->_reset();
		$this->_type = "select";
		return( $this );
	}

	/**
	 * select2insertの開始  insert into a (...) select ... from b 
	 * @access	public
	 * @return	インスタンス
	 * @info	select2insertの開始
	 */
	public function select2insert( $option="" ) {
		$this->_reset();
		$this->_type = "select2insert";
		return( $this );
	}

	/**
	 * insert句の開始
	 * @access	public
	 * @return	インスタンス
	 * @info	select句の開始
	 */
	public function insert( $option="" ) {
		$this->_reset();
		$this->_type = "insert";
		return( $this );
	}

	/**
	 * update句の開始
	 * @access	public
	 * @param	string	$table		updateするテーブル
	 * @return	インスタンス
	 * @info	update句の開始
	 */
	public function update( $table ) {
		$this->_reset();
		$this->_type = "update";
		$this->_from[] = $table;
		return( $this );
	}

	/**
	 * delete句の開始
	 * @access	public
	 * @return	インスタンス
	 * @info	delete句の開始
	 */
	public function delete( $option="" ) {
		$this->_reset();
		$this->_type = "delete";
		return( $this );
	}

	/**
	 * set句の開始
	 * @access	public
	 * @return	インスタンス
	 * @info	明示的な記述で処理はしていない
	 */
	public function set(){
		return( $this );
	}

	/**
	 * into句の開始
	 * @access	public
	 * @param	string	$value		insert対象のテーブル
	 * @return	インスタンス
	 * @info	into に記述するテーブル名の設定
	 */
	public function into( $value ) {
		$this->_table = $value;
		return( $this );
	}

	/**
	 * value設定
	 * @access	public
	 * @param	string	$fieldname	フィールド名
	 * @param	string	$value		設定する値
	 * @param	intger	$type		データの型（FD_NUM FD_STR等）初期値 FD_STR(文字列）
	 * @return	インスタンス
	 * @info	insert update で使用する値を設定
	 */
	public function value() {
		$arg_num = func_num_args();
		if ( $arg_num == 1 ) {
			//処理なし
			return( $this );
		} else if ( $arg_num == 2 ) {
			$flg = false;
			$fieldname  = func_get_arg(0);
			$value      = func_get_arg(1);
			$type       = FD_STR;
		} else if ( $arg_num == 3 ){
			$flg = false;
			$fieldname  = func_get_arg(0);
			$value      = func_get_arg(1);
			$type       = func_get_arg(2);
		} else {
			$flg        = func_get_arg(0);
			$fieldname  = func_get_arg(1);
			$value      = func_get_arg(2);
			$type       = func_get_arg(3);
		}

		if ( !$this->_isNull( $value, $flg ) ){
			if ( is_array($this->template) ){
				$sqlvalue = call_user_func( $this->template, $value, $type );
			} else {
				$sqlvalue = $value;
			}
			$this->_fields[] = $fieldname;
			$this->_values[] = $sqlvalue;
		}
		return( $this );
	}

	/**
	 * tofield設定
	 * @access	public
	 * @param	string	$fieldname	フィールド名
	 * @param	string	$value		設定する値
	 * @param	intger	$type		データの型（FD_NUM FD_STR等）初期値 FD_STR(文字列）
	 * @return	インスタンス
	 * @info	insert update で使用する値を設定
	 */
	public function tofield() {
		$arg_num = func_num_args();
		if ( $arg_num == 1 ) {
			//処理なし
			return( $this );
		} else if ( $arg_num == 2 ) {
			$fieldname  = func_get_arg(0);
			$value      = func_get_arg(1);
			$type       = -1;
		} else if ( $arg_num == 3 ){
			$fieldname  = func_get_arg(0);
			$value      = func_get_arg(1);
			$type       = func_get_arg(2);
		}
		//2020-07-08 Notice対応（toFieldは省略形式を指定できないので常にfalse)
		$flg = false;

		if ( $type >= 0 ){
			if ( !$this->_isNull( $value, $flg ) ){
				if ( is_array($this->template) ){
					$sqlvalue = call_user_func( $this->template, $value, $type );
				} else {
					$sqlvalue = $value;
				}
			}
			$this->_values[] = $sqlvalue;
		} else {
			$this->_values[] = $value;
		}
		$this->_fields[] = $fieldname;

		return( $this );
	}

	/**
	 * field設定
	 * @access	public
	 * @param	string	$value		field句
	 * @return	インスタンス
	 * @info	select で使用する field を設定
	 */
	public function field( $value ) {
		$this->_fields[] = $value;
		return( $this );
	}

	/**
	 * from設定
	 * @access	public
	 * @param	string	$value		from句
	 * @return	インスタンス
	 * @info	select で使用する from を設定
	 */
	public function from( $value ) {
		$this->_from[] = $value;
		return( $this );
	}

	/**
	 * join設定
	 * @access	public
	 * @param	string	$left		"left", "right", "inner"
	 * @param	string	$table		table
	 * @param	string	$exp		接続式
	 * @return	インスタンス
	 * @info	select で使用する from を設定
	 */
	public function join( $left, $table, $exp ) {
		$sqlstr = "{$left} join {$table} on {$exp}";
		$this->_from[] = $sqlstr;
		return( $this );
	}

	/**
	 * where設定
	 * @access	public
	 * @param	string	$value		where句
	 * @return	インスタンス
	 * @info	whereの開始宣言と１つしかconditionがなければ直接記述できる
	 */
	public function where( $value="" ) {
		if ( $value == "" ) $value = "and";
		$this->_exp[] = $value;
		//2019-03-05 追加
		$this->_havingFlg = false;
		return( $this );
	}

	/**
	 * having設定
	 * @access	public
	 * @param	string	$value		having句
	 * @return	インスタンス
	 * @info	havingの開始宣言と１つしかconditionがなければ直接記述できる
	 * 2019-03-05 追加
	 */
	public function having( $value="" ) {
		if ( $value == "" ) $value = "and";
		$this->_exp_hv[] = $value;
		//2019-03-05 追加
		$this->_havingFlg = true;
		return( $this );
	}

	/**
	 * groupby設定
	 * @access	public
	 * @param	string	$value		group by句
	 * @return	インスタンス
	 * @info	
	 */
	public function groupby( $value ) {
		if ( $value != "" ){
			$this->_groupby[] = $value;
		}
		return( $this );
	}

	/**
	 * orderby設定
	 * @access	public
	 * @param	string	$value		order by句
	 * @return	インスタンス
	 * @info	
	 */
	public function orderby( $value ) {
		if ( $value != "" ){
			$this->_orderby[] = $value;
		}
		return( $this );
	}

	/**
	 * page設定
	 * @access	public
	 * @param	intger	$page		1から始まるページ番号
	 * @param	intger	$type		1ページあたりの行数
	 * @return	インスタンス
	 * @info	この設定からoffset limitを自動で設定する
	 */
	public function page( $page, $lines ) {
		$offset = ($page - 1) * $lines;
		$limit = $lines;
		$this->offset( $offset );
		$this->limit( $limit );
		return( $this );
	}

	/**
	 * limit設定
	 * @access	public
	 * @param	intger	$value		limit値
	 * @return	インスタンス
	 * @info	直接limit値を設定
	 */
	public function limit( $value ) {
		$this->_limit = $value;
		return( $this );
	}
	/**
	 * offset設定
	 * @access	public
	 * @param	intger	$value		offset値
	 * @return	インスタンス
	 * @info	直接offset値を設定
	 */
	public function offset( $value ) {
		$this->_offset = $value;
		return( $this );
	}

	/**
	 * condition設定
	 * @access	public
	 * @param	boolean	$skipflg	空欄時のskip true:する false:しない
	 * @param	string	$fieldname	フィールド+不等号 "a=" "a like" "a in"とか記述
	 * @param	string	$value		値 ["%","etc","%"] とすると '%etc%'と結合される skipflg判定は配列内のでれかが空欄だったらskipする
	 * @param	string	$type		型
	 * @return	インスタンス
	 * @info	可変引数関数
	 *			( fieldname+value )
	 *			( fieldname,value ) ※skipmoed:false type:FD_STR
	 *			( fieldname,value,type ) ※skipmode:false
	 *			( skipmode, fieldname, value, type )
	 */
	public function condition() {
		$toValue = "";
		$toType = "";
		$between = "";
		$to = "";
		$inCondition = false;
		//引数の数を取得
		$arg_num = func_num_args();
		if ( $arg_num == 1 ) {
			$con = func_get_arg(0);
			$this->_addCondition( $con );
		} else {
			if ( $arg_num == 2 ) {
				$flg = false;
				$fieldname  = func_get_arg(0);
				$fieldvalue = func_get_arg(1);
				$type       = FD_STR;
			} else if ( $arg_num == 3 ){
				$flg = false;
				$fieldname  = func_get_arg(0);
				$fieldvalue = func_get_arg(1);
				$type       = func_get_arg(2);
			} else if ( $arg_num == 6 ){
				$flg        = false;
				$fieldname  = func_get_arg(0);
				$between    = func_get_arg(1);
				$fieldvalue = func_get_arg(2);
				$type       = func_get_arg(3);
				$toValue    = func_get_arg(4);
				$toType     = func_get_arg(5);
			} else if ( $arg_num == 7 ){
				$flg        = func_get_arg(0);
				$fieldname  = func_get_arg(1);
				$between    = func_get_arg(2);
				$fieldvalue = func_get_arg(3);
				$type       = func_get_arg(4);
				$toValue    = func_get_arg(5);
				$toType     = func_get_arg(6);
			} else {
				$flg        = func_get_arg(0);
				$fieldname  = func_get_arg(1);
				$fieldvalue = func_get_arg(2);
				$type       = func_get_arg(3);
			}
			
			if ( strtolower(substr(trim($fieldname), -3)) == ' in' ) $inCondition = true;
			
			if ( $between != "" ){
				if ( !$this->_isNull( $toValue, $flg ) ){
					if ( is_array($toValue) ){
						$to = implode( "", $toValue );
					} else {
						$to = $toValue;
					}
					//データ変換
					if ( is_array($this->template) ){
						$to = call_user_func( $this->template, $to, $toType );
					}
				}
			}
			if ( !$this->_isNull( $fieldvalue, $flg ) ){
				//in (...)の場合
				if ( $inCondition ){
					$inary = array();
					foreach( $fieldvalue as $conval ){
						if ( mb_strlen($conval) > 0 ){
							$inary[] = call_user_func( $this->template, $conval, $type );
						}
					}
					if ( count($inary) > 0 ){
						$value = "(" . implode(",", $inary) . ")";
					} else {
						$value = "";
					}
				} else {
					if ( is_array($fieldvalue) ){
						$value = implode( "", $fieldvalue );
					} else {
						$value = $fieldvalue;
					}
					//データ変換
					if ( is_array($this->template) ){
						$value = call_user_func( $this->template, $value, $type );
					}
				}
				if(  $between != "" ){
					//
					if ( ($value == "NULL" || $to == "NULL") && $flg == true ) return( $this );
					if ( ($value == "NULL" && $to == "NULL") && $flg == false ) return( $this );
					if ( $value != "NULL" && $to == "NULL"  && $flg == false ){
						if ( strtolower($between) == "not between" ){
							$this->_addCondition( $fieldname." < {$value}");
						} else {
							$this->_addCondition( $fieldname." >= {$value}");
						}
						return( $this );
					}
					if ( $value == "NULL" && $to != "NULL" && $flg == false ){
						if ( strtolower($between) == "not between" ){
							$this->_addCondition( $fieldname." > {$to}");
						} else {
							$this->_addCondition( $fieldname." <= {$to}");
						}
						return( $this );
					}
					$this->_addCondition( $fieldname." {$between} {$value} and {$to}");
				} else {
					$this->_addCondition( $fieldname.$value );
				}
			}
		}
		return( $this );
	}

	/**
	 * and条件
	 * @access	public
	 * @param	boolean	$skipflg	空欄時のskip true:する false:しない
	 * @param	string	$fieldname	フィールド+不等号 "a=" "a like" "a in"とか記述
	 * @param	string	$value		値 ["%","etc","%"] とすると '%etc%'と結合される skipflg判定は配列内のでれかが空欄だったらskipする
	 * @param	string	$type		型
	 * @return	インスタンス
	 * @info	可変引数関数
	 *			( fieldname+value )
	 *			( fieldname,value ) ※skipmoed:false type:FD_STR
	 *			( fieldname,value,type ) ※skipmode:false
	 *			( skipmode, fieldname, value, type )
	 *         and で条件を追加します。初めての場合はそのまま条件のみが記述されます
	 */
	public function and() {
		//php7から予約語も関数名に使えるようになったので
		$this->_exp[@count($this->_exp)-1] = "and";
		$arg_num = func_num_args();
		if ( $arg_num == 1 ) {
			$this->condition( func_get_arg(0) );
		} else if ( $arg_num == 2 ) {
			$this->condition( func_get_arg(0), func_get_arg(1) );
		} else if ( $arg_num == 3 ) {
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2) );
		} else if ( $arg_num == 4 ) {
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3) );
		} else if ( $arg_num == 6 ) {
			//between
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4), func_get_arg(5) );
		} else if ( $arg_num == 7 ) {
			//between
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4), func_get_arg(5), func_get_arg(6) );
		} else {
			//処理なし
		}
		return( $this );
	}
	/**
	 * or条件
	 * @access	public
	 * @param	boolean	$skipflg	空欄時のskip true:する false:しない
	 * @param	string	$fieldname	フィールド+不等号 "a=" "a like" "a in"とか記述
	 * @param	string	$value		値 ["%","etc","%"] とすると '%etc%'と結合される skipflg判定は配列内のでれかが空欄だったらskipする
	 * @param	string	$type		型
	 * @return	インスタンス
	 * @info	可変引数関数
	 *			( fieldname+value )
	 *			( fieldname,value ) ※skipmoed:false type:FD_STR
	 *			( fieldname,value,type ) ※skipmode:false
	 *			( skipmode, fieldname, value, type )
	 *         or で条件を追加します。初めての場合はそのまま条件のみが記述されます
	 */
	public function or() {
		//php7から予約語も関数名に使えるようになったので
		$this->_exp[@count($this->_exp)-1] = "or";
		$arg_num = func_num_args();
		if ( $arg_num == 1 ) {
			$this->condition( func_get_arg(0) );
		} else if ( $arg_num == 2 ) {
			$this->condition( func_get_arg(0), func_get_arg(1) );
		} else if ( $arg_num == 3 ) {
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2) );
		} else if ( $arg_num == 4 ) {
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3) );
		} else if ( $arg_num == 6 ) {
			//between
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4), func_get_arg(5) );
		} else if ( $arg_num == 7 ) {
			//between
			$this->condition( func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4), func_get_arg(5), func_get_arg(6) );
		} else {
			//処理なし
		}
		return( $this );
	}

	/**
	 * subquery条件
	 * @access	public
	 * @param	string	$fieldname	フィールド+不等号 "a=" "a like" "a in"とか記述
	 * @param	string	$sql		値 ["%","etc","%"] とすると '%etc%'と結合される skipflg判定は配列内のでれかが空欄だったらskipする
	 * @return	インスタンス
	 * @info	可変引数関数
	 *			( fieldname+sql )
	 *			( fieldname,sql )
	 *          fieldname in ( sql ) として条件を追加する
	 */
	public function subQuery() {
		//引数の数を取得
		$arg_num = func_num_args();
		if ( $arg_num == 1 ){
			$con = func_get_arg(0);
			$this->_addCondition( $con );
		} else if ( $arg_num == 2 ){
			$con = func_get_arg(0) . " in (" . func_get_arg(1) . ")";
			$this->_addCondition( $con );
		}
		return( $this );
	}

	/**
	 * for update
	 * @access	public
	 * @param	string	$exp		括弧内の条件をつなぐデフォルトの論理式
	 * @return	インスタンス
	 * @info	括弧の開始を宣言
	 */
	public function forUpdate() {
		$this->_lock[] = "for update";
		return( $this );
	}

	/**
	 * 括弧開始宣言
	 * @access	public
	 * @param	string	$exp		括弧内の条件をつなぐデフォルトの論理式
	 * @return	インスタンス
	 * @info	括弧の開始を宣言
	 */
	public function groupStart( $exp="and" ) {
	
		$this->_exp[] = $exp;
		$this->_addCondition( "(" );
/*		
		if ( @count($this->_where) == 0 ){
			$this->_where[] = "(";
		} else {
			$this->_where[] = $this->_last( $this->_exp );
			$this->_exp[] = $exp;
			$this->_where[] = "(";
		}
*/
		return( $this );
	}

	/**
	 * 括弧終了宣言
	 * @access	public
	 * @return	インスタンス
	 * @info	括弧の終了を宣言
	 */
	public function groupEnd() {
		//最後のexpを削除
		array_pop( $this->_exp );
		$lastcon =  $this->_last( $this->_where );
		//2019-04-16 判定方法を変更
		if ( $lastcon == "and (" || $lastcon == "or (" || $lastcon == "(" ){
//		if ( $this->_last( $this->_where ) == "(" ){
			array_pop( $this->_where );
		} else {
			$this->_where[] = ")";
		}
		return( $this );
	}

	/**
	 * SQLを生成し文字列として出力する
	 * @access	public
	 * @param	string	$del		各設定間の区切り文字デフォルトはスペース
	 * @return	SQL文字列
	 * @info	SQLを出力する
	 *			createSQL("\n")とすればデバッグ時にメソッドチェインで記述した段落どおりに文字列が出力される
	 */
	public function createSQL( $del=" ") {
		if( $this->_type == "select" ){
			//select処理
			//各句ごとのデータを生成
			$fld = implode( ",{$del}", $this->_fields );
			$from = implode( " {$del}", $this->_from );
			$where = $this->_buildWhere();
			$having = $this->_buildHaving();
			$groupby = implode( ",{$del}", $this->_groupby );
			$orderby = implode( ",{$del}", $this->_orderby );
			$lock    = implode( ",{$del}", $this->_lock );
			//SQL文生成
			$this->_sql  = "{$this->_type}{$del}{$fld}{$del}";
			//2019-06-27 from句なしに対応
			if ( $from != "" ){
				$this->_sql .= "from {$from}{$del}";
			}
			if ( $where != "" ){
				$this->_sql .= "where {$where}{$del}";
			}
			if ( $groupby != "" ){
				$this->_sql .= "group by {$groupby}{$del}";
			}
			//2019-03-05 having句の追加
			if ( $having != "" ){
				$this->_sql .= "having {$having}{$del}";
			}
			if ( $orderby != "" ){
				$this->_sql .= "order by {$orderby}{$del}";
			}
			//limitはmysqlと他のDBで記述が
			if ( $this->_limit >= 0 ){
				if ( $this->_dbtype == "mysql" ){
					if ( $this->_offset >= 0 ){
						$this->_sql .= "limit {$this->_offset},{$this->_limit}{$del}";
					} else {
						$this->_sql .= "limit {$this->_limit}{$del}";
					}
				} else {
					if ( $this->_offset >= 0 ){
						$this->_sql .= "offset {$this->_offset}{$del}limit {$this->_limit}{$del}";
					} else {
						$this->_sql .= "limit {$this->_limit}{$del}";
					}
				}
			}
			if ( $lock != "" ){
				$this->_sql .= $lock;
			}
		} else if ( $this->_type == "select2insert" ){	
			//insert処理
			$tofld   = implode( ",", $this->_fields );
			$fromfld = implode( ",", $this->_values );
			$from    = implode( " {$del}", $this->_from );
			$where   = $this->_buildWhere();
			$having  = $this->_buildHaving();
			$groupby = implode( ",{$del}", $this->_groupby );
			$orderby = implode( ",{$del}", $this->_orderby );

			$this->_sql  = "insert into {$this->_table}{$del}";
			$this->_sql .= "({$tofld}){$del}";
			$this->_sql .= "select {$fromfld}{$del}";
			$this->_sql .= "from {$from}{$del}";
			if ( $where != "" ){
				$this->_sql .= "where {$where}{$del}";
			}
			if ( $groupby != "" ){
				$this->_sql .= "group by {$groupby}{$del}";
			}
			//2019-03-05 having句の追加
			if ( $having != "" ){
				$this->_sql .= "having {$having}{$del}";
			}
			if ( $orderby != "" ){
				$this->_sql .= "order by {$orderby}{$del}";
			}
			//limitはmysqlと他のDBで記述が
			if ( $this->_limit >= 0 ){
				if ( $this->_dbtype == "mysql" ){
					$this->_sql .= "limit {$this->_offset},{$this->_limit}{$del}";
				} else {
					$this->_sql .= "offset {$this->_offset}{$del}limit {$this->_limit}{$del}";
				}
			}
		} else if ( $this->_type == "insert" ){	
			//insert処理
			$fld = implode( ",{$del}", $this->_fields );
			$values = implode( ",{$del}", $this->_values );
			$this->_sql  = "insert into {$this->_table}{$del}";
			$this->_sql .= "({$fld}){$del}values ({$values})";
		} else if ( $this->_type == "update" ){
			//update処理
			$fld = array();
			for($i=0;$i<count($this->_fields);$i++){
				$val = $this->_values[$i];
				if ( $val == "" || $val == "''") {
					$val = "null";
				}
				$fld[] = "{$this->_fields[$i]}={$this->_values[$i]}";
			}
			$values = implode( ",{$del}", $fld );
			$from = implode( " {$del}", $this->_from );
			$where = $this->_buildWhere();
			$this->_sql  = "update {$from}{$del}";
			$this->_sql .= "set {$values}{$del}";
			$this->_sql .= "where {$where}{$del}";
			//limit 2019-03-22 追加
			if ( $this->_limit >= 0 ){
				if ( $this->_dbtype == "mysql" ){
					if ( $this->_offset >= 0 ){
						$this->_sql .= "limit {$this->_offset},{$this->_limit}{$del}";
					} else {
						$this->_sql .= "limit {$this->_limit}{$del}";
					}
				} else {
					if ( $this->_offset >= 0 ){
						$this->_sql .= "offset {$this->_offset}{$del}limit {$this->_limit}{$del}";
					} else {
						$this->_sql .= "limit {$this->_limit}{$del}";
					}
				}
			}
		} else if ( $this->_type == "delete" ){
			$from  = implode( " {$del}", $this->_from );
			$where = $this->_buildWhere();
			//SQL文生成
			$this->_sql  = "{$this->_type}{$del}";
			//2019-06-27 from句なしに対応
			if ( $from != "" ){
				$this->_sql .= "from {$from}{$del}";
			}
			if ( $where != "" ){
				$this->_sql .= "where {$where}{$del}";
			}
		}
		return( $this->_sql );
	}

	/**
	 * where文の構築
	 * @access	private
	 * @return	SQL where文字列
	 * @info	where文を出力する
	 */
	private function _buildWhere() {
		if ( @count($this->_where) == 0 ){
			//2020-06-25 Notice対応
			if ( count($this->_exp) == 0 ){
				return( "" );
			}
			if ( $this->_exp[0] == "and" || $this->_exp[0] == "or" ){
				return( "" );
			} else {
				return( implode( " ", $this->_exp ) );
			}
		} else {
			return( implode( " ", $this->_where ) );
		}
	}

	/**
	 * having文の構築
	 * @access	private
	 * @return	SQL where文字列
	 * @info	where文を出力する
	 */
	private function _buildHaving() {
		if ( @count($this->_having) == 0 ){
			if ( @count($this->_exp_hv) > 0 ){
				if ( $this->_exp_hv[0] == "and" || $this->_exp_hv[0] == "or" ){
					return( "" );
				} else {
					return( implode( " ", $this->_exp_hv ) );
				}
			} else {
				return( "" );
			}
		} else {
			return( implode( " ", $this->_having ) );
		}
	}

	/**
	 * 条件文の追加
	 * @access	private
	 * @return	なし
	 * @info	where配列にconditionを追加する
	 */
	private function _addCondition( $con ) {
		//パターン追加 2019-04-16
		$noexplist = array( "", "and", "or", "(", "and (", "or (" );
		//2019-03-05 追加
		if ( $this->_havingFlg == false ){
			$lst = $this->_last( $this->_where );
			if ( array_search( $lst, $noexplist ) === false ){
				$this->_where[] = $this->_last( $this->_exp )." ".$con;
			} else {
				$this->_where[] = $con;
			}
		} else {
			$lst = $this->_last( $this->_having );
			if ( array_search( $lst, $noexplist ) === false ){
				$this->_having[] = $this->_last( $this->_exp_hv )." ".$con;
			} else {
				$this->_having[] = $con;
			}
		}
	}

	/**
	 * null判定
	 * @access	private
	 * @param	string	$value		検証する値
	 * @param	boolean	$flg		true:空欄判定 false:returnを必ずfalseで返す
	 * @return  boolean				結果(true：null / false：null以外)
	 * @info	null値判定
	 *			$flg is arrayなら、配列内部の値が$value だったら trueを返す
	 */
	private function _isNull( $value, $flg ) {
		//$flg の値に含まれるかの判定
		if ( is_array($value) ){
			foreach( $value as $item ){
				if ( $item == "" ) return( true );
			}
			return( false );
		} else {
			if ( $flg == true ){
				if ( $value == "" ){
					return( true );
				} else {
					return( false );
				}
			} else {
				return( false );
			}
		}
	}

	/**
	 * 最終文字列取得
	 * @access	private
	 * @param	array	$ary		配列
	 * @return  string				配列の最終文字列
	 * @info	指定した配列の最終値を取得する
	 */
	private function _last( $ary ) {
		if ( @count($ary) == 0 ) return ( "" );
		return( $ary[@count($ary)-1] );
	}

}
?>
