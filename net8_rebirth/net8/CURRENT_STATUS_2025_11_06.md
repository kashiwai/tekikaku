# NET8 プロジェクト現在の状況 - 2025/11/06

## 🎯 現在のミッション
**機種No.3「ミリオンゴッド4号機」をDBに登録し、MACHINE-03で遊べるようにする**

---

## ✅ 完了済みの作業

### 1. SQLSTATE[HY093]バグの完全修正
以下の5ファイル、14箇所を修正済み（すべてGitHub/Railwayにデプロイ済み）：
- `data/xxxadmin/model.php` (3箇所)
- `data/xxxadmin/maker.php` (2箇所)
- `data/xxxadmin/member.php` (7箇所)
- `data/xxxadmin/owner.php` (1箇所)
- `data/xxxadmin/pointconvert.php` (1箇所)

**修正内容**: SqlStringクラスが空文字列パラメータでSQL構築するとPDOエラーが発生する問題を修正
- SQL構築前に値が入力されているかチェック
- nullセーフガード追加

### 2. Machine #3 登録用ファイル作成
以下のファイルを作成済み：
1. `/Users/kotarokashiwai/net8_rebirth/net8/insert_milliongod_model3.sql` - SQL直接実行用
2. `/Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/execute_insert_model3.php` - Web経由実行用

### 3. Git状況
- **リポジトリ**: https://github.com/mgg00123mg-prog/mgg001.git
- **ブランチ**: main
- **最新コミット**: `b9f9588` (2025/11/06 プッシュ済み)
- **Gitルート**: `/Users/kotarokashiwai/` （重要！）

---

## ❌ 未解決の問題

### Railway Root Directory設定が間違っている

**現在の設定（間違い）**:
```
Root Directory: net8_rebirth
```

**正しい設定（これに変更必要）**:
```
Root Directory: net8_rebirth/net8/02.ソースファイル/net8_html
```

**理由**:
- Gitルートが `/Users/kotarokashiwai/` である
- PHPファイルは `/Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/` にある
- Railwayは `net8_rebirth` を見ているため、PHPファイルが見つからず404エラー

**症状**:
```bash
curl https://mgg-webservice-production.up.railway.app/execute_insert_model3.php
# 結果: 404 Not Found
```

---

## 🚀 次にやるべきこと（最優先）

### ステップ1: Railway Root Directory設定変更

1. Railway WebUI にアクセス: https://railway.app/dashboard
2. プロジェクト: `mmg2501`
3. 環境: `production`
4. サービス: `mgg-webservice-production`
5. 「Settings」タブをクリック
6. 「Source」セクションを見つける
7. 「Root Directory」を以下に変更:
   ```
   net8_rebirth/net8/02.ソースファイル/net8_html
   ```
8. 「Redeploy」ボタンをクリック
9. デプロイ完了まで待つ（1〜3分）

### ステップ2: デプロイ確認

デプロイ完了後、以下のURLにアクセス:
```bash
# テストファイルで確認
curl https://mgg-webservice-production.up.railway.app/railway_test.php
# 期待される結果: "Railway Test OK - Commit: b9f9588"

# 本番ファイルで確認
curl https://mgg-webservice-production.up.railway.app/execute_insert_model3.php
# 期待される結果: "✅ 成功！機種No.3が登録されました。"
```

### ステップ3: 機種No.3をDBに登録

2つの方法どちらでも可：

**方法A: Web経由（推奨）**
```bash
curl https://mgg-webservice-production.up.railway.app/execute_insert_model3.php
```

**方法B: SQL直接実行**
```bash
cd /Users/kotarokashiwai/net8_rebirth/net8
mysql -h 136.116.70.86 -P 3306 -u net8_dev -p net8_dev < insert_milliongod_model3.sql
# パスワード: Net8@Dev#2024!
```

### ステップ4: 画像アップロード

機種No.3の画像を管理画面からアップロード:
1. https://mgg-webservice-production.up.railway.app/xxxadmin/model.php
2. 機種No.3「ミリオンゴッド4号機」を選択
3. 画像をアップロード（ユーザーから提供された画像）

### ステップ5: MACHINE-03で動作確認

1. Windows PC (MACHINE-03) で `slotserver.exe` が起動しているか確認
2. PeerJS接続（PEER002）が確立しているか確認
3. 会員アカウントでログイン
4. 機種「ミリオンゴッド4号機」を選択して遊べるか確認

