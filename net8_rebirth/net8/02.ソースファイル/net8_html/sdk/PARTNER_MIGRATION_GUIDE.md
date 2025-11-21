# 既存パートナー様向け NET8 SDK v1.1.0 移行ガイド

## 🔄 概要

このガイドは、NET8 SDK v1.0.x を既に利用されている既存パートナー様が、v1.1.0 に移行する際の手順を説明します。

**重要**: v1.1.0 は後方互換性があります。既存のコードは引き続き動作しますが、新機能を活用するには若干の修正が必要です。

---

## 📋 移行前の確認事項

### 1. 現在のアカウント状況確認

まず、あなたのパートナーアカウントとAPIキーの状況を確認してください。

**SQL Studioで以下のSQLを実行**:
```sql
-- あなたのパートナー情報確認
SELECT
    ak.id, ak.partner_name, ak.key_value, ak.environment, ak.is_active,
    COUNT(DISTINCT su.id) AS users, COUNT(DISTINCT gs.id) AS sessions
FROM api_keys ak
LEFT JOIN sdk_users su ON ak.id = su.api_key_id
LEFT JOIN game_sessions gs ON ak.id = gs.api_key_id
WHERE ak.partner_name = 'YOUR_PARTNER_NAME'  -- ← あなたのパートナー名
GROUP BY ak.id;
```

確認項目:
- ✅ APIキーが有効（`is_active = 1`）
- ✅ 環境が正しい（`test` / `staging` / `production`）
- ✅ ユーザー数とセッション数が正常

---

### 2. 既存のSDKバージョン確認

現在使用しているSDKのバージョンを確認してください。

```javascript
console.log('SDK Version:', Net8.version);
// v1.0.1 または v1.0.0 と表示される場合は更新が必要
```

---

## 🚀 移行手順

### ステップ1: SDKファイルの更新（5分）

#### 1-A. CDN経由で利用している場合

**従来の記述**:
```html
<script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-v1.0.1.js"></script>
```

**新しい記述**:
```html
<script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
```

#### 1-B. ローカルファイルを利用している場合

最新版をダウンロードしてください:
- SDK本体: https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js
- UIコンポーネント: https://mgg-webservice-production.up.railway.app/sdk/net8-ui-components.js

---

### ステップ2: コードの更新（10-30分）

#### 2-A. 最小限の変更（既存機能のみ利用）

**後方互換性があるため、コード変更は不要です**:

```javascript
// v1.0.1 のコード（そのまま動作します）
await Net8.init('YOUR_API_KEY');

const game = Net8.createGame({
  model: 'HOKUTO4GO',
  container: '#game-container'
});

await game.start();
```

#### 2-B. 新機能を活用する場合（推奨）

**ユーザーID連携を追加**:

```javascript
await Net8.init('YOUR_API_KEY');

const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: getUserId(),  // ← NEW: あなたのシステムのユーザーID
  container: '#game-container'
});

// 新イベントをリッスン
game.on('started', (data) => {
  console.log('消費ポイント:', data.pointsConsumed);
});

game.on('end', (result) => {
  console.log('獲得ポイント:', result.pointsWon);
  console.log('新残高:', result.newBalance);
});

await game.start();
```

---

### ステップ3: データ移行（必要な場合）

#### 3-A. 既存ユーザーの移行

v1.0.x で独自にユーザー管理していた場合、v1.1.0 のユーザーテーブルに移行する必要があります。

**手順**:

1. 既存ユーザーリストをエクスポート
2. 以下のSQLで一括登録:

```sql
-- 既存ユーザーをsdkusersに移行
INSERT INTO sdk_users (partner_user_id, api_key_id, email, username, is_active)
SELECT
    your_user_id,
    1,  -- ← あなたのAPIキーID
    your_email,
    your_username,
    1
FROM your_existing_user_table
WHERE 条件;

-- 初期残高を設定
INSERT INTO user_balances (user_id, balance, total_deposited)
SELECT
    su.id,
    10000,  -- ← 初期残高
    10000
FROM sdk_users su
WHERE su.api_key_id = 1  -- ← あなたのAPIキーID
AND NOT EXISTS (
    SELECT 1 FROM user_balances ub WHERE ub.user_id = su.id
);
```

#### 3-B. 既存ポイントの移行

独自のポイントシステムがある場合、残高を同期してください:

```sql
-- 既存ポイント残高を反映
UPDATE user_balances ub
JOIN sdk_users su ON ub.user_id = su.id
JOIN your_points_table yp ON su.partner_user_id = yp.user_id
SET
    ub.balance = yp.current_points,
    ub.total_deposited = yp.total_deposited,
    ub.total_consumed = yp.total_consumed,
    ub.total_won = yp.total_won
WHERE su.api_key_id = 1;  -- ← あなたのAPIキーID
```

---

### ステップ4: テスト（15-30分）

#### 4-A. テスト環境で動作確認

```javascript
// テスト用APIキーで確認
await Net8.init('pk_demo_12345');

const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: 'test_user_001',
  container: '#game-container'
});

game.on('started', (data) => {
  console.log('✅ ゲーム開始:', data);
});

game.on('end', (result) => {
  console.log('✅ ゲーム終了:', result);
});

await game.start();
```

