# NET8 iframe クイックデプロイガイド

## 🚀 5分でデプロイ

### ステップ1: ブランチ確認
```bash
cd /Users/kotarokashiwai/net8_rebirth/ch_ifream
git checkout feature/ifream
git pull origin feature/ifream
```

### ステップ2: 修正作業
ファイルを編集...

### ステップ3: キャッシュバスター更新（PHPファイル変更時のみ）
```bash
# Dockerfile 4行目を編集
RUN echo "FORCE-REBUILD-2026-01-27-ch-ifream-vXX-description" > /tmp/cache-bust
```

### ステップ4: コミット＆プッシュ
```bash
git add .
git commit -m "fix: 修正内容"
git push origin feature/ifream
```

### ステップ5: 確認（2-3分待機）
```
https://ifreamnet8-development.up.railway.app/xxxadmin/test_china_embed.html
```

---

## ⚠️ 絶対に守るべきルール

### ❌ やってはいけないこと
```php
// ❌ /data/ プレフィックスを使う
$path = '/data/img/model/hokuto.jpg';
$path = '/data/play_embed/';
$path = '/data/play_v2/js/view_functions.js';

// ❌ Dockerfileのキャッシュバスターを更新しない（PHPファイル変更時）
```

### ✅ 正しい書き方
```php
// ✅ /data/ プレフィックスなし
$path = '/img/model/hokuto.jpg';
$path = '/play_embed/';
$path = '/play_v2/js/view_functions.js';

// ✅ キャッシュバスター更新（PHPファイル変更時）
```

---

## 🔧 よくある修正パターン

### パターン1: 画像パス修正
```php
// Before
define('DIR_IMG_MODEL', '/data/img/model/');

// After
define('DIR_IMG_MODEL', '/img/model/');
```

### パターン2: play_embed パス修正
```php
// Before
$gameUrl = "/data/play_embed/?sessionId={$sessionId}";

// After
$gameUrl = "/play_embed/?sessionId={$sessionId}";
```

### パターン3: JS/CSS パス修正
```html
<!-- Before -->
<script src="/data/play_v2/js/view_functions.js"></script>

<!-- After -->
<script src="/play_v2/js/view_functions.js"></script>
```

---

## 🐛 トラブルシューティング 1分チェック

### 画像が404
→ `/data/img/` を `/img/` に修正

### JS/CSSが読めない
→ `/data/play_v2/` を `/play_v2/` に修正

### play_embedが404
→ `game_start.php` のURL生成を確認

### WebRTC映像なし
→ cameraIdパラメータを確認

### API 400エラー
→ テストページでAPIキー自動取得

### PHP変更が反映されない
→ Dockerfileのキャッシュバスター更新

---

## 📝 チェックリスト

デプロイ前：
- [ ] `/data/` プレフィックス使用なし
- [ ] PHPファイル変更時はキャッシュバスター更新
- [ ] feature/ifreameブランチで作業
- [ ] コミットメッセージ明確

デプロイ後：
- [ ] 2-3分待機
- [ ] テストページで確認
- [ ] ブラウザ開発者ツールでエラーなし確認

---

## 🔗 主要URL

- **本番**: https://ifreamnet8-development.up.railway.app/
- **テスト**: https://ifreamnet8-development.up.railway.app/xxxadmin/test_china_embed.html
- **APIキー取得**: https://ifreamnet8-development.up.railway.app/check_api_keys.php

---

## 📞 困ったら

1. `DEPLOYMENT_MANUAL.md` の詳細版を確認
2. Railway ログを確認
3. ブラウザ開発者ツール（F12）でコンソールエラー確認

---

**最終更新**: 2026-01-27