---

## 📋 重要情報まとめ

### プロジェクト構造
```
/Users/kotarokashiwai/                     ← Gitルート
└── net8_rebirth/                           ← プロジェクトルート
    └── net8/
        ├── insert_milliongod_model3.sql   ← SQL登録スクリプト
        └── 02.ソースファイル/
            └── net8_html/                  ← PHPアプリケーションルート（Railwayのルートに設定必要）
                ├── execute_insert_model3.php  ← 機種登録スクリプト
                ├── railway_test.php           ← テストファイル
                └── data/xxxadmin/             ← 管理画面
                    ├── model.php
                    ├── maker.php
                    ├── member.php
                    ├── owner.php
                    └── pointconvert.php
```

### Railway設定
- **プロジェクト**: mmg2501
- **環境**: production
- **サービス名**: mgg-webservice-production
- **URL**: https://mgg-webservice-production.up.railway.app
- **リポジトリ**: mgg00123mg-prog/mgg001
- **ブランチ**: main
- **Root Directory（要変更）**: `net8_rebirth` → `net8_rebirth/net8/02.ソースファイル/net8_html`

### データベース接続情報
- **ホスト**: 136.116.70.86
- **ポート**: 3306
- **データベース**: net8_dev
- **ユーザー**: net8_dev
- **パスワード**: Net8@Dev#2024!

### 機種No.3 登録情報
```sql
model_no: 3
category: 2 (スロット)
model_cd: MILLIONGOD01
model_name: ミリオンゴッド4号機
model_roman: MILLIONGOD
type_no: 5 (タイプA)
unit_no: 4 (4号機)
maker_no: 1 (ユニバーサルエンターテイメント)
renchan_games: 0
tenjo_games: 9999
layout_data: {"video_portrait":0,"video_mode":4,"drum":0,"bonus_push":[{"label":"select","path":"noselect_bonus.png"}],"version":1,"hide":["changePanel"]}
```

---

## 🔧 よく使うコマンド

### Gitコマンド
```bash
# 作業ディレクトリに移動
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html

# 最新コミット確認
git log --oneline -5

# プッシュ
git add .
git commit -m "メッセージ"
git push origin main

# Railway強制デプロイ
git commit --allow-empty -m "chore: Trigger Railway redeploy"
git push origin main
```

### Railway CLI
```bash
# Railway状態確認
railway status

# ログ確認
railway logs
```

### データベース接続
```bash
mysql -h 136.116.70.86 -P 3306 -u net8_dev -p net8_dev
# パスワード: Net8@Dev#2024!
```

---

## 🐛 トラブルシューティング

### 問題: Railway で 404 Not Found
**原因**: Root Directory設定が間違っている
**解決**: 上記「ステップ1: Railway Root Directory設定変更」を実行

### 問題: SQLSTATE[HY093] エラー
**原因**: 空文字列でSQL構築している
**状態**: すでに修正済み（コミット ddab88a）
**確認**: 管理画面で新規登録→全項目入力→登録ボタン→成功すればOK

### 問題: Machine #3 が slotserver.exe で接続できない
**確認事項**:
1. `slotserver.exe` が起動中か
2. `slotserver.ini` の設定が正しいか
3. PeerJS接続（PEER002）が確立しているか
4. Railway の Signaling Server が動作しているか

---

## 📝 次回セッションでの開始方法

1. このファイルを読む: `/Users/kotarokashiwai/net8_rebirth/net8/CURRENT_STATUS_2025_11_06.md`
2. Railway設定が修正されているか確認: `curl https://mgg-webservice-production.up.railway.app/railway_test.php`
3. 未完了のステップから続行

---

## 💡 重要な教訓

1. **Gitルートの確認が最重要**: Railway Root Directory設定はGitルートからの相対パス
2. **空コミットは無意味**: ファイルを追加せずにコミットしてもRailwayには反映されない
3. **デプロイ前にローカルテスト**: `php -l` だけでなく実際にWebサーバーで動作確認
4. **SqlStringクラスの罠**: 空文字列パラメータを渡すとPDOエラーが発生する

---

**作成日**: 2025/11/06
**最終更新**: 2025/11/06 04:30 JST
**次回担当者へ**: Railway Root Directory設定を変更すれば、すぐに機種登録できます！
