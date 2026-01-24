var _sconnect;

	var activeBonus = false;					//ボーナス中かどうかの判定
	var activeBB = false;						//BBのボーナス判定
	var activeFluctuation = false;				//確変判定
	var countNavel = 0;							//へそ投入回数
	var navalReady = true;						//へそボタン押下可能フラグ
	var startTop;
	var startLeft;
	var startWidth;
	var autoMode = false;
	var autoCredit = false;
	var aliveInterval = null;
	var lastTimestamp = (new Date()).getTime();
	var leaveTime = 5 * 60000;					//離籍警告時間
	var usePoint = 0;
	var targetUsePoint = 0;
	var bonusCountMark = '-';

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
		'activeBonus' : false,
	};
	
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
		debug: 3
	};
	console.log( peersetting );
	var peer = new Peer( peersetting );
	
	
	function _sendStr( btn, msg){
		var _r = "";
		_r += "tag:" + btn;
		_r += ",msg:" + msg;
		return( _r+" ");
	}

	var dataConnection;
	peer.on('open', function(){
		//id
		$('#my-id').text(peer.id);
		
		//データチャンネル
		dataConnection = peer.connect(cameraid,{
			'metadata': memberno+':'+authID
		});
		dataConnection.maxRetransmits = 1;

		dataConnection.on('close', function(){
			writeLog( 'connect lost' );
		});

		_sconnect = dataConnection;

		//購入ボタン生成
		buildPayLink();
		
		//一定時間操作がない場合は終了させるイベント設定及びセッションの維持
		aliveInterval = setInterval(function(){
			var span = (new Date()).getTime() - lastTimestamp;
			console.log( "3min not action:"+span );
			if ( span >= leaveTime ){
				_sconnect.send(_sendStr( 'pay', 'timeout'));
			}

			sessionAPI()
			.then(function(data){
				
			},function(data){
				writeLog( 'sessionAPI error' );
			});
		}, leaveTime );
		
		//データチャンネルで送信
		$('.sendBtn').each(function(){
			$(this).bind(_touch,function(){
				var id = $(this).attr('id');
				
	 			if ( game.credit < 15 && id == 'sendBtnpv' ){
					errorAlert( errorMessages['U5051'] );
	 				return;
	 			}
				
				dataConnection.send(_sendStr( 'b'+$(this).attr('id').split('sendBtn')[1], "click"));
			});
		});
		
		$(document).keydown(function(e) {
			var id = '';
			switch(e.keyCode){
				case 90 :
					$('#animeNumber').animetionNumber( 1 );
					return;
					break;

				case 88 :
					$('#animeNumber').animetionNumber( -1 );
					return;
					break;

				case 51 :
		 			if ( game.credit < game.min_credit ){
						errorAlert( errorMessages['U5051'] );
		 				return;
		 			}
					id = 'pv';
					break;
				case 49 :
					id = 'pc';
					break;
				case 50 :
					id = 'pf';
					break;
				case 52 :
					id = 'pm';
					break;

				case 87 :
					id = 'pou';
					break;
				case 65 :
					id = 'pol';
					break;
				case 68 :
					id = 'por';
					break;
				case 83 :
					id = 'pod';
					break;
				default:
					return;
			}

			dataConnection.send(_sendStr( 'b'+id, "click"));
		});

		//デバッグモード設定
		$('.situation-gc').bind(_click, function(){
			$('#consolelog').toggle();
			$('#consolelog').animate({scrollTop: $('#consolelog')[0].scrollHeight}, 20);
		});
		$('.situation-bb').bind(_click, function(){
			console.log( 'dummy settle' );
			dummySettle();
		});
		
		//プレイポイント購入ボタン処理
		$('.buyButton').bind(_click, function(){
			var data = $(this).attr('target');
			console.log( 'click buy('+data+')' );
			dataConnection.send(_sendStr( 'cpb', data));
		});

		//クレジット変換ボタン処理
		$('#convCredit').bind(_click, function(){
			console.log( 'click convCredit' );
			dataConnection.send(_sendStr( 'ccc', ''));
		});

		//精算ボタン処理
		$('#pay').bind(_click, function(){
			//操作禁止処理を入れる
			console.log( 'click pay' );
			dataConnection.send(_sendStr( 'pay', ''));
		});
		
		
		//精算結果のモーダルが閉じた時の処理
		$('#end-modal').on('hide.bs.modal', function () {
			location.href = '/gameafter.php';
		});
		
		//データチャンネルハンドリング
		dataConnection.on('data', function(data){
			//放置時間計測用
			lastTimestamp = (new Date()).getTime();
			
			var addCredit = 0;
			
			console.log( 'recieve' , data);
			var _darry = data.trim().split(',');
			var _tag = _darry[0].split(':')[1];
			var _msg = _darry[1].split(':')[1];

			if( _tag == 'Signal_0' ){

			} else if ( _tag == 'Signal_0_End' ){
				if ( !activeFluctuation ){
					game.count++;
					game.total_count++;
					$('#count').text(game.count);
					$('#total_count').text(game.total_count);
					countNavel--;
					if ( countNavel < 0 ) countNavel = 0;
					$('#countNavel').text(countNavel);
				} else {
					if ( !activeBonus ){
						game.count++;
						game.total_count++;
						$('#count').text(game.count);
						$('#total_count').text(game.total_count);
					}
				}
				//自動モードならへそ＋
				if ( autoMode ){
					addNavel();
				}
			} else if ( _tag == 'Signal_1' ){
				console.log( '================時短開始================' );
				activeFluctuation = true;
				$('#activeKakuhen').show();
			} else if ( _tag == 'Signal_1_End' ){
				activeFluctuation = false;
				execNavelFlg = false;				//へそ再度開始させるため
				//autoplayが設定されていればへそ再開
				if ( $('#autoplay').hasClass('autoplay-on') || $('#autoplay_credit').hasClass('autoplay-on') ){
					addNavel();
				}
				console.log( '----------------時短終了----------------' );
				$('#activeKakuhen').hide();
			} else if ( _tag == 'Signal_2' ){
				console.log( '================大当り開始================' );
				activeBonus = true;
				game.bb_count++;
				$('#bb_count').text(game.bb_count);
				$('#ActiveBonus').show();
				$('#count').text(bonusCountMark);
				$('#bb_count').bonusAnime(true);
			} else if ( _tag == 'Signal_2_End' ){
				activeBonus = false;
				game.count = 0;
				console.log( '----------------大当り終了=----------------' );
				$('#ActiveBonus').hide();
				$('#count').text(game.count);
				$('#bb_count').bonusAnime(false);
			} else if ( _tag == 'Signal_3' ){
				countNavel++;
				if ( countNavel > maxNavel ){
					countNavel = maxNavel + 1;
				}
				addCredit = (outNavel - Math.abs(oneGameCredit) );
				game.credit += addCredit;
				if ( game.credit < 0 ){
					addCredit = (Math.abs(oneGameCredit) + game.credit);
					game.in_credit += addCredit;
					game.credit = 0;
					$('#animeNumber').animetionNumber( addCredit*-1 );
				} else {
					game.in_credit += Math.abs(oneGameCredit);
					game.out_credit += outNavel;
					$('#animeNumber').animetionNumber( addCredit );
				}
				$('#credit').text(game.credit);
				$('#countNavel').text(countNavel);
			} else if ( _tag == 'Signal_4' ){
				if ( activeFluctuation ){
					addCredit = outTulip;
				} else {
					addCredit = (outTulip - Math.abs(oneGameCredit));
					game.in_credit  += Math.abs(oneGameCredit);
				}
				game.out_credit += outTulip;
				game.credit += addCredit;
				$('#animeNumber').animetionNumber( addCredit );
				$('#credit').text(game.credit);
			} else if ( _tag == 'Signal_6' ){
				addCredit = outAttacker1;
				game.credit += addCredit;
				game.out_credit += addCredit;
				$('#animeNumber').animetionNumber( addCredit );
				$('#credit').text(game.credit);
			} else if ( _tag == 'Signal_7' ){
				addCredit = outAttacker2;
				game.credit += addCredit;
				game.out_credit += addCredit;
				$('#animeNumber').animetionNumber( addCredit );
				$('#credit').text(game.credit);

			//ここからデータ関連
			//ボーナスフラグ
			} else if ( _tag == 'Aab' ){
				activeBonus = true;
			//確変フラグ
			} else if ( _tag == 'Aaf' ){
				activeFluctuation = true;
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
						console.log( 'auto use point:' + usePoint );
						//使ったポイントを減算してテキスト表示
						$('#maxpoint').val( targetUsePoint - usePoint );
					}
				}
				$('#playpoint').text(game.playpoint);
			//総ゲーム数
			} else if ( _tag == 'Atc' ){
				game.total_count = parseInt(_msg)
				$('#total_count').text(game.total_count);
			//ボーナス間ゲーム数
			} else if ( _tag == 'Act' ){
				game.count = parseInt(_msg)
				$('#count').text(game.count);
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
			//へそ残数
			} else if ( _tag == 'Ach' ){
				countNavel = parseInt(_msg)
				$('#countNavel').text( countNavel );
			//ゲーム準備完了
			} else if ( _tag == 'RDY' ){
				setConvText();
				$('#machine_no').text(machineno);
				$('nav').show();
				$('#loading').hide();
			//終了予告
			} else if ( _tag == 'Dnt' ){
				$('#time-modal').modal()
			//決済画面に移動
			} else if ( _tag == 'cpd' ){
				requestSettle(_msg)
			//抽選ポイント決済の場合
			} else if ( _tag == 'cpp' ){
				requestSettle(_msg)
			//決済準備エラー
			} else if ( _tag == 'cpe' ){
				errorAlert( errorMessages['U5060'] );
			//決済エラー
			} else if ( _tag == 'cpf' ){
				errorAlert( errorMessages['U5061'] );
			//プレイポイント
			} else if ( _tag == 'Cpt' ){
				var span = game.playpoint;
				game.playpoint = parseInt(_msg);
				$('#playpoint').text(game.playpoint);
				errorAlert( errorMessages['U5063'], errorMessages['U5064'] );
			//クレジット変換ステータス
			} else if ( _tag == 'Cst' ){
				game.ccc_status = _msg;
				if( game.ccc_status == "ng" ){
					//クレジット変換エラー
					errorAlert( errorMessages['U5062'] );
				}
			//精算（プレイポイント）
			} else if ( _tag == 'Ppp' ){
				$('#pay_play_point').text( parseInt(_msg) );
			//精算（クレジット）
			} else if ( _tag == 'Pcr' ){
				$('#pay_credit').text( parseInt(_msg) );
			//精算（抽選ポイント）
			} else if ( _tag == 'Pdr' ){
				$('#pay_draw_point').text( parseInt(_msg) );
			//精算（トータル抽選ポイント）
			} else if ( _tag == 'Ptd' ){
				$('#pay_total_draw_point').text( parseInt(_msg) );
			//ゲーム終了
			} else if ( _tag == 'EXT' ){
				//終了
				dataConnection.close();
				//精算結果モーダル表示
				$('#end-modal').modal()
			}
		});
		
	});

	peer.on('call', function(call) {
		// カメラ側からStreamが送られてきた場合に呼ばれます
		// 閲覧側のカメラは利用しないので、何も指定しないでanswerをします
		call.answer();
		// カメラからのStreamをvideoタグに追加します
		call.on('stream', function(stream) {
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
			
			setVideoWidth();
			
			
			$('.machine-no').bind(_click, function(){
				stream.getVideoTracks().forEach((track) => {
				    track.enabled = false;
				});
				setTimeout(function(){
					stream.getVideoTracks().forEach((track) => {
					    track.enabled = true;
					});
				},3000);
			});
			
			
			
			//即時で切り替えるとvideoの表示がスムースにいかないのでDelayさせる
			setTimeout(function(){
				$('#video').show();
				$('.img-fluid').hide();

				setVideoWidth();

				if ( getDevice() == "other" ){
					setAudio();
				}

			},500);
		});
	});
	
	$('#audiostart,#audiostart_auto').click(function(){
		setAudio();

	});

	$('#autoplay').click(function(){
		if( $(this).hasClass('autoplay-off') ) {
			autoPlay(true);
		} else {
			autoPlay(false);
		}
	});

	$('#autoplay_credit').click(function(){
		if( $(this).hasClass('autoplay-off') ) {
			targetUsePoint = parseInt($('#maxpoint').val());
			if ( !targetUsePoint ) targetUsePoint = 0;
			//指定なし、クレジットなし
			if ( targetUsePoint == 0 && game.credit <= 0 ){
				errorAlert( errorMessages['U5051'] );
				return;
			}
			if ( targetUsePoint > game.playpoint ){
				errorAlert( errorMessages['U5053'] );
				return;
			} else {
				usePoint = 0;
				autoCredit = true;
				autoPlay(true);
			}
		} else {
			autoPlay(false);
			autoCredit = false;
		}
	});

	function autoPlay(mode){
		
		autoMode = mode;
		if( mode ) {
			$('#autoplay')
				.removeClass('autoplay-off')
				.addClass('autoplay-on')
				.text( $('#autoplay').attr('stoplabel') );
			$('#autoplay_credit')
				.removeClass('autoplay-off')
				.addClass('autoplay-on')
				.text( $('#autoplay_credit').attr('stoplabel') );
			$('#maxpoint').attr('readonly', true );

			execNavelFlg = false;
			addNavel();
		} else {
			$('#autoplay')
				.removeClass('autoplay-on')
				.addClass('autoplay-off')
				.text( $('#autoplay').attr('startlabel') );
			$('#autoplay_credit')
				.removeClass('autoplay-on')
				.addClass('autoplay-off')
				.text( $('#autoplay_credit').attr('startlabel') );
			$('#maxpoint').attr('readonly', false );
		}
	}
	
	var saveHeso = 0;
	var execNavelFlg = false;
	function addNavel(){
		if ( !autoMode ) return;
		if ( execNavelFlg ) return;
		execNavelFlg = true;

		//ランダムで一定の確率でへそ入賞の数を決める
		if ( countNavel == maxNavel ) {
			var rnd = Math.floor( Math.random() * (100 + 1 - 1) ) + 1
			console.log( rnd,maxNavelRate );
			if ( rnd > maxNavelRate ) {
				execNavelFlg = false;
				return;
			}
		}
		autoBet()
		.then(function(ret){
			if ( !ret ){
				autoPlay(false);
				return;
			}
			//クレジットが足りない場合は押さない
			if ( game.credit <= 0 ) {
				autoPlay(false);
				errorAlert( errorMessages['U5051'] );
				return;
			}
			//確変中は処理しない
			if ( activeFluctuation ) return;
			//既にMAXなら押さない
			if ( countNavel > maxNavel  ) return;
			console.log( 'へそ押すよ' );
			//現在のへそ数を保存
			saveHeso = countNavel;
			//コード送信
			_sconnect.send(_sendStr( 'bpv', 'auto') );
			setTimeout(function(){
				console.log( 'へそ確認' );
				//変動していなければ機器に受け付けられていないのでもう一度押す
				if (saveHeso == countNavel ){
					execNavelFlg = false;
					addNavel();
				} else {
					console.log( 'へそ押せた('+countNavel+'/'+maxNavel );
					execNavelFlg = false;
					//自動モードでmaxへそ数でない場合は続けて押す
					if ( autoMode && countNavel <= maxNavel ){
						addNavel();
					}
				}
			}, 2500);
		},function(){
			autoPlay(false);
			errorAlert( errorMessages['U5054'] );
			return;
		});
	}

	function autoBet(){
		var intid;
		return new Promise(function(resolve, reject) {
			if ( !autoCredit ) {
				resolve(true)
				return;
			}
			if ( game.credit <= 0 ){
				if ( targetUsePoint <= usePoint ){
					resolve(false);
					return;
				}
				console.log( '[autoBet] exec' );
				//クレジット変換を自動実行
				game.ccc_status = "";
				dataConnection.send(_sendStr( 'ccc', ''));
				intid = setInterval(function(){
					if ( game.ccc_status == "ok" ){
						console.log( '[autoBet] ok' );
						clearInterval( intid );
						resolve(true);
					} else if ( game.ccc_status == "ng" ){
						console.log( '[autoBet] ng' );
						clearInterval( intid );
						reject();
					}
				},50);
			} else {
				resolve(true);
			}
		});
	}
	

