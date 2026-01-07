# NET8 リアルタイムコールバック統合ガイド（韓国チーム向け）

**最終更新**: 2026-01-08
**バージョン**: v2.0
**対象**: 韓国開発チーム

---

## 📋 実装完了内容（NEW!）

NET8側で**リアルタイムベット・勝利イベントのコールバック**機能を実装しました。

### 新機能

- ✅ **game.bet** - ベット発生時に即座にコールバック
- ✅ **game.win** - 勝利発生時に即座にコールバック
- ✅ **game.ended** - ゲーム終了時のコールバック（既存）

これにより、**ユーザーがブラウザを閉じてもベット・勝利データが確実に韓国側に届きます**。

---

## 🎯 問題解決

### 以前の問題

```
game_start → ゲーム中（ベット発生）→ ブラウザ閉じる
                                    ↓
                            韓国側にデータ届かない ❌
```

### 新システム

```
game_start
  ↓
ベット発生 → Windows → NET8 API → 即座にコールバック → 韓国側 ✅
  ↓
勝利発生 → Windows → NET8 API → 即座にコールバック → 韓国側 ✅
  ↓
game_end → 最終コールバック → 韓国側 ✅
```

---

## 🔧 韓国側で追加実装が必要な内容

### 1. コールバックエンドポイントの拡張

既存の `POST /api/v1/net8/callback` に **2つの新しいイベントタイプ**を追加:

```javascript
app.post('/api/v1/net8/callback', async (req, res) => {
  // 署名検証（既存のまま）
  if (!verifySignature(req)) {
    return res.status(401).json({ error: 'INVALID_SIGNATURE' });
  }

  // タイムスタンプ検証（既存のまま）
  if (!verifyTimestamp(req)) {
    return res.status(401).json({ error: 'TIMESTAMP_INVALID' });
  }

  const { event, data } = req.body;

  // イベントタイプ別処理
  switch (event) {
    case 'game.bet':
      await handleBetEvent(data);
      break;

    case 'game.win':
      await handleWinEvent(data);
      break;

    case 'game.ended':
      await handleGameEndEvent(data);
      break;

    default:
      return res.status(400).json({ error: 'UNKNOWN_EVENT_TYPE' });
  }

  res.status(200).json({ success: true });
});
```

---

## 📡 コールバックイベント仕様

### 1. game.bet（ベット発生時）

**ヘッダー**:
```http
Content-Type: application/json
X-NET8-Signature: sha256=abc123...
X-NET8-Timestamp: 1673000000
X-NET8-Event: game.bet
```

**リクエストボディ**:
```json
{
  "event": "game.bet",
  "timestamp": 1673000000,
  "data": {
    "sessionId": "gs_1234567890",
    "memberNo": 12345,
    "userId": "korea_user_12345",
    "modelId": "HOKUTO4GO",
    "machineNo": 1,
    "betAmount": 3,
    "creditBefore": 100,
    "creditAfter": 97,
    "totalBetsInSession": 5,
    "timestamp": "2026-01-08T10:15:30+00:00",
    "currency": "JPY"
  }
}
```

### 2. game.win（勝利発生時）

**ヘッダー**:
```http
Content-Type: application/json
X-NET8-Signature: sha256=def456...
X-NET8-Timestamp: 1673000100
X-NET8-Event: game.win
```

**リクエストボディ**:
```json
{
  "event": "game.win",
  "timestamp": 1673000100,
  "data": {
    "sessionId": "gs_1234567890",
    "memberNo": 12345,
    "userId": "korea_user_12345",
    "modelId": "HOKUTO4GO",
    "machineNo": 1,
    "winAmount": 15,
    "winType": "normal",
    "creditBefore": 97,
    "creditAfter": 112,
    "totalWinsInSession": 3,
    "timestamp": "2026-01-08T10:16:00+00:00",
    "currency": "JPY"
  }
}
```

**winType値**:
- `normal` - 通常勝利
- `bonus` - ボーナス（BB/RB）
- `jackpot` - ジャックポット

### 3. game.ended（ゲーム終了時）

既存の仕様と同じです（`CALLBACK_INTEGRATION_GUIDE_KR.md` 参照）。

---

## 💻 実装例（Node.js + Express）

### ベットイベント処理

```javascript
async function handleBetEvent(data) {
  const { sessionId, userId, betAmount, creditAfter } = data;

  // トランザクション開始
  const transaction = await db.transaction();

  try {
    // 1. ベット履歴記録
    await db.BetHistory.create({
      sessionId,
      userId,
      betAmount,
      creditAfter,
      timestamp: new Date(data.timestamp)
    }, { transaction });

    // 2. ユーザー統計更新
    await db.UserStats.increment('totalBets', {
      by: 1,
      where: { userId },
      transaction
    });

    await db.UserStats.increment('totalBetAmount', {
      by: betAmount,
      where: { userId },
      transaction
    });

    await transaction.commit();

    console.log(`✅ Bet recorded: user=${userId}, amount=${betAmount}`);

  } catch (error) {
    await transaction.rollback();
    throw error;
  }
}
```

### 勝利イベント処理

