# 中国向け NET8 ユーザー連携仕様書

**作成日**: 2025-12-31
**バージョン**: 1.0.0
**対象**: 中国チーム（KYチーム）統合

---

## 📋 目的

中国チーム側のユーザーアカウントとNET8側のユーザーアカウントを紐づけ、シームレスなゲームプレイ体験を提供する。

**韓国チームと同じ対応**: 既存の韓国チーム統合と同様のユーザー連携フローを適用し、通貨情報（CNY）を追加する。

---

## 🔗 ユーザー連携の全体フロー

### 1. 基本概念

```
中国側ユーザー（KYチーム管理）
    ↓ partner_user_id で紐づけ
NET8側SDKユーザー（sdk_users テーブル）
    ↓ member_no で紐づけ
NET8側会員（mst_member テーブル）
    ↓
ゲームセッション、ポイント管理
```

### 2. データベーステーブル構成

#### **sdk_users** - パートナーユーザー管理
```sql
CREATE TABLE sdk_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  partner_user_id VARCHAR(255) NOT NULL COMMENT '中国側のユーザーID',
  api_key_id INT UNSIGNED NOT NULL COMMENT 'APIキーID',
  member_no INT UNSIGNED COMMENT 'NET8側の会員番号（mst_member.member_no）',
  email VARCHAR(255),
  username VARCHAR(255),
  currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード',
  is_active TINYINT DEFAULT 1,
  metadata JSON COMMENT '追加ユーザー情報',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_partner_user (api_key_id, partner_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**重要カラム**:
- `partner_user_id`: 中国側が管理するユーザーID（必須）
- `api_key_id`: 中国チーム専用のAPIキーID
- `member_no`: NET8内部の会員番号（自動生成）
- `currency`: ユーザーのデフォルト通貨（CNY）

#### **user_balances** - ポイント残高管理
```sql
CREATE TABLE user_balances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL COMMENT 'sdk_users.id',
  balance DECIMAL(15,2) DEFAULT 0 COMMENT '現在の残高',
  currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード',
  total_deposited DECIMAL(15,2) DEFAULT 0 COMMENT '総入金額',
  total_consumed DECIMAL(15,2) DEFAULT 0 COMMENT '総消費額',
  total_won DECIMAL(15,2) DEFAULT 0 COMMENT '総獲得額',
  last_transaction_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### **mst_member** - NET8内部の会員テーブル（既存）
```sql
-- 既存テーブル（変更なし）
-- member_no でユーザーを識別
-- point カラムで残高を管理（両建て）
```

---

## 🔄 ユーザー連携フロー詳細

### シーケンス図

```
中国側システム                NET8 API                    データベース
     |                           |                              |
     |---(1) game_start.php----->|                              |
     |   { userId: "china_123",  |                              |
     |     currency: "CNY",       |                              |
     |     initialPoints: 1000 }  |                              |
     |                           |---(2) sdk_users検索--------->|
     |                           |<----ユーザー存在確認---------|
     |                           |                              |
     |                           |- (3a) 既存ユーザーなら取得---|
     |                           |   OR                         |
     |                           |- (3b) 新規ユーザーなら作成---|
     |                           |       - sdk_users INSERT     |
     |                           |       - mst_member作成       |
     |                           |       - user_balances作成    |
     |                           |                              |
     |                           |---(4) ポイント処理---------->|
     |                           |   balance += initialPoints   |
     |                           |                              |
     |                           |---(5) ゲームセッション作成-->|
     |                           |   + member_no保存            |
     |                           |   + currency保存             |
     |                           |                              |
     |<---(6) sessionId返却------|                              |
     |    gameUrl返却            |                              |
```

### (1) game_start.php リクエスト

**中国側からのAPI呼び出し**:
```javascript
const response = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/game_start.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer pk_china_xxxxx',  // 中国チーム専用APIキー
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    userId: 'china_user_12345',      // ← 中国側のユーザーID（必須）
    modelId: 'HOKUTO4GO',
    initialPoints: 1000,
    currency: 'CNY',                  // ← 通貨指定（必須）
    lang: 'zh',                       // ← 言語（オプション）
    balanceMode: 'add'                // ← add or set
  })
});
```

**パラメータ詳細**:
- `userId`: **必須** - 中国側が管理するユーザーの一意なID
  - 例: `"china_user_12345"`, `"wechat_openid_abc123"`, `"mobile_13800138000"`
- `currency`: **必須** - ユーザーの通貨（`"CNY"`）
- `initialPoints`: オプション - セッション開始時に付与するポイント（デフォルト: 0）
- `balanceMode`: オプション
  - `"add"`: 既存残高に加算（デフォルト）
  - `"set"`: 既存残高を上書き

### (2-3) ユーザー取得または作成

**処理ロジック**（user_helper.php: getOrCreateUser）:

