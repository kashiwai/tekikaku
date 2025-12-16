# NET8 Railway デプロイ完全ガイド

**最終更新**: 2025-12-16
**バージョン**: 2.0（MPM問題解決版）

---

## 目次

1. [概要](#概要)
2. [ディレクトリ構造](#ディレクトリ構造)
3. [重要なファイルの場所](#重要なファイルの場所)
4. [Dockerfile設定](#dockerfile設定)
5. [デプロイ手順](#デプロイ手順)
6. [トラブルシューティング](#トラブルシューティング)
7. [DB初期化手順](#db初期化手順)
8. [チェックリスト](#チェックリスト)

---

## 概要

### Railwayサービス構成

| サービス名 | 種類 | URL | 用途 |
|-----------|------|-----|------|
| mgg-webservice | PHP/Apache | mgg-webservice-production-final.up.railway.app | メインアプリ |
| mgg-signaling | PeerJS | mgg-signaling-production.up.railway.app | WebRTC シグナリング |
| mysql | MySQL 5.7 | (内部) | データベース |

### カスタムドメイン
- **https://net8games.win/** （Cloudflare経由）

---

## ディレクトリ構造

```
/Users/kotarokashiwai/           ← Gitルート（ホームディレクトリ）
├── Dockerfile                   ← ★実際にRailwayが使用するDockerfile★
├── docker-entrypoint.sh         ← ★起動時MPMクリーンアップスクリプト★
├── railway.toml                 ← Railway設定ファイル
│
└── net8_rebirth/                ← プロジェクトディレクトリ
    ├── Dockerfile               ← ※使われない（参考用）
    ├── docker-entrypoint.sh     ← ※使われない（参考用）
    ├── .dockerignore
    ├── DEPLOY_COMPLETE_GUIDE.md ← このファイル
    ├── CLAUDE.md
    │
    └── net8/
        ├── 02.ソースファイル/
        │   └── net8_html/       ← PHPアプリケーション本体
        │       ├── index.php
        │       ├── data/
        │       │   ├── api/
        │       │   └── play_v2/
        │       └── _etc/
        │
        └── docker/
            └── web/
                ├── php.ini
                └── apache-config/
                    └── 000-default.conf
```

---

## 重要なファイルの場所

### ★最重要★ Railwayが実際に使用するファイル

| ファイル | 正しい場所 | 備考 |
|---------|-----------|------|
| **Dockerfile** | `/Users/kotarokashiwai/Dockerfile` | Gitルート直下 |
| **docker-entrypoint.sh** | `/Users/kotarokashiwai/docker-entrypoint.sh` | Gitルート直下 |
| **railway.toml** | `/Users/kotarokashiwai/railway.toml` | Gitルート直下 |

### よくある間違い

```
❌ 間違い: /Users/kotarokashiwai/net8_rebirth/Dockerfile を編集
✅ 正しい: /Users/kotarokashiwai/Dockerfile を編集
```

### railway.toml 設定

```toml
[build]
builder = "DOCKERFILE"
dockerfilePath = "Dockerfile"
rootDirectory = "net8_rebirth"

[deploy]
runtime = "V2"
numReplicas = 1
sleepApplication = false
restartPolicyType = "ON_FAILURE"
restartPolicyMaxRetries = 10
```

**注意**: `rootDirectory = "net8_rebirth"` が設定されていても、**Dockerfileは Gitルートから検索される**場合があります。

---

## Dockerfile設定

### 正しいDockerfile（Gitルート: /Users/kotarokashiwai/Dockerfile）

```dockerfile
FROM php:7.2-apache

# キャッシュ無効化（変更時に更新）
RUN echo "FORCE-REBUILD-YYYY-MM-DD-vX" > /tmp/cache-bust

# Debian Busterのリポジトリをアーカイブに変更（EOLのため）
RUN sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i 's|security.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && sed -i '/stretch-updates/d' /etc/apt/sources.list

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev libonig-dev zip unzip git curl wget netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能のインストール
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql mbstring xml gd zip

# ★★★ MPMモジュールの競合を完全に解決 ★★★
# mpm_event と mpm_worker の .so ファイルを物理削除
RUN rm -f /usr/lib/apache2/modules/mod_mpm_event.so \
    && rm -f /usr/lib/apache2/modules/mod_mpm_worker.so \
    && rm -f /etc/apache2/mods-available/mpm_event.* \
    && rm -f /etc/apache2/mods-available/mpm_worker.* \
    && rm -f /etc/apache2/mods-enabled/mpm_*.load \
    && rm -f /etc/apache2/mods-enabled/mpm_*.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && a2enmod rewrite headers deflate

# Composerインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定ファイルをコピー（パスはnet8/から始める）
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# アプリケーションファイルをコピー
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

# ★★★ カスタムエントリポイント（起動時にもMPMクリーンアップ）★★★
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE ${PORT:-80}

# エントリポイントで起動
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
```

### docker-entrypoint.sh（Gitルート: /Users/kotarokashiwai/docker-entrypoint.sh）

```bash
#!/bin/bash
set -e

# MPMモジュールの競合を起動時に確実に解消
echo "Cleaning up MPM modules..."
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf

# mpm_preforkのみ有効化
if [ ! -f /etc/apache2/mods-enabled/mpm_prefork.load ]; then
    ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
fi
if [ ! -f /etc/apache2/mods-enabled/mpm_prefork.conf ]; then
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
fi

# 有効なMPMを表示
echo "Enabled MPM modules:"
ls -la /etc/apache2/mods-enabled/mpm_* 2>/dev/null || echo "No MPM found"

# Apache設定テスト
echo "Testing Apache configuration..."
apache2ctl -t

# Apache起動
echo "Starting Apache..."
exec apache2-foreground
```

---

## デプロイ手順

### 方法1: Git Push（推奨）

```bash
# 1. ホームディレクトリに移動
cd /Users/kotarokashiwai

# 2. 変更を確認
git status
git diff

# 3. 変更をステージング
git add Dockerfile docker-entrypoint.sh
# または特定のファイル
git add "net8_rebirth/net8/02.ソースファイル/net8_html/ファイル名"

# 4. コミット
git commit -m "fix: 修正内容の説明"

# 5. プッシュ（Railwayが自動デプロイ開始）
git push origin main
```

### デプロイ確認

1. **Railwayダッシュボード**: https://railway.app
2. プロジェクト `mmg2501` → `mgg-webservice` を選択
3. **Deployments** タブでビルドログを確認
4. 本番URL確認: https://net8games.win/

---

## トラブルシューティング

### エラー1: `AH00534: More than one MPM loaded`

**原因**: Apache MPMモジュール（mpm_event, mpm_worker, mpm_prefork）が複数有効

**解決方法**:

1. **Dockerfile**に以下を追加（ビルド時のクリーンアップ）:
```dockerfile
RUN rm -f /usr/lib/apache2/modules/mod_mpm_event.so \
    && rm -f /usr/lib/apache2/modules/mod_mpm_worker.so \
    && rm -f /etc/apache2/mods-available/mpm_event.* \
    && rm -f /etc/apache2/mods-available/mpm_worker.* \
    && rm -f /etc/apache2/mods-enabled/mpm_*.load \
    && rm -f /etc/apache2/mods-enabled/mpm_*.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
```

2. **docker-entrypoint.sh**を追加（起動時のクリーンアップ）

3. **キャッシュ無効化**:
```dockerfile
RUN echo "FORCE-REBUILD-$(date +%Y%m%d-%H%M)" > /tmp/cache-bust
```

### エラー2: `Dockerfile does not exist`

**原因**: Dockerfileの場所が間違っている

**解決方法**:
- Dockerfileは**Gitルート**（`/Users/kotarokashiwai/Dockerfile`）に配置
- `net8_rebirth/Dockerfile`ではない

### エラー3: キャッシュが効いて修正が反映されない

**症状**: Dockerfileを変更したのに、ビルドログに`cached`と表示される

**解決方法**:
```dockerfile
# Dockerfileの先頭付近に追加
RUN echo "FORCE-REBUILD-2025-12-16-v10" > /tmp/cache-bust
```
毎回異なる値に変更してコミット

### エラー4: ビルドログの確認方法

ビルドログで以下を確認:

1. **正しいDockerfileが使われているか**:
   - `RUN echo "FORCE-REBUILD-..."` が表示されること
   - MPM削除コマンドが実行されていること

2. **起動ログで以下が表示されるか**:
   ```
   Cleaning up MPM modules...
   Enabled MPM modules:
   mpm_prefork.conf -> ...
   mpm_prefork.load -> ...
   Syntax OK
   Starting Apache...
   ```

---

## DB初期化手順

デプロイ後、DBが完全にリセットされた場合、以下の順番でセットアップ:

### 自動セットアップ（推奨）
```
https://net8games.win/xxxadmin/auto_setup.php
```

### 手動セットアップ（順番に実行）

| STEP | URL | 内容 |
|------|-----|------|
| 1 | /complete_setup.php | 基本マスタ登録（機種、メーカー、管理者） |
| 2 | /check_convertpoint_table.php | ポイント変換テーブル |
| 3 | /register_camera.php | カメラマスター登録 |
| 4 | /register_machine_complete.php | 実機登録 |
| 5 | /update_hokuto_image.php | 画像パス設定 |
| 6 | /debug_index_sql.php | データ確認（3件表示されればOK） |
| 7 | / | トップページで北斗の拳が3台表示されることを確認 |

---

## チェックリスト

### デプロイ前チェック

- [ ] `/Users/kotarokashiwai/Dockerfile` を編集した（net8_rebirth/ではない）
- [ ] `/Users/kotarokashiwai/docker-entrypoint.sh` が存在する
- [ ] Dockerfileにキャッシュ無効化の行がある
- [ ] DockerfileにMPM削除コマンドがある
- [ ] DockerfileでENTRYPOINTがdocker-entrypoint.shを指定している

### デプロイ後チェック

- [ ] ビルドログに`cached`が多用されていない
- [ ] ビルドログにMPM削除が実行されている
- [ ] 起動ログに「Cleaning up MPM modules...」が表示
- [ ] 起動ログに「Syntax OK」が表示
- [ ] https://net8games.win/ にアクセス可能
- [ ] HTTP 200が返る

---

## データベース接続情報

### GCP Cloud SQL
| 項目 | 値 |
|------|-----|
| ホスト | 136.116.70.86 |
| DB名 | net8_dev |
| ユーザー | net8tech001 |
| パスワード | Nene11091108!! |
| ポート | 3306 |

---

## 履歴

| 日付 | バージョン | 変更内容 |
|------|-----------|---------|
| 2025-12-16 | 2.0 | MPM問題解決、正しいDockerfile場所を明記、entrypoint追加 |
| 2025-12-12 | 1.0 | 初版作成 |

---

## 関連ファイル

- **このガイド**: `/Users/kotarokashiwai/net8_rebirth/DEPLOY_COMPLETE_GUIDE.md`
- **開発ルール**: `/Users/kotarokashiwai/net8_rebirth/CLAUDE.md`
- **Dockerfile**: `/Users/kotarokashiwai/Dockerfile`
- **entrypoint**: `/Users/kotarokashiwai/docker-entrypoint.sh`
- **Railway設定**: `/Users/kotarokashiwai/railway.toml`
