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
 * @since     2019/04/19 ver1.0 村上俊行 初版作成
 * @using
 * @desc
 */

	//使用しないSignal設定
	var notSendSignals = ['Signal_0_End', 'Signal_1_End'];

	//ドラム停止判断シグナル
	var drumStopSignalName = ['Signal_5', 'Signal_5_End']
	//drum指定がない場合のデフォルト設定
	if ( typeof layoutOption !== 'undefined'){
		if ( typeof layoutOption['drum'] === 'undefined') layoutOption['drum'] = 1;
	}
	console.log( layoutOption );

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
			//動作設定
			'min_credit'    : 3,
		}
		return game;
	}

	game = $.extend(true, {}, resetGame() );

	var saveCredit = 0;
	var addCredit = 0;
	//カメラサーバを再起動する時間(setting.phpの値へ移行）
	//var serverRestartTime = "09:30";

	//peerjsのセッションのオートリセット
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
		//接続中の場合
		if ( activeFlg ){
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
						console.log( "===========!!auto stop pay" );
						//精算実行
						pay()
						.then(function(){
							//_sconnect.close();
						});
					}, countdown );
				}

			},function(data){
				
			});
		}

	},30000);
	
	console.log( 'camera_name:'+cameraid );
	
	//PythonのローカルWebSocketサーバに接続、成功後カメラとPeerを確立させる
	window.pythonServerConnect = function(){
		console.log('Try Connect Python WebSocket Server');
		
		//pythonとのwebsocket通信
		keysocket = new WebSocket('ws://127.0.0.1:59007');
		keysocket.onopen    = onOpen;
		keysocket.onmessage = onMessage;
		keysocket.onclose   = onClose;
		keysocket.onerror   = onError;
		
		//Open CallBack
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
				//USBIOを起動の判定とするのでここは動作させない
				//setCameraStatus("start")
			} else {
				//初期設定がまだの場合
				$('#setting').show();
				$('#running').hide();
			}
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

				if ( _sconnect ){
					var _tag = event.data;
					var _msg = '';
					
					//デバッグSignal5を発生させない
					//if ( _tag == 'Signal_5' || _tag == 'Signal_5_End'){
					//	return;
					//}
					
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
						if ( !activeBonus ){
							//ボーナス中はゲーム数の加算をしない
							game.total_count++;
							game.count++
							game.user_game++;
						}
						$('#total_count').text(game.total_count);
						$('#count').text(game.count);
					} else if ( _tag == 'Signal_0' ){						//Signal_IN
						//１ゲーム開始設定
						if( endOneGame ){
							endOneGame = false;							//1ゲームの開始
							upGameCount++;
							if ( !activeBonus ){
								//ボーナス中はゲーム数の加算をしない
								game.total_count++;
								game.count++
								game.user_game++;
							}
							$('#total_count').text(game.total_count);
							$('#count').text(game.count);
							
							//Signal5が発生しないタイプ用に一定時間後に停止信号をおくるイベント
							// 5～7秒のラグがあるので無理
							//setTimeout(function(){
							//	checkPlayLog();
							//},6000 );
						}

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
						//var saveCredit = game.credit;
						//100msまってSignal_OUTが発生しなくなったら終了とみなす
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
						//エウレカ250 マドマギ200 安定を見て300に設定する
					} else if ( _tag == 'Signal_2' ){						//Signal_RB_Start
						activeBonus = true;
						game.rb_count++;
						game.user_RB++;
						$('#rb_count').text(game.rb_count);
						$('#rb_on').prop('checked', true);
						playLog('rb')								//ログ記録
						.then(function(){
						
						},function(data){

						});
					} else if ( _tag == 'Signal_3' ){						//Signal_BB_Start
						activeBonus = true;
						activeBB = true;
						game.bb_count++;
						game.user_BB++;
						$('#bb_count').text(game.bb_count);
						$('#bb_on').prop('checked', true);
						playLog('bb')								//ログ記録
						.then(function(){
						
						},function(data){

						});
					} else if ( _tag == 'Signal_2_End' ){					//Signal_RB_End
						activeBonus = false;
						game.count = 0;
						$('#rb_on').prop('checked', false);
					} else if ( _tag == 'Signal_3_End' ){					//Signal_BB_End'
						activeBonus = false;
						activeBB = false;
						game.count = 0;
						$('#bb_on').prop('checked', false);
					} else if ( _tag == 'Signal_5' || _tag == 'Signal_5_End'){
																			//Signal_DrumStop
						//設定されている
						if ( drumStopSignalName[layoutOption['drum']] != _tag ){
							//console.log( 'skip Signal5' );
							return;
						}
						//シグナルの共通化
						_tag = 'Signal_5';
						if ( saveCredit > 0 ){
							setTimeout(function(){
								checkPlayLog();
							}, 500 );
						} else {
							checkPlayLog();
						}
						//console.log( autoModeFlg, autoStopFlg );
						if ( autoModeFlg && autoStopFlg ){
							//機器を初期状態に戻す
							resetMachine();
						}
					}
					
					_sconnect.send( _sendStr(_tag, _msg) );

					showGame();
					
				}
			}
		}
		
		//Error CallBack
		function onError(event) {
			console.log("エラーが発生しました。");
			console.log("Python WebSocket Serverの起動がまだなので、しばらくしたらリトライ");
			
			//本来はここに
			setTimeout( pythonServerConnect, 5000);
			
		}
		
		//Close CallBack
		function onClose(event) {
			console.log("切断しました。");
			keysocket = null;
			$('#keysocket').removeClass('active');
		}
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
		showGame();

		// 閲覧側からの接続要求をハンドリングします
		peer.on('connection', function(dataConnection) {
			console.log("===========!!peer connect Start");
			
			//言語設定をnullに
			languageMode = null;

			//metaデータから認証
			checkAuth(dataConnection.metadata)
			.then(
				function(data){
					console.log( data );
				
					//FireFox対策
					if ( activeFlg ){
						connectCloseMethod();
						closeSkipFlg = true;
					}

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
					//接続時に自動起動関係はオフにする
					autoModeFlg = false;
					autoStopFlg = false;
				
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

						$('#useractive').removeClass('active');
						
/*
						console.log( 'connection lost');
						//アクティブフラグ解除
						activeFlg = false;
						//終了予告タイマーの解除
						clearTimeout(noticeTimeout);
						
						//autoplay中に切断が発生したときの停止信号を設定
						if ( autoModeFlg ) autoStopFlg = true;
						
						//未清算の場合はタイマーを設定
						if ( noPayFlg ){
							console.log( '===========no pay wait');
							noPayTimer = setTimeout(function(){
								console.log( "===========!!auto pay" );
//								if ( !activeFlg && noPayFlg ){
									execPay();
//								}
							}, autoPayTime * 60000 );
						}
*/
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
								dataConnection.send( _sendStr('Acre', game.credit) );
								dataConnection.send( _sendStr('Apt',  game.playpoint) );
								dataConnection.send( _sendStr('Cst',  'ok') );
							},function(data){
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
						console.log( game );
					}
					
					//一応画面に現在の状態を表示
					showGame();

/*
					dataConnection.on('open', function(data){
						console.log( '===========!!dataConnection open' );
						setTimeout(function(){
							if ( activeBB ){
								_sconnect.send( _sendStr('Aabb',  'on') );
							}
							if ( activeBonus ){
								_sconnect.send( _sendStr('Aab',  'on') );
							}
							_sconnect.send( _sendStr('Acre', game.credit) );
							_sconnect.send( _sendStr('Apt',  game.playpoint) );
							_sconnect.send( _sendStr('Atc',  game.total_count) );
							_sconnect.send( _sendStr('Act',  game.count) );
							_sconnect.send( _sendStr('Abb',  game.bb_count) );
							_sconnect.send( _sendStr('Arb',  game.rb_count) );
							_sconnect.send( _sendStr('Acp',  game.conv_point) );
							_sconnect.send( _sendStr('Acc',  game.conv_credit) );
							_sconnect.send( _sendStr('Amc',  game.min_credit) );
							_sconnect.send( _sendStr('RDY',  game.member_no+'_'+game.play_dt) );
							console.log( '===========!!RDY send' );
							
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
									
						}, 3000);
					});
*/


					//初回のデータを送る（コネクションの確立に時間がかかるので１秒待ち）
					checkReadyGame();
					
					/*
					setTimeout(function(){
						console.log( '===========!!send data start' );
						if ( activeBB ){
							_sconnect.send( _sendStr('Aabb',  'on') );
						}
						if ( activeBonus ){
							_sconnect.send( _sendStr('Aab',  'on') );
						}
						_sconnect.send( _sendStr('Acre', game.credit) );
						_sconnect.send( _sendStr('Apt',  game.playpoint) );
						_sconnect.send( _sendStr('Atc',  game.total_count) );
						_sconnect.send( _sendStr('Act',  game.count) );
						_sconnect.send( _sendStr('Abb',  game.bb_count) );
						_sconnect.send( _sendStr('Arb',  game.rb_count) );
						_sconnect.send( _sendStr('Acp',  game.conv_point) );
						_sconnect.send( _sendStr('Acc',  game.conv_credit) );
						_sconnect.send( _sendStr('Amc',  game.min_credit) );
						_sconnect.send( _sendStr('RDY',  game.member_no+'_'+game.play_dt) );
						console.log( '===========!!RDY send' );

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

					}, 3000);
					*/
					console.log( '===========!!peer connect End' );

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
			var confMessage   = ['メンテナンス中に切り替えますか？','稼働中に切り替えますか？'];
			var buttonMessage = ['メンテナンス中に切り替える','稼働中に切り替える'];
			var mno = 0;

			if ( $(this).hasClass('deactive') ){
				mno = 1;
			}
			
			if ( confirm(confMessage[mno]) ){
				//ステータスを送る
				if ( mno == 0 ){
					//メンテナンスモードへ移行
					toMaintenance();
				} else {
					setCameraStatus("start");
					$(this).text(buttonMessage[0]);
					$(this).removeClass('deactive');
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
		
		function checkReadyGame( tm=1000 ) {
			setTimeout(function(){
				if ( !languageMode ){
					console.log( 'client not standby ... 1sec wait' );
					checkReadyGame();
					return;
				}
				console.log( '===========!!send data start' );
				if ( activeBB ){
					_sconnect.send( _sendStr('Aabb',  'on') );
				}
				if ( activeBonus ){
					_sconnect.send( _sendStr('Aab',  'on') );
				}
				_sconnect.send( _sendStr('Acre', game.credit) );
				_sconnect.send( _sendStr('Apt',  game.playpoint) );
				_sconnect.send( _sendStr('Atc',  game.total_count) );
				_sconnect.send( _sendStr('Act',  game.count) );
				_sconnect.send( _sendStr('Abb',  game.bb_count) );
				_sconnect.send( _sendStr('Arb',  game.rb_count) );
				_sconnect.send( _sendStr('Acp',  game.conv_point) );
				_sconnect.send( _sendStr('Acc',  game.conv_credit) );
				_sconnect.send( _sendStr('Amc',  game.min_credit) );
				_sconnect.send( _sendStr('RDY',  game.member_no+'_'+game.play_dt) );
				console.log( '===========!!RDY send' );

				//他の接続を切る
				console.log( peer.connections );
				var peers = Object.keys(peer.connections);
				for (var i = 0, ii = peers.length; i < ii; i++) {
					//console.log( peers[i] );
					//console.log( peer.connections[peers[i]][0].peer );
					if ( peer.connections[peers[i]][0].peer != _sconnect.peer ){
						//ログインしたものと違うキー peer.connections[peers[i]][0].peer
						if ( peer.connections[peers[i]][0].open == true ){
							//まだopenしているものがある場合
							peer._cleanupPeer( peer.connections[peers[i]][0].peer );
							console.log( 'other connect close', peers[i] );
						}
					}
				}

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
					if ( activeFlg ) return;
					console.log( "===========!!auto pay" );
//								if ( !activeFlg && noPayFlg ){
						execPay();
//								}
				}, autoPayTime * 60000 );
			}
		}
		
		function toMaintenance(){
			//接続中の場合
			if ( activeFlg ){
				//強制的に精算処理
				pay()
				.then(function(){
					//現在の接続を解除
					connectCloseMethod();
					//ステータスを変更
					setCameraStatus("end");
					$(this).text(buttonMessage[1]);
					$(this).addClass('deactive');
				});
			} else {
				//ステータスを変更
				setCameraStatus("end");
				$(this).text(buttonMessage[1]);
				$(this).addClass('deactive');
			}
		}
	}
	
	//再起動時などで既にボーナス状態なのに感知できていない場合用の強制ボーナスフラグ
	$('#bb_on').bind('click', function(){
		if ( $(this).prop('checked') ){
			activeBonus = true;
			activeBB = true;
		} else {
			activeBonus = false;
			activeBB = false;
		}
		showGame();
	});
	$('#rb_on').bind('click', function(){
		if ( $(this).prop('checked') ){
			activeBonus = true;
		} else {
			activeBonus = false;
		}
		showGame();
	});
