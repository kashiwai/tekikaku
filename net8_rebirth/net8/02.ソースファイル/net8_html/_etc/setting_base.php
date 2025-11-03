<?php
/**
 * NET8 Basic Site Settings
 *
 * サイトの基本設定ファイル
 */

// サイト基本情報
define('SITE_NAME', getenv('SITE_NAME') ?: 'NET8 System');
define('SITE_URL', getenv('SITE_URL') ?: 'https://mgg-webservice-production.up.railway.app/');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@example.com');
define('DOMAIN', getenv('DOMAIN') ?: 'mgg-webservice-production.up.railway.app');
define('URL_SSL_SITE', getenv('URL_SSL_SITE') ?: 'https://mgg-webservice-production.up.railway.app/'); // Railway本番環境

// 営業時間設定
define('GLOBAL_OPEN_TIME', getenv('GLOBAL_OPEN_TIME') ?: '10:00');
define('GLOBAL_CLOSE_TIME', getenv('GLOBAL_CLOSE_TIME') ?: '22:00');
define('REFERENCE_TIME', getenv('REFERENCE_TIME') ?: '04:00'); // 基準時間

// 表示設定
define('INDEX_VIEW_MACHINES', (int)(getenv('INDEX_VIEW_MACHINES') ?: 20)); // トップページ表示台数
define('FOLDER_LANG', getenv('FOLDER_LANG') ?: 'ja'); // 言語フォルダ
define('NEW_DAYS', 30); // 新台として表示する日数

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// セッション設定
define('SESSION_LIFETIME', 3600); // 1時間
define('SESSION_NAME', 'NET8_SESSION');
define('SESSION_SEC', 'secure_session_key'); // セッションセキュリティキー
define('SESSION_SID', 'NETSID'); // セッションID名

// セキュリティ設定
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15分

// ファイルアップロード設定
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp4', 'webm']);
define('UPLOAD_PATH', '/var/www/html/data/uploads/');

// ページネーション設定
define('ITEMS_PER_PAGE', 20);

// デバッグモード（本番環境では必ずfalseに設定）
define('DEBUG_MODE', getenv('DEBUG_MODE') === 'true' ? true : false);

// エラー表示設定
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// ログレベル設定
define('LOG_LEVEL', getenv('LOG_LEVEL') ?: 'INFO'); // DEBUG, INFO, WARNING, ERROR

// キャッシュ設定
define('CACHE_ENABLED', getenv('CACHE_ENABLED') === 'true' ? true : false);
define('CACHE_LIFETIME', 3600); // 1時間

// メール送信設定（将来の実装用）
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@example.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'NET8 System');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: '25');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: ''); // tls or ssl

// API設定
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // 1時間あたりのリクエスト数

// ディレクトリパス設定
define('DIR_BASE', '/var/www/html/data/');
define('DIR_LIB', __DIR__ . '/../_lib/');
define('DIR_HTML_SCRIPT', '/systemjs/');

// サイト表示設定
define('URL_SITE', getenv('URL_SITE') ?: 'https://mgg-webservice-production.up.railway.app/');
define('SITE_TITLE', getenv('SITE_TITLE') ?: '777ONLINE');
define('COPYRIGHT', getenv('COPYRIGHT') ?: 'Copyright (C) 777ONLINE All Rights Reserved.');
define('DEFAULT_LANG', 'ja');
define('CLIENT_CODE', '001'); // クライアントコード
define('DOMAIN_DEVELOPMENT', 'localhost');
define('DOMAIN_PRODUCTION', getenv('DOMAIN_PRODUCTION') ?: 'mgg-webservice-production.up.railway.app');