```php
// 1. sdk_usersテーブルを検索
$stmt = $pdo->prepare("
    SELECT id, partner_user_id, member_no, currency
    FROM sdk_users
    WHERE api_key_id = :api_key_id
    AND partner_user_id = :partner_user_id
");

$user = $stmt->fetch();

if ($user) {
    // 既存ユーザー
    return $user;
} else {
    // 新規ユーザー作成
    // (a) mst_memberを作成（NET8内部会員）
    $memberNo = createMstMember($pdo, $apiKeyId, $partnerUserId);

    // (b) sdk_usersレコードを作成
    INSERT INTO sdk_users (
        partner_user_id,
        api_key_id,
        member_no,
        currency  -- ← 通貨を保存
    ) VALUES (
        'china_user_12345',
        123,
        456,
        'CNY'
    );

    // (c) user_balancesレコードを作成
    INSERT INTO user_balances (
        user_id,
        balance,
        currency  -- ← 通貨を保存
    ) VALUES (
        789,
        0,
        'CNY'
    );
}
```

### (4) ポイント処理

**balanceMode = "add"** の場合:
```sql
UPDATE user_balances
SET balance = balance + 1000,  -- 既存残高に加算
    total_deposited = total_deposited + 1000
WHERE user_id = 789;

-- mst_member.pointも同期（両建て管理）
UPDATE mst_member
SET point = point + 1000
WHERE member_no = 456;
```

**balanceMode = "set"** の場合:
```sql
UPDATE user_balances
SET balance = 1000,  -- 上書き
    total_deposited = 1000
WHERE user_id = 789;

UPDATE mst_member
SET point = 1000
WHERE member_no = 456;
```

### (5) ゲームセッション作成

```sql
INSERT INTO game_sessions (
    session_id,
    user_id,
    api_key_id,
    member_no,
    partner_user_id,
    machine_no,
    model_cd,
    points_consumed,
    currency,        -- ← 通貨情報を保存
    status,
    started_at
) VALUES (
    'sess_abc123',
    789,
    123,
    456,
    'china_user_12345',
    1,
    'HOKUTO4GO',
    100,
    'CNY',           -- ← 通貨
    'playing',
    NOW()
);
```

### (6) レスポンス

```json
{
  "success": true,
  "sessionId": "sess_abc123",
  "balance": {
    "amount": 900,
    "currency": "CNY",
    "formatted": "900元"
  },
  "gameUrl": "https://mgg-webservice-production.up.railway.app/data/play_embed/?sessionId=sess_abc123",
  "machineNo": 1,
  "modelName": "北斗の拳",
  "pointsConsumed": 100,
  "userId": "china_user_12345",  // ← パートナー側のユーザーID
  "memberNo": 456                // ← NET8内部の会員番号
}
```

---

## 🔐 セキュリティ考慮事項

### APIキー管理

**中国チーム専用のAPIキーを発行**:
```sql
INSERT INTO api_keys (
    key_value,
    key_type,
    name,
    environment,
    allowed_domains,
    rate_limit,
    is_active
) VALUES (
    'pk_china_xxxxxxxxxx',
    'public',
    'China Team Production Key',
    'production',
    '["https://china-partner.com", "https://game.china-partner.com"]',
    10000,  -- 1時間あたりのリクエスト上限
    1
);
```

### ユーザーID検証

**partner_user_id の一意性保証**:
```sql
-- UNIQUE制約で重複を防止
UNIQUE KEY unique_partner_user (api_key_id, partner_user_id)
```

**同じユーザーIDが異なるAPIキーで使用された場合**: 別ユーザーとして扱う

---

## 📊 データ整合性管理

### 両建て残高管理（二重管理）

**理由**: NET8既存システム（mst_member.point）との互換性維持

**同期処理**:
```php
// user_balances を更新
UPDATE user_balances SET balance = 900 WHERE user_id = 789;

// mst_member.point も同期
UPDATE mst_member SET point = 900 WHERE member_no = 456;
```

**トランザクション保証**:
```php
$pdo->beginTransaction();
try {
    // user_balances更新
    updateUserBalance($userId, $amount);

    // mst_member.point更新
    updateMemberPoint($memberNo, $amount);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## 🧪 テストケース

### ケース1: 新規ユーザー登録

**リクエスト**:
```json
{
  "userId": "china_new_user_001",
  "modelId": "HOKUTO4GO",
  "initialPoints": 1000,
  "currency": "CNY",
  "lang": "zh"
}
```

**期待動作**:
1. sdk_users に新規レコード作成
2. mst_member に新規レコード作成（member_no自動採番）
3. user_balances に初期残高1000 CNY作成
4. game_sessions 作成
5. レスポンスに sessionId と gameUrl を返す

**検証SQL**:
```sql
-- sdk_users確認
SELECT * FROM sdk_users WHERE partner_user_id = 'china_new_user_001';

-- user_balances確認
SELECT * FROM user_balances WHERE user_id = (上記のid);

