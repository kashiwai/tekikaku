# 中国向け NET8 API 簡易設計仕様書

**作成日**: 2025-12-31
**バージョン**: 2.0.0-simplified
**対象環境**: Railway Production

---

## 📋 要件の明確化

### KY側（中国チーム）の実装方針
1. **UI/UX**: 既存のNet8トップページと同じデザインをそのまま使用
2. **埋め込み方式**: iframeで全Net8ページを埋め込むのみ
3. **連携**: iframeの内外での相互連携は行わない
4. **API**: 通貨情報の受け渡しとポイント処理のみ

### MGGO側（開発チーム）の対応
1. **UI変更**: なし（既存UIをそのまま提供）
2. **API対応**: 通貨パラメータ追加のみ
3. **iframe対応**: 全ページで動作確認（実装済み）
4. **iOS対応**: 修正済み

---

## ✅ 現状確認

### iframe対応状況 - **完了済み**

#### 1. API v1 (.htaccess)
```apache
# CORS headers (強化版)
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS, PATCH"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept, Origin"
Header always set Access-Control-Allow-Credentials "true"
```
✅ **全オリジンからのAPI呼び出しを許可**

#### 2. play_embed/.htaccess
```apache
# X-Frame-Options を削除（iFrame埋め込み許可）
Header always unset X-Frame-Options

# CORS設定（外部サイトからのiFrame埋め込みを許可）
Header always set Access-Control-Allow-Origin "*"
```
✅ **全オリジンからのiframe埋め込みを許可**

#### 3. frame_security.php
```php
// X-Frame-Options と CSP は設定しない（全iFrame埋め込み許可）
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
```
✅ **プログラマティックにiframe許可を設定**

---

## 🎯 実装タスク（簡略版）

### Phase 1: データベース準備
- [ ] テーブル構造確認（check_currency_schema.php 実行）
- [ ] currency カラム追加（ALTER TABLE）
- [ ] mst_currency テーブル作成

### Phase 2: API拡張（通貨対応のみ）
- [ ] game_start.php に `currency` パラメータ追加
- [ ] game_end.php で通貨情報をレスポンスに含める
- [ ] currency_helper.php 作成（フォーマット関数）

### Phase 3: iframe動作確認
- [ ] iframe埋め込みテストページ作成
- [ ] デスクトップブラウザ動作確認
- [ ] iOS Safari 動作確認

### Phase 4: ドキュメント
- [ ] API仕様書作成（日本語・中国語）
- [ ] iframe埋め込み方法ドキュメント
- [ ] サンプルコード提供

---

## 📝 API設計（変更点のみ）

### game_start.php - 新規パラメータ

**リクエスト**:
```json
{
  "modelId": "HOKUTO4GO",
  "userId": "china_user_123",
  "initialPoints": 1000,
  "currency": "CNY",  // ← 新規追加（省略時: "JPY"）
  "lang": "zh"
}
```

**レスポンス**:
```json
{
  "success": true,
  "sessionId": "sess_abc123",
  "balance": {
    "amount": 900,
    "currency": "CNY",       // ← 追加
    "formatted": "900元"      // ← 追加
  },
  "gameUrl": "https://mgg-webservice-production.up.railway.app/data/play_embed/?sessionId=sess_abc123"
}
```

### game_end.php - レスポンス拡張のみ

**レスポンス**:
```json
{
  "success": true,
  "finalBalance": {
    "amount": 1400,
    "currency": "CNY",       // ← 追加
    "formatted": "1400元"     // ← 追加
  },
  "pointsAdded": 500
}
```

---

## 🌐 iframe埋め込み方法

### KY側の実装例（簡易版）

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Net8 游戏 - 中国版</title>
    <style>
        body { margin: 0; padding: 0; overflow: hidden; }
        #gameFrame {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            border: none;
        }
    </style>
