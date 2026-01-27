# NET8 iframe (ch_ifream) デプロイマニュアル

## 📋 プロジェクト概要

**プロジェクト名**: ch_ifream
**デプロイ環境**: Railway (ifreamnet8-development.up.railway.app)
**用途**: 中国・海外市場向けiframe埋め込み型パチンコ/スロットゲーム配信
**ブランチ**: `feature/ifream`

---

## 🏗️ プロジェクト構造

```
ch_ifream/
├── Dockerfile                    # Railwayデプロイ用（重要！）
├── docker-entrypoint.sh          # Apache起動スクリプト
├── apache-mpm-fix.conf           # MPM設定
├── config/
│   ├── 000-default.conf          # Apache VirtualHost設定
│   └── php.ini                   # PHP設定
└── ch/                           # アプリケーションルート
    ├── api/v1/
    │   └── game_start.php        # ゲーム開始API
    ├── data/                     # DocumentRoot（重要！）
    │   ├── index.php             # トップページ
    │   ├── play_embed/           # iframe埋め込みプレイヤー
    │   │   ├── index.php
    │   │   ├── js/
    │   │   └── css/
    │   ├── play_v2/              # 通常プレイヤー
    │   │   └── js/
    │   ├── xxxadmin/             # 管理画面・テストツール
    │   │   ├── test_china_embed.html
    │   │   └── check_api_keys.php
    │   └── img/model/            # 機種画像
    ├── _api/                     # シグナリングAPI
    ├── css/                      # 共通CSS
    └── js/                       # 共通JS
```

---

## 🚀 デプロイフロー

### 1. ローカル開発＆テスト

```bash
cd /Users/kotarokashiwai/net8_rebirth/ch_ifream
git checkout feature/ifream
```

### 2. Docker キャッシュバスター更新

PHPファイルを変更した場合、OPcacheをクリアするためDockerfileのキャッシュバスターを更新：

```dockerfile
# Dockerfile 4行目を更新
RUN echo "FORCE-REBUILD-2026-01-27-ch-ifream-vXX-description" > /tmp/cache-bust
```

**バージョン番号ルール**:
- v1-v9: 初期開発
- v10: play_embed パス修正
- v11: WebRTC cameraId追加
- v12: JS/CSS パス＆Apache設定修正
- v13: 中国テストページ追加
- v14: APIキー自動取得機能（最新）

### 3. Git コミット＆プッシュ

```bash
git add .
git commit -m "fix: 修正内容の説明

- 変更内容1
- 変更内容2

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
git push origin feature/ifream
```

### 4. Railway 自動デプロイ

- プッシュ後、Railwayが自動的にビルド＆デプロイを開始
- 所要時間: 約2-3分
- Railway Dashboard: https://railway.app/

### 5. デプロイ確認

```bash
# ヘルスチェック
curl -I https://ifreamnet8-development.up.railway.app/

# APIエンドポイント確認
curl https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php
```

---

## ⚙️ 重要な設定

### DocumentRoot設定

**Apache DocumentRoot**: `/var/www/html/ch/data`

**重要**: すべてのURLパスは `/data/` プレフィックスなしで記述する必要があります。

#### ❌ 間違った例
```php
$imageUrl = '/data/img/model/hokuto.jpg';  // 404エラー
$jsUrl = '/data/play_v2/js/view_functions.js';  // 404エラー
$gameUrl = '/data/play_embed/?sessionId=xxx';  // 404エラー
```

#### ✅ 正しい例
```php
$imageUrl = '/img/model/hokuto.jpg';
$jsUrl = '/play_v2/js/view_functions.js';
$gameUrl = '/play_embed/?sessionId=xxx';
```

**理由**: DocumentRootが既に `/data` を指しているため、`/data/` を付けると `/data/data/` を探してしまう。

---

### Apache RewriteCond除外設定

`config/000-default.conf` 42行目:

```apache
RewriteCond %{REQUEST_URI} !^/(font|css|js|img|vendor|data|content|_api|api|sdk|test_client|play_v2|play_embed)/
```

**追加されたディレクトリ**:
- `play_v2`: 通常プレイヤーJS/CSS
- `play_embed`: iframe埋め込みプレイヤーJS/CSS

これらがないと、ApacheがRewriteルールを適用して404になります。

---

### Alias設定

`config/000-default.conf` 16-21行目:

```apache
Alias /api /var/www/html/ch/api
<Directory /var/www/html/ch/api>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

**重要**: Alias は RewriteEngine の**前**に定義する必要があります。

---

## 🧪 テスト方法

### 1. 中国市場テストページ

```
URL: https://ifreamnet8-development.up.railway.app/xxxadmin/test_china_embed.html
```

**操作手順**:
1. ページを開く（APIキーが自動取得される）
2. 必要に応じて設定を変更（通貨、言語、ポイント）
3. 「🚀 开始游戏」ボタンをクリック
4. ゲームがiframe内に表示される

### 2. API直接テスト

```bash
# APIキー取得
curl https://ifreamnet8-development.up.railway.app/check_api_keys.php

# ゲーム開始（APIキーを取得後に置き換え）
curl -X POST https://ifreamnet8-development.up.railway.app/api/v1/game_start.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer pk_test_xxxxx" \
  -d '{
    "memberNo": "9999",
    "machineNo": 9999,
    "currency": "CNY",
    "lang": "zh",
    "points": 1000
  }'
```

### 3. 画像表示テスト

```
URL: https://ifreamnet8-development.up.railway.app/
```

トップページで機種画像が正しく表示されることを確認。

---

## 🐛 トラブルシューティング

### 問題1: 画像が404エラー

**症状**: 機種画像が表示されない
**原因**: `/data/img/model/` パスを使用している
**解決策**: `/img/model/` に修正

**修正ファイル**:
- `ch/_etc/setting_base.php` (DIR_IMG_MODEL定数)
- `ch/_sys/TemplateUser.php`
- `ch/api/v1/*.php` (全APIファイル)
- `ch/data/index.php`

### 問題2: JS/CSSが読み込めない（MIME type error）

**症状**: `MIME type 'text/html' is not executable`
**原因1**: `/data/play_v2/js/` パスを使用
**原因2**: Apache RewriteCond除外設定にplay_v2/play_embedがない

**解決策**:
1. `play_embed/index.php` のパスを `/play_v2/` に修正
2. `000-default.conf` のRewriteCond除外リストに追加

### 問題3: play_embedが404エラー

**症状**: `/play_embed/` にアクセスすると404
**原因**: `/data/play_embed/` パスを使用している
**解決策**: `game_start.php` 756行目を修正

```php
// 修正前
$playEmbedUrl = "/data/play_embed/?sessionId={$sessionId}...";

// 修正後
$playEmbedUrl = "/play_embed/?sessionId={$sessionId}...";
```

### 問題4: WebRTC映像が表示されない

**症状**: ローディング画面のまま映像が表示されない
**原因**: gameURLにcameraId（peerID）パラメータがない

**解決策**: `game_start.php` 754-764行目でcameraIdをURLに追加

```php
$cameraIdParam = '';
if ($cameraInfo && isset($cameraInfo['peerId'])) {
    $cameraIdParam = "&cameraId=" . urlencode($cameraInfo['peerId']);
}
$playEmbedUrl = "/play_embed/?sessionId={$sessionId}&NO={$machineNo}&lang={$lang}&currency={$currency}{$cameraIdParam}";
```

### 問題5: HTTP 400エラー (Authorization header required)

**症状**: ゲーム開始APIが400エラーを返す
**原因**: 無効なAPIキーを使用

**解決策**:
1. `check_api_keys.php` でAPIキーを取得
2. テストページの自動取得機能を使用
3. または手動でapi_keysテーブルに登録

### 問題6: PHP変更が反映されない

**症状**: コードを修正してデプロイしても変更が反映されない
**原因**: PHP OPcacheがコードをキャッシュしている

**解決策**: Dockerfileのキャッシュバスターを更新

```dockerfile
RUN echo "FORCE-REBUILD-2026-01-27-ch-ifream-v15-new-fix" > /tmp/cache-bust
```

---

## 📝 開発チェックリスト

コード変更時に確認すべき項目：

### パス関連
- [ ] `/data/` プレフィックスを使用していないか
- [ ] 画像パスは `/img/model/` になっているか
- [ ] JS/CSSパスは `/play_v2/` または `/play_embed/` か
- [ ] APIパスは `/api/v1/` になっているか

### Apache設定
- [ ] 新しいディレクトリをRewriteCond除外リストに追加したか
- [ ] Aliasは RewriteEngine の前に定義されているか

### API関連
- [ ] game_start.php の gameURL は正しいパスを返すか
- [ ] cameraId パラメータは含まれているか
- [ ] Authorization ヘッダーは必須か確認

### デプロイ関連
- [ ] Dockerfileのキャッシュバスターを更新したか
- [ ] コミットメッセージは明確か
- [ ] feature/ifreameブランチにプッシュしたか

---

## 🌐 マルチ言語対応

### サポート言語
- **zh**: 简体中文（中国語簡体字）
- **en**: English（英語）
- **ja**: 日本語
- **ko**: 한국어（韓国語）

### 言語ファイル
- `ch/data/play_embed/lang/zh.php`
- `ch/data/play_embed/lang/en.php`
- `ch/data/play_embed/lang/ja.php`
- `ch/data/play_embed/lang/ko.php`

### 通貨サポート
- **CNY**: 人民币（中国）
- **USD**: US Dollar（アメリカ）
- **JPY**: 日本円
- **KRW**: 韓国ウォン

---

## 🔐 セキュリティ

### API認証
- すべてのAPI呼び出しに `Authorization: Bearer {api_key}` ヘッダーが必要
- APIキーは `api_keys` テーブルで管理
- テスト環境: `environment = 'test'`
- 本番環境: `environment = 'production'`

### CORS設定
play_embed/index.phpで設定済み:

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
```

---

## 📊 環境変数

Railwayで設定されている環境変数（推定）:

```env
# データベース
DB_HOST=xxx
DB_NAME=xxx
DB_USER=xxx
DB_PASSWORD=xxx

# Railway
PORT=8080  # Railwayが自動設定
```

---

## 🔗 関連リンク

- **本番URL**: https://ifreamnet8-development.up.railway.app/
- **テストページ**: https://ifreamnet8-development.up.railway.app/xxxadmin/test_china_embed.html
- **GitHubリポジトリ**: https://github.com/mgg00123mg-prog/mgg001.git
- **ブランチ**: `feature/ifream`

---

## 📅 バージョン履歴

| バージョン | 日付 | 変更内容 |
|---------|------|---------|
| v14 | 2026-01-27 | APIキー自動取得機能追加 |
| v13 | 2026-01-27 | 中国テストページ追加 |
| v12 | 2026-01-27 | JS/CSS パス＆Apache設定修正 |
| v11 | 2026-01-27 | WebRTC cameraId追加 |
| v10 | 2026-01-27 | play_embed パス修正 |
| v9 | 2026-01-26 | HTTPS強制対応 |

---

## 👥 開発者向けメモ

### 重要な設計決定

1. **DocumentRoot を /data に設定**
   - すべてのURLパスから `/data/` プレフィックスを削除する必要がある
   - これにより、韓国版と同じURL構造を維持

2. **iframe埋め込み専用プレイヤー**
   - `play_embed/` ディレクトリで管理
   - sessionId認証のみ（通常ログイン不要）
   - 外部サイトからの埋め込みに対応

3. **マルチ通貨・マルチ言語**
   - game_start.php で通貨と言語を受け取る
   - play_embed/index.php で対応する言語ファイルを読み込む

4. **WebRTC接続**
   - cameraId（PeerID）がURL必須パラメータ
   - シグナリングサーバー経由で映像配信

---

## 📞 サポート

問題が解決しない場合：

1. Railway ログを確認
2. ブラウザの開発者ツールでコンソールエラーを確認
3. `ch/data/xxxadmin/test_china_embed.html` でテスト
4. このマニュアルのトラブルシューティングセクションを参照

---

**最終更新**: 2026-01-27
**作成者**: Claude Sonnet 4.5
**プロジェクト**: NET8 China/International Market iframe Embedding
