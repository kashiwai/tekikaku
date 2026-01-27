<?php

/** This file is part of KCFinder project
  *
  *      @desc Base configuration file
  *   @package KCFinder
  *   @version 3.12
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://opensource.org/licenses/GPL-3.0 GPLv3
  *   @license http://opensource.org/licenses/LGPL-3.0 LGPLv3
  *      @link http://kcfinder.sunhater.com
  */

global $_CONFIG;	// 2018/09 [ADD]

/* IMPORTANT!!! Do not comment or remove uncommented settings in this file
   even if you are using session configuration.
   See http://kcfinder.sunhater.com/install for setting descriptions */

$_CONFIG = array(


// GENERAL SETTINGS

    'disabled' => true,
    'uploadURL' => "upload",
    'uploadDir' => "",
    'theme' => "default",

    'types' => array(

    // (F)CKEditor types
//        'files'   =>  "",
//       'flash'   =>  "swf",
        'images'  =>  "*img",

    // TinyMCE types
//        'file'    =>  "",
//        'media'   =>  "swf flv avi mpg mpeg qt mov wmv asf rm",
//        'image'   =>  "*img",
    ),


// IMAGE SETTINGS

    'imageDriversPriority' => "imagick gmagick gd",
    'jpegQuality' => 90,
    'thumbsDir' => ".thumbs",

    'maxImageWidth' => 1400,
    'maxImageHeight' => 0,

    'thumbWidth' => 100,
    'thumbHeight' => 100,

    'watermark' => "",


// DISABLE / ENABLE SETTINGS

    'denyZipDownload' => true,
    'denyUpdateCheck' => true,
    'denyExtensionRename' => true,


// PERMISSION SETTINGS

    'dirPerms' => 0755,
    'filePerms' => 0644,

    'access' => array(

        'files' => array(
            'upload' => true,
            'delete' => true,
            'copy'   => false,
            'move'   => false,
            'rename' => false
        ),

        'dirs' => array(
            'create' => false,
            'delete' => false,
            'rename' => false
        )
    ),

    'deniedExts' => "exe com msi bat cgi pl php phps phtml php3 php4 php5 php6 py pyc pyo pcgi pcgi3 pcgi4 pcgi5 pchi6",


// MISC SETTINGS

    'filenameChangeChars' => array(/*
        ' ' => "_",
        ':' => "."
    */),

    'dirnameChangeChars' => array(/*
        ' ' => "_",
        ':' => "."
    */),

    'mime_magic' => "",

    'cookieDomain' => "",
    'cookiePath' => "",
    'cookiePrefix' => 'KCFINDER_',


// THE FOLLOWING SETTINGS CANNOT BE OVERRIDED WITH SESSION SETTINGS

    '_normalizeFilenames' => false,
    '_check4htaccess' => false,
    //'_tinyMCEPath' => "/tiny_mce",

    '_sessionVar' => "KCFINDER",
    //'_sessionLifetime' => 30,
    //'_sessionDir' => "/full/directory/path",
    //'_sessionDomain' => ".mysite.com",
    //'_sessionPath' => "/my/path",

    //'_cssMinCmd' => "java -jar /path/to/yuicompressor.jar --type css {file}",
    //'_jsMinCmd' => "java -jar /path/to/yuicompressor.jar --type js {file}",

);


// 2018/09 [ADD]
//  有効、無効判定
if (!function_exists('KCF_CheckAuthentication')) {
	function KCF_CheckAuthentication() {
		global $_CONFIG;

		// 判定用クラス
		if (!class_exists('KcfCheckAuth')) {
			require('../../../../_sys/KcfCheckAuth.php');
		}
		// 判定
		$KcfCheckAuth = new KcfCheckAuth();
		$ret = $KcfCheckAuth->CheckAuth();
		// 動的設定
		$_CONFIG['uploadURL'] = $KcfCheckAuth->UploadURL;
		$_CONFIG['uploadDir'] = $KcfCheckAuth->UploadDir;
		return $ret;
    }
}

// ユーザー認証の実行
$_CONFIG['disabled'] = !KCF_CheckAuthentication();

?>