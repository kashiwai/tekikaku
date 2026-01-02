# NET8チームからの回答 - SDK ユーザー管理について

**日付**: 2026年1月2日
**対応者**: NET8 SDK開発チーム

---

## 1. list_users.php の実装状況

### ✅ 実装完了しています

**エンドポイント**: `GET /api/v1/list_users.php`

**実装ファイル**: `/net8/02.ソースファイル/net8_html/api/v1/list_users.php`

**機能**:
- ユーザー一覧の取得
- プレフィックスフィルター対応（例: `kr_net8_`）
- 残高フィルター対応
- ページネーション対応（limit/offset）

**パラメータ**:
```
GET /api/v1/list_users.php?prefix=kr_net8_&hasBalance=true&limit=100&offset=0
Authorization: Bearer {API_KEY}
```

**レスポンス例**:
```json
{
  "success": true,
  "total": 150,
  "limit": 100,
  "offset": 0,
  "count": 100,
  "users": [
    {
      "user_id": "kr_net8_user_001",
      "balance": "45000.00",
      "created_at": "2025-12-20 10:30:00",
      "updated_at": "2026-01-02 09:15:00",
      "total_games": 25,
      "last_played_at": "2026-01-02 09:15:00",
      "total_consumed": "55000.00",
      "total_won": "100000.00"
    }
  ]
}
```

---

## 2. データベーステーブル構造

### ⚠️ 注意: テーブル名が異なります

韓国チームが想定している `mgg_users` テーブルは使用していません。
代わりに、以下の3つのテーブルで管理しています：

### テーブル1: `sdk_users`
SDK経由で作成されたユーザーの管理テーブル

```sql
CREATE TABLE sdk_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_user_id VARCHAR(255) NOT NULL COMMENT 'パートナー側のユーザーID（例: kr_net8_user_001）',
    api_key_id INT NOT NULL COMMENT 'APIキーID',
    member_no INT COMMENT 'NET8側のユーザーID（mst_member.member_no）',
    email VARCHAR(255),
    username VARCHAR(255),
    currency VARCHAR(3) DEFAULT 'JPY',
    metadata TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_partner_user (api_key_id, partner_user_id),
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id),
    FOREIGN KEY (member_no) REFERENCES mst_member(member_no)
);
```

### テーブル2: `user_balances`
SDK ユーザーの残高管理テーブル

```sql
CREATE TABLE user_balances (
    user_id INT PRIMARY KEY COMMENT 'sdk_users.id',
    balance DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'JPY',
    total_deposited DECIMAL(10,2) DEFAULT 0.00,
    total_consumed DECIMAL(10,2) DEFAULT 0.00,
    total_won DECIMAL(10,2) DEFAULT 0.00,
    last_transaction_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES sdk_users(id)
);
```

### テーブル3: `mst_member`
NET8 本体のユーザーテーブル（既存）

```sql
CREATE TABLE mst_member (
    member_no INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(100),
    mail VARCHAR(255) UNIQUE,
    pass VARCHAR(255),
    point DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'JPY',
    -- その他のカラム省略
);
```

---

## 3. game_start.php でのユーザー自動生成

### ✅ 自動生成機能は実装済みです

**実装場所**: `/api/v1/game_start.php` 249行目

**処理フロー**:

```php
// 1. ユーザーを取得または作成
$user = getOrCreateUser($pdo, $apiKeyId, $partnerUserId);
$userId = $user['id']; // sdk_users.id
$memberNo = $user['member_no']; // mst_member.member_no
```

**getOrCreateUser関数の動作**:

```php
function getOrCreateUser($pdo, $apiKeyId, $partnerUserId) {
    // 1. sdk_usersテーブルで既存ユーザーを検索
    $stmt = $pdo->prepare("
        SELECT id, partner_user_id, api_key_id, member_no
        FROM sdk_users
        WHERE api_key_id = :api_key_id
        AND partner_user_id = :partner_user_id
    ");
    $stmt->execute([
        'api_key_id' => $apiKeyId,
        'partner_user_id' => $partnerUserId
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 既存ユーザーを返す
        return $user;
    }

    // 2. 新規ユーザーの場合、mst_memberを作成
    $mstMember = getOrCreateMstMember($pdo, $apiKeyId, $partnerUserId);
    $memberNo = $mstMember['member_no'];

    // 3. sdk_usersに登録
    $stmt = $pdo->prepare("
        INSERT INTO sdk_users (partner_user_id, api_key_id, member_no, is_active)
        VALUES (:partner_user_id, :api_key_id, :member_no, 1)
    ");
    $stmt->execute([
        'partner_user_id' => $partnerUserId,
        'api_key_id' => $apiKeyId,
        'member_no' => $memberNo
    ]);
    $userId = $pdo->lastInsertId();

    // 4. user_balancesに初期残高を作成
    $stmt = $pdo->prepare("
        INSERT INTO user_balances (user_id, balance)
        VALUES (:user_id, 10000)
    ");
    $stmt->execute(['user_id' => $userId]);

    return [
        'id' => $userId,
        'partner_user_id' => $partnerUserId,
        'api_key_id' => $apiKeyId,
        'member_no' => $memberNo
    ];
}
```

