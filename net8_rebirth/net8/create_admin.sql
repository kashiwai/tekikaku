-- 管理者ユーザー作成SQL
-- admin / admin123

INSERT INTO mst_admin (
    admin_id,
    admin_pass,
    admin_name,
    admin_auth,
    del_flg,
    add_no,
    add_dt
) VALUES (
    'admin',
    '0192023a7bbd73250516f069df18b500',  -- MD5('admin123')
    'システム管理者',
    9,
    0,
    1,
    NOW()
) ON DUPLICATE KEY UPDATE
    admin_pass = '0192023a7bbd73250516f069df18b500',
    admin_name = 'システム管理者',
    admin_auth = 9,
    upd_dt = NOW();

-- 確認
SELECT admin_no, admin_id, admin_name, admin_auth, add_dt
FROM mst_admin
WHERE admin_id = 'admin';
