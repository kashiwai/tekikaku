/**
 * @fileOverview
 * スロット用関数JS
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
 *            2020/06/10 ver 1.4.0-s1-c1 村上俊行 デバッグモードフラグ追加 MAX+STARTボタンの時間調整電文 Ampを追加
 *            2020/07/10 ver 1.4.3-s1-c2 村上俊行 デバッグモード以外でのイベントを登録させない
 *            2023/09/01 ver 1.4.3-s1-c3 村上俊行 PayPal決済機能追加
 * @using
 * @desc
 *
 */
;
	var noConsole = true;							//consoleを消す
	var blurFlg = false;							//フォーカス離脱フラグ
	var waitBonusAnimeFlg = false;					//bonusアニメーション遅延フラグ
	var logMessageAry = [];
	var saveLogFunc = null;
	var settleDummyURL = "";
	var messageShowTime = 40;						//管理者メッセージを表示させておく秒数
													//cssの.marquee-container > .messageの
													// animation-duration * animation-iteration-countの値
	var countDownLimitDate = null;					//プレイ可能制限日時
	//2020-06-03 追加
	var endPlayFlg = false;							//プレイ終了フラグ

	var saveHeight = 0;								//videoの高さを記録

	var isIOS = /iP(hone|(o|a)d)/.test(navigator.userAgent);

	history.pushState(null, null, null);
	$(window).on('popstate', function (event) {
		history.pushState(null, null, null);
		$('#popstate-modal').modal();
		//errorAlert('ページを離れる場合は「精算」ボタンを押して下さい。');
		//alert('ページを離れる場合は「精算」ボタンを押して下さい。');
		return;
	});


	//close時にpeerを閉じる
	$(window).on('beforeunload', function(){
		if (! peer.destroyed) {
			peer.destroy();
		}
	});


	function dummySettle() {
		if ( settleDummyURL == "" ) return;
		var dt = new Date();
		var tmsp = dt.getTime();
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url: settleDummyURL,
				type:'GET'
			})
			.done( (data) => {
				settleDummyURL = "";
				errorAlert('決済完了送信', 'Debug Mode');
				resolve(data);
			})
			.fail( (data) => {
				reject(data);
			});
		});
	}



	//デバイス設定
	var getDevice = function(){
		var ua = navigator.userAgent;
		if(ua.indexOf('iPhone') > 0 || ua.indexOf('iPod') > 0 || ua.indexOf('Android') > 0 && ua.indexOf('Mobile') > 0){
			return 'sp';
		}else if(ua.indexOf('iPad') > 0 || ua.indexOf('Android') > 0){
			return 'tab';
		}else{
			return 'other';
		}
	}
	var _touch = "mousedown";
	var _touchend = "mouseup";
	var _click = "click";
	if( getDevice() !== "other"){
		$('#audiostart').css('display', 'inline-block');
		_touch = "touchstart";
		_click = "touchstart";
		_touchend = "touchend";
	}

	//デバッグモードへconsole.logを追加させる
	if ( getDevice() == 'other' ){
		saveLogFunc = console.log;
		console.log = phone_log;
	} else {
		console.log = phone_log;
	}
	
	//ドラック禁止のおまじない
	document.ondragstart = function(){return false;};
	document.onselectstart = function(){return false;};

	//2020-07-17 デバッグモードのみとする
	if ( noConsole ){
		//javascriptエラーが発生した場合にデバッグモードへ転送
		window.onerror = function(msg, url, line, col, error) {
			console.log(msg); // エラーの内容
			$('#consolelog').show();
		};
	}

	//リサイズ
	$(window).resize(function(){
		setVideoWidth();
	});

	//windowフォーカスが外れた時
	$(window).on('blur', function(){
		blurFlg = true;
		waitBonusAnimeFlg = true;
		writeLog( '======= blur =======' );
	});
	//windowフォーカスが設定された時
	$(window).on('focus', function(){
		blurFlg = false;
		writeLog( '======= focus =======' );
		if ( waitBonusAnimeFlg ){
			if ( activeBonus ){
				if( activeBB ){
					$('#bb_count').bonusAnime(true);
					$('#bonus_count').bonusAnime(true);
					waitBonusAnimeFlg = false;
				} else {
					$('#rb_count').bonusAnime(true);
					$('#bonus_count').bonusAnime(true);
					waitBonusAnimeFlg = false;
				}
			}
		}
		
		//creditがある（play途中の場合）
		if ( !endPlayFlg ){
			//再接続時にdataconnectionを確認
			try {
				console.log( 'destroyed:'+peer.destroyed );
				console.log( 'disconnected:'+peer.destroyed );
				//document.getElementById('video').load(); //reload できた？
				//if ( peer.destroyed || peer.disconnected || isIOS ){
				if ( peer.destroyed || peer.disconnected ){
					//接続が切れているのでリロードする
					ShowConnectError('connect lost');
				} else {
					//2020-06-11 iOS用の再接続のルーチンを追加
					if ( isIOS ){
						//videoのみ再ロード（1秒空けないとloadメソッドが効かない）
						setTimeout(function(){
							document.getElementById('video').load();
						},1000);
						//5秒後にconnectを再チェック
						//setTimeout(function(){
						//	if ( peer.destroyed || peer.disconnected ){
						//		ShowConnectError('connect lost');
						//	}
						//},5000);
						//3秒後にconnectとloadが失敗している可能性があるのでvideoチェック
						setTimeout(function(){
							if ( peer.destroyed || peer.disconnected ){
								ShowConnectError('connect lost');
							} else {
								//document.getElementById('video').load();
								console.log( 'height check' );
								console.log( saveHeight );
								console.log( parseInt($('.playing-screen').height()) );
								if ( saveHeight != parseInt($('.playing-screen').height()) ){
									ShowConnectError('not video');
								}
							}
						},3000);
					}
				}
			} catch(e) {
				//エラーが発生する場合もリロード
				ShowConnectError('connect lost');
			}
		}
	});
	$('#btn_reload').one('click', function(){
		location.reload();
	});
	$('#btn_reload2').one('click', function(){
		location.reload();
	});

	function ShowConnectError(message){
		$('#conn_error_message').text(message);
		$('#connectlost').show();
		$('.loader').hide();
		$('#loading_connect').hide();
		$('#loadinglost').hide();
		$('#loading')
			.css('height', window.innerHeight + 20 )
			.show();
	}

	function numberFormat(num){
		return String(num).replace( /(\d)(?=(\d\d\d)+(?!\d))/g, '$1,');
	}
	//console.logの代替
	function phone_log(){
		if ( !noConsole ){
			var message = '';
			for (var i = 0; i < arguments.length; i++) {
				if ( typeof(arguments[i]) == 'object' ){
					message += JSON.stringify(arguments[i])+' ';
				} else {
					message += arguments[i]+' ';
				}
			}
			if ( saveLogFunc != null ){
				//saveLogFunc(Array.prototype.join.call(arguments));
				//saveLogFunc(message);
			}
			logMessageAry.push(message);
			if ( logMessageAry.length > 200 ){
				logMessageAry.shift();
			}

			$('#consolelog')
				.html(logMessageAry.join('<br>'))
				.animate({scrollTop: $('#consolelog')[0].scrollHeight}, 20);
		}
	}

	//cssサポート（video widthにplaying-screenの幅に合わせる
	function setVideoWidth(){
		videoWidth = parseInt($('.playing-screen').width());

		setTimeout(function(){
			saveHeight = parseInt($('.playing-screen').height());
		},1000);
		$('#video').width(videoWidth);

		if ( !noConsole ){
			$('#consolelog').height($('#video').height());
		}

		// play_embed モード対応: #creditbox が存在しない場合はスキップ
		var creditbox = $('#creditbox');
		if (creditbox.length > 0 && creditbox.offset()) {
			startTop  = creditbox.offset().top;
			startLeft = creditbox.offset().left;
			var w = creditbox.width();
			var h = creditbox.height();
			var buttonwidth = $('#convcr-button').width()
					+parseInt($('#convcr-button').css('padding-left'))+parseInt($('#convcr-button').css('padding-right'));
			w -= buttonwidth;
			$('#animeField')
				.offset({top: startTop, left: startLeft})
				.css( 'width', w+'px' )
				.css( 'height', h+'px' )
			;
			var creditElem = $('#credit').get(0);
			if (creditElem) {
				startTop  = creditElem.offsetTop;
				startLeft = creditElem.offsetLeft;
				$('#animeNumber')
					.css('top', startTop+'px')
					.css('left', startLeft+'px')
					.css('font-size', $('#credit').css('font-size') )
				;
			}
		}

		//レイアウト設定
		if (typeof layoutOption !== 'undefined' && layoutOption['hide']) {
			layoutOption['hide'].forEach( function(name, idx){
				//$('#'+name).hide();
				$('[id^='+name+']').hide();
			});
		}
	}

	//html内メッセージ入れ替え
	function setConvText() {
		$('#conv_point').html( numberFormat(game.conv_point) );
		$('#conv_credit').html( game.conv_credit );
	}

	//決済画面表示
	function requestSettle(data){
		var ary = data.split('|');
		var param = [];
		
		ary.forEach( function(itm) {
			var kv = itm.split('=');
			param[kv[0]] = '';
			for(var i=1;i<kv.length;i++){
				param[kv[0]] += kv[i]
			}
		});
		
		console.log( param )
		
		if ( param['payment'] == 'drawpoint' ){
			return;
		}

		if (param['_type'] == '40'){
			$('#settleform').hide();
			$('#settleform_paypal').show();
			$('#settleform_paypal').attr( 'href', param['accessurl'].replace('token', 'token=') );
		} else {
			$('#settleform').show();
			$('#settleform_paypal').hide();
		}

		Object.keys(param).forEach( function(k,idx) {
			if ( k == '_url' ){
				$('#settleform').attr( 'action', param['_url']  );
			} else if ( k == 'data' ){
				$('input[name='+k+']').val( param[k] + '='.repeat(param[k].length % 4) );
			} else {
				$('input[name='+k+']').val( param[k] );
			}
		});


		/*
		if ( param['_url'] ){
			$('#settleform').attr( 'action', param['_url']  );
		}
		if ( param['payment'] == 'p99' ){
			$('input[name=data]').val( param['data'] );
		}
		if ( param['payment'] == 'lavy' ){
			$('input[name=IP_CODE]').val( param[3] );
			$('input[name=cookie]').val( param[4] );
			$('input[name=price]').val( param[5] );
			$('input[name=sendid]').val( param[6] );
			$('input[name=payment_code]').val( param[7] );
			$('input[name=email]').val( param[8] );

			//settleDummyURL = param[8];
		}
		*/
		
		$('#settle-modal').modal();

		$('#send_settle').one('click',function(){
			console.log( 'one click send_settle' );
			$('#settleform')
				.submit()
				.find('input').val("")				//内容を消す
			;
			$('#settle-modal').modal('hide');
		});

		$('#settleform_paypal').one('click',function(){
			$('#settle-modal').modal('hide');
		});
	}

	//精算用html作成
	function buildPayLink() {
		var pType = '';
		$('#buypt-modal .modal-body').append(
			'<div class="accordion" id="purchase_accordion" role="tablist" aria-multiselectable="false"></div>'
		);
		var target = $('#purchase_accordion');
		$.map( purchase, function(rec,idx){
			var message = numberFormat(rec.amount) + rec.amountType + ' ⇒ ' + numberFormat(rec.point) + rec.pointUnit;
			if (pType !== rec.purchaseType) {
				var card = $('<div>', {
					class: 'card'
				})
				.append( $('<div>', {
							class: 'card-header',
							role:  'tab',
							id:    'purchase_head_'+rec.purchase_type
						})
						.append(
							$('<a />', {
								class: 'collapsed text-body d-block p-3 m-n3',
								'data-toggle': 'collapse',
								'href'       : '#purchase_'+rec.purchase_type,
								'role'       : 'button',
								'aria-expanded': 'false',
								'aria-controls': 'purchase_'+rec.purchase_type,
								text: rec.purchaseType
							})
						)
				);
				pType = rec.purchaseType;
				var bd = 
					$('<div>',{
						id: 'purchase_'+rec.purchase_type,
						class: 'collapse purchase',
						role:  'tabpanel',
						'aria-labelledby': 'purchase_head_'+rec.purchase_type,
						'data-parent':     '#purchase_accordion'
					});
					
				bd.append('<div>',{
						id: 'purchase_body_'+rec.purchase_type,
						class: 'card-body'
					});
				card.append(bd);
				target.append(card);
			}
			var tag = $('<a>',{
				class:  'buyButton btn btn-block btn-primary',
				'data-dismiss': 'modal',
				html:    message,
				target: ''+rec.purchase_type+'-'+rec.amount
			});
			$('#purchase_'+rec.purchase_type).append(tag);
		});

		$('#buypt-modal').on('hidden.bs.modal', function () {
			$('.purchase').removeClass('show');
		})
		/* 2020-07-30 決済をアコーディオン式にするので削除
		var tags = $.map( purchase, function(rec,idx){
			var message = numberFormat(rec.amount) + rec.amountType + ' ⇒ ' + numberFormat(rec.point) + rec.pointUnit;
			var tag = $('<a>',{
				class:  'buyButton btn btn-block btn-primary',
				'data-dismiss': 'modal',
				html:    message,
				target: ''+rec.purchase_type+'-'+rec.amount
			});

			if (pType !== rec.purchaseType) {
				
				var title = $('<h6 />', {
					class: 'mt-4',
					text: rec.purchaseType
				});
				pType = rec.purchaseType;
				return title.add(tag);
			} else {
				return tag;
			}

			var tag = $('<a>',{
				class:  'buyButton btn btn-block btn-primary',
				'data-dismiss': 'modal',
				html:    message,
				target: ''+rec.purchase_type+'-'+rec.amount
			});

			if (pType !== rec.purchaseType) {
				var title = $('<h6 />', {
					class: 'mt-4',
					text: rec.purchaseType
				});
				pType = rec.purchaseType;
				return title.add(tag);
			} else {
				return tag;
			}
		});
		$('#buypt-modal .modal-body').append( tags );
		*/
	}

	//sessionAPI
	function sessionAPI() {
		var dt = new Date();
		var tmsp = dt.getTime();
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url:'../api_public/sessionAPI.php?ts='+tmsp,
				type:'GET'
			})
			.done( (data) => {
				resolve(data);
			})
			.fail( (data) => {
				reject(data);
			});
		});
	}

	//電文作成
	function _sendStr( btn, msg){
		var _r = "";
		_r += "tag:" + btn;
		_r += ",msg:" + msg;
		return( _r+" ");
	}

	//creditアニメーション
	$.fn.animetionNumber = function( add,  num=null ) {
		var oldnum = parseInt( $(this).attr('nextnumber') );
		if ( num == null ){
			num = oldnum + add;
		}
		$(this).attr('nextnumber', num );
		if ( oldnum < num ){
			animeNumber_add( num );
		} else if ( oldnum > num ){
			animeNumber_dec( num );
		} else {
			$(this).text( num );
		}
		return( $(this) );
	}


	//creditアニメーション(加算）
	function animeNumber_add(toNumber) {
		if ( !document.hasFocus() ) return;
		$('#credit').text(toNumber);

		startTop  = $('#credit').get( 0 ).offsetTop;
		startLeft = $('#credit').get( 0 ).offsetLeft;

		$('#animeNumber')
			.css('top', startTop+'px')
			.css('left', startLeft+'px')
			.velocity({scale: 1},{
				duration:0,
				complete:function(){
					$('#animeNumber')
						.text(toNumber)
						.css('opacity', 1);
					$('#credit').css('opacity', 0);
				}
			})
			.velocity({scale: 5},{duration:40})
			.velocity({scale: 1},{
				duration: 60,
				complete:function(){
					$('#animeNumber')
						.css('opacity', 0);
					$('#credit').css('opacity', 1);
				}
			})
		;
	}

	//creditアニメーション(減算）
	function animeNumber_dec(toNumber) {
		if ( !document.hasFocus() ) return;
		$('#credit').text(toNumber);
		startTop  = $('#credit').get( 0 ).offsetTop;
		startLeft = $('#credit').get( 0 ).offsetLeft;
		$('#animeNumber')
			.css('top', startTop+'px')
			.css('left', startLeft+'px')
			.velocity({scale: 1,top:startTop - 20},{
				duration:0,
				complete:function(){
					$('#animeNumber')
						.css('opacity', 1)
						.text(toNumber);
					$('#credit').css('opacity', 0);
				}
			})
			.velocity({top: startTop },{
				easing: [1000, 30],
				duration: 200,
				complete:function(){
					$('#animeNumber')
						.css('opacity', 0)
					$('#credit').css('opacity', 1);
				}
			})
		;

	}

	//bonusアニメーション
	$.fn.bonusAnime = function( flg = false ){
		if ( flg ){
			if ( $(this).attr('basecolor') != "" ){
			} else {
				$(this).attr('basecolor', $(this).css('color') );
			}
			if ( !document.hasFocus() ) {
				waitBonusAnimeFlg = true;
				return;
			}
			$(this)
				.velocity("stop")
				.css('color', $(this).attr('basecolor') )
				.velocity({color: $(this).attr('basecolor')},{duration:0})
				.velocity({color: '#24282d'},{duration:300, loop:true});
		} else {
			$(this)
				.velocity("stop")
				.css('color', $(this).attr('basecolor') )
				.attr('basecolor', '');
			waitBonusAnimeFlg = false;
		}
	}

	function showMessage( messages, limit = true ){
		var tag = $('<p>',{
						class: 'message',
						html: messages
		});
		$('.marquee-container')
			.addClass('on-message')
			.append( tag )
		;
		if ( limit ){
			setTimeout(function(){
				hideMessage();
			},messageShowTime * 1000);
		}
	}
	
	function hideMessage(){
		$('.marquee-container')
			.empty()
			.removeClass('on-message')
		;
	}

	function coundDownTimer(){
		if ( countDownLimitDate == null ) return;
	
		var d = 0;
		var h = 0;
		var m = 0;
		var s = 0;
		var a_day = 24 * 60 * 60 * 1000;
		var startDateTime = new Date();
		var left = countDownLimitDate - startDateTime;
		var left = countDownLimitDate - startDateTime;
		if ( left > 0 ){
			d = Math.floor(left / a_day);
			h = Math.floor((left % a_day) / (60 * 60 * 1000));
			m = Math.floor((left % a_day) / (60 * 1000)) % 60;
			s = Math.floor((left % a_day) / 1000) % 60 % 60;
		}
		if (left < 60000) {
			$('.countdown-container').addClass('warning');
		}
		$('#countdown').text( ('0'+h).substr(-2)+':'+('0'+m).substr(-2)+':'+('0'+s).substr(-2) );
		$('.countdown-container').show();
		setTimeout('coundDownTimer()', 1000);
	}



	//audio設定
	function setAudio(){
		var audio = document.querySelector('audio');
		var video = document.getElementById('video');
		if( $('#audiostart').hasClass('playing')){
			$('#audiostart,#audiostart_auto')
				.removeClass('sound-on')
				.addClass('sound-off')
				.find('i')
					.removeClass('fa-volume-up')
					.addClass('fa-volume-mute');
			$('#audiostart,#audiostart_auto').removeClass('playing')
			if (audio) audio.pause();
			// video要素もミュート
			if (video) video.muted = true;
		} else {
			$('#audiostart,#audiostart_auto')
				.removeClass('sound-off')
				.addClass('sound-on')
				.find('i')
					.removeClass('fa-volume-mute')
					.addClass('fa-volume-up');
			$('#audiostart,#audiostart_auto').addClass('playing')
			// audio要素で再生を試みる
			if (audio) {
				audio.play()
				.then(function(){
					console.log('🔊 Audio element playing');
				},function(e){
					console.log('🔇 Audio play failed, trying video unmute:', e);
					// audio失敗時はvideo要素をunmute
					if (video) {
						video.muted = false;
						console.log('🔊 Video unmuted as fallback');
					}
				});
			}
			// video要素も同時にunmute（フォールバック）
			if (video) {
				video.muted = false;
			}
		}
	}


	function leaveOnlyNumber(e){
		// 数字以外の不要な文字を削除
		var st = String.fromCharCode(e.which);
		if ("0123456789".indexOf(st,0) < 0) { return false; }
		return true;
	}

	//エラーメッセージ表示
	function errorAlert( msg, titleMessage='' ) {
		if ( titleMessage == '' ){
			if ( languageMode == 'ja' ){
				titleMessage = '警告';
			} else {
				titleMessage = 'Warning';
			}
		}
		$('#error-modal_title').text( titleMessage );
		$('#error-modal_message').text( msg );
		$('#error-modal')
			.css('z-index', 6000)
			.modal();
	}

	//コンソールログ出力
	function writeLog( mes, level=20 ) {
		console.log( mes );
	}

	function checkPoint() {
		var dt = new Date();
		var tmsp = dt.getTime();
		return new Promise(function(resolve, reject) {
			//API呼び出し
			$.ajax({
				url:'./checkpoint.php?no='+machineno+'&in_credit='+inCreditCount+'&ts='+tmsp,
				type:'GET'
			})
			.done( (data) => {
				resolve(data);
			})
			.fail( (data) => {
				reject({status: 'fail'});
			});
		});
	}

