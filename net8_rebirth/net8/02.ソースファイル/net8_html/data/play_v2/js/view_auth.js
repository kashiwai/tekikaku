/**
 * @fileOverview
 * スロット用ゲーム画面JS
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
 *            2020/06/10 ver 1.4.0-s1-c1  村上俊行 デバッグモードフラグ追加 MAX+STARTボタンの時間調整電文 Ampを追加
 *            2020/06/16 ver 1.4.1-s1-c1  村上俊行 セミオート対応 自動変換数値がauto時に減らないバグの修正 Bonus時のキーボード抑制
 *            2020/06/18 ver 1.4.1a-s1-c1 村上俊行 連打start対策 connectionチェック機能追加
 *            2020/06/19 ver 1.4.1a-s1-c2 村上俊行 iPhoneでの誤動作を招くのでショートカットを削除
 *            2020/06/22 ver 1.4.1d-s1-c1 村上俊行 clickModeの実装
 *            2020/06/23 ver 1.4.1e-s1-c1 村上俊行 defaultデザインでもpanelを切り替えたらautoがoffになる修正
 *            2020/06/29 ver 1.4.1f-s1-c1 村上俊行 ショートカットが効かなくなる問題の修正
 *            2020/06/29 ver 1.4.2-s1-c1  村上俊行 ショートカットが効かなくなる問題の修正 04コード対応
 *            2020/06/29 ver 1.4.3-s1-c1  村上俊行 連チャン数を表示するように修正
 *            2020/07/17 ver 1.4.3-s1-c2  村上俊行 デバッグモードでない場合はconsoleを消す
 *            2020/07/17 ver 1.4.3-s1-c3  村上俊行 coin表示可能なように修正
 *            2020/09/18 ver 1.4.5-s1-c1  村上俊行 シグナリングサーバportを変更可能に修正
 *            2023/05/24 ver 1.4.5-s5-c1  村上俊行 無操作強制退出機能の削除
 * @using
 * @desc
 *
 */
