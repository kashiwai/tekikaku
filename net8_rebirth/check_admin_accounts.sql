-- NET8 管理者アカウント確認・作成スクリプト
-- 実行日: 2025-12-12

-- データベース選択
USE net8_dev;

-- 1. mst_adminテーブル構造確認
DESCRIBE mst_admin;

-- 2. 既存の管理者アカウント確認
SELECT 
    admin_no,
    admin_id,
    admin_name,
    admin_pass,
    auth_flg,
    del_flg,
    created_at,
    updated_at
FROM mst_admin
WHERE del_flg = 0
ORDER BY admin_no;

-- 3. 削除済みアカウントも含めて全確認
SELECT 
    admin_no,
    admin_id,
    admin_name,
    CASE 
        WHEN del_flg = 0 THEN '有効'
        ELSE '削除済み'
    END as status,
    created_at
FROM mst_admin
ORDER BY admin_no;

-- 4. デフォルト管理者アカウントの作成（存在しない場合）
-- 注意: 以下のINSERTは必要に応じて実行してください

-- 管理者アカウントが0件の場合のみ実行
SET @admin_count = (SELECT COUNT(*) FROM mst_admin WHERE del_flg = 0);

-- デフォルト管理者作成（admin/admin123）
INSERT INTO mst_admin (
    admin_no, 
    admin_id, 
    admin_pass, 
    admin_name, 
    auth_flg, 
    del_flg, 
    deny_menu,
    created_at, 
    updated_at
)
SELECT 
    1,
    'admin',
    'admin123',
    'システム管理者',
    1,
    0,
    '',
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM mst_admin WHERE admin_id = 'admin' AND del_flg = 0
);

-- テスト管理者作成（testadmin/testpass123）
INSERT INTO mst_admin (
    admin_no, 
    admin_id, 
    admin_pass, 
    admin_name, 
    auth_flg, 
    del_flg, 
    deny_menu,
    created_at, 
    updated_at
)
SELECT 
    COALESCE(MAX(admin_no), 0) + 1,
    'testadmin',
    'testpass123',
    'テスト管理者',
    1,
    0,
    '',
    NOW(),
    NOW()
FROM mst_admin
WHERE NOT EXISTS (
    SELECT 1 FROM mst_admin WHERE admin_id = 'testadmin' AND del_flg = 0
);

-- 5. 作成後の確認
SELECT 
    '=== 利用可能な管理者アカウント ===' as info
UNION ALL
SELECT 
    CONCAT('ID: ', admin_id, ' / Password: ', 
           CASE 
               WHEN admin_id = 'admin' THEN 'admin123'
               WHEN admin_id = 'testadmin' THEN 'testpass123'
               ELSE '(設定済み)'
           END
    ) as info
FROM mst_admin
WHERE del_flg = 0
ORDER BY info;