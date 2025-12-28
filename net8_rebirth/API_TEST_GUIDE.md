# NET8 API実装テストガイド
**作成日**: 2025-12-28
**対象**: 韓国チーム統合用API修正（全7ケース）

## ✅ 構文チェック結果

全てのPHPファイルで構文エラーなし：
- ✅ `set_balance.php` - OK
- ✅ `adjust_balance.php` - OK
- ✅ `list_users.php` - OK
- ✅ `game_start.php` - OK
- ✅ `play_history.php` - OK
- ✅ `play_embed/index.php` - OK
- ✅ `play_embed/lang/ja.php` - OK
- ✅ `play_embed/lang/ko.php` - OK
- ✅ `play_embed/lang/en.php` - OK
- ✅ `play_embed/lang/zh.php` - OK

---

## 📋 実装済みAPI一覧

### Case 0: set_balance.php（新規）
**機能**: ユーザー残高を絶対値で設定

**エンドポイント**:
```
POST /api/v1/set_balance.php
```

**テスト方法**:
```bash
curl -X POST https://your-domain.com/api/v1/set_balance.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test001",
    "balance": 10000,
    "reason": "initial_deposit"
  }'
```

**期待レスポンス**:
```json
{
  "success": true,
  "userId": "kr_net8_test001",
  "oldBalance": 0,
  "newBalance": 10000,
  "timestamp": "2025-12-28T15:30:00+09:00"
}
```

**検証ポイント**:
- [ ] 既存残高を無視して新しい値が設定される
- [ ] user_balances と mst_member.point の両方が更新される
- [ ] 負の値はエラーとなる

---

### Case 1: game_start.php - balanceMode（修正）
**機能**: 残高処理モード選択

**エンドポイント**:
```
POST /api/v1/game_start.php
```

**テスト方法（setモード）**:
```bash
curl -X POST https://your-domain.com/api/v1/game_start.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test001",
    "machineNo": "001",
    "initialPoints": 5000,
    "balanceMode": "set"
  }'
```

**テスト方法（addモード）**:
```bash
curl -X POST https://your-domain.com/api/v1/game_start.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test001",
    "machineNo": "001",
    "initialPoints": 5000,
    "balanceMode": "add"
  }'
```

**検証ポイント**:
- [ ] `balanceMode: "set"` - 既存残高10,000が5,000に置き換わる
- [ ] `balanceMode: "add"` - 既存残高10,000に5,000が加算され15,000になる
- [ ] デフォルトは `"add"` モード

---

### Case 2: adjust_balance.php（新規）
**機能**: ユーザー残高を相対値で調整

**エンドポイント**:
```
POST /api/v1/adjust_balance.php
```

**テスト方法（加算）**:
```bash
curl -X POST https://your-domain.com/api/v1/adjust_balance.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test001",
    "amount": 3000,
    "reason": "bonus_points"
  }'
```

**テスト方法（減算）**:
```bash
curl -X POST https://your-domain.com/api/v1/adjust_balance.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test001",
    "amount": -2000,
    "reason": "fee_deduction"
  }'
```

**期待レスポンス**:
```json
{
  "success": true,
  "userId": "kr_net8_test001",
  "oldBalance": 10000,
  "adjustment": 3000,
  "newBalance": 13000,
  "timestamp": "2025-12-28T15:35:00+09:00"
}
```

**検証ポイント**:
- [ ] 正の値で加算される
- [ ] 負の値で減算される
- [ ] 残高不足時はエラーとなる（新残高が0未満）
- [ ] amount=0 はエラーとなる

---

### Case 3: play_history.php - タイムアウト検知（修正）
**機能**: タイムアウトセッション検知と自動終了

**エンドポイント**:
```
GET /api/v1/play_history.php
```

