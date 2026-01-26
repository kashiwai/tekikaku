	var blurFlg = false;							//フォーカス離脱フラグ
	var waitBonusAnimeFlg = false;					//bonusアニメーション遅延フラグ
	var logMessageAry = [];
	var saveLogFunc = null;
	var settleDummyURL = "";
	var messageShowTime = 40;						//管理者メッセージを表示させておく秒数
													//cssの.marquee-container > .messageの
													// animation-duration * animation-iteration-countの値

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
		//$('#audiostart').show();
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

	//javascriptエラーが発生した場合にデバッグモードへ転送
	window.onerror = function(msg, url, line, col, error) {
		console.log(msg); // エラーの内容
		$('#consolelog').show();
	};


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
					waitBonusAnimeFlg = false;
				} else {
					$('#rb_count').bonusAnime(true);
					waitBonusAnimeFlg = false;
				}
			}
		}
	});

	function numberFormat(num){
		return String(num).replace( /(\d)(?=(\d\d\d)+(?!\d))/g, '$1,');
	}
	//console.logの代替
	function phone_log(){
		var message = '';
		for (var i = 0; i < arguments.length; i++) {
			if ( typeof(arguments[i]) == 'object' ){
				message += JSON.stringify(arguments[i])+' ';
			} else {
				message += arguments[i]+' ';
			}
		}
		if ( saveLogFunc != null ){
		}
		logMessageAry.push(message);
		if ( logMessageAry.length > 200 ){
			logMessageAry.shift();
		}

		$('#consolelog')
			.html(logMessageAry.join('<br>'))
			.animate({scrollTop: $('#consolelog')[0].scrollHeight}, 20);
	}

	//cssサポート（video widthにplaying-screenの幅に合わせる
	function setVideoWidth(){
		videoWidth = parseInt($('.playing-screen').width());
		$('#video').width(videoWidth);

		$('#consolelog').height($('#video').height());

		startTop  = $('#creditbox').offset().top;
		startLeft = $('#creditbox').offset().left;
		var w = $('#creditbox').width();
		var h = $('#creditbox').height();
		var buttonwidth = $('#convcr-button').width()
				+parseInt($('#convcr-button').css('padding-left'))+parseInt($('#convcr-button').css('padding-right'));
		w -= buttonwidth;
		$('#animeField')
			.offset({top: startTop, left: startLeft})
			.css( 'width', w+'px' )
			.css( 'height', h+'px' )
		;
		startTop  = $('#credit').get( 0 ).offsetTop;
		startLeft = $('#credit').get( 0 ).offsetLeft;
		$('#animeNumber')
			.css('top', startTop+'px')
			.css('left', startLeft+'px')
			.css('font-size', $('#credit').css('font-size') )
		;

		//レイアウト設定
		layoutOption['hide'].forEach( function(name, idx){
			//$('#'+name).hide();
			$('[id^='+name+']').hide();
		});
	}

	//html内メッセージ入れ替え
	function setConvText() {
		$('#conv_point').html( numberFormat(game.conv_point) );
		$('#conv_credit').html( game.conv_credit );
	}

	//決済画面表示
	function requestSettle(data){
		var param = data.split('|');
		console.log( param )
		if ( param[0] == "drawpoint" ){
			return;
		}

		$('input[name=IP_CODE]').val( param[0] );
		$('input[name=cookie]').val( param[1] );
		$('input[name=price]').val( param[2] );
		$('input[name=sendid]').val( param[3] );
		$('input[name=payment_code]').val( param[4] );
		$('input[name=email]').val( param[5] );

		settleDummyURL = param[6];

		$('#settle-modal').modal();

		$('#send_settle').one('click',function(){
			console.log( 'one click send_settle' );
			$('#settleform')
				.submit()
				.find('input').val("")				//内容を消す
			;
			$('#settle-modal').modal('hide');
		});

	}

	//精算用html作成
	function buildPayLink() {
		var pType = "";
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
		});
		$('#buypt-modal .modal-body').append( tags );
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

	//audio設定
	function setAudio(){
		var audio = document.querySelector('audio');
		if( $('#audiostart').hasClass('playing')){
			$('#audiostart,#audiostart_auto')
				.removeClass('sound-on')
				.addClass('sound-off')
				.find('i')
					.removeClass('fa-volume-up')
					.addClass('fa-volume-mute');
			$('#audiostart,#audiostart_auto').removeClass('playing')
			audio.pause();
		} else {
			$('#audiostart,#audiostart_auto')
				.removeClass('sound-off')
				.addClass('sound-on')
				.find('i')
					.removeClass('fa-volume-mute')
					.addClass('fa-volume-up');
			$('#audiostart,#audiostart_auto').addClass('playing')
			audio.play()
			.then(function(){},function(){});
		}
	}


	function leaveOnlyNumber(e){
		// 数字以外の不要な文字を削除
		var st = String.fromCharCode(e.which);
		if ("0123456789".indexOf(st,0) < 0) { return false; }
		return true;
	}

	//エラーメッセージ表示
	function errorAlert( msg, titleMessage='警告' ) {
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