</head>
<body>
    <iframe
        id="gameFrame"
        src="https://mgg-webservice-production.up.railway.app/data/play_embed/"
        allow="autoplay; fullscreen; camera; microphone"
        allowfullscreen>
    </iframe>

    <script>
        // 1. APIでゲーム開始（ポイント送信）
        async function startGame() {
            const response = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/game_start.php', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer YOUR_API_KEY',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    modelId: 'HOKUTO4GO',
                    userId: 'china_user_123',
                    initialPoints: 1000,
                    currency: 'CNY',  // 通貨指定
                    lang: 'zh'
                })
            });

            const data = await response.json();

            // 2. gameUrlをiframeに設定
            document.getElementById('gameFrame').src = data.gameUrl;
        }

        // ページ読み込み時にゲーム開始
        startGame();
    </script>
</body>
</html>
```

---

## 📊 データベース変更（最小限）

### 必要なALTER TABLE

```sql
-- sdk_users
ALTER TABLE sdk_users
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード' AFTER balance;

-- user_balances
ALTER TABLE user_balances
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード' AFTER balance;

-- game_sessions
ALTER TABLE game_sessions
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'セッション時の通貨' AFTER points_consumed;

-- his_play（構造確認後）
ALTER TABLE his_play
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'プレイ時の通貨';
```

### 通貨マスタ（新規）

```sql
CREATE TABLE IF NOT EXISTS mst_currency (
  currency_code VARCHAR(3) PRIMARY KEY,
  currency_name VARCHAR(100) NOT NULL,
  currency_symbol VARCHAR(10),
  decimal_places TINYINT DEFAULT 0,
  is_active TINYINT DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO mst_currency VALUES
('JPY', '日本円', '¥', 0, 1, NOW(), NOW()),
('CNY', '人民元', '元', 2, 1, NOW(), NOW()),
('KRW', '韓国ウォン', '₩', 0, 1, NOW(), NOW());
```

---

## 🔧 実装するファイル

### 修正ファイル
1. `/api/v1/game_start.php` - currency パラメータ受け取り
2. `/api/v1/game_end.php` - currency レスポンス追加
3. `/api/v1/helpers/user_helper.php` - 通貨対応

### 新規ファイル
1. `/api/v1/helpers/currency_helper.php` - 通貨フォーマット関数
2. `/data/xxxadmin/migrate_currency.php` - マイグレーション
3. `/data/xxxadmin/test_iframe_embed.html` - iframeテスト

---

## ✅ テスト計画

### 1. データベーステスト
```bash
# スキーマ確認
https://mgg-webservice-production.up.railway.app/data/xxxadmin/check_currency_schema.php

# マイグレーション実行
https://mgg-webservice-production.up.railway.app/data/xxxadmin/migrate_currency.php
```

### 2. API テスト

```bash
# CNY通貨でゲーム開始
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer {API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "HOKUTO4GO",
    "userId": "test_china_user",
    "initialPoints": 1000,
    "currency": "CNY",
    "lang": "zh"
  }'
```

### 3. iframe埋め込みテスト

```bash
# テストページにアクセス
https://mgg-webservice-production.up.railway.app/data/xxxadmin/test_iframe_embed.html
```

### 4. iOS Safari テスト
- iPad / iPhone でiframe埋め込みページを開く
- カメラ・マイク権限の確認
- ゲームプレイの動作確認

---

## 📅 スケジュール（簡略版）

| タスク | 期間 | 完了 |
|--------|------|------|
| データベース準備 | 0.5日 | ⏳ |
| API実装 | 1日 | ⏳ |
| iframe動作確認 | 0.5日 | ⏳ |
| ドキュメント作成 | 0.5日 | ⏳ |
| **合計** | **2.5日** | |

---

## 🎉 完了条件

1. ✅ `currency` パラメータでゲーム開始できる
2. ✅ レスポンスに通貨情報が含まれる
3. ✅ iframe埋め込みでゲームが正常に動作する
4. ✅ iOS Safari でも動作する
5. ✅ API仕様書が完成している

---

## 📌 重要なポイント

### UI/UX変更なし
- 既存のNet8画面をそのまま使用
- デザイン変更は一切なし
- KY側はiframe埋め込みのみ

### API変更のみ
- `currency` パラメータ追加
- レスポンスに通貨情報追加
- 内部的な通貨管理のみ

### iframe対応済み
- CORS設定完了
- X-Frame-Options削除済み
- 全ページでiframe埋め込み可能

---

**次のステップ**:
1. `check_currency_schema.php` 実行
2. マイグレーション実行
3. API実装
4. iframe動作確認
