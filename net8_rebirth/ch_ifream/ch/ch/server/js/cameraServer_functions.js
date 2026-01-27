/**
 * @fileOverview
 * カメラ端末用関数
 * 
 * (C)SmartRams Corp. 2003-2019 All Rights Reserved．
 * 
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * @package
 * @author    村上 俊行
 * @language  JavaScript
 * @version   1.0
 * @since     2019/04/19 ver1.0 村上俊行 初版作成
 * @using
 * @desc
 */

	//globals
	var languageMode = '';						//言語モード
	var keysocket;
	var _sconnect;								//dataConnectionのグローバルアクセス用変数
	var game = {}
	var autoPayTime = 3;						//自動pay時間
	var closeSkipFlg = false;					//Firefoxリロード対策
	var endOneGame = true;						//1Game終了フラグ
	var activeFlg = false;						//接続中フラグ
	var noPayFlg = true;						//未清算フラグ
	var closeGameFlg = false;					//営業時間外フラグ
	var noPayTimer = null;						//自動精算までにsettimeoutインスタンス
	var activeBonus = false;					//ボーナス中かどうかの判定
	var activeBB = false;						//BBのボーナス判定
	var upGameCount = 0;						//開始からのゲーム数
	var noticeTimeout = null;					//終了予告用タイムアウト
	var autoModeFlg = false;					//現在の自動モードフラグ
	var autoStopFlg = false;					//停止信号フラグ

	var intvCheckBuy = null;					//購入結果取得待ちインターバル
	var SETTLE_CHECK_SPAN = 5;					//決済ステータス確認のインターバル間隔（秒)
												//画面に表示するシグナルログ
	var userSignalAry   = ['','','','','','','','','',''];
	var socketSignalAry = ['','','','','','','','','',''];

	var gameStopTime = '';						//管理者メッセージからの緊急停止時間
	var lastMessageTime = '';					//管理者メッセージ

	/*
	 * 現在利用できるメディアをプルダウンに設定
	 * @info    なし
	 */
	navigator.mediaDevices.enumerateDevices()
	.then(function(devices) { // 成功時
		devices.forEach(function(device) {
			if ( device['kind'] == 'audioinput' ){
				if ( device['label'] == "" ) device['label'] = '機器許可前により名称取得できません';
				var tag = $('<option>', { value: device['deviceId'], text: device['label'] } );
				$('#audiosorce').append( tag );
				if ( device['deviceId'] == $.cookie("audioid") ) $('#selectaudio').text( device['label'] );
			}
			if ( device['kind'] == 'videoinput' ){
				var tag = $('<option>', { value: device['deviceId'], text: device['label'] } );
				$('#videosorce').append( tag );
				if ( device['deviceId'] == $.cookie("videoid") ) $('#selectvideo').text( device['label'] );
			}
		});
		$('#videosorce').val($.cookie("videoid"));
		$('#audiosorce').val($.cookie("audioid"));
	})
	.catch(function(err) { // エラー発生時
		console.error('enumerateDevide ERROR:', err);
	});

	/*
	 * セッティング画面切り替えボタンクリックイベント
	 * @info    なし
	 */
	$('#goSetting').on('click',function(){
		if ( $('#setting').css('display') == 'none' ){
			$('#setting').show();
		} else {
			$('#setting').hide();
		}
	});

	/*
	 * オーディオプルダウンチェンジイベント
	 * @info    なし
	 */
	$('#audiosorce').on('change',function(){
		$('#selectaudio').text( $(this).find('option:selected').text() );
	});
	/*
	 * ビデオプルダウンチェンジイベント
	 * @info    なし
	 */
	$('#videosorce').on('change',function(){
		$('#selectvideo').text( $(this).find('option:selected').text() );
	});
	/*
	 * 再起動ボタンイベント（cookieに設定情報を記録し再読み込み）
	 * @info    なし
	 */
	$('#reboot').on('click',function(){
		$.cookie("videoid", $('#videosorce').val(), { expires: 365 });
		$.cookie("audioid", $('#audiosorce').val(), { expires: 365 });
		location.reload( true );
	});

	$('#restartbutton').on('click',function(){
		console.log( 'restart');
		setTimeout(function(){
			location.reload( true );
		}, 30000);
	});



