# Railway デプロイ後の手順

## デプロイごとにテーブルが消える問題の対処法

Railwayにデプロイするたびにデータベーステーブルが消えてしまう場合、以下の手順を実行してください。

### **重要：デプロイ後に必ず実行してください**

```
https://dockerfileweb-production.up.railway.app/auto_setup_db.php?key=auto_setup_2025
```

このスクリプトは：
- ✓ 4つの必須テーブルの存在を確認
- ✓ 不足しているテーブルを自動作成
- ✓ 正しいlicense_cd値を設定
- ✓ Windows PC (MAC: 34-a6-ef-35-73-73) の設定を復元

### デプロイ後の完全な手順

**1. デプロイ完了を確認**
- Railwayダッシュボードで "Success" を確認

**2. データベースセットアップ**
```
https://dockerfileweb-production.up.railway.app/auto_setup_db.php?key=auto_setup_2025
```
→ "✅ Database is ready!" が表示されればOK

**3. Windows側でテスト**
```powershell
cd C:\serverset
.\slotserver.exe -c COM4
```
→ "status:ok" が表示されればOK

## 根本的な解決方法（推奨）

### Railway MySQLサービスにVolumeを設定

1. **Railwayダッシュボード** → プロジェクト選択
2. **MySQLサービス** (meticulous-vitality) をクリック
3. **Settings** タブ → **Volumes** セクション
4. **+ Add Volume** をクリック
5. **Mount Path**: `/var/lib/mysql` と入力
6. **Save** をクリック

これにより、デプロイ後もデータが永続化されます。

### 環境変数の固定（既に設定済み）

以下の環境変数がWebサービスに設定されていることを確認：

```
MYSQLHOST=meticulous-vitality.railway.internal
MYSQL_DATABASE=net8_dev
MYSQLUSER=root
MYSQL_ROOT_PASSWORD=DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy
```

## サービス構成

```
Railway Services:
├─ Web: https://dockerfileweb-production.up.railway.app
│   ├─ Root Directory: railway-web
│   └─ DocumentRoot: /var/www/html/data
│
├─ MySQL: meticulous-vitality.railway.internal
│   ├─ Database: net8_dev
│   ├─ User: root
│   └─ Port: 3306
│
└─ Signaling: https://dockerfilesignaling-production.up.railway.app
    ├─ Root Directory: railway-signaling
    └─ Port: 8080
```

## 必須テーブル

1. **mst_cameralist** - カメラ端末情報（license_cd含む）
2. **mst_camera** - カメラ情報
3. **dat_machine** - マシン情報
4. **mst_model** - 機種情報（layout_data, prizeball_data含む）

## トラブルシューティング

### テーブルの存在確認
```
https://dockerfileweb-production.up.railway.app/check_env.php?key=check_env_2025
```

### API動作確認
```
https://dockerfileweb-production.up.railway.app/test_api_response.php?key=test_api_2025
```

### ファイル存在確認
```
https://dockerfileweb-production.up.railway.app/check_files.php?key=check_files_2025
```

## セキュリティ注意事項

本番運用時は、以下のスクリプトを削除してください：
- auto_setup_db.php
- check_env.php
- check_files.php
- check_tables.php
- test_api_response.php
- create_cameralist_table.php
- create_api_tables.php
- update_correct_license_cd.php
- add_license_cd_column.php

または、`$EXEC_KEY` の値を変更してセキュリティを強化してください。
