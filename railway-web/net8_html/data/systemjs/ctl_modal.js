/* 
/* ネットパチンコ用
 * sweetalert2でモーダルを開くときに、各データをDOM操作で埋める
 * 
 * @package 
 * @author  片岡 充
 * @vervion JavaScript / jQuery1.9+必須
 *          
 * @since   2019/02/19 ver1.0 初版作成 片岡 充
 * @info    
 */
$(function(){
	// onloadタイミングで基準hrefを保存
	$('.detail-modal .modal-content a').attr( '_href', $('.detail-modal .modal-content a').attr('href'));
	//
	function getParam(name, url) {if (!url) url = window.location.href;name = name.replace(/[\[\]]/g, "\\$&");var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),    results = regex.exec(url);if (!results) return null;if (!results[2]) return '';return decodeURIComponent(results[2].replace(/\+/g, " "));}
	//
	window._modalOpen = function(){
		
		//各パラメータを一覧側HTMLから取得
		//	とりあえず 番号（NO）、台の名前、世代、メーカー名のみ。
		//		モーダルでは表示するデータは、一覧側では非表示かアトリビュートに埋め込んでおく
		// 親要素の .card に特定クラスがあるかどうかで切り分ける
		var _modal      = $('.detail-modal .modal-content');
		var _parent     = $(this).parents('.card');
		var _datas      = _parent.find('.card-header');
		
		if( _parent.hasClass('goods_list')){
			// 商品系
			var _goods_name = _parent.find('.card-header a').html();
			var _timespan   = _parent.find('.card-subtitle').html();
			var _status     = _parent.find('.card-body>.prize-bg-pill').html();
			var _detail_img = _parent.find('.mainimage').attr('src');
			var _draw_point = _parent.find('.card-body').eq(1).find('.prize-bg-pill').eq(0).html();
			var _goods_info = _parent.find('.goods_info_text').html();
			var _win        = _parent.find('ul.list-group li').eq(0).find('span').eq(1).html();
			var _nowstatus  = _parent.find('ul.list-group li').eq(1).find('span').eq(1).html();
			var _goods_no   = _datas.data('no');
			var _goods_cd   = _datas.data('cd');
			var _draw_dt    = _datas.data('drawdt');
			var _recept_min = _datas.data('min');
			var _request    = _datas.data('req');
			var _btn_check  = _parent.find('.card-body .row .col-6').eq(0);
			var _btn_status = _parent.find('.card-body .row .col-6').eq(1);
			// modal
			_modal.find('.modal-title').html( _goods_name);
			_modal.find('.card-subtitle').html( _timespan);
			_modal.find('.modal-body>.prize-bg-pill').html( _status);
			_modal.find('.modal-body .mainimage').attr('src', _detail_img);
			_modal.find('.modal-body .mainimage').next().html( _draw_point);
			_modal.find('.modal-body>.row .alert-secondary>p').html( _goods_info);
			_modal.find('.modal-body>.row .alert-secondary>.text-right>small>span').html( _goods_cd);
			_modal.find('.modal-body>.row .col-lg-7 .prize-bg-pill').html( _draw_dt);
			_modal.find('.modal-body>.row .col-lg-7 .list-group li').eq(0).find('span').eq(1).html( _win);
			_modal.find('.modal-body>.row .col-lg-7 .list-group li').eq(1).find('span').eq(1).html( _recept_min);
			_modal.find('.modal-body>.row .col-lg-7 .list-group li').eq(2).find('span').eq(1).html( _request);
			_modal.find('.modal-body>.row .col-lg-7 .list-group li').eq(3).find('span').eq(1).html( _nowstatus);
			// btn
			_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(0).attr('href', _btn_check.find('a').attr('href'));
			_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(1).hide();
			_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(2).hide();
			_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(3).hide();
			if( _btn_status.hasClass('outofrange')){
				_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(1).show();
			}else if( _btn_status.hasClass('soldout')){
				_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(2).show();
			}else{
//				_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(3).attr('href', _btn_status.find('a').attr('href'));
				_modal.find('.modal-body>.row .col-lg-7>div.mt-2>a.btn').eq(3).show();
			}
			// apply
			var _page = getParam("P");
			if( _page != null){
				var _pageque = "&P=" + _page;
			}else{
				var _pageque = "";
			}
			var _odr = getParam("ODR");
			if( _odr != null){
				var _odrque = "&ODR=" + _odr;
			}else{
				var _odrque = "";
			}
			var _view = getParam("VIEW");
			if( _view != null){
				var _viewque = "&VIEW=" + _view;
			}else{
				var _viewque = "";
			}
			var _type = getParam("TYPE");
			if( _type != null){
				var _typeque = "&TYPE=" + _type;
			}else{
				var _typeque = "";
			}
			var _apply_modal = $('.apply-modal .modal-content');
			_apply_modal.find('.apply_draw_point').html( _draw_point);
			_apply_modal.find('.apply_send_btn').attr('href', '?M=regist&NO='+_goods_no+'&T='+$one+_pageque+_odrque+_viewque+_typeque);
			
		}else{
			// 台情報系
			var _assing_flg = _datas.data('assign');
			var _play_status= _datas.data('play_status');
			var _tester= _datas.data('tester');
			var _machine_status= _datas.data('machine_status');
			var _no         = _datas.data('no');
			var _id         = _datas.data('id');
			var _category   = _datas.data('category');
			var _maker_name = _datas.data('maker_name');
			var _model_name = _parent.find('.card-header a').html();
			var _play2credit= _parent.find('ul.list-group li').eq(0).find('span').eq(1).html();
			var _credit2draw= _parent.find('ul.list-group li').eq(1).find('span').eq(1).html();
			var _unit_name  = _parent.find('ul.list-group li').eq(2).find('span').eq(0).html();
			var _categorys  = _parent.find('ul.list-group li').eq(2).find('span').eq(1).html();
			var _total_game = _parent.find('.game-info .cnt-total div').eq(1).html();
			var _game_count = _parent.find('.game-info .cnt-game div').eq(1).html();
			var _big_name   = _parent.find('.game-info .cnt-big div').eq(0).html();
			var _big_count  = _parent.find('.game-info .cnt-big div').eq(1).html();
			var _reg_name   = _parent.find('.game-info .cnt-reg div').eq(0).html();
			var _reg_count  = _parent.find('.game-info .cnt-reg div').eq(1).html();
			var _hbonus_count = _parent.find('.game-info .cnt-hbonus div').eq(1).html();
			var _detail_img = _parent.find('.img-fluid').attr('_src');
			
			//モーダル側にコピー
			_modal.find('a').attr('href', _modal.find('a').attr('_href') + _no);
			_modal.find('.modal-title').html( _model_name);
			_modal.find('.alert-primary ul.list-group li').eq(0).find('span').eq(1).html( _no);
			_modal.find('.alert-primary ul.list-group li').eq(1).find('span').eq(1).html( _id);
			_modal.find('.alert-primary ul.list-group li').eq(2).find('span').eq(1).html( _maker_name);
			_modal.find('.alert-primary ul.list-group li').eq(3).find('span').eq(0).html( _unit_name);
			_modal.find('.alert-primary ul.list-group li').eq(3).find('span').eq(1).html( _categorys);
			_modal.find('.medal-change').find('span').eq(1).html( _play2credit);
			_modal.find('.medal-rate').find('span').eq(1).html( _credit2draw);
			_modal.find('.modal-body .game-info .cnt-total div').eq(1).html( _total_game);
			_modal.find('.modal-body .game-info .cnt-game div').eq(1).html( _game_count);
			_modal.find('.modal-body .game-info .cnt-big small span').eq(0).html( _big_name);
			_modal.find('.modal-body .game-info .cnt-big span').eq(1).html( _big_count);
			_modal.find('.modal-body .game-info .cnt-reg small span').eq(0).html( _reg_name);
			_modal.find('.modal-body .game-info .cnt-reg span').eq(1).html( _reg_count);
			_modal.find('.modal-body .game-info .cnt-hbonus div').eq(1).html( _hbonus_count);
			_modal.find('.modal-body .img-fluid').attr('src', _detail_img);
			
			//グラフ
			var _hit = false;
			var _graph = _modal.find('.modal-body .game-graph-inner ul').empty();
			if( _parent.find('.hitdata').eq(0).html() != ""){
				var _json  = JSON.parse( _parent.find('.hitdata').eq(0).html());
				var _now = new Date();
				var _h = _now.getHours();
				
				if( _h < 9 ){
					//日付をまたいでいるので前日の12時にする
					var _chkdate = new Date( _now.getFullYear(), _now.getMonth(), _now.getDate() - 1, parseInt( _open_time.split(':')[0], 10), parseInt( _open_time.split(':')[1], 10), 0);
				}else{
					//またいでいないので同日12時以前の判定
					var _chkdate = new Date( _now.getFullYear(), _now.getMonth(), _now.getDate(), parseInt( _open_time.split(':')[0], 10), parseInt( _open_time.split(':')[1], 10), 0);
				}
				
				var _mcnt = 0;
				
				// 現在のゲーム数
				var _now_append = "";
				var _now_append = $('<li class="now_separate"></li>');//.html( _mcnt);
				if( _game_count > 0){
					var _hc = (Math.floor( _game_count / 100)>6)? 6:Math.floor( _game_count / 100)+1;
					if( _hc > 6) _hc = 6;
					_now_append.addClass('p'+_hc);
				}else{
					_now_append.addClass('p0');
				}
				_now_append.addClass("now");
				_graph.append( _now_append);
				
				var _dateHit = false;
				var _backDate = _now_append;
				_json.forEach(function( value, index ) {
				//_json.reverse().slice(-9).forEach(function( value, index ) {
					if( index > 8) return;
					var _d = new Date( value.DATE);
					if( _d > _chkdate && !_dateHit){
						var _append = $('<li class="date_separate"></li>').html( index+1);
						_backDate.removeClass('date_separate');
						_backDate = _append;
					}else{
						_dateHit = true;
						var _append = $('<li></li>').html( index+1);
					}
					
					
					if( value.COUNT > 0){
						var _hits = (Math.floor( value.COUNT / 100)>6)? 6:Math.floor( value.COUNT / 100)+1;
						if( _hits > 6) _hits = 6;
						_append.addClass('p'+_hits);
					}else{
						_append.addClass('p0');
					}
					if( value.TYPE != "") _append.addClass(value.TYPE);
					_graph.append( _append);
					_mcnt++;
				});
			}
			
			if( _play_status == 2){		// メンテ中
				_modal.find('p.closed').hide();
				_modal.find('p.maintenance').show();
				_modal.find('a.btn').hide();
				_modal.find('p.notavailable').hide();
			} else {
				if (_tester == 1) {	//-- テスター
						if( _assing_flg == 1) {		// 使用中
							_modal.find('a.btn').hide();
							_modal.find('p.notavailable').show();
						} else {
							_modal.find('a.btn').hide();	// 一旦リンクを全て隠す
							if (_machine_status == 0) {	// 準備中
								_modal.find('a.preparation').show();
							} else {
								if( _play_status == 1){	// 時間外
									_modal.find('a.closed').show();
								} else {
									if (_machine_status == 1) {		// 通常
										_modal.find('a.play').show();
									} else {	// メンテナンス
										_modal.find('a.maintenance').show();
									}
								}
							}
							_modal.find('p.notavailable').hide();
						}
						_modal.find('p.closed').hide();
						_modal.find('p.maintenance').hide();
				} else {			//-- 一般
					if( _play_status == 0){		// 通常
						if( _assing_flg == 0){
							_modal.find('a.btn').show();
							_modal.find('p.notavailable').hide();
						}else{
							_modal.find('a.btn').hide();
							_modal.find('p.notavailable').show();
						}
						_modal.find('.closed').hide();
						_modal.find('.maintenance').hide();
					} else {	// 時間外
						_modal.find('p.closed').show();
						_modal.find('p.maintenance').hide();
						_modal.find('a.btn').hide();
						_modal.find('p.notavailable').hide();
					}
				}
			}
		}
	};
	
	
	// イベント追加
	$('.card-header a, .card-body a, a.modal_a').on('click', _modalOpen);
	
});
