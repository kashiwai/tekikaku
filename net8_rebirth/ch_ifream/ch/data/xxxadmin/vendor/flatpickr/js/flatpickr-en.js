/* 
 * flatpicker 英語化
 * 
 * @package
 * @version
 * 
 * @since	2016/10/13 ver1.0 初版作成
 */

var toEN = {
	weekdays : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
	months: ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May', 'June', 'July', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'
	]
};

// '曜日'を英語化
flatpickr.init.prototype.l10n.weekdays.shorthand = toEN.weekdays;

// '月'を英語化
flatpickr.init.prototype.l10n.months.longhand = toEN.months;

$(function(){
	// flatpickrを起動させるinputのID *classでも可
	flatpickr('.flatpickr', { dateFormat: 'Y/m/d'});
});