```javascript
async function handleWinEvent(data) {
  const { sessionId, userId, winAmount, winType, creditAfter } = data;

  const transaction = await db.transaction();

  try {
    // 1. 勝利履歴記録
    await db.WinHistory.create({
      sessionId,
      userId,
      winAmount,
      winType,
      creditAfter,
      timestamp: new Date(data.timestamp)
    }, { transaction });

    // 2. ユーザー統計更新
    await db.UserStats.increment('totalWins', {
      by: 1,
      where: { userId },
      transaction
    });

    await db.UserStats.increment('totalWinAmount', {
      by: winAmount,
      where: { userId },
      transaction
    });

    // 3. ボーナス勝利の場合は追加処理
    if (winType === 'bonus' || winType === 'jackpot') {
      await db.UserAchievements.recordBigWin({
        userId,
        winType,
        amount: winAmount
      }, { transaction });
    }

    await transaction.commit();

    console.log(`✅ Win recorded: user=${userId}, amount=${winAmount}, type=${winType}`);

  } catch (error) {
    await transaction.rollback();
    throw error;
  }
}
```

---

## 🗄️ 推奨データベーススキーマ

### ベット履歴テーブル

```sql
CREATE TABLE bet_history (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  session_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(100) NOT NULL,
  bet_amount INT NOT NULL,
  credit_after INT NOT NULL,
  timestamp DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_session_id (session_id),
  INDEX idx_user_id (user_id),
  INDEX idx_timestamp (timestamp)
);
```

### 勝利履歴テーブル

```sql
CREATE TABLE win_history (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  session_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(100) NOT NULL,
  win_amount INT NOT NULL,
  win_type ENUM('normal', 'bonus', 'jackpot') NOT NULL,
  credit_after INT NOT NULL,
  timestamp DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_session_id (session_id),
  INDEX idx_user_id (user_id),
  INDEX idx_timestamp (timestamp),
  INDEX idx_win_type (win_type)
);
```

---

## 🧪 テスト方法

### テストリクエスト送信（ベットイベント）

```bash
#!/bin/bash

SECRET="whsec_a6b6bb13e4623bc9e2efb25cead7e338b962653e6605f323"
URL="https://korea-api-staging.example.com/api/v1/net8/callback"

PAYLOAD='{
  "event": "game.bet",
  "timestamp": '$(date +%s)',
  "data": {
    "sessionId": "gs_test_123",
    "userId": "test_user_001",
    "betAmount": 3,
    "creditBefore": 100,
    "creditAfter": 97
  }
}'

SIGNATURE="sha256=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')"

curl -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-NET8-Signature: $SIGNATURE" \
  -H "X-NET8-Timestamp: $(date +%s)" \
  -H "X-NET8-Event: game.bet" \
  -d "$PAYLOAD"
```

### テストリクエスト送信（勝利イベント）

```bash
PAYLOAD='{
  "event": "game.win",
  "timestamp": '$(date +%s)',
  "data": {
    "sessionId": "gs_test_123",
    "userId": "test_user_001",
    "winAmount": 15,
    "winType": "normal",
    "creditBefore": 97,
    "creditAfter": 112
  }
}'

SIGNATURE="sha256=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')"

curl -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-NET8-Signature: $SIGNATURE" \
  -H "X-NET8-Timestamp: $(date +%s)" \
  -H "X-NET8-Event: game.win" \
  -d "$PAYLOAD"
```

---

## ✅ 実装チェックリスト

### 韓国チーム実装必須項目

- [ ] コールバックエンドポイントに `game.bet` イベント処理追加
- [ ] コールバックエンドポイントに `game.win` イベント処理追加
- [ ] ベット履歴テーブル作成
- [ ] 勝利履歴テーブル作成
- [ ] ユーザー統計更新ロジック実装
- [ ] トランザクション処理実装
- [ ] エラーハンドリング実装
- [ ] ログ記録実装
- [ ] ステージング環境テスト
- [ ] 本番環境デプロイ

### NET8側確認項目（完了✅）

- [x] `game_event.php` エンドポイント作成
- [x] `callback_helper.php` 拡張（game.bet, game.win対応）
- [x] HMAC-SHA256署名生成機能
- [x] リトライ機構（最大3回）
- [x] エラーログ記録

---

## 📊 期待される効果

### セキュリティ向上

- ✅ ブラウザ閉じても全データ記録
- ✅ 不正な勝利申告を防止（サーバー側で検証済み）
- ✅ HMAC-SHA256署名で改ざん防止

### ユーザー体験向上

- ✅ ベット・勝利が即座に反映
- ✅ リアルタイム統計表示が可能
- ✅ 接続切断時もデータ損失なし

### 運用効率向上

- ✅ リアルタイムモニタリング可能
- ✅ 不正検知が容易
- ✅ ユーザーサポート対応が迅速化

---

## 📞 サポート連絡先

**技術的な質問**: NET8チーム
**緊急時**: 即座にNET8チームに連絡

---

**実装完了**: 2026-01-08
**ステータス**: NET8側完了 ✅ / 韓国側実装待ち 🔄
