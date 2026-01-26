$(function(){
	// オートプレイ切替
	// cssクラスの「d-flex」をあてた状態だと非表示にできないので先に処理してから表示切替をする
	$('input[name="ctrl-autoplay"]:radio').change( function() {
		if ($(this).val() == 1) {
			// ON
			// オートプレイ時はパネルは「プレイ」
			$('#panel-play').addClass('d-flex');
			$('#panel-play').show();
			$('#panel-menu').hide();

			$('#play-manual').removeClass('d-flex');
			$('#play-manual').hide();
			$('#play-auto').addClass('d-flex');
			$('#play-auto').show();

			// パネル切替不可
			$('input[name="ctrl-panel"]:radio').prop('disabled', true);
			$('#ctrl-panel-play').addClass('disabled');
			$('#ctrl-panel-menu').addClass('disabled');
			// メニューパネルがactiveの場合は解除しておく
			$('#ctrl-panel-menu').removeClass('active');
		} else {
			// OFF
			// パネル切替許可
			$('input[name="ctrl-panel"]:radio').prop('disabled', false);
			$('#ctrl-panel-play').removeClass('disabled');
			$('#ctrl-panel-menu').removeClass('disabled');
			$('input[name="ctrl-panel"]').val(['1']);
			$('#ctrl-panel-play').addClass('active');
			// パネル切替に従って表示
			changePanel($('input[name="ctrl-panel"]:checked').val());

			$('#play-manual').addClass('d-flex');
			$('#play-manual').show();
			$('#play-auto').removeClass('d-flex');
			$('#play-auto').hide();
		}
	});

	// パネル切替
	$('input[name="ctrl-panel"]:radio').change( function() {
		changePanel($(this).val());
	});

	// スライドメニュー
	$('.btn-slidemenu-reel').click(function() {
		$(this).toggleClass('open');
		$('.slidemenu-bg').fadeToggle();
		$('.slide-reel').toggleClass('open');
	});
	$('.btn-slidemenu-set').click(function() {
		$(this).toggleClass('open');
		$('.slidemenu-bg').fadeToggle();
		$('.slide-setting').toggleClass('open');
	});
	$('.slidemenu-bg').click(function() {
		$(this).fadeOut();
		$('.btn-slidemenu-reel').removeClass('open');
		$('.btn-slidemenu-set').removeClass('open');
		$('.slide-reel').removeClass('open');
		$('.slide-setting').removeClass('open');
	});

});

function changePanel(val) {
	if (val == 1) {
		$('#panel-play').addClass('d-flex');
		$('#panel-play').show();
		$('#panel-menu').hide();
	} else {
		$('#panel-play').removeClass('d-flex');
		$('#panel-play').hide();
		$('#panel-menu').show();
	}
}
