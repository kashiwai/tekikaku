
	var countNavel = 0;							//へそ投入回数
	var hesoReady = true;						//へそボタン押下可能フラグ
	var autoMode = false;						//自動モード判定
	var notSendSignals = ['Signal_0', 'Signal_3_End', 'Signal_4_End','Signal_6_End', 'Signal_7_End',];
												//使用しないSignal
	var activeFluctuation = false;				//確変判定

	function resetGame(){
		var game = {
			'machine_no'    : machine_no,
			'member_no'     : 0,
			'play_dt'       : '',
			'credit'        : 0,
			'playpoint'     : 0,
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
			//動作設定
			'min_credit'    : 15,
		}
		return game;
	}

	game = $.extend(true, {}, resetGame() );

	//カメラサーバを再起動する時間
	var serverRestartTime = "09:30";

	var restartInt = setInterval(function(){
		var dt = new Date();
		var nowDateStr = ("00" + dt.getHours()).slice(-2) +':'+ ("00" + dt.getMinutes()).slice(-2);
		//console.log( nowDateStr, serverRestartTime );
		$('#nowTime').html(nowDateStr);
		$('#restartTime').html(serverRestartTime);
		if ( nowDateStr == serverRestartTime ) {
			clearInterval( restartInt );
			location.reload();
			return;
		}
	},60000);

	console.log( 'camera_name:'+cameraid );
	
	//PythonのローカルWebSocketサーバに接続、成功後カメラとPeerを確立させる
	window.pythonServerConnect = function(){
		console.log('Try Connect Python WebSocket Server');
		
		//pythonとのsocket通信
		keysocket = new WebSocket('ws://127.0.0.1:50007');
		keysocket.onopen    = onOpen;
		keysocket.onmessage = onMessage;
		keysocket.onclose   = onClose;
		keysocket.onerror   = onError;
		
		function onOpen(event) {
			console.log("接続しました。");
			//Chrome起動確認用の送信
			//keysocket.send("run");
			//USBIOのステータス取得
			keysocket.send('@getUSBIO');
			console.log( $.cookie('videoid') );
			if ( $.cookie('videoid') != undefined ){
				$.cookie("videoid", $.cookie('videoid'), { expires: 365 });
				$.cookie("audioid", $.cookie('audioid'), { expires: 365 });
				$('#setting').hide();
				$('#running').show();
				//Pythonサーバの起動が確認されたのでカメラとPeerを確立させる => main.js
				AdapterJsStart();
				//開始をAPIを通じてサーバに知らせる
				//setCameraStatus("start")
			} else {
				//初期設定がまだの場合
				$('#setting').show();
				$('#running').hide();
			}
			$('#keysocket').addClass('active');
		}
		function onMessage(event) {
			if (event && event.data) {
				if ( notSendSignals.indexOf( event.data ) >= 0 ) return;
				//console.log("python event ", event.data);
				socketSignalLog( event.data );
				
				//デジカウンターの制御信号
				if ( event.data == '@startUSBIO' ){
					//DigiCounterの接続が確認された
					setCameraStatus("start");
				} else if ( event.data == '@endUSBIO' ){
					//DigiCounterの接続が切れた。
					setCameraStatus("end");
				}
				
				if ( _sconnect ){
					
					var _tag = event.data;
					var addCredit = 0;
					//ゲーム機からの信号別の処理
					if( _tag == 'Signal_0_End' ){
						if ( !activeFluctuation ){
							game.count++;
							game.total_count++;
							countNavel--;
							if ( countNavel < 0 ) countNavel = 0;
							$('#countHeso').text(countNavel);
						} else {
							if ( !activeBonus ){
								game.count++;
								game.total_count++;
							}
						}
						//絵柄確定を全体ゲーム数としてカウント
						upGameCount++;
						//絵柄確定が10回ごとにログに記録していく。
						if ( upGameCount % 10 == 0 ){
							playLog()
							.then(function(){
							
							},function(data){
								setCameraStatus('log', 30, 'play log API error' );
							});
						}
					} else if ( _tag == 'Signal_1' ){				//確変開始
						activeFluctuation = true;
						$('#rb_on').prop('checked', true);
					} else if ( _tag == 'Signal_1_End' ){			//確変終了
						activeFluctuation = false;
						$('#rb_on').prop('checked', false);
					} else if ( _tag == 'Signal_2' ){				//大当り開始
						activeBonus = true;
						game.bb_count++;
						game.user_BB++;
						$('#bb_on').prop('checked', true);
						playLog('bb')								//ログ記録
						.then(function(){
						
						},function(data){
							setCameraStatus('log', 30, 'play log API error' );
						});
					} else if ( _tag == 'Signal_2_End' ){			//大当り終了
						activeBonus = false;
						$('#bb_on').prop('checked', false);
					} else if ( _tag == 'Signal_3' ){				//へそ入賞 
						countNavel++;
						if ( countNavel > maxHeso ){
							countNavel = maxHeso + 1;
						}
						addCredit = (outHeso - Math.abs(oneGameCredit));
						game.credit += addCredit;
						if ( game.credit < 0 ){
							game.in_credit += (Math.abs(oneGameCredit) + game.credit);
							game.mc_in_credit += (Math.abs(oneGameCredit) + game.credit);
							game.credit = 0;
						} else {
							game.in_credit += Math.abs(oneGameCredit);
							game.out_credit += outHeso;
							game.mc_in_credit += Math.abs(oneGameCredit);
							game.mc_out_credit += outHeso;
						}
						$('#credit').text(game.credit);
						$('#countNavel').text(countNavel);
					} else if ( _tag == 'Signal_4' ){				//電チュー
						if ( activeFluctuation ){
							addCredit = outDchu;
						} else {
							addCredit = (outDchu + Math.abs(oneGameCredit));
							game.in_credit  += Math.abs(oneGameCredit);
							game.mc_in_credit += Math.abs(oneGameCredit);
						}
						game.credit += addCredit;
						game.out_credit += outDchu;
						game.mc_out_credit += outDchu;
						$('#credit').text(game.credit);
					} else if ( _tag == 'Signal_6' ){
						addCredit = outAttacker1;
						game.credit += addCredit;
						game.out_credit += outAttacker1;
						game.mc_out_credit += outAttacker1;
						$('#credit').text(game.credit);
					} else if ( _tag == 'Signal_7' ){
						addCredit = outAttacker2;
						game.out_credit += outAttacker2;
						game.mc_out_credit += outAttacker2;
						game.credit += addCredit;
						$('#credit').text(game.credit);
					}
					
					_sconnect.send( _sendStr(event.data, '') );

					showGame();
					
				}
			}
		}
		
		function onError(event) {
			console.log("エラーが発生しました。");
			console.log("Python WebSocket Serverの起動がまだなので、しばらくしたらリトライ");
			
			//本来はここに
			setTimeout( pythonServerConnect, 5000);
			
		}
		function onClose(event) {
			console.log("切断しました。");
			keysocket = null;
			setCameraStatus('log', 20, 'socket close' );
			$('#keysocket').removeClass('active');
		}
	}
	setTimeout( pythonServerConnect, 5000);	//本番では 25000 ぐらい
	
	function checkPlayLog(){
		if ( !endOneGame ){
			endOneGame = true;
			if ( upGameCount % 10 == 0 ){
				playLog()
				.then(function(){
				
				},function(){
					setCameraStatus('log', 30, 'play log API error' );
				});
			}
			
		}
		//時間をチェックして時間外になっていたら終了処理
		checkCloseGame();
	}
	
	var _ct = false;
	
	function connectPeer (cameraStream) {
		var peer = new Peer(
			cameraid, {
			host: sigHost,
			port: 9000,
			key:peerjskey, 			//API key
			token:authID,
			config: {
				iceServers,
				"iceTransportPolicy":"all",
				"iceCandidatePoolSize":"0"
			},
			debug: 3 // 詳細なログをconsoleに表示
		});
		$('#peerserver').addClass('active');

		// 閲覧側からの接続要求をハンドリングします
		peer.on('connection', function(dataConnection) {
			console.log("peer connect");
			
			//metaデータから認証
			checkAuth(dataConnection.metadata)
			.then(
				function(data){

					//FireFox対策
					if ( activeFlg ){
						connectCloseMethod();
						closeSkipFlg = true;
					}

					activeFlg = true;
					noPayFlg = true;				//未清算フラグをセット
					clearTimeout(noPayTimer);		//未清算のタイマーをクリア
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
						if ( !closeSkipFlg ){
							connectCloseMethod();
						}
						closeSkipFlg = false;

						$('#useractive').removeClass('active');
/*					
						console.log( 'connection lost');
						//アクティブフラグ解除
						active = false;
						//終了予告タイマーの解除
						clearTimeout(noticeTimeout);
						//未清算の場合はタイマーを設定
						if ( noPayFlg ){
							console.log( 'no pay wait');
							noPayTimer = setTimeout(function(){
								console.log( "!!auto pay" );
								setCameraStatus('log', 20, 'auto pay exec' );
								execPay();
							}, autoPayTime * 60000 );
						}
*/
					});
					//データチャンネルハンドリング
					dataConnection.on('data', function(data){
						//var _json = JSON.parse( data);
						//console.log(_json);
						//var _t = _json['tag'];
						
						var _darry = data.trim().split(',');
						var _t = _darry[0].split(':')[1];
						var _msg = _darry[1].split(':')[1];
						
						//console.log( 'dc:'+_t);
						if ( _msg != '' ){
							userSignalLog( _t+' ['+_msg+']');
						} else {
							userSignalLog( _t );
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
								dataConnection.send( _sendStr('Acre', game.credit) );
								dataConnection.send( _sendStr('Apt',  game.playpoint) );
								dataConnection.send( _sendStr('Cst',  'ok') );
							},function(){
								//エラー処理
								dataConnection.send( _sendStr('Cst',  'ng') );
							});
							return;
						}
						if ( _t == 'pay' ){
							//精算処理
							pay();
							return;
						}
						
						//営業時間外処理
						if ( !closeGameFlg ){
							keysocket.send(_t);
						} else {
							//console.log( 'NG control: '+ _t );
							userSignalLog( '!!NG '+_t );
						}
					});
					
					//初回設定（未清算状態ならmember_noが残っているのでその場合はステータスを保持）
					if( game.member_no == 0 ){
						game = $.extend( true, game, data.game );
						//console.log( game );
					}
					
					//一応画面に現在の状態を表示
					showGame();
					
					//初回のデータを送る（コネクションの確立に時間がかかるので１秒待ち）
					setTimeout(function(){
						if ( activeBonus ){
							dataConnection.send( _sendStr('Aab',  'on') );
						}
						if ( activeFluctuation ){
							dataConnection.send( _sendStr('Aaf',  'on') );
						}
						dataConnection.send( _sendStr('Acre', game.credit) );
						dataConnection.send( _sendStr('Apt',  game.playpoint) );
						dataConnection.send( _sendStr('Atc',  game.total_count) );
						dataConnection.send( _sendStr('Act',  game.count) );
						dataConnection.send( _sendStr('Abb',  game.bb_count) );
						dataConnection.send( _sendStr('Arb',  game.rb_count) );
						dataConnection.send( _sendStr('Acp',  game.conv_point) );
						dataConnection.send( _sendStr('Acc',  game.conv_credit) );
						dataConnection.send( _sendStr('Amc',  game.min_credit) );
						dataConnection.send( _sendStr('Ach',  countNavel) );
						dataConnection.send( _sendStr('RDY',  game.member_no+'_'+game.play_dt) );
					}, 1000);
				},
				function(reason){
					//認証失敗
					console.log("checkAuth failed");
					//強制的に接続を切る
					dataConnection.close();
					console.log( "dataConnection.close" );
				}
			);
		});
		peer.on('close', function(){
			//終了ステータスを送る
			setCameraStatus("end")
			.then(function(){

			});
		});
		
		var unloadflg = false;
		// ページを閉じた際にpeerをクリアします
		$(window).on('beforeunload', function(){
			if (! peer.destroyed) {
				peer.destroy();
			}
		});
		$('#exitbutton').on('click', function(){
			if ( confirm('カメラサーバを終了しますか？') ){
				//もしプレイ中であれば強制的に精算処理をする。
				pay()
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
						//Chromeはタブを閉じれないなのでblankのタブにする
						window.open('about:blank', '_self').close();
					});
				});
			}
		});

		function connectCloseMethod() {
			console.log( 'connection lost');
			//アクティブフラグ解除
			activeFlg = false;
			//終了予告タイマーの解除
			clearTimeout(noticeTimeout);
			//決済インターバルの解除
			clearInterval( intvCheckBuy );
			//autoplay中に切断が発生したときの停止信号を設定
			if ( autoModeFlg ) autoStopFlg = true;
			
			//未清算の場合はタイマーを設定
			if ( noPayFlg ){
				console.log( '===========no pay wait');
				noPayTimer = setTimeout(function(){
					//もし既にプレイ状態だった場合は処理しない
					if ( activeFlg ) return;
					console.log( "===========!!auto pay" );
						setCameraStatus('log', 20, 'auto pay exec' );
						execPay();
				}, autoPayTime * 60000 );
			}
		}
	}
	
	//再起動時などで既にボーナス状態なのに感知できていない場合用の強制ボーナスフラグ
	$('#bb_on').bind('click', function(){
		if ( $(this).prop('checked') ){
			activeBonus = true;
		} else {
			activeBonus = false;
		}
		showGame();
	});
	$('#rb_on').bind('click', function(){
		if ( $(this).prop('checked') ){
			activeFluctuation = true;
		} else {
			activeFluctuation = false;
		}
		showGame();
	});
