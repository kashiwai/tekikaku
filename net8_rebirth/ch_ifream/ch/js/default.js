//modal
$(function() {
	$('a[rel*=leanModal]').leanModal({
		overlay : 0.5, 
		closeButton: ".modal_close",
		parentdiv: "#modaldiv"
	});
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
