/**
 * @fileOverview
 * システム共通
 *
 * (C)SmartRams Corp. 2016 All Rights Reserved．
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
 * @author    片岡 充
 * @language  JavaScript
 * @version   1.0
 * @since     2019/10/23 ver1.0 片岡充 初版作成
 * @using
 * @desc
 * @info      サイドバー保持、制御
 */


// 画面ロード時処理
;(function($){
	//toggle function
	var _toggleNav = function(){
		var _nav = $.cookie("navToggle");
		if( _nav == 1){
			_nav = 0;//小さい表示
		}else{
			_nav = 1;//大きい表示
		}
		$.cookie("navToggle", _nav);
	}
	
	//change event
	$('#sidebarToggle').on('click', _toggleNav);
	
	//ready
	$(document).ready(function(){
		if( $.cookie("navToggle") === undefined ){
			if( $('#page-top').hasClass('sidebar-toggled')){
				$.cookie("navToggle", 0);
			}else{
				$.cookie("navToggle", 1);
			}
		}
		var _nav = $.cookie("navToggle");
		if( _nav == 1){
			$('#page-top').addClass('sidebar-toggled');
			$('.sidebar.navbar-nav').addClass('toggled');
		}else{
			$('#page-top').removeClass('sidebar-toggled');
			$('.sidebar.navbar-nav').removeClass('toggled');
		}
	});
})(jQuery);

