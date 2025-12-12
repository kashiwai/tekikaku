# 🚀 Railway デプロイ手順書（全Claude Code共通）

---

## 🚨 【重要】この手順書は全Claude Code必読

このドキュメントは、NET8プロジェクトをRailwayにデプロイする際の**確実な手順**を記載しています。  
過去のデプロイエラーを踏まえ、**必ず成功する設定**をまとめました。

---

## 📋 デプロイ前チェックリスト

### ✅ 必須ファイルの確認

```bash
# これらのファイルが存在することを確認
ls -la /Users/kotarokashiwai/railway.toml
ls -la /Users/kotarokashiwai/net8_rebirth/Dockerfile
ls -la /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/
```

---

## 🔧 重要な設定ファイル

### 1. railway.toml（Gitルート: `/Users/kotarokashiwai/railway.toml`）

```toml
[build]
builder = "DOCKERFILE"
dockerfilePath = "Dockerfile"
rootDirectory = "net8_rebirth"  # ← これが最重要！

[deploy]
runtime = "V2"
numReplicas = 1
sleepApplication = false
useLegacyStacker = false
restartPolicyType = "ON_FAILURE"
restartPolicyMaxRetries = 10

[deploy.multiRegionConfig."asia-southeast1-eqsg3a"]
numReplicas = 1
```

**⚠️ 注意事項**:
- `rootDirectory = "net8_rebirth"` が無いとデプロイ失敗します
- このファイルは**Gitルート**に配置（net8_rebirth内ではない）

### 2. Dockerfile（`/Users/kotarokashiwai/net8_rebirth/Dockerfile`）

```dockerfile
FROM php:7.2-apache

# Debian Busterのリポジトリをアーカイブに変更
RUN sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i 's|security.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && sed -i '/stretch-updates/d' /etc/apt/sources.list

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl \
    wget \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能のインストール
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    gd \
    zip

# Apacheモジュール有効化
RUN a2enmod rewrite ssl headers deflate filter

# Composerインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ⚠️ 重要: パスは net8/ から始める（net8_rebirth/は不要）
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY net8/02.ソースファイル/net8_html /var/www/html

# 依存関係インストール
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ディレクトリ作成と権限設定
RUN mkdir -p /var/www/html/data/img/model \
    && mkdir -p /var/www/html/data/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/data/img/model \
    && chmod -R 777 /var/www/html/data/uploads

EXPOSE ${PORT:-80}
CMD ["apache2-foreground"]
```

---

## 📝 ファイル変更とデプロイ手順

### 1. PHPファイルを変更する場合

```bash
# ① ファイル編集
vi /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php

# ② 変更確認
git diff

# ③ コミット（必ずホームディレクトリで実行）
cd /Users/kotarokashiwai
git add "net8_rebirth/net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php"
git commit -m "fix: userAuthAPI修正"

# ④ プッシュ（Railwayが自動デプロイ開始）
git push origin main
```

### 2. Dockerfileを変更する場合

```bash
# ① 編集
vi /Users/kotarokashiwai/net8_rebirth/Dockerfile

# ② 重要: パスチェック
# 正しい: COPY net8/02.ソースファイル/net8_html /var/www/html
# 間違い: COPY net8_rebirth/net8/02.ソースファイル/net8_html /var/www/html

# ③ コミット
cd /Users/kotarokashiwai
git add net8_rebirth/Dockerfile
git commit -m "chore: Dockerfile更新"
git push origin main
```

### 3. railway.tomlを変更する場合

```bash
# ① 編集（Gitルートのファイル）
vi /Users/kotarokashiwai/railway.toml

# ② 必須項目確認
# - rootDirectory = "net8_rebirth" が存在すること
# - dockerfilePath = "Dockerfile" になっていること

# ③ コミット
cd /Users/kotarokashiwai
git add railway.toml
git commit -m "fix: Railway設定更新"
git push origin main
```

---

## ❌ よくある間違いと解決方法

