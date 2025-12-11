# NET8 Gaming SDK トラブルシューティングガイド

## 🔍 よくある問題と解決方法

このガイドでは、NET8 Gaming SDKを使用する際によく発生する問題とその解決方法を説明します。

---

## 目次

1. [インストール・初期化の問題](#インストール初期化の問題)
2. [認証エラー](#認証エラー)
3. [ゲーム起動の問題](#ゲーム起動の問題)
4. [ポイント管理の問題](#ポイント管理の問題)
5. [表示・UI の問題](#表示uiの問題)
6. [ネットワークエラー](#ネットワークエラー)
7. [デバッグ方法](#デバッグ方法)

---

## インストール・初期化の問題

### 問題1: SDKが読み込めない

**症状**:
```
Uncaught ReferenceError: Net8 is not defined
```

**原因**: SDKスクリプトが正しく読み込まれていません。

**解決方法**:

1. スクリプトタグを確認:
```html
<!-- 正しい -->
<script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>

<!-- 間違い: HTTPSが必要 -->
<script src="http://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
```

2. スクリプトが実行される順序を確認:
```html
<!-- SDKを先に読み込み -->
<script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>

<!-- その後、あなたのコード -->
<script>
  // Net8が利用可能
  Net8.init('YOUR_API_KEY');
</script>
```

3. ブラウザのコンソールでネットワークエラーを確認

---

### 問題2: SDK初期化が失敗する

**症状**:
```javascript
await Net8.init('pk_demo_12345');
// Error: Failed to initialize SDK
```

**原因**:
- 無効なAPIキー
- ネットワーク接続の問題
- CORSエラー

**解決方法**:

1. APIキーを確認:
```javascript
// テスト用キー
await Net8.init('pk_demo_12345');

// 本番用キー（正しい形式か確認）
await Net8.init('pk_live_xxxxxxxxxxxxx');
```

2. エラーメッセージを確認:
```javascript
try {
  await Net8.init('YOUR_API_KEY');
  console.log('✅ SDK初期化成功');
} catch (error) {
  console.error('❌ 初期化エラー:', error.message);
  console.error('詳細:', error);
}
```

3. ブラウザのコンソールでCORSエラーを確認:
```
Access to fetch at '...' from origin '...' has been blocked by CORS policy
```

→ この場合、管理画面でドメインを登録してください

---

## 認証エラー

### 問題3: UNAUTHORIZED エラー

**症状**:
```json
{
  "error": "UNAUTHORIZED",
  "message": "Authorization header required"
}
```

**原因**: APIキーが正しく送信されていません。

**解決方法**:

1. SDK初期化を確認:
```javascript
// 初期化を忘れずに
await Net8.init('YOUR_API_KEY');

// その後ゲーム作成
const game = Net8.createGame({...});
```

2. 手動でAPIを呼び出す場合:
```javascript
const response = await fetch('/api/v1/game_start.php', {
  headers: {
    'Authorization': `Bearer ${Net8.token}`,  // ← 重要
    'Content-Type': 'application/json'
  }
});
```

---

### 問題4: INVALID_API_KEY エラー

**症状**:
```json
{
  "error": "INVALID_API_KEY",
  "message": "Invalid API key"
}
```

**原因**: APIキーが無効または期限切れです。

**解決方法**:

1. 管理画面でAPIキーの状態を確認
2. APIキーが有効（`is_active = 1`）か確認
3. 新しいAPIキーを発行

---

## ゲーム起動の問題

### 問題5: MODEL_NOT_FOUND エラー

**症状**:
```json
{
  "error": "MODEL_NOT_FOUND",
  "message": "Model not found"
}
```

**原因**: 指定した機種IDが存在しません。

**解決方法**:

1. 利用可能な機種を取得:
```javascript
const models = await Net8.getModels();
console.log('利用可能な機種:', models.map(m => m.id));
```

2. 正しい機種IDを使用:
```javascript
// 正しい
const game = Net8.createGame({
  model: 'HOKUTO4GO',  // ← 大文字・小文字を正確に
  container: '#game-container'
});

// 間違い
const game = Net8.createGame({
  model: 'hokuto4go',  // ← 小文字は無効
  container: '#game-container'
});
```

---

### 問題6: NO_AVAILABLE_MACHINE エラー

**症状**:
```json
{
  "error": "NO_AVAILABLE_MACHINE",
  "message": "No available machine for this model"
}
```

**原因**:
- テスト環境: 正常な動作（実機がないため）
- 本番環境: 該当機種の台が全て使用中

**解決方法**:

**テスト環境の場合**:
```javascript
// pk_demo_* キーは自動的にモック環境
await Net8.init('pk_demo_12345');

// モックゲームが起動
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  container: '#game-container'
});
await game.start();  // ✅ 成功（モック環境）
```

**本番環境の場合**:
1. 推奨機種APIで空き状況を確認:
```javascript
const response = await fetch(
  `${Net8.apiUrl}/api/v1/recommended_models.php?balance=5000`,
  {
    headers: { 'Authorization': `Bearer ${Net8.token}` }
  }
);
const data = await response.json();

// 空きがある機種のみ表示
const available = data.models.filter(m => m.availability.isAvailable);
console.log('空き台あり:', available);
```

2. ユーザーに別の機種を提案

---

### 問題7: ゲームが表示されない

**症状**: iframe が空白、または何も表示されない

**原因**:
- コンテナ要素が見つからない
- コンテナのサイズが0px
- iFrameが正しく生成されていない

**解決方法**:

1. コンテナ要素を確認:
```html
<!-- ID指定 -->
<div id="game-container" style="width:800px; height:600px;"></div>

<script>
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  container: '#game-container'  // ← # を忘れずに
});
</script>
```

2. CSSでサイズを確認:
```css
#game-container {
  width: 100%;
  max-width: 800px;
  height: 600px;  /* ← 高さ必須 */
  min-height: 400px;
}
```

3. ブラウザのコンソールで確認:
```javascript
const container = document.querySelector('#game-container');
console.log('コンテナ:', container);
console.log('サイズ:', container.offsetWidth, container.offsetHeight);
```

---

## ポイント管理の問題

### 問題8: INSUFFICIENT_BALANCE エラー

**症状**:
```json
{
  "error": "INSUFFICIENT_BALANCE",
  "message": "Insufficient points",
  "balance": 50,
  "required": 100
}
```

**原因**: ユーザーのポイントが不足しています。

**解決方法**:

1. エラーハンドリング:
```javascript
game.on('error', (error) => {
  if (error.message.includes('INSUFFICIENT_BALANCE')) {
    alert('ポイントが不足しています。チャージしてください。');
    // チャージページへリダイレクト
    window.location.href = '/charge';
  }
});
```

2. ゲーム開始前に残高を確認:
```javascript
// 推奨機種APIで残高をチェック
const response = await fetch(
  `${Net8.apiUrl}/api/v1/recommended_models.php?balance=${userBalance}`,
  {
    headers: { 'Authorization': `Bearer ${Net8.token}` }
  }
);
const data = await response.json();

// プレイ可能な機種のみ表示
const playable = data.models.filter(m => m.canPlay);
```

---

### 問題9: ポイントが消費されない

**症状**: ゲームを開始してもポイントが減らない

**原因**: `userId` パラメータが指定されていません。

**解決方法**:

```javascript
// NG: userIdなし（ポイント管理無効）
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  container: '#game-container'
});

// OK: userId指定（ポイント管理有効）
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: 'user_12345',  // ← 必須
  container: '#game-container'
});

// startedイベントで確認
game.on('started', (data) => {
  if (data.pointsConsumed) {
    console.log('✅ ポイント消費:', data.pointsConsumed);
  } else {
    console.warn('⚠️ ポイント管理が無効です');
  }
});
```

---

## 表示・UIの問題

### 問題10: レスポンシブ対応

**症状**: スマホで表示が崩れる

**解決方法**:

```html
<style>
  #game-container {
    width: 100%;
    max-width: 800px;
    height: 0;
    padding-bottom: 75%;  /* 4:3アスペクト比 */
    position: relative;
    margin: 0 auto;
  }

  #game-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }

  /* モバイル対応 */
  @media (max-width: 768px) {
    #game-container {
      max-width: 100%;
      padding-bottom: 133%;  /* 3:4（縦長） */
    }
  }
</style>
```

---

### 問題11: 全画面表示

**症状**: ゲームを全画面にしたい

**解決方法**:

```javascript
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: 'user_12345',
  container: '#game-container'
});

// 全画面ボタンを追加
const fullscreenBtn = document.createElement('button');
fullscreenBtn.textContent = '全画面';
fullscreenBtn.onclick = () => {
  const container = document.querySelector('#game-container');
  if (container.requestFullscreen) {
    container.requestFullscreen();
  }
};

document.body.appendChild(fullscreenBtn);
```

---

## ネットワークエラー

### 問題12: CORS エラー

**症状**:
```
Access to fetch at 'https://mgg-webservice-production.up.railway.app/...'
from origin 'https://your-domain.com' has been blocked by CORS policy
```

**原因**: ドメインが登録されていません。

**解決方法**:

1. 管理画面でドメインを登録:
   - https://mgg-webservice-production.up.railway.app/data/xxxadmin/partner_domains.php
   - あなたのAPIキーを選択
   - ドメイン（例: `https://your-domain.com`）を追加

2. localhostでテストする場合:
```
http://localhost:3000
http://localhost:8080
http://127.0.0.1:3000
```
は自動的に許可されています

---

### 問題13: タイムアウトエラー

**症状**:
```
Error: Request timeout
```

**原因**: ネットワークが遅い、またはサーバーが応答しません。

**解決方法**:

1. タイムアウト時間を延長（SDK内部で30秒設定済み）

2. リトライ機能を実装:
```javascript
async function startGameWithRetry(config, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const game = Net8.createGame(config);
      await game.start();
      return game;
    } catch (error) {
      console.warn(`起動失敗 (${i + 1}/${maxRetries}):`, error.message);
      if (i === maxRetries - 1) throw error;
      // Exponential backoff
      await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
    }
  }
}

// 使用例
const game = await startGameWithRetry({
  model: 'HOKUTO4GO',
  userId: 'user_12345',
  container: '#game-container'
});
```

---

## デバッグ方法

### デバッグモードの有効化

```javascript
// コンソールに詳細ログを出力
window.NET8_DEBUG = true;

await Net8.init('pk_demo_12345');
// → 🚀 [NET8] Initializing SDK...
// → ✅ [NET8] SDK initialized successfully
```

### イベントログの出力

```javascript
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: 'user_12345',
  container: '#game-container'
});

// すべてのイベントをログ
['ready', 'started', 'play', 'win', 'lose', 'bonus', 'score', 'end', 'error'].forEach(event => {
  game.on(event, (data) => {
    console.log(`🎮 [${event}]`, data);
  });
});

await game.start();
```

### ネットワークリクエストの確認

ブラウザの開発者ツール（F12）:
1. **Network** タブを開く
2. **XHR** フィルターを適用
3. API リクエストを確認:
   - リクエストURL
   - ヘッダー（Authorization）
   - レスポンス

### SDK状態の確認

```javascript
// SDK情報を表示
console.log('SDK Version:', Net8.version);
console.log('API URL:', Net8.apiUrl);
console.log('Token:', Net8.token);
console.log('Is Initialized:', Net8.isInitialized);

// ゲーム状態を表示
console.log('Game State:', game.getGameState());
```

---

## 連絡先

上記の方法で解決しない場合は、以下の情報を添えてNET8サポートチームまでお問い合わせください：

**必要な情報**:
- エラーメッセージ（完全なスタックトレース）
- ブラウザの種類とバージョン
- SDK バージョン（`Net8.version`）
- 再現手順
- ブラウザのコンソールログ

**サポート窓口**:
- メール: support@net8.jp
- 管理画面: https://mgg-webservice-production.up.railway.app/data/xxxadmin/

---

**NET8 Gaming SDK トラブルシューティングガイド**
最終更新: 2025-11-21
