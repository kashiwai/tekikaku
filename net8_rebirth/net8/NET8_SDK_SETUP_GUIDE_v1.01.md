# NET8 Gaming SDK - お客様向け設定ガイド v1.01

**最終更新**: 2025-11-07
**対象**: NET8 SDK v1.0.1-beta 統合パートナー企業様

---

## 📋 目次

1. [はじめに](#はじめに)
2. [受領物の確認](#受領物の確認)
3. [統合準備](#統合準備)
4. [基本統合手順](#基本統合手順)
5. [高度な統合](#高度な統合)
6. [環境切り替え](#環境切り替え)
7. [トラブルシューティング](#トラブルシューティング)
8. [セキュリティ](#セキュリティ)
9. [サポート](#サポート)

---

## はじめに

この度はNET8 Gaming SDKをご採用いただき、誠にありがとうございます。

本ガイドは、貴社のウェブサイトにNET8 SDKを統合するための完全な手順を記載しています。

### 所要時間

- **最小統合**: 約5分
- **カスタマイズ統合**: 約30分〜1時間
- **完全統合・テスト**: 約2〜3時間

---

## 受領物の確認

NET8開発チームから以下のファイル・情報を受領していることを確認してください：

### ✅ 必須受領物

| ファイル・情報 | 説明 | 形式 |
|--------------|------|------|
| **個別接続ガイド** | 貴社専用のAPIキーが埋め込まれた統合ガイド | Markdown (.md) |
| **環境設定情報** | APIキー、エンドポイント情報 | JSON/CSV |
| **本設定ガイド** | 統合手順の詳細マニュアル | Markdown (.md) |
| **APIキー** | 貴社専用の認証キー | テキスト文字列 |

### 📂 受領ファイル例

```
NET8_SDK_Connection_Guide_貴社名.md
NET8_Client_Config_貴社名.json
NET8_SDK_SETUP_GUIDE_v1.01.md （本ファイル）
```

---

## 統合準備

### 1. 開発環境の確認

#### 必須要件

- **ウェブサーバー**: Apache, Nginx, または任意のHTTPサーバー
- **ブラウザ**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **JavaScript**: ES6対応（2015年以降のブラウザなら対応済み）
- **インターネット接続**: 必須（CDN経由でSDKを読み込むため）

#### 推奨環境

- **HTTPS**: 本番環境では必須（WebRTC使用のため）
- **ドメイン**: 独自ドメイン推奨
- **サーバーサイド**: PHP 7.2+, Node.js 14+, Python 3.7+ など（APIキー管理用）

### 2. 環境情報の確認

受領したJSON/CSVファイルを開き、以下の情報を確認してください：

```json
{
  "credentials": {
    "api_key": "pk_demo_xxxxxxxxxx",
    "environment": "test",
    "mock_mode": true
  },
  "endpoints": {
    "base_url": "https://mgg-webservice-production.up.railway.app",
    "sdk_url": "https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"
  }
}
```

**確認事項**:
- ✅ APIキーが正しく記載されているか
- ✅ environment が `test` または `staging` になっているか（初回統合時）
- ✅ mock_mode が `true` になっているか（テスト環境の場合）

---

## 基本統合手順

### ステップ1: HTMLファイルの準備

貴社のウェブページに以下のコードを追加します。

#### 最小構成（5分で完了）

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 ゲーム統合</title>

    <!-- NET8 SDK読み込み -->
    <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
</head>
<body>
    <!-- ゲーム表示エリア -->
    <div id="game-container" style="width:800px; height:600px; border:2px solid #333;"></div>

    <script>
        // 貴社のAPIキーに置き換えてください
        const API_KEY = 'pk_demo_xxxxxxxxxx'; // ← ここに貴社のAPIキー

        // SDK初期化とゲーム起動
        (async function() {
            try {
                // 初期化
                await Net8.init(API_KEY);
                console.log('✅ NET8 SDK初期化完了');

                // ゲーム作成
                const game = Net8.createGame({
                    model: 'HOKUTO4GO',      // 機種ID
                    container: '#game-container'
                });

                // ゲーム開始
                await game.start();
                console.log('✅ ゲーム開始');

            } catch (error) {
                console.error('❌ エラー:', error);
                alert('ゲームの起動に失敗しました: ' + error.message);
            }
        })();
    </script>
</body>
</html>
```

### ステップ2: ブラウザでテスト

1. 上記HTMLファイルを保存（例: `game-test.html`）
2. ローカルサーバーで起動

#### ローカルサーバーの起動方法

```bash
# Python 3を使用
python3 -m http.server 8000

# Node.jsを使用
npx http-server -p 8000

# PHPを使用
php -S localhost:8000
```

3. ブラウザで開く: `http://localhost:8000/game-test.html`

### ステップ3: 動作確認

#### 成功時の表示

- ゲームコンテナにゲーム画面が表示される
- ブラウザコンソールに `✅ NET8 SDK初期化完了` と表示される
- ブラウザコンソールに `✅ ゲーム開始` と表示される

#### テスト環境の確認方法

ブラウザの開発者ツール（F12キー）を開き、Networkタブで `/api/v1/game_start.php` のレスポンスを確認：

```json
{
  "success": true,
  "environment": "test",
  "mock": true,          // ← これが true ならテスト環境
  "machineNo": 9999      // ← 9999 はモックマシン番号
}
```

---

## 高度な統合

### 機種選択機能の追加

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>NET8 ゲームセレクト</title>
    <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a2e; color: white; }
        .model-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .model-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            cursor: pointer;
            text-align: center;
            transition: transform 0.3s;
        }
        .model-card:hover { transform: scale(1.05); }
        .model-card h3 { margin: 0 0 10px 0; font-size: 20px; }
        .model-card p { margin: 0; opacity: 0.8; }
        #game-container {
            width: 100%;
            max-width: 900px;
            height: 600px;
            margin: 20px auto;
            border: 3px solid #667eea;
            border-radius: 15px;
            background: #000;
        }
        #status {
            text-align: center;
            padding: 15px;
            background: #2a2a3e;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 900px;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">🎮 NET8 ゲームセレクト</h1>

    <div id="status">
        <p id="status-text">SDK初期化中...</p>
    </div>

    <div class="model-grid" id="models"></div>

    <div id="game-container"></div>

    <script>
        const API_KEY = 'pk_demo_xxxxxxxxxx'; // ← 貴社のAPIキー

        let currentGame = null;

        function updateStatus(message, isError = false) {
            const statusEl = document.getElementById('status-text');
            statusEl.textContent = message;
            statusEl.style.color = isError ? '#ff6b6b' : '#51cf66';
        }

        // SDK初期化
        (async function() {
            try {
                await Net8.init(API_KEY);
                updateStatus('✅ SDK初期化完了');

                // 機種一覧取得
                const models = await Net8.getModels();
                const modelsContainer = document.getElementById('models');

                models.forEach(model => {
                    const card = document.createElement('div');
                    card.className = 'model-card';
                    card.innerHTML = `
                        <h3>${model.name}</h3>
                        <p>${model.category}</p>
                    `;
                    card.onclick = () => startGame(model.id, model.name);
                    modelsContainer.appendChild(card);
                });

                updateStatus(`✅ ${models.length}機種が利用可能`);

            } catch (error) {
                updateStatus('❌ SDK初期化エラー: ' + error.message, true);
            }
        })();

        // ゲーム起動
        async function startGame(modelId, modelName) {
            try {
                if (currentGame) {
                    currentGame.stop();
                }

                updateStatus(`${modelName}を起動中...`);

                currentGame = Net8.createGame({
                    model: modelId,
                    container: '#game-container'
                });

                currentGame.on('ready', () => {
                    updateStatus(`✅ ${modelName} 準備完了`);
                });

                currentGame.on('started', () => {
                    updateStatus(`✅ ${modelName} プレイ中`);
                });

                currentGame.on('error', (err) => {
                    updateStatus(`❌ エラー: ${err.message}`, true);
                });

                await currentGame.start();

            } catch (error) {
                updateStatus(`❌ 起動エラー: ${error.message}`, true);
            }
        }
    </script>
</body>
</html>
```

---

## 環境切り替え

### テスト環境 → 本番環境への移行

#### 移行タイミング

以下のテストが完了したら、本番環境への移行を検討してください：

- ✅ 全機種の起動確認完了
- ✅ UI/UXの動作確認完了
- ✅ エラーハンドリングの確認完了
- ✅ レスポンシブ対応の確認完了
- ✅ ブラウザ互換性の確認完了

#### 移行手順

**ステップ1**: NET8開発チームに本番環境APIキーを依頼

メール件名: `[NET8] 本番環境APIキー発行依頼`

メール本文:
```
お世話になっております。
貴社名です。

テスト環境での統合テストが完了しましたので、
本番環境用APIキーの発行をお願いいたします。

【テスト環境APIキー】
pk_demo_xxxxxxxxxx

【確認済み事項】
- 全機種の起動確認: 完了
- エラーハンドリング: 完了
- ブラウザ互換性確認: 完了

よろしくお願いいたします。
```

**ステップ2**: 本番用APIキーを受領

NET8開発チームから `pk_live_yyyyyyyy` 形式のキーが発行されます。

**ステップ3**: コード内のAPIキーを変更

```javascript
// 変更前（テスト環境）
const API_KEY = 'pk_demo_xxxxxxxxxx';

// 変更後（本番環境）
const API_KEY = 'pk_live_yyyyyyyy';
```

**これだけです！** 他のコードは一切変更不要です。

#### 本番環境の違い

| 項目 | テスト環境 | 本番環境 |
|------|----------|---------|
| APIキー | `pk_demo_*` | `pk_live_*` |
| マシン接続 | モック（仮想） | 実機 |
| レスポンス | 即座に返却 | 実機の状態に依存 |
| エラー発生 | ほぼなし | マシン利用中などで発生可能 |

---

## トラブルシューティング

### Q1: SDKが初期化できない

**症状**: `Net8.init()` でエラーが発生

**考えられる原因**:
1. APIキーが間違っている
2. ネットワーク接続がない
3. CORSエラー（file:// で開いている）

**解決方法**:

```javascript
try {
    await Net8.init(API_KEY);
} catch (error) {
    console.error('初期化エラー詳細:', error);

    // エラーメッセージを確認
    if (error.message.includes('CORS')) {
        alert('ローカルサーバーで起動してください（file:// では動作しません）');
    } else if (error.message.includes('invalid key')) {
        alert('APIキーが無効です。NET8開発チームに確認してください');
    } else if (error.message.includes('network')) {
        alert('ネットワーク接続を確認してください');
    }
}
```

### Q2: ゲームが表示されない

**症状**: `game.start()` を実行しても画面が空白

**確認事項**:
1. コンテナ要素が存在するか
2. コンテナのサイズが設定されているか

**解決方法**:

```html
<!-- 明示的なサイズ設定 -->
<div id="game-container" style="
    width: 800px;
    height: 600px;
    border: 1px solid #ccc;
    background: #000;
"></div>
```

### Q3: "NO_AVAILABLE_MACHINE" エラー

**症状**: 本番環境で「利用可能なマシンがありません」

**原因**: 全マシンが使用中、またはマシン登録がない

**解決方法**:
1. しばらく待ってから再試行
2. NET8開発チームに連絡してマシン追加を依頼

**注意**: テスト環境ではこのエラーは発生しません。

### Q4: コンソールに403エラー

**症状**: `Forbidden` または `Access Denied`

**原因**: APIキーの有効期限切れまたは無効化

**解決方法**:
NET8開発チームに連絡してAPIキーの状態を確認

---

## セキュリティ

### APIキーの管理

#### ❌ 避けるべき方法

```javascript
// 悪い例: フロントエンドに直接記述（本番環境）
const API_KEY = 'pk_live_xxxxxxxxxx'; // ← 本番キーは露出してはいけない
```

#### ✅ 推奨される方法

**テスト環境**: フロントエンドに直接記述してもOK

```javascript
const API_KEY = 'pk_demo_xxxxxxxxxx'; // テストキーは露出してもOK
```

**本番環境**: サーバーサイドでプロキシ経由

```javascript
// フロントエンド
const API_KEY = await fetch('/api/get-net8-key').then(r => r.text());
await Net8.init(API_KEY);
```

```php
// サーバーサイド（/api/get-net8-key）
<?php
// 環境変数から取得
echo getenv('NET8_API_KEY');
?>
```

### 環境変数の設定

#### .env ファイル例

```bash
# NET8 SDK設定
NET8_API_KEY=pk_live_xxxxxxxxxx
NET8_API_URL=https://mgg-webservice-production.up.railway.app
NET8_ENVIRONMENT=production
```

---

## サポート

### ドキュメント

- **ユーザーズマニュアル**: NET8_SDK_USER_MANUAL_v1.01.md
- **技術仕様書**: NET8_SDK_TECHNICAL_MANUAL_v1.01.md
- **API仕様**: NET8_JAVASCRIPT_SDK_SPEC.md（提供された場合）

### デモサイト

完全動作デモ:
https://mgg-webservice-production.up.railway.app/sdk/demo.html

### お問い合わせ

**技術サポート**: NET8開発チーム
**Email**: support@net8.example.com（仮）

**サポート対応時間**: 平日 10:00-18:00（日本時間）

---

## チェックリスト

統合完了前に以下を確認してください：

### テスト環境

- [ ] APIキーを受領した
- [ ] 環境設定ファイル（JSON/CSV）を受領した
- [ ] SDKが正常に初期化できる
- [ ] 最低1機種のゲームが起動できる
- [ ] ブラウザコンソールにエラーがない
- [ ] レスポンスに `"mock": true` が含まれている

### 本番環境移行前

- [ ] 全機種の起動テスト完了
- [ ] エラーハンドリングの実装完了
- [ ] UI/UXの調整完了
- [ ] ブラウザ互換性確認完了
- [ ] 本番用APIキーを受領した
- [ ] APIキーをサーバーサイドで管理している（推奨）
- [ ] HTTPS環境で動作確認した

### 本番環境移行後

- [ ] 本番環境で最低1機種の起動確認
- [ ] レスポンスに `"mock": false` が含まれている
- [ ] 実機との接続が確認できた
- [ ] エラーハンドリングが正常に動作している

---

**NET8 Gaming SDK v1.01 - お客様向け設定ガイド**
© 2025 NET8 Development Team

このガイドは統合パートナー企業様専用です。
第三者への転送・共有はご遠慮ください。

---

発行日: 2025年11月07日
