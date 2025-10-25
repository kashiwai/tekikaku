# NET8 Docker環境 セットアップガイド

このドキュメントは、NET8システムをDocker環境で起動し、AWS環境へ移行するための完全ガイドです。

## 目次

1. [前提条件](#前提条件)
2. [クイックスタート](#クイックスタート)
3. [環境設定](#環境設定)
4. [Docker構成](#docker構成)
5. [トラブルシューティング](#トラブルシューティング)
6. [AWS移行ガイド](#aws移行ガイド)

---

## 前提条件

以下のソフトウェアがインストールされていることを確認してください：

- Docker Desktop 20.10以上
- Docker Compose 2.0以上
- Git（ソース管理用）

### インストール確認

```bash
docker --version
docker-compose --version
```

---

## クイックスタート

### 1. 環境変数ファイルの作成

```bash
cp .env.example .env
```

`.env` ファイルを開き、必要に応じて設定を変更してください。
特に本番環境では、以下の値を必ず変更してください：

- `DB_PASSWORD`: データベースパスワード
- `MYSQL_ROOT_PASSWORD`: MySQLルートパスワード
- `DEBUG_MODE`: 本番環境では `false` に設定

### 2. Dockerコンテナの起動

```bash
# イメージのビルドとコンテナの起動
docker-compose up -d --build

# ログの確認
docker-compose logs -f
```

### 3. アクセス確認

ブラウザで以下のURLにアクセスしてください：

- **Webアプリケーション（ユーザー側）**: http://localhost:8080
- **管理画面**: http://localhost:8080/xxxadmin/login.php
- **シグナリングサーバ**: http://localhost:59000

#### 管理画面ログイン情報

**Basic認証（管理画面アクセス時）**
- ユーザー名: `basicuser`
- パスワード: `admin123`

**管理者アカウント**
- ID: `sradmin`
- パスワード: `admin123`

⚠️ **セキュリティ警告**: 上記のパスワードは開発環境用です。本番環境では必ず変更してください。

### 4. 停止と再起動

```bash
# 停止
docker-compose stop

# 再起動
docker-compose start

# 完全削除（データベースも削除されます）
docker-compose down -v
```

---

## 環境設定

### .env ファイルの詳細

| 変数名 | デフォルト値 | 説明 |
|--------|-------------|------|
| `DB_HOST` | `db` | データベースホスト名 |
| `DB_NAME` | `net8_dev` | データベース名 |
| `DB_USER` | `net8user` | データベースユーザ名 |
| `DB_PASSWORD` | `net8pass` | データベースパスワード ⚠️本番環境では変更必須 |
| `MYSQL_ROOT_PASSWORD` | `root_password_change_me` | MySQLルートパスワード ⚠️本番環境では変更必須 |
| `SITE_URL` | `http://localhost:8080` | サイトURL |
| `DEBUG_MODE` | `true` | デバッグモード（本番: `false`） |
| `SIGNALING_PORT` | `59000` | シグナリングサーバポート |

---

## Docker構成

### サービス一覧

#### 1. Web (Apache + PHP 7.2)

- **ポート**: 8080:80
- **ソースコード**: `./02.ソースファイル/net8_html` をマウント
- **DocumentRoot**: `/var/www/html/data`
- **設定ファイル**:
  - `docker/web/apache-config/000-default.conf`: Apache設定
  - `docker/web/php.ini`: PHP設定

#### 2. Database (MySQL 5.7)

- **ポート**: 3306:3306
- **初期化スクリプト**: `docker/mysql/init/` 配下のSQLファイルを自動実行
- **永続化**: 名前付きボリューム `mysql_data` を使用

#### 3. Signaling (PeerJS Server)

- **ポート**: 59000:59000
- **ソースコード**: `./01.サーバ構築手順/net8peerjs-server`
- **WebRTC**: ピア間通信のシグナリングサーバ

### ディレクトリ構造

```
net8/
├── docker/
│   ├── web/
│   │   ├── Dockerfile
│   │   ├── apache-config/
│   │   │   └── 000-default.conf
│   │   └── php.ini
│   ├── signaling/
│   │   └── Dockerfile
│   └── mysql/
│       └── init/
│           ├── 01_create.sql
│           ├── 02_init.sql
│           └── 03_alter.sql
├── docker-compose.yml
├── .env
└── .env.example
```

---

## トラブルシューティング

### コンテナが起動しない

```bash
# コンテナの状態確認
docker-compose ps

# ログの確認
docker-compose logs web
docker-compose logs db
docker-compose logs signaling

# コンテナの再ビルド
docker-compose down
docker-compose up -d --build
```

### データベース接続エラー

```bash
# データベースコンテナに接続
docker-compose exec db mysql -u root -p

# データベースとユーザの確認
SHOW DATABASES;
SELECT user, host FROM mysql.user;
```

### ポート競合エラー

`.env` ファイルまたは `docker-compose.yml` でポート番号を変更してください：

```yaml
services:
  web:
    ports:
      - "8081:80"  # 8080 → 8081に変更
```

### ファイルのパーミッションエラー

```bash
# コンテナ内でパーミッション修正
docker-compose exec web chown -R www-data:www-data /var/www/html
docker-compose exec web chmod -R 755 /var/www/html
```

### データベースの初期化リセット

```bash
# すべてのコンテナとボリュームを削除
docker-compose down -v

# 再度起動（データベースが初期化される）
docker-compose up -d --build
```

---

## AWS移行ガイド

Docker環境はAWS環境への移行を前提に設計されています。

### 移行ステップ

#### 1. ECR（Amazon Elastic Container Registry）へのプッシュ

```bash
# ECRにログイン
aws ecr get-login-password --region ap-northeast-1 | \
  docker login --username AWS --password-stdin <your-account-id>.dkr.ecr.ap-northeast-1.amazonaws.com

# イメージのタグ付け
docker tag net8-web:latest <your-account-id>.dkr.ecr.ap-northeast-1.amazonaws.com/net8-web:latest
docker tag net8-signaling:latest <your-account-id>.dkr.ecr.ap-northeast-1.amazonaws.com/net8-signaling:latest

# イメージのプッシュ
docker push <your-account-id>.dkr.ecr.ap-northeast-1.amazonaws.com/net8-web:latest
docker push <your-account-id>.dkr.ecr.ap-northeast-1.amazonaws.com/net8-signaling:latest
```

#### 2. RDS（Amazon Relational Database Service）のセットアップ

1. RDS MySQL 5.7インスタンスを作成
2. セキュリティグループでアクセスを許可
3. データベースとユーザを作成
4. 初期化SQLを実行：

```bash
# ローカルからRDSへ接続
mysql -h <rds-endpoint> -u admin -p

# データベース作成
CREATE DATABASE net8_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'net8user'@'%' IDENTIFIED BY '<secure-password>';
GRANT ALL PRIVILEGES ON net8_production.* TO 'net8user'@'%';
FLUSH PRIVILEGES;

# 初期化スクリプトの実行
mysql -h <rds-endpoint> -u net8user -p net8_production < docker/mysql/init/01_create.sql
mysql -h <rds-endpoint> -u net8user -p net8_production < docker/mysql/init/02_init.sql
mysql -h <rds-endpoint> -u net8user -p net8_production < docker/mysql/init/03_alter.sql
```

#### 3. ECS（Amazon Elastic Container Service）のセットアップ

**タスク定義の作成:**

```json
{
  "family": "net8-web",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "512",
  "memory": "1024",
  "containerDefinitions": [
    {
      "name": "web",
      "image": "<your-account-id>.dkr.ecr.ap-northeast-1.amazonaws.com/net8-web:latest",
      "portMappings": [
        {
          "containerPort": 80,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {
          "name": "DB_HOST",
          "value": "<rds-endpoint>"
        },
        {
          "name": "DB_NAME",
          "value": "net8_production"
        },
        {
          "name": "DEBUG_MODE",
          "value": "false"
        }
      ],
      "secrets": [
        {
          "name": "DB_PASSWORD",
          "valueFrom": "arn:aws:secretsmanager:ap-northeast-1:<account-id>:secret:net8/db-password"
        }
      ]
    }
  ]
}
```

**サービスの作成:**

```bash
aws ecs create-service \
  --cluster net8-cluster \
  --service-name net8-web-service \
  --task-definition net8-web \
  --desired-count 2 \
  --launch-type FARGATE \
  --network-configuration "awsvpcConfiguration={subnets=[subnet-xxx],securityGroups=[sg-xxx],assignPublicIp=ENABLED}" \
  --load-balancers targetGroupArn=arn:aws:elasticloadbalancing:...,containerName=web,containerPort=80
```

#### 4. ALB（Application Load Balancer）のセットアップ

1. ALBを作成
2. ターゲットグループを作成（ヘルスチェックパス: `/`）
3. リスナーを設定（HTTP:80、HTTPS:443）
4. SSL証明書をACMから取得してアタッチ

#### 5. 環境変数の管理

AWS Systems Manager Parameter Store または AWS Secrets Manager を使用：

```bash
# Secrets Managerにシークレットを保存
aws secretsmanager create-secret \
  --name net8/db-password \
  --secret-string "secure-password-here"

aws secretsmanager create-secret \
  --name net8/mysql-root-password \
  --secret-string "secure-root-password-here"
```

### 推奨AWSアーキテクチャ

```
                           ┌─────────────┐
                           │  Route 53   │
                           └──────┬──────┘
                                  │
                           ┌──────▼──────┐
                           │   CloudFront │ (Optional)
                           └──────┬──────┘
                                  │
┌─────────────────────────────────▼─────────────────────────────────┐
│                           VPC (10.0.0.0/16)                        │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │             Application Load Balancer                    │    │
│  └────┬───────────────────────────────┬─────────────────────┘    │
│       │                               │                           │
│  ┌────▼─────────────┐          ┌──────▼───────────┐              │
│  │  Public Subnet   │          │  Public Subnet   │              │
│  │   (AZ-1a)        │          │   (AZ-1c)        │              │
│  └────┬─────────────┘          └──────┬───────────┘              │
│       │                               │                           │
│  ┌────▼────────────────┐      ┌───────▼──────────────┐           │
│  │ ECS Fargate Task    │      │ ECS Fargate Task     │           │
│  │ - net8-web          │      │ - net8-web           │           │
│  │ - net8-signaling    │      │ - net8-signaling     │           │
│  └─────────────────────┘      └──────────────────────┘           │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │             Private Subnet (AZ-1a, 1c)                   │    │
│  │                                                           │    │
│  │   ┌──────────────┐              ┌──────────────┐        │    │
│  │   │ RDS MySQL    │──────────────│ RDS MySQL    │        │    │
│  │   │ (Primary)    │              │ (Standby)    │        │    │
│  │   └──────────────┘              └──────────────┘        │    │
│  └──────────────────────────────────────────────────────────┘    │
└────────────────────────────────────────────────────────────────────┘
```

### コスト最適化のヒント

1. **Fargate Spot**: 本番以外の環境でコスト削減
2. **RDS Reserved Instances**: 長期運用でコスト削減
3. **CloudWatch Logs**: ログの保持期間を適切に設定
4. **Auto Scaling**: トラフィックに応じてタスク数を調整

---

## 開発ワークフロー

### ローカル開発

```bash
# コンテナを起動
docker-compose up -d

# ソースコードを編集（ホットリロード対応）
# 02.ソースファイル/net8_html/ 配下のファイルを編集

# ログの確認
docker-compose logs -f web

# データベースのバックアップ
docker-compose exec db mysqldump -u root -p net8_dev > backup.sql

# コンテナのシェルに入る
docker-compose exec web bash
docker-compose exec db bash
```

### テスト環境へのデプロイ

```bash
# イメージのビルド
docker-compose build

# ECRへプッシュ
./scripts/push-to-ecr.sh

# ECSタスク定義の更新
aws ecs register-task-definition --cli-input-json file://ecs-task-definition.json

# サービスの更新
aws ecs update-service --cluster test-cluster --service net8-web-service --force-new-deployment
```

---

## セキュリティ考慮事項

### 本番環境でのチェックリスト

- [ ] `.env` ファイルのパスワードを強固なものに変更
- [ ] `DEBUG_MODE=false` に設定
- [ ] 不要なポートを閉じる
- [ ] HTTPS（SSL/TLS）を有効化
- [ ] データベースのバックアップを定期実行
- [ ] CloudWatch Logsでログを監視
- [ ] WAF（Web Application Firewall）の設定
- [ ] セキュリティグループの最小権限設定
- [ ] IAMロールの適切な権限設定

---

## サポート

問題が発生した場合は、以下を確認してください：

1. Docker/Docker Composeのバージョン
2. エラーログ（`docker-compose logs`）
3. システムリソース（メモリ、ディスク容量）

詳細なサポートが必要な場合は、開発チームまでお問い合わせください。
