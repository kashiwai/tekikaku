# Signaling Branch - PeerJSシグナリングサーバー専用ブランチ

このブランチは**PeerJSシグナリングサーバー専用**です。

## 目的

Railway Git連携デプロイで、Dockerfileビルダーを確実に使用するために作成されました。

## ブランチ構成

```
main (PHPアプリ + 全ファイル)
  - Net8パチンコゲームアプリケーション
  - データベース設定
  - 全プロジェクトファイル

signaling (PeerJSシグナリングサーバー専用)
  - PeerJSサーバーのみ
  - 最小構成
  - Dockerfile強制ビルド
```

## このブランチの特徴

### 1. Docker強制ビルド
`railway.json` により、Dockerfileビルダーが強制されます：
```json
{
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "net8/Dockerfile.signaling"
  }
}
```

### 2. 監視パターン
以下のファイルが変更されると自動デプロイ：
- `net8/Dockerfile.signaling`
- `net8/01.サーバ構築手順/net8peerjs-server/**`

### 3. ヘルスチェック設定
- パス: `/`
- タイムアウト: 300秒
- リトライ: 最大10回

## Railway設定

### プロジェクト情報
- **プロジェクト名**: mmg2501
- **プロジェクトID**: 8d81850a-8a75-4707-8439-4a87062f4927
- **サービスID**: cd86cf5f-9de1-4409-b0ee-9cae2ddf04da
- **環境**: production (987220ce-9a8f-4220-a224-c937537486f9)
- **URL**: https://dockerfilesignaling-production.up.railway.app/

### Railway設定変更手順

1. **Railwayダッシュボードを開く**
   https://railway.com/project/8d81850a-8a75-4707-8439-4a87062f4927/service/cd86cf5f-9de1-4409-b0ee-9cae2ddf04da

2. **Settings タブを開く**

3. **Source セクション**
   - "Branch" を **`signaling`** に変更
   - "Root Directory" は空白のまま

4. **保存して再デプロイ**
   - "Deploy" ボタンをクリック

## デプロイフロー

```
GitHub (signaling branch)
  ↓
Railway Git連携
  ↓
railway.json 読み込み
  ↓
Dockerfile指定 (net8/Dockerfile.signaling)
  ↓
Dockerビルド実行
  ↓
PeerJSサーバー起動
  ↓
https://dockerfilesignaling-production.up.railway.app/
```

## 使用されるファイル

### デプロイに必要なファイル
- `railway.json` - Railway設定
- `net8/Dockerfile.signaling` - Dockerビルド設定
- `net8/01.サーバ構築手順/net8peerjs-server/` - PeerJSサーバーソース
  - `package.json` - Node.js依存関係
  - `app.json` - サーバー情報
  - `bin/peerjs` - 起動スクリプト
  - `lib/server.js` - サーバーロジック

### 削除済みファイル（このブランチのみ）
- `railway.toml` - 混乱防止のため削除
- `railway-signaling/` - 未使用ディレクトリを削除

## 開発ワークフロー

### 修正をこのブランチに反映する

```bash
# signalingブランチに切り替え
git checkout signaling

# 修正を実施
vim net8/Dockerfile.signaling

# コミット
git add .
git commit -m "fix: PeerJS server configuration"

# プッシュ（自動デプロイ）
git push origin signaling
```

### mainブランチからの変更を取り込む

```bash
# signalingブランチで作業中
git checkout signaling

# mainブランチから特定のファイルをマージ
git checkout main -- net8/01.サーバ構築手順/net8peerjs-server/
git checkout main -- net8/Dockerfile.signaling

# コミットしてプッシュ
git add .
git commit -m "merge: Update PeerJS server from main"
git push origin signaling
```

## トラブルシューティング

### デプロイが失敗する場合

1. **ビルドログを確認**
   - Railwayダッシュボード → Deployments → 最新デプロイ → Logs

2. **Dockerfileのデバッグ出力を確認**
   ```
   === Checking app.json exists ===
   -rw-r--r-- 1 root root 153 ... app.json
   === Checking server.js line 221 ===
   ```
   この出力が見えない場合、Dockerfileが正しく使用されていません。

3. **Railway設定を再確認**
   - Source → Branch が `signaling` になっているか
   - Settings → Build → Builder が "Dockerfile" になっているか

### Nixpacksが使用される場合

Railway設定で以下を確認：
- `railway.json` が存在するか
- `"builder": "DOCKERFILE"` が指定されているか
- ブランチが `signaling` に設定されているか

## 注意事項

- このブランチは**シグナリングサーバー専用**です
- PHPアプリの変更は `main` ブランチで行ってください
- `signaling` ブランチには最小限のファイルのみ含めてください

## 技術スタック

- **Platform**: Railway
- **Runtime**: Node.js 14 (Alpine)
- **Server**: PeerJS Server v0.2.9 (WebRTC Signaling)
- **Build**: Docker
- **Port**: 8080（Railway環境変数PORTから自動取得）
- **Key**: peerjs

## 関連ドキュメント

- `.claude/workspace/railway_signaling_fix_final_20251029.md` - 修正履歴
- `net8/CLAUDE.md` - プロジェクト開発ルール
- `CLAUDE.md` - AI運用5原則

## 作成日

2025-10-29

## AI運用5原則適用

このブランチの作成・管理もAI運用5原則に従っています：
1. 事前確認必須
2. 迂回禁止
3. ユーザー最優先
4. ルール厳守
5. 毎回表示