**テスト方法（タイムアウト検知のみ）**:
```bash
curl -X GET "https://your-domain.com/api/v1/play_history.php?userId=kr_net8_test001&status=timeout" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**テスト方法（自動終了あり）**:
```bash
curl -X GET "https://your-domain.com/api/v1/play_history.php?userId=kr_net8_test001&status=timeout&autoClose=true" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**テスト方法（特定セッション取得）**:
```bash
curl -X GET "https://your-domain.com/api/v1/play_history.php?sessionId=SESSION_ID_HERE" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**期待レスポンス（autoClose有効）**:
```json
{
  "success": true,
  "data": [
    {
      "session_id": "abc123",
      "computed_status": "timeout",
      "elapsed_minutes": 65,
      "auto_closed": true,
      "auto_close_time": "2025-12-28T15:40:00+09:00",
      "final_balance": 8500
    }
  ],
  "pagination": {
    "total": 1,
    "limit": 20,
    "offset": 0,
    "hasMore": false
  },
  "autoClosedCount": 1,
  "autoClosedSessions": ["abc123"]
}
```

**検証ポイント**:
- [ ] 60分以上のセッションが `computed_status: "timeout"` になる
- [ ] `autoClose=true` でタイムアウトセッションが自動終了される
- [ ] reserved_points が返金される
- [ ] `status=active/completed/timeout` でフィルタリングできる

---

### Case 4: play_embed - 多言語対応（修正）
**機能**: プレイヤーUI多言語化

**エンドポイント**:
```
GET /data/play_embed/index.php
```

**テスト方法（日本語）**:
```
https://your-domain.com/data/play_embed/index.php?NO=001&sessionId=SESSION_ID&lang=ja
```

**テスト方法（韓国語）**:
```
https://your-domain.com/data/play_embed/index.php?NO=001&sessionId=SESSION_ID&lang=ko
```

**テスト方法（英語）**:
```
https://your-domain.com/data/play_embed/index.php?NO=001&sessionId=SESSION_ID&lang=en
```

**テスト方法（中国語）**:
```
https://your-domain.com/data/play_embed/index.php?NO=001&sessionId=SESSION_ID&lang=zh
```

**検証ポイント**:
- [ ] `lang=ja` で日本語UI表示
- [ ] `lang=ko` で韓国語UI表示
- [ ] `lang=en` で英語UI表示
- [ ] `lang=zh` で中国語UI表示
- [ ] langパラメータ省略時は日本語がデフォルト
- [ ] サポート外の言語は日本語にフォールバック
- [ ] JavaScript変数 `languageMode` が正しく設定される

**UI確認項目**:
- [ ] ローディング画面のテキスト
- [ ] ナビゲーションバーのラベル
- [ ] コントロールパネルのボタン
- [ ] モーダルのタイトルと説明文
- [ ] エラーメッセージ

---

### Case 5: list_users.php（新規）
**機能**: ユーザー一覧取得

**エンドポイント**:
```
GET /api/v1/list_users.php
```

**テスト方法（プレフィックスフィルタ）**:
```bash
curl -X GET "https://your-domain.com/api/v1/list_users.php?prefix=kr_net8_" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**テスト方法（残高あるユーザーのみ）**:
```bash
curl -X GET "https://your-domain.com/api/v1/list_users.php?hasBalance=true&limit=50" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**期待レスポンス**:
```json
{
  "success": true,
  "total": 150,
  "limit": 100,
  "offset": 0,
  "count": 100,
  "users": [
    {
      "user_id": "kr_net8_test001",
      "balance": 10000,
      "created_at": "2025-12-25 10:00:00",
      "updated_at": "2025-12-28 15:30:00",
      "total_games": 25,
      "last_played_at": "2025-12-28 14:00:00",
      "total_consumed": 50000,
      "total_won": 45000
    }
  ]
}
```

**検証ポイント**:
- [ ] `prefix` でユーザーIDプレフィックスフィルタリング
- [ ] `hasBalance=true` で残高あるユーザーのみ取得
- [ ] `limit` でページネーション（デフォルト100、最大1000）
- [ ] `offset` でページネーションオフセット
- [ ] ゲーム統計情報も含まれる

---

### Case 6: game_start.php - consumeImmediately（修正）
**機能**: ポイント消費タイミング制御

**エンドポイント**:
```
POST /api/v1/game_start.php
```

**テスト方法（即座に消費）**:
```bash
curl -X POST https://your-domain.com/api/v1/game_start.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test001",
    "machineNo": "001",
    "initialPoints": 5000,
    "consumeImmediately": true
  }'
```

**テスト方法（予約のみ）**:
```bash
curl -X POST https://your-domain.com/api/v1/game_start.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test001",
    "machineNo": "001",
    "initialPoints": 5000,
    "consumeImmediately": false
  }'
```

**検証ポイント**:
- [ ] `consumeImmediately: true` - ポイントが即座に消費される
- [ ] `consumeImmediately: false` - ポイントが予約される（reserved_points）
- [ ] デフォルトは `true` （即座に消費）
- [ ] false時は残高チェックがスキップされる
- [ ] game_sessionsテーブルに `reserved_points` と `balance_mode` が記録される

---

## 🧪 統合テストシナリオ

### シナリオ1: 新規ユーザー登録から初回プレイまで

```bash
# 1. 残高設定（Case 0）
curl -X POST https://your-domain.com/api/v1/set_balance.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"userId": "kr_test_user", "balance": 10000, "reason": "初期入金"}'

