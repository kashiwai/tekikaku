/* 
 * flatpicker 日本語化
 * 
 * @package
 * @version
 * 
 * @since	2016/06/23 ver1.0 初版作成
 */

var toJPN = {
	weekdays : ['日', '月', '火', '水', '木', '金', '土'],
	months: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'
	]
};

// '曜日'を日本語化
flatpickr.init.prototype.l10n.weekdays.shorthand = toJPN.weekdays;

// '月'を日本語化
flatpickr.init.prototype.l10n.months.longhand = toJPN.months;

$(function(){
	// flatpickrを起動させるinputのID *classでも可
	flatpickr('.flatpickr', { dateFormat: 'Y/m/d'});
});