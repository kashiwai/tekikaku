<?php
/**
 * Frame Security for Play Pages - /ch/ サブドメイン版
 * プレイページ用のX-Frame-Options設定（iframe完全許可）
 *
 * このファイルをplay_v2/index.phpの最初でインクルードしてください
 *
 * ★ /ch/ サブドメイン専用: すべてのiframe埋め込みを無条件で許可
 */

// ★ iframe完全許可設定
header("X-Frame-Options: ALLOWALL");
header("Content-Security-Policy: frame-ancestors *");

// CORS設定 - すべてのオリジンを許可
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Max-Age: 3600");

// postMessage通信のため、Referrer-Policyを緩和
header("Referrer-Policy: no-referrer-when-downgrade");

// セキュリティヘッダー
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

error_log('✅ [/ch/] Frame Security: iframe ALLOWALL mode activated');
