# クイックリジューム情報

最終更新: 2025-12-17

## 現在の状態

✅ play_embed 2列コンパクトレイアウト完了
✅ 機種状況API（/api/v1/models, /api/v1/machines）作成完了
✅ 韓国側ポイント統合完了
✅ デプロイ完了（Railway）

## すぐに確認できるURL

```bash
# 本番環境
https://mgg-webservice-production.up.railway.app

# play_embed テスト
https://mgg-webservice-production.up.railway.app/data/play_embed/?NO=1&sessionId=test&cameraId=test&points=10000&credit=0

# モデル一覧API
https://mgg-webservice-production.up.railway.app/api/v1/models

# 台一覧API
https://mgg-webservice-production.up.railway.app/api/v1/machines

# korea_net8front
http://localhost:3000/ja/pachinko
```

## 主要ファイル（編集が必要な場合）

| 目的 | ファイルパス |
|------|------------|
| play_embed HTML/PHP | `net8/02.ソースファイル/net8_html/data/play_embed/index.php` |
| play_embed CSS | `net8/02.ソースファイル/net8_html/data/play_embed/css/embed.css` |
| 台一覧API | `net8/02.ソースファイル/net8_html/api/v1/machines.php` |
| モデル一覧API | `net8/02.ソースファイル/net8_html/api/v1/models.php` |
| WebRTC/ゲームロジック | `net8/02.ソースファイル/net8_html/data/play_v2/js/view_auth.js` |
| korea iframe コンポーネント | `korea_net8front/client-pachinko/src/components/net8/Net8GamePlayerIframe.tsx` |

## デプロイコマンド

```bash
cd /Users/kotarokashiwai/net8_rebirth

# ファイル追加（-f でgitignore無視）
git add -f "net8/02.ソースファイル/net8_html/data/play_embed/index.php"
git add -f "net8/02.ソースファイル/net8_html/data/play_embed/css/embed.css"

# コミット
git commit -m "変更内容"

# プッシュ（自動デプロイ）
git push origin main

# 2-3分待ってから確認
```

## 直近のコミット

```
55f411e - 2列コンパクトレイアウト
1544cec - 機種状況・画像API追加
031a167 - 韓国側ポイント統合
271846c - クレジット変換モーダル追加
```

## 次にやるべきこと

1. **本番テスト**: play_embedのボタンが実際に動作するか確認
2. **korea_net8front統合**: 機種選択ページで /api/v1/models を使用
3. **画像確認**: モデル画像が正しく表示されるか

## トラブルシューティング

### ボタンが動かない場合
- `dataConnection` が確立されているか確認
- ブラウザコンソールでエラーチェック
- WebRTC接続状態を確認

### コントロールが表示されない場合
- `embed.css` の `display: none !important` を確認
- `.compact-2row` クラスが適用されているか確認

### ポイントが0になる場合
- URLパラメータ `points` が渡されているか確認
- `game` オブジェクトが view_auth.js より先に定義されているか確認

## 詳細ドキュメント

詳細な実装情報は以下を参照:
- `.claude/memory/play_embed_implementation.md`
