$(function(){
	var gamechat = {
		url        : '{%URL%}home/',
		authToken  : '{%TOKEN%}',
		nickname   : '{%NICKNAME%}',
		channels   : {%CHANNELS%},
		open       : false,
		openpage   : '-----',
		texts      : {
			start  : '',
			home   : 'Home',
			select : 'Home',
			close  : 'Close',
			dm     : ''
		},
		unread_max : 99
	};

	//デバイス設定
	var getDevice = function(){
		var ua = navigator.userAgent;
		if(ua.indexOf('iPhone') > 0 || ua.indexOf('iPod') > 0 || ua.indexOf('Android') > 0 && ua.indexOf('Mobile') > 0){
			return 'sp';
		}else if(ua.indexOf('iPad') > 0 || ua.indexOf('Android') > 0){
			//return 'other';
			return 'tab';
		}else{
			return 'other';
		}
	}
	var _touch = "mousedown";
	var _touchend = "mouseup";
	var _click = "click";
	var _device = getDevice();
	if( _device !== "other"){
		//$('#audiostart').css('display', 'inline-block');
		_touch = "touchstart";
		_click = "touchstart";
		_touchend = "touchend";
	}
	var _startY = 0;
	var _swipe = false;

	//起動時
	$(document).ready(function(){
		addCss();
		addButtonDom();
		
		console.log( $('nav').innerHeight() );
		console.log( $('.sub-navbar').innerHeight() );
		console.log( window.innerHeight );

		addWindowDom();
		authenticateIFrame();

	});

	//chatwindowのクリックを透過させない
	$(document).on(_touch, '#chatwindow', function(e){
		e.stopPropagation();
	});

	//
	$(document).on(_touchend, '#leaveroom', function(e){
		e.preventDefault();
		console.log( 'leave push' );
		$('#rcChannel')[0].contentWindow.postMessage({
			externalCommand: 'go',
			path: 'home?layout=embedded'
		}, '*');
	});

	//開始フローティングアイコンクリック時動作
	$(document).on(_touchend, '#startchat', function(e){
		e.preventDefault();
		startChat();
	});
	
	//チャット終了ボタンクリック
	$(document).on(_touchend, '#closechat', function(e){
		e.stopPropagation();
		$('#chatwindow').removeClass('showSlideIn');
		$('#chatwindow').addClass('showSlideOut');
		gamechat.open = false;
		
		$('.btn-slidemenu-reel').show();
		$('.btn-slidemenu-set').show();
		$('.btn-slidemenu-note').show();
		
		$('#startchat').show();
		$('body').removeClass('bodyfixed');
	});

	//チャンネルボタン
	$(document).on('change', '#channels', function(e){
		var name = $(this).val();
		changeChannelIFrame(name)
	});
	
	//チャンネル移動
	$(document).on(_touch, '.select_channel', function(e){
		//offset取得
		if (_device != 'other'){
			_swipe = false;
			_startY = e.touches[0].pageY;
		}
	});
	$(document).on('touchmove', '.select_channel', function(e){
		//offset取得
		if (_device != 'other'){
			if ( Math.abs(_startY - e.touches[0].pageY) > 32 ) _swipe = true;
		}
	});
	$(document).on(_touchend, '.select_channel', function(e){
		//offset比較
		if (_device != 'other'){
			//swipe判定
			if ( _swipe ) return
		}
		
		var name = $(this).data('value');
		setTimeout(function(){
			$('#chatloading').show();
			changeChannelIFrame(name)
		},200);
	});

	//chatイベント取得
	window.addEventListener('message', function(e) {
		var eventname = e.data.eventName;
		var eventdata = e.data.data;
		console.log(e.data.eventName); // event name
		console.log(e.data.data); // event data
		//チャンネル情報の更新
		updateChannel(eventname, eventdata);
		if ( eventname == 'room-opened' ){
			$('#chatloading').hide();
			$('#rcChannel').show();
			gamechat.openpage = e.data.data['name'];
		}
		//if ( e.data.eventName == 'startup' ) {
		if ( e.data.data == 'online' || eventname == 'unread-changed-by-subscription' ) {
			$('#rcChannel').show();
			$('#chatloading').hide();
		}
	});

	//チャット開始処理
	function startChat(){
		if ( gamechat.open ){
			$('#chatwindow').hide();
			gamechat.open = false;
		} else {
			$('#startchat').hide();
			if ( $('#chatwindow').length ){
				$('#chatwindow').removeClass('showSlideOut');
				$('#chatwindow').show();
				$('#chatwindow').addClass('showSlideIn');
			} else {
				addWindowDom();
				$('#chatwindow').show();
				$('#chatwindow').removeClass('showSlideOut');
				$('#chatwindow').addClass('showSlideIn');
				authenticateIFrame();
				resizeWindow();
			}
			gamechat.open = true;
			if( getDevice() !== "other") $('body').addClass('bodyfixed');
			$('.btn-slidemenu-reel').hide();
			$('.btn-slidemenu-set').hide();
			$('.btn-slidemenu-note').hide();
		}
	}

	function updateChannel(eventname, eventdata){
		var chTag;
		var cid = '';
		var selecter;
		//既に閲覧済みのchannelに発言があった場合
		if ( eventname == 'new-message'){
			if ( gamechat.openpage == eventdata['name'] ){
				console.log( '同じページなので処理しない' );
				return;
			}
			if ( eventdata.room['type'] == 'd' ){
				cid = eventdata['rid'];
			} else {
				cid = eventdata['name'];
			}
			
			//console.log( 'cid', cid );
			selecter = 'a[data-value='+cid+']';
			chTag = $(selecter);
			
			if ( gamechat.channels[cid] ){
				//既に設定がある
				gamechat.channels[cid]['unread'] += 1;
			} else {
				//設定がない
				return;
			}
			
			var unread = chTag.find('.channel-unread');
			var urc = gamechat.channels[cid]['unread'];
			unread.text( urc );
			chTag.find('.channel-unread').removeClass('hidedata')

			//全体件数の設定
			allUnread();
		}
		/* notificationは処理しなくてよい
		if ( eventname == 'notification' ){
			console.log( eventdata['notification']['payload'] );
			if ( gamechat.openpage == eventdata['notification']['payload']['name'] ){
				console.log( '同じページなので処理しない' );
				return;
			}
			selecter = 'a[data-value='+eventdata['notification']['payload']['name']+']';
			chTag = $(selecter);
			var unread = chTag.find('.channel-unread');
			var urc = parseInt(unread.text()) + 1;
			unread.text( urc );
			chTag.find('.channel-unread').removeClass('hidedata')
		}
		*/
		//ダイレクトメッセージやその他のchannel情報変更時
		if ( eventname == 'unread-changed-by-subscription'){
			if ( eventdata['t'] == 'd' ){
				cid = eventdata['rid'];
			} else {
				cid = eventdata['name'];
			}
			selecter = 'a[data-value='+cid+']';
			//uneradを設定
			//ここでidxがない場合は新規だがnameが自分と同じ場合は追加しない
			//console.log( 'cid', cid );
			if ( gamechat.channels[cid] ){
				//既に設定がある
				gamechat.channels[cid]['unread'] = eventdata.unread;
				//チャンネルにアラート通知があって
				if ( eventdata['alert'] && eventdata.unread == 0 ) gamechat.channels[cid]['unread'] = 1;
			} else {
				//設定がない
				console.log( 'no channel', eventdata['fname'] );
				console.log( eventdata['alert'] );
				if ( eventdata['alert'] ){
					addChannelObj( eventdata['fname'], cid, eventdata['t'], eventdata['usersCount'], eventdata['unread']);
					console.log( 'addChannelObj' );
				} else {
					return;
				}
			}
			console.log( 'change room ' + selecter );
			chTag = $(selecter);
			if ( eventdata['t'] == 'd' ){
				chTag.find('.channel-usercount').text('')
			} else {
				chTag.find('.channel-usercount').text('('+eventdata.usersCount+')')
			}
			if ( gamechat.channels[cid]['unread'] > 0 ){
				chTag.find('.channel-unread').text(gamechat.channels[cid]['unread'])
				chTag.find('.channel-unread').removeClass('hidedata')
			} else {
				chTag.find('.channel-unread').text('0');
				chTag.find('.channel-unread').addClass('hidedata')
			}
			
			//全体件数の設定
			allUnread();
		}
	}

	function allUnread(){
		var countUnread = 0;
		Object.keys(gamechat.channels).forEach( function( idx ){
			countUnread += gamechat.channels[idx].unread;
		});
		
		//表示最大件数を超えたら固定
		countUnread = (countUnread > gamechat.unread_max)? gamechat.unread_max : countUnread;

		$('.chat-unread').text(countUnread);
		$('.chat-unread-button').text(countUnread);
		if ( countUnread > 0 ){
			$('.chat-unread').removeClass('hidedata')
			$('.chat-unread-button').removeClass('hidedata')
		} else {
			$('.chat-unread').addClass('hidedata')
			$('.chat-unread-button').addClass('hidedata')
		}
	}

	function setUnread(name, count){
		if ( name == '' ) return;
		var selecter = 'a[data-value='+name+']';
		var chTag = $(selecter);
		var urc = (count > gamechat.unread_max)? gamechat.unread_max : count;
		chTag.find('.channel-unread').text(urc);
		if ( count > 0 ){
			chTag.find('.channel-unread').removeClass('hidedata')
		} else {
			chTag.find('.channel-unread').addClass('hidedata')
		}
		if ( count == 0 ){
			gamechat.channels[name].unread = 0;
		}
	}

	function resizeWindow(){
		if( getDevice() !== "other"){
			var wh = window.innerHeight;
			var ww = window.innerWidth;
			if ( ww > 384 ) return;
			//var nh = $('nav').innerHeight() + $('.sub-navbar').innerHeight();
			var nh = $('nav').innerHeight();

			var h = wh - nh;
			var w = ww - 20;
			var ch = h - $('#chatheader').innerHeight() - 20;

			$('#chatwindow').css('width',  ww+'px');
			$('#chatwindow').css('height', h+'px');
		
			$('#rcChannel').css('width',  w+'px');
			$('#rcChannel').css('height', ch+'px');
		}
	}


	function addChannelObj(fname, name, type, usercount, unread ){
		var opt = {
			fname     : fname,
			name      : name,
			type      : type,
			usercount : usercount,
			unread    : unread
		};
		gamechat.channels[name] = opt;
		//console.log( opt );
		//console.log( gamechat.channels );
		if ( type == 'd' ) fname = gamechat.texts.dm+fname;
		var tag = addChannelBar(fname, name, usercount, type);
		$('#channel_menu').append( tag );
		
		return opt;
	}

	function addCss(){
		var tm = new Date();
		var link_style = $('<link>',{	
				rel:  'stylesheet',
				href: '/chat/css/chat.css?tm=' + tm
		});
		$('body').append( link_style );
	}

	function addButtonDom(){
		var buttonTag = $('<a>', {
			id: 'startchat',
			class: 'btn-pressable chat-start',
			text: gamechat.texts.start
		});
		
		buttonTag
			.append(
				$('<span>',{
					class: 'chat-unread-button notification-cnt bg-danger hidedata',
					text: '3'
				})
			)
		;
		
		$('body').append( buttonTag );
	}

	function addWindowDom(){
		if ( $('#chathtml').length > 0 ){
			$('#chathtml')
				.append(
					$('<iframe>', {
						id  : 'rcChannel',
						src : gamechat.url+'?resumeToken='+gamechat.authToken+'&layout=embedded',

					})
				);
			return;
		}
		var chattag = 
			$('<div>',{id : 'chatwindow', style: 'display: none;'})
				.append(
					$('<div>',{id : 'chatheader', class: 'modal-header'})
						.append(
							$('<a>', {
								id: 'channel_select',class:'btn btn-secondary', 'data-toggle':'dropdown',text: 'channel'
							})
							.append(
								$('<span>',{
									class: 'chat-unread notification-cnt bg-danger hidedata',
									text: '3'
								})
							)
					)
					.append( buildSelectChannel() )
					.append( $('<span>', { text: gamechat.nickname }) )
					.append(
						$('<a>', {
							id: 'closechat',
							class: 'btn btn-secondary',
							text: gamechat.texts.close
						})
					)
				)
				.append(
					$('<div>',{id : 'chatloading'})
					.append( $('<div>', { class: 'chatloader', text: 'Loading...' }) )
					.append(
						$('<p>',{id : 'chatloading_message', text: 'connect...' })
					)
				)
				.append(
					$('<iframe>', {
						id  : 'rcChannel',
//						src : gamechat.url+'?resumeToken='+gamechat.authToken,
						src : gamechat.url+'?resumeToken='+gamechat.authToken+'&layout=embedded',

					})
				)
			;
		//console.log( chattag );
		$('body').append( chattag );
	}

	function buildSelectChannel() {
		//var tag = $('<a>', { id: 'channel_select',class:'btn btn-light btn-sm dropdown-toggle', 'data-toggle':'dropdown' });
		var tag = $('<ul>',{class: 'dropdown-menu', id:'channel_menu'});
		tag.append(
			$('<li>',{
				class: 'dropdown-item px-2'
			})
			.append(
				$('<a>', {
					class: 'select_channel',
					'data-value': '',
					text: gamechat.texts.select
				})
			)
		);
		Object.keys(gamechat.channels).forEach( function(ky, idx) {
			var ch = gamechat.channels[ky];
			var chname = ch['fname'];
			var usercount = ' ('+ch['usersCount']+')';
			//if ( ch['type'] == 'c' ) chname +=' ('+ch['usersCount']+')';
			if ( ch['type'] == 'd' ){
				chname = gamechat.texts.dm+chname;
				usercount = '';
			}
			tag.append(
				addChannelBar( chname, ch['name'], usercount, ch['type'] )
			);
		});
	
		return tag;
	}

	function addChannelBar(chname, name, usercount, type){
		//var dmclass = ( type == 'd' )? 'fa-user' : 'fa-comments';
		var dmclass = ( type == 'd' )? 'fa-user' : 'fa-hashtag';
		var tag = $('<li>',{
			class: 'dropdown-item px-2'
		})
		.append(
			$('<a>', {
				class: 'select_channel',
				'data-value': name,
			})
			.append(
				$('<i>',{
					class: 'fas '+dmclass,
				})
			)
			.append(
				$('<span>',{
					class: 'channel-name',
					text: chname
				})
			)
			.append(
				$('<span>',{
					class: 'channel-usercount',
					text: usercount
				})
			)
			.append(
				$('<span>',{
					class: 'channel-unread notification-cnt bg-danger hidedata',
					text: ''
				})
			)
		);
	
		return tag;
	}

	function authenticateIFrame() {
		//document.getElementById('rcChannel').contentWindow.postMessage({
		//jqueryの場合は[0]にしないとcontentWindowがない
		$('#rcChannel')[0].contentWindow.postMessage({
			externalCommand: 'login-with-token',
			token: gamechat.authToken
		}, '*');
	}

	function changeChannelIFrame(name){
		//今開いているのと同じなら処理しない
		if ( gamechat.openpage == name ){
			$('#chatloading').hide();
			console.log( '同じぺーじ' );
			return;
		}
		var pathname = '/channel/'+name;
		if ( name == '' ) {
			pathname = 'home';
			$('#chatloading').hide();
			gamechat.openpage = '';
		} else {
			if ( gamechat.channels[name]['type'] == 'd' ){
				pathname = '/direct/'+name;
			}
		}
		$('#rcChannel')[0].contentWindow.postMessage({
			externalCommand: 'go',
			path: pathname
		}, '*');
		setUnread(name, 0);
	
		//全体件数の設定
		allUnread();
	}

	function removeToken(){
		$.ajax({
			url: 'https://web.smart-gear.jp/chat/chatRemoveToken.php',
			type:'get'
		})
		.done( (data) => {
			console.log( 'done' );
		})
		.fail( (data) => {
			console.log( 'fail' );
		});
	}

});
