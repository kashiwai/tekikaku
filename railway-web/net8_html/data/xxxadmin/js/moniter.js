
var keysocket;

$(document).ready(function(){
	
	// セッション保持用ajax
	setInterval(function(){
		$.ajax({
			url:'./api_public/sessionAPI_admin.php',
			type:'GET'
		}).done( (data) => {
			//console.log(data);
		});
	},7200000);
	
	$(document).click(function(event) {
		//全部消す
		$('.send_select').hide();
	});
	
	//操作ボタンクリック
	$('.send_select>li').on('click', function(e){
		e.stopPropagation();
		//送信する
		var _target = $(this).parent().data().machine;
		var _data   = $(this).data();
		message = JSON.stringify( {"sendmachine": _target.toString(), "message": _emg_msg[_data.code]} );
		console.log( "send", message);
		keysocket.send( message );
		$('.send_select').hide();
	});
	
	//押したら開く
	$('.send_button>.button_label').on('click', function(e){
		e.stopPropagation();
		if( $(this).next().is(':visible')) {
			$('.send_select').hide();
		}else{
			//全部消す
			$('.send_select').hide();
			//表示
			$(this).next().show();
		}
	});
	
	//表示切替
	$('.machine_filter input').on('change', function(){
		var _input = $(this);
		var _target = $(this).val();
		_filter = _input.prop('checked');
		if( _filter ){
			$(this).parent().removeClass('btn-info');
			$(this).parent().addClass('btn-primary');
			//使用中のみ
			if( _target == "active"){
				$('.monitering_item').each( function(){
					var _tag = $(this);
					if( _tag.find('.machine_moniter').hasClass('machine_moniter_deactive')){
						_tag.hide();
					}
				});
			}
		}else{
			$(this).parent().addClass('btn-info');
			$(this).parent().removeClass('btn-primary');
			//全部
			$('.monitering_item').show();
		}
	});
	
	
	socketMain();
});

/*
//状況確認ボタン
$('#send').on('click', function(){
	var message = '@cnf_';
	
	message = JSON.stringify( { member_no: 6, credit: 20 } );

	console.log( message );
	keysocket.send( message );
});
*/

var _testcode = '{"sendmachine":"2", "message": "メッセージ内容"}';


const isNumber = function(value) {
	return ((typeof value === 'number') && (isFinite(value)));
};

//基本更新情報
var _tags = ["mode","bonus","credit", "pycommand","game","game_t","rbcount","bbcount"];
var _timer;
var _filter = false;