//---------

	/*
	 * カメラチェックボタンクリックイベント
	 * @info    なし
	 */
	$('#cameracheck').on('click', function(){
		if ( $(this).hasClass('active') ){
			video = attachMediaStream(video, null);
			$(this).removeClass('active');
			$('#container').hide();
		} else {
			video = attachMediaStream(video, stream);
			$(this).addClass('active');
			$('#container').show();
		}

	});

	// main.js からカメラ完了でコールされる
	window.cameraReady = function( stream){
		//Peerコネクト
		connectPeer(stream);
	}

	function socketSignalLog(message){
		socketSignalAry.shift();
		socketSignalAry.push(message);
		$('#socketsignal').html(socketSignalAry.join('<br>'));
	}

	function userSignalLog(message){
		userSignalAry.shift();
		userSignalAry.push(message);
		$('#usersignal').html(userSignalAry.join('<br>'));
	}

	function dispAPIstatus( id, status='OK' ){
		var dt = new Date();
		var nowTimeStr = ("00" + dt.getHours()).slice(-2) +':'+ ("00" + dt.getMinutes()).slice(-2)+':'+ ("00" + dt.getSeconds()).slice(-2);
		var message = nowTimeStr+' ['+status+']';
		if ( status == 'OK' ){
			$('#'+id).html( message );
		} else {
			$('#'+id+'_err')
				.html( $('#'+id+'_err').html()+message )
				.addClass('error')
			;
		}
	}

	/*
	 * 営業時間チェック
	 * @access	public
	 * @param	なし
	 * @return	なし
	 * @info    なし
	 */
	function checkCloseGame() {
		var openTime  = gameOpenTime;
		var closeTime = gameCloseTime;
		var csp = closeTime.split(':');
		var osp = openTime.split(':');
		//var nowdt   = new Date();
		var nowdt   = new Date();
		var closedt = new Date(nowdt.getFullYear(),nowdt.getMonth(),nowdt.getDate(),csp[0],csp[1],0);
		var opendt  = new Date(nowdt.getFullYear(),nowdt.getMonth(),nowdt.getDate(),osp[0],osp[1],0);
		var stopdt = null;
		if ( gameStopTime != '' ){
			var ssp = gameStopTime.split(' ')[1].split(':');
			closeTime = ''+ssp[0]+':'+ssp[1];
			closedt  = new Date(nowdt.getFullYear(),nowdt.getMonth(),nowdt.getDate(),ssp[0],ssp[1],0);
		}
		//日をまたがる営業日の場合
		
		if ( openTime > closeTime ){
			var ntime = ('0'+nowdt.getHours()).slice(-2)+':'+('0'+nowdt.getMinutes()).slice(-2);
			if ( gameOpenTime > ntime ){
				if ( gameCloseTime >= ntime ){
					opendt.setDate(opendt.getDate() -1);
				}
			} else {
				closedt.setDate(closedt.getDate() +1);
			}
		}
		//通常営業時間チェック
		if ( nowdt.getTime() > closedt.getTime() ||  nowdt.getTime() < opendt.getTime()){
			closeGameFlg = true;
		} else {
			closeGameFlg = false;
		}
	}

	/*
	 * 切断時に機器の状態を正常にするための停止信号処理
	 * @access	public
	 * @param	なし
	 * @return	なし
	 * @info    なし
	 */
	function resetMachine(){
		//精算時にドラムを止めないまま終了していた時の対策
		//ダミーの停止コードだけ送る
		if ( machineMode == 2 ){
			if ( autoModeFlg ){
				//自動モードで終了されてしまった場合には止める
				//2019-04-09 1回転モードに切り替えるので廃止
				//keysocket.send('bse');
				autoModeFlg = false;
			}
			setTimeout(function(){
				//2019-05-08 フルモード動作に変わったので新しい停止信号を送信
				keysocket.send('bsy');
				keysocket.send('bs1');
				keysocket.send('bs2');
				keysocket.send('bs3');
			},1000);
		}
		autoStopFlg = false;
	}
	
	/*
	 * websocket電文作成処理
	 * @access	public
	 * @param	string		signal	電文名
	 * @param	string		msg		送信文字列
	 * @return	string				dataConnection電文
	 * @info    なし
	 */
	function _sendStr( signal, msg){
		var _r = "";
		_r += "tag:" + signal;
		_r += ",msg:" + msg;
		return( _r+" ");			//iOSの最終文字欠落対応
	}

	/*
	 * 終了予告時間までの秒数取得
	 * @access	public
	 * @param	なし
	 * @return	int		秒数
	 * @info    なし
	 */
	function getNoticeTime() {
		var sp = noticeTime.split(':');
		var nowdt = new Date();
		var curdt = new Date(nowdt.getFullYear(),nowdt.getMonth(),nowdt.getDate(),sp[0],sp[1],0);
		if ( nowdt.getTime() > curdt.getTime() ){
			curdt = new Date(nowdt.getFullYear(),nowdt.getMonth(),nowdt.getDate()+1,sp[0],sp[1],0);
		}
		var span = curdt.getTime() - nowdt.getTime()
		return( span );
	}
	
	/*
	 * 決済処理
	 * @access	public
	 * @param	string		message	99-9999 ([決済方法]-[金額])
	 * @return	なし
	 * @info    なし
	 */
	function requestSettle( message ) {
		//購入履歴を準備する
		requestBuyPlayPoint( message )
		.then(function(data){
			//正常終了
			if ( data["IP_CODE"] == "drawpoint" ){
				_sconnect.send( _sendStr('cpp',  data["IP_CODE"]+'|'+data["point"]+'|'+data["before_drawpoint"]+'|'+data["drawpoint"] ) );
			} else {
				//クライアントに決済用データを送信
				_sconnect.send( _sendStr('cpd',  data.message) );
			}
			//前のインターバルは削除
			clearInterval( intvCheckBuy );
			
			//コンビニ決済の場合は結果を待たない
			if ( data['payment_code'] == '30003'){
				_sconnect.send( _sendStr('cps',  game.playpoint) );
				return;
			}
			
			//結果待ちインターバル起動
			intvCheckBuy = setInterval(function(){
				//プレイポイント確認
				execBuyPlayPoint(data['cookie'],data['sendid'])
				.then(function(data){
					//正常終了
					if ( data.result == 1 ){
						//ログに記録
						playLog()
						.then(function(){
							//プレイポイントをクライアントに送信
							_sconnect.send( _sendStr('Cpt',  game.playpoint) );
							//インターバル終了
							clearInterval( intvCheckBuy );
						});
					} else if ( data.result == 9 ){
						//決済失敗
						_sconnect.send( _sendStr('cpf', '' ) );
						//インターバル終了
						clearInterval( intvCheckBuy );
					} else {
						//結果未通知
						_sconnect.send( _sendStr('cpw',  '') );
					}
				},function(){
					//エラー処理
					_sconnect.send( _sendStr('cpe', '' ) );
				});
			},SETTLE_CHECK_SPAN * 1000);
		},function(data){
			//購入履歴作成NG
			//決済機能NGのコードを送る
			_sconnect.send( _sendStr('cpe',  '') );
		});
	}


	/*
	 * 精算処理（メイン）
	 * @access	public
	 * @param	なし
	 * @return	Promise		resolve引数なし
	 * @info    なし
	 */
	function pay(){
		return new Promise(function(resolve) {
			if ( !activeFlg ){
				resolve();
				return;
			}
			//精算処理
			execPay()
			.then(function(data){
				_sconnect.send( _sendStr('Ppp',  data.pay.play_point) );
				_sconnect.send( _sendStr('Pcr',  data.pay.credit) );
				_sconnect.send( _sendStr('Pdr',  data.pay.draw_point) );
				_sconnect.send( _sendStr('Ptd',  data.pay.total_draw_point) );
				_sconnect.send( _sendStr('EXT',  '') );
				
				//automodeでなければリセット処理を行う(automodeの場合はautoStopFlgを優先）
				if ( !autoModeFlg ) resetMachine();
				resolve();
			},function(){
				//エラー処理
				_sconnect.send( _sendStr('ERP',  'pay') );
				setCameraStatus('log', 40, 'pay Error' );
				resolve();
			});
		});
	}

	/*
	 * 画面ステータス表示
	 * @access	public
	 * @param	なし
	 * @return	なし
	 * @info    なし
	 */
	function showGame(){
		var dummyUser = '';
		if ( game.tester_flg != 0 ){
			dummyUser = ' [Dummy]';
		}
		$('#machine_no').text(game.machine_no);
		$('#camera_name').text(cameraid);
		$('#member_no').text(''+ game.member_no + dummyUser);
		$('#play_dt').text(game.play_dt);
		$('#languageMode').text(languageMode);
		$('#credit').text(game.credit);
		$('#playpoint').text(game.playpoint);
		$('#total_count').text(game.total_count);
		$('#count').text(game.count);
		$('#rb_count').text(game.rb_count);
		$('#bb_count').text(game.bb_count);
		$('#mc_in_credit').text(game.mc_in_credit);
		$('#mc_out_credit').text(game.mc_out_credit);

		$('#in_point').text(game.in_point);
		$('#out_point').text(game.out_point);
		$('#in_credit').text(game.in_credit);
		$('#out_credit').text(game.out_credit);

		$('#videosize').text(videoSize);

		if ( machineMode == 1 ){
			$('#countNavel').text(countNavel);
			if ( activeBonus ){
				$('#ActiveBonus').show();
			} else {
				$('#ActiveBonus').hide();
			}
			if ( activeFluctuation ){
				$('#activeFluctuation').show();
			} else {
				$('#activeFluctuation').hide();
			}
		} else {
			if ( activeBonus && activeBB ){
				$('#bb_name').show();
			} else {
				$('#bb_name').hide();
			}
			if ( activeBonus && activeBB == false){
				$('#rb_name').show();
			} else {
				$('#rb_name').hide();
			}
			$('#drumtype').text(drumStopSignalName[layoutOption['drum']]);
		}
	}
	
	/*
	 * peerjs認証処理（API起動)
	 * @access	public
	 * @param	string		metadata
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function checkAuth( metadata ) {
		//console.log( "============start checkAuth" );
		var dt = new Date();
		var tmsp = dt.getTime();
		return new Promise(function(resolve, reject) {
			if( !metadata ){
				//console.log( '[userAuth] metadata error' );
				dispAPIstatus('userAuth', 'metadata error');
				reject();
				return;
			}
			var ids = metadata.split(':');
			if ( ids.length != 2 ){
				//console.log( '[userAuth] metadata format error' );
				dispAPIstatus('userAuth', 'metadata format error');
				reject();
				return;
			}
			//API呼び出し
			$.ajax({
				url:'../api/userAuthAPI.php?MACHINENO='+game.machine_no+'&PLAYDT='+game.play_dt+'&MEMBERNO='+ids[0]+'&ONETIMEAUTHID='+ids[1]+'&ts='+tmsp,
				type:'GET'
			})
			.done( (data) => {
				//console.log("[userAuth]ajax success", data);
				if ( data.status == 'ok' ){
					//console.log( data );
					dispAPIstatus('userAuth');
					resolve(data);
				} else {
					//console.log("[userAuth]status NG", data);
					//console.log( data );
					dispAPIstatus('userAuth', 'NG');
					reject();
				}
			})
			.fail( (data) => {
				//console.log("[userAuth]ajax fail", data);
				dispAPIstatus('userAuth', 'fail');
				reject();
			});
		});
	}

	/*
	 * プレイポイント購入結果取得（API起動)
	 * @access	public
	 * @param	string		purchase_no		購入No
	 * @param	string		member_no		会員No
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function execBuyPlayPoint(purchase_no, member_no) {
		var dt = new Date();
		var tmsp = dt.getTime();
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url:'../api/playpointAPI.php?M=get&MEMBER_NO='+game.member_no+'&PURCHASE_NO='+purchase_no+'&ts='+tmsp,
				type:'GET'
			})
			.done( (data) => {
				//console.log("[buyPlaypoint]ajax success");
				if ( data.status == 'ok' ){
					dispAPIstatus('buyPlaypoint');
					if ( data.result == 1 ){
						//playpointの設定
						var wpoint = data.game.playpoint - game.playpoint;
						game.out_point += wpoint;
						game.playpoint = data.game.playpoint;
						showGame();
					}
					resolve(data);
				} else {
					dispAPIstatus('buyPlaypoint', 'NG');
					reject();
				}
			})
			.fail( (data) => {
				//console.log("[buyPlaypoint]ajax fail", data);
				dispAPIstatus('buyPlaypoint', 'fali');
				reject();
			});
		});
	}


	/*
	 * プレイポイント→クレジット変換処理（API起動)
	 * @access	public
	 * @param	なし
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function execConvCredit(){
		return new Promise(function(resolve, reject) {
			//会員の種別を判別
			if ( game.tester_flg == 0 ){
				if ( game.playpoint < game.conv_point ){
					reject();
					return;
				}
				usePlayPoint(game.conv_point)
				.then(function(){
					//変換
					game.playpoint    -= game.conv_point;
					game.credit       += game.conv_credit;
					//変動ログに追加
					game.in_point     += game.conv_point;
					game.out_credit   += game.conv_credit;

					//ログに記録
					playLog()
					.then(function(data){
						resolve(data)
					},
					function(data){
						resolve(data)
					});
				},function(){
					console.log( '[usePlayPoint] reject' );
					reject();
				});
			} else {
				game.credit       += game.conv_credit;
				//変動ログに追加
				game.out_credit   += game.conv_credit;
				//ログに記録
				playLog()
				.then(function(data){
					resolve(data)
				},
				function(data){
					resolve(data)
				});
			}
		});
	}

	/*
	 * プレイポイント使用（API起動)
	 * @access	public
	 * @param	int			point			使用するプレイポイント
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function usePlayPoint( point ) {
		var dt = new Date();
		var tmsp = dt.getTime();
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url:'../api/playpointAPI.php?M=use&MEMBER_NO='+game.member_no+'&PLAYPOINT='+point+'&ts='+tmsp,
				type:'GET'
			})
			.done( (data) => {
				if ( data.status == 'ok' ){
					//console.log("[playpoint]ajax success");
					dispAPIstatus('usePlayPoint');
					resolve(data);
				} else {
					//console.log("[playpoint]status [NG]", data);
					dispAPIstatus('usePlayPoint', 'NG');
					_sconnect.send( _sendStr('ERP',  'point') );
					reject();
				}
			})
			.fail( (data) => {
				//console.log("[playpoint]ajax fail", data);
				dispAPIstatus('usePlayPoint', 'fail');
				_sconnect.send( _sendStr('ERP',  'point') );
				reject();
			});
		});
	}

	/*
	 * プレイログの記録（API起動)
	 * @access	public
	 * @param	string		bonusName		ボーナス名('bb' or 'rb')
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function playLog(bonusName=null) {
		if ( bonusName ){
			game.bonusspan = game.count;
			game.count = 0;
		}
		//他のシグナルに影響されないようにこの時点でのデータをディープコピーして処理する
		var saveGame = $.extend(true, {}, game)
		if ( bonusName ){
			//ボーナス更新の場合はボーナス更新の種類を設定
			saveGame['bonusUpdate'] = bonusName;
			//console.log( saveGame );
		}
		//console.log( "[playLog] total_count="+game.total_count );
		return new Promise(function(resolve, reject) {
			//精算後などの停止処理でのログ書き込みの誤動作防止
			if ( game.member <= 0 || game.play_dt == '') {
				resolve();
				return;
			}
			//API呼び出し
			$.ajax({
				url:'../api/playLogAPI.php',
				data: saveGame,
				type:'POST'
			})
			.done( (data) => {
				//console.log("[playLog]ajax success");
				if ( data.status == 'ok' ){
					dispAPIstatus('playLog');
					resolve(data);
				} else {
					//console.log("[playLog]status [NG]", data);
					dispAPIstatus('playLog', 'NG');
					_sconnect.send( _sendStr('ERP',  'log') );
					reject(data);
				}
			})
			.fail( (data) => {
				//console.log("[playLog]ajax fail", data);
				dispAPIstatus('playLog', 'fail');
				_sconnect.send( _sendStr('ERP',  'log') );
				reject();
			});
		});
	}
	
	/*
	 * 精算処理（API起動）
	 * @access	public
	 * @param	なし
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function execPay(){
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url:'../api/payAPI.php',
				data: game,
				type:'POST'
			})
			.done( (data) => {
				//console.log("[payAPI] ajax success");
				if ( data.status == 'ok' ){
					dispAPIstatus('pay');
					noPayFlg = false;								//精算済み
					game = $.extend(true, {}, resetGame() );
					showGame();
					resolve(data);
				} else {
					//console.log("[payAPI]status [NG]", data);
					dispAPIstatus('pay', 'NG');
					reject();
				}
			})
			.fail( (data) => {
				//console.log("[payAPI] ajax fail", data);
				dispAPIstatus('pay', 'fail');
				reject();
			});
		});
	}

	/*
	 * プレイポイント購入申請（API起動）
	 * @access	public
	 * @param	string		data	99-9999 ([決済方法]-[金額])
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function requestBuyPlayPoint(data) {
		var dt = new Date();
		var tmsp = dt.getTime();
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url:'../api/playpointAPI.php?M=request&MEMBER_NO='+game.member_no+'&T='+data+'&ts='+tmsp,
				type:'GET'
			})
			.done( (data) => {
				//console.log("[requestBuyPlayPoint]ajax success");
				if ( data.status == 'ok' ){
					if ( data['IP_CODE'] != "drawpoint" ){
						data['message'] = ''+data['IP_CODE']+'|'+data['crypt_cookie']+'|'+data['price']+'|'+data['crypt_sendid']+'|'+data['payment_code']+'|'+data['email']+'|'+data['dummyurl'];
					}
					dispAPIstatus('requestBuyPlayPoint');
					resolve(data);
				} else {
					//console.log("[requestBuyPlayPoint]status [NG]", data);
					dispAPIstatus('requestBuyPlayPoint', 'NG');
					reject();
				}
			})
			.fail( (data) => {
				//console.log("[requestBuyPlayPoint]ajax fail", data);
				dispAPIstatus('requestBuyPlayPoint', 'fail');
				reject();
			});
		});
	}

	/*
	 * ステータスログ処理（API起動）
	 * @access	public
	 * @param	string		mode		ステータス文字列
	 * @param	int			level=20	記録レベル
	 * @param	string		message		オプションメッセージ
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function setCameraStatus( mode, level=20, message="" ){
		var dt = new Date();
		var tmsp = dt.getTime();
		var urlstring = '../api/cameraAPI.php?M='+mode+'&MACHINE_NO='+machine_no;
		if ( mode == 'log' ){
			'&level='+level+'&message='+message
		}
		urlstring += '&ts='+tmsp;
		return new Promise(function(resolve) {
			//API呼び出し
			$.ajax({
				url: urlstring,
				type:'GET'
			})
			.done( (data) => {
				if ( mode == "start" ){
					game = $.extend(true, {}, resetGame() );
				}
				//console.log("[cameraAPI] ajax success");
				if ( data.status == 'ok' ){
					dispAPIstatus('cameraAPI');
					resolve(data);
				} else {
					//console.log("[cameraAPI]status [NG]", data);
					dispAPIstatus('cameraAPI', 'NG');
					resolve(data);
				}
			})
			.fail( (data) => {
				//console.log("[cameraAPI] ajax fail", data);
				dispAPIstatus('cameraAPI', 'fail');
				resolve(data);
			});
		});
	}

	/*
	 * 管理画面からのメッセージを取得（API起動)
	 * @access	public
	 * @param	なし
	 * @return	Promise		resolve:data	APIの結果
	 * @info    なし
	 */
	function getClientMessage() {
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url:'../api/messageAPI.php?MACHINE_NO='+game.machine_no+'&LAST_DATE='+lastMessageTime+'&LANG='+languageMode,
				type:'GET'
			})
			.done( (data) => {
				if ( data.status == 'ok' ){
					//console.log("[playpoint]ajax success");
					dispAPIstatus('clientMessage');
					//次回チェック用の時間を保持
					lastMessageTime = data.nextdate;
					resolve(data);
				} else {
					//console.log("[playpoint]status [NG]", data);
					dispAPIstatus('clientMessage', 'NG');
					reject();
				}
			})
			.fail( (data) => {
				//console.log("[playpoint]ajax fail", data);
				dispAPIstatus('clientMessage', 'fail');
				reject();
			});
		});
	}
