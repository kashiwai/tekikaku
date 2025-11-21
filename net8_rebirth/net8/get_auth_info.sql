-- ================================================================
-- WebSocket認証情報取得SQL
-- ================================================================
-- slotserver.ini に設定するための認証情報を取得します

-- 【クエリ1】機械情報と認証キーを取得
SELECT
    dm.machine_no AS 'machine_id (設定値)',
    dm.machine_cd AS '機械コード',
    mm.model_name AS '機種名',
    mc.camera_mac AS 'カメラMAC',
    dk.auth_key AS 'auth_token (設定値)',
    'peerjs' AS 'api_key (設定値)'
FROM dat_machine dm
INNER JOIN mst_model mm ON mm.model_no = dm.model_no
LEFT JOIN mst_camera mc ON mc.camera_no = dm.camera_no
LEFT JOIN dat_key dk ON dk.machine_no = dm.machine_no AND dk.del_flg = 0
WHERE dm.del_flg = 0
  AND dm.machine_no = 1  -- ← Windows PCの機械番号（必要に応じて変更）
LIMIT 1;

-- ================================================================
-- 【クエリ2】全機械の認証情報を確認
SELECT
    dm.machine_no,
    dm.machine_cd,
    mm.model_name,
    dk.auth_key,
    CASE WHEN dk.auth_key IS NOT NULL THEN '✓ あり' ELSE '✗ なし' END AS 'auth_key状態'
FROM dat_machine dm
INNER JOIN mst_model mm ON mm.model_no = dm.model_no
LEFT JOIN dat_key dk ON dk.machine_no = dm.machine_no AND dk.del_flg = 0
WHERE dm.del_flg = 0
ORDER BY dm.machine_no ASC;

-- ================================================================
-- 【クエリ3】auth_keyが存在しない場合、作成する
-- 実行前にバックアップを取ってください！

-- INSERT INTO dat_key (machine_no, auth_key, add_dt, upd_dt, del_flg)
-- VALUES (
--     1,  -- machine_no
--     CONCAT('auth_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 100000)),  -- ランダムなauth_key生成
--     NOW(),
--     NOW(),
--     0
-- );

-- または、手動で設定する場合：
-- INSERT INTO dat_key (machine_no, auth_key, add_dt, upd_dt, del_flg)
-- VALUES (1, 'your_secure_auth_token_here', NOW(), NOW(), 0);

-- ================================================================
