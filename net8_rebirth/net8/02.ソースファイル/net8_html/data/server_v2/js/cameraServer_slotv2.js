/**
 * @fileOverview
 * スロット用カメラ端末サーバJS
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
 * @since     2019/04/19 ver 1.0          村上俊行 初版作成
 *            2020/06/10 ver 1.4.0-s1     村上俊行 keyMappingによるSTART連打対応
 *            2020/06/10 ver 1.4.1-s1     村上俊行 COM通信ができないときにrestartが実行されるとおこる不具合修正
 *            2020/06/18 ver 1.4.1a-s2    村上俊行 pingでのdataConnection監視を追加 接続断のタイムオーバーの自動pay追加
 *            2020/06/22 ver 1.4.1d-s1    村上俊行 プレイ中のCOM通信エラー時の自動精算機能の追加 自動精算タイミングの修正
 *            2020/06/29 ver 1.4.2-s1     村上俊行 自動精算とゲーム開始が1秒以内の場合に精算できなくなるバグの修正
 *            2020/07/15 ver 1.4.3-s1     村上俊行 台湾ROMだと今後リスタートタイミングが営業時間にかかる可能性があるので時間を変更
 *                                                 ゲーム画面決済情報をkeysocketへ記録する処理を追加
 *            2020/07/15 ver 1.4.3-s2     村上俊行 リロード時にplaypointだけは更新するように修正
 *            2020/07/16 ver 1.4.3-s3     村上俊行 dataConnectionイベントが遅延した際にintAutoPAYが実行されてしまう問題の修正
 *            2020/07/17 ver 1.4.3-s4     村上俊行 coinがゲーム画面に表示されるように修正
 *            2020/08/05 ver 1.4.4-s1     村上俊行 @LOSTPING時のclose問題の修正
 *            2020/08/17 ver 1.4.4-s2     村上俊行 @DBLPAYERROR時の挙動修正 RESETタイミングを精算後に変更
 *            2020/08/26 ver 1.4.4-s3     村上俊行 CHATのログを出力するように修正
 *            2020/09/18 ver 1.4.5-s1     村上俊行 再起動フラグ対応 連チャン、天井、一撃数の処理を追加
 *            2020/12/17 ver 1.4.5-s2     村上俊行 creditIn outの未送信故障検知機能を追加
 *            2021/01/26 ver 1.4.5-s3     村上俊行 サラ番がボーナス突入時にCreditInなしで２回回る＞errorIN_count判定は5回
 *            2021/01/27 ver 1.4.5-s4     村上俊行 iniファイルで設定したoutチェックゲーム数を反映するように修正
 *            2022/05/11 ver 1.4.5-s5     村上俊行 1日1回の再起動用reload処理を削除
 *            2023/04/14 ver 1.4.5-s6     村上俊行 精算行動終了コードを設定
 *            2023/09/25 ver 1.4.5-s7     村上俊行 readyコードが受信できなかった場合に強制精算にならないように修正
 * @desc
 */
 	//サーババージョンを記述
	var serverVersion = '1.4.5-s7';
	//連打対策用（コメントにしている部分は無制限） msec ボタン間隔(ms) oldtime 前回押下時間
	//使う箇所はmsecを希望の数値にしてコメントを外す
	var keyMapping = {
		'bsbd':  { 'msec' : 1000, 'oldtime': null },			//MAXBET
		'bssd':  { 'msec' : 1000, 'oldtime': null },			//START down
		'bssu':  { 'msec' : 1000, 'oldtime': null },			//START up
//		'bsb':   { 'msec' : 1000, 'oldtime': null },			//MAXBET
//		'bss':   { 'msec' : 1000, 'oldtime': null },			//START
//		'bs1':   { 'msec' : 1000, 'oldtime': null },			//REAL STOP1
//		'bs2':   { 'msec' : 1000, 'oldtime': null },			//REAL STOP2
//		'bs3':   { 'msec' : 1000, 'oldtime': null },			//REAL STOP3
		'bsoc':  { 'msec' : 2000, 'oldtime': null },			//CHANCE(set)
		'bsom':  { 'msec' : 2000, 'oldtime': null },			//MAX+
		'bsocd': { 'msec' : 2000, 'oldtime': null },			//CHANCE
		'bsocu': { 'msec' : 2000, 'oldtime': null },			//CHANCE
		'bsomd': { 'msec' : 2000, 'oldtime': null },			//MAX+
		'bsomu': { 'msec' : 2000, 'oldtime': null },			//MAX+
		'bsou':  { 'msec' : 1000, 'oldtime': null },			//up
		'bsol':  { 'msec' : 1000, 'oldtime': null },			//left
		'bsod':  { 'msec' : 1000, 'oldtime': null },			//down
		'bsor':  { 'msec' : 1000, 'oldtime': null },			//right
		'bsos':  { 'msec' : 2000, 'oldtime': null },			//select
		'bsoe':  { 'msec' : 2000, 'oldtime': null },			//enter
	};

	var _peer;

	//リセット設定
	var resetIntervalSec = 30;								//peerReset
	var resetLnkSec1 = 300  / resetIntervalSec;				//無条件lnk解除（１回目）
	var resetLnkSec2 = 3600 / resetIntervalSec;				//無条件lnk解除（２回目以降）

	//メンテナンスメッセージ
	var confMessage   = ['メンテナンス中に切り替えますか？','稼働中に切り替えますか？'];
	var buttonMessage = ['メンテナンス中に切り替える','稼働中に切り替える'];
	//使用しないSignal設定
	var notSendSignals = ['Signal_0_End', 'Signal_1_End'];
	//disconnect flg
	var disconFlg = false;
	//１度以上プレイしているか？
	var playedFlg = false;
	//ドラム停止判断シグナル
	var drumStopSignalName = ['Signal_5', 'Signal_5_End']
	//drum指定がない場合のデフォルト設定
	if ( typeof layoutOption !== 'undefined'){
		if ( typeof layoutOption['drum'] === 'undefined') layoutOption['drum'] = 1;
	}
	console.log( layoutOption );

	//初回起動
	var firstBoot = true;
	//再接続中のモード設定
	var reconnectMode = false;
	//reloadフラグ
	var reloadGame = false;
	//閉店時間による自動pay実行フラグ
	var closePay = false;
	//累積離席時間
	var lvtime = 0;
	//ping累積count
	var pingCount = 0;

	var readyFlg = false;

	//障害検知用のgame数保持
	var saveIN_day_count  = 0;
	var errorIN_count = 0;
	var saveOUT_day_count = 0;
	var spanOUTCheckGame = 100;
	var sumOUTcount = 0;

	$('#signame').text(sigHost+':'+sigPort);

	/*
	 * game情報のリセット
	 * @access	public
	 * @param	なし
	 * @return	game object
	 * @info    なし
	 */
	function resetGame(){
		var game = {
			'machine_no'    : machine_no,
			'member_no'     : 0,
			'play_dt'       : '',
			'credit'        : 0,
			'playpoint'     : 0,
			//2020-07-17 coin(drawpoint)を追加
			'drawpoint'     : 0,
			'tester_flg'    : 0,					//2019-06-11追加
			//machinePlay関連
			'total_count'   : 0,
			'count'         : 0,
			'bb_count'      : 0,
			'rb_count'      : 0,
			'mc_in_credit'  : 0,
			'mc_out_credit' : 0,
			//使用履歴
			'in_credit'     : 0,
			'out_credit'    : 0,
			'in_point'      : 0,
			'out_point'     : 0,
			'user_game'     : 0,
			'user_BB'       : 0,
			'user_RB'       : 0,
			'autodraw'      : 0,
			//動作設定
			'min_credit'    : 3,
			//2020-09-23 追加情報
			'renchan_count' : 0,
			'maxrenchan_count' : 0,
			'tenjo_count'   : 0,
			'ichigeki_credit' : 0,
			//2020-12-04 追加
			'day_max_credit' : 0,
			'day_count'     : 0,
			'past_max_credit' : 0,
			'past_max_bb'     : 0,
			//2020-12-17 障害データ設定
			'abort_machine' : '',
		}
		return game;
	}

	//バージョンを即時表示
	$('#serverversion').text(serverVersion);

	game = $.extend(true, {}, resetGame() );

	var saveCredit = 0;
	var addCredit = 0;
	var sleepCount = 0;								//未接続のままでのインターバル回数
	//カメラサーバを再起動する時間(setting.phpの値へ移行）
	//var serverRestartTime = "09:30";

	//peerjsのセッションのオートリセット
	var restartInt = setInterval(function(){
		var dt = new Date();
		var nowDateStr = ("00" + dt.getHours()).slice(-2) +':'+ ("00" + dt.getMinutes()).slice(-2);
		//console.log( nowDateStr, serverRestartTime );
		//console.log( dt );
		$('#nowTime').html(nowDateStr);
		$('#restartTime').html(serverRestartTime);
		//接続中の場合
		if ( activeFlg ){
			//leaveTime無操作で強制的に退出させる
			var span = (new Date()).getTime() - lastTimestamp;
			//console.log( span, leaveTime );
			var spanmin = Math.round(span / 60000);
			if ( spanmin > 0 ){
				$('#nowleavetime').text('(無操作:'+spanmin+'min)');
			}
			if ( span >= leaveTime ){

				lastTimestamp =  (new Date()).getTime();
				if ( activeFlg ){
					//2020-03-30 追加 タイミングによってpayが２回実行されてしまうのを防ぐ為
					//既に精算されている場合は実行しない
					if ( game.member_no <= 0 || game.play_dt == '') {
						try {
							activeFlg = false;
							//強制切断
							_sconnect.close(true);
						} catch(e) {
						
						}
						return;
					}
					//2020-06-29 1秒以下の同時刻だった場合にauth直後に精算されてしまう問題の修正
					var nowtm   = new Date().getTime();
					var playtm  = new Date( game.play_dt.replace('-','/') ).getTime();
					console.log( Math.abs(nowtm - playtm) );
					//2020-07-16 PCや回線によって判定時間が2秒で足りないことがあるので5秒ぐらいを確保する
					if ( Math.abs(nowtm - playtm) <= 5000 ){
					//if ( Math.abs(nowtm - playtm) <= 2000 ){
						console.log( '[pay] new login data');
						return;
					}
					//精算実行
					try {
						pay('auto', '31')
						.then(function(){
							keysocket.send('@intAutoPAY');				//keysocketのログに@ACTを記録させる
							keysocket.send('@PAYACTION_31');		//精算行動をログに記録させる
							activeFlg = false;
						},function(){
							keysocket.send('@DBLPAYERROR-iaPAYa(31)');			//keysocketのログに@DBLPAYERRORを記録させる
						});
						//強制切断
						//_sconnect.close(true);
					} catch(e) {
					
					}
				} else {
		
				}
			}
			//2020-06-18 pingCount処理
			if ( pingCount == 0 ){
				activeFlg = false;
				//2020-08-05 ここでclose処理をさせるためにflg変更
				closeSkipFlg = false;
				//強制切断
				_sconnect.close(true);
				keysocket.send('@LOSTPING');			//記録させる
				//2020-11-09 closeのイベントが発生しない可能性があるのでここで再度実行
				//----
				keysocket.send('@peerCLS');				//keysocketのログに@peerCLSを記録させる
				resetMachine();
				//終了予告タイマーの解除
				clearTimeout(noticeTimeout);
				//決済インターバルの解除
				clearInterval( intvCheckBuy );
				//離席時間リセット
				lvtime = 0;
				//メッセージ初期化
				$('#nowleavetime').text('');
				//----

				return;
			}
			pingCount = 0;
			//メッセージを取得
			getClientMessage()
			.then(function(data){
				//メッセージ処理
				if ( data.message_text != '' ){
					_sconnect.send( _sendStr('Dmg', data.message_text) );
				}
				//停止処理
				if ( data.stop_time != '' ){
					gameStopTime = data.stop_time;
					console.log( gameStopTime );
					var nowdt   = new Date();
					var tgdt    = new Date( gameStopTime.replace('-','/') );
					var countdown = tgdt.getTime() - nowdt.getTime();
					console.log( 'countdown:' + countdown );

					//自動精算を埋め込む
					noPayTimer = setTimeout(function(){
						if ( !activeFlg ) return;
						// keysocket.send('@forcedPAY');					//keysocketのログに@ACTを記録させる
						console.log( "===========!!auto stop pay" );
						//精算実行
						pay('auto', '32')
						.then(function(){
							keysocket.send('@forcedPAY');				//keysocketのログに@ACTを記録させる
							keysocket.send('@PAYACTION_32');		//精算行動をログに記録させる
							activeFlg = false;
							//_sconnect.close();
						},function(){
							keysocket.send('@DBLPAYERROR-fPAY(32)');				//keysocketのログに@DBLPAYERRORを記録させる
						});
					}, countdown );
				}

			},function(data){
				
			});
			sleepCount = 0;
		} else {
			if( sleepCount > 0 ){
				//１日１回の定期リスタート
				//２回目以降から処理させる
				/* 2022-05-11 削除
				if ( nowDateStr == serverRestartTime ) {
					clearInterval( restartInt );
					//2020-07-15 ROMだと時間がオーバーする可能性があるので間隔を変える
					setTimeout(function(){
						location.reload();
					}, 2000 * game.machine_no );
//					}, 30000 * game.machine_no );
					return;
				}
				*/
			}
			//クライアントが未接続の場合
			sleepCount++;
			if ( _peer.socket ){
				//画面離席が発生している場合
				if ( sleepCount % 2 == 0 ){
					if ( reconnectMode == false ){
						lvtime += 0.5;
						if ( game.member_no > 0) {
							$('#nowleavetime').text('(接続断:'+lvtime+'min)');
						} else {
							$('#nowleavetime').text('');
						}
						//2020-06-18 接続断がこちらでもオーバーしている場合は切断する
						if ( lvtime > (leaveTime/60000) ){
							//既に精算されている場合は実行しない
							if ( game.member_no <= 0 || game.play_dt == '') {
								//何もしない
							} else {
								console.log( 'connectin lost 未検出による特殊処理:' );
								//強制的にactiveにして精算させる
								activeFlg = true;
								//精算実行
								pay('auto', '31')
								.then(function(){
									keysocket.send('@intAutoPAY_'+lvtime);			//keysocketのログに@intAutoPAYを記録させる
									keysocket.send('@PAYACTION_31');		//精算行動をログに記録させる
									activeFlg = false;
									lvtime = 0;
									$('#useractive').removeClass('active');
									showGame();
									
									//2020-06-08 connection lost がキャンセルだったことを考慮してここでlnkもlost下かを確認
									setTimeout(function(){
										setCameraStatus('status');			//確認用取得（ここに設定変更の処理が入る）
									},1000);
								},function(){
									//2020-08-17 ２重処理になった場合のリセット処理追加
									activeFlg = false;
									lvtime = 0;
									showGame();
									keysocket.send('@DBLPAYERROR-iAPAY(31)');			//keysocketのログに@DBLPAYERRORを記録させる
								});
							}
						}
						try {
							keysocket.send('@ACT');				//keysocketのログに@ACTを記録させる
							if ( game.member_no > 0 ){
								setCameraStatus('status');		//確認用取得（ここに設定変更の処理が入る）
							}
						} catch(e) {
							pythonServerConnect();
						}
					}
					if ( game.member_no == 0 ){
						setCameraStatus('reset');
					} else {
						sleepCount = 1;
					}
				}
				/*
				if ( sleepCount % 2 == 0 ){
					if ( reconnectMode == false ){
						try {
							keysocket.send('@ACT');				//keysocketのログに@ACTを記録させる
							//cameraAPIでlnkチェック
							setCameraStatus('reset');
						} catch(e) {
							pythonServerConnect();
						}
					}
				}
				*/
				//5分経過または1時間に1回、データの救済をせずstatusをリセット
				if ( sleepCount == resetLnkSec1 || sleepCount % resetLnkSec2 == 0 ){
				//１時間に1回、データの救済をせずstatusをリセット
				//if (  sleepCount % resetLnkSec2 == 0 ){
					//setCameraStatus("start");
					if ( playedFlg ){
						keysocket.send('@RESET');				//keysocketのログに@ACTを記録させる
						//2019-11-14 リロードに変更
						//再起動
						clearInterval( restartInt );
						location.reload();
						return;
					}
				}
			}
			
			/*
			//カメラ画像のチェック
			if ( sleepCount % 2 == 0 ){
				checkCamera()
				.then(function(cameraFlg){
					if ( !cameraFlg ){
						keysocket.send('@DEACT');				//keysocketのログに@ACTを記録させる
						//再起動
						clearInterval( restartInt );
						var dt = new Date();
						var nowDateStr = ("00" + dt.getHours()).slice(-2) +':'+ ("00" + dt.getMinutes()).slice(-2);
						url = location.href + '&cerr=' + nowDateStr;
						location.href = url
						return;
					}
				});
			}
			*/
			
			try {
				//2019/11/06 フラグが立っていたら起動に失敗しているのでリロードする。
				if ( disconFlg ){
				//if ( peer.destroyed == true) {
					if ( sleepCount % 2 == 0 ){
						keysocket.send('@DEACT');				//keysocketのログに@DEACTを記録させる
						//再起動
						clearInterval( restartInt );
						location.reload();
						return;
					}
				}
			} catch(e) {
				
			}
		}

	},resetIntervalSec * 1000);
	
	console.log( 'camera_name:'+cameraid );
	
	//PythonのローカルWebSocketサーバに接続、成功後カメラとPeerを確立させる
	window.pythonServerConnect = function(){
		console.log('Try Connect Python WebSocket Server');
		
		//pythonとのwebsocket通信
		try {
			keysocket = new WebSocket('ws://127.0.0.1:59007');
		} catch(e) {
			console.log('WebSocket connect error...');
		}
		keysocket.onopen    = onOpen;
		keysocket.onmessage = onMessage;
		keysocket.onclose   = onClose;
		keysocket.onerror   = onError;
		
		//Open CallBack
		function onOpen(event) {
			console.log("接続しました。");
			reconnectMode = false;
			//Chrome起動確認用の送信
			//keysocket.send("run");
			//USBIOのステータス取得
			keysocket.send('@getUSBIO');
			if ( firstBoot == true ){
				console.log( $.cookie('videoid') );
				if ( $.cookie('videoid') != undefined ){
					$.cookie("videoid", $.cookie('videoid'), { expires: 365 });
					$.cookie("audioid", $.cookie('audioid'), { expires: 365 });
					$('#setting').hide();
					$('#running').show();
					//Pythonサーバの起動が確認されたのでカメラとPeerを確立させる => main.js
					AdapterJsStart();
					//開始をAPIを通じてサーバに知らせる
					//USBIOを起動の判定とするのでここは動作させない
					//setCameraStatus("start")
				} else {
					//初期設定がまだの場合
					$('#setting').show();
					$('#running').hide();
				}
				firstBoot = false;
			}
			//強制精算の設定
			if ( layoutOption['limitcredit'] ){
				if ( layoutOption['limitcredit'] > 0 ){
					keysocket.send('@SETLIMITPAY_'+layoutOption['limitcredit']);
				} else {
					keysocket.send('@SETLIMITPAY_100000');
				}
			} else {
				keysocket.send('@SETLIMITPAY_100000');
			}
			keysocket.send('@OPENTIME_'+gameOpenTime);
			keysocket.send('@CLOSETIME_'+gameCloseTime);
			//シグナリング情報をログに追加
			keysocket.send('@SIG_'+sigHost+':'+sigPort);
			$('#keysocket').addClass('active');
		}
		
		//Message CallBack
		function onMessage(event) {
			if (event && event.data) {
				if ( notSendSignals.indexOf( event.data ) >= 0 ) return;
//				console.log("python event ", event.data);
				socketSignalLog( event.data );
				
				//デジカウンターの制御信号
				if ( event.data == '@startUSBIO' ){
					//DigiCounterの接続が確認された
					setCameraStatus("start");
				} else if ( event.data == '@endUSBIO' ){
					//DigiCounterの接続が切れた。
					setCameraStatus("end");
				}

				//自動離席時間の設定
				if ( event.data.substr(0,11) == '@LEAVETIME_' ){
					var c = event.data.split('_');
					autoPayTime = parseInt(c[1]);
					leaveTime = autoPayTime * 1000;
					//resetLnkSec1 = (leaveTime + 300)  / resetIntervalSec;
					//resetLnkSec2 = (leaveTime + 3600) / resetIntervalSec;
				}
				//2020-06-10追加 MAX+START用Delay時間追加
				if ( event.data.substr(0,13) == '@MAXPLUSTIME_' ){
					var c = event.data.split('_');
					maxplusTime = parseInt(c[1]);
				}

				if ( event.data.substr(0,1) == '#' ){
					if( _sconnect ){
						_sconnect.send( _sendStr('Dmg', event.data.substr(1) ) );
					}
				}

				//2020-06-11 RDY送信タイミングを変更
				if ( event.data == '@RDY' ){
					console.log( '===== ready2RDY' );
					readyFlg = true;
					_sconnect.send( _sendStr('RDY',  game.member_no+'_'+game.play_dt) );
				}

				//設定変更完了通知
				if ( event.data == 'Rs1'){
					setCameraStatus('setting', 1);
				} else if ( event.data == 'Rs2'){
					setCameraStatus('setting', 2);
				} else if ( event.data == 'Rs3'){
					setCameraStatus('setting', 3);
				} else if ( event.data == 'Rs4'){
					setCameraStatus('setting', 4);
				} else if ( event.data == 'Rs5'){
					setCameraStatus('setting', 5);
				} else if ( event.data == 'Rs6'){
					console.log( 'setting done Rs6');
					setCameraStatus('setting', 6);
				}

				//2020-07-01 連チャン回数
				if ( event.data.substr(0,4) == 'CNT_' ){
					var cb = event.data.split('_');
					continuousBonus = parseInt(cb[1]);
					game.renchan_count = continuousBonus;
				}
				//2020-09-24 最大連チャン回数
				if ( event.data.substr(0,5) == 'MCNT_' ){
					var cb = event.data.split('_');
					game.maxrenchan_count = parseInt(cb[1]);
				}
				//2020-09-23 天井回数
				if ( event.data.substr(0,4) == 'MCC_' ){
					var cb = event.data.split('_');
					game.tenjo_count = parseInt(cb[1]);
				}
				//2020-09-23 一撃回数
				if ( event.data.substr(0,4) == 'IGC_' ){
					var cb = event.data.split('_');
					game.ichigeki_credit = parseInt(cb[1]);
				}

				//2020-12-04 最大出玉
				if ( event.data.substr(0,4) == 'MDD_' ){
					var cb = event.data.split('_');
					game.day_max_credit = parseInt(cb[1]);
					if ( game.past_max_credit < game.day_max_credit ){
						game.past_max_credit = game.day_max_credit;
					}
				}

				//2020-09-28 日ゲーム数を取得
				if ( event.data.substr(0,5) == 'Xact_' ){
					var cb = event.data.split('_');
					game.day_count = parseInt(cb[1]);
				}
				if ( event.data.substr(0,5) == 'Zhoc_' ){
					var cb = event.data.split('_');
					spanOUTCheckGame = parseInt(cb[1]);
					console.log( 'change spanOUTCheckGame', spanOUTCheckGame);
				}

				//自動精算シグナル
				if ( event.data == '@execPAY' ){
					//精算処理
					execPay('auto', '31')
					.then(function(){
						keysocket.send('@autoPAY');				//keysocketのログに@ACTを記録させる
						keysocket.send('@PAYACTION_31');		//精算行動をログに記録させる
						activeFlg = false;
					});
				}
				//2020-06-22 エラーによる強制精算
				if ( event.data == '@forcePAY' ){
					pay('', '33')
					.then(function(){
						keysocket.send('@PAY');					//keysocketのログに@ACTを記録させる
						keysocket.send('@PAYACTION_33');		//精算行動をログに記録させる
						activeFlg = false;
					},function(){
						keysocket.send('@DBLPAYERROR-fPAYp(33)');			//keysocketのログに@DBLPAYERRORを記録させる
						console.log( "pay error");
					});
					return;
				}

				if ( _sconnect ){
					var _tag = event.data;
					var _msg = '';
					
					//デバッグSignal5を発生させない
					//if ( _tag == 'Signal_5' || _tag == 'Signal_5_End'){
					//	return;
					//}
					
					//放置時間計測用
					if (_tag.substr(0,7) == 'Signal_' ){
						//シグナルのみ計測
						lastTimestamp = (new Date()).getTime();
						//leave reset
						$('#nowleavetime').text('');
					}
					//ゲーム機からの信号別の処理
					if( _tag == 'Signal_Game+1' ){
						//10の倍数ごとでDrumStopもSignal_OUTも発生しなかった時のsave予備
						if ( upGameCount % 10 == 0 && endOneGame == false ){
							playLog()
							.then(function(){
							
							},function(data){

							});
						}

						endOneGame = false;							//1ゲームの開始
						upGameCount++;
						game.day_count++;							//日ゲーム数加算
						if ( !activeBonus ){
							//ボーナス中はゲーム数の加算をしない
							game.total_count++;
							game.count++
							game.user_game++;
						}
						$('#total_count').text(game.total_count);
						$('#count').text(game.count);
					} else if ( _tag == 'Signal_0' ){						//Signal_IN
						game.credit--;
						//サービス回し追加による数値補正
						if ( game.credit < 0 ) {
							game.credit = 0;
						} else {
							game.in_credit++;
							game.mc_in_credit++;
						}
						saveCredit = 0;
						addCredit = 0;
						$('#credit').text(game.credit);
					} else if ( _tag == 'Signal_1' ){						//Signal_OUT
						game.credit++;
						game.out_credit++;
						//2019-06-18追加
						game.mc_out_credit++;
						saveCredit++;
						addCredit++;
						$('#credit').text(game.credit);
						//2020-12-17 CreditOut検出用
						sumOUTcount++;
						//var saveCredit = game.credit;
						//100msまってSignal_OUTが発生しなくなったら終了とみなす
						/*
						setTimeout(function(){
							saveCredit--;
							if ( saveCredit == 0 ){
							//if ( game.credit == saveCredit ){
								checkPlayLog();
								_sconnect.send( _sendStr('Sac', addCredit) );
								//console.log( autoModeFlg, autoStopFlg );
								if ( autoModeFlg && autoStopFlg ){
									//機器を初期状態に戻す
									resetMachine();
								}
							}
						},300 );
						*/
						//エウレカ250 マドマギ200 安定を見て300に設定する
					} else if ( _tag == 'Signal_2' ){						//Signal_RB_Start
						activeBonus = true;
						if ( !activeBB ){
							game.rb_count++;
							game.user_RB++;
							$('#rb_count').text(game.rb_count);
							$('#rb_on').prop('checked', true);
							$.cookie('rb', 'on', { expires: 365 });
							playLog('rb')								//ログ記録
							.then(function(){
							
							},function(data){

							});
						}
					} else if ( _tag == 'Signal_3' ){						//Signal_BB_Start
						if ( !activeBB ){
							activeBonus = true;
							activeBB = true;
							game.bb_count++;
							game.user_BB++;
							$('#bb_count').text(game.bb_count);
							$('#bb_on').prop('checked', true);
						}
						$.cookie('bb', 'on', { expires: 365 });
						playLog('bb')								//ログ記録
						.then(function(){
						
						},function(data){

						});
					} else if ( _tag == 'Signal_2_End' ){					//Signal_RB_End
						if ( !activeBB ){
							activeBonus = false;
							game.count = 0;
							$('#rb_on').prop('checked', false);
							$.cookie('rb', 'off', { expires: 365 });
						}
					} else if ( _tag == 'Signal_3_End' ){					//Signal_BB_End'
						activeBonus = false;
						activeBB = false;
						game.count = 0;
						$('#bb_on').prop('checked', false);
						$.cookie('bb', 'off', { expires: 365 });
					} else if ( _tag == 'Signal_5' || _tag == 'Signal_5_End'){
						// ドラム停止時にキー連打防止タイマーをリセット（次ゲーム用）
						// Reset key cooldown timers when drums stop (for next game)
						if (_tag == 'Signal_5_End') {
							Object.keys(keyMapping).forEach(function(key) {
								keyMapping[key].oldtime = null;
							});
						}
					} else if ( _tag == 'Trr'){
						drawVideo();
					} else if ( _tag == 'Trs'){
						var abortFlg = false;
						//2020-12-17 リール停止時に障害チェック
						
						/*
						console.log(
							'IN', game.day_count,saveIN_day_count,errorIN_count
						);
						console.log(
							'OUT',saveOUT_day_count,spanOUTCheckGame,sumOUTcount
						);
						*/
						//CreditINのチェック
						if ( game.day_count == saveIN_day_count ){
							errorIN_count++;
							if ( errorIN_count >= 20 ){
								//Signal_0が故障で発生していないと判断
								console.log( 'ホッパーエラー(CreditIn)' );
								keysocket.send('@HPERROR_IN');
								abortFlg = true;
								game.abort_machine = 'HOPPER_ERROR_IN'
							}
						} else {
							errorIN_count = 0;
						}
						//現在のゲーム数を記録
						saveIN_day_count = game.day_count;

						//CreditOUTのチェック
						//console.log( sumOUTcount, saveOUT_day_count, '>=', spanOUTCheckGame )
						if ( saveOUT_day_count >= spanOUTCheckGame ){
							if ( sumOUTcount == 0 ){
								//Signal_0が故障で発生していないと判断
								console.log( 'ホッパーエラー(CreditOut)' );
								keysocket.send('@HPERROR_OUT');
								abortFlg = true;
								game.abort_machine = 'HOPPER_ERROR_OUT'
							}
							//カウントリセット
							saveOUT_day_count = 0;
							sumOUTcount = 0;
						} else {
							saveOUT_day_count++;
						}
						if ( abortFlg ){
							//エラーをクライアントに送信
							_sconnect.send( _sendStr('Her', game.abort_machine) );
							//強制精算処理
							pay('', '42')
							.then(function(){
								keysocket.send('@PAY');
								keysocket.send('@PAYACTION_42');		//精算行動をログに記録させる
								activeFlg = false;
							},function(){
								keysocket.send('@DBLPAYERROR-PAYhp(42)');			//keysocketのログに@DBLPAYERRORを記録させる
								console.log( "pay error");
							});
							return;
						}
					
					} else if ( _tag == 'lcc'){
						game.credit -= layoutOption['limitcredit'];
						//内部drawPointに加算
						game.autodraw += Math.floor(layoutOption['limitcredit'] / game.conv_credit * game.conv_drawpoint);
						_msg = ''+game.autodraw;
					
					//制限時間などの筐体ロックによる精算処理
					} else if ( _tag == 'lck'){
						//精算処理
						pay('', '33')
						.then(function(){
							keysocket.send('@PAY');
							keysocket.send('@PAYACTION_33');		//精算行動をログに記録させる
							activeFlg = false;
						},function(){
							keysocket.send('@DBLPAYERROR-PAYl(33)');			//keysocketのログに@DBLPAYERRORを記録させる
							console.log( "pay error");
						});
						return;
					}
					_sconnect.send( _sendStr(_tag, _msg) );

					showGame();
					
				}
			}
		}
		
		//Error CallBack
		function onError(event) {
			console.log("Python WebSocket Serverの起動がまだなので、しばらくしたらリトライ");
			reconnectMode = true;

			//本来はここに
			setTimeout( pythonServerConnect, 10000);
			
		}
		
		//Close CallBack
		function onClose(event) {
			console.log("切断しました。");
			setCameraStatus("end");
			keysocket = null;
			$('#keysocket').removeClass('active');
		}
		
		
	}

	//連打送信チェック
	function keysocketSend( data ){
		if ( data in keyMapping ){
			var dt = new Date().getTime();
			if ( keyMapping[data].oldtime ){
				//前回送信したときの時間からmsec経過していない場合は無効にする
				if ( keyMapping[data].oldtime+keyMapping[data].msec > dt ){
					return;
				}
			}
			keyMapping[data].oldtime = dt;
		}
	
		keysocket.send(data);
	}
	
	//Pythonサーバへの接続（起動を考えてTimeer処理）
	setTimeout( pythonServerConnect, 1000);	
	
	/*
	 * ログ記録（10gameに1回ログを記録
	 * @access	public
	 * @param	なし
	 * @return	なし
	 * @info    なし
	 */
	function checkPlayLog(){
		if ( !endOneGame ){
		
			keysocket.send('bsy');					//1gameで強制停止
			endOneGame = true;
			if ( upGameCount % 10 == 0 ){
				playLog()
				.then(function(){
				},function(){
				});
			}
			
		}
		//時間をチェックして時間外になっていたら終了処理
		checkCloseGame();
	}
	
	var _ct = false;
	
	/*
	 * peer接続
	 * @access	public
	 * @param	object	cameraStream		設定情報
	 * @return	なし
	 * @info    なし
	 */
	function connectPeer (cameraStream) {
		var peer = new Peer(
			cameraid, {
			host: sigHost,
			// 2020-09-18 portを外部変更可能に修正
			port: sigPort,
			path: '/',  // サーバー側が自動的に/peerjsを追加するため、ここは/のみ
			secure: true,  // HTTPS/WSS使用（ngrok対応）
			key:peerjskey, 			//API key
			token:authID,
			config: {
				iceServers,
				"iceTransportPolicy":"all",
				"iceCandidatePoolSize":"0"
			},
			debug: 3 // 詳細なログをconsoleに表示
		});
		
		//グローバルへ
		_peer = peer;
		
		//初回のリセットをここへ
		game = $.extend(true, {}, resetGame() );

		$('#peerserver').addClass('active');
		showGame();

		//closeの場合
		peer.on('close', function() {
			console.log("=========== peer close");
			setCameraStatus("end");
		});

		peer.on('disconnected', function() {
			console.log("=========== peer disconnected");
			setCameraStatus("end");
			disconFlg = true;
			
			$('#peerserver').removeClass('active');
			$('#peerserver').addClass('error');
		});

		// 閲覧側からの接続要求をハンドリングします
		peer.on('connection', function(dataConnection) {
			console.log("===========!!peer connect Start");
			keysocket.send('@peerCNCT');				//keysocketのログに@peerCNCTを記録させる

			//leave reset
			$('#nowleavetime').text('');

			//言語設定をnullに
			languageMode = null;
			//connectionがあったらsleepCountはリセット
			sleepCount = 0;

			//metaデータから認証
			checkAuth(dataConnection.metadata)
			.then(
				function(data){
					console.log( data );
					keysocket.send('@peerAUTH');				//keysocketのログに@peerCCTを記録させる
				
					//FireFox対策
					if ( activeFlg ){
						connectCloseMethod();
						closeSkipFlg = true;
					}
					//プレイが一度でも開始された。
					playedFlg = true;
/*
					//他の接続を切る
					console.log( peer.connections );
					var peers = Object.keys(peer.connections);
					for (var i = 0, ii = peers.length; i < ii; i++) {
						//console.log( peers[i] );
						//console.log( peer.connections[peers[i]][0].peer );
						if ( peer.connections[peers[i]][0].peer != dataConnection.peer ){
							//ログインしたものと違うキー peer.connections[peers[i]][0].peer
							peer._cleanupPeer( peer.connections[peers[i]][0].peer );
							console.log( 'other connect close', peers[i] );
						}
					}
*/
					dataConnection.on('open', function(data){
						console.log( '===========!!dataConnection open');
						lastTimestamp =  (new Date()).getTime();
						//checkReadyGame(this);
					});


					//接続時に自動起動関係はオフにする
					autoModeFlg = false;
					autoStopFlg = false;
				
					//2020-06-18 connectLost誤爆しないようにここでpingアップ
					pingCount = 1;
				
					//2020-07-16 lastTimestampを更新
					lastTimestamp = (new Date()).getTime();
					activeFlg = true;

					noPayFlg = true;				//未清算フラグをセット
					clearTimeout(noPayTimer);		//未清算のタイマーをクリア
					console.log( "===========!!clear auto pay" );
					checkCloseGame();				//営業時間チェック
					
					// 接続が確立したので、カメラ側からMediaConnectionでStreamを渡します
					var mediaConnection = peer.call(dataConnection.peer, cameraStream);
					_sconnect = dataConnection;
					// 終了予告タイマー
					noticeTimeout = setTimeout(function(){
						if ( activeFlg ){
							_sconnect.send( _sendStr('Dnt',  '') );
						}
					}, getNoticeTime());
					
					$('#useractive').addClass('active');

					dataConnection.on('close', function(data){
						console.log( '===========!!dataConnection close' );
						if ( !closeSkipFlg ){
							connectCloseMethod();
						}
						closeSkipFlg = false;

						//離席時間をリセット
						lvtime = 0;
						$('#useractive').removeClass('active');
						showGame();
						
						//2020-06-08 connection lost がキャンセルだったことを考慮してここでlnkもlost下かを確認
						setTimeout(function(){
							setCameraStatus('status');			//確認用取得（ここに設定変更の処理が入る）
						},1000);
					});
					//データチャンネルハンドリング
					dataConnection.on('data', function(data){
						//var _json = JSON.parse( data);
						//console.log(_json);
						//var _t = _json['tag'];
						
						checkCloseGame();
						
						var _darry = data.trim().split(',');
						var _t = _darry[0].split(':')[1];
						var _msg = _darry[1].split(':')[1];

						//2020-06-18 pingをカウント
						if ( _t == 'ping' ){
							pingCount++;
							showGame();
							return;
						}
						
//						console.log( 'dc:'+_t);
						if ( _msg != '' ){
							userSignalLog( _t+' ['+_msg+']');
						} else {
							userSignalLog( _t );
						}

						//言語設定 2019-06-21 追加
						if ( _t == 'Lng' ){
							languageMode = _msg;
							showGame();
							dataConnection.send( _sendStr('Lng', _msg) );
							console.log( '=========== client ready' );
							return;
						}
						//特殊処理系
						if ( _t == 'cpb' ){
							//ポイント購入
							requestSettle(_msg);
							return;
						}
						if ( _t == 'ccc' ){
							//プレイポイント→クレジット変換処理
							execConvCredit()
							.then(function(data){
								//正常終了
								keysocket.send('@CREDIT_'+game.credit);				//2019-11-26 新バージョン用
								dataConnection.send( _sendStr('Acre', game.credit) );
								dataConnection.send( _sendStr('Apt',  game.playpoint) );
								dataConnection.send( _sendStr('Cst',  'ok') );
							},function(data){
								console.log( data );
								//エラー処理
								dataConnection.send( _sendStr('Cst',  data.status) );
							});
							return;
						}
						if ( _t == 'cca' ){
							//プレイポイント→クレジット変換処理（金額指定）
							var amount = parseInt(_msg);
							console.log('📝 Convert credit amount request:', amount);
							execConvCreditAmount(amount)
							.then(function(data){
								//正常終了
								keysocket.send('@CREDIT_'+game.credit);
								dataConnection.send( _sendStr('Acre', game.credit) );
								dataConnection.send( _sendStr('Apt',  game.playpoint) );
								dataConnection.send( _sendStr('Cst',  'ok') );
								console.log('✅ Convert credit amount success');
							},function(data){
								console.log('❌ Convert credit amount failed:', data);
								//エラー処理
								dataConnection.send( _sendStr('Cst',  data.status) );
							});
							return;
						}
						// 韓国統合用：クライアントからplaypointを設定
						if ( _t == 'Spt' ){
							var newPlaypoint = parseInt(_msg);
							if (!isNaN(newPlaypoint) && newPlaypoint >= 0) {
								console.log('💰 [Korea] Setting playpoint from client:', newPlaypoint);
								game.playpoint = newPlaypoint;
								game.koreaMode = true;  // 韓国モードを有効化（gameオブジェクトに直接設定）
								// 韓国モード用デフォルト値を設定（未定義の場合）
								if (!game.conv_point || game.conv_point <= 0) {
									game.conv_point = 100;  // デフォルト: 100ポイント = 50クレジット
									console.log('💰 [Korea] Set default conv_point:', game.conv_point);
								}
								if (!game.conv_credit || game.conv_credit <= 0) {
									game.conv_credit = 50;  // デフォルト: 50クレジット
									console.log('💰 [Korea] Set default conv_credit:', game.conv_credit);
								}
								if (!game.tester_flg) {
									game.tester_flg = 0;  // 通常ユーザーとして扱う
								}
								console.log('💰 [Korea] Mode enabled: game.koreaMode =', game.koreaMode, 'conv_point:', game.conv_point, 'conv_credit:', game.conv_credit, 'credit:', game.credit);
								keysocket.send('@SETPOINT_'+newPlaypoint);
								keysocket.send('@KOREA_MODE_ENABLED');
								dataConnection.send( _sendStr('Apt',  game.playpoint) );
								dataConnection.send( _sendStr('Acre', game.credit) );     // クレジットを送信（重要！）
								dataConnection.send( _sendStr('Acp',  game.conv_point) );  // 変換ポイントを送信
								dataConnection.send( _sendStr('Acc',  game.conv_credit) ); // 変換クレジットを送信
								showGame();
							} else {
								console.log('❌ [Korea] Invalid playpoint value:', _msg);
							}
							return;
						}
						if ( _t == 'pay' ){
							//精算処理
							pay('', '11')
							.then(function(){
								keysocket.send('@PAY');					//keysocketのログに@PAYを記録させる
								keysocket.send('@PAYACTION_11');		//精算行動をログに記録させる
								activeFlg = false;
							},function(){
								keysocket.send('@DBLPAYERROR(11)');			//keysocketのログに@DBLPAYERRORを記録させる
								console.log( "pay error");
							});
							return;
						}
						
						//送信可能電文チェック
						
						//maxbet game1playはcreditがなければ通さない
						if ( game.credit <= 0 && ( _t == 'bsb' || _t == 'bsa' || _t == 'bst' ) ) return;
						
//						if ( _t == 'bse' ){
						//フルオート用の信号が来たらautoFlgを設定
						if ( _t == 'bst' ){
							if ( autoModeFlg == false ){
								autoModeFlg = true;
							} else {
								autoModeFlg = false;
							}
						}
						
						
						//営業時間外処理（🇰🇷 Koreaモードはバイパス）
						if ( !closeGameFlg || game.koreaMode === true ){
							keysocketSend(_t);
							//keysocket.send(_t);
						} else {
							//console.log( 'NG control: '+ _t );
							userSignalLog( '!!NG '+_t );
							//2020-01-30 新基盤だとauto止めるコードを送信しないと止まらない
							//営業時間外なのでautoも止める
							keysocket.send('bae');
							if ( !closePay ){
								closePay = true;
								//精算処理
								pay('', '33')
								.then(function(){
									keysocket.send('@PAY');					//keysocketのログに@ACTを記録させる
									keysocket.send('@PAYACTION_33');		//精算行動をログに記録させる
									activeFlg = false;
								},function(){
									keysocket.send('@DBLPAYERROR-cls(33)');			//keysocketのログに@DBLPAYERRORを記録させる
									console.log( "pay error");
								});
							}
							
						}
					});
					
					//初回設定（未清算状態ならmember_noが残っているのでその場合はステータスを保持）
					if( game.member_no == 0 ){
						game = $.extend( true, game, data.game );
						console.log( game );
						reloadGame = false;
					} else {
						reloadGame = true;
						//2020-07-15 リロード時にbitを反映させる
						game.playpoint = data.game.playpoint;
					}
					
					//一応画面に現在の状態を表示
					showGame();

					readyFlg = false;

					//初回のデータを送る（コネクションの確立に時間がかかるので１秒待ち）
					checkReadyGame(dataConnection);
					setTimeout(function(){
						if ( !readyFlg ){
							checkReadyGame(dataConnection);
						}
					},2000);
					
					console.log( '===========!!peer connect End' );

				},
				function(reason){
					keysocket.send('@peerNG');				//keysocketのログに@peerCCTを記録させる
					//認証失敗
					console.log("checkAuth failed");
					//強制的に接続を切る
					dataConnection.close();
					console.log( "dataConnection.close" );
				}
			);
		});

		var unloadflg = false;
		// ページを閉じた際にpeerをクリアします
		$(window).on('beforeunload', function(){
			keysocket.close();
			if (! peer.destroyed) {
				peer.destroy();
			}
		});

		/*
		 * メンテナンスボタンクリックイベント
		 * @info    なし
		 */
		$('#mentebutton').on('click', function(){
			var mno = 0;

			if ( $(this).hasClass('deactive') ){
				mno = 1;
			}
			
			if ( confirm(confMessage[mno]) ){
				//ステータスを送る
				if ( mno == 0 ){
					//メンテナンスモードへ移行
					keysocket.send('@MNT_MODE');				//keysocketのログに@MNT_MODEを記録させる
					toMaintenance();
				} else {
					//setCameraStatus("start");
					//$(this).text(buttonMessage[0]);
					//$(this).removeClass('deactive');
					keysocket.send('@MNT_RESET');				//keysocketのログに@MNT_RESETを記録させる
					location.reload();
				}
			}
		});
		
		/*
		 * 終了ボタンクリックイベント
		 * @info    なし
		 */
		$('#exitbutton').on('click', function(){
			if ( confirm('カメラサーバを終了しますか？') ){
				//もしプレイ中であれば強制的に精算処理をする。
				pay('auto', '43')
				.then(function(){
					//終了ステータスを送る
					setCameraStatus("end")
					.then(function(){
						//keysocketとの通信をcloseする
						keysocket.close();
						//peerも終了
						if (! peer.destroyed) {
							peer.destroy();
						}
						alert("終了しました");
						//Chromeはタブを閉じれないのでblankのタブにする
						window.open('about:blank', '_self').close();
					});
				});
			}
		});
		
		var notReadycnt = 0;
		function checkReadyGame( dc, tm=1000 ) {
			setTimeout(function(){

				//他の接続を切る（中身は消してはダメ）
				console.log( peer.connections );
				var peers = Object.keys(peer.connections);
				var delkeys = [];
				for (var i = 0, ii = peers.length; i < ii; i++) {
					//console.log( peers[i] );
					//console.log( peer.connections[peers[i]][0].peer );
					if ( peer.connections[peers[i]][0].peer != _sconnect.peer ){
						//ログインしたものと違うキー peer.connections[peers[i]][0].peer
						if ( peer.connections[peers[i]][0].open == true ){
							//まだopenしているものがある場合
							peer._cleanupPeer( peer.connections[peers[i]][0].peer );
							console.log( 'other connect close', peers[i] );
							delkeys.push(peers[i]);
						}
					}
				}

				if ( !languageMode ){
					if ( _sconnect.open ){
						notReadycnt++;
						console.log( 'client not standby ... 1sec wait' );
						if ( notReadycnt > 20 ){
							if ( dc.open == false ){
								console.log( 'checkReadyGame::connect lost' );
								//強制的に接続を切る
								_sconnect.close();
								// 2023-09-25 ここが実行されることが頻発しているようなのでセッションを切るだけにとどめる
								// keysocket.send('@PAY');		//強制精算を実行しておく
								// keysocket.send('@PAYACTION_43');		//精算行動をログに記録させる
								return;
							}
						}
						checkReadyGame(dc );
					} else {
						//2020-06-08 connection lost がキャンセルだったことを考慮してここでlnkもlost下かを確認
						setTimeout(function(){
							setCameraStatus('status');			//確認用取得（ここに設定変更の処理が入る）
						},1000);
					}
					return;
				}
				
				if ( readyFlg ) return;
				
				console.log( '===========!!send data start' );
				if ( reloadGame == false ){
					if ( layoutOption['limittime'] ){
						if ( layoutOption['limittime'] > 0 ){
							keysocket.send('@SETLIMITTIME_'+layoutOption['limittime']);
						}
					}
				}
				
				if ( activeBB ){
					_sconnect.send( _sendStr('Aabb',  'on') );
				}
				if ( activeBonus ){
					_sconnect.send( _sendStr('Aab',  'on') );
				}
				_sconnect.send( _sendStr('Acre', game.credit) );
				_sconnect.send( _sendStr('Apt',  game.playpoint) );
				//2020-07-17 coin(drawpoint)を追加
				_sconnect.send( _sendStr('Adp',  game.drawpoint) );
				_sconnect.send( _sendStr('Atc',  game.total_count) );
				_sconnect.send( _sendStr('Act',  game.count) );
				_sconnect.send( _sendStr('Abb',  game.bb_count) );
				_sconnect.send( _sendStr('Arb',  game.rb_count) );
				_sconnect.send( _sendStr('Acp',  game.conv_point) );
				_sconnect.send( _sendStr('Acc',  game.conv_credit) );
				_sconnect.send( _sendStr('Amc',  game.min_credit) );
				_sconnect.send( _sendStr('Alv',  leaveTime) );					//2020-06-03追加 離席時間
				_sconnect.send( _sendStr('Amp',  maxplusTime) );				//2020-06-10追加 MAX+START delayTime
				//_sconnect.send( _sendStr('RDY',  game.member_no+'_'+game.play_dt) );
				console.log( '===========!!RDY send' );

				//2020-09-18 ボーナス途中で再起動したときにpython側とのgame数に誤差がでるので状態を送信
				if ( activeBonus == true ){
					if ( activeBB == true ){
						keysocket.send('@SETBB');
					} else {
						keysocket.send('@SETRB');
					}
				} else {
					keysocket.send('@SETNOTBONUS');
				}

				keysocket.send('@SETBONUS_'+game.rb_count+'_'+game.bb_count);
				keysocket.send('@SETGAME_'+game.total_count+'_'+game.count);
																//keysocketのログに@RDYを記録させる
				keysocket.send('@ASSIGN_'+game.member_no);		//メンバーNOを記録させる
				//2020-06-24 テスターモードを送信
				keysocket.send('@TESTER_'+game.tester_flg);		//テスターフラグを送信
				keysocket.send('@RDY');							//keysocketのログに@RDYを記録させる
				lastTimestamp =  (new Date()).getTime();		//強制退出タイマーをリセット

			}, tm);
		}
		
		/*
		 * close時処理（firefox対策）
		 * @access	public
		 * @param	なし
		 * @return	なし
		 * @info    なし
		 */
		function connectCloseMethod() {
			console.log( 'connection lost');
			keysocket.send('@peerCLS');				//keysocketのログに@peerCLSを記録させる
			resetMachine();
			//アクティブフラグ解除
			activeFlg = false;
			//終了予告タイマーの解除
			clearTimeout(noticeTimeout);
			//決済インターバルの解除
			clearInterval( intvCheckBuy );
			//離席時間リセット
			lvtime = 0;
			//メッセージ初期化
			$('#nowleavetime').text('');
			/*
			//autoplay中に切断が発生したときの停止信号を設定
			if ( autoModeFlg ) autoStopFlg = true;
			//未清算の場合はタイマーを設定
			if ( noPayFlg ){
				console.log( '===========no pay wait');
				noPayTimer = setTimeout(function(){
					if ( activeFlg ) return;
					console.log( "===========!!auto pay" );
					//pay('auto')
					execPay('auto')
					.then(function(){
						keysocket.send('@autoPAY');				//keysocketのログに@ACTを記録させる
					});
				}, autoPayTime * 60000 );
			}
			*/
		}
		
		//メンテナンスモード切替
		function toMaintenance(){
			//接続中の場合
			if ( activeFlg ){
				//強制的に精算処理
				pay('auto', '43')
				.then(function(){
					//現在の接続を解除
					connectCloseMethod();
					//ステータスを変更
					setCameraStatus("end");
					$('#mentebutton').addClass('deactive');
					$('#mentebutton').html(buttonMessage[1]);
					keysocket.send('@PAY');
					keysocket.send('@PAYACTION_43');		//精算行動をログに記録させる
					activeFlg = false;
				},function(){
					keysocket.send('@DBLPAYERROR(43)');			//keysocketのログに@DBLPAYERRORを記録させる
					console.log( "pay error");
				});
			} else {
				//ステータスを変更
				setCameraStatus("end");
				$('#mentebutton').addClass('deactive');
				$('#mentebutton').html(buttonMessage[1]);
			}
		}
	}
	
	//再起動時などで既にボーナス状態なのに感知できていない場合用の強制ボーナスフラグ
	$('#bb_on').bind('click', function(){
		if ( $(this).prop('checked') ){
			activeBonus = true;
			activeBB = true;
			$.cookie('bb', 'on', { expires: 365 });
		} else {
			activeBonus = false;
			activeBB = false;
			$.cookie('bb', 'off', { expires: 365 });
		}
		showGame();
	});
	$('#rb_on').bind('click', function(){
		if ( $(this).prop('checked') ){
			activeBonus = true;
			$.cookie('rb', 'on', { expires: 365 });
		} else {
			activeBonus = false;
			$.cookie('rb', 'off', { expires: 365 });
		}
		showGame();
	});

	$('#command').bind('keypress', function(e){
		if (e.which == 13){
			var cmd = $('#command').val();
			if ( cmd.length == 2 ){
				keysocket.send('@CMD_'+cmd);
			} else {
				keysocket.send(cmd);
			}
		}
	});

	$('#comdsend').bind('click', function(){
		var cmd = $('#command').val();
		var sel = $('#selectcommand').val();
		if ( sel != '' ){
			cmd = sel
		}
		if (cmd != '' ){
			keysocket.send('@CMD_'+cmd);
		}
	});

