/**
 * @license Copyright (c) 2003-2018, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For complete reference see:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// ツールバー設定
	config.toolbar = [
				["Cut", "Copy", "Paste"],		// 切取、コピー、張り付け
				["Undo", "Redo"],				// 元に戻す、やり直す
				["Link", "Unlink", "Image"],	// リンク挿入、リンク解除、イメージ
				["Bold", "Italic", "Strike"],	// 太字、斜体、打ち消し線
				["TextColor","BGColor"],		// テキストカラー、テキスト背景色
				["Format"],						// 段落の書式
				["Source"],						// ソース
			];

	// Set the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';

	// Simplify the dialog windows.
	config.removeDialogTabs = 'image:advanced;link:advanced';

	// フィルタオフ
	config.allowedContent = true;

	// サーバブラウザ設定
	config.filebrowserWindowWidth = '45%';
	config.filebrowserWindowHeight = '50%';
	config.filebrowserImageBrowseUrl = '../' + adminPath + '/systemjs/kcfinder/browse.php?type=images';

	// プラグイン設定
	config.extraPlugins = 'colorbutton,panelbutton,colordialog';

	// カラーセレクタに表示される色(4.6.2以降のデフォルト)
	config.colorButton_colors =
		'000,800000,8B4513,2F4F4F,008080,000080,4B0082,696969,' +
		'B22222,A52A2A,DAA520,006400,40E0D0,0000CD,800080,808080,' +
		'F00,FF8C00,FFD700,008000,0FF,00F,EE82EE,A9A9A9,' +
		'FFA07A,FFA500,FFFF00,00FF00,AFEEEE,ADD8E6,DDA0DD,D3D3D3,' +
		'FFF0F5,FAEBD7,FFFFE0,F0FFF0,F0FFFF,F0F8FF,E6E6FA,FFF';
};
