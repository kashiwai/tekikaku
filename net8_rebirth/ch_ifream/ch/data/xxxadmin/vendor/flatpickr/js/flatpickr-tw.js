/* 
 * flatpicker 台湾
 * 
 * @package
 * @version
 * 
 * @since	2020/05/29 ver1.0 初版作成
 */

var toTW = {
	weekdays : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
	months: ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May', 'June', 'July', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'
	]
};

// '曜日'を英語化
flatpickr.init.prototype.l10n.weekdays.shorthand = toTW.weekdays;

// '月'を英語化
flatpickr.init.prototype.l10n.months.longhand = toTW.months;

$(function(){
	// flatpickrを起動させるinputのID *classでも可
	flatpickr('.flatpickr', { dateFormat: 'Y/m/d'});
});