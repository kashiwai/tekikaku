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
 * @since     2020/11/19 ver 1.0          村上俊行 初版作成 slot ver 1.4.5-s1-c1 反映
 *            2021/06/01 ver 1.1          村上俊行          user_credit表示追加など
 *            2023/05/24 ver 1.0.7-s6-c1  村上俊行 無操作強制退出機能の削除
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
			'day_count'   : 0,
			'bb_count'    : 0,
			'rb_count'    : 0,
			'count'       : 0,
			'min_credit'  : 2,
			'ccc_status'  : '',
			'max_continuous' :  0,
			'max_dedama'  : 0,
			'max_bb'      : 0,
		};
	} else {
		// 既存のgameオブジェクトに不足しているプロパティを追加
		game.drawpoint = game.drawpoint || 0;
		game.total_count = game.total_count || 0;
		game.day_count = game.day_count || 0;
		game.bb_count = game.bb_count || 0;
		game.rb_count = game.rb_count || 0;
		game.count = game.count || 0;
		game.min_credit = game.min_credit || 2;
		game.ccc_status = game.ccc_status || '';
		game.max_continuous = game.max_continuous || 0;
		game.max_dedama = game.max_dedama || 0;
		game.max_bb = game.max_bb || 0;
		console.log('📝 Preserved existing game object - playpoint:', game.playpoint, 'credit:', game.credit);
	}
	//2021-06-01 credit加算警告の猶予期間
	var alertOffTime = 86400;
	var alert2play = false;
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
	var disableAuto = false;					//auto連打禁止用フラグ 2021-05-15
	var activeGame = false;						//回転中
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
	var old_heso = 0;
	var old_denchu = 0;

	var autoFirstEventFlg = false;
	//peer setting
	var peersetting = {
		host: sigHost,
		//2020-09-18 Port変更可能に修正
		port: sigPort,
		secure: true,  // HTTPS/WSS使用（ngrok対応）
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
		window.location.href = '/gameafter.php' + '?' + machineno + '-' + authID;
	});

	//目押し操作不可に変更
	setBonusMode(false);

	showDateandName();
		
	var peer = new Peer( peersetting );

	peer.on('open', function(){
		//id
		$('#my-id').text(peer.id);

		showPhase('open');

		//データチャンネル
		dataConnection = peer.connect(cameraid,{
			'metadata': memberno+':'+authID
		});
		showPhase('connect');

		dataConnection.maxRetransmits = 1;

		dataConnection.on('close', function(){
			console.log( 'connect lost' );
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
			//if ( !clickMode ){
				if ( lastid.length > 0 ){
					var id = lastid.shift();
					setTimeout(function(){
						dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1]+'u', "up"));
					},300);
				}
			//}
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

		// credit変換ボタン押下
		$('#convcr-button').bind('click', function(){
			alert2play = false;
			var dt = new Date();
			dt = dt.getTime()
			var limit = localStorage.getItem('net8checkdate', '0');
			// rateが 0なら確認モーダルを表示しない
			if ( pachi_rate == 0 ){
				$('#convcr-modal').modal();
				return;
			}
			console.log( limit, dt );
			if (parseInt(limit) < dt || limit == null){
				$('#conv-alert-check').removeAttr('checked').prop('checked', false).change();
				$('#conv-alert-modal').modal();
			} else {
				$('#convcr-modal').modal();
			}
		});
		
		// credit確認時のokボタン押下
		$('#conv-alert-ok').on('click', function(){
			if ($('#conv-alert-check').prop('checked')) {
				var dt = new Date();
				//営業終了時間に設定
				var tm = closeTime.split(':');
				var closeH = parseInt(tm[0]);
				var closeM = parseInt(tm[1]);
				if ( dt.getHours() >= closeH ){
					dt.setDate(dt.getDate() + 1);
				}
				dt.setHours(closeH);
				dt.setMinutes(closeM);
				dt.setSeconds(0);
				console.log(dt);
				dt = dt.getTime();
				localStorage.setItem('net8checkdate', dt)
			} else {
				localStorage.removeItem('net8checkdate');
			}
			if (alert2play == false){
				$('#convcr-modal').modal();
			} else {
				pushAutoPlay()
			}
			alert2play == false
		});
		// credit確認時のcancelボタン押下
		$('#conv-alert-cancel').on('click', function(){
			localStorage.removeItem('net8checkdate');
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

		//クレジット変換ボタン処理
		$('#convCredit').bind(_click, function(){
			console.log( 'click convCredit:'+ cccCount);
			if (cccCount == 0){
				dataConnection.send(_sendStr( 'ccc', ''));
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
			if ( !recvLang ){
				console.log( 'retry:lang');
				retryLang();
				//dataConnection.send(_sendStr( 'Lng', languageMode ));
			}
		});
		
		
		//データチャンネルハンドリング
		dataConnection.on('data', function(data){
			//放置時間計測用
			lastTimestamp = (new Date()).getTime();

			writeLog( 'recieve:'+data+' '+lastTimestamp);
			var _darry = data.trim().split(',');
			var _tag = _darry[0].split(':')[1];
			//電文にメッセージが入ることになったので再構築させる
			var msgAry = _darry[1].split(':');
			msgAry.shift();
			var _msg = msgAry.join(':');

			if( _tag == 'Signal_Game+1' ){
				game.day_count++;
				$('#day_count').text(numberFormat(game.day_count));
				//if ( !activeBonus ){
				if ( !activeBB ){
					//bonusに入っているときはゲーム数を加算しない
					game.total_count++;
					game.count++;
					$('#total_count').text(numberFormat(game.total_count));
					$('#count').text(numberFormat(game.count));
				}
			} else if ( _tag.substr(0,5) == 'HESO_' ){				//へそ
				var crcd = _tag.split('_');
				//稼働中判定用
				var now_heso = parseInt(crcd[1]);
				var now_denchu = parseInt(crcd[2]);
				if ( now_heso > old_heso || now_denchu > old_denchu ){
					//増加判定
				}
				old_heso = now_heso;
				old_denchu = now_denchu;
				var heso   = '●'.repeat(parseInt(crcd[1]));
				var denchu = '▲'.repeat(parseInt(crcd[2]));
				if ( heso + denchu != ''){
					$('.active-game').show();
				}
				$('#heso_mark').text(heso);
				$('#denchu_mark').text(denchu);
			} else if ( _tag.substr(0,4) == 'CRI_' ){				//CreditIn
				var crcd = _tag.split('_');
				var cr = parseInt(crcd[1]);
				var sa = game.credit - cr;
				if ( sa < 0 ) cr -= sa;
				game.credit -= cr;
				inCreditCount += cr;
				$('#animeNumber').animetionNumber( cr * -1 );
				$('#credit').text(game.credit);
				
			} else if ( _tag.substr(0,4) == 'CRO_' ){				//CreditIn
				var crcd = _tag.split('_');
				var cr = parseInt(crcd[1]);
				addCredit += cr;
				game.credit += cr;
				$('#credit').text(game.credit);
				$('#animeNumber').animetionNumber( cr );
				$('#credit').text(game.credit);
			} else if ( _tag.substr(0,4) == 'UCR_' ){				//CreditIn
				var crcd = _tag.split('_');
				var cr = parseInt(crcd[1]);
				$('#user_credit').text(numberFormat(cr));
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

			} else if ( _tag == 'Signal_1' ){							//Signal_OUT
				endOneGame = true;
				addCredit++;
				game.credit++;
				$('#credit').text(game.credit);
				$('#animeNumber').animetionNumber( 1 );
			} else if ( _tag == 'Sac' ){								//Credit払い出し総数通知
				if ( $('#autoplay_credit').hasClass('auto-on') && !autoStopSignal ){
					setTimeout(function(){
						//autoPlay(true);
					},autoPlayRestartTime);
				} else {
					
				}
			} else if ( _tag == 'Signal_3' ){							//Signal_BB_Start
				writeLog( '================BIG_Start================' );
				activeBonus = true;
				activeBB = true;
				game.bb_count++;
				game.count = 0;										//カウントリセット

				$('#bb_count').text(game.bb_count);
				$('#count').text(bonusCountMark);
				$('#bb_count').bonusAnime(true);
				$('#bonus_count').text(game.bb_count+game.rb_count);
				$('#bonus_count').bonusAnime(true);
				
				if ( game.max_bb < game.bb_count ){
					game.max_bb = game.bb_count;
					$('#max_bb').text(game.max_bb);
				}
				
				// 2021-05-06 入賞中にメッセージを変更＆ボタンを押せないようにする
				$('#autoplay_credit').addClass('active_bb');
				$('#autoplay_credit').text( $('#autoplay_credit').attr('bblabel') );
				if ( !$('#autoplay_credit').hasClass('auto-on') ){
					$('#pay-button').attr("disabled", true);
				}
			} else if ( _tag == 'Signal_2' ){							//Signal_RB_Start
				writeLog( '================REG_Start================' );
				activeBonus = true;
				if ( !activeBB ){
					game.rb_count++;
					//game.count = 0;									//カウントリセット
					$('#rb_count').bonusAnime(true);
				}
				$('#rb_count').text(game.rb_count);
				//$('#count').text(bonusCountMark);
				$('#bonus_count').text(game.bb_count+game.rb_count);
				$('#bonus_count').bonusAnime(true);
			} else if ( _tag == 'Signal_3_End' ){						//Signal_BB_End'
				writeLog( '----------------BIG_End----------------' );
				activeBonus = false;
				activeBB = false;
				game.count = 0;
				$('#bb_count').bonusAnime(false);
				$('#bonus_count').bonusAnime(false);

				// 2021-05-06 入賞中にメッセージを変更＆ボタンを押せないようにする
				$('#autoplay_credit').removeClass('active_bb');
				if ( !$('#autoplay_credit').hasClass('auto-on') ){
					autoPlay_Off();
					$('#pay-button').attr("disabled", false);
					_sconnect.send(_sendStr( '@AUTO-BTN-OFF', '' ));
				} else {
					$('#autoplay_credit').text( $('#autoplay_credit').attr('stoplabel') );
					_sconnect.send(_sendStr( '@AUTO-BTN-ON', '' ));
				}
			} else if ( _tag == 'Signal_2_End' ){						//Signal_RB_End'
				writeLog( '----------------REG_End----------------' );
				if ( !activeBB ){
					activeBonus = false;
					//game.count = 0;
				}
				$('#rb_count').bonusAnime(false);
				$('#bonus_count').bonusAnime(false);
			} else if ( _tag == 'Signal_5' ){							//Signal_DrumStop
				
			} else if ( _tag == 'bsy' ){								//1gameの終了
				endOneGame = true;
			} else if ( _tag == 'bst' ){								//auto開始(echo)
			//ここからは操作関連
			} else if ( _tag == 'STG' ){								//Game開始通知
				$('.active-game').show();
				activeGame = true;
			} else if ( _tag == 'EDG' ){								//Game終了通知
				$('.active-game').hide();
				activeGame = false;
			} else if ( _tag == 'Trs' ){								//全停止
				// 2020-06-05 meoshiの判定を追加
				//if ( !autoMode ){
				if ( !autoMode && !meoshiFlg ){
 					//bouns選択可能に
					setBonusMode(true);
				}
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
			//新auto開始
			} else if ( _tag == 'aon' ){
				_sconnect.send(_sendStr( '@A-ON', '' ));
				$('.link-credit').show();
				$('#pay-button').attr("disabled", true);
			} else if ( _tag == 'aof' ){
				_sconnect.send(_sendStr( '@A-OFF', '' ));
				$('.link-credit').hide();
				$('#pay-button').attr("disabled", false);
			//新auto停止
			} else if ( _tag == 'Tae' ){
				console.log( game.credit, targetUsePoint );
				// convPlaypointが未定義の場合はgame.conv_pointを使用（韓国play_embed対応）
				var convPointValue = (typeof convPlaypoint !== 'undefined' && convPlaypoint > 0)
					? convPlaypoint
					: (game.conv_point || 100);
				if ( game.credit == 0 && targetUsePoint - usePoint < convPointValue ){
					writeLog( "---------no credit!!" );
					autoPlay_Off();
					errorAlert( errorMessages['U5051'] );
					$('#pay-button').attr("disabled", false);
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
				// 2021-05-06 入賞中にメッセージを変更＆ボタンを押せないようにする
				$('#autoplay_credit').addClass('active_bb');
				$('#autoplay_credit').text( $('#autoplay_credit').attr('bblabel') );
				if ( !$('#autoplay_credit').hasClass('auto-on') ){
					$('#pay-button').attr("disabled", true);
				}
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
			} else if ( _tag == 'Adc' ){
				game.day_count = parseInt(_msg)
				$('#day_count').text(numberFormat(game.day_count));
			//総ゲーム数
			} else if ( _tag == 'Atc' ){
				game.total_count = parseInt(_msg)
				$('#total_count').text(numberFormat(game.total_count));
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
			//精算（出玉控除）
			} else if ( _tag == 'Pdd' ){
				var deduction_credit = Number(_msg);
				console.log('Pdd:', deduction_credit);
				if (deduction_credit > 0){
					var txt = '(-' + (deduction_credit * 100) + '%) ';
					$('#pay_deduction_credit').text( txt );
				} else {
					$('#pay_deduction_credit').text('');
				}
			//ゲーム終了
			} else if ( _tag == 'EXT' ){
				//2020-06-03 終了フラグをセット
				endPlayFlg = true

				// 韓国モード: 親フレームに精算完了を通知
				if ( koreaMode && window.parent !== window ) {
					console.log('💰 [Korea] Settlement complete - notifying parent');
					try {
						window.parent.postMessage({
							type: 'game:settlement',
							payload: {
								playPoint: parseInt($('#pay_play_point').text().replace(/,/g, '') || '0'),
								credit: parseInt($('#pay_credit').text().replace(/,/g, '') || '0'),
								drawPoint: parseInt($('#pay_draw_point').text().replace(/,/g, '') || '0'),
								totalDrawPoint: parseInt($('#pay_total_draw_point').text().replace(/,/g, '') || '0'),
								result: 'completed'
							}
						}, '*');
					} catch (e) {
						console.error('💰 [Korea] Settlement notify error:', e);
					}
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
			} else if ( _tag.substr(0,4) == 'MDD_' ){
				var cbstr = _tag.split('_');
				var dedama =  parseInt(cbstr[1]);
				if ( game.max_dedama == 0 ){
					game.max_dedama = dedama;
					$('#max_credit').text(numberFormat(game.max_dedama));
				}
				/* 自動更新させる場合
				if (dedama > game.max_dedama){
					game.max_dedama =dedama;
				}
				$('#max_credit').text(numberFormat(game.max_dedama));
				*/
			} else if ( _tag.substr(0,4) == 'MBB_' ){
				var cbstr = _tag.split('_');
				var bb =  parseInt(cbstr[1]);
				if ( bb < game.bb_count ) bb = game.bb_count;
				if ( bb > game.max_bb ){
					game.max_bb = bb;
				}
				$('#max_bb').text(game.max_bb);
				//$('#max_bb').text('set');
			} else if ( _tag.substr(0,5) == 'MCNT_' ){
				var cbstr = _tag.split('_');
				game.max_continuous = parseInt(cbstr[1]);
				$('#max_continuous').text(game.max_continuous);
			} else if ( _tag.substr(0,4) == 'CNT_' ){
				var cbstr = _tag.split('_');
				var cb = parseInt(cbstr[1]);
				$('#cb').text(''+cb);
				if ( cb > 1 ){
					$('.continuous-bonus').show();
				} else {
					$('.continuous-bonus').hide();
				}
				if ( cb > 0 ){
					$('#continuous').text(cb);
					if ( $('#max_continuous') ){
						$('#max_continuous').hide();
						$('#max_continuous_name').hide();
						$('#continuous').show();
						$('#continuous_name').show();
					}
				} else {
					//通常は連チャンが終わったら - 表記
					$('#continuous').text('-');
					if ( $('#max_continuous') ){
						$('#max_continuous').show();
						$('#max_continuous_name').show();
						$('#continuous').hide();
						$('#continuous_name').hide();
					}
					
					
				}
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
		
		call.answer();
		showPhase('answer');
		// カメラからのStreamをvideoタグに追加します
		call.on('stream', function(stream) {
			showPhase('stream');
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
		if ( $('#autoplay_credit').hasClass('auto-on') ){
			autoPlay_Off();
			_sconnect.send(_sendStr( 'bae', 'autostop' ));
		}
	});
	// 2020-06-23 デフォルトhtmlでもauto画面を切り替えたらautoが中止される
	$('#changeauto,#changeauto2').change(function(){
		if ( $(this).val() == 0 ){
			//autoパネル切替の場合でもautoplayを止める
			if ( $('#autoplay_credit').hasClass('auto-on') ){
				autoPlay_Off();
				_sconnect.send(_sendStr( 'bae', 'autostop' ));
			}
		}
	});

	$('#autoplay_credit').click(function(){
		//2021-05-15 disableAutoがtrueなら実行しない
		if (disableAuto) return false;
		//2020-06-10 ROMとの共通化
		//Wait中の場合はボタン操作不可
		if ( $(this).hasClass('autoplay-wait') ){
			return;
		}
		//2021-05-06 自動入賞中表示の時はボタン操作なし
		if ( $(this).hasClass('active_bb') ){
			return false;
		}
		//2021-06-01 チェック判定追加
		if ( pachi_rate > 0 ){
			console.log(game.credit,parseInt($('#maxpoint').val()));
			if ( game.credit == 0 && parseInt($('#maxpoint').val()) > 0 ){
				var dt = new Date();
				dt = dt.getTime()
				var limit = localStorage.getItem('net8checkdate', '0');
				console.log( limit, dt );
				if (parseInt(limit) < dt || limit == null){
					//実行先をautoplayにしてcheckmodalを開く
					alert2play = true;
					$('#conv-alert-check').removeAttr('checked').prop('checked', false).change();
					$('#conv-alert-modal').modal();
					return;
				}
			}
		}
		/*
		//Wait中の場合はボタン操作不可
		if ( $(this).attr('waitlabel') == $(this).text() ){
			return;
		}
		*/
	
		if( !$(this).hasClass('auto-on') ) {
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
					.addClass('auto-on');

				//#bonus_countが存在しない時はラベルを変更
				if(!($('#bonus_count').length)){
					$(this).text( $(this).attr('stoplabel') );
				}
				$('#maxpoint').attr('readonly', true );
				usePoint = 0;

				// AUTOモード開始（韓国モードでも同じ動作）
				autoPlay(true);
			}
		} else {
			autoPlay_Off();
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
		if( !$('#autoplay_credit').hasClass('auto-on') ) {
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
					.addClass('auto-on');
				$('#autoplay_credit').text( $('#autoplay_credit').attr('stoplabel') );

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
			autoPlay_Off();
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
				if ( $('#autoplay_credit').hasClass('auto-on') && !autoStopSignal ){
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
		disableAuto = true;
		$('#autoplay_credit')
			.removeClass('auto-on');
		$('#maxpoint').attr('readonly', false );
		autoMode = false;
		autoModePrep = false;
		//#bonus_countが存在しない時はラベルを変更
		if( !($('#bonus_count').length)){
			$('#autoplay_credit').text( $('#autoplay_credit').attr('startlabel') );
		}
		//目押し操作可能に変更
		$('#menu_select').removeClass('disabled');
		$('#menu2_select').removeClass('disabled');

		$('.link-credit').hide();

		//連打禁止
		setTimeout(function(){
			disableAuto = false;
		}, 1500 );
	}

	//オートプレイ
	function autoPlay(cremode){
		var endInt = null

		autoBet(cremode)
		.then(function(ret){
			if ( !ret || game.credit <= 0 ){
				autoPlay_Off();
				_sconnect.send(_sendStr( 'bae', 'autoend' ));
				//bonus設定を戻す
				resetBonusSelect()
				return;
			}

			if ( $('#autoplay_credit').hasClass('auto-on') && !autoStopSignal ){
				if ( autoFirstEventFlg ){
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
				// 2021-05-15 追加
				disableAuto = true;
				//連打禁止
				setTimeout(function(){
					disableAuto = false;
				}, 1500 );
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
				// convPlaypointが未定義の場合はgame.conv_pointを使用（韓国play_embed対応）
				var convPointValue = (typeof convPlaypoint !== 'undefined' && convPlaypoint > 0)
					? convPlaypoint
					: (game.conv_point || 100);
				console.log( 'autoBet check:', targetUsePoint, usePoint, convPointValue, 'koreaMode:', koreaMode );
				if ( targetUsePoint - usePoint < convPointValue){
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
		} else if( id == 'sendBtnsoc' ){
			dataConnection.send(_sendStr( 'bsoc', 'click'));
			/*
			if ( activeGame ){
				btnStatus[id] = true;
				dataConnection.send(_sendStr( 'bsocd', "down"));
				lastid.push(id);
			} else {
				dataConnection.send(_sendStr( 'bsoc', "down"));
			}
			*/
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
