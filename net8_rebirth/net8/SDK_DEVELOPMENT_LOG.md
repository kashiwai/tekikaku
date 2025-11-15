# NET8 SDK開発ログ - 詳細記録
**最終更新**: 2025-11-13
**SDKバージョン**: 1.01-beta
**ステータス**: テストパートナーアカウント作成完了、マニュアル作成待ち

---

## 📋 現在の状態

### ✅ 完了した作業

#### 1. iframe ナビゲーション問題の修正
**問題**: SDK埋め込み時に「TOPへ戻る」ボタンが動作しない

**修正内容**:
- `_html/ja/play/no_assign.html`: `target="_top"` 追加（29行目）
- `_html/en/play/no_assign.html`: `target="_top"` 追加（28行目）
- `_html/ja/play/reload_error.html`: iframe検知ロジック追加（98-106行目）

```javascript
// reload_error.html の修正（98-106行目）
if (countdown <= 0) {
    clearInterval(interval);
    // iframe内の場合は親ウィンドウを遷移
    if (window.top !== window.self) {
        window.top.location.href = retryButton.href;
    } else {
        window.location.href = retryButton.href;
    }
}
```

**コミット**: `main ca40043` - iframe navigation fixes

---

#### 2. Apache静的リソース配信の修正
**問題**: CSS/JS/画像ファイルが404エラー

**修正内容**:
- `docker/web/apache-config/000-default.conf`: Alias設定追加（69-90行目）

```apache
# 静的リソースのAlias設定
Alias /css /var/www/html/data/css
Alias /js /var/www/html/data/js
Alias /img /var/www/html/data/img

<Directory /var/www/html/data/css>
    Options FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

**コミット**: `main eb7b181` - Apache Alias configuration

---

#### 3. 管理画面セッション管理の修正
**問題**: `api_keys_manage.php`にログインできない

**根本原因**: `$template`オブジェクトが初期化されていなかった

**修正内容**:
- `data/xxxadmin/api_keys_manage.php`: `$template = new TemplateAdmin();` 追加（11行目）

```php
// 修正後（8-17行目）
require_once('../../_etc/require_files_admin.php');

// TemplateAdminインスタンスを生成（セッション管理含む）
$template = new TemplateAdmin();

