//戻るボタン禁止
history.pushState(null, null, location.href);
$(window).on('popstate', function(){
history.go(1);
});


//ダブルタップ禁止
window.onload = function() {
  document.addEventListener("dblclick", function(e){ e.preventDefault();}, { passive: false });
};


//ポップアップ禁止
window.oncontextmenu = function(event) {
     event.preventDefault();
     event.stopPropagation();
     return false;
};

//ピンチイン・ピンチアウト禁止
  document.documentElement.addEventListener('touchstart', function (e) {
    if (e.touches.length >= 2) {e.preventDefault();}
  }, {passive: false});

//modal
$(function() {
	$('a[rel*=leanModal]').leanModal({
		overlay : 0.5, 
		closeButton: ".modal_close",
		parentdiv: "#modaldiv"
	});
}); 

//遊技台選択プルダウンリスト
var target = "";
function jump(){
	var url = document.form1.select.options[document.form1.select.selectedIndex].value;
	if(url != "" ){
		if(target == 'top'){
			top.location.href = url;
		}
		else if(target == 'blank'){
			window.open(url, 'window_name');
		}
		else if(target != ""){
			eval('parent.' + target + '.location.href = url');
		}
		else{
			location.href = url;
		}
	}
}