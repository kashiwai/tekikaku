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
 */
;
var _sconnect;						//外部call用dataConnection

	//保持データ
	var game = {
		'credit'      : 0,
		'playpoint'   : 0,
		'total_count' : 0,
		'bb_count'    : 0,
		'rb_count'    : 0,
		'count'       : 0,
		'min_credit'  : 2,
		'ccc_status'  : '',
	};
	
	var activeBonus = false;					//ボーナス中かどうかの判定
	var activeBB = false;						//BBのボーナス判定
	var dataConnection;							//dataConnection(peer)
	var endOneGame = true;						//ゲーム終了フラグ false:1ゲームサイクル中 true:ゲーム終了
	var startTop;
	var startLeft;
	var startWidth;
	//autoplay用設定
	var usePoint = 0;							//使用ポイント数
	var targetUsePoint = 0;						//Max利用可能ポイント
	var aliveInterval = null;					//離籍確認タイマー
	var lastTimestamp = (new Date()).getTime();	//タイムスタンプ
	var leaveTime = 5 * 60000;					//離籍警告時間
	var bonusCountMark = '-';					//ボーナス中のゲーム数表記
	var addCredit = 0;							//残り加算クレジット数
	var autoStopSendFlg = false;				//autoモードの停止信号送信フラグ
	var autoStopSignal = false;					//autoモード停止フラグ
	var autoMode = false;						//autoモード設定
	var videoWidth;								//videoサイズ
	var recvLang = false;						//recv 'Lng'

	var inCreditCount = 0;						//inCreditのカウント
	var inCreditTimeSpan = 0;					//inCreditの来る間隔
	var inCreditCheckTime = 0;					//inCreditの初回time
	var inCreditStartTime = 0;					//inCreditの開始時間

	var autoPlayRestartTime = 1000;				//Signal5またはSac後から bst を送信するまでの時間(ms)
	var autoPlayResendTime  = 3000;				//bst送信後にSignal_0が来ない
	
	var btnStatus = {'sendBtns1': false, 'sendBtns2': false, 'sendBtns3': false };
	var autoFirstEventFlg = false;
	//peer setting
	var peersetting = {
		host: sigHost,
		port: 9000,
		key:peerjskey,
		token:authID,
		config: {
			'iceServers': iceServers,
			"iceTransportPolicy":"all",
			"iceCandidatePoolSize":"0"
		},
		debug: 0
	};
	
	//FireFox media.peerconnection.enabled = false の対応
	try {
		var a = new window.RTCPeerConnection();
	} catch(e) {
		$('#loading').html('接続に失敗しました。(RTC01)');
	}
	
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
			writeLog( 'connect lost' );
			//connectionだけが落ちることがあるのでconnectionが切れたらpeerを切る
			if (! peer.destroyed) {
				peer.destroy();
			}
		});

		_sconnect = dataConnection;

		//購入ボタン生成
		buildPayLink();

		//一定時間操作がない場合は終了させるイベント設定及びセッションの維持
		aliveInterval = setInterval(function(){
			var span = (new Date()).getTime() - lastTimestamp;
			writeLog( "5min not action:"+span );
			if ( span >= leaveTime ){
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

			//セッション維持用API呼び出し
			sessionAPI()
			.then(function(data){
				
			},function(data){
				writeLog( 'sessionAPI error' );
			});
		}, 60000 );
		
		$('.sendBtn').each(function(){
			$(this).bind(_touch,function(){
				var id = $(this).attr('id').split('_')[0];
				
				if (game.credit <= 0 && id == 'sendBtnsb' ){
					errorAlert( errorMessages['U5051'] );
					return;
				}
				if (game.credit <= 0 && id == 'sendBtnss' ){
					errorAlert( errorMessages['U5051'] );
					return;
				}
				if ( id == 'sendBtnss' ){
					btnStatus = {'sendBtns1': false, 'sendBtns2': false, 'sendBtns3': false };
				}
				if ( id == 'sendBtns1' || id == 'sendBtns2' || id == 'sendBtns3' ) {
					console.log( 'click:'+id );
					btnStatus[id] = true;
				}if ( btnStatus['sendBtns1'] == true && btnStatus['sendBtns2'] == true && btnStatus['sendBtns3'] == true ){
					console.log( 'all click');
					setTimeout(function(){
						console.log( 'check reset!');
						if ( btnStatus['sendBtns1'] == true && btnStatus['sendBtns2'] == true && btnStatus['sendBtns3'] == true ){
							if ( !endOneGame ) {
								console.log( 'no sigunal: reset endOneGame!!' );
								endOneGame = true;
							}
						}
					},1000 );
				}
				
				//autoplay中にマニュアルボタンを押したらautoplayを解除してマニュアルモードに移行する
				if ( $('#autoplay_credit').hasClass('autoplay-on') ){
					//解除するのはリールストップボタンのみ
					if ( id == 'sendBtns1' || id == 'sendBtns2' || id == 'sendBtns3' ){
						autoPlay_Off();
						dataConnection.send(_sendStr( 'bsy', "stop"));
					}
				}

				try {
					dataConnection.send(_sendStr( 'b'+id.split('sendBtn')[1], "click"));
				} catch (e) {
					console.log( '====SEND ERROR:','b'+id.split('sendBtn')[1] );
				}
				
			});
		});
		
		/*
		 * keydownイベント
		 * @access	public
		 * @param	object		e			イベント
		 * @return	なし
		 * @info    なし
		 */
		$(document).keydown(function(e) {
			//autoplay時にはショートカットを使えない
			if ( $('input[name=ctrl-autoplay]:checked').val() == 1 ) return;
			writeLog( e.keyCode );
			switch(e.keyCode){
				case 90 :
		 			if ( game.credit <= 0 ){
		 				errorAlert( errorMessages['U5051'] );
		 				return;
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
				default:
					return;
			}
			dataConnection.send(_sendStr( 'b'+id, "click"));
		});

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

		//maxbitエリア処理
		$('#maxpoint').bind('keypress', function(){
			return leaveOnlyNumber(event);
		});
		//paste禁止
		$('#maxpoint').bind('paste', function(e){
			e.preventDefault();
		});
		//プレイポイント購入ボタン処理
		$('.buyButton').bind(_click, function(){
			var data = $(this).attr('target');
			writeLog( 'click buy('+data+')' );
			dataConnection.send(_sendStr( 'cpb', data));
		});

		//クレジット変換ボタン処理
		$('#convCredit').bind(_click, function(){
			writeLog( 'click convCredit' );
			dataConnection.send(_sendStr( 'ccc', ''));
		});

		//精算ボタン処理
		$('#pay').bind(_click, function(){
			//操作禁止処理を入れる
			writeLog( 'click pay' );
			dataConnection.send(_sendStr( 'pay', ''));
		});
		
		
		//精算結果のモーダルが閉じた時の処理
		$('#end-modal').on('hide.bs.modal', function () {
			location.href = '/gameafter.php';
		});
		
		//データチャンネルハンドリング
		dataConnection.on('open', function(data){
			showPhase('dataConnection open');
			console.log( 'dataConnection open');
			if ( !recvLang ){
				dataConnection.send(_sendStr( 'Lng', languageMode ));
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
				
			} else if ( _tag == 'Signal_0' ){							//Signal_IN
				//2019-10-02 タイミングを変更
				if ( endOneGame ) {
					endOneGame = false;
					if ( !activeBonus ){
						//bonusに入っているときはゲーム数を加算しない
						game.total_count++;
						game.count++;
						$('#total_count').text(game.total_count);
						$('#count').text(game.count);
					}
				}
				if ( inCreditCheckTime > 0 && inCreditCount > 0){
					var tspan = lastTimestamp - inCreditCheckTime;
					if ( inCreditTimeSpan == 0 ) inCreditTimeSpan = tspan;
					inCreditTimeSpan = Math.ceil((inCreditTimeSpan + tspan) / 2);
				}
				inCreditCheckTime = lastTimestamp
				//2019-09-10 increditをカウントして3枚投入ならgame+1にする方式に変更
				//2019-10-02 gameカウントは別でする仕様に変更
				inCreditCount++;
				if ( inCreditCount == 1 ){
					var gameSpanTime = 0;
					if ( inCreditStartTime > 0 ){
						gameSpanTime = lastTimestamp - inCreditStartTime;
						writeLog( 'gameSpanTime:'+gameSpanTime );
						if ( gameSpanTime < 4100 ){
							gameSpanTime = 4100 - gameSpanTime;
						} else {
							gameSpanTime = 0;
						}
					}
					//開始時間設定
					inCreditStartTime = lastTimestamp

					writeLog( 'inCreditTimeSpan:'+inCreditTimeSpan );
					writeLog( 'gameSpanTime:'+gameSpanTime );
					var bluedelay = 400 + (inCreditTimeSpan*2) + gameSpanTime;
					if ( bluedelay == 0 ) bluedelay = 600;
					writeLog( 'blue time delay:'+bluedelay );
					//青ボタン点灯
					setTimeout(function(){
						setRealStop('#sendBtns1', true);
						setRealStop('#sendBtns2', true);
						setRealStop('#sendBtns3', true);
						//このタイミングぐらいしかリセットできない
						inCreditCount = 0;
					},bluedelay);
				}
				if ( inCreditCount >= 3 ){
					inCreditCount = 0;					//0に戻す
					endOneGame = false;
				}
			
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
				if ( $('#autoplay_credit').hasClass('autoplay-on') && !autoStopSignal ){
					setTimeout(function(){
						autoPlay(true);
					},autoPlayRestartTime);
				} else {
					autoPlay_Off();
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
			} else if ( _tag == 'Signal_2' ){							//Signal_RB_Start
				writeLog( '================REG_Start================' );
				activeBonus = true;
				if ( !activeBB ){
					game.rb_count++;
					game.count = 0;										//カウントリセット
					$('#rb_count').text(game.rb_count);
					$('#count').text(bonusCountMark);
					$('#rb_count').bonusAnime(true);
				}
			} else if ( _tag == 'Signal_3_End' ){						//Signal_BB_End'
				writeLog( '----------------BIG_End----------------' );
				activeBonus = false;
				activeBB = false;
				game.count = 0;
				$('#bb_count').bonusAnime(false);
			} else if ( _tag == 'Signal_2_End' ){						//Signal_RB_End'
				writeLog( '----------------REG_End----------------' );
				if ( !activeBB ){
					activeBonus = false;
					game.count = 0;
					$('#rb_count').bonusAnime(false);
				}
			} else if ( _tag == 'Signal_5' ){							//Signal_DrumStop
				
			} else if ( _tag == 'bsy' ){								//1gameの終了
				endOneGame = true;
				//autoplayの判定
				console.log( game.credit, targetUsePoint );
				if ( game.credit == 0 && targetUsePoint - usePoint < convPlaypoint ){
					writeLog( "---------no credit!!" );
					autoPlay_Off();
					errorAlert( errorMessages['U5051'] );
					return;
				}
				if ( $('#autoplay_credit').hasClass('autoplay-on') && !autoStopSignal ){
					setTimeout(function(){
						autoPlay(true);
					},autoPlayRestartTime);
				} else {
					writeLog( "---------drum stop!!!" );
					autoPlay_Off();

				}
			} else if ( _tag == 'bst' ){								//auto開始(echo)
			//ここからは操作関連
			} else if ( _tag == 'bss' ){								//スタート
			} else if ( _tag == 'bs1' ){								//左
				setRealStop('#sendBtns1', false);
			} else if ( _tag == 'bs2' ){								//中
				setRealStop('#sendBtns2', false);
			} else if ( _tag == 'bs3' ){								//右
				setRealStop('#sendBtns3', false);
			
			//ここからデータ関連
			//ボーナスフラグ
			} else if ( _tag == 'Aabb' ){
				activeBB = true;
				$('#bb_count').bonusAnime(true);
			} else if ( _tag == 'Aab' ){
				activeBonus = true;
				if ( !activeBB ){
					$('#rb_count').bonusAnime(true);
				}
				$('#count').text(bonusCountMark);
			//クレジット
			} else if ( _tag == 'Acre' ){
				game.credit = parseInt(_msg)
				$('#credit').text(game.credit);
				$('#animeNumber').animetionNumber( 0, game.credit );
			//プレイポイント
			} else if ( _tag == 'Apt' ){
				var span = game.playpoint;
				game.playpoint = parseInt(_msg);
				
				if ( $('#autoplay_credit').hasClass('autoplay-on') ){
					span = span - game.playpoint;
					if ( span > 0 ){
						usePoint += span;
						writeLog( 'auto use point:' + usePoint );
						//使ったポイントを減算してテキスト表示
						$('#maxpoint').val( targetUsePoint - usePoint );
					}
				}
				$('#playpoint').text(numberFormat(game.playpoint));
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
			//ゲーム準備完了
			} else if ( _tag == 'RDY' ){
				setConvText();
				$('#machine_no').text(machineno);
				$('nav').show();
				$('#loading').hide();
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
				if( game.ccc_status == "fail" ){
					dataConnection.close();
					//クレジット変換エラー
					errorAlert( errorMessages['U5059'] );
				} else if ( game.ccc_status == "ng" ) {
					errorAlert( errorMessages['U5054'] );
				}
			//精算（プレイポイント）
			} else if ( _tag == 'Ppp' ){
				$('#pay_play_point').text( numberFormat(parseInt(_msg)) );
			//精算（クレジット）
			} else if ( _tag == 'Pcr' ){
				$('#pay_credit').text( numberFormat(parseInt(_msg)) );
			//精算（抽選ポイント）
			} else if ( _tag == 'Pdr' ){
				$('#pay_draw_point').text( numberFormat(parseInt(_msg)) );
			//精算（トータル抽選ポイント）
			} else if ( _tag == 'Ptd' ){
				$('#pay_total_draw_point').text( numberFormat(parseInt(_msg)) );
			//ゲーム終了
			} else if ( _tag == 'EXT' ){
				$('#loading_connect').hide();
				$('#loading_pay').show();
				$('#loading')
					.css('height', $('body').css('height') )
					.show();
				dataConnection.close();
				if (! peer.destroyed) {
					peer.destroy();
				}
				//精算結果モーダル表示
				setTimeout(function(){
					$('#end-modal')
						.css('z-index', 6000)
						.modal({
							backdrop: 'static',
							keyboard: false
						})
					;
					//インターバルの停止
					clearInterval( aliveInterval );
				},2000);
			//精算処理失敗
			} else if ( _tag == 'ERP' ){
				//精算失敗
				errorAlert( errorMessages['U5058'] );
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
			try {
				document.getElementById('video').srcObject = stream;
			} catch (error) {
				$('#video').attr('src', URL.createObjectURL(stream));
			}

			//
			var audio = document.querySelector('audio');
			try {
				document.getElementById('audio').srcObject = stream;
			} catch (error) {
				audio.src = window.URL.createObjectURL(stream);
			}

			console.log( 'browserVersion:'+browserVersion );

			setVideoWidth();

			showPhase('video');
			
			//即時で切り替えるとvideoの表示がスムースにいかないのでDelayさせる
			setTimeout(function(){
				showPhase('audio');

				$('#video').show();
				$('.img-fluid').hide();

				setVideoWidth();

				//PCでもワンアクションがないと音がでない時があるので最初は音をださないほうがいい？
				if ( getDevice() == "other" ){
					setAudio();
				}

				//言語設定の送信
				_sconnect.send(_sendStr( 'Lng', languageMode ));

			},500);
		});
	});
	
	$('#audiostart,#audiostart_auto').click(function(){
		setAudio();
	});

	$('#autoplay_credit').click(function(){
		//Wait中の場合はボタン操作不可
		if ( $(this).attr('waitlabel') == $(this).text() ){
			return;
		}
	
		if( $(this).hasClass('autoplay-off') ) {
			targetUsePoint = parseInt($('#maxpoint').val());
			if ( !targetUsePoint ) targetUsePoint = 0;
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
					.addClass('autoplay-on')
					.text( $(this).attr('stoplabel') );
				$('#maxpoint').attr('readonly', true );
				usePoint = 0;
				
				autoStopSendFlg = false;
				autoStopSignal = false;
				autoFirstEventFlg = true;
				autoPlay(true);
			}
		} else {
			autoStopSignal = true;
			//autoPlay_Off();
			autoPlay_Wait();
		}
	});

	//オートプレイの継続or停止チェック
	function checkAutoPlay(){
		//50msまってSignal_OUTが発生しなくなったら終了とみなす
		setTimeout(function(){
			if ( signalOUT_End ) return;
			if ( game.credit == saveCredit ){
				if ( $('#autoplay_credit').hasClass('autoplay-on') && !autoStopSignal ){
					signalOUT_End = true;
					setTimeout(function(){
						autoPlay(true);
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
			.addClass('autoplay-wait')
			.text( $('#autoplay_credit').attr('waitlabel') );
		autoMode = false;
	}


	//オートプレイ停止
	function autoPlay_Off(){
		$('#autoplay_credit')
			.removeClass('autoplay-on')
			.removeClass('autoplay-wait')
			.addClass('autoplay-off')
			.text( $('#autoplay_credit').attr('startlabel') );
		$('#maxpoint').attr('readonly', false );
		autoMode = false;
	}

	//オートプレイ
	function autoPlay(cremode){
		var endInt = null

		autoBet(cremode)
		.then(function(ret){
			if ( !ret || game.credit <= 0 ){
				autoPlay_Off();
				return;
			}

			if ( $('#autoplay_credit').hasClass('autoplay-on') && !autoStopSignal ){
				if ( autoFirstEventFlg ){
					//autoplayの初回のみ
					dataConnection.send(_sendStr( 'bsb', "auto"));				//Signal5が保留になっているかもしれないので先に消化
					//自動でbsyが発行されるのでディレイしてbstを送信する
					setTimeout(function(){
						dataConnection.send(_sendStr( 'bst', "auto"));			//自動モードへの切り替え
					}, 800 );
					autoFirstEventFlg = false;
				} else {
					//通常
					dataConnection.send(_sendStr( 'bst', "auto"));			//自動モードへの切り替え
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
			console.log( targetUsePoint,usePoint,convPlaypoint );
				if ( targetUsePoint - usePoint < convPlaypoint){
					resolve(false);
					return;
				}
				writeLog( '[autoBet] exec' );
				//クレジット変換を自動実行
				game.ccc_status = "";
				dataConnection.send(_sendStr( 'ccc', ''));
				intid = setInterval(function(){
					if ( game.ccc_status == "ok" ){
						writeLog( '[autoBet] ok' );
						clearInterval( intid );
						resolve(true);
					} else if ( game.ccc_status == "ng" ){
						console.log( '[autoBet] ng' );
						writeLog( intid );
						reject();
					}
				},50);
			} else {
				resolve(true);
			}
		});
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

