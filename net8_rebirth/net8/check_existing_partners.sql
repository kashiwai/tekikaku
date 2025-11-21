-- ========================================
-- 既存SDK連携パートナー確認クエリ
-- 実行: SQL Studio等で実行してください
-- ========================================

-- 1. 全パートナーとAPIキーの状況確認
SELECT
    ak.id AS api_key_id,
    ak.partner_name AS パートナー名,
    ak.key_value AS APIキー,
    ak.environment AS 環境,
    CASE
        WHEN ak.is_active = 1 THEN '有効'
        ELSE '無効'
    END AS ステータス,
    ak.created_at AS 作成日時,
    COUNT(DISTINCT su.id) AS 登録ユーザー数,
    COUNT(DISTINCT gs.id) AS 総ゲームセッション数,
    MAX(gs.started_at) AS 最終ゲームプレイ日時
FROM api_keys ak
LEFT JOIN sdk_users su ON ak.id = su.api_key_id
LEFT JOIN game_sessions gs ON ak.id = gs.api_key_id
WHERE ak.is_active = 1
GROUP BY ak.id, ak.partner_name, ak.key_value, ak.environment, ak.is_active, ak.created_at
ORDER BY ak.created_at DESC;

-- 2. パートナーごとのユーザー詳細
SELECT
    ak.partner_name AS パートナー名,
    su.id AS ユーザーID,
    su.partner_user_id AS パートナー側ユーザーID,
    su.email AS メール,
    su.username AS ユーザー名,
    ub.balance AS 残高,
    ub.total_deposited AS 総入金,
    ub.total_consumed AS 総消費,
    ub.total_won AS 総獲得,
    su.created_at AS 登録日時,
    ub.last_transaction_at AS 最終取引日時
FROM sdk_users su
JOIN api_keys ak ON su.api_key_id = ak.id
LEFT JOIN user_balances ub ON su.id = ub.user_id
WHERE ak.is_active = 1
ORDER BY su.created_at DESC
LIMIT 50;

-- 3. 最近のゲームセッション履歴（直近10件）
SELECT
    ak.partner_name AS パートナー名,
    gs.session_id AS セッションID,
    gs.model_cd AS 機種コード,
    gs.model_name AS 機種名,
    gs.points_consumed AS 消費ポイント,
    gs.points_won AS 獲得ポイント,
    gs.result AS 結果,
    gs.status AS ステータス,
    gs.started_at AS 開始日時,
    gs.ended_at AS 終了日時,
    TIMESTAMPDIFF(SECOND, gs.started_at, gs.ended_at) AS プレイ時間秒
FROM game_sessions gs
JOIN api_keys ak ON gs.api_key_id = ak.id
WHERE ak.is_active = 1
ORDER BY gs.started_at DESC
LIMIT 10;

-- 4. パートナーごとの統計サマリー
SELECT
    ak.partner_name AS パートナー名,
    ak.environment AS 環境,
    COUNT(DISTINCT su.id) AS ユーザー総数,
    COUNT(DISTINCT gs.id) AS ゲームセッション総数,
    SUM(gs.points_consumed) AS 総消費ポイント,
    SUM(gs.points_won) AS 総獲得ポイント,
    ROUND(AVG(gs.points_consumed), 2) AS 平均消費ポイント,
    ROUND(AVG(gs.points_won), 2) AS 平均獲得ポイント,
    MAX(gs.started_at) AS 最終プレイ日時,
    DATEDIFF(NOW(), MAX(gs.started_at)) AS 最終プレイからの経過日数
FROM api_keys ak
LEFT JOIN sdk_users su ON ak.id = su.api_key_id
LEFT JOIN game_sessions gs ON ak.id = gs.api_key_id
WHERE ak.is_active = 1
GROUP BY ak.partner_name, ak.environment
ORDER BY 最終プレイ日時 DESC;

-- 5. ポイント取引履歴（直近20件）
SELECT
    ak.partner_name AS パートナー名,
    su.partner_user_id AS ユーザーID,
    pt.transaction_id AS 取引ID,
    pt.type AS 取引種類,
    pt.amount AS 金額,
    pt.balance_before AS 変更前残高,
    pt.balance_after AS 変更後残高,
    pt.game_session_id AS セッションID,
    pt.description AS 説明,
    pt.created_at AS 取引日時
FROM point_transactions pt
JOIN sdk_users su ON pt.user_id = su.id
JOIN api_keys ak ON su.api_key_id = ak.id
WHERE ak.is_active = 1
ORDER BY pt.created_at DESC
LIMIT 20;

-- 6. 非アクティブなパートナー（過去30日間プレイなし）
SELECT
    ak.partner_name AS パートナー名,
    ak.environment AS 環境,
    ak.key_value AS APIキー,
    COUNT(DISTINCT su.id) AS ユーザー数,
    MAX(gs.started_at) AS 最終プレイ日時,
    DATEDIFF(NOW(), MAX(gs.started_at)) AS 経過日数,
    ak.created_at AS APIキー作成日
FROM api_keys ak
LEFT JOIN sdk_users su ON ak.id = su.api_key_id
LEFT JOIN game_sessions gs ON ak.id = gs.api_key_id
WHERE ak.is_active = 1
GROUP BY ak.partner_name, ak.environment, ak.key_value, ak.created_at
HAVING MAX(gs.started_at) < DATE_SUB(NOW(), INTERVAL 30 DAY)
    OR MAX(gs.started_at) IS NULL
ORDER BY MAX(gs.started_at) DESC;

-- 7. テスト環境vs本番環境の比較
SELECT
    ak.environment AS 環境,
    COUNT(DISTINCT ak.id) AS パートナー数,
    COUNT(DISTINCT su.id) AS ユーザー総数,
    COUNT(DISTINCT gs.id) AS セッション総数,
    SUM(gs.points_consumed) AS 総消費ポイント,
    SUM(gs.points_won) AS 総獲得ポイント
FROM api_keys ak
LEFT JOIN sdk_users su ON ak.id = su.api_key_id
LEFT JOIN game_sessions gs ON ak.id = gs.api_key_id
WHERE ak.is_active = 1
GROUP BY ak.environment
ORDER BY ak.environment;
