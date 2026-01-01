# NET8 中国側統合ガイド

## 概要

NET8パチンコ・スロットプラットフォームを中国側Webアプリケーションに統合するためのガイドです。

## 実装完了内容

### ✅ 通貨対応
- **対応通貨**: CNY（人民元）、USD（米ドル）、TWD（台湾ドル）、JPY（日本円）
- **表示**: 残高・獲得ポイントが通貨記号付きで表示（例: 5000.00元）
- **決済**: 中国側ポイントシステムと完全連携

### ✅ 多言語対応
- **対応言語**: 中国語（zh）、英語（en）、日本語（ja）、韓国語（ko）
- **切り替え**: URLパラメータで指定可能

### ✅ URL分離
- **中国側**: play_v2（通貨対応版）
- **韓国側**: play_embed（従来版）
- **完全独立**: 互いに影響なし

---

## 統合方法

### 方法1: ゲームページ直接埋め込み（推奨）

game_start APIを呼び出してゲームURLを取得し、iframeで表示します。

#### ステップ1: game_start API呼び出し

```javascript
const response = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/game_start.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer pk_demo_12345', // APIキー（本番環境では差し替え）
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    userId: 'china_user_001',        // 中国側ユーザーID
    modelId: 'HOKUTO4GO',            // 機種ID
    initialPoints: 5000,             // 初期ポイント（中国側残高）
    currency: 'CNY',                 // 通貨（CNY, USD, TWD）
    lang: 'zh',                      // 言語（zh, en, ja, ko）
    balanceMode: 'set',              // 残高設定モード
    consumeImmediately: false        // ゲーム開始時にポイント消費しない
  })
});

const data = await response.json();
console.log('Game URL:', data.gameUrl);
// => https://mgg-webservice-production.up.railway.app/data/play_v2/index.php?NO=1
```

#### ステップ2: iframeで表示

```html
<iframe
  src="{data.gameUrl}"
  width="100%"
  height="800px"
  allow="camera; microphone; autoplay; fullscreen"
  allowfullscreen
  style="border: none;">
</iframe>
```

#### ステップ3: ゲーム終了処理

```javascript
const endResponse = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/game_end.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer pk_demo_12345',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    sessionId: data.sessionId,  // game_start時のsessionId
    result: 'win',              // 'win', 'lose', 'draw'
    pointsWon: 3000             // 獲得ポイント
  })
});

const endData = await endResponse.json();
console.log('Final balance:', endData.balance);
// => { amount: 8000, currency: 'CNY', formatted: '8000.00元' }
```

---

### 方法2: トップページ全体表示

NET8のトップページ（機種一覧）から直接ゲームを選択させたい場合の方法です。

#### オプションA: iframe全体埋め込み

```html
<!-- トップページ全体を埋め込み -->
<iframe
  src="https://mgg-webservice-production.up.railway.app/data/top/index.php?lang=zh&currency=CNY&userId=china_user_001"
  width="100%"
  height="900px"
  allow="camera; microphone; autoplay; fullscreen"
  allowfullscreen
  style="border: none;">
</iframe>
```

**URLパラメータ**:
- `lang`: 言語（zh, en, ja, ko）
- `currency`: 通貨（CNY, USD, TWD, JPY）
- `userId`: 中国側ユーザーID（ログイン連携用）

#### オプションB: 機種APIで独自UI構築

```javascript
// 機種一覧を取得
const modelsResponse = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/models.php', {
  headers: {
    'Authorization': 'Bearer pk_demo_12345'
  }
});

const models = await modelsResponse.json();
/*
[
  {
    "model_cd": "HOKUTO4GO",
    "model_name": "北斗の拳",
    "category": "pachinko",
    "image_url": "https://...",
    "description": "..."
  },
  ...
]
*/

// 中国側UIで機種一覧を表示 → ユーザーが選択 → game_start API呼び出し
```

---

## APIエンドポイント

### Base URL
```
https://mgg-webservice-production.up.railway.app
```

### 1. ゲーム開始 (game_start)

**エンドポイント**: `POST /api/v1/game_start.php`

**リクエスト**:
```json
{
  "userId": "china_user_001",
  "modelId": "HOKUTO4GO",
  "initialPoints": 5000,
  "currency": "CNY",
  "lang": "zh",
  "balanceMode": "set",
  "consumeImmediately": false
}
```

**レスポンス**:
```json
{
  "success": true,
  "sessionId": "gs_xxxxx",
  "machineNo": 1,
  "gameUrl": "https://mgg-webservice-production.up.railway.app/data/play_v2/index.php?NO=1",
  "mode": "currency",
  "balance": {
    "amount": 5000,
    "currency": "CNY",
    "formatted": "5000.00元",
    "symbol": "元",
    "name": "人民元"
  },
  "model": {
    "id": "HOKUTO4GO",
    "name": "北斗の拳",
    "category": "pachinko"
  }
}
```

### 2. ゲーム終了 (game_end)

**エンドポイント**: `POST /api/v1/game_end.php`

**リクエスト**:
```json
{
  "sessionId": "gs_xxxxx",
  "result": "win",
  "pointsWon": 3000
}
```

**レスポンス**:
```json
{
  "success": true,
  "sessionId": "gs_xxxxx",
  "currency": "CNY",
  "balance": {
    "amount": 8000,
    "currency": "CNY",
    "formatted": "8000.00元",
    "symbol": "元",
    "name": "人民元"
  },
  "pointsWon": 3000,
  "finalResult": "win"
}
```

### 3. 機種一覧取得 (models)

**エンドポイント**: `GET /api/v1/models.php`

