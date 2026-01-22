# リアルタイムCallback実装完了レポート

**実装日**: 2026-01-23
**ステータス**: ✅ **実装完了 - テスト準備中**

---

## 🎯 実装サマリー

Korean Noaさんの要求に応じて、以下の**4種類のリアルタイムcallback**を実装しました：

| # | イベント | Callback名 | トリガー | ステータス |
|---|---------|-----------|----------|-----------|
| 1️⃣ | ベット | `game.bet` | クレジット減少時 | ✅ 完了 |
| 2️⃣ | 勝利 | `game.win` | クレジット増加時 | ✅ 完了 |
| 3️⃣ | ポイント変換 | `game.point_converted` | クレジット→ポイント変換時 | ✅ 完了 |
| 4️⃣ | ゲーム終了 | `game.ended` | 精算完了時（統計データ付き） | ✅ 完了 |

---

## 📂 変更ファイル

### 新規作成（3ファイル）

#### 1. `net8/02.ソースファイル/net8_html/api/v1/game_bet.php`
**機能**:
- ベットイベント受信
- game_sessions.total_bets 累計更新
- `game.bet` callback送信（HMAC-SHA256署名付き）

**APIエンドポイント**: `POST /api/v1/game_bet.php`

**リクエスト例**:
```json
{
  "sessionId": "gs_xxx",
  "betAmount": 100,
  "creditBefore": 500,
  "creditAfter": 400
}
```

**Callback送信データ**:
```json
{
  "event": "game.bet",
  "data": {
    "sessionId": "gs_xxx",
    "memberNo": 12345,
    "userId": "korea_user_001",
    "betAmount": 100,
    "creditBefore": 500,
    "creditAfter": 400,
    "totalBetsInSession": 1500
  }
}
```

---

#### 2. `net8/02.ソースファイル/net8_html/api/v1/game_win.php`
**機能**:
- 勝利イベント受信
- game_sessions.total_wins 累計更新
- `game.win` callback送信

**APIエンドポイント**: `POST /api/v1/game_win.php`

**リクエスト例**:
```json
{
  "sessionId": "gs_xxx",
  "winAmount": 200,
  "creditBefore": 400,
  "creditAfter": 600
}
```

**Callback送信データ**:
```json
{
  "event": "game.win",
  "data": {
    "sessionId": "gs_xxx",
    "memberNo": 12345,
    "userId": "korea_user_001",
    "winAmount": 200,
    "creditBefore": 400,
    "creditAfter": 600,
    "totalWinsInSession": 2000
  }
}
```

---

#### 3. `net8/02.ソースファイル/net8_html/api/v1/game_point_converted.php`
**機能**:
- ポイント変換イベント受信
- `game.point_converted` callback送信

**APIエンドポイント**: `POST /api/v1/game_point_converted.php`

**リクエスト例**:
```json
{
  "sessionId": "gs_xxx",
  "creditConverted": 1000,
  "pointsReceived": 10000,
  "conversionRate": 10
}
```

**Callback送信データ**:
```json
{
  "event": "game.point_converted",
  "data": {
    "sessionId": "gs_xxx",
    "memberNo": 12345,
    "userId": "korea_user_001",
    "creditConverted": 1000,
    "pointsReceived": 10000,
    "conversionRate": 10
  }
}
```

---

### 修正ファイル（1ファイル）

#### 4. `net8/02.ソースファイル/net8_html/data/play_v2/js/view_auth_pachi.js`

**変更内容**:

1. **gameオブジェクトに累計追跡変数を追加** (34-47行目)
```javascript
var game = {
  // ... 既存フィールド ...
  'totalBets': 0,  // ★ 新規追加
  'totalWins': 0   // ★ 新規追加
};
```

2. **リアルタイムcallback送信ヘルパー関数を追加** (129-197行目)
```javascript
function sendBetCallback(betAmount, creditBefore, creditAfter)
function sendWinCallback(winAmount, creditBefore, creditAfter)
function sendPointConvertedCallback(creditConverted, pointsReceived, conversionRate)
```

3. **カメライベント検出箇所に callback 呼び出しを追加**:

