# Railway Root Directory 設定修正ガイド

## 問題の説明

**症状**: Railway で PHP ファイルにアクセスすると 404 Not Found エラーが発生

**原因**: Railway の Root Directory 設定が間違っている

---

## 現在の設定（間違い）

```
Root Directory: net8_rebirth
```

この設定では、Railway は以下のディレクトリをWebルートとして認識:
```
/workspace/net8_rebirth/
```

しかし、実際のPHPファイルは以下にある:
```
/workspace/net8_rebirth/net8/02.ソースファイル/net8_html/
```

→ **4階層分ずれている！**

---

## 正しい設定

```
Root Directory: net8_rebirth/net8/02.ソースファイル/net8_html
```

---

## 設定変更手順（ステップバイステップ）

### ステップ1: Railway ダッシュボードにアクセス

1. ブラウザで https://railway.app/dashboard を開く
2. GitHubアカウントでログイン

### ステップ2: プロジェクトを選択

1. プロジェクト一覧から **「mmg2501」** をクリック
2. 環境が **「production」** になっていることを確認

### ステップ3: サービスを選択

1. サービス一覧から **「mgg-webservice-production」** をクリック
   - URL: `mgg-webservice-production.up.railway.app`

### ステップ4: Settings タブを開く

1. 上部のタブから **「Settings」** をクリック

### ステップ5: Source セクションを見つける

1. ページをスクロールして **「Source」** セクションを探す
2. 以下の情報が表示されているはず:
   ```
   Repository: mgg00123mg-prog/mgg001
   Branch: main
   Root Directory: net8_rebirth  ← これを変更する
   ```

### ステップ6: Root Directory を変更

1. **「Root Directory」** の右側にある **「Edit」** または **鉛筆アイコン** をクリック
2. 現在の値 `net8_rebirth` を削除
3. 以下の値を入力:
   ```
   net8_rebirth/net8/02.ソースファイル/net8_html
   ```
   **注意**: スペースを含めない！コピペ推奨！
4. **「Save」** または **チェックマーク** をクリック

### ステップ7: デプロイをトリガー

Root Directory の変更だけでは自動的にデプロイされない場合があります。

**方法A: Redeploy ボタンを使用（推奨）**
1. Settings ページの一番下にスクロール
2. **「Redeploy」** ボタンをクリック
3. 確認ダイアログで **「Redeploy」** をクリック

**方法B: 空コミットをプッシュ**
```bash
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
git commit --allow-empty -m "chore: Trigger redeploy after Root Directory fix"
git push origin main
```

### ステップ8: デプロイを監視

1. サービス画面の **「Deployments」** タブをクリック
2. 最新のデプロイが **「Building」** または **「Running」** になっていることを確認
3. ステータスが **「Success」** になるまで待つ（通常1〜3分）

### ステップ9: 動作確認

デプロイ完了後、以下のコマンドで確認:

```bash
# テストファイルで確認
curl https://mgg-webservice-production.up.railway.app/railway_test.php

# 期待される出力:
# Railway Test OK - Commit: b9f9588

# 404エラーが出る場合は、Root Directory設定が反映されていない
```

```bash
# 本番ファイルで確認
curl https://mgg-webservice-production.up.railway.app/execute_insert_model3.php

# 期待される出力:
# ✅ 成功！機種No.3が登録されました。
```

---

## トラブルシューティング

### 問題1: Root Directory の入力フィールドが見つからない

**解決策**:
1. ブラウザをリフレッシュ (Cmd+R / Ctrl+R)
2. Railway からログアウトして再ログイン
3. 別のブラウザで試す

### 問題2: 設定を変更しても 404 エラーが出続ける

**考えられる原因**:
1. デプロイがまだ完了していない
   → 「Deployments」タブで確認
2. Root Directory の値が間違っている
   → Settings で再確認（スペースや余分な文字がないか）
3. Apacheの DocumentRoot 設定が間違っている
   → `.htaccess` や `railway.toml` を確認

**確認コマンド**:
```bash
# Deployments タブで最新のコミットハッシュを確認
# 期待値: b9f9588 以降

# ローカルの最新コミットと比較
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
git log --oneline -1
```

### 問題3: Redeploy ボタンが見つからない

**代替方法**:
```bash
# 空コミットでデプロイをトリガー
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
git commit --allow-empty -m "chore: Force redeploy"
git push origin main
```

### 問題4: 「Permission denied」エラーが出る

**解決策**:
- Railway WebUIから操作する権限がない可能性
- プロジェクトのオーナーまたは管理者に連絡

---

## 設定後の確認チェックリスト

- [ ] Root Directory が `net8_rebirth/net8/02.ソースファイル/net8_html` に設定されている
- [ ] Deployments タブで最新のデプロイが「Success」になっている
- [ ] `curl https://mgg-webservice-production.up.railway.app/railway_test.php` が成功する
- [ ] `curl https://mgg-webservice-production.up.railway.app/execute_insert_model3.php` が成功する
- [ ] 管理画面 `https://mgg-webservice-production.up.railway.app/xxxadmin/` にアクセスできる

---

## 参考: Git リポジトリ構造

```
/Users/kotarokashiwai/                           ← Gitルート
└── net8_rebirth/                                 ← Railwayはここを起点として見る
    └── net8/
        └── 02.ソースファイル/
            └── net8_html/                        ← これがWebルート（DocumentRoot）
                ├── index.php
                ├── execute_insert_model3.php
                ├── railway_test.php
                ├── data/
                │   └── xxxadmin/
                │       ├── model.php
                │       ├── maker.php
                │       └── ...
                ├── _etc/
                │   └── setting_base.php
                └── _lib/
                    └── smartDB.php
```

**Railway の Root Directory 設定**:
```
net8_rebirth/net8/02.ソースファイル/net8_html
└─ これがGitルートからの相対パス
```

---

## Railway 設定の全体像

### 必須設定項目

| 項目 | 値 |
|------|-----|
| Repository | mgg00123mg-prog/mgg001 |
| Branch | main |
| Root Directory | `net8_rebirth/net8/02.ソースファイル/net8_html` |
| Build Command | (自動検出) |
| Start Command | (自動検出: Apache) |

### 環境変数（すでに設定済み）

| 変数名 | 値 |
|--------|-----|
| DB_TYPE | mysql |
| DB_SERVER | 136.116.70.86 |
| DB_PORT | 3306 |
| DB_USER | net8_dev |
| DB_PASSWORD | Net8@Dev#2024! |
| DB_NAME | net8_dev |
| DB_CHARSET | utf8mb4 |

---

## よくある質問（FAQ）

### Q1: Root Directory を変更すると既存のデータは消える？
**A**: いいえ、消えません。Root Directory はどのディレクトリをWebルートとして公開するかを指定するだけで、データベースやファイルには影響しません。

### Q2: 変更後、すぐに反映される？
**A**: Redeploy が必要です。設定変更後、必ず「Redeploy」ボタンをクリックするか、空コミットをプッシュしてください。

### Q3: 間違った値を設定してしまった場合は？
**A**: 再度 Settings から Root Directory を正しい値に変更して、Redeploy すればOKです。

### Q4: ローカルでも Root Directory の設定が必要？
**A**: いいえ、ローカルではPHPサーバーを直接起動するディレクトリが Root になります。
```bash
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
php -S localhost:8888  # ← このディレクトリがWebルートになる
```

---

**作成日**: 2025/11/06
**最終更新**: 2025/11/06 04:35 JST
**次回担当者へ**: この手順通りに実行すれば、確実に動作します！
