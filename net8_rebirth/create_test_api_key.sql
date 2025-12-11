-- SDK v1.1.0: テスト用APIキー作成
-- 実行日時: 2025-11-22

-- 既存のテストAPIキーを確認
SELECT id, key_value, partner_name, environment, is_active
FROM api_keys
WHERE key_value LIKE 'pk_test_%' OR key_value LIKE 'sk_test_%'
LIMIT 10;

-- テスト用APIキーを作成（存在しない場合）
INSERT IGNORE INTO api_keys (
    key_value,
    partner_name,
    environment,
    is_active,
    allowed_origins,
    rate_limit_per_minute,
    created_at
) VALUES (
    'pk_test_demo_2025',
    'Demo Partner',
    'test',
    1,
    '*',
    100,
    NOW()
);

-- 作成されたAPIキーを確認
SELECT id, key_value, partner_name, environment, is_active
FROM api_keys
WHERE key_value = 'pk_test_demo_2025';
