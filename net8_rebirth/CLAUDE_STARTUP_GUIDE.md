# 🚀 Claude Code 起動時必読ガイド

---

## 🔴 【最重要】新しいClaude Codeセッション開始時の手順

### 1️⃣ 最初に必ず読むファイル（順番通りに）

```bash
# ① プロジェクト基本ルールを読む
cat /Users/kotarokashiwai/net8_rebirth/CLAUDE.md

# ② デプロイ構造を理解する
cat /Users/kotarokashiwai/net8_rebirth/DEPLOY.md

# ③ Railway デプロイ手順を確認する
cat /Users/kotarokashiwai/net8_rebirth/RAILWAY_DEPLOY_INSTRUCTIONS.md

# ④ このスタートアップガイドを読む
cat /Users/kotarokashiwai/net8_rebirth/CLAUDE_STARTUP_GUIDE.md
```

### 2️⃣ 環境状態の確認

```bash
# 現在のディレクトリ確認
pwd

# Gitステータス確認
cd /Users/kotarokashiwai && git status

# 重要ファイルの存在確認
ls -la /Users/kotarokashiwai/railway.toml
ls -la /Users/kotarokashiwai/net8_rebirth/Dockerfile
ls -la /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/
```

---

## 📁 ローカルファイル構造と役割

### 🔑 必読ドキュメント（優先順位順）

| ファイルパス | 役割 | いつ読むか |
|------------|------|-----------|
| `/Users/kotarokashiwai/net8_rebirth/CLAUDE.md` | プロジェクト開発ルール | **セッション開始時必須** |
| `/Users/kotarokashiwai/net8_rebirth/DEPLOY.md` | デプロイ構造の詳細 | **デプロイ作業前必須** |
| `/Users/kotarokashiwai/net8_rebirth/RAILWAY_DEPLOY_INSTRUCTIONS.md` | デプロイ手順書 | **デプロイ実行時必須** |
| `/Users/kotarokashiwai/net8_rebirth/CLAUDE_STARTUP_GUIDE.md` | このファイル | **セッション開始時必須** |

### 📝 作業記録・ログファイル

| ファイルパス | 内容 |
|------------|------|
| `/Users/kotarokashiwai/net8_rebirth/IMPLEMENTATION_RECORD_*.md` | 実装記録 |
| `/Users/kotarokashiwai/net8_rebirth/LOCAL_TEST_RESULTS.md` | ローカルテスト結果 |
| `/Users/kotarokashiwai/net8_rebirth/NET8_SDK_IMPLEMENTATION_GUIDE*.md` | SDK実装ガイド |

### ⚙️ 設定ファイル

| ファイルパス | 役割 | 編集時の注意 |
|------------|------|------------|
| `/Users/kotarokashiwai/railway.toml` | Railway設定 | **rootDirectory = "net8_rebirth"必須** |
| `/Users/kotarokashiwai/net8_rebirth/Dockerfile` | Dockerビルド設定 | **パスはnet8/から始める** |
| `/Users/kotarokashiwai/net8_rebirth/composer.json` | PHP依存関係 | Google Cloud Storage SDK設定 |

### 💻 ソースコード本体

```
/Users/kotarokashiwai/net8_rebirth/net8/
├── 02.ソースファイル/
│   └── net8_html/          ← PHPアプリケーション本体
│       ├── data/api/       ← APIエンドポイント
│       ├── _html/          ← HTMLテンプレート
│       └── _etc/           ← 設定ファイル
├── source/
│   └── net8_html/          ← 02.ソースファイルのコピー（日本語パス回避用）
└── docker/
    └── web/
        ├── php.ini
        └── apache-config/
```

---

## 🎯 作業開始前のチェックリスト

```markdown
## Claude Code セッション開始チェックリスト

- [ ] CLAUDE.md を読んだ
- [ ] DEPLOY.md を読んだ
- [ ] RAILWAY_DEPLOY_INSTRUCTIONS.md を読んだ
- [ ] Git status を確認した
- [ ] railway.toml の設定を確認した
- [ ] Dockerfile のパス設定を確認した
- [ ] 現在のブランチが main であることを確認した
```

---

## 🔧 よく使うコマンド集

### Git操作

```bash
# 状態確認
cd /Users/kotarokashiwai && git status

# ファイル追加とコミット（例：API修正）
git add "net8_rebirth/net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php"
git commit -m "fix: userAuthAPI修正"
git push origin main

# 最近のコミット確認
git log --oneline -5
```

### ファイル編集

```bash
# API編集
vi /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php

# Dockerfile編集
vi /Users/kotarokashiwai/net8_rebirth/Dockerfile

# railway.toml編集
vi /Users/kotarokashiwai/railway.toml
```

### デプロイ確認

```bash
# 本番環境の動作確認
curl https://mgg-webservice-production.up.railway.app/

# 最新のプッシュ確認
git log --oneline -1 --pretty=format:'%h %s (%cr)'
```

---

## ⚠️ 絶対に守るルール

### ❌ やってはいけないこと

1. **DEPLOY.md を編集しない**（読み取り専用）
2. **railway.toml から rootDirectory を削除しない**
3. **Dockerfile のパスに net8_rebirth/ を含めない**
4. **Git root を変更しない**（/Users/kotarokashiwai のまま）

### ✅ 必ずやること

1. **セッション開始時に必読ファイルを読む**
2. **デプロイ前に RAILWAY_DEPLOY_INSTRUCTIONS.md を読む**
3. **変更前に git status で状態確認**
4. **コミットメッセージは日本語で明確に書く**

---

## 🆘 困ったときの対処法

### Q: デプロイでエラーが出る
A: `/Users/kotarokashiwai/net8_rebirth/RAILWAY_DEPLOY_INSTRUCTIONS.md` の「よくある間違い」セクションを確認

### Q: どのファイルを編集すればいいかわからない
A: `/Users/kotarokashiwai/net8_rebirth/DEPLOY.md` でファイル構造を確認

### Q: Gitの状態がおかしい
A: 以下のコマンドで確認
```bash
cd /Users/kotarokashiwai
git status
git log --oneline -5
git diff
```

### Q: 前回の作業内容がわからない
A: 最近のコミットを確認
```bash
git log --oneline -10 --pretty=format:'%h %s (%cr by %an)'
```

---

## 📊 プロジェクト情報

### GitHub リポジトリ
- URL: `https://github.com/mgg00123mg-prog/mgg001.git`
- ブランチ: `main`

### Railway プロジェクト
- プロジェクト名: `mmg2501`
- サービス: `mgg-webservice`
- URL: `https://mgg-webservice-production.up.railway.app/`

### データベース（GCP Cloud SQL）
- ホスト: `136.116.70.86`
- DB名: `net8_dev`
- ユーザー: `net8tech001`

---

## 🎯 このガイドの使い方

1. **新しいClaude Codeセッション開始時**:
   - このファイルを最初に読む
   - チェックリストを実行する

2. **作業開始前**:
   - 関連ドキュメントを読む
   - 環境状態を確認する

3. **デプロイ作業時**:
   - RAILWAY_DEPLOY_INSTRUCTIONS.md を参照
   - チェックリストを使って確認

4. **エラー発生時**:
   - このガイドの「困ったとき」セクションを確認
   - 関連ドキュメントを再読

---

## 🔄 更新履歴

- 2025-12-12: 初版作成（デプロイ成功後の完全版）
- rootDirectory設定の重要性を強調
- よく使うコマンド集を追加

---

**このファイルは全Claude Code共通の起動ガイドです。**  
**必ずセッション開始時に読んでください。**

成功を祈っています！ 🚀