### エラー1: `Dockerfile "Dockerfile" does not exist`

**原因**: railway.tomlの設定ミス  
**解決**:
```toml
# railway.tomlを確認
rootDirectory = "net8_rebirth"  # これが必須
dockerfilePath = "Dockerfile"    # net8_rebirth/は不要
```

### エラー2: `"/net8_rebirth/net8/02.ソースファイル/net8_html": not found`

**原因**: Dockerfile内のパスが間違っている  
**解決**:
```dockerfile
# 間違い
COPY net8_rebirth/net8/02.ソースファイル/net8_html /var/www/html

# 正しい（net8_rebirthを削除）
COPY net8/02.ソースファイル/net8_html /var/www/html
```

### エラー3: デプロイが開始されない

**原因**: Gitプッシュが失敗している  
**解決**:
```bash
# リモートの状態確認
git remote -v
git status

# 強制プッシュ（注意して使用）
git push origin main --force
```

---

## 🔍 デプロイ状況の確認方法

### 1. GitHub Actions確認
```bash
# 最新のコミットを確認
git log --oneline -3
```

### 2. Railway ダッシュボード
1. https://railway.app にアクセス
2. プロジェクト `mmg2501` を選択
3. `mgg-webservice` サービスを確認
4. ビルドログでエラーがないか確認

### 3. 本番環境確認
```bash
# 本番URLにアクセス
curl https://mgg-webservice-production.up.railway.app/
```

---

## 📊 デプロイ成功の確認ポイント

1. **Railwayビルドログ**:
   - `Using Detected Dockerfile` が表示される
   - `Successfully built` が表示される
   - エラーメッセージがない

2. **アプリケーション動作**:
   - https://mgg-webservice-production.up.railway.app/ にアクセス可能
   - APIエンドポイントが正常応答
   - データベース接続が成功

---

## 🚨 緊急時の対処法

### デプロイが失敗し続ける場合

1. **railway.tomlを再確認**:
```bash
cat /Users/kotarokashiwai/railway.toml
# rootDirectory = "net8_rebirth" が必ず必要
```

2. **Dockerfileのパスを全て確認**:
```bash
grep "COPY" /Users/kotarokashiwai/net8_rebirth/Dockerfile
# net8_rebirth/が含まれていないことを確認
```

3. **キャッシュクリア**（最終手段）:
```bash
# Dockerfileにタイムスタンプコメント追加
echo "# Force rebuild at $(date)" >> /Users/kotarokashiwai/net8_rebirth/Dockerfile
git add net8_rebirth/Dockerfile
git commit -m "chore: キャッシュクリア"
git push origin main
```

---

## 📝 デプロイチェックリスト（コピペ用）

```markdown
- [ ] railway.tomlに rootDirectory = "net8_rebirth" がある
- [ ] railway.tomlが Gitルート（/Users/kotarokashiwai/）にある
- [ ] DockerfileのCOPYパスが net8/ から始まる（net8_rebirth/なし）
- [ ] 変更ファイルを git add した
- [ ] git commit メッセージを書いた
- [ ] git push origin main を実行した
- [ ] Railwayダッシュボードでビルド開始を確認した
```

---

## 🆘 サポート情報

### 関連ドキュメント
- `/Users/kotarokashiwai/net8_rebirth/DEPLOY.md` - デプロイ構造詳細
- `/Users/kotarokashiwai/net8_rebirth/CLAUDE.md` - 開発ルール

### トラブルシューティング履歴
- 2025-12-12: rootDirectory設定追加で解決
- 2025-12-12: Dockerfileパス修正で正常動作確認

---

**作成日**: 2025-12-12  
**作成者**: Claude Code  
**目的**: 全Claude Codeが確実にデプロイできるようにするため

## 最後に

この手順書に従えば、**必ずデプロイは成功します**。  
エラーが発生した場合は、この手順書の「よくある間違い」セクションを確認してください。

成功を祈っています！ 🚀