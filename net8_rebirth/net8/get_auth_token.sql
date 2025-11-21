-- ================================================================
-- WebSocket認証トークン取得SQL（dat_machineテーブル版）
-- ================================================================
-- slotserver.ini に設定するための認証情報を dat_machine から取得します
-- ================================================================

-- 【クエリ1】Windows PC（machine_no=1）の認証情報を取得
SELECT
    dm.machine_no AS 'machine_id (設定値)',
    dm.machine_cd AS '機械コード',
    mm.model_name AS '機種名',
    mc.camera_mac AS 'カメラMAC',
    dm.signaling_id AS 'signaling_id (PeerJS ID)',
    dm.token AS 'auth_token (設定値)',
    'peerjs' AS 'api_key (設定値)',
    CONCAT(
        'wss://mgg-signaling-production-c1bd.up.railway.app/peerjs',
        '?id=', dm.signaling_id,
        '&token=', dm.token,
        '&key=peerjs'
    ) AS 'WebSocket URL (完全版)'
FROM dat_machine dm
LEFT JOIN mst_model mm ON mm.model_no = dm.model_no AND mm.del_flg = 0
LEFT JOIN mst_camera mc ON mc.camera_no = dm.camera_no
WHERE dm.del_flg = 0
  AND dm.machine_no = 1  -- ← Windows PCの機械番号（必要に応じて変更）
LIMIT 1;

-- ================================================================
-- 【クエリ2】全マシンの認証トークン確認
-- ================================================================
SELECT
    dm.machine_no AS '機械番号',
    dm.machine_cd AS '機械コード',
    dm.signaling_id AS 'Signaling ID',
    LEFT(dm.token, 30) AS 'トークン（先頭30文字）',
    CASE
        WHEN dm.token IS NOT NULL AND dm.token != '' THEN '✓ あり'
        ELSE '✗ なし'
    END AS 'トークン状態'
FROM dat_machine dm
WHERE dm.del_flg = 0
ORDER BY dm.machine_no ASC;

-- ================================================================
-- 【slotserver.ini 設定例】
-- ================================================================
--
-- 上記クエリ1の結果を使用して、以下のように設定してください：
--
-- [Auth]
-- machine_id = 1
-- auth_token = (取得したトークン)
-- api_key = peerjs
--
-- [Monitor]
-- url = wss://mgg-signaling-production-c1bd.up.railway.app/peerjs?id=(signaling_id)&token=(auth_token)&key=peerjs
--
-- または、URLに直接パラメータを含める方法：
--
-- [Monitor]
-- url = (クエリ1の「WebSocket URL (完全版)」をコピー)
--
-- ================================================================