**確認項目**:
- ✅ ゲームが正常に起動する
- ✅ `started` イベントが発火する
- ✅ ポイントが正しく消費される
- ✅ `end` イベントで結果データが取得できる
- ✅ ポイントが正しく払い出される

#### 4-B. 本番環境でスモークテスト

**段階的にロールアウト**:
1. 管理者アカウントでテスト
2. 限定ユーザー（10-20名）で先行利用
3. 全ユーザーに展開

---

## 🆕 v1.1.0 の新機能活用ガイド

### 1. ポイント管理の自動化

```javascript
// v1.0.x: 手動でポイント管理
// - ゲーム開始前にポイントチェック
// - 独自のデータベースで残高管理
// - ゲーム終了後に手動で払い出し

// v1.1.0: 自動管理
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: 'user_12345',  // ← これだけでOK
  container: '#game-container'
});

game.on('started', (data) => {
  // 自動的にポイント消費済み
  updateUI({
    balance: data.balance,
    consumed: data.pointsConsumed
  });
});

game.on('end', (result) => {
  // 自動的にポイント払い出し済み
  updateUI({
    won: result.pointsWon,
    newBalance: result.newBalance
  });
});
```

### 2. プレイ中のボーナスポイント付与

```javascript
// ログインボーナス、デイリーボーナス等を付与
const result = await game.addPoints(500, 'ログインボーナス');
console.log('新残高:', result.transaction.balanceAfter);
```

### 3. リアルタイムゲーム状態取得

```javascript
// 1秒ごとに状態を更新
const interval = setInterval(() => {
  const state = game.getGameState();
  updateGameUI({
    credit: state.credit,
    playpoint: state.playpoint,
    bb_count: state.bb_count,
    rb_count: state.rb_count
  });
}, 1000);

game.on('end', () => clearInterval(interval));
```

### 4. 推奨機種の表示

```javascript
// ユーザーの残高に応じた推奨機種を表示
const response = await fetch(
  `${Net8.apiUrl}/api/v1/recommended_models.php?balance=${userBalance}`,
  {
    headers: { 'Authorization': `Bearer ${Net8.token}` }
  }
);

const data = await response.json();
displayRecommendations(data.models);
```

---

## 📊 移行チェックリスト

### 移行前

- [ ] 現在のSDKバージョン確認
- [ ] APIキーの状態確認
- [ ] 既存ユーザー数・セッション数確認
- [ ] テスト計画作成

### 移行中

- [ ] SDKファイル更新
- [ ] コード修正（userId追加）
- [ ] イベントリスナー追加（started, end）
- [ ] ユーザーデータ移行（必要な場合）
- [ ] ポイント残高移行（必要な場合）

### 移行後

- [ ] テスト環境で動作確認
- [ ] 本番環境でスモークテスト
- [ ] 全ユーザーに展開
- [ ] ログ監視（エラー確認）
- [ ] ユーザーフィードバック収集

---

## 🆘 トラブルシューティング

### 問題1: "INSUFFICIENT_BALANCE" エラー

**原因**: ユーザーの残高が不足しています。

**解決**:
```sql
-- 残高を確認
SELECT su.partner_user_id, ub.balance
FROM sdk_users su
JOIN user_balances ub ON su.id = ub.user_id
WHERE su.partner_user_id = 'your_user_id';

-- 残高を追加
UPDATE user_balances ub
JOIN sdk_users su ON ub.user_id = su.id
SET ub.balance = ub.balance + 10000
WHERE su.partner_user_id = 'your_user_id';
```

### 問題2: ユーザーが見つからない

**原因**: `sdk_users` テーブルにユーザーが登録されていません。

**解決**:
```sql
-- 手動でユーザー登録
INSERT INTO sdk_users (partner_user_id, api_key_id, email, username, is_active)
VALUES ('your_user_id', 1, 'user@example.com', 'Username', 1);

-- 初期残高を設定
INSERT INTO user_balances (user_id, balance, total_deposited)
VALUES (LAST_INSERT_ID(), 10000, 10000);
```

### 問題3: APIキーが無効

**原因**: APIキーが無効化されています。

**解決**:
管理画面でAPIキーの状態を確認し、再発行してください:
https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php

---

## 📞 サポート

移行に関するご質問は、以下までお問い合わせください。

**NET8 サポートチーム**
- メール: support@net8.jp
- 管理画面: https://mgg-webservice-production.up.railway.app/data/xxxadmin/

**対応時間**: 平日 10:00-18:00（日本時間）

---

## 📚 関連ドキュメント

- [クイックスタートガイド](./QUICKSTART_GUIDE.md)
- [API仕様書](./API_SPECIFICATION.md)
- [トラブルシューティング](./TROUBLESHOOTING.md)
- [完全なAPIリファレンス](./README_v1.1.0.md)

---

**NET8 SDK v1.1.0 移行ガイド**
最終更新: 2025-11-21
