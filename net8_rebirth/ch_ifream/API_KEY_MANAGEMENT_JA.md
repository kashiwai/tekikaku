# NET8 APIキー発行・管理マニュアル（日本語版）

**バージョン:** 1.0.0
**最終更新:** 2026年1月30日
**対象:** NET8管理者・外部パートナー

---

## 📋 目次

1. [概要](#概要)
2. [APIキー管理システム](#apiキー管理システム)
3. [APIキーの発行方法](#apiキーの発行方法)
4. [APIキーの種類](#apiキーの種類)
5. [管理画面の使い方](#管理画面の使い方)
6. [パートナーへの提供方法](#パートナーへの提供方法)
7. [セキュリティガイドライン](#セキュリティガイドライン)
8. [トラブルシューティング](#トラブルシューティング)

---

## 概要

NET8では、外部パートナーがAPIを利用するために**APIキー認証システム**を採用しています。本マニュアルでは、APIキーの発行・管理方法について説明します。

### システム構成

- **管理画面:** `/xxxadmin/api_keys_manage.php`
- **データベーステーブル:** `api_keys`, `api_usage_logs`
- **認証エンドポイント:** `/api/v1/auth.php`

---

## APIキー管理システム

### データベース構造

#### api_keys テーブル

```sql
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NULL,
  `key_value` VARCHAR(100) NOT NULL UNIQUE,    -- APIキー値
  `key_type` VARCHAR(20) NOT NULL DEFAULT 'public',
  `name` VARCHAR(100) NULL,                     -- キー名（識別用）
  `environment` VARCHAR(20) NOT NULL DEFAULT 'test',  -- test or live
  `rate_limit` INT(10) UNSIGNED NOT NULL DEFAULT 1000,  -- レート制限
  `is_active` TINYINT(4) NOT NULL DEFAULT 1,    -- 有効/無効
  `last_used_at` DATETIME NULL,                 -- 最終使用日時
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL,                   -- 有効期限（オプション）
  PRIMARY KEY (`id`),
  KEY `idx_key_value` (`key_value`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### api_usage_logs テーブル（使用統計）

```sql
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `api_key_id` INT(10) UNSIGNED NOT NULL,
  `endpoint` VARCHAR(255) NOT NULL,             -- 呼び出されたエンドポイント
  `method` VARCHAR(10) NOT NULL,                -- HTTPメソッド
  `status_code` INT(10) UNSIGNED NULL,          -- レスポンスステータス
  `response_time_ms` INT(10) UNSIGNED NULL,     -- レスポンス時間
  `ip_address` VARCHAR(45) NULL,                -- IPアドレス
  `user_agent` VARCHAR(512) NULL,               -- User-Agent
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_key_id` (`api_key_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

## APIキーの発行方法

### 方法1: 管理画面から発行（推奨）

**アクセスURL:**
```
https://ifreamnet8-development.up.railway.app/xxxadmin/api_keys_manage.php
```

**ステップ:**

1. **管理画面にログイン**
   - 管理者アカウントでログイン

2. **「新しいAPIキーを生成」セクションに移動**

3. **必要情報を入力:**
   - **キー名:** パートナー識別名（例: "中国パートナーA社"）
   - **環境:** `test`（テスト用）または `live`（本番用）

4. **「生成」ボタンをクリック**

5. **生成されたAPIキーをコピー**
   - 例: `pk_live_a1b2c3d4e5f6...`
   - ⚠️ **重要:** この画面でしかキー値は表示されません！必ずコピーして保存してください

---

### 方法2: SQLで直接発行

**テスト環境用キー:**

```sql
INSERT INTO `api_keys` (
  `key_value`,
  `key_type`,
  `name`,
  `environment`,
  `rate_limit`,
  `is_active`
) VALUES (
  'pk_test_abc123def456',  -- ランダムな文字列
  'public',
  '中国パートナーA社（テスト）',
  'test',
  10000,
  1
);
```

**本番環境用キー:**

```sql
INSERT INTO `api_keys` (
  `key_value`,
  `key_type`,
  `name`,
  `environment`,
  `rate_limit`,
  `is_active`
) VALUES (
  'pk_live_xyz789ghi012',  -- ランダムな文字列
  'public',
  '中国パートナーA社（本番）',
  'live',
  100000,
  1
);
```

**安全なキー値の生成:**

```bash
# Linuxコマンドラインで生成
echo "pk_live_$(openssl rand -hex 16)"

# 出力例: pk_live_f3a8b2c9d4e5f6g7h8i9j0k1l2m3n4o5
```

---

## APIキーの種類

### 環境別

| 環境 | プレフィックス | 用途 | レート制限（デフォルト） |
|------|---------------|------|------------------------|
| **test** | `pk_test_` | テスト・開発用 | 10,000回/日 |
| **live** | `pk_live_` | 本番環境用 | 100,000回/日 |

### タイプ別

| タイプ | 説明 | 権限 |
|--------|------|------|
| **public** | パブリックキー | フロントエンドで使用可能、読み取り専用 |
| **secret** | シークレットキー | サーバー側のみ、書き込み権限あり |

**現在の実装:** すべて `public` タイプ（JWT認証で保護）

---

## 管理画面の使い方

### 画面構成

#### 1. APIキー生成セクション

```
┌─────────────────────────────────────────┐
│ 🔑 新しいAPIキーを生成                   │
│                                         │
│ キー名: [__________________________]   │
│ 環境:   [test ▼]                       │
│                                         │
│ [生成] ボタン                           │
└─────────────────────────────────────────┘
```

#### 2. APIキー一覧セクション

```
┌─────────────────────────────────────────────────────────────┐
│ ID | キー名 | キー値 | 環境 | レート制限 | 状態 | 最終使用 │
├────┼────────┼────────┼──────┼──────────┼──────┼─────────┤
│ 1  │ A社    │ pk_... │ live │ 100000   │ 有効 │ 1時間前  │
│ 2  │ B社    │ pk_... │ test │ 10000    │ 無効 │ 3日前    │
└─────────────────────────────────────────────────────────────┘
```

#### 3. 使用統計セクション

```
┌─────────────────────────────────────────┐
│ 📊 使用統計（直近7日間）                 │
│                                         │
│ 日付       | リクエスト数 | 平均応答時間 │
│ 2026-01-30 | 15,234      | 120ms       │
│ 2026-01-29 | 14,892      | 115ms       │
└─────────────────────────────────────────┘
```

### 主な機能

#### APIキーの有効化/無効化

1. APIキー一覧で該当キーの「切替」ボタンをクリック
2. `is_active` が 1（有効）↔ 0（無効）に切り替わる
3. 無効化されたキーは即座に使用不可になる

**用途:**
- 一時的な停止（メンテナンス時）
- セキュリティインシデント発生時の緊急停止
- パートナー契約終了時の永久無効化

#### 使用状況の確認

- **最終使用日時:** いつ最後に使われたか
- **リクエスト数:** 日次の使用回数
- **平均応答時間:** API パフォーマンス指標

---

## パートナーへの提供方法

### ステップ1: APIキーの発行

1. 管理画面でパートナー用のAPIキーを生成
2. 環境に応じて `test` または `live` を選択
3. キー値を安全にコピー

### ステップ2: 認証情報パッケージの作成

**提供する情報:**

```yaml
パートナー名: 中国パートナーA社
環境: live（本番環境）

認証情報:
  API Key: pk_live_f3a8b2c9d4e5f6g7h8i9j0k1l2m3n4o5
  Base URL: https://ifreamnet8-development.up.railway.app/api/v1

制限事項:
  レート制限: 100,000リクエスト/日
  有効期限: なし（無期限）

ドキュメント:
  - API_MANUAL_JA.md（日本語版）
  - API_MANUAL_ZH.md（中国語版）
  - REALTIME_CALLBACK_GUIDE_JA.md
  - REALTIME_CALLBACK_GUIDE_ZH.md
```

### ステップ3: セキュアな方法で送信

**推奨方法:**

1. **暗号化メール** - S/MIME または PGP暗号化
2. **セキュアファイル共有** - Box, Dropbox Business, Google Drive（パスワード保護）
3. **専用ポータル** - パートナー専用の管理画面

**❌ 避けるべき方法:**

- 平文メール
- Slack/ChatWorkなどのチャット
- SMS/電話

### ステップ4: 使用開始の確認

1. パートナーにテスト実施を依頼
2. 管理画面で使用統計を確認
3. 問題なければ本番環境へ移行

---

## セキュリティガイドライン

### APIキーの管理

#### ✅ すべきこと

- **環境変数に保存:** サーバー環境変数に格納
- **定期的なローテーション:** 3〜6ヶ月ごとに更新
- **最小権限の原則:** 必要最小限の権限のみ付与
- **使用状況の監視:** 異常なアクセスパターンを検知
- **バックアップ:** パートナー情報を安全に記録

#### ❌ してはいけないこと

- **コードにハードコード:** ソースコードに直接記述
- **バージョン管理に含める:** Git/SVNにコミット
- **クライアント側で使用:** ブラウザのJavaScriptで使用
- **複数パートナーで共有:** 1パートナー = 1キー
- **期限切れキーの放置:** 不要なキーは削除

### インシデント対応

#### 漏洩が疑われる場合

1. **即座に無効化:** 管理画面で該当キーを無効化
2. **影響範囲の調査:** `api_usage_logs` で不正使用を確認
3. **新しいキーの発行:** 新規キーを生成してパートナーに提供
4. **原因の特定:** どのように漏洩したか調査
5. **再発防止策:** セキュリティ対策の強化

---

## トラブルシューティング

### 問題1: 「Invalid API Key」エラー

**症状:**
```json
{
  "error": "INVALID_API_KEY",
  "message": "Invalid or expired API key"
}
```

**原因と解決方法:**

| 原因 | 確認方法 | 解決方法 |
|------|---------|---------|
| キーが無効化されている | `is_active = 0` | 管理画面で有効化 |
| キーが存在しない | `SELECT * FROM api_keys WHERE key_value = '...'` | 正しいキーを使用 |
| キーの有効期限切れ | `expires_at < NOW()` | 新しいキーを発行 |
| タイポ | キー値を再確認 | 正確にコピー＆ペースト |

---

### 問題2: レート制限エラー

**症状:**
```json
{
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Rate limit exceeded"
}
```

**解決方法:**

```sql
-- レート制限を増やす
UPDATE api_keys
SET rate_limit = 200000
WHERE key_value = 'pk_live_...';
```

---

### 問題3: 管理画面でエラー

**症状:** 「api_keysテーブルが存在しない可能性があります」

**解決方法:**

```bash
# SQLファイルを実行してテーブルを作成
mysql -u username -p database_name < /path/to/setup_api_keys_table.sql
```

または管理画面から：

```sql
-- setup_api_keys_table.sql の内容をコピー＆ペーストして実行
```

---

## よくある質問（FAQ）

### Q1: APIキーは何個まで発行できますか？

**A:** 制限はありませんが、管理の観点から以下を推奨：
- パートナーごとに1つの本番キー
- テスト用に1つの開発キー
- 合計で50〜100個程度に留める

---

### Q2: APIキーの有効期限は設定できますか？

**A:** はい。`expires_at` カラムに日時を設定：

```sql
UPDATE api_keys
SET expires_at = '2026-12-31 23:59:59'
WHERE id = 1;
```

---

### Q3: APIキーをリセットすることはできますか？

**A:** いいえ。セキュリティ上の理由から、一度発行したキーの値は変更できません。新しいキーを発行して、古いキーを無効化してください。

---

### Q4: test環境とlive環境のキーを同時に使えますか？

**A:** はい。同一パートナーに対して両方の環境用のキーを発行できます。

---

### Q5: APIキーの使用統計を確認できますか？

**A:** はい。管理画面の「使用統計」セクションで以下を確認できます：
- 日次のリクエスト数
- 平均応答時間
- 最終使用日時

---

## 付録: 自動化スクリプト

### APIキー生成スクリプト（Bash）

```bash
#!/bin/bash
# generate_api_key.sh

# 使用方法: ./generate_api_key.sh "パートナー名" "test|live"

PARTNER_NAME=$1
ENVIRONMENT=${2:-test}
PREFIX="pk_${ENVIRONMENT}_"
KEY_VALUE="${PREFIX}$(openssl rand -hex 16)"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "NET8 APIキー生成"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "パートナー名: $PARTNER_NAME"
echo "環境: $ENVIRONMENT"
echo "APIキー: $KEY_VALUE"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "以下のSQLを実行してください:"
echo ""
cat <<EOF
INSERT INTO api_keys (key_value, name, environment, rate_limit, is_active)
VALUES (
  '$KEY_VALUE',
  '$PARTNER_NAME',
  '$ENVIRONMENT',
  $([ "$ENVIRONMENT" = "live" ] && echo "100000" || echo "10000"),
  1
);
EOF
```

---

## サポート

APIキー発行・管理に関する質問:

- 📧 **Email:** support@net8gaming.com
- 🌐 **Website:** https://net8gaming.com
- 📱 **技術サポート:** https://docs.net8gaming.com

---

**© 2026 NET8 Gaming. All rights reserved.**
