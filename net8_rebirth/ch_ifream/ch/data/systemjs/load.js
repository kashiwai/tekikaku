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
 * @author    鶴野 美香
 * @language  JavaScript
 * @version   1.0
 * @since     2016/10/13 ver1.0 鶴野美香 初版作成
 * @using
 * @desc
 * @info      ロード時処理
 */


// 画面ロード時処理
$(function() {
	//--- TOPへ戻るボタン
	var topBtn = $('#page-top');
	topBtn.hide();
	//スクロールが100に達したらボタン表示
	$(window).scroll(function () {
		if ($(this).scrollTop() > 100) {
			topBtn.fadeIn();
		} else {
			topBtn.fadeOut();
		}
	});
	//フッター手前でボタンを止める
	$(window).scroll(function () {
		var height = $(document).height(); //ドキュメントの高さ 
		var position = $(window).height() + $(window).scrollTop(); //ページトップから現在地までの高さ
		var footer = $("footer").height(); //フッターの高さ
		if ( height - position  < footer ) { 
			topBtn.css({
				position : "absolute",
				top : 10
			});
		} else { 
			topBtn.css({
				position : "fixed",
				top: "auto"
			});
		}
	});
	//スクロールしてトップ
	topBtn.click(function () {
		$('body,html').animate({
			scrollTop: 0
		}, 500);
		return false;
	});

	//--- アコーディオン開閉アイコン
	$('#collapseOne, #collapseTwo, #collapseThree').on('show.bs.collapse', function() {		// 折り畳み開く処理
		$('a[href="#' + this.id + '"]').find('i.icon-chevron-circle-down').removeClass('icon-chevron-circle-down').addClass('icon-chevron-circle-up');
	})
	.on('hide.bs.collapse', function() {													// 折り畳み閉じる処理
		$('a[href="#' + this.id + '"]').find('i.icon-chevron-circle-up').removeClass('icon-chevron-circle-up').addClass('icon-chevron-circle-down');
	});

	//--- ツールチップ
	$('[data-toggle="tooltip"]').tooltip();
	
	//--- トップ画面タブ
	if ($('.flow').length) {
		var $win = $(window),
			$flow = $('.flow'),
			navPos = $flow.offset().top,
			fixedClass = 'flow-fixed';

		$win.on('load scroll', function() {
			var value = $(this).scrollTop();
			if ( value > navPos ) {
				$flow.addClass(fixedClass);
			} else {
				$flow.removeClass(fixedClass);
			}
		});
	}
});