function socketMain() {
	//pythonとのsocket通信
	//keysocket = new WebSocket('ws://smpp.suteki-f.com:50190');
	keysocket = new WebSocket('wss://web.smart-gear.jp:59777/ws');
	keysocket.onopen    = onOpen;
	keysocket.onmessage = onMessage;
	keysocket.onclose   = onClose;
	keysocket.onerror   = onError;
	
	function onOpen(event) {
		console.log('接続しました。');
		changeConnectIcon(true);
		sendSocket( {command:"monitor"} );
	}
	function onMessage(event) {
		if (event && event.data) {
			json = JSON.parse( event.data );
			console.log( json );
			var base = $('#machine_'+json['machine_no']);
			
			if( json["member_no"] == 0 || typeof json["member_no"] === "undefined"){
				if( !base.hasClass("machine_moniter_deactive")){
					//非稼働状態に
					base.addClass("machine_moniter_deactive");
					if( _filter){
						base.parents('.monitering_item').hide();
					}
					
					//非稼働で削除する項目
					base.find('.member_no').html("");
					base.find('.mode').html("");
					base.find('.bonus').html("");
					base.find('.credit').html("");
					base.find('.increase').html("");
					for(var i=1;i<4;i++){
						base.find('.reel'+i).removeClass("red");
						base.find('.reel'+i).removeClass("blue");
					}
				}
				//非稼働でも更新する項目
				if( typeof json["pycommand"] !== "undefined" ){
					base.find('.pycommand').html(json["pycommand"]);
				}
			}else{
				if( base.hasClass("machine_moniter_deactive")){
					//稼働状態に
					base.removeClass("machine_moniter_deactive");
					base.parents('.monitering_item').show();
				}
				//状況更新
				$.map( _tags, function( keys){
					if( "pycommand" == keys){
						if( typeof json["pycommand"] === "undefined" ){
							base.find('.'+keys).html("");
						}
					}
					if( typeof json[keys] === "undefined" ){
						//base.find('.'+keys).html("");
					}else{
						if( isNumber(json[keys])){
							json[keys] = json[keys].toLocaleString();
						}
						base.find('.'+keys).html(json[keys]);
					}
				});
				//member_no
				if( typeof json["member_no"] !== "undefined" ){
					base.find('.member_no').html( '<a href="member.php?S_MEMBER_NO='+ json["member_no"] +'">' + json["member_no"] + '</a>');
				}
				//increase
				if( typeof json["signal"] !== "undefined" ){
					if( typeof json["credit"] !== "undefined"){
						if( json["signal"].substr(0, 1) != ".") base.find('.increase').html(" ( - )");
						if( json["signal"].substr(1, 1) != ".") base.find('.increase').html(" ( + )");
						if( json["signal"].substr(0, 2) == "..") base.find('.increase').html("");
					}
					//bonus
					if( json["signal"].substr(2, 1) == "."){
						base.find('.status_reg').removeClass("active");
					}else{
						base.find('.status_bb').removeClass("active");
						base.find('.status_reg').addClass("active");
					}
					if( json["signal"].substr(3, 1) == "."){
						base.find('.status_bb').removeClass("active");
					}else{
						base.find('.status_reg').removeClass("active");
						base.find('.status_bb').addClass("active");
					}
				}
				//reel
				if( typeof json["reel"] !== "undefined" ){
					for(var i=1;i<4;i++){
						var _str = json["reel"].substr(i-1, 1);
						if( _str == "-"){
							base.find('.reel'+i).addClass("red");
							base.find('.reel'+i).removeClass("blue");
						}else if( _str == "+"){
							base.find('.reel'+i).removeClass("red");
							base.find('.reel'+i).addClass("blue");
						}else{
							base.find('.reel'+i).removeClass("red");
							base.find('.reel'+i).removeClass("blue");
						}
					}
				}
			}
		}
	}
	
	function onError(event) {
		console.log(event);
		changeConnectIcon(false);
		keysocket = null;
		clearTimeout( _timer);
		_timer = setTimeout( socketMain, 10000);
	}
	function onClose(event) {
		console.log('切断しました。');
		changeConnectIcon(false);
		keysocket = null;
		clearTimeout( _timer);
		_timer = setTimeout( socketMain, 10000);
	}
}


function closeConnection(){
	$('.monitering_item').each( function(){
		var base = $(this).find('.machine_moniter');
		if( !base.hasClass("machine_moniter_deactive")){
			//非稼働状態に
			base.addClass("machine_moniter_deactive");
			//非稼働で削除する項目
			base.find('.member_no').html("");
			base.find('.mode').html("");
			base.find('.bonus').html("");
			base.find('.credit').html("");
			base.find('.increase').html("");
			for(var i=1;i<4;i++){
				base.find('.reel'+i).removeClass("red");
				base.find('.reel'+i).removeClass("blue");
			}
		}
	});
}


function sendSocket( json ){
	message = JSON.stringify( json );
	console.log( message );
	keysocket.send( message );
}

function changeConnectIcon( _connect){
	if( _connect){
		$('.connect_status').addClass('active');
		$('.connect_status>i').removeClass('fa-comment-slash');
		$('.connect_status>i').addClass('fa-comment');
	}else{
		closeConnection();
		$('.connect_status').removeClass('active');
		$('.connect_status>i').addClass('fa-comment-slash');
		$('.connect_status>i').removeClass('fa-comment');
	}
}