// 会員登録設定
define('NICKNAME_LIMIT', 20); // ニックネーム文字数制限
define('MAIL_LIMIT', 255); // メールアドレス文字数制限
define('MEMBER_PASS_MIN', 8); // パスワード最小文字数
define('MEMBER_PASS_MAX', 20); // パスワード最大文字数
define('MEMBER_PASS_PATTERN', '^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$'); // 英数字混在
define('INVITE_CODE_LENGTH', 8); // 招待コード長
define('BENEFITS_CODE_LENGTH', 10); // 特典コード長
define('BENEFITS_CODE_STR', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'); // 特典コード使用文字
define('MEMBER_BENEFITS', true); // 会員特典機能有効化
define('REGIST_LIMIT', 7); // 仮登録有効期限（日数）

// メール送信設定（詳細）
define('MAIL_PROTOCOL', 'mail'); // mail, smtp, sendmail
define('MAIL_ERROR', getenv('MAIL_ERROR') ?: ADMIN_EMAIL);
define('MAIL_ENABLED', getenv('MAIL_ENABLED') === 'true' ? true : false); // 開発環境ではfalse
define('MEMBER_REGIST_SUBJECT', '【777ONLINE】会員登録のご案内');
define('MEMBER_REGIST_BODY',
"この度は、777ONLINEにご登録いただき、誠にありがとうございます。

以下のURLにアクセスして、会員登録を完了してください。

登録URL: %REGISTURL%

※このURLの有効期限は%LIMITSPAN%日間（%LIMITDATE%まで）です。
※ご登録のメールアドレス: %MAIL%

今後ともよろしくお願いいたします。

---
777ONLINE 運営チーム
");

// グローバル変数設定
$GLOBALS["MagazineReadStatus"] = array(
    "0" => "受け取らない",
    "1" => "受け取る"
);

$GLOBALS["langList"] = array(
    "ja" => "日本語",
    "en" => "English"
);

// カテゴリ使用設定
$GLOBALS["CategoryUseList"] = array(
    "PACH" => true,  // パチンコ（Pachinko）
    "SLOT" => true   // スロット（Slot）
);

// 存在ステータス（あり/なし）
$GLOBALS["thereIsStatus"] = array(
    "0" => "なし",  // No/None
    "1" => "あり"   // Yes/Exists
);

// メーカー表示ステータス
$GLOBALS["makerDispStatus"] = array(
    "0" => "表示しない",  // Don't display
    "1" => "表示する"     // Display
);

// 管理者ステータス
$GLOBALS["AdminStatus"] = array(
    "0" => "有効",
    "1" => "削除済み"
);

// 管理者権限ステータス
$GLOBALS["AdminAuthStatus"] = array(
    "0" => "一般",
    "1" => "管理者",
    "9" => "システム管理者"
);

// 会員ステータス
$GLOBALS["MemberStatus"] = array(
    "0" => "有効",
    "1" => "仮登録",
    "2" => "停止",
    "3" => "退会"
);

// ブラックリスト会員ステータス
$GLOBALS["BlackMemberStatus"] = array(
    "1" => "ブラックリスト"
);

// テスター会員
$GLOBALS["TesterMember"] = array(
    "1" => "テスター"
);

// カテゴリリスト（パチンコ/スロット）
$GLOBALS["categoryList"] = array(
    "1" => "パチンコ",
    "2" => "スロット"
);

// 機種ステータスリスト
$GLOBALS["machineStatusList"] = array(
    "0" => "停止中",
    "1" => "稼働中",
    "2" => "メンテナンス中"
);

// 曜日リスト
$GLOBALS["weekList"] = array(
    "日", "月", "火", "水", "木", "金", "土"
);

// 購入タイプ
$GLOBALS["viewPurchaseType"] = array(
    "1" => "プレイポイント",
    "2" => "クレジット",
    "3" => "抽選ポイント"
);

// 購入結果ステータス
$GLOBALS["purchaseResultStatus"] = array(
    "0" => "未決済",
    "1" => "決済完了",
    "2" => "決済失敗",
    "3" => "キャンセル"
);

// 金額表示単位
$GLOBALS["viewAmountType"] = array(
    "1" => "pt",
    "2" => "円",
    "3" => "pt"
);

// 表示単位リスト
$GLOBALS["viewUnitList"] = array(
    "1" => "pt",     // プレイポイント
    "2" => "円",     // クレジット
    "3" => "pt"      // 抽選ポイント
);

// 退出アクションタイプ
$GLOBALS["outActionType"] = array(
    "0" => "通常退出",
    "1" => "強制退出",
    "2" => "タイムアウト"
);

// 配送ステータスリスト
$GLOBALS["shippingStatusList"] = array(
    "0" => "未発送",
    "1" => "発送済み",
    "2" => "配送中",
    "3" => "配達完了"
);

// 抽選タイプリスト
$GLOBALS["drawTypeList"] = array(
    "0" => "通常",
    "1" => "プレミアム"
);

// 売り切れステータス
$GLOBALS["SoldOutStatusList"] = array(
    "0" => "在庫あり",
    "1" => "売り切れ"
);

// 抽選ステータスリスト
$GLOBALS["drawStatusList"] = array(
    "0" => "抽選前",
    "1" => "当選",
    "2" => "落選"
);

// ボードタイプリスト
$GLOBALS["boardTypeList"] = array(
    "1" => "タイプA",
    "2" => "タイプB"
);

// カテゴリボード利用可能性
$GLOBALS["CategoryBoardAvailability"] = array(
    "1" => array("1" => true, "2" => true),  // パチンコ
    "2" => array("1" => true, "2" => true)   // スロット
);

// 画像拡張子
$GLOBALS["ImgExtension"] = array(
    array("ext" => "jpg", "mine" => "image/jpeg"),
    array("ext" => "jpeg", "mine" => "image/jpeg"),
    array("ext" => "png", "mine" => "image/png"),
    array("ext" => "gif", "mine" => "image/gif")
);

// レイアウト名リスト
$GLOBALS["LayoutNameList"] = array(
    "1" => "レイアウト1",
    "2" => "レイアウト2",
    "3" => "レイアウト3"
);

// 機種ボーナスリセットリスト
$GLOBALS["resetMachineBonusList"] = array(
    "0" => "リセットしない",
    "1" => "リセットする"
);

// ギフト追加設定タイプ
$GLOBALS["GiftAddSetTypeList"] = array(
    "1" => "固定値",
    "2" => "割合"
);

// ギフト追加設定値単位
$GLOBALS["GiftAddSetValUnitList"] = array(
    "1" => "pt",
    "2" => "%"
);

// RTC Signaling Servers（WebRTC用）
// フォーマット: "host:port"
// 環境変数からシグナリングサーバー設定を動的に取得（Railway対応）
$signaling_host = $_SERVER['SIGNALING_HOST'] ?? $_ENV['SIGNALING_HOST'] ?? getenv('SIGNALING_HOST') ?: 'mgg-signaling-production-c1bd.up.railway.app';
$signaling_port = $_SERVER['SIGNALING_PORT'] ?? $_ENV['SIGNALING_PORT'] ?? getenv('SIGNALING_PORT') ?: '443';

$GLOBALS["RTC_Signaling_Servers"] = array(
    "default" => $signaling_host . ':' . $signaling_port,  // 環境変数から動的取得
    "1" => $signaling_host . ':' . $signaling_port,         // シグナリングサーバーID=1
    "2" => $signaling_host . ':' . $signaling_port          // シグナリングサーバーID=2（予備）
);

// WebRTC関連定数
$GLOBALS["RTC_PEER_APIKEY"] = "peerjs";  // PeerJS APIキー（デフォルト）

// カメラ設定
if (!defined('CAMERA_NAME')) define('CAMERA_NAME', 'camera_%d_%d');  // カメラ名フォーマット
if (!defined('API_CAMERA_ADD_NO')) define('API_CAMERA_ADD_NO', 1);  // カメラ追加時の管理者番号

// タイミング設定（秒）
if (!defined('AUTO_PAY_TIME')) define('AUTO_PAY_TIME', 180);  // 自動精算時間（180秒 = 3分）
if (!defined('NOTICE_CLOSE_TIME')) define('NOTICE_CLOSE_TIME', 30);  // 閉店予告時間（30分前）
if (!defined('CHROME_RESTART_TIME')) define('CHROME_RESTART_TIME', 28800);  // Chrome再起動時間（28800秒 = 8時間）

$GLOBALS["MailParam"] = array(
    "Host" => SMTP_HOST,
    "Port" => SMTP_PORT,
    "User" => SMTP_USER,
    "Password" => SMTP_PASSWORD,
    "Secure" => SMTP_SECURE
);

$GLOBALS["footerviews"] = array(
    "TERMS" => 1,   // 利用規約の表示・チェック必須
    "POLICY" => 1   // プライバシーポリシーの表示・チェック必須
);

// フローコーナー固定タブ
$GLOBALS["Frow_Fixed_Array"] = array(
    "" => "全て",
    "new" => "新台"
);

// フロー表示設定
define('FLOW_COL_MAX', 4); // フロー表示の列数（1行あたりの最大表示数）

// その他の設定
define('AUTH_MEMBER_MOBILE', false); // 携帯番号認証を使用しない（開発環境）

// 画像アップロード設定（管理画面用）
define('UPFILE_IMG_EXT', 'jpg / jpeg / png / gif'); // アップロード可能な画像拡張子
define('UPFILE_IMG_MAX', 10); // アップロード最大ファイルサイズ（MB）
define('PUSHORDER_IMG_MAX', 500); // プッシュオーダー画像の最大ファイルサイズ（KB）

// 追加機能設定
define('TRACK_BONUS_BREAKDOWN', true); // ボーナス内訳記録機能
define('PUSH_ORDER_COUNT', 10); // プッシュオーダー数

// 機種設定リスト
$GLOBALS["ModelSettingList"] = array(1, 2, 3, 4, 5, 6); // 設定1～6

// レイアウト非表示リスト
$GLOBALS["layout_hideList"] = array("1", "2", "3");

// ボード別追加設定
$GLOBALS["AddSettingByBoard"] = array(
    "1" => false,  // タイプA: 追加設定なし
    "2" => true    // タイプB: 追加設定あり
);

// 特殊追加設定
$GLOBALS["AddSettingBySpecial"] = array(
    "settinglist" => array(2)  // タイプBで設定リスト使用
);

// ボードバージョンデータ
$GLOBALS["boardVersionData"] = array(
    "1" => array(
        "video_portrait" => 0,
        "video_mode" => 4,
        "drum" => 0,
        "version" => 1
    ),
    "2" => array(
        "video_portrait" => 0,
        "video_mode" => 4,
        "drum" => 0,
        "version" => 2
    )
);

// 画像保存ディレクトリ
if (!defined('DIR_IMG_MODEL')) {
    define('DIR_IMG_MODEL', '/var/www/html/data/img/model/');
}
