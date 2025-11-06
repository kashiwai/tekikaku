-- テストプレイヤーを作成

INSERT INTO mst_member (
    member_id,
    password,
    nickname,
    mail,
    state,
    add_no,
    add_dt,
    upd_no,
    upd_dt,
    del_flg
) VALUES (
    'player01',
    MD5('password123'),  -- パスワードをMD5ハッシュ化
    'テストプレイヤー',
    'player01@test.com',
    1,  -- state=1（有効）
    1,
    NOW(),
    1,
    NOW(),
    0
) ON DUPLICATE KEY UPDATE
    password = MD5('password123'),
    nickname = 'テストプレイヤー',
    mail = 'player01@test.com',
    state = 1,
    upd_dt = NOW();

-- 確認
SELECT member_no, member_id, nickname, state
FROM mst_member
WHERE member_id = 'player01';
