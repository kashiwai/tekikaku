-- ================================================
-- NET8 AI Control System - Add Columns to dat_machine
-- 既存のdat_machineテーブルにAI Control用カラムを追加
-- ================================================
-- 作成日: 2025-11-05
-- 環境: GCP Cloud SQL (136.116.70.86:3306 / net8_dev)
-- ================================================

-- 既存テーブル構造確認
SELECT '========================================' AS '';
SELECT '既存のdat_machineテーブル構造' AS '';
SELECT '========================================' AS '';
DESCRIBE dat_machine;

-- ================================================
-- カラム追加（AI Control System用）
-- ================================================
-- 注意: カラムが既に存在する場合はエラーが出ますが問題ありません

-- 1. name カラム追加（マシン名）
ALTER TABLE dat_machine
ADD COLUMN name VARCHAR(50)
COMMENT 'マシン名（MACHINE-01など）';

-- 2. token カラム追加（認証トークン）
ALTER TABLE dat_machine
ADD COLUMN token VARCHAR(255)
COMMENT '認証トークン（MCP Server接続用）';

-- 3. status カラム追加（ステータス）
ALTER TABLE dat_machine
ADD COLUMN status ENUM('online', 'offline', 'error') DEFAULT 'offline'
COMMENT 'ステータス（online/offline/error）';

-- 4. ip_address カラム追加（IPアドレス）
ALTER TABLE dat_machine
ADD COLUMN ip_address VARCHAR(45)
COMMENT 'IPアドレス';

-- 5. mac_address カラム追加（MACアドレス）
ALTER TABLE dat_machine
ADD COLUMN mac_address VARCHAR(17)
COMMENT 'MACアドレス';

-- 6. chrome_rd_session_id カラム追加（Chrome Remote Desktop ID）
ALTER TABLE dat_machine
ADD COLUMN chrome_rd_session_id VARCHAR(255)
COMMENT 'Chrome Remote Desktop ID';

-- 7. last_heartbeat カラム追加（最終接続時刻）
ALTER TABLE dat_machine
ADD COLUMN last_heartbeat DATETIME
COMMENT '最終接続時刻（死活監視用）';

-- ================================================
-- インデックス追加
-- ================================================

-- status インデックス
ALTER TABLE dat_machine ADD INDEX idx_status (status);

-- last_heartbeat インデックス
ALTER TABLE dat_machine ADD INDEX idx_last_heartbeat (last_heartbeat);

-- ================================================
-- 変更後のテーブル構造確認
-- ================================================

SELECT '========================================' AS '';
SELECT '変更後のdat_machineテーブル構造' AS '';
SELECT '========================================' AS '';
DESCRIBE dat_machine;

-- ================================================
-- マシン1台目のデータ初期化
-- ================================================

-- machine_no=1 のレコードにAI Control用データを設定
UPDATE dat_machine
SET
    name = 'MACHINE-01',
    token = CONCAT('net8_m001_', MD5(CONCAT(UNIX_TIMESTAMP(), RAND()))),
    status = 'offline'
WHERE machine_no = 1
AND token IS NULL;

-- ================================================
-- 完了メッセージ
-- ================================================

SELECT '========================================' AS '';
SELECT '✅ カラム追加完了' AS status;
SELECT '========================================' AS '';

-- マシン1台目のデータ確認
SELECT
    machine_no,
    name,
    camera_no,
    signaling_id,
    LEFT(token, 20) AS token_preview,
    status,
    ip_address,
    mac_address,
    LEFT(chrome_rd_session_id, 20) AS chrome_rd_preview,
    last_heartbeat
FROM dat_machine
WHERE machine_no = 1;