**レスポンス**:
```json
{
  "success": true,
  "models": [
    {
      "model_cd": "HOKUTO4GO",
      "model_name": "北斗の拳",
      "category": "pachinko",
      "image_url": "https://...",
      "description": "..."
    },
    {
      "model_cd": "MILLIONGOD01",
      "model_name": "ミリオンゴッド",
      "category": "slot",
      "image_url": "https://...",
      "description": "..."
    }
  ]
}
```

---

## 言語対応

### 対応言語コード

| コード | 言語 | 通貨（推奨） |
|--------|------|--------------|
| `zh` | 中国語 | CNY |
| `en` | 英語 | USD |
| `ja` | 日本語 | JPY |
| `ko` | 韓国語 | KRW (未対応・JPY使用) |

### 言語切り替え方法

#### game_start APIで指定
```json
{
  "lang": "zh"  // zh, en, ja, ko
}
```

#### URLパラメータで指定
```
https://mgg-webservice-production.up.railway.app/data/top/index.php?lang=zh
```

---

## 通貨対応

### 対応通貨

| 通貨コード | 名称 | 記号 | 小数点 |
|------------|------|------|--------|
| CNY | 人民元 | 元 | 2桁 |
| USD | 米ドル | $ | 2桁 |
| TWD | 台湾ドル | NT$ | 2桁 |
| JPY | 日本円 | ¥ | 0桁 |

### 通貨フォーマット例

```
CNY: 5000.00元
USD: $5000.00
TWD: NT$5000.00
JPY: ¥5000
```

---

## ポイント同期フロー

```
1. ユーザーが中国側アプリでゲーム開始
   ↓
2. 中国側 → game_start API（initialPoints=5000, currency=CNY）
   ↓
3. NET8: mst_member.point に 5000 を保存（currency=CNY）
   ↓
4. ユーザーがゲームプレイ → 獲得: +3000
   ↓
5. 中国側 → game_end API（pointsWon=3000）
   ↓
6. NET8: 残高を 8000 に更新
   ↓
7. レスポンス: { balance: { amount: 8000, currency: 'CNY' } }
   ↓
8. 中国側: ユーザーのポイント残高を 8000 に更新
```

---

## セキュリティ

### APIキー認証

本番環境では、専用のAPIキーを発行します。

**リクエストヘッダー**:
```
Authorization: Bearer {YOUR_API_KEY}
```

**デモAPIキー**（テスト用）:
```
pk_demo_12345
```

### CORS対応

NET8サーバーは以下のドメインからのアクセスを許可します：
- `https://your-china-domain.com`（本番環境で設定）
- `http://localhost:*`（開発環境）

---

## トラブルシューティング

### Q1: iframe内でゲームが表示されない

**原因**: CORS制限

**解決**: APIキーを使用してgame_start APIを呼び出し、gameUrlを取得してください。

### Q2: 通貨が正しく表示されない

**原因**: currencyパラメータが正しく送信されていない

**解決**: game_start APIリクエストに `"currency": "CNY"` を含めてください。

### Q3: ポイントが反映されない

**原因**: game_end APIを呼び出していない

**解決**: ゲーム終了時に必ずgame_end APIを呼び出してください。

---

## サンプルコード（完全版）

```javascript
// 中国側Webアプリケーション統合例

class NET8GameClient {
  constructor(apiKey, baseUrl = 'https://mgg-webservice-production.up.railway.app') {
    this.apiKey = apiKey;
    this.baseUrl = baseUrl;
    this.currentSession = null;
  }

  async startGame(userId, modelId, points, currency = 'CNY', lang = 'zh') {
    const response = await fetch(`${this.baseUrl}/api/v1/game_start.php`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        userId,
        modelId,
        initialPoints: points,
        currency,
        lang,
        balanceMode: 'set',
        consumeImmediately: false
      })
    });

    if (!response.ok) {
      throw new Error(`Game start failed: ${response.status}`);
    }

    const data = await response.json();
    this.currentSession = data;
    return data;
  }

  async endGame(result, pointsWon) {
    if (!this.currentSession) {
      throw new Error('No active game session');
    }

    const response = await fetch(`${this.baseUrl}/api/v1/game_end.php`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        sessionId: this.currentSession.sessionId,
        result,
        pointsWon
      })
    });

    if (!response.ok) {
      throw new Error(`Game end failed: ${response.status}`);
    }

    const data = await response.json();
    this.currentSession = null;
    return data;
  }

  showGameInIframe(containerId) {
    if (!this.currentSession || !this.currentSession.gameUrl) {
      throw new Error('No game URL available');
    }

    const container = document.getElementById(containerId);
    container.innerHTML = `
      <iframe
        src="${this.currentSession.gameUrl}"
        width="100%"
        height="800px"
        allow="camera; microphone; autoplay; fullscreen"
        allowfullscreen
        style="border: none;">
      </iframe>
    `;
  }
}

// 使用例
const net8Client = new NET8GameClient('pk_demo_12345');

// ゲーム開始
const session = await net8Client.startGame(
  'china_user_001',  // ユーザーID
  'HOKUTO4GO',       // 機種ID
  5000,              // 初期ポイント
  'CNY',             // 通貨
  'zh'               // 言語
);

// iframeで表示
net8Client.showGameInIframe('game-container');

// ゲーム終了
const result = await net8Client.endGame('win', 3000);
console.log('Final balance:', result.balance.formatted); // "8000.00元"
```

---

## サポート

質問・問題がある場合は、以下にお問い合わせください：
- Email: support@net8games.com
- Slack: #net8-china-integration

---

最終更新: 2026-01-01
バージョン: 1.0.0
