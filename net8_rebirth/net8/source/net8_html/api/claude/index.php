<?php
/**
 * NET8 Claude Code API - Main Router
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * すべてのClaude Code APIリクエストをルーティングするメインエントリーポイント
 *
 * ベースURL: /api/claude/
 *
 * 利用可能なエンドポイント:
 * - POST /api/claude/auth                    - 認証・トークン取得
 * - /api/claude/api-keys/*                   - APIキー管理
 * - /api/claude/models/*                     - 機種管理
 * - /api/claude/machines/*                   - 台管理
 * - /api/claude/members/*                    - 会員管理
 * - /api/claude/points/*                     - ポイント管理
 * - /api/claude/sales/*                      - 売上管理
 * - /api/claude/play-history/*               - プレイ履歴
 * - /api/claude/masters/*                    - マスタ管理
 * - /api/claude/stats/*                      - 統計・ダッシュボード
 * - /api/claude/system/*                     - システム設定
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// プリフライトリクエスト処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// リクエストURIからルート解析
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/claude';

// ベースパスを除去してルートを取得
$fullPath = parse_url($requestUri, PHP_URL_PATH);
$route = substr($fullPath, strlen($basePath));
$route = ltrim($route, '/');

// ルートの最初のセグメントを取得
$segments = explode('/', $route);
$resource = $segments[0] ?? '';
$subPath = count($segments) > 1 ? '/' . implode('/', array_slice($segments, 1)) : '';

// PATH_INFOをセット（子ファイルで使用）
$_SERVER['PATH_INFO'] = $subPath;

// ルーティング
switch ($resource) {
    case '':
        // API情報
        echo json_encode([
            'success' => true,
            'data' => [
                'name' => 'NET8 Claude Code API',
                'version' => '1.0.0',
                'description' => 'Claude Code連携用REST API',
                'documentation' => '/api/claude/docs',
                'endpoints' => [
                    'auth' => 'POST /api/claude/auth - 認証',
                    'api-keys' => '/api/claude/api-keys/* - APIキー管理',
                    'models' => '/api/claude/models/* - 機種管理',
                    'machines' => '/api/claude/machines/* - 台管理',
                    'members' => '/api/claude/members/* - 会員管理',
                    'points' => '/api/claude/points/* - ポイント管理',
                    'sales' => '/api/claude/sales/* - 売上管理',
                    'play-history' => '/api/claude/play-history/* - プレイ履歴',
                    'masters' => '/api/claude/masters/* - マスタ管理',
                    'stats' => '/api/claude/stats/* - 統計・ダッシュボード',
                    'system' => '/api/claude/system/* - システム設定'
                ],
                'authentication' => [
                    'methods' => ['X-API-Key header', 'Authorization: Bearer <token>', 'api_key query parameter'],
                    'token_endpoint' => 'POST /api/claude/auth'
                ]
            ]
        ]);
        break;

    case 'docs':
        // API ドキュメント
        require_once __DIR__ . '/docs.php';
        break;

    case 'auth':
        require_once __DIR__ . '/auth.php';
        break;

    case 'api-keys':
        require_once __DIR__ . '/api-keys.php';
        break;

    case 'models':
        require_once __DIR__ . '/models.php';
        break;

    case 'machines':
        require_once __DIR__ . '/machines.php';
        break;

    case 'members':
        require_once __DIR__ . '/members.php';
        break;

    case 'points':
        require_once __DIR__ . '/points.php';
        break;

    case 'sales':
        require_once __DIR__ . '/sales.php';
        break;

    case 'play-history':
        require_once __DIR__ . '/play-history.php';
        break;

    case 'masters':
        require_once __DIR__ . '/masters.php';
        break;

    case 'stats':
        require_once __DIR__ . '/stats.php';
        break;

    case 'system':
        require_once __DIR__ . '/system.php';
        break;

    case 'setup-api-key.php':
        require_once __DIR__ . '/setup-api-key.php';
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'エンドポイントが見つかりません',
                'available_endpoints' => [
                    'auth', 'api-keys', 'models', 'machines', 'members',
                    'points', 'sales', 'play-history', 'masters', 'stats', 'system'
                ]
            ]
        ]);
}