// 管理者ログインチェック
if (!isset($template->Session->AdminInfo)) {
    header('Location: login.php');
    exit;
}
```

**コミット**: `main 45ad5df` - Initialize TemplateAdmin instance

---

#### 4. テストパートナーアカウント自動生成
**作成ファイル**: `create_test_partner_account.php`

**機能**:
- APIキー自動生成（pk_demo_プレフィックス）
- データベース登録
- JSON形式のクレデンシャル出力
- テスト用HTML生成

**Apache設定追加**:
- `000-default.conf` 54行目: RewriteCond除外追加

```apache
RewriteCond %{REQUEST_URI} !^/create_test_partner_account\.php
```

**コミット**:
- `main ca40043` - Add test partner account creation script
- `main 6ebba70` - Add to Apache RewriteCond exclusions

---

## 🔑 生成されたテストアカウント情報

### APIキー詳細
```json
{
  "client": {
    "id": 6,
    "name": "Test Partner Company (Netlify Demo)",
    "environment": "test",
    "sdk_version": "1.01-beta"
  },
  "credentials": {
    "api_key": "pk_demo_cc5276f2f9c341538179dd5ded93e350",
    "rate_limit": 10000,
    "expires_at": "2026-11-13"
  },
  "endpoints": {
    "sdk_url": "https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js",
    "demo_url": "https://mgg-webservice-production.up.railway.app/sdk/demo.html",
    "auth": "https://mgg-webservice-production.up.railway.app/api/v1/auth.php",
    "game_start": "https://mgg-webservice-production.up.railway.app/api/v1/game_start.php"
  },
  "test_mode": true,
  "mock_data": {
    "enabled": true,
    "description": "テスト環境では実機接続不要。モックデータで動作します。"
  }
}
```

### アクセス情報
```
アカウント生成URL: https://mgg-webservice-production.up.railway.app/create_test_partner_account.php
管理画面URL: https://mgg-webservice-production.up.railway.app/xxxadmin/login.php
管理者ID: sradmin
パスワード: admin123
APIキー管理: https://mgg-webservice-production.up.railway.app/xxxadmin/api_keys_manage.php
```

---

## 🛠️ 技術的な変更点まとめ

### 修正したファイル一覧

| ファイル | 変更内容 | 行番号 |
|---------|---------|--------|
| `_html/ja/play/no_assign.html` | `target="_top"` 追加 | 29 |
| `_html/en/play/no_assign.html` | `target="_top"` 追加 | 28 |
| `_html/ja/play/reload_error.html` | iframe検知ロジック | 98-106 |
| `docker/web/apache-config/000-default.conf` | Alias設定追加 | 69-90 |
| `docker/web/apache-config/000-default.conf` | RewriteCond除外追加 | 54 |
| `data/xxxadmin/api_keys_manage.php` | TemplateAdmin初期化 | 11 |

### 新規作成ファイル

| ファイル | 用途 |
|---------|------|
| `create_test_partner_account.php` | テストパートナーアカウント自動生成 |

---

## 📊 データベース変更

### api_keysテーブル - 新規レコード

```sql
INSERT INTO api_keys (
    id,
    key_value,
    key_type,
    name,
    environment,
    rate_limit,
    is_active,
    expires_at,
    created_at
) VALUES (
    6,
    'pk_demo_cc5276f2f9c341538179dd5ded93e350',
    'public',
    'Test Partner Company (Netlify Demo) - SDK v1.01',
    'test',
    10000,
    1,
    '2026-11-13',
    NOW()
);
```

---

## 🎯 次のステップ（未完了タスク）

### 1. SDKマニュアル作成（日本語）
**ファイル**: `sdk/docs/integration-guide-ja.md`

**必須内容**:
- クイックスタート（5分で統合）
- APIキー取得方法
- HTML統合コード
- 初期化手順
- ゲーム開始手順
- エラーハンドリング
- トラブルシューティング

**使用するAPIキー**: `pk_demo_cc5276f2f9c341538179dd5ded93e350`

### 2. SDKマニュアル作成（英語）
**ファイル**: `sdk/docs/integration-guide-en.md`

**必須内容**: 日本語版と同じ構成

### 3. テスト用HTMLサンプル
**ファイル**: `sdk/examples/test-integration.html`

**必須機能**:
- SDK初期化ボタン
- ゲーム開始ボタン
- ログ表示エリア
- コンソールエラー検出
- ステップバイステップガイド

### 4. お客様向け統合ガイド（PDF/HTML）
**対象**: 技術者でない担当者向け
**内容**:
- 環境準備
- APIキー設定方法
- テスト手順
- 本番環境移行手順

---

## 🔧 環境情報

### Railway デプロイ情報
```
プロジェクト: mgg-webservice-production
URL: https://mgg-webservice-production.up.railway.app
GitHub: https://github.com/mgg00123mg-prog/mgg001.git
ブランチ: main
最新コミット: 6ebba70 (feat: Add create_test_partner_account.php to Apache RewriteCond exclusions)
```

### データベース接続
```
ホスト: DB_HOST（環境変数）
データベース: net8_dev
ユーザー: net8tech001
文字セット: utf8mb4
```

### 使用技術スタック
```
サーバー: Apache 2.4
PHP: 7.4+
データベース: MySQL 8.0
フロントエンド: JavaScript (ES6+), jQuery
SDK: カスタムJavaScript SDK v1.01-beta
コンテナ: Docker
CI/CD: Railway自動デプロイ
```

---

## 📝 重要な設定値

### X-Frame-Options設定
```apache
# デフォルト: 全ページにX-Frame-Options SAMEORIGINを設定
Header always set X-Frame-Options "SAMEORIGIN"

# SDK統合のための例外設定
<Location "/data/play_v2">
    Header always unset X-Frame-Options
    Header always set Content-Security-Policy "frame-ancestors *"