### 実行例:

**リクエスト**:
```json
POST /api/v1/game_start.php
Authorization: Bearer pk_korea_xxx

{
  "userId": "kr_net8_user_001",
  "modelId": "HOKUTO4GO",
  "initialPoints": 50000,
  "balanceMode": "set"
}
```

**処理**:
1. `sdk_users` で `kr_net8_user_001` を検索
2. 存在しない場合:
   - `mst_member` に新規ユーザー作成（例: member_no=127）
   - `sdk_users` に登録（partner_user_id='kr_net8_user_001', member_no=127）
   - `user_balances` に初期残高を設定（balance=50000）
3. 存在する場合:
   - 既存レコードを使用
   - `initialPoints` が指定されている場合は残高を更新

---

## 4. DATABASE_ERROR の詳細ログ

### 🔧 エラーログを改善しました

**修正内容**:

```php
// 修正前
catch (PDOException $e) {
    error_log("list_users.php error: " . $e->getMessage());
    // ...
}

// 修正後
catch (PDOException $e) {
    error_log("❌ list_users.php DATABASE ERROR:");
    error_log("  Message: " . $e->getMessage());
    error_log("  Code: " . $e->getCode());
    error_log("  File: " . $e->getFile());
    error_log("  Line: " . $e->getLine());
    error_log("  Stack trace: " . $e->getTraceAsString());
    // ...
}
```

### デバッグ用エンドポイント追加

**新規API**: `GET /api/v1/debug_sdk_session.php`

データベースの状態を確認できるデバッグAPIを追加しました。

**使用例**:
```
GET /api/v1/debug_sdk_session.php?machineNo=9999&memberNo=127
```

**レスポンス**:
```json
{
  "success": true,
  "machineNo": 9999,
  "memberNo": "127",
  "game_sessions": [
    {
      "session_id": "gs_xxx",
      "member_no": "127",
      "partner_user_id": "kr_net8_user_001",
      "status": "playing",
      "currency": "JPY"
    }
  ],
  "mst_member": {
    "member_no": "127",
    "nickname": "SDK123456",
    "mail": "sdk_korea_kr_net8_user_001@net8.local",
    "point": "50000.00",
    "currency": "JPY"
  },
  "sdk_login_check": {
    "should_auto_login": true,
    "has_session": true,
    "has_member_no": true,
    "status_valid": true
  }
}
```

---

## 5. データベース接続確認

### ✅ 接続は正常です

**接続情報**:
- ホスト: Railway MySQL（production）
- データベース: net8_db
- 文字セット: utf8mb4
- PDO接続: 正常

**テーブル存在確認**:
```sql
-- 以下のテーブルはすべて存在します
SHOW TABLES LIKE 'sdk_users';       -- ✅ 存在
SHOW TABLES LIKE 'user_balances';   -- ✅ 存在
SHOW TABLES LIKE 'mst_member';      -- ✅ 存在
SHOW TABLES LIKE 'game_sessions';   -- ✅ 存在
SHOW TABLES LIKE 'api_keys';        -- ✅ 存在
```

---

## 6. 韓国チーム用 - 簡易確認方法

### 方法1: list_users.php でユーザー一覧確認

```bash
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_users.php?prefix=kr_net8_" \
  -H "Authorization: Bearer pk_korea_xxx"
```

### 方法2: game_start.php でテストユーザー作成

```bash
curl -X POST "https://mgg-webservice-production.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer pk_korea_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "kr_net8_test_001",
    "modelId": "HOKUTO4GO",
    "initialPoints": 50000,
    "balanceMode": "set"
  }'
```

### 方法3: デバッグAPIで状態確認

```bash
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/debug_sdk_session.php?machineNo=9999&memberNo=127"
```

---

## 7. まとめ

| 項目 | 状況 | 詳細 |
|------|------|------|
| **list_users.php** | ✅ 実装済み | `/api/v1/list_users.php` で稼働中 |
| **テーブル構造** | ⚠️ 異なる | `mgg_users` ではなく `sdk_users` + `user_balances` + `mst_member` |
| **ユーザー自動生成** | ✅ 実装済み | `game_start.php` で `getOrCreateUser()` により自動生成 |
| **データベース接続** | ✅ 正常 | Railway MySQL に正常接続 |
| **エラーログ** | 🔧 改善予定 | 詳細ログを追加中 |

---

## 8. 次のステップ

### すぐに実施してください:

1. **list_users.php をテスト**:
   ```bash
   curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_users.php?prefix=kr_net8_" \
     -H "Authorization: Bearer {韓国側APIキー}"
   ```

2. **エラーが出る場合**:
   - エラーメッセージ全文を共有してください
   - Railwayログを確認してください（https://railway.app）

3. **DATABASE_ERROR が出る場合**:
   - APIキーが正しいか確認
   - テーブル権限を確認（SELECT, INSERT, UPDATE）

---

## 連絡先

追加の質問や問題がある場合は、以下の情報を共有してください：

1. エラーメッセージ全文
2. リクエスト内容（curlコマンド）
3. 期待する動作

NET8 SDK開発チーム
