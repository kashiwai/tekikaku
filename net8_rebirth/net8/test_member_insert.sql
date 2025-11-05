-- テストメンバー登録SQL
-- 管理画面でのテスト用メンバーを直接登録

-- まず既存のテストメンバーを確認
SELECT member_no, nickname, mail, state FROM mst_member WHERE mail = 'test@admin.com';

-- テストメンバーを登録（パスワード: password123）
INSERT INTO mst_member (
    nickname,
    mail,
    pass,
    sex,
    point,
    draw_point,
    state,
    regist_id,
    invite_cd,
    join_dt,
    mail_magazine,
    tester_flg,
    add_dt,
    add_no,
    upd_dt,
    upd_no
) VALUES (
    'テストユーザー',
    'test@admin.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password123 (bcrypt)
    1,
    1000,
    0,
    1,
    '0',
    CONCAT('TEST', FLOOR(RAND() * 100000000)),
    NOW(),
    0,
    0,
    NOW(),
    1,
    NOW(),
    1
) ON DUPLICATE KEY UPDATE
    pass = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    state = 1,
    point = 1000,
    upd_dt = NOW();

-- 登録確認
SELECT
    member_no,
    nickname,
    mail,
    point,
    draw_point,
    state,
    join_dt,
    'password123' as test_password
FROM mst_member
WHERE mail = 'test@admin.com';