# 2. ゲーム開始（Case 1 & 6）
curl -X POST https://your-domain.com/api/v1/game_start.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_test_user",
    "machineNo": "001",
    "initialPoints": 0,
    "balanceMode": "add",
    "consumeImmediately": true
  }'

# 3. プレイ履歴確認（Case 3）
curl -X GET "https://your-domain.com/api/v1/play_history.php?userId=kr_test_user" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### シナリオ2: ボーナスポイント付与

```bash
# 1. ボーナス加算（Case 2）
curl -X POST https://your-domain.com/api/v1/adjust_balance.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"userId": "kr_test_user", "amount": 5000, "reason": "ウェルカムボーナス"}'

# 2. 残高確認
curl -X GET "https://your-domain.com/api/v1/list_users.php?prefix=kr_test_user" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### シナリオ3: タイムアウトセッション処理

```bash
# 1. タイムアウトセッション検出と自動終了（Case 3）
curl -X GET "https://your-domain.com/api/v1/play_history.php?status=timeout&autoClose=true" \
  -H "Authorization: Bearer YOUR_API_KEY"

# 2. 全ユーザーリスト取得（Case 5）
curl -X GET "https://your-domain.com/api/v1/list_users.php?hasBalance=true" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

---

## 📊 データベース確認クエリ

### Case 0, 1, 2 確認用
```sql
-- 残高確認
SELECT user_id, balance, updated_at
FROM user_balances
WHERE user_id LIKE 'kr_%'
ORDER BY updated_at DESC
LIMIT 10;

-- mst_member同期確認
SELECT m.login_id, m.point, ub.balance
FROM mst_member m
LEFT JOIN user_balances ub ON m.login_id = ub.user_id
WHERE m.login_id LIKE 'kr_%';
```

### Case 1, 6 確認用
```sql
-- game_sessionsのbalance_modeとreserved_points確認
SELECT
    session_id,
    user_id,
    points_consumed,
    reserved_points,
    balance_mode,
    status,
    started_at
FROM game_sessions
WHERE balance_mode IS NOT NULL OR reserved_points > 0
ORDER BY started_at DESC
LIMIT 10;
```

### Case 3 確認用
```sql
-- タイムアウトセッション検出
SELECT
    session_id,
    user_id,
    started_at,
    ended_at,
    TIMESTAMPDIFF(MINUTE, started_at, NOW()) as elapsed_minutes,
    CASE
        WHEN ended_at IS NULL AND TIMESTAMPDIFF(MINUTE, started_at, NOW()) > 60 THEN 'timeout'
        WHEN ended_at IS NULL THEN 'active'
        ELSE status
    END as computed_status
FROM game_sessions
WHERE ended_at IS NULL
ORDER BY started_at ASC;
```

---

## ⚠️ 注意事項

1. **APIキー認証必須**
   - 全APIで `Authorization: Bearer YOUR_API_KEY` ヘッダーが必要
   - JWT形式またはAPIキー直接指定に対応

2. **CORS設定**
   - play_embed/index.phpは `Access-Control-Allow-Origin: *` に設定済み
   - 韓国側フロントエンドからのアクセスが可能

3. **デフォルト動作の維持**
   - 全てのパラメータはオプション
   - 既存の挙動を壊さないように実装

4. **エラーハンドリング**
   - 全APIで適切なHTTPステータスコードを返却
   - エラーメッセージは `error` と `message` フィールドで提供

5. **トランザクション管理**
   - 残高操作は全てトランザクション内で実行
   - user_balances と mst_member.point の同期を保証

---

## 🚀 デプロイ前チェックリスト

- [ ] 全PHPファイルの構文チェック完了
- [ ] 各APIの基本動作確認
- [ ] データベーステーブル存在確認
- [ ] APIキー認証動作確認
- [ ] エラーハンドリング確認
- [ ] 言語ファイル読み込み確認
- [ ] トランザクション動作確認
- [ ] CORS設定確認

---

## 📝 次のステップ

1. **ローカル/ステージング環境でのテスト実施**
2. **韓国チームへのAPI仕様共有**
3. **本番環境へのデプロイ**
4. **モニタリング設定**

---

**テスト完了日**: _______
**テスト担当者**: _______
**承認者**: _______