-- mst_member確認
SELECT * FROM mst_member WHERE member_no = (上記のmember_no);
```

### ケース2: 既存ユーザーの再プレイ

**リクエスト**:
```json
{
  "userId": "china_existing_user_123",
  "modelId": "MILLIONGOD01",
  "initialPoints": 500,
  "currency": "CNY",
  "balanceMode": "add"
}
```

**期待動作**:
1. sdk_users から既存ユーザー取得
2. 既存残高 800 CNY に 500 CNY を加算 → 1300 CNY
3. game_sessions 新規作成（別セッションID）
4. 100 CNY消費 → 残高1200 CNY

### ケース3: 残高不足エラー

**リクエスト**:
```json
{
  "userId": "china_user_poor",
  "modelId": "HOKUTO4GO",
  "initialPoints": 50,
  "currency": "CNY",
  "consumeImmediately": true
}
```

**期待動作**:
1. 残高: 50 CNY
2. ゲーム価格: 100 CNY（例）
3. **HTTP 402** エラー返却
```json
{
  "error": "INSUFFICIENT_BALANCE",
  "message": "Insufficient points",
  "balance": 50,
  "required": 100,
  "currency": "CNY"
}
```

---

## 📝 実装チェックリスト

### データベース準備
- [ ] `sdk_users.currency` カラム追加
- [ ] `user_balances.currency` カラム追加
- [ ] `game_sessions.currency` カラム追加
- [ ] `mst_currency` テーブル作成
- [ ] CNYデータ登録

### API実装
- [ ] `game_start.php` で `currency` パラメータ受け取り
- [ ] `getOrCreateUser()` で `currency` を保存
- [ ] `getUserBalance()` で `currency` を返す
- [ ] `consumePoints()` で `currency` を考慮
- [ ] `depositPoints()` で `currency` を考慮

### レスポンス拡張
- [ ] `balance.currency` フィールド追加
- [ ] `balance.formatted` フィールド追加（"900元"）
- [ ] `userId` フィールド追加（パートナー側ID）
- [ ] `memberNo` フィールド追加（NET8側ID）

### テスト
- [ ] 新規ユーザー登録テスト
- [ ] 既存ユーザー再プレイテスト
- [ ] balanceModeテスト（add/set）
- [ ] 残高不足エラーテスト
- [ ] 通貨表示テスト（CNY → 元）

---

## 🌐 中国側の実装例

### JavaScript SDK 例

```javascript
class Net8ChinaSDK {
  constructor(apiKey) {
    this.apiKey = apiKey;
    this.baseUrl = 'https://mgg-webservice-production.up.railway.app/api/v1';
  }

  /**
   * ゲーム開始
   * @param {string} userId - 中国側のユーザーID（必須）
   * @param {string} modelId - 機種ID
   * @param {number} initialPoints - 初期ポイント
   * @returns {Promise<Object>} ゲームセッション情報
   */
  async startGame(userId, modelId, initialPoints = 1000) {
    const response = await fetch(`${this.baseUrl}/game_start.php`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        userId: userId,           // 中国側のユーザーID
        modelId: modelId,
        initialPoints: initialPoints,
        currency: 'CNY',          // 固定値
        lang: 'zh',               // 固定値
        balanceMode: 'add'
      })
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'API Error');
    }

    return await response.json();
  }

  /**
   * 残高照会
   * @param {string} userId - 中国側のユーザーID
   * @returns {Promise<Object>} 残高情報
   */
  async getBalance(userId) {
    const response = await fetch(`${this.baseUrl}/balance.php?userId=${userId}`, {
      headers: {
        'Authorization': `Bearer ${this.apiKey}`
      }
    });

    if (!response.ok) {
      throw new Error('Balance fetch failed');
    }

    return await response.json();
  }
}

// 使用例
const sdk = new Net8ChinaSDK('pk_china_xxxxxxxxxx');

// WeChatログイン後のユーザーIDを使用
const wechatOpenId = 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o';

try {
  const session = await sdk.startGame(wechatOpenId, 'HOKUTO4GO', 1000);
  console.log('ゲーム開始:', session.sessionId);
  console.log('残高:', session.balance.formatted);  // "900元"

  // iframeにゲームURLを設定
  document.getElementById('gameFrame').src = session.gameUrl;
} catch (error) {
  console.error('エラー:', error.message);
}
```

---

## 📌 重要ポイント

### ユーザーIDの選択

中国側で使用可能なID:
- **WeChat OpenID** - 推奨（一意性保証）
- **モバイル番号** - 可能（ハッシュ化推奨）
- **独自ユーザーID** - 可能
- **メールアドレス** - 可能

**要件**:
- 一意であること
- 永続的であること（変更されない）
- 255文字以内

### 通貨管理

- **Phase 1**: 1 CNY = 1ポイント（換算なし）
- **Phase 2**: 換算レート導入（将来対応）

### データプライバシー

- ユーザーの個人情報（名前、メール等）は `sdk_users.metadata` にJSON形式で保存
- 最小限の情報のみ保存を推奨
- GDPRやPIPL（中国個人情報保護法）に準拠

---

**次のステップ**:
1. 中国チーム専用のAPIキー発行
2. データベースマイグレーション実行
3. API実装（currency対応）
4. テスト環境で動作確認
5. 本番デプロイ