</Location>

<Location "/sdk">
    Header always unset X-Frame-Options
    Header always set Content-Security-Policy "frame-ancestors *"
</Location>
```

**理由**: SDK埋め込みサービスのため、play_v2とsdkディレクトリは任意のドメインからのiframe埋め込みを許可

### セキュリティ対策
1. **APIキー認証**: 全リクエストでAPIキー検証
2. **レート制限**: 10,000 req/day
3. **JWT発行**: 認証成功時に1時間有効なトークン発行
4. **CORS設定**: 必要なオリジンのみ許可
5. **セッション管理**: 管理画面は24分タイムアウト

---

## 🐛 既知の問題と解決済み

### ✅ 解決済み

1. **vendor/ ファイル404エラー**
   - 原因: 相対パスと絶対パスの混在
   - 解決: 全HTMLテンプレートを絶対パスに統一

2. **CSS MIME type エラー**
   - 原因: Apache Alias設定不足
   - 解決: /css, /js, /img の Alias追加

3. **iframe内ナビゲーション失敗**
   - 原因: target属性未指定
   - 解決: target="_top" 追加 + iframe検知ロジック

4. **api_keys_manage.php ログインできない**
   - 原因: $templateオブジェクト未初期化
   - 解決: TemplateAdminインスタンス生成追加

### ⚠️ 注意事項

1. **パスワードリセット**: 本番環境では必ずadmin123から変更すること
2. **APIキー管理**: テストキーと本番キーを明確に分離
3. **git警告**: `git prune` 実行推奨（unreachable loose objects）

---

## 📧 お客様とのコミュニケーション

### 送付済み情報
1. X-Frame-Options変更の説明（英語）
2. iframe対応の技術的背景
3. セキュリティ多層防御の説明

### 送付予定
1. SDKマニュアル（日英）
2. テスト統合ガイド
3. APIキー（pk_demo_cc5276f2f9c341538179dd5ded93e350）

---

## 🔄 Git履歴

```bash
# 主要コミット
6ebba70 - feat: Add create_test_partner_account.php to Apache RewriteCond exclusions
45ad5df - fix: Initialize TemplateAdmin instance in api_keys_manage.php
eb7b181 - fix: コーナー管理画面のテーブル位置修正 & 完了メッセージ日本語化
ca40043 - feat: Add test partner account creation script for SDK v1.01
```

---

## 🚀 再開時の手順

### 即座に実行可能なコマンド

1. **現在の状態確認**:
```bash
cd /Users/kotarokashiwai/net8_rebirth
git status
git log --oneline -5
```

2. **テストアカウント確認**:
```bash
curl https://mgg-webservice-production.up.railway.app/create_test_partner_account.php
```

3. **管理画面アクセス**:
```
URL: https://mgg-webservice-production.up.railway.app/xxxadmin/login.php
ID: sradmin / PASS: admin123
```

### 次の作業開始

```bash
# 1. SDKマニュアル作成（日本語）
# ファイル: net8/02.ソースファイル/net8_html/sdk/docs/integration-guide-ja.md

# 2. SDKマニュアル作成（英語）
# ファイル: net8/02.ソースファイル/net8_html/sdk/docs/integration-guide-en.md

# 3. テストHTMLサンプル
# ファイル: net8/02.ソースファイル/net8_html/sdk/examples/test-integration.html

# 4. デプロイ
git add .
git commit -m "docs: Add SDK integration guides (ja/en) and test samples"
git push origin main
```

---

## 📚 参考リンク

- **SDK Demo**: https://mgg-webservice-production.up.railway.app/sdk/demo.html
- **SDK JS**: https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js
- **API認証**: https://mgg-webservice-production.up.railway.app/api/v1/auth.php
- **ゲーム開始**: https://mgg-webservice-production.up.railway.app/api/v1/game_start.php

---

**作成者**: Claude Code
**プロジェクト**: NET8 Gaming SDK
**ドキュメントバージョン**: 1.0
**最終確認日**: 2025-11-13