| イベント | 行数 | トリガー | Callback |
|---------|------|---------|----------|
| `CRI_` | 673-684行目 | Credit In（複数クレジットベット） | sendBetCallback() |
| `CRO_` | 686-697行目 | Credit Out（複数クレジット勝利） | sendWinCallback() |
| `Signal_0` | 710-722行目 | 1クレジットベット | sendBetCallback() |
| `Signal_1` | 724-734行目 | 1クレジット勝利 | sendWinCallback() |
| `Cst` (ok) | 1123-1140行目 | ポイント変換成功 | sendPointConvertedCallback() |

4. **game:settlement に totalBets/totalWins を追加** (1143-1155行目)
```javascript
window.parent.postMessage({
  type: 'game:settlement',
  payload: {
    playPoint: finalPlayPoint,
    credit: finalCredit,
    drawPoint: finalDrawPoint,
    totalDrawPoint: finalTotalDrawPoint,
    result: 'completed',
    // ★ 新規追加
    totalBets: game.totalBets || 0,
    totalWins: game.totalWins || 0
  }
}, '*');
```

---

## 🔧 技術仕様

### データフロー

```
┌──────────────────────────────────────────────┐
│  play_embed (JavaScript)                     │
│  view_auth_pachi.js                          │
│                                              │
│  カメラからコマンド受信:                     │
│  ├─ CRI_ / Signal_0 → sendBetCallback()     │
│  ├─ CRO_ / Signal_1 → sendWinCallback()     │
│  ├─ Cst(ok) → sendPointConvertedCallback()  │
│  └─ EXT → postMessage('game:settlement')    │
└──────────────────────────────────────────────┘
              ↓ fetch() POST
┌──────────────────────────────────────────────┐
│  NET8 API (PHP)                              │
│  ├─ game_bet.php                             │
│  │   ├─ game_sessions.total_bets 更新       │
│  │   └─ sendRealtimeCallback('game.bet')    │
│  ├─ game_win.php                             │
│  │   ├─ game_sessions.total_wins 更新       │
│  │   └─ sendRealtimeCallback('game.win')    │
│  ├─ game_point_converted.php                │
│  │   └─ sendRealtimeCallback('game.point_converted') │
│  └─ game_end.php (既存)                      │
│      └─ sendRealtimeCallback('game.ended')  │
└──────────────────────────────────────────────┘
              ↓ HTTPS POST (HMAC-SHA256署名付き)
┌──────────────────────────────────────────────┐
│  Korean Callback Endpoint                    │
│  https://api.harumi.com/net8/callback        │
│                                              │
│  受信するCallback:                           │
│  ├─ game.bet ✅                              │
│  ├─ game.win ✅                              │
│  ├─ game.point_converted ✅                  │
│  └─ game.ended ✅ (統計データ付き)          │
└──────────────────────────────────────────────┘
```

---

### セキュリティ

**HMAC-SHA256署名検証**:
- すべてのcallbackに署名ヘッダー付与: `X-NET8-Signature: sha256=xxx`
- callbackSecret使用（game_start.php で設定）
- タイムスタンプヘッダー: `X-NET8-Timestamp`
- リプレイ攻撃防止（5分以内のタイムスタンプのみ有効）

---

## 🧪 テスト手順

### ローカルテスト

1. **MySQL起動確認**:
```bash
docker ps | grep mysql
# または
mysql -u rootuser -prootuserpass --port 3310 -h localhost -e "SELECT 1;"
```

2. **ベットイベントテスト**:
```bash
curl -X POST http://localhost:8000/api/v1/game_bet.php \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "gs_test_123",
    "betAmount": 100,
    "creditBefore": 500,
    "creditAfter": 400
  }'
```

3. **勝利イベントテスト**:
```bash
curl -X POST http://localhost:8000/api/v1/game_win.php \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "gs_test_123",
    "winAmount": 200,
    "creditBefore": 400,
    "creditAfter": 600
  }'
```

4. **ポイント変換テスト**:
```bash
curl -X POST http://localhost:8000/api/v1/game_point_converted.php \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "gs_test_123",
    "creditConverted": 1000,
    "pointsReceived": 10000,
    "conversionRate": 10
  }'
```

---

### 本番環境テスト

**前提条件**:
- Railway デプロイ完了
- Korean callback endpoint (`https://api.harumi.com/net8/callback`) 稼働中

**テストフロー**:
1. Korean側からgame_start.phpを呼び出し
2. play_embed でゲーム開始
3. ベット/勝利/変換イベント発生
4. Korean callback endpoint でイベント受信確認
5. 精算実行
6. game.ended callback 受信確認（統計データ確認）

