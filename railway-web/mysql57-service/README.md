# MySQL 5.7 Service for Railway

PHP 7.2と互換性のあるMySQL 5.7をRailwayで動かすための設定です。

## 環境変数設定（Railway UI で設定）

以下の環境変数をRailway Dashboardで設定してください：

```
MYSQL_ROOT_PASSWORD=DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy
MYSQL_DATABASE=net8_dev
MYSQL_USER=net8user
MYSQL_PASSWORD=net8pass
```

## デプロイ手順

1. Railway Dashboardで「New Service」をクリック
2. 「GitHub Repo」を選択
3. 同じリポジトリ（mgg001）を選択
4. Root Directory を `mysql57-service` に設定
5. 環境変数を上記の通り設定
6. Deploy

## ダンプインポート

デプロイ後、外部接続用のURLとポートが発行されます。
ローカルから以下のコマンドでダンプをインポート：

```bash
mysql -h [RAILWAY_MYSQL_HOST] -P [PORT] -u root -p[PASSWORD] net8_dev < /tmp/net8_db_dump_clean.sql
```

## 接続情報

- 内部接続（Railway内のサービスから）: `mysql57:3306`
- 外部接続: Railway Dashboardで確認
