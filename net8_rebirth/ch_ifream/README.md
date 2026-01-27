# NET8 iframe - 中国・海外市場向けゲーム配信プラットフォーム

[![Railway Deploy](https://img.shields.io/badge/Railway-Deployed-success)](https://ifreamnet8-development.up.railway.app/)
[![PHP](https://img.shields.io/badge/PHP-7.2-blue)](https://www.php.net/)
[![Apache](https://img.shields.io/badge/Apache-2.4-red)](https://httpd.apache.org/)

中国・海外市場向けパチンコ/スロットゲームをiframe埋め込みで配信するプラットフォーム。

## 🌟 主要機能

- **iframe埋め込み**: 外部サイトへのゲーム埋め込みに対応
- **マルチ言語**: 中国語、英語、日本語、韓国語対応
- **マルチ通貨**: CNY、USD、JPY、KRW対応
- **WebRTC**: リアルタイム映像配信
- **SDK API**: RESTful APIでゲームセッション管理

## 🚀 クイックスタート

### テストページ

```
https://ifreamnet8-development.up.railway.app/xxxadmin/test_china_embed.html
```

1. ページを開く（APIキーが自動取得されます）
2. 「🚀 开始游戏」ボタンをクリック
3. ゲームがiframe内に表示されます

### API使用例

```javascript
// 1. APIキー取得
const response = await fetch('/check_api_keys.php');
const { keys } = await response.json();
const apiKey = keys[0].key_value;

// 2. ゲーム開始
const gameResponse = await fetch('/api/v1/game_start.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${apiKey}`
    },
    body: JSON.stringify({
        memberNo: '9999',
        machineNo: 9999,
        currency: 'CNY',
        lang: 'zh',
        points: 1000
    })
});

const { gameUrl } = await gameResponse.json();

// 3. iframe表示
document.getElementById('gameFrame').src = gameUrl;
```

## 📚 ドキュメント

- **[完全デプロイマニュアル](./DEPLOYMENT_MANUAL.md)** - 詳細な設定とトラブルシューティング
- **[クイックデプロイガイド](./QUICK_DEPLOY_GUIDE.md)** - 5分でデプロイ

## 🏗️ 技術スタック

- **言語**: PHP 7.2
- **Webサーバー**: Apache 2.4
- **データベース**: MySQL 8.0
- **デプロイ**: Railway + Docker
- **リアルタイム通信**: WebRTC, PeerJS
- **認証**: JWT + API Key

## 🌐 サポート言語・通貨

### 言語
- 🇨🇳 简体中文 (zh)
- 🇺🇸 English (en)
- 🇯🇵 日本語 (ja)
- 🇰🇷 한국어 (ko)

### 通貨
- 💴 CNY - 人民币
- 💵 USD - US Dollar
- 💴 JPY - 日本円
- 💵 KRW - 韓国ウォン

## 📁 プロジェクト構造

```
ch_ifream/
├── Dockerfile              # Railwayデプロイ用
├── docker-entrypoint.sh    # 起動スクリプト
├── config/
│   ├── 000-default.conf    # Apache設定
│   └── php.ini             # PHP設定
└── ch/                     # アプリケーション
    ├── api/v1/             # REST API
    ├── data/               # DocumentRoot
    │   ├── play_embed/     # iframe埋め込みプレイヤー
    │   ├── play_v2/        # 通常プレイヤー
    │   └── xxxadmin/       # 管理ツール
    ├── _api/               # シグナリングAPI
    └── vendor/             # PHP依存関係
```

## ⚙️ 環境変数

```env
# データベース
DB_HOST=your_db_host
DB_NAME=your_db_name
DB_USER=your_db_user
DB_PASSWORD=your_db_password

# アプリケーション
ENVIRONMENT=test  # or production
```

## 🔧 開発

### ローカル開発

```bash
# リポジトリクローン
git clone https://github.com/mgg00123mg-prog/mgg001.git
cd mgg001/ch_ifream
git checkout feature/ifream

# Dockerビルド
docker build -t ch_ifream .

# コンテナ起動
docker run -p 8080:80 ch_ifream
```

### デプロイ

```bash
# 修正作業...

# キャッシュバスター更新（PHPファイル変更時）
# Dockerfile 4行目を編集

# コミット＆プッシュ
git add .
git commit -m "fix: 修正内容"
git push origin feature/ifream

# Railway が自動デプロイ（2-3分）
```

## 🐛 トラブルシューティング

### よくある問題

| 症状 | 原因 | 解決策 |
|-----|------|-------|
| 画像404 | `/data/img/` パス使用 | `/img/` に修正 |
| JS/CSS読めない | `/data/play_v2/` パス使用 | `/play_v2/` に修正 |
| API 400エラー | 無効なAPIキー | テストページで自動取得 |
| PHP変更反映されない | OPcacheキャッシュ | Dockerfileキャッシュバスター更新 |

詳細は [DEPLOYMENT_MANUAL.md](./DEPLOYMENT_MANUAL.md) を参照。

## 📊 API エンドポイント

### ゲーム管理

- `POST /api/v1/game_start.php` - ゲーム開始
- `POST /api/v1/game_end.php` - ゲーム終了
- `GET /api/v1/list_machines.php` - 機台一覧取得
- `GET /api/v1/models.php` - 機種一覧取得

### ユーティリティ

- `GET /check_api_keys.php` - APIキー取得/作成

## 🔐 セキュリティ

- すべてのAPI呼び出しに認証が必要
- CORS設定により外部サイトからの埋め込みに対応
- sessionIdベースの認証でセキュアなゲームセッション管理

## 📝 ライセンス

Proprietary - All Rights Reserved

## 👥 チーム

- **開発**: Claude Sonnet 4.5 + Human Team
- **デプロイ**: Railway Platform
- **インフラ**: Google Cloud (画像ストレージ)

## 🔗 関連リンク

- **本番環境**: https://ifreamnet8-development.up.railway.app/
- **テストページ**: https://ifreamnet8-development.up.railway.app/xxxadmin/test_china_embed.html
- **GitHub**: https://github.com/mgg00123mg-prog/mgg001
- **Railway**: https://railway.app/

## 📅 更新履歴

- **v14 (2026-01-27)**: APIキー自動取得機能追加
- **v13 (2026-01-27)**: 中国市場テストページ追加
- **v12 (2026-01-27)**: JS/CSS パス＆Apache設定修正
- **v11 (2026-01-27)**: WebRTC cameraId追加
- **v10 (2026-01-27)**: play_embed パス修正

---

**最終更新**: 2026-01-27
**現在のバージョン**: v14
**ステータス**: ✅ Production Ready
