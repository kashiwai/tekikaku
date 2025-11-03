<?php
/**
 * WebRTC Setting File
 *
 * WebRTC関連の追加設定ファイル
 * setting_base.phpで定義された設定を上書き、または追加設定を行います
 */

// WebRTCの追加設定がある場合はここに記述
// 現在はsetting_base.phpで全ての必要な設定が定義されています

// 開発環境用のデバッグ設定
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    // デバッグモードでは詳細なログを出力
    error_log('WebRTC Settings Loaded - Debug Mode: ON');
}

// 環境別のシグナリングサーバー設定の上書き（必要に応じて）
// 例: 本番環境では異なるシグナリングサーバーを使用する場合
/*
if (getenv('ENV') === 'production') {
    $GLOBALS["RTC_Signaling_Servers"] = array(
        "default" => "production-signal.example.com:59000",
        "1" => "production-signal.example.com:59000",
        "2" => "production-signal-backup.example.com:59000"
    );
}
*/

// ICE Servers設定（STUN/TURNサーバー）
// WebRTCAPIクラスで使用されます

// STUN サーバー設定（WebRTCAPI用）
if (!isset($GLOBALS["RTC_Stun_Servers"])) {
    $GLOBALS["RTC_Stun_Servers"] = array(
        "stun.l.google.com:19302",  // Google公開STUNサーバー
        "stun1.l.google.com:19302"  // 予備STUNサーバー
    );
}

// TURN サーバー設定（WebRTCAPI用）
if (!isset($GLOBALS["RTC_Turn_Servers"])) {
    $GLOBALS["RTC_Turn_Servers"] = array(
        // TURNサーバーが必要な場合はここに追加
        // "turn.example.com:3478"
    );
}

// TURN API URL設定（WebRTCAPI用）
if (!isset($GLOBALS["RTC_Turn_APIURL"])) {
    $GLOBALS["RTC_Turn_APIURL"] = ""; // TURNサーバー認証API（未使用の場合は空）
}

// Signaling API URL設定（WebRTCAPI用）
if (!isset($GLOBALS["RTC_Signaling_APIURL"])) {
    $GLOBALS["RTC_Signaling_APIURL"] = "https://%s/peerjs/id"; // シグナリングAPI
}

// ICE Servers設定（従来のフォーマット・互換性のため残す）
if (!isset($GLOBALS["ICE_SERVERS"])) {
    $GLOBALS["ICE_SERVERS"] = array(
        array(
            'urls' => 'stun:stun.l.google.com:19302'  // Google公開STUNサーバー
        ),
    );
}

?>