;
//var debugMode = true;				//台番号をクリックするとデバッグが表示されます
var debugMode = false;				//本番は上をコメントにしてこちらを有効にしてください
var _sconnect;						//外部call用dataConnection
var _savestream;					//Video確認用
	//保持データ
	// play_embedで事前にgameが定義されている場合は、その値を保持する
	if (typeof game === 'undefined') {
		var game = {
			'credit'      : 0,
			'playpoint'   : 0,
			'drawpoint'   : 0,
			'total_count' : 0,
			'bb_count'    : 0,
			'rb_count'    : 0,
			'count'       : 0,
			'min_credit'  : 2,
			'ccc_status'  : '',
		};
	} else {
		// 既存のgameオブジェクトに不足しているプロパティを追加
		game.drawpoint = game.drawpoint || 0;
		game.total_count = game.total_count || 0;
		game.bb_count = game.bb_count || 0;
		game.rb_count = game.rb_count || 0;
		game.count = game.count || 0;
		game.min_credit = game.min_credit || 2;
		game.ccc_status = game.ccc_status || '';
		console.log('📝 Preserved existing game object - playpoint:', game.playpoint, 'credit:', game.credit);
	}
	//2020-06-22 clickMode実装
	var clickMode = true;						// false:down/up式 true:click式
	//2020-06-11 セミオート設定追加 (2020-06-15 trueに変更)
	var semiAutoPlayFlg = true;					//セミオートモードのデフォルト設定
	var activeBonus = false;					//ボーナス中かどうかの判定
	var activeBB = false;						//BBのボーナス判定
	var readyFlg = false;						//RDY受信フラグ
	var cccCount= 0;							//credit変換中Count
	var maxstartFlg = false;					//MaxStart用
	var dataConnection;							//dataConnection(peer)
	var endOneGame = true;						//ゲーム終了フラグ false:1ゲームサイクル中 true:ゲーム終了
	var startTop;
	var startLeft;
	var startWidth;
	//autoplay用設定
	var usePoint = 0;							//使用ポイント数
	var targetUsePoint = 0;						//Max利用可能ポイント
	var aliveInterval = null;					//離籍確認タイマー
	var pingInterval = null;					//pingタイマー
	var lastTimestamp = (new Date()).getTime();	//タイムスタンプ
	var leaveTime = 5 * 60000;					//離籍警告時間
	var bonusCountMark = '-';					//ボーナス中のゲーム数表記
	var addCredit = 0;							//残り加算クレジット数
	var autoStopSendFlg = false;				//autoモードの停止信号送信フラグ
	var autoStopSignal = false;					//autoモード停止フラグ
	var autoMode = false;						//autoモード設定
	var koreaMode = false;						//韓国統合モード（Spt送信時にtrue）
	var autoModePrep = false;					//韓国用: AUTO待機状態（STARTで開始）
	var videoWidth;								//videoサイズ
	var recvLang = false;						//recv 'Lng'

	var inCreditCount = 0;						//inCreditのカウント
	var inCreditTimeSpan = 0;					//inCreditの来る間隔
	var inCreditCheckTime = 0;					//inCreditの初回time
	var inCreditStartTime = 0;					//inCreditの開始時間

	//var autoPlayRestartTime = 2000;				//Signal5またはSac後から bst を送信するまでの時間(ms)
	var autoPlayRestartTime = 1000;				//Signal5またはSac後から bst を送信するまでの時間(ms)
	var autoPlayResendTime  = 3000;				//bst送信後にSignal_0が来ない
	
	var maxPlusStartDelay   = 300;				//MAX+START時のbet->startのディレイ時間
	
	var meoshiFlg = false;						//目押しモード判定
	var singlePushMode = false;					//目押し中は他のボタンが押せないフラグ
	
	var reelMoveFlg = false;
	
	var changeManualMode = false;				//自動マニュアルモードへの切り替え実行フラグ
	
	var btnStatus = {'sendBtns1': false, 'sendBtns2': false, 'sendBtns3': false };
	var stackButton = [];
	var lastid = [];

	var autoFirstEventFlg = false;
	var startTimestamp = null; // ゲーム開始時刻を記録

	/**
	 * SDK通知用ヘルパー関数
	 * 親ウィンドウ（SDK）にpostMessageでイベントを送信
	 */
	function notifySDK(eventType, payload) {
		if (window.parent !== window) {
			try {
				// セキュリティ: 許可されたオリジンのみに送信
				// 開発環境とプロダクション環境の両方をサポート
				var allowedOrigins = [
					'https://mgg-webservice-production.up.railway.app',
					'http://localhost:3000',
					'http://localhost:8000',
					window.location.origin  // 同一オリジン
				];

				// 親ウィンドウのオリジンを取得（可能な場合）
				var targetOrigin = '*';
				try {
					if (document.referrer) {
						var referrerUrl = new URL(document.referrer);
						var referrerOrigin = referrerUrl.origin;
						// 許可リストに含まれている場合のみ使用
						if (allowedOrigins.indexOf(referrerOrigin) !== -1) {
							targetOrigin = referrerOrigin;
						}
					}
				} catch (e) {
					// referrer取得失敗時はデフォルトのまま
				}

				window.parent.postMessage({
					type: 'game:' + eventType,
					payload: payload
				}, targetOrigin);
				writeLog('SDK通知: ' + eventType + ' (origin: ' + targetOrigin + ')');
			} catch (e) {
				console.error('SDK通知エラー:', e);
			}
		}
	}

	//peer setting
	var peersetting = {
		host: sigHost,
		//2020-09-18 Port変更可能に修正
		port: sigPort,
		secure: true,  // HTTPS/WSS使用（iframe対応）
		key:peerjskey,
		token:authID,
		config: {
			'iceServers': iceServers,
			"iceTransportPolicy":"all",
			"iceCandidatePoolSize":"0"
		},
		debug: 0
	};
	
	//2020-07-17 デバッグモード以外ではconsole要素を消す
	if ( !debugMode ){
		$('#consolelog').remove();
	}
	//FireFox media.peerconnection.enabled = false の対応
	try {
		var a = new window.RTCPeerConnection();
	} catch(e) {
		$('#loading').html('接続に失敗しました。(RTC01)');
	}

	$('.game-after-button').bind(_touch,function(e){
		// SDK通知: ゲーム終了
		notifySDK('end', {
			result: 'completed',
			credit: game.credit,
			pointsWon: game.drawpoint,
			in_credit: game.in_credit || 0,
			out_credit: game.credit || 0,
			play_count: game.total_count || 0,
			bb_count: game.bb_count || 0,
			rb_count: game.rb_count || 0,
			total_count: game.total_count || 0,
			duration: Date.now() - (startTimestamp || Date.now())
		});

		window.location.href = '/gameafter.php' + '?' + machineno + '-' + authID;
	});


	//目押し操作不可に変更
	setBonusMode(false);
	
	showDateandName();
	
	var peer = new Peer( peersetting );

	// iframe親フレームへのデバッグ通知関数
	function notifyParent(eventType, data) {
		if (window.parent !== window) {
			window.parent.postMessage({
				type: 'NET8_PEER_EVENT',
				event: eventType,
				data: data,
				timestamp: Date.now()
			}, '*');
		}
		console.log('🔌 PeerJS Event:', eventType, data);
	}

	// PeerJSエラーハンドリング
	peer.on('error', function(err) {
		console.error('❌ PeerJS Error:', err.type, err.message);
		notifyParent('peer_error', { type: err.type, message: err.message });
	});

	peer.on('disconnected', function() {
		console.warn('⚠️ PeerJS Disconnected from signaling server');
		notifyParent('peer_disconnected', {});
	});

	peer.on('close', function() {
		console.warn('🔒 PeerJS Connection closed');
		notifyParent('peer_closed', {});
	});

	peer.on('open', function(){
		//id
		$('#my-id').text(peer.id);
		notifyParent('peer_open', { peerId: peer.id, cameraId: cameraid });

		showPhase('open');

		//データチャンネル
		dataConnection = peer.connect(cameraid,{
			'metadata': memberno+':'+authID
		});
		showPhase('connect');
		notifyParent('data_connecting', { cameraId: cameraid });

		dataConnection.maxRetransmits = 1;

		// データ接続のエラーハンドリング
		dataConnection.on('error', function(err) {
			console.error('❌ DataConnection Error:', err);
			notifyParent('data_error', { error: err.toString() });
		});

		dataConnection.on('open', function() {
			console.log('✅ DataConnection opened');
			notifyParent('data_open', { cameraId: cameraid });
		});

		dataConnection.on('close', function(){
			console.log( 'connect lost' );
			notifyParent('data_close', {});
			setTimeout(function(){
				if ( !endPlayFlg ){
					ShowConnectError('connect lost');
				} else {
					console.log( 'dataConnection.close' );
				}
				//connectionだけが落ちることがあるのでconnectionが切れたらpeerを切る
				if (! peer.destroyed) {
					peer.destroy();
				}
			}, 5000);
		});
		//データチャンネルハンドリング
		dataConnection.on('close', function(data){
		});

		_sconnect = dataConnection;

		//購入ボタン生成
		buildPayLink();

		//一定時間操作がない場合は終了させるイベント設定及びセッションの維持
		aliveInterval = setInterval(function(){
			var span = (new Date()).getTime() - lastTimestamp;
			var mtime = leaveTime / 60000;
			writeLog( ""+mtime+"min not action check:"+span );
			// 2023/05/24 無操作退出機能の削除
			/*
			if ( span >= leaveTime ){
				$('#loadinglost').hide();
				//事前に開いているモーダルがあれば閉じる
				$('#pay-modal').modal('hide');
				$('#buypt-modal ').modal('hide');
				$('#settle-modal').modal('hide');
				$('#convcr-modal').modal('hide');
				$('#error-modal').modal('hide');
				//精算用電文送信
				_sconnect.send(_sendStr( 'pay', 'timeout'));
				$('#end-modal .modal-title').text( errorMessages['U5052'] );
			}
			*/
			
			//セッション維持用API呼び出し
			sessionAPI()
			.then(function(data){
				
			},function(data){
				writeLog( 'sessionAPI error' );
			});
		}, 60000 );
		
		//サーバにpingを送信
		pingInterval = setInterval(function(){
			dataConnection.send(_sendStr( 'ping', ''));
			
			showDateandName();
		},10000 );
		var tendtime = 0;
		//upをどこで放しても有効なように
		$(document).bind(_touchend, 'body', function(e) {
			if ( !clickMode ){
				if ( lastid.length > 0 ){
					var id = lastid.shift();
					dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1]+'u', "up"));
				}
			}
			//ダブルタップ禁止処理
			var now = new Date().getTime();
			if ((now - tendtime) < 350){
				e.preventDefault();
			}
			tendtime = now;
		});
		$('.sendBtn').each(function(){
			//down
			$(this).bind(_touch,function(e){
				console.log( $(this).attr('id') );
				e.preventDefault();
				var id = $(this).attr('id').split('_')[0];
				//キー制御に渡す
				if ( keyCtrl(id) ){
					if ( !clickMode ){
						lastid.push(id);
					}
				}
			});
		});

		/* 2020-06-03 追加 一括押し */
		$('#ms-button').bind(_touch,function(e){
			if ( maxstartFlg ) {
				console.log( '## maxstartFlg!!');
				return;
			}
			if ( lastid.length > 0 ) {
				console.log( '## lastid!!');
				return;
			}
			if ( reelMoveFlg ){
				console.log( '## reel active push!!');
				return false;
			}
			if ( singlePushMode || autoMode ){
				console.log( '## bad push!!');
				return;
			}
			maxstartFlg = true;
			dataConnection.send(_sendStr( 'bsbd', "setdown"));
			setTimeout(function(){
				dataConnection.send(_sendStr( 'bss', "setdown"));
				//maxstartFlg = false;
			},maxPlusStartDelay);
		});

		/*
		 * keydownイベント
		 * @access	public
		 * @param	object		e			イベント
		 * @return	なし
		 * @info    なし
		 */
		var saveKeydown = {};
		$(document).keydown(function(e) {
			//maxstart中は押せない
			if ( maxstartFlg ){
				console.log( 'maxstartFlg ng' );
				return;
			}//リピート回避
			//autoplay時にはショートカットを使えない
			if ( $('input[name=ctrl-autoplay]:checked').val() == 1 ){
				console.log( 'autoplay ng' );
				return;
			}//目押しのセレクターが開いている時は押せない
			if ( $('#menu_select').attr('aria-expanded') == 'true'){
				console.log( 'menu_select ng' );
				return;
			}
			if ( $('#menu2_select').attr('aria-expanded') == 'true') {
				console.log( 'menu2_select ng' );
				return;
			}
			//セミオートモードで目押し中ならキー操作させない
			if ( semiAutoPlayFlg && meoshiFlg ) {
				console.log( 'semiAutoPlayFlg ng' );
				return;
			}
			id = key2Tele(e.keyCode)
			try{
				if (saveKeydown[id] == true) {
					console.log( 'saveKeydown ng' );
					return;
				}
			} catch(e) {
				
			}
			//writeLog( e.keyCode );

			if ( id != '' ){
				var cid = 'sendBtn'+id;
				
				if ( keyCtrl(cid) ){
					//2020-06-29 clickmodeの時はフラグチェックを入れない
					if ( !clickMode ){
						saveKeydown[id] = true;
					}
				}
			}
		});

		$(document).keyup(function(e) {
 			if ( clickMode ) return;

			//maxstart中は押せない
			if ( maxstartFlg ) return;
			//autoplay時にはショートカットを使えない
			if ( $('input[name=ctrl-autoplay]:checked').val() == 1 ) return;
			//目押しのセレクターが開いている時は押せない
			if ( $('#menu_select').attr('aria-expanded') == 'true') return;
			if ( $('#menu2_select').attr('aria-expanded') == 'true') return;
			//セミオートモードで目押し中ならキー操作させない
			if ( semiAutoPlayFlg && meoshiFlg ) return;
			id = key2Tele(e.keyCode)
			if ( id != '' ){
				if ( saveKeydown[id] ){
					dataConnection.send(_sendStr( 'b'+id+'u', "up"));
				}
				saveKeydown[id] = false
			}

		});

		//2020-06-08 デバッグモードのフラグ切替
		if ( debugMode ){
			/*
			 * デバッグモード設定(clickイベント）
			 * @access	public
			 * @param	なし
			 * @return	なし
			 * @info    なし
			 */
			$('#loading_connect').bind(_click, function(){
				$('#consolelog').toggle();
				$('#consolelog').animate({scrollTop: $('#consolelog')[0].scrollHeight}, 20);
				$('#consolelog').css('z-index', 10000 );
			});
			$('.situation-gc').bind(_click, function(){
				$('#consolelog').toggle();
				$('#consolelog').animate({scrollTop: $('#consolelog')[0].scrollHeight}, 20);

			});
			$('.situation-bb').bind(_click, function(){
				
			});
		}

		//目押し設定
		$('#form-meoshi,#form-meoshi2').bind('change', function(){
			var code = $(this).val();
			if ( code != 'bb0' ){
				//目押し
				if ( meoshiFlg ) return;
				dataConnection.send(_sendStr( code, 'bonus'));
				meoshiFlg = true;

				//2020-06-11 追加 セミオート
				semiAutoPlay();
			}
		});

		$('.selectbonus').bind('click', function(){
			var code = $(this).attr('value');
			var id = '#'+code+'_img';
			$('#selectedimg').attr('src', $(id).attr('src'));
			if ( code != 'bb0' ){
				meoshiFlg = true;
				dataConnection.send(_sendStr( code, 'bonus'));

				//2020-06-11 追加 セミオート
				semiAutoPlay();
			} else {
				meoshiFlg = false;
			}
		});
		$('.selectbonus2').bind('click', function(){
			var code = $(this).attr('value');
			var id = '#'+code+'_img2';
			$('#selectedimg2').attr('src', $(id).attr('src'));
			if ( code != 'bb0' ){
				meoshiFlg = true;
				dataConnection.send(_sendStr( code, 'bonus'));

				//2020-06-11 追加 セミオート
				semiAutoPlay();
			} else {
				meoshiFlg = false;
			}
		});

		//maxbitエリア処理
		$('#maxpoint').bind('keypress', function(){
			return leaveOnlyNumber(event);
		});
		//paste禁止
		$('#maxpoint').bind('paste', function(e){
			e.preventDefault();
		});
		//プレイポイント購入ボタン処理
		//$('.buyButton').bind(_click, function(){
		// 2021-09-24 clickに変更
		$('.buyButton').bind('click', function(){
			var data = $(this).attr('target');
			console.log( 'click buy('+data+')' );
			// 2021-09-24 選択されたら必ず閉じるように修正
			$('#buypt-modal').modal('hide');
			dataConnection.send(_sendStr( 'cpb', data));
		});

		//クレジット変換ボタン処理（従来の全額変換）
		$('#convCredit').bind(_click, function(){
			console.log( 'click convCredit:'+ cccCount);
			if (cccCount == 0){
				dataConnection.send(_sendStr( 'ccc', ''));
				cccCount++;
			}
		});

		//クレジット変換ボタン処理（金額指定）
		$('.conv-amount-btn').bind(_click, function(){
			var amount = $(this).data('amount');
			console.log( 'click conv-amount-btn: ' + amount);
			if (cccCount == 0){
				// 金額指定で変換（ccaコマンド: Convert Credit Amount）
				dataConnection.send(_sendStr( 'cca', amount));
				cccCount++;
			}
		});

		$('#payconf').bind(_click, function(){
			checkPoint()
			.then(function(data){
				if (data.status) {
					// 確認のモーダルを出す
					$('#pay-conf').modal();
				} else {
					// 直接精算処理実行
					dataConnection.send(_sendStr( 'pay', ''));
				}
			},function(data){
			
			});
		});

		//精算ボタン処理
		$('#pay').bind(_click, function(){
			//操作禁止処理を入れる
			console.log( 'click pay' );
			dataConnection.send(_sendStr( 'pay', ''));
		});
		
		
		//精算結果のモーダルが閉じた時の処理
		$('#end-modal').on('hide.bs.modal', function () {
			var lnk = $('#endgame_a').attr('href');
			location.href = lnk;
		});
		
		//データチャンネルハンドリング
		dataConnection.on('open', function(data){
			showPhase('dataConnection open');
			console.log( 'dataConnection open');
			notifyParent('data_channel_ready', { ready: true });

			// 即座にLngメッセージを送信（カメラがメッセージを待っている可能性）
			if ( !recvLang && dataConnection && dataConnection.open ){
				console.log('📤 Sending initial Lng message immediately');
				notifyParent('sending_lng', { immediate: true });
				try {
					dataConnection.send(_sendStr( 'Lng', languageMode ));
				} catch(e) {
					console.error('Failed to send Lng:', e);
					notifyParent('lng_send_error', { error: e.toString() });
				}
				// 応答がなければリトライ
				retryLang();
			}

			// SDK通知: ゲーム準備完了
			startTimestamp = Date.now(); // ゲーム開始時刻を記録
			notifySDK('ready', {
				timestamp: startTimestamp,
				machineNo: machineno
			});
		});
		
		
		//データチャンネルハンドリング
		dataConnection.on('data', function(data){
			//放置時間計測用
			lastTimestamp = (new Date()).getTime();

			// カメラからのデータを親フレームに通知（デバッグ用）
			notifyParent('camera_data', { data: data.substring(0, 100), timestamp: lastTimestamp });

			writeLog( 'recieve:'+data+' '+lastTimestamp);
			var _darry = data.trim().split(',');
			var _tag = _darry[0].split(':')[1];
			//電文にメッセージが入ることになったので再構築させる
			var msgAry = _darry[1].split(':');
			msgAry.shift();
			var _msg = msgAry.join(':');

			if( _tag == 'Signal_Game+1' ){
				if ( !activeBonus ){
					//bonusに入っているときはゲーム数を加算しない
					game.total_count++;
					game.count++;
					$('#total_count').text(game.total_count);
					$('#count').text(game.count);
				}
			} else if ( _tag == 'Signal_0' ){							//Signal_IN
				inCreditCount++;
				game.credit--;
				//サービス回し追加による数値補正
				if ( game.credit < 0 ) {
					game.credit = 0;
				} else {
					$('#animeNumber').animetionNumber( -1 );
				}
				$('#credit').text(game.credit);

				// SDK通知: プレイ開始（クレジット消費）
				notifySDK('play', {
					credit: game.credit,
					action: 'spin'
				});

			} else if ( _tag == 'Signal_1' ){							//Signal_OUT
				endOneGame = true;
				addCredit++;
				game.credit++;
				$('#credit').text(game.credit);
				$('#animeNumber').animetionNumber( 1 );

				// SDK通知: クレジット更新（勝利）
				notifySDK('win', {
					credit: game.credit,
					addCredit: 1
				});
			} else if ( _tag == 'Sac' ){								//Credit払い出し総数通知
				if ( $('#autoplay_credit').hasClass('autoplay-on') && !autoStopSignal ){
					setTimeout(function(){
						//autoPlay(true);
					},autoPlayRestartTime);
				} else {

				}

				// SDK通知: スコア更新（払い出し完了）
				notifySDK('score', {
					credit: game.credit,
					playpoint: game.playpoint,
					drawpoint: game.drawpoint,
					bb_count: game.bb_count,
					rb_count: game.rb_count
				});
			} else if ( _tag == 'Signal_3' ){							//Signal_BB_Start
				writeLog( '================BIG_Start================' );
				if ( !activeBonus ){
					activeBonus = true;
					activeBB = true;
					game.bb_count++;
					game.count = 0;										//カウントリセット
				}
				$('#bb_count').text(game.bb_count);
				$('#count').text(bonusCountMark);
				$('#bb_count').bonusAnime(true);
				$('#bonus_count').text(game.bb_count+game.rb_count);
				$('#bonus_count').bonusAnime(true);

				// SDK通知: BB当選
				notifySDK('bonus', {
					type: 'BB',
					count: game.bb_count,
					totalBonus: game.bb_count + game.rb_count
				});
			} else if ( _tag == 'Signal_2' ){							//Signal_RB_Start
				writeLog( '================REG_Start================' );
				activeBonus = true;
				if ( !activeBB ){
					game.rb_count++;
					game.count = 0;										//カウントリセット
					$('#rb_count').bonusAnime(true);
				}
				$('#rb_count').text(game.rb_count);
				$('#count').text(bonusCountMark);
				$('#bonus_count').text(game.bb_count+game.rb_count);
				$('#bonus_count').bonusAnime(true);

				// SDK通知: RB当選
				notifySDK('bonus', {
					type: 'RB',
					count: game.rb_count,
					totalBonus: game.bb_count + game.rb_count
				});
			} else if ( _tag == 'Signal_3_End' ){						//Signal_BB_End'
				writeLog( '----------------BIG_End----------------' );
				activeBonus = false;
				activeBB = false;
				game.count = 0;
				$('#bb_count').bonusAnime(false);
				$('#bonus_count').bonusAnime(false);
			} else if ( _tag == 'Signal_2_End' ){						//Signal_RB_End'
				writeLog( '----------------REG_End----------------' );
				if ( !activeBB ){
					activeBonus = false;
					game.count = 0;
				}
				$('#rb_count').bonusAnime(false);
				$('#bonus_count').bonusAnime(false);
			} else if ( _tag == 'Signal_5' ){							//Signal_DrumStop
				
			} else if ( _tag == 'bsy' ){								//1gameの終了
				endOneGame = true;
			} else if ( _tag == 'bst' ){								//auto開始(echo)
			//ここからは操作関連
			} else if ( _tag == 'bss' ){								//スタート
			} else if ( _tag == 'Trr' ){								//操作開始
				setRealStop('#sendBtns1', true);
				setRealStop('#sendBtns2', true);
				setRealStop('#sendBtns3', true);
				reelMoveFlg = true;
				singlePushMode = false;
				btnStatus = {'sendBtns1': false, 'sendBtns2': false, 'sendBtns3': false };
				//MaxStart解除
				maxstartFlg = false;
			} else if ( _tag == 'Tfs' ){								//スタートエラー
				//MaxStart解除
				maxstartFlg = false;
			} else if ( _tag == 'Tr1' ){								//左
				setRealStop('#sendBtns1', false);
				singlePushMode = false;
			} else if ( _tag == 'Tr2' ){								//中
				setRealStop('#sendBtns2', false);
				singlePushMode = false;
			} else if ( _tag == 'Tr3' ){								//右
				setRealStop('#sendBtns3', false);
				singlePushMode = false;
			} else if ( _tag == 'Ts1' ){								//左
				setRealStop('#sendBtns1', true);
				singlePushMode = false;
			} else if ( _tag == 'Ts2' ){								//中
				setRealStop('#sendBtns2', true);
				singlePushMode = false;
			} else if ( _tag == 'Ts3' ){								//右
				setRealStop('#sendBtns3', true);
				singlePushMode = false;
			} else if ( _tag == 'Trs' ){								//全停止
				// 2020-06-05 meoshiの判定を追加
				//if ( !autoMode ){
				if ( !autoMode && !meoshiFlg ){
 					//bouns選択可能に
					setBonusMode(true);
				}
				/*
				// 2020/06/05 直後に有効にして送信すると止まるので遅延させる
				var meoshiDelayTime = 100
				if ( meoshiFlg ) meoshiDelayTime = 700;
				setTimeout(function(){
					meoshiFlg = false;
					reelMoveFlg = false;
					//bonus選択をリセット
					resetBonusSelect();
				},meoshiDelayTime);
				//reelMoveFlg = false;
				*/
				reelMoveFlg = false;
			// 2020-06-29 04コードを新設し、04が発生するまではmeoshiFlgをリセットしない
			} else if ( _tag == 'T04' ){								//bonus全停止
				setTimeout(function(){
					meoshiFlg = false;
					resetBonusSelect();
					//bouns選択可能に
					setBonusMode(true);
				},500);
			} else if ( _tag == 'Tnc' ){								//クレジットなし警告
				errorAlert( errorMessages['U5051'] );
				//MaxStart解除
				maxstartFlg = false;
			//新auto停止
			} else if ( _tag == 'Tae' ){								//右
				console.log( game.credit, targetUsePoint );
				// convPlaypointが未定義の場合はgame.conv_pointを使用
				var taeConvPoint = (typeof convPlaypoint !== 'undefined' && convPlaypoint > 0)
					? convPlaypoint
					: (game.conv_point || 100);
				if ( game.credit == 0 && targetUsePoint - usePoint < taeConvPoint ){
					writeLog( "---------no credit!!" );
					autoPlay_Off();
					errorAlert( errorMessages['U5051'] );
					return;
				}
				if (targetUsePoint > 0 ){
					// 2020-04-09 Tcm受信の時には再開しないようにしないといけない
					if ( !changeManualMode ){
						autoPlay(true);
						return;
					}
				}
				autoPlay_Off();
				//2020-04-09 Tcmフラグをリセット
				changeManualMode = false;
			//2020-04-09 autoplayが中断した場合などに自動的にマニュアルモードに切り替える
			} else if ( _tag == 'Tcm' ){
				changeManualMode = true;
				// autoplay 画面 OFF
				$('input[name="ctrl-autoplay"][value="0"]:radio').prop('checked', true);
				$('input[name="ctrl-autoplay"][value="1"]:radio').parent().removeClass('active');
				$('input[name="ctrl-autoplay"][value="0"]:radio').parent().addClass('active');
				// パネル切替許可
				$('input[name="ctrl-panel"]:radio').prop('disabled', false);
				$('#ctrl-panel-play').removeClass('disabled');
				$('#ctrl-panel-menu').removeClass('disabled');
				$('input[name="ctrl-panel"]').val(['1']);
				$('#ctrl-panel-play').addClass('active');
				// パネル切替に従って表示
				changePanel($('input[name="ctrl-panel"]:checked').val());

				$('#play-manual').addClass('d-flex');
				$('#play-manual').show();
				$('#play-auto').removeClass('d-flex');
				$('#play-auto').hide();
			//ここからデータ関連
			//制限日時
			} else if ( _tag.substr(0,1) == '-' ){
				var addsec =  parseInt(_tag.substr(1));
				countDownLimitDate = new Date();
				countDownLimitDate.setSeconds(countDownLimitDate.getSeconds() + addsec);
				console.log( 'setTimer:', countDownLimitDate );
				coundDownTimer();
			//ボーナスフラグ
			} else if ( _tag == 'Aabb' ){
				activeBB = true;
				$('#bb_count').bonusAnime(true);
				$('#bonus_count').bonusAnime(true);
			} else if ( _tag == 'Aab' ){
				activeBonus = true;
				if ( !activeBB ){
					$('#rb_count').bonusAnime(true);
					$('#bonus_count').bonusAnime(true);
				}
				$('#count').text(bonusCountMark);
			//クレジット
			} else if ( _tag == 'Acre' ){
				showPhase('init machine');
				game.credit = parseInt(_msg)
				$('#credit').text(game.credit);
				$('#animeNumber').animetionNumber( 0, game.credit );
			//プレイポイント
			} else if ( _tag == 'Apt' ){
				var span = game.playpoint;
				game.playpoint = parseInt(_msg);
				
				console.log( 'ccc:'+cccCount);
				//RDY後は処理する
				if ( readyFlg ){
					/*
					if ( $('#autoplay_credit').hasClass('autoplay-on') ){
						//オート継続
						span = span - game.playpoint;
						if ( span > 0 ){
							usePoint += span;
							console.log( 'auto use point:' + usePoint );
							//使ったポイントを減算してテキスト表示
							if ( targetUsePoint > 0 ){
								$('#maxpoint').val( targetUsePoint - usePoint );
							}
						}
					}
					*/
					//ボタンで直接購入
					//2020-06-16 ccc変換があれば
					if ( cccCount > 0 ){
						console.log( 'ccc--');
						cccCount--;
					} else {
						console.log( 'auto use');
						span = span - game.playpoint;
						if ( span > 0 ){
							usePoint += span;
							console.log( 'auto use point:' + usePoint );
							//使ったポイントを減算してテキスト表示
							if ( targetUsePoint > 0 ){
								$('#maxpoint').val( targetUsePoint - usePoint );
							}
						}
					}
				}
				$('#playpoint').text(numberFormat(game.playpoint));
			//2020-07-17 追加 Coin(drawpoint)
			} else if ( _tag == 'Adp' ){
				game.drawpoint = parseInt(_msg)
				$('.coin').each(function(){
					$(this).text(numberFormat(game.drawpoint));
				});
			//総ゲーム数
			} else if ( _tag == 'Atc' ){
				game.total_count = parseInt(_msg)
				$('#total_count').text(game.total_count);
			//ボーナス間ゲーム数
			} else if ( _tag == 'Act' ){
				game.count = parseInt(_msg)
				if ( activeBonus ){
					$('#count').text(bonusCountMark);
				} else {
					$('#count').text(game.count);
				}
			//RB数
			} else if ( _tag == 'Arb' ){
				game.rb_count = parseInt(_msg)
				$('#rb_count').text(game.rb_count);
			//BB数
			} else if ( _tag == 'Abb' ){
				game.bb_count = parseInt(_msg)
				$('#bb_count').text(game.bb_count);
			//変換ポイント
			} else if ( _tag == 'Acp' ){
				game.conv_point = parseInt(_msg)
			//変換クレジット
			} else if ( _tag == 'Acc' ){
				game.conv_credit = parseInt(_msg)
			//変換クレジット
			} else if ( _tag == 'Amc' ){
				game.min_credit = parseInt(_msg)
			//離席時間設定
			} else if ( _tag == 'Alv' ){
				leaveTime = parseInt(_msg);
			//2020-06-10 追加 MAX+STARTディレイ時間
			} else if ( _tag == 'Amp' ){
				maxPlusStartDelay = parseInt(_msg);
			//ゲーム準備完了
			} else if ( _tag == 'RDY' ){
				readyFlg = true;
				setConvText();
				$('#machine_no').text(machineno);
				$('nav').show();
				$('#loading').hide();
				$('#loading_cancel').hide();
				$('#bonus_count').text(game.bb_count+game.rb_count);
				$('#menu_select').removeClass('disabled');
				$('#menu2_select').removeClass('disabled');
//				//言語設定の送信
//				_sconnect.send(_sendStr( 'Lng', languageMode ));

				// 韓国統合用：クライアント側のplaypointをカメラに同期
				// play_embedで事前に設定されたポイントがある場合、カメラに送信
				if (game.playpoint > 0 && _sconnect && _sconnect.open) {
					console.log('💰 [Korea] Syncing playpoint to camera:', game.playpoint);
					_sconnect.send(_sendStr('Spt', game.playpoint));
					koreaMode = true;  // 韓国モードを有効化
					console.log('💰 [Korea] Korea mode enabled for AUTO');
				}
				
			//終了予告
			} else if ( _tag == 'Dnt' ){
				$('#time-modal').modal();
			
			//管理者メッセージ表示
			} else if ( _tag == 'Dmg' ){
				showMessage( _msg );
			
			//決済画面に移動
			} else if ( _tag == 'cpd' ){
				requestSettle(_msg)
			//抽選ポイント決済の場合
			} else if ( _tag == 'cpp' ){
				requestSettle(_msg)
			//決済準備エラー
			} else if ( _tag == 'cpe' ){
				errorAlert( errorMessages['U5060'] );
			//コンビニ決済の為の告知
			} else if ( _tag == 'cps' ){
				//errorAlert( errorMessages['U5061'] );
			//決済エラー
			} else if ( _tag == 'cpf' ){
				errorAlert( errorMessages['U5062'] );
			//プレイポイント
			} else if ( _tag == 'Cpt' ){
				var span = game.playpoint;
				game.playpoint = parseInt(_msg);
				$('#playpoint').text(numberFormat(game.playpoint));
				errorAlert( errorMessages['U5063'], errorMessages['U5064'] );
			//クレジット変換ステータス
			} else if ( _tag == 'Cst' ){
				game.ccc_status = _msg;
				if( game.ccc_status == "ok" ) return;
				
				cccCount--;
				console.log( 'ccc--');
				
				if( game.ccc_status == "fail" ){
					dataConnection.close();
					//クレジット変換エラー
					errorAlert( errorMessages['U5059'] );
				} else if ( game.ccc_status == "ng" ) {
					errorAlert( errorMessages['U5054'] );
				}
			//強制精算
			} else if ( _tag == 'lcc' ){
				game.credit -= layoutOption['limitcredit'];
				$('#credit').text(game.credit);
				$('#animeNumber').animetionNumber( 0, game.credit );
				//強制精算通知を出す処理はここに記述する。
				errorAlert( errorMessages['U5067'].replace('%credit%', layoutOption['limitcredit']), errorMessages['U5066'] );
				
			//精算（プレイポイント）
			} else if ( _tag == 'Ppp' ){
				$('#pay_play_point').text( numberFormat(parseInt(_msg)) );
			//精算（クレジット）
			} else if ( _tag == 'Pcr' ){
				$('#pay_credit').text( numberFormat(parseInt(_msg)) );
			//精算（抽選ポイント）
			} else if ( _tag == 'Pdr' ){
				$('#pay_draw_point').text( numberFormat(parseInt(_msg)) );
			//精算（抽選ポイント：強制精算分）
			} else if ( _tag == 'Pda' ){
				$('#pay_autodraw_point').text( numberFormat(parseInt(_msg)) );
				if ( layoutOption['limitcredit'] ){
					if ( layoutOption['limitcredit'] > 0 ){
						$('#show_autodraw').show();
					} else {
						$('#show_autodraw').remove();
					}
				} else {
					$('#show_autodraw').remove();
				}
			//精算（トータル抽選ポイント）
			} else if ( _tag == 'Ptd' ){
				$('#pay_total_draw_point').text( numberFormat(parseInt(_msg)) );
			//ゲーム終了
			} else if ( _tag == 'EXT' ){
				//2020-06-03 終了フラグをセット
				endPlayFlg = true

				// 韓国モード: SDK通知を送信
				if ( koreaMode ) {
					console.log('💰 [Korea] Settlement complete - notifying parent');
					notifySDK('settlement', {
						playPoint: parseInt($('#pay_play_point').text().replace(/,/g, '') || '0'),
						credit: parseInt($('#pay_credit').text().replace(/,/g, '') || '0'),
						drawPoint: parseInt($('#pay_draw_point').text().replace(/,/g, '') || '0'),
						totalDrawPoint: parseInt($('#pay_total_draw_point').text().replace(/,/g, '') || '0'),
						result: 'completed'
					});
				}

				$('#loading_connect').hide();
				$('#loadinglost').hide();
				$('#loading_pay').show();
				$('#loading')
					//.css('height', $('body').css('height') )
					.css('height', window.innerHeight )
					.show();
				dataConnection.close();
				if (! peer.destroyed) {
					peer.destroy();
				}
				//精算結果モーダル表示
				setTimeout(function(){
					// 韓国モード: モーダル表示せずに通知のみ
					if ( koreaMode ) {
						console.log('💰 [Korea] Settlement modal skipped - parent will handle');
						clearInterval( aliveInterval );
						clearInterval( pingInterval );
						return;
					}
					$('#end-modal')
						.css('z-index', 6000)
						.modal({
							backdrop: 'static',
							keyboard: false
						})
					;
					//インターバルの停止
					clearInterval( aliveInterval );
					clearInterval( pingInterval );
				},2000);
			//精算処理失敗
			} else if ( _tag == 'ERP' ){
				//精算失敗
				errorAlert( errorMessages['U5058'] );
			//セミオート設定 OFF
			} else if ( _tag == 'Xsaf' ){
				semiAutoPlayFlg = false;
				$('.semiauto_on').hide();
				$('.semiauto_off').show();
			//セミオート設定 ON
			} else if ( _tag == 'Xsat' ){
				semiAutoPlayFlg = true;
				$('.semiauto_on').show();
				$('.semiauto_off').hide();
			} else if ( _tag.substr(0,4) == 'CNT_' ){
				var cbstr = _tag.split('_');
				var cb = parseInt(cbstr[1]);
				$('#cb').text(''+cb);
				if ( cb > 1 ){
					$('.continuous-bonus').show();
				} else {
					$('.continuous-bonus').hide();
				}
			} else if ( _tag == 'Her' ){
				$('#end-modal .modal-title').text( errorMessages['U5069'] );
			} else if ( _tag == 'Lng' ){
				//言語設定のエコー
				showPhase('Langage');
				recvLang = true;
			}
		});
		
	});

	peer.on('call', function(call) {
		// カメラ側からStreamが送られてきた場合に呼ばれます
		// 閲覧側のカメラは利用しないので、何も指定しないでanswerをします
		showPhase('call');
		notifyParent('call_received', { callId: call.peer });

		call.answer();
		showPhase('answer');
		notifyParent('call_answered', {});

		// callエラーハンドリング
		call.on('error', function(err) {
			console.error('❌ Call Error:', err);
			notifyParent('call_error', { error: err.toString() });
		});

		call.on('close', function() {
			console.warn('📞 Call closed');
			notifyParent('call_close', {});
		});

		// カメラからのStreamをvideoタグに追加します
		call.on('stream', function(stream) {
			showPhase('stream');
			notifyParent('stream_received', { streamId: stream.id, tracks: stream.getTracks().length });
			// URL.createObjectURL(stream) は最新ブラウザでは非推奨・削除済み
			// MediaStreamは直接srcObjectに設定するのが正しい方法
			_savestream = stream; // デバッグ用にstreamオブジェクトを保存
			console.log( stream.id )
			try {
				var videoElement = document.getElementById('video');
				videoElement.srcObject = stream;

				// モバイルブラウザ対応: 明示的にplay()を呼ぶ
				videoElement.play().catch(function(error) {
					console.log('Video autoplay failed:', error);
					// ユーザーインタラクション後に再試行
					document.addEventListener('click', function playOnClick() {
						videoElement.play().catch(function(e) {
							console.log('Video play on click failed:', e);
						});
						document.removeEventListener('click', playOnClick);
					}, { once: true });
				});
			} catch (error) {
				// srcObject が利用できない場合のフォールバック
				// 注意: URL.createObjectURL(MediaStream) は最新ブラウザで非推奨
				console.error('Video srcObject failed:', error);
			}

			//
			var audio = document.querySelector('audio');
			try {
				var audioElement = document.getElementById('audio');
				if (audioElement) {
					audioElement.srcObject = stream;

					// モバイルブラウザ対応: 明示的にplay()を呼ぶ
					audioElement.play().catch(function(error) {
						console.log('Audio autoplay failed:', error);
						// ユーザーインタラクション後に再試行
						document.addEventListener('click', function playAudioOnClick() {
							audioElement.play().catch(function(e) {
								console.log('Audio play on click failed:', e);
							});
							document.removeEventListener('click', playAudioOnClick);
						}, { once: true });
					});
				}
			} catch (error) {
				// srcObject が利用できない場合のフォールバック
				console.error('Audio srcObject failed:', error);
			}

			console.log( 'browserVersion:'+browserVersion );

			setVideoWidth();

			showPhase('video');

			
			//即時で切り替えるとvideoの表示がスムースにいかないのでDelayさせる
			setTimeout(function(){
				showPhase('audio');

				$('#video').show();
				$('.img-fluid').hide();

				//2020-06-15 セミオートの表示枠を切替
				if ( semiAutoPlayFlg ){
					$('.semiauto_on').show();
					$('.semiauto_off').hide();
				} else {
					$('.semiauto_on').hide();
					$('.semiauto_off').show();
				}
				setVideoWidth();

				//PCまたは韓国モード(play_embed)では自動で音声を有効化
				if ( getDevice() == "other" || koreaMode ){
					setAudio();
				}

				// 韓国モード: 最初のクリックで音声有効化（ブラウザポリシー対策）
				if ( koreaMode && !$('#audiostart').hasClass('playing') ) {
					document.addEventListener('click', function enableAudioOnFirstClick() {
						if (!$('#audiostart').hasClass('playing')) {
							setAudio();
							console.log('🔊 [Korea] Audio enabled on first click');
						}
						document.removeEventListener('click', enableAudioOnFirstClick);
					}, { once: true });
				}

				//言語設定の送信
				_sconnect.send(_sendStr( 'Lng', languageMode ));

				retryLang();
			},500);
		});
	});
	
	$('#audiostart,#audiostart_auto').click(function(){
		setAudio();
	});

	$('#changeauto').click(function(){
		//autoパネル切替の場合でもautoplayを止める
		if ( $('#autoplay_credit').hasClass('autoplay-on') ){
			autoPlay_Wait();
			_sconnect.send(_sendStr( 'bae', 'autostop' ));
		}
	});
	// 2020-06-23 デフォルトhtmlでもauto画面を切り替えたらautoが中止される
	$('#changeauto,#changeauto2').change(function(){
		if ( $(this).val() == 0 ){
			//autoパネル切替の場合でもautoplayを止める
			if ( $('#autoplay_credit').hasClass('autoplay-on') ){
				autoPlay_Wait();
				_sconnect.send(_sendStr( 'bae', 'autostop' ));
			}
		}
	});

	$('#autoplay_credit').click(function(){
		//2020-06-10 ROMとの共通化
		//Wait中の場合はボタン操作不可
		if ( $(this).hasClass('autoplay-wait') ){
			return;
		}
		/*
		//Wait中の場合はボタン操作不可
		if ( $(this).attr('waitlabel') == $(this).text() ){
			return;
		}
		*/
	
		if( $(this).hasClass('autoplay-off') ) {
			//リール動作中はautoへの変更不可
			if ( reelMoveFlg == true ) {
				console.log('reel moveing');
				return;
			}
			targetUsePoint = parseInt($('#maxpoint').val());
			if ( !targetUsePoint ) targetUsePoint = 0;

			// 韓国モード: targetUsePointが0の場合、全playpointを使用可能に設定
			if ( koreaMode && targetUsePoint == 0 && game.playpoint > 0 ) {
				targetUsePoint = game.playpoint;
				console.log('💰 [Korea] AUTO: targetUsePoint auto-set to', targetUsePoint);
			}

			//指定なし、クレジットなし
			console.log( targetUsePoint,game.credit );
			if ( targetUsePoint == 0 && game.credit <= 0 ){
				errorAlert( errorMessages['U5051'] );
				return;
			}
			if ( targetUsePoint > game.playpoint ){
				errorAlert( errorMessages['U5053'] );
				return;
			} else {
				$(this)
					.removeClass('autoplay-off')
					.addClass('autoplay-on');

				//#bonus_countが存在しない時はラベルを変更
				if(!($('#bonus_count').length)){
					$(this).text( $(this).attr('stoplabel') );
				}
				$('#maxpoint').attr('readonly', true );
				usePoint = 0;

				// AUTOモード開始（韓国モードでも同じ動作）
				autoPlay(true);
				//目押し操作不可に変更
				setBonusMode(false);
			}
		} else {
			autoPlay_Wait();
			autoModePrep = false;
			_sconnect.send(_sendStr( 'bae', 'autostop' ));
		}
	});

	function retryLang(){
		setTimeout(function(){
			if (recvLang){
				return;
			}
			
			showPhase('retry::lang');
			//言語設定の送信
			_sconnect.send(_sendStr( 'Lng', languageMode ));
			
			//再度呼び出す
			retryLang();
		},2000);
	}


	function pushAutoPlay(){
		//2020-06-10 ROMとの共通化
		//Wait中の場合はボタン操作不可
		if ( $('#autoplay_credit').hasClass('autoplay-wait') ){
			return false;
		}
		/*
		//Wait中の場合はボタン操作不可
		if ( $(this).attr('waitlabel') == $(this).text() ){
			return;
		}
		*/
	
		if( $('#autoplay_credit').hasClass('autoplay-off') ) {
			//リール動作中はautoへの変更不可
			/*
			if ( reelMoveFlg == true ) {
				console.log('reel moveing');
				return;
			}
			*/
			targetUsePoint = parseInt($('#maxpoint').val());
			if ( !targetUsePoint ) targetUsePoint = 0;

			// 韓国モード: targetUsePointが0の場合、全playpointを使用可能に設定
			if ( koreaMode && targetUsePoint == 0 && game.playpoint > 0 ) {
				targetUsePoint = game.playpoint;
				console.log('💰 [Korea] AUTO (push): targetUsePoint auto-set to', targetUsePoint);
			}

			//指定なし、クレジットなし
			console.log( targetUsePoint,game.credit );
			if ( targetUsePoint == 0 && game.credit <= 0 ){
				errorAlert( errorMessages['U5051'] );
				//bonus選択を初期化
				resetBonusSelect();
				return false;
			}
			if ( targetUsePoint > game.playpoint ){
				errorAlert( errorMessages['U5053'] );
				//bonus選択を初期化
				resetBonusSelect();
				return false;
			} else {
				$('#autoplay_credit')
					.removeClass('autoplay-off')
					.addClass('autoplay-on');

				//#bonus_countが存在しない時はラベルを変更
				if(!($('#bonus_count').length)){
					$(this).text( $(this).attr('stoplabel') );
				}
				$('#maxpoint').attr('readonly', true );
				usePoint = 0;

				// AUTOモード開始（韓国モードでも同じ動作）
				autoPlay(true);
				//目押し操作不可に変更
				setBonusMode(false);
			}
		} else {
			autoPlay_Wait();
			_sconnect.send(_sendStr( 'bae', 'autostop' ));
		}
		return true;
	}

	//bonusの有効化無効化
	function setBonusMode(mode){
		if ( mode ){
			//目押し操作可能に変更
			$('#menu_select').removeClass('disabled');
			$('#menu2_select').removeClass('disabled');
		} else {
			//目押し操作不可に変更
			$('#menu_select').addClass('disabled');
			$('#menu2_select').addClass('disabled');
		}
	}

	//bonusを未選択に戻す
	function resetBonusSelect(){
		if ( meoshiFlg ){
			_sconnect.send(_sendStr( 'bb0', 'non credit' ));
		}
		meoshiFlg = false;
		$('#form-meoshi').val('bb0');							//bonusを選択に戻す
		$('#form-meoshi2').val('bb0');							//bonusを選択に戻す
		$('#selectedimg').attr('src', $('#bb0_img').attr('src'));
		$('#selectedimg2').attr('src', $('#bb0_img2').attr('src'));
	}


	function key2Tele(keyCode){
		switch(keyCode){
			case 90 :
	 			if ( game.credit <= 0 ){
	 				errorAlert( errorMessages['U5051'] );
	 				return ('');
	 			}
				id = 'sb';
				break;
			case 88 :
				id = 'ss';
				break;
			case 67 :
				id = 's1';
				break;
			case 86 :
				id = 's2';
				break;
			case 66 :
				id = 's3';
				break;
			/* 2020-06-19 iPhoneで誤動作するので削除
			case 49 :
				id = 'soc';
				break;
			case 50 :
				id = 'sos';
				break;
			case 51 :
				id = 'soe';
				break;
			case 87 :
				id = 'sou';
				break;
			case 65 :
				id = 'sol';
				break;
			case 68 :
				id = 'sor';
				break;
			case 83 :
				id = 'sod';
				break;
			*/
			default:
				return('');

		}
		return( id );
	}

	//オートプレイの継続or停止チェック
	function checkAutoPlay(){
		//50msまってSignal_OUTが発生しなくなったら終了とみなす
		setTimeout(function(){
			if ( signalOUT_End ) return;
			if ( game.credit == saveCredit ){
				if ( $('#autoplay_credit').hasClass('autoplay-on') && !autoStopSignal ){
					signalOUT_End = true;
					setTimeout(function(){
						//autoPlay(true);
					},1000);
				} else {
					autoPlay_Off();
				}
			}
		},50);
	}


	//オートプレイ停止(前段階）
	function autoPlay_Wait(){
		$('#autoplay_credit')
			.removeClass('autoplay-on')
			.addClass('autoplay-off')
			.addClass('autoplay-wait');
		//#bonus_countが存在しない時はラベルを変更
		if(!($('#bonus_count').length)){
			$('#autoplay_credit').text( $('#autoplay_credit').attr('waitlabel') );
		}
		autoMode = false;
		autoModePrep = false;
	}


	//オートプレイ停止
	function autoPlay_Off(){
		$('#autoplay_credit')
			.removeClass('autoplay-on')
			.removeClass('autoplay-wait')
			.addClass('autoplay-off');
		//#bonus_countが存在しない時はラベルを変更
		if(!($('#bonus_count').length)){
			$('#autoplay_credit').text( $('#autoplay_credit').attr('startlabel') );
		}
		$('#maxpoint').attr('readonly', false );
		autoMode = false;
		autoModePrep = false;
		//目押し操作可能に変更
		$('#menu_select').removeClass('disabled');
		$('#menu2_select').removeClass('disabled');
	}

	//オートプレイ
	function autoPlay(cremode){
		var endInt = null

		autoBet(cremode)
		.then(function(ret){
			if ( !ret || game.credit <= 0 ){
				autoPlay_Off();
				_sconnect.send(_sendStr( 'bae', 'autostart' ));
				//bonus設定を戻す
				resetBonusSelect()
				return;
			}

			if ( $('#autoplay_credit').hasClass('autoplay-on') && !autoStopSignal ){
				if ( autoFirstEventFlg ){
					//autoplayの初回のみ
					dataConnection.send(_sendStr( 'bsb', "auto"));				//Signal5が保留になっているかもしれないので先に消化
					//自動でbsyが発行されるのでディレイしてbstを送信する
					setTimeout(function(){
						_sconnect.send(_sendStr( 'bas', 'autostart' ));
						autoMode = true;
					}, 800 );
					autoFirstEventFlg = false;
				} else {
					//通常
					_sconnect.send(_sendStr( 'bas', 'autostart' ));
					autoMode = true;
				}
			} else {
				autoPlay_Off();
			}
		},function(){
			//credit変換できなかった時
			autoPlay_Off();
		});
	}
	
	//自動クレジット追加
	function autoBet(cremode, btnidx){
		var intid;
		return new Promise(function(resolve, reject) {
			if ( !cremode ) {
				resolve(true)
				return;
			}
			//目標ポイント数を使い切った
			if ( game.credit <= 0 ){
				// convPlaypointが未定義の場合はgame.conv_pointを使用
				var convPointValue = (typeof convPlaypoint !== 'undefined' && convPlaypoint > 0)
					? convPlaypoint
					: (game.conv_point || 100);
				console.log( 'autoBet check:', targetUsePoint, usePoint, convPointValue, 'koreaMode:', koreaMode );
				if ( targetUsePoint - usePoint < convPointValue ){
					console.log( 'autoBet: not enough points for conversion' );
					resolve(false);
					return;
				}
				writeLog( '[autoBet] exec' );
				//クレジット変換を自動実行
				game.ccc_status = "";
				var waitCount = 0;
				var maxWait = 100; // 5秒タイムアウト (50ms * 100)
				dataConnection.send(_sendStr( 'ccc', ''));
				intid = setInterval(function(){
					waitCount++;
					if ( game.ccc_status == "ok" && game.credit > 0 ){
						// ccc成功 かつ クレジットが更新された
						writeLog( '[autoBet] ok, credit=' + game.credit );
						clearInterval( intid );
						resolve(true);
					} else if ( game.ccc_status == "ok" && waitCount < maxWait ){
						// ccc成功したがクレジット更新待ち
						console.log( '[autoBet] waiting for credit update...', game.credit );
					} else if ( game.ccc_status == "ng" ){
						console.log( '[autoBet] ng' );
						writeLog( intid );
						clearInterval( intid );
						reject();
					} else if ( waitCount >= maxWait ){
						// タイムアウト - ccc_statusがokならクレジット更新を待たずに続行
						console.log( '[autoBet] timeout, ccc_status=' + game.ccc_status + ', credit=' + game.credit );
						clearInterval( intid );
						if ( game.ccc_status == "ok" ) {
							resolve(true);
						} else {
							reject();
						}
					}
				},50);
			} else {
				resolve(true);
			}
		});
	}

	function keyCtrl( id ){
		//スタートアクション中は送信禁止
		if ( maxstartFlg ) {
			console.log( 'maxstartFlg On' );
			return false;
		}

		var addChar = 'd';
		if ( clickMode ){
			addChar = '';
		}

		if ( id == 'sendBtns1' || id == 'sendBtns2' || id == 'sendBtns3' ) {
			if ( !$('#'+id).hasClass('reel-stop-topush') ){
				console.log( 'not ready' );
				return false;
			}

			$('#menu_select').addClass('disabled');
			$('#menu2_select').addClass('disabled');
			/*
			//2点押しなどで同時押しになってる場合
			if ( lastid.length > 0 ){
				stackButton.push(id);
				return false;
			}
			*/
			if ( singlePushMode || autoMode ){
				console.log( '## bad push!!');
				return false;
			}
			if ( meoshiFlg ){
				singlePushMode = true;
			}
			if ( singlePushMode && btnStatus[id] ){
				console.log( '## bonus bad push!!');
				return false;
			}
			if ( $('#'+id).hasClass('reel-stop-topush') ){
				btnStatus[id] = true;
				dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1]+addChar, "down"));

				// タイムアウト処理: 5秒後に強制リセット（サーバー応答遅延対策）
				setTimeout(function() {
					if (btnStatus[id]) {
						console.log('Button timeout reset:', id);
						btnStatus[id] = false;
					}
				}, 5000);
				//lastid.push(id);
			} else {
				console.log( 'NG push' );
				return false;
			}
		} else if( id == 'sendBtnsb' || id == 'sendBtnss' ) {
			if ( reelMoveFlg ){
				console.log( '## reel active push!!');
				return false;
			}
			if ( singlePushMode || autoMode ){
				console.log( '## bad push!!');
				return false;
			}
			if ( id == 'sendBtnss' ){
				dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1], "down"));
				maxstartFlg = true;
				return false;
			} else {
				dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1]+addChar, "down"));
			}
			//lastid.push(id);
		} else {
			if ( singlePushMode || meoshiFlg ){
				console.log( '## bad push!!');
				return false;
			}
			//dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1]+'d', "down"));
			dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1], "down"));
			//lastid.push(id);
			return false;
		}
		return true;
	}

	//2020-06-12 Bonus用のセミオート
	function semiAutoPlay(retry=false){
		if ( semiAutoPlayFlg ){
			if ( !pushAutoPlay() ) return;
			if ( !retry ){
				setTimeout(function(){
					//スマホ用にパネルを閉じる
					$('.btn-slidemenu-set').toggleClass('open');
					$('.slidemenu-bg').fadeToggle();
					$('.slide-setting').toggleClass('open');
				},200);
			}
			setTimeout(function(){
				if ( reelMoveFlg ){
					pushAutoPlay();
				} else {
					//retry
					semiAutoPlay(true);
				}
			},1500);
		}
	}

	//リール停止ボタンの表示変更
	function setRealStop( selecter, ready=false ){
		if ( ready ){
			$(selecter)
				.removeClass('reel-stop')
				.addClass('reel-stop-topush')
			;
		} else {
			$(selecter)
				.removeClass('reel-stop-topush')
				.addClass('reel-stop')
			;
		}
	}
	
	function showPhase( no ){
		$('#phase').html( '<br>['+no+']' );
	}
	
	//2020-12-16 日付とユーザー名を画面に表示
	function showDateandName(){
		var nowTime = new Date();
		var year  = ('0'+nowTime.getFullYear()).substr(-4);
		var month = ('0'+(nowTime.getMonth()+1)).substr(-2);
		var day = ('0'+nowTime.getDate()).substr(-2);
		var hh = ('0'+nowTime.getHours()).substr(-2);
		var mm = ('0'+nowTime.getMinutes()).substr(-2);
		$('#nowclock').text(year+'/'+month+'/'+day+' '+hh+':'+mm);
		if ( typeof username !== 'undefined' ){
			$('#username').text(username);
		}
	}

