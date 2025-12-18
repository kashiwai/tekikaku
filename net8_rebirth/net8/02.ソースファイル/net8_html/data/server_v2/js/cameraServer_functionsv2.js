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
 * @since     2019/04/19 ver 1.0         村上俊行 初版作成
 *            2020/06/10 ver 1.4.0-s1    村上俊行 keyMappingによるSTART連打対応
 *            2023/04/14 ver 1.4.1       村上俊行 精算時の状態をAPIに送信する機能を追加
 *            2023/09/01 ver 1.4.2       村上俊行 PayPal決済追加
 * @using
 * @desc
 */

	//globals
	var languageMode = '';						//言語モード
	var keysocket;
	var _sconnect;								//dataConnectionのグローバルアクセス用変数
	var game = {}
	var autoPayTime = 180;						//自動pay時間(sec)
	var closeSkipFlg = false;					//Firefoxリロード対策
	var endOneGame = true;						//1Game終了フラグ
	var activeFlg = false;						//接続中フラグ
	var noPayFlg = true;						//未清算フラグ
	var runPayFlg = false;						//精算実行中フラグ
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

	var lastTimestamp = (new Date()).getTime();	//タイムスタンプ
	var leaveTime = autoPayTime * 1000;			//離籍警告時間
	var maxplusTime = 700;						//MAX+START delayTime	2020-06-10追加

	var gameStopTime = '';						//管理者メッセージからの緊急停止時間
	var lastMessageTime = '';					//管理者メッセージ

	var continuousBonus = 0;					//連チャン回数

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

	//イメージ取得処理テスト版
	function drawVideo(){
		/*
		var video = document.getElementById("localVideo");
		var canvas = document.getElementById("cpimage");
		var context = canvas.getContext("2d");
		var od1 = document.getElementById("oi1");
		var od1con = od1.getContext("2d");
		var od2 = document.getElementById("oi2");
		var od2con = od2.getContext("2d");
		var od3 = document.getElementById("oi3");
		var od3con = od3.getContext("2d");
		//context.drawImage(video, 218, 166, 300, 84);
		context.drawImage(video, 0, 0);
		od1con.drawImage(canvas, -374, -270);
		od2con.drawImage(canvas, -507, -270);
		od3con.drawImage(canvas, -647, -270);

		var od1base64 = od1.toDataURL('image/png');
		var od2base64 = od2.toDataURL('image/png');
		var od3base64 = od3.toDataURL('image/png');
		var fData = new FormData();
		fData.append('img1', od1base64);
		fData.append('img2', od2base64);
		fData.append('img3', od3base64);
		
		$.ajax({
			//画像処理サーバーに返す場合
			url: 'http://localhost:5000/getimage',   
			type: 'POST',
			data: fData ,
			contentType: false,
			processData: false,
			success: function(data, dataType) {
			    //非同期で通信成功時に読み出される [200 OK 時]
			    //console.log('Success', data);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
			    //非同期で通信失敗時に読み出される
			    console.log('Error : ' + errorThrown);
			}
		});
		*/
	}


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

	/*
	 * カメラが正常に表示されているかのチェック
	 * @info    なし
	 */
	function checkCamera(){
		return new Promise(function(resolve, reject) {
			if ( $('#cameracheck').hasClass('active') ){
				resolve(true);
				return;
			}
		
			flg = true
			video = attachMediaStream(video, stream);
			$('#container').show();
			setTimeout(function(){
				w =  $('#localVideo').width();
				h =  $('#localVideo').height();
				if ( h != 480 ){
					console.log( '[error] video height:'+h);
					flg = false
				}
				video = attachMediaStream(video, null);
				$('#container').hide();
				resolve(flg)
			}, 1000);
		})
	}

	// main.js からカメラ完了でコールされる
	window.cameraReady = function( stream){
		//Peerコネクト
		connectPeer(stream);
		
		if ( resetBonus != 'on' ){
			if ( $.cookie('bb') == 'on' ) {
				activeBonus = true;
				activeBB = true;
				$('#bb_on').prop('checked', true);
			}
			if ( $.cookie('rb') == 'on' ) {
				activeBonus = true;
				$('#rb_on').prop('checked', true);
			}
			showGame();
		} else {
			$.cookie('bb', 'off', { expires: 365 });
			$.cookie('rb', 'off', { expires: 365 });
			console.log( 'reset bonus!!');
		}
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
		
		if ( game.tester_flg != 0 ){
			closeGameFlg = false;
			return;
		}
		
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
		if ( nowdt.getTime() >= closedt.getTime() ||  nowdt.getTime() <= opendt.getTime()){
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
		//2019-11-26 新基盤はリセットコードのみ送信
		keysocket.send('rst');

		/*
		//精算時にドラムを止めないまま終了していた時の対策
		//ダミーの停止コードだけ送る
		if ( machineMode == 2 ){
			if ( autoModeFlg ){
				//自動モードで終了されてしまった場合には止める
				//2019-04-09 1回転モードに切り替えるので廃止
				//keysocket.send('bse');
				autoModeFlg = false;
			}
			//2019-05-08 フルモード動作に変わったので新しい停止信号を送信
			setTimeout(function(){ keysocket.send('bsy'); },1000 );
			setTimeout(function(){ keysocket.send('bs1'); },2000 );
			setTimeout(function(){ keysocket.send('bs2'); },4000 );
			setTimeout(function(){ keysocket.send('bs3'); },6000 );
		}
		autoStopFlg = false;
		*/
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
			var checkspan = SETTLE_CHECK_SPAN;
			//正常終了
			if ( data["IP_CODE"] == "drawpoint" ){
				_sconnect.send( _sendStr('cpp',  'payment=drawpoint|IP_CODE='+data["IP_CODE"]+'|point='+data["point"]+'|before_drawpoint='+data["before_drawpoint"]+'|drawpoint='+data["drawpoint"] ) );
				//drawpointなら即時反映
				checkspan = 0.5;
			} else {
				//クライアントに決済用データを送信
				_sconnect.send( _sendStr('cpd',  data.message) );
			}
			//前のインターバルは削除
			clearInterval( intvCheckBuy );

			//2020-07-15 決済開始を記録
			keysocket.send('@SETTLE_START_'+message+'_'+game.playpoint);
			
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
							//2020-07-17 coin(drawpoint)を送信
							_sconnect.send( _sendStr('Adp',  game.drawpoint) );
							//インターバル終了
							clearInterval( intvCheckBuy );
							//2020-07-15 決済完了で合計値を記録
							keysocket.send('@SETTLE_OK_'+game.playpoint);
						});
					} else if ( data.result == 9 ){
						//決済失敗
						_sconnect.send( _sendStr('cpf', '' ) );
						//インターバル終了
						clearInterval( intvCheckBuy );
						//2020-07-15 決済NGを記録
						keysocket.send('@SETTLE_NG');
					} else {
						//結果未通知
						_sconnect.send( _sendStr('cpw',  '') );
					}
				},function(){
					//エラー処理
					_sconnect.send( _sendStr('cpe', '' ) );
					//インターバル終了
					clearInterval( intvCheckBuy );
					//2020-07-15 決済NGを記録
					keysocket.send('@SETTLE_FAIL');
				});
			},checkspan * 1000);
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
	function pay(paymode='', exitCode="11"){
		return new Promise(function(resolve, reject) {
			//2重精算防止
			if ( runPayFlg ){
				console.log( 'pay reject' );
				reject();
				return;
			}
			runPayFlg = true;
			if ( !activeFlg ){
				//2020-08-17 フラグリセット追加
				runPayFlg = false;
				if ( game.member_no <= 0 ){
					console.log( 'pay reject' );
					reject();
				} else {
					resolve();
				}
				return;
			}
			//精算処理
			execPay(paymode, exitCode)
			.then(function(data){
				_sconnect.send( _sendStr('Ppp',  data.pay.play_point) );
				_sconnect.send( _sendStr('Pcr',  data.pay.credit) );
				_sconnect.send( _sendStr('Pdr',  data.pay.draw_point) );
				//強制精算用に値を追加
				_sconnect.send( _sendStr('Pda',  data.pay.autodraw) );
				_sconnect.send( _sendStr('Ptd',  data.pay.total_draw_point) );
				//2021-06-01出玉控除用の値を追加
				_sconnect.send( _sendStr('Pdd',  data.pay.deduction_credit) );
				
				_sconnect.send( _sendStr('EXT',  '') );
				
				//精算したら一度メンテナンスにする
				//setCameraStatus("end");
				//10秒後に再度開始に設定する
				//setTimeout(function(){ setCameraStatus("start"); },10000 );

				runPayFlg = false;

				//automodeでなければリセット処理を行う(automodeの場合はautoStopFlgを優先）
				//if ( !autoModeFlg ) resetMachine();
				//2019-11-26 必ずリセット処理を行う
				resetMachine();
				resolve();
			},function(){
				//エラー処理
				_sconnect.send( _sendStr('ERP',  'pay') );
				setCameraStatus('log', 40, 'pay Error' );
				runPayFlg = false;
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
		var pingStatus = '';
		if ( game.tester_flg != 0 ){
			dummyUser = ' [Tester]';
		}
		//2020-06-18 Pingの受信を画面に出す
		if ( pingCount > 0 ){
			pingStatus = ' (Ping)'
		}
		$('#serverversion').text(serverVersion);
		$('#machine_no').text(game.machine_no);
		$('#camera_name').text(cameraid);
		$('#member_no').text(''+ game.member_no + dummyUser + pingStatus);
		$('#play_dt').text(game.play_dt);
		$('#languageMode').text(languageMode);
		$('#credit').text(game.credit);
		$('#playpoint').text(game.playpoint);
		$('#day_count').text(game.day_count);
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

		$('#autodraw').text(game.autodraw);

		$('#leavetime').text(leaveTime/60000);


		$('#videosize').text(videoSize);

		if ( machineMode == 1 ){
			/*
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
			*/
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
			//$('#drumtype').text(drumStopSignalName[layoutOption['drum']]);
		}

		$('#signame').text(sigHost+':'+sigPort);
		
		if ( activeFlg ){
			$('#useractive').removeClass('leave');
			$('#useractive').addClass('active');
		} else {
			$('#useractive').removeClass('active');
			$('#useractive').removeClass('leave');
			if ( game.member_no != 0 ){
				$('#useractive').addClass('leave');
			} else {
			
			}
		}
		
		$('#cb').text(continuousBonus);
		$('#maxcb').text(game.maxrenchan_count);
		$('#tenjo').text(game.tenjo_count);

		$('#ichigeki_credit').text( game.ichigeki_credit);
		$('#day_max_credit').text( game.day_max_credit);
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
			// テスト環境用：特定のmetadataの場合は認証をバイパス
			if (metadata === "1:test_onetime_auth_id") {
				console.log('[userAuth] TEST MODE - Auth bypassed');
				dispAPIstatus('userAuth', 'TEST MODE');
				resolve({status: 'ok', message: 'Test auth success', member_no: 1});
				return;
			}

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
			if ( game.member_no == 0 ){
				resolve({result: 0});
				return;
			}
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
						//2020-07-17 coin(drawpoint)の追加
						game.drawpoint = data.game.drawpoint;
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
					reject({status: 'ng'});
					return;
				}

				// 韓国統合モード: APIを呼ばずにローカルで処理（game.koreaModeで管理）
				if ( game.koreaMode === true ) {
					console.log('💰 [Korea] Local conversion (ccc) - no API call');
					game.playpoint    -= game.conv_point;
					game.credit       += game.conv_credit;
					game.in_point     += game.conv_point;
					console.log('✅ [Korea] ccc Convert success:', game.conv_point, 'points ->', game.conv_credit, 'credits');
					keysocket.send('@KOREA_CONV_'+game.conv_point+'_'+game.conv_credit);
					playLog()
					.then(function(data){
						resolve(data)
					},
					function(data){
						resolve(data)
					});
					return;
				}

				usePlayPoint(game.conv_point)
				.then(function(data){
					//変換
					game.playpoint    -= game.conv_point;
					game.credit       += game.conv_credit;
					//変動ログに追加
					game.in_point     += game.conv_point;
					//2020-05-18 間違って加算しているので削除
					//game.out_credit   += game.conv_credit;
					//2022-09-26 ログに有効期限ポイントを記録
					if (data.exppoint > 0){
						keysocket.send('@EXP_POINT ' + data.exppoint);
					}
					//ログに記録
					playLog()
					.then(function(data){
						resolve(data)
					},
					function(data){
						resolve(data)
					});
				},function(data){
					console.log( '[usePlayPoint] reject' );
					reject(data);
				});
			} else {
				game.credit       += game.conv_credit;
				//変動ログに追加
				//2020-05-18 間違って加算しているので削除
				//game.out_credit   += game.conv_credit;
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
	 * プレイポイント→クレジット変換処理（金額指定）
	 * @access	public
	 * @param	int			amount			変換するクレジット数
	 * @return	Promise		resolve:data	APIの結果
	 * @info    指定されたクレジット数に必要なポイントを計算して変換
	 */
	function execConvCreditAmount(amount){
		return new Promise(function(resolve, reject) {
			// クレジット数からポイント数を計算
			// game.conv_point : game.conv_credit = X : amount
			// X = (amount / game.conv_credit) * game.conv_point
			var needPoint = Math.floor((amount / game.conv_credit) * game.conv_point);

			console.log('🔍 Convert credit amount:', amount, 'Need point:', needPoint, 'game.koreaMode:', game.koreaMode);

			//会員の種別を判別
			if ( game.tester_flg == 0 ){
				if ( game.playpoint < needPoint ){
					console.log('❌ Not enough points:', game.playpoint, '<', needPoint);
					reject({status: 'ng', message: 'ポイント不足'});
					return;
				}

				// 韓国統合モード: APIを呼ばずにローカルで処理（game.koreaModeで管理）
				if ( game.koreaMode === true ) {
					console.log('💰 [Korea] Local conversion - no API call');
					game.playpoint    -= needPoint;
					game.credit       += amount;
					game.in_point     += needPoint;
					console.log('✅ [Korea] Convert success:', needPoint, 'points ->', amount, 'credits');
					keysocket.send('@KOREA_CONV_'+needPoint+'_'+amount);
					playLog()
					.then(function(data){
						resolve(data)
					},
					function(data){
						resolve(data)
					});
					return;
				}

				usePlayPoint(needPoint)
				.then(function(data){
					//変換
					game.playpoint    -= needPoint;
					game.credit       += amount;
					//変動ログに追加
					game.in_point     += needPoint;
					//2020-05-18 間違って加算しているので削除
					//game.out_credit   += amount;
					//2022-09-26 ログに有効期限ポイントを記録
					if (data.exppoint > 0){
						keysocket.send('@EXP_POINT ' + data.exppoint);
					}
					console.log('✅ Convert success:', needPoint, 'points ->', amount, 'credits');
					//ログに記録
					playLog()
					.then(function(data){
						resolve(data)
					},
					function(data){
						resolve(data)
					});
				},function(data){
					console.log( '❌ [usePlayPoint] reject' );
					reject(data);
				});
			} else {
				game.credit       += amount;
				//変動ログに追加
				//2020-05-18 間違って加算しているので削除
				//game.out_credit   += amount;
				console.log('✅ Tester convert success:', amount, 'credits');
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
					//_sconnect.send( _sendStr('ERP',  'point') );
					console.log( data );
					reject(data);
				}
			})
			.fail( (data) => {
				//console.log("[playpoint]ajax fail", data);
				dispAPIstatus('usePlayPoint', 'fail');
				//_sconnect.send( _sendStr('ERP',  'point') );
				reject({status: 'fail'});
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
	function execPay(paymode="", exitCode="11"){
		return new Promise(function(resolve, reject) {
			// 韓国統合モード: APIを呼ばずにローカルで精算処理
			if ( game.koreaMode === true ) {
				console.log('💰 [Korea] Local settlement - no API call');

				// クレジットをポイントに変換して返却
				// game.conv_point : game.conv_credit = X : game.credit
				// X = (game.credit / game.conv_credit) * game.conv_point
				var returnPoint = Math.floor((game.credit / game.conv_credit) * game.conv_point);
				var totalReturnPoint = game.playpoint + returnPoint;

				console.log('💰 [Korea] Settlement: credit=' + game.credit + ' -> returnPoint=' + returnPoint);
				console.log('💰 [Korea] Total return: playpoint=' + game.playpoint + ' + return=' + returnPoint + ' = ' + totalReturnPoint);

				// 精算データを構築
				var payData = {
					status: 'ok',
					pay: {
						play_point: totalReturnPoint,
						credit: game.credit,
						draw_point: game.out_credit || 0,
						autodraw: game.autodraw || 0,
						total_draw_point: (game.out_credit || 0) + (game.autodraw || 0),
						deduction_credit: 0
					}
				};

				keysocket.send('@KOREA_PAY_' + totalReturnPoint + '_' + game.credit);

				// ゲーム状態をリセット
				noPayFlg = false;
				game = $.extend(true, {}, resetGame() );
				showGame();

				// 10秒後にリロード
				clearInterval( restartInt );
				setCameraStatus("end");
				setTimeout(function(){
					if ( $('#mentebutton').hasClass('deactive') ) return;
					setCameraStatus("reset")
					.then(function(data){
						keysocket.send('@RESET');
						location.reload();
					},function(){});
				}, 10000);

				resolve(payData);
				return;
			}

			var postgame = $.extend(true, {
				paymode: paymode,
				exitcode: exitCode
			}, game );
			//API呼び出し
			$.ajax({
				url:'../api/payAPI.php',
				data: postgame,
				type:'POST'
			})
			.done( (data) => {
				//console.log("[payAPI] ajax success");
				if ( data.status == 'ok' ){
					dispAPIstatus('pay');
					noPayFlg = false;								//精算済み
					game = $.extend(true, {}, resetGame() );
					showGame();

					//2020-08-17 精算したら必ずリロードに変更
					//終了後にメンテナンス中に切り替え
					clearInterval( restartInt );
					setCameraStatus("end");
					//2020-08-26 chatの送信ログ記録
					if ( data['chat'] ) keysocket.send('@CHAT_'+data['chat']);			//keysocketのログに@CHATを記録させる
					//10秒後に再起動
					setTimeout(function(){
						//画面からメンテナンスモードにした場合はリロードしない
						if ( $('#mentebutton').hasClass('deactive') ) return;

						//一度リセットステータスで再起動があるかを確認
						setCameraStatus("reset")
						.then(function(data){
							keysocket.send('@RESET');				//keysocketのログに@ACTを記録させる
							location.reload();
						},function(){
							
						});
					},10000 );

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
					//決済メッセージの共通化
					var mes = [];
					var message = '';
					Object.keys(data).forEach(function(k){
						if ( k == 'status' ) return;
						mes.push( ''+k+'='+data[k]);
					});
					message = mes.join('|');
					console.log( 'message='+message );
					data['message'] = message;
					/*
					// p99 pymentのの場合
					if ( data['data'] ){
						data['payment'] = 'p99';
						data['message'] = 'payment='+data['payment']+'|_type='+data['_type']+'|_url='+data['_url']+'|data='+data['data'];
					// lavy の場合(drawpoint時もこちら）
					} else if ( data['IP_CODE'] ){
						data['payment'] = 'lavy';
						if ( data['IP_CODE'] != "drawpoint" ){
							data['message'] = 'payment='+data['payment']+'|_type='+data['_type']+'|_url='+data['_url']+'|IP_CODE='+data['IP_CODE']+'|crypt_cookie='+data['crypt_cookie']+'|price='+data['price']+'|crypt_sendid='+data['crypt_sendid']+'|payment_code='+data['payment_code']+'|email='+data['email']+'|dummyurl='+data['dummyurl'];
						}
					}
					*/
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
			urlstring += '&level='+level+'&message='+message;
		}
		if ( mode == 'setting' ){
			urlstring += '&LEVEL='+level;
		}
		urlstring += '&ts='+tmsp;
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url: urlstring,
				type:'GET'
			})
			.done( (data) => {
				if ( mode == 'start' ){
					game = $.extend(true, {}, resetGame() );
				}
				if ( mode == 'status' ){
					if ( data.userleave == '1' ){
						//離席状態でassignが外れた
						execPay('auto', '32')
						.then(function(){
							keysocket.send('@PAY');				//Python側も精算
							keysocket.send('@leavePAY(32)');		//ログにpay条件を記録
						});
					}
				}
				if ( mode == 'reset' ){
					//2020-09-18 rebbotがあれば再起動
					if (data['reboot'] ){
						//settingがある場合のみ
						if ( data['reboot'] > '0') {
							//現在プレイ中でないことを確認
							if ( activeFlg == false && game.member_no <= 0 && game.play_dt == '' ){
								//Chromeのみ再起動
								if ( data['reboot'] == '1' ){
									keysocket.send('@REBOOT_CHROME');
									//メンテナンス中に変更
									//Promiseに変更
									setCameraStatus('reboot')
									.then(function(){
										//リロードする
										location.reload();
									});
									reject();
								}
								//pythonのみ再起動
								if ( data['reboot'] == '2' ){
									keysocket.send('@REBOOT_PYTHON');
									//Promiseに変更
									setCameraStatus('reboot')
									.then(function(){
									});
									reject();
								}
								//camera再起動
								if ( data['reboot'] == '3' ){
									keysocket.send('@REBOOT_CAMERA');
									//Promiseに変更
									setCameraStatus('reboot')
									.then(function(){
										//Chromeを閉じる
										open('about:blank', '_self').close();
									});
									reject();
								}
							}
						}
					}
					//2020-06-08 settingがあればメンテナンス中に変更し設定変更を送信
					if ( data['setting'] ){
						//settingがある場合のみ
						if ( data['setting'] > '0') {
							//メンテナンス中に変更
							//Promiseに変更
							setCameraStatus('end')
							.then(function(){
								//設定変更コードを送信
								keysocket.send('@CHANGESETTING_'+data['setting']);
							});
						}
					}
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
					// 2020-08-17 assign check
					if ( data['userleave'] == '1' ){
						pay('auto', '32')
						.then(function(){
							keysocket.send('@PAY');				//Python側も精算
							keysocket.send('@exitflgPAY_32');		//ログにpay条件を記録
						});
					} else {
						//console.log("[playpoint]ajax success");
						dispAPIstatus('clientMessage');
						//次回チェック用の時間を保持
						lastMessageTime = data.nextdate;
					}
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
