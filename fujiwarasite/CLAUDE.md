
# fujiwarasite — WordPress管理プロジェクト

## WordPress操作コマンド

WordPressを操作するときは `wp/client.ts` を使う。
事前に `.env` ファイルを作成してサイト情報を設定すること（`.env.example` 参照）。

### よく使うコマンド

```bash
# 投稿一覧
bun wp/client.ts posts list

# 投稿作成（JSONをstdinに渡す）
echo '{"title":"タイトル","content":"<p>本文</p>","status":"draft"}' | bun wp/client.ts posts create

# 投稿を公開する
echo '{"status":"publish"}' | bun wp/client.ts posts update <ID>

# 投稿を下書きに戻す
echo '{"status":"draft"}' | bun wp/client.ts posts update <ID>

# ページ一覧
bun wp/client.ts pages list

# ページ作成
echo '{"title":"ページ名","content":"<p>内容</p>","status":"publish"}' | bun wp/client.ts pages create

# 画像アップロード（単体）
bun wp/client.ts media upload ./path/to/image.jpg

# 画像一括アップロード（ディレクトリ指定）
bun wp/bulk-upload-images.ts ./images/

# メディア一覧
bun wp/client.ts media list

# カテゴリ一覧
bun wp/client.ts categories list

# カテゴリ作成
echo '{"name":"カテゴリ名","slug":"category-slug"}' | bun wp/client.ts categories create

# タグ一覧
bun wp/client.ts tags list

# プラグイン一覧
bun wp/client.ts plugins list

# プラグイン有効化
bun wp/client.ts plugins activate plugin-folder/plugin-file.php

# プラグイン無効化
bun wp/client.ts plugins deactivate plugin-folder/plugin-file.php

# テーマ一覧
bun wp/client.ts themes list

# サイト設定確認
bun wp/client.ts settings get

# キーワード検索
bun wp/client.ts search キーワード
```

### 投稿作成の完全なJSON例

```json
{
  "title": "投稿タイトル",
  "content": "<p>本文HTML</p><h2>見出し</h2><p>段落</p>",
  "excerpt": "抜粋テキスト",
  "status": "publish",
  "categories": [1, 2],
  "tags": [3, 4],
  "featured_media": 123
}
```

### セットアップ手順（初回のみ）

1. WordPress管理画面 → ユーザー → プロフィール → 最下部「アプリケーションパスワード」
2. アプリ名を入力（例: "Claude Code"）→「新しいアプリケーションパスワードを追加」
3. 表示されたパスワードをコピー
4. `.env.example` を `.env` にコピーして値を設定:
   ```
   WP_URL=https://your-site.com
   WP_USER=ユーザー名
   WP_APP_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx
   ```

## Skill routing

When the user's request matches an available skill, invoke it via the Skill tool. When in doubt, invoke the skill.

Key routing rules:
- Product ideas/brainstorming → invoke /office-hours
- Strategy/scope → invoke /plan-ceo-review
- Architecture → invoke /plan-eng-review
- Design system/plan review → invoke /design-consultation or /plan-design-review
- Full review pipeline → invoke /autoplan
- Bugs/errors → invoke /investigate
- QA/testing site behavior → invoke /qa or /qa-only
- Code review/diff check → invoke /review
- Visual polish → invoke /design-review
- Ship/deploy/PR → invoke /ship or /land-and-deploy
- Save progress → invoke /context-save
- Resume context → invoke /context-restore
