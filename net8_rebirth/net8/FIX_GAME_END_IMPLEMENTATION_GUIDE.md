# 🔧 NET8 game_end.php エラー修正 - 実装ガイド

**作成日**: 2025-12-23
**対象エラー**: SQLSTATE[22001] および SQLSTATE[01000]
**修正方法**: データベーススキーマ拡張（方針A）

---

## 📋 修正概要

### エラー内容
```
SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'out_action_type' at row 1
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'result' at row 1
```

### 根本原因
1. **out_action_type カラム**: `char(2)` で定義されているが、`'sdk_end'` (7文字) を挿入しようとしている
2. **result カラム**: ENUMに `'completed'`, `'cancelled'` が定義されていない

### 修正内容
1. `his_play.out_action_type`: `char(2)` → `varchar(20)`
2. `game_sessions.result`: ENUM に `'completed'`, `'cancelled'` を追加

---

## 🚀 実行手順

### **STEP 1: データベースバックアップ（必須）**

本番環境で実行する前に、必ずバックアップを取得してください。

```bash
# GCP Cloud SQL のバックアップ
# Railway Dashboard → Database → Backups → Create Backup

# または mysqldump でバックアップ
mysqldump -h 136.116.70.86 -u net8tech001 -p net8_dev \
  --tables his_play game_sessions > backup_$(date +%Y%m%d_%H%M%S).sql
```

---

### **STEP 2: SQLファイルの確認**

修正SQLファイルが正しく作成されていることを確認します。

```bash
# ファイルの存在確認
ls -lh /Users/kotarokashiwai/net8_rebirth/net8/fix_game_end_schema.sql

# 内容確認
cat /Users/kotarokashiwai/net8_rebirth/net8/fix_game_end_schema.sql
```

---

### **STEP 3: 本番データベースでSQL実行**

#### **方法A: MySQLクライアント経由（推奨）**

```bash
# データベースに接続
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev

# SQLファイルを実行
mysql> source /Users/kotarokashiwai/net8_rebirth/net8/fix_game_end_schema.sql;

# または一行で実行
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev < /Users/kotarokashiwai/net8_rebirth/net8/fix_game_end_schema.sql
```

#### **方法B: Railway Dashboard 経由**

1. Railway Dashboard にログイン
2. プロジェクト `mmg2501` → Database タブ
3. Query タブで SQL を実行

```sql
-- his_play.out_action_type 拡張
ALTER TABLE his_play
MODIFY COLUMN out_action_type VARCHAR(20) DEFAULT NULL;

-- game_sessions.result ENUM拡張
ALTER TABLE game_sessions
MODIFY COLUMN result ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout', 'completed', 'cancelled')
DEFAULT 'playing';
```

---

### **STEP 4: 修正結果の確認**

```sql
-- スキーマ確認
SHOW COLUMNS FROM his_play WHERE Field = 'out_action_type';
SHOW COLUMNS FROM game_sessions WHERE Field = 'result';

-- 期待される結果
-- his_play.out_action_type: varchar(20)
-- game_sessions.result: enum('playing','win','lose','draw','error','timeout','completed','cancelled')
```

---

## 🧪 動作テスト

### **テスト1: game_end API呼び出し**

```bash
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_end.php \
  -H "Authorization: Bearer pk_live_xxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "gs_test_session_20251223",
    "result": "completed",
    "pointsWon": 500
  }'
```

**期待されるレスポンス**:
```json
{
  "success": true,
  "sessionId": "gs_test_session_20251223",
  "result": "completed",
  "pointsWon": 500,
  "newBalance": 10500
}
```

**エラーが出ないことを確認**:
- ✅ `SQLSTATE[22001]` が発生しない
- ✅ `SQLSTATE[01000]` が発生しない

---

### **テスト2: データベース確認**

```sql
-- game_sessions テーブルを確認
SELECT session_id, result, status, points_won
FROM game_sessions
WHERE session_id = 'gs_test_session_20251223';

-- his_play テーブルを確認
SELECT machine_no, member_no, out_action_type, out_point
FROM his_play
WHERE out_action_type = 'sdk_end'
ORDER BY start_dt DESC
LIMIT 5;
```

**期待される結果**:
- `game_sessions.result` に `'completed'` が正しく格納されている
- `his_play.out_action_type` に `'sdk_end'` が正しく格納されている

---

## 📊 影響範囲の確認

### **修正の影響**

| 項目 | 影響 | 詳細 |
|------|------|------|
| **既存データ** | ✅ 影響なし | ALTER TABLE は既存レコードを保護 |
| **ダウンタイム** | ⚠️ 数秒 | テーブルロック発生（短時間） |
| **アプリケーション** | ✅ 影響なし | game_end.php はそのまま動作 |
| **後方互換性** | ✅ 維持 | char(2) で格納されていた既存値も有効 |

### **ロールバック手順（万が一のため）**

問題が発生した場合の戻し方:

```sql
-- 元に戻す（ただし 'completed' や 'cancelled' が含まれるレコードはエラーになる）
ALTER TABLE his_play
MODIFY COLUMN out_action_type CHAR(2) DEFAULT NULL;

ALTER TABLE game_sessions
MODIFY COLUMN result ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout')
DEFAULT 'playing';
```

**⚠️ 注意**: ロールバックは新しい値が使用されていない場合のみ可能です。

---

## ✅ チェックリスト

実行前に以下を確認してください:

- [ ] データベースのバックアップを取得した
- [ ] `fix_game_end_schema.sql` の内容を確認した
- [ ] 本番環境のデータベース接続情報を確認した
- [ ] SQL実行のタイミングを決定した（アクセスの少ない時間帯推奨）
- [ ] ロールバック手順を理解した

実行後に以下を確認してください:

- [ ] `SHOW COLUMNS` でスキーマ変更を確認した
- [ ] game_end API のテストリクエストが成功した
- [ ] エラーログに `SQLSTATE[22001]` が出ていない
- [ ] `his_play` と `game_sessions` にデータが正しく格納されている

---

## 🆘 トラブルシューティング

### **問題1: ALTER TABLE がタイムアウトする**

**原因**: テーブルにロックがかかっている
**解決策**:
```sql
-- 現在のロック状況を確認
SHOW PROCESSLIST;

-- 必要に応じて接続をkill
KILL <process_id>;
```

### **問題2: ENUM値が反映されない**

**原因**: キャッシュの問題
**解決策**:
```sql
-- テーブルをフラッシュ
FLUSH TABLES game_sessions;
```

### **問題3: 既存データとの競合**

**原因**: すでに 'completed' や 'cancelled' を含むレコードがある
**解決策**: 問題なし（拡張なので既存値は保護されます）

---

## 📞 サポート

問題が発生した場合:
1. エラーログを確認: `net8/02.ソースファイル/net8_html/_lib/log/error_log.txt`
2. データベース接続を確認: `mysql -h 136.116.70.86 -u net8tech001 -p`
3. Railway Dashboard でデータベースステータスを確認

---

**実装完了日**: _________
**実装者**: _________
**確認者**: _________
