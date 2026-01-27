<?php
/**
 * NET8 Claude Code API - Documentation
 * Version: 1.0.0
 * Created: 2025-12-12
 */

header('Content-Type: application/json; charset=utf-8');

$documentation = [
    'name' => 'NET8 Claude Code API',
    'version' => '1.0.0',
    'base_url' => '/api/claude',
    'authentication' => [
        'description' => 'APIキーまたはJWTトークンで認証',
        'methods' => [
            [
                'name' => 'X-API-Key Header',
                'example' => 'X-API-Key: ck_claude_xxxxxxxxxxxxxxxx'
            ],
            [
                'name' => 'Authorization Bearer',
                'example' => 'Authorization: Bearer <jwt_token>'
            ],
            [
                'name' => 'Query Parameter',
                'example' => '?api_key=ck_claude_xxxxxxxxxxxxxxxx'
            ]
        ],
        'token_endpoint' => [
            'method' => 'POST',
            'path' => '/api/claude/auth',
            'body' => ['api_key' => 'string (required)'],
            'response' => ['token' => 'JWT token', 'expires_in' => 'seconds']
        ]
    ],
    'endpoints' => [
        'auth' => [
            'POST /api/claude/auth' => [
                'description' => '認証・トークン取得',
                'body' => ['api_key' => 'string'],
                'response' => ['token' => 'string', 'expires_in' => 'number']
            ]
        ],
        'api-keys' => [
            'GET /api/claude/api-keys' => [
                'description' => 'APIキー一覧',
                'params' => ['page', 'per_page', 'environment', 'is_active']
            ],
            'POST /api/claude/api-keys' => [
                'description' => 'APIキー新規発行',
                'body' => ['name' => 'required', 'environment' => 'test|live', 'key_type' => 'public|secret|claude']
            ],
            'GET /api/claude/api-keys/{id}' => ['description' => 'APIキー詳細'],
            'PUT /api/claude/api-keys/{id}' => ['description' => 'APIキー更新'],
            'DELETE /api/claude/api-keys/{id}' => ['description' => 'APIキー削除'],
            'POST /api/claude/api-keys/{id}/toggle' => ['description' => '有効/無効切替'],
            'POST /api/claude/api-keys/{id}/regenerate' => ['description' => 'キー再発行'],
            'GET /api/claude/api-keys/{id}/usage' => ['description' => '使用統計']
        ],
        'models' => [
            'GET /api/claude/models' => [
                'description' => '機種一覧',
                'params' => ['page', 'per_page', 'category', 'maker_no', 'type_no', 'search']
            ],
            'POST /api/claude/models' => [
                'description' => '機種登録',
                'body' => ['model_name' => 'required', 'category' => 'required (1=パチンコ, 2=スロット)']
            ],
            'GET /api/claude/models/{id}' => ['description' => '機種詳細'],
            'PUT /api/claude/models/{id}' => ['description' => '機種更新'],
            'DELETE /api/claude/models/{id}' => ['description' => '機種削除'],
            'GET /api/claude/models/{id}/settings' => ['description' => '機種設定取得'],
            'PUT /api/claude/models/{id}/settings' => ['description' => '機種設定更新']
        ],
        'machines' => [
            'GET /api/claude/machines' => [
                'description' => '台一覧',
                'params' => ['page', 'per_page', 'model_no', 'status', 'category']
            ],
            'POST /api/claude/machines' => [
                'description' => '台登録',
                'body' => ['model_no' => 'required', 'machine_cd' => 'required']
            ],
            'GET /api/claude/machines/{id}' => ['description' => '台詳細'],
            'PUT /api/claude/machines/{id}' => ['description' => '台更新'],
            'DELETE /api/claude/machines/{id}' => ['description' => '台削除'],
            'PUT /api/claude/machines/{id}/status' => [
                'description' => '台状態変更',
                'body' => ['status' => '0=準備中, 1=稼働中, 2=メンテナンス']
            ],
            'PUT /api/claude/machines/{id}/corner' => ['description' => 'コーナー配置変更']
        ],
        'members' => [
            'GET /api/claude/members' => [
                'description' => '会員一覧',
                'params' => ['page', 'per_page', 'status', 'search']
            ],
            'GET /api/claude/members/{id}' => ['description' => '会員詳細'],
            'PUT /api/claude/members/{id}' => ['description' => '会員更新'],
            'POST /api/claude/members/{id}/suspend' => [
                'description' => '緊急停止',
                'body' => ['reason' => 'string']
            ],
            'POST /api/claude/members/{id}/activate' => ['description' => '再アクティベート'],
            'GET /api/claude/members/{id}/points' => ['description' => 'ポイント履歴'],
            'POST /api/claude/members/{id}/points' => [
                'description' => 'ポイント付与',
                'body' => ['point' => 'required', 'reason' => 'string']
            ],
            'GET /api/claude/members/{id}/play-history' => ['description' => 'プレイ履歴']
        ],
        'points' => [
            'GET /api/claude/points/grant-settings' => ['description' => 'ポイント付与設定取得'],
            'PUT /api/claude/points/grant-settings' => ['description' => 'ポイント付与設定更新'],
            'POST /api/claude/points/bulk-grant' => [
                'description' => '一括ポイント付与',
                'body' => [
                    'point' => 'required',
                    'target_type' => 'all|active|specific',
                    'member_nos' => 'array (target_type=specificの場合)'
                ]
            ]
        ],
        'sales' => [
            'GET /api/claude/sales' => [
                'description' => '売上一覧',
                'params' => ['start_date', 'end_date', 'model_no', 'machine_no']
            ],
            'GET /api/claude/sales/summary' => ['description' => '売上サマリー'],
            'GET /api/claude/sales/by-date' => ['description' => '日別売上'],
            'GET /api/claude/sales/by-model' => ['description' => '機種別売上'],
            'GET /api/claude/sales/by-machine' => ['description' => '台別売上']
        ],
        'play-history' => [
            'GET /api/claude/play-history' => [
                'description' => 'プレイ履歴一覧',
                'params' => ['start_date', 'end_date', 'member_no', 'machine_no', 'status']
            ],
            'GET /api/claude/play-history/{id}' => ['description' => 'プレイ詳細'],
            'GET /api/claude/play-history/active' => ['description' => 'アクティブセッション'],
            'POST /api/claude/play-history/{id}/end' => [
                'description' => 'セッション強制終了',
                'body' => ['reason' => 'string']
            ]
        ],
        'masters' => [
            'GET /api/claude/masters/makers' => ['description' => 'メーカー一覧'],
            'POST /api/claude/masters/makers' => ['description' => 'メーカー登録'],
            'GET /api/claude/masters/types' => ['description' => '種別一覧'],
            'GET /api/claude/masters/units' => ['description' => '単位一覧'],
            'GET /api/claude/masters/corners' => ['description' => 'コーナー一覧'],
            'POST /api/claude/masters/corners' => ['description' => 'コーナー登録'],
            'GET /api/claude/masters/owners' => ['description' => 'オーナー一覧'],
            'GET /api/claude/masters/categories' => ['description' => 'カテゴリ一覧']
        ],
        'stats' => [
            'GET /api/claude/stats/dashboard' => ['description' => 'ダッシュボード概要'],
            'GET /api/claude/stats/realtime' => ['description' => 'リアルタイム状況'],
            'GET /api/claude/stats/members' => ['description' => '会員統計'],
            'GET /api/claude/stats/machines' => ['description' => '台統計'],
            'GET /api/claude/stats/revenue' => ['description' => '売上統計']
        ],
        'system' => [
            'GET /api/claude/system/health' => ['description' => 'ヘルスチェック（認証不要）'],
            'GET /api/claude/system/settings' => ['description' => 'システム設定取得'],
            'PUT /api/claude/system/settings' => ['description' => 'システム設定更新'],
            'GET /api/claude/system/hours' => ['description' => '営業時間取得'],
            'PUT /api/claude/system/hours' => ['description' => '営業時間更新'],
            'GET /api/claude/system/logs' => ['description' => 'システムログ'],
            'POST /api/claude/system/maintenance' => ['description' => 'メンテナンスモード切替']
        ]
    ],
    'response_format' => [
        'success' => [
            'success' => true,
            'data' => '...',
            'message' => 'optional message'
        ],
        'list' => [
            'success' => true,
            'data' => ['items' => '...'],
            'pagination' => ['total', 'page', 'per_page', 'total_pages']
        ],
        'error' => [
            'success' => false,
            'error' => [
                'code' => 'ERROR_CODE',
                'message' => 'エラーメッセージ'
            ]
        ]
    ],
    'error_codes' => [
        'VALIDATION_ERROR' => 'バリデーションエラー',
        'UNAUTHORIZED' => '認証エラー',
        'FORBIDDEN' => 'アクセス拒否',
        'NOT_FOUND' => 'リソースが見つかりません',
        'METHOD_NOT_ALLOWED' => 'メソッドが許可されていません',
        'RATE_LIMIT_EXCEEDED' => 'レート制限超過',
        'SERVER_ERROR' => 'サーバーエラー'
    ]
];

echo json_encode($documentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