---

## 📊 期待される結果

### game.ended callback データ（Korean Noaさんの期待値）

**ゲーム前（修正前）**: ❌
```json
{
  "points": {
    "initial": 0,    // ❌ 0
    "consumed": 0,   // ❌ 0
    "won": 0,        // ❌ 0
    "final": 53090,  // ✓ 正常
    "net": 0         // ❌ 0
  }
}
```

**ゲーム後（修正後）**: ✅
```json
{
  "points": {
    "initial": 10000,   // ✅ 実際の開始時残高
    "consumed": 1500,   // ✅ 総ベット額
    "won": 2000,        // ✅ 総勝利額
    "final": 10500,     // ✅ 最終残高
    "net": 500          // ✅ 純益 (final - initial)
  }
}
```

---

## 🚀 デプロイ手順

### 1. Git コミット

```bash
cd /Users/kotarokashiwai/net8_rebirth

# 変更確認
git status

# ステージング
git add net8/02.ソースファイル/net8_html/api/v1/game_bet.php
git add net8/02.ソースファイル/net8_html/api/v1/game_win.php
git add net8/02.ソースファイル/net8_html/api/v1/game_point_converted.php
git add net8/02.ソースファイル/net8_html/data/play_v2/js/view_auth_pachi.js
git add REALTIME_CALLBACK_IMPLEMENTATION_COMPLETE.md

# コミット
git commit -m "feat(callback): implement realtime bet/win/conversion callbacks for Korean partner

- Add game_bet.php API endpoint for realtime bet event callbacks
- Add game_win.php API endpoint for realtime win event callbacks
- Add game_point_converted.php API endpoint for point conversion callbacks
- Update view_auth_pachi.js to track totalBets/totalWins
- Add callback helper functions (sendBetCallback, sendWinCallback, sendPointConvertedCallback)
- Integrate callbacks on camera events (CRI_, CRO_, Signal_0, Signal_1, Cst)
- Include totalBets/totalWins in game:settlement postMessage
- All callbacks use HMAC-SHA256 signatures for security
- Resolves Korean team requirement for realtime event notifications

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"

# Push
git push origin main
```

### 2. Railway 自動デプロイ確認

Railway が自動的にデプロイを開始します：
1. https://railway.app/dashboard でデプロイログ確認
2. ビルド成功を確認
3. ヘルスチェック確認

### 3. Korean チームへ通知

**通知内容**:
```
📢 NET8 リアルタイムCallback実装完了

4種類のcallbackが利用可能になりました：

1. game.bet - ベット時
2. game.win - 勝利時
3. game.point_converted - ポイント変換時
4. game.ended - ゲーム終了時（統計データ付き）

すべてのcallbackは https://api.harumi.com/net8/callback に送信されます。

game.endedの統計データには、totalBets/totalWinsが含まれるようになりました。

テストをお願いします！
```

---

## ✅ チェックリスト

### 実装完了項目
- [x] game_bet.php API作成
- [x] game_win.php API作成
- [x] game_point_converted.php API作成
- [x] view_auth_pachi.js に totalBets/totalWins 追跡機能追加
- [x] ヘルパー関数作成（sendBetCallback, sendWinCallback, sendPointConvertedCallback）
- [x] カメライベント検出箇所に callback 呼び出し追加
- [x] game:settlement に totalBets/totalWins 追加
- [x] callback_helper.php の既存関数活用（buildBetCallbackData, buildWinCallbackData）

### デプロイ前確認
- [ ] ローカルテスト実施
- [ ] コミット＆Push
- [ ] Railway デプロイ確認
- [ ] Korean callback endpoint 稼働確認

### テスト項目
- [ ] ベットイベントcallback受信確認
- [ ] 勝利イベントcallback受信確認
- [ ] ポイント変換callback受信確認
- [ ] game.ended callback統計データ確認
- [ ] HMAC-SHA256署名検証確認

---

## 🎉 完了

Korean Noaさんの要求「すべてリアルタイムcallbackが必要です」に対応完了しました！

**実装時間**: 約2時間
**変更ファイル数**: 4ファイル（新規3、修正1）
**API呼び出し追加箇所**: 5箇所

---

**次のステップ**: デプロイ＆テスト 🚀
