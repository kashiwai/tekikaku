# NET8 オンラインパチンコ・スロットゲームシステム

## 📚 ドキュメント

- **[WebRTCシステム完全ドキュメント](./WEBRTC_SYSTEM_DOCUMENTATION.md)** ⭐ **必読**
  - システム全体のアーキテクチャ
  - 接続フロー詳細
  - プレイヤー側・Windows PC側実装
  - トラブルシューティング

- **[プロジェクト開発ルール](./CLAUDE.md)**
  - AI運用5原則
  - コーディング規約
  - 開発フロー

## 🚀 クイックスタート

### 本番環境URL
- **Webサーバー**: https://mgg-webservice-production.up.railway.app
- **プレイ画面**: https://mgg-webservice-production.up.railway.app/data/play/?NO=1

### デプロイ

```bash
git add .
git commit -m "feat: 修正内容"
git push origin main
```

Railwayが自動的にデプロイします。

## 🎮 動作確認

### マシン状態確認

```bash
curl "https://mgg-webservice-production.up.railway.app/data/api/debug_play_check.php?machine_no=1"
```

### マシンリセット

```bash
curl "https://mgg-webservice-production.up.railway.app/data/api/reset_machine_mode.php?machine_no=1"
```

## 📁 プロジェクト構造

```
net8/
├── WEBRTC_SYSTEM_DOCUMENTATION.md    # WebRTC完全ドキュメント ⭐
├── CLAUDE.md                          # 開発ルール
├── README.md                          # このファイル
├── 02.ソースファイル/
│   └── net8_html/
│       ├── data/
│       │   ├── play/                  # プレイ画面エントリー
│       │   ├── play_v2/               # プレイ画面メイン処理
│       │   │   ├── index.php          # 認証・テンプレート選択
│       │   │   ├── js/                # JavaScript
│       │   │   │   ├── view_auth.js   # WebRTC接続
│       │   │   │   ├── view_functions.js
│       │   │   │   └── playground.js
│       │   │   └── vendor/            # 外部ライブラリ
│       │   ├── api/                   # API
│       │   └── xxxadmin/              # 管理画面
│       ├── _html/                     # HTMLテンプレート
│       ├── _sys/                      # システムクラス
│       └── _etc/                      # 設定ファイル
└── docker/                            # Docker設定
    └── web/
        └── apache-config/
            └── 000-default.conf       # Apache設定
```

## 🔧 技術スタック

- **フロントエンド**: HTML5, JavaScript (jQuery), PeerJS
- **バックエンド**: PHP 7.4+, Apache
- **WebRTC**: PeerJS, STUN/TURN
- **データベース**: MySQL 8.0 (GCP Cloud SQL)
- **デプロイ**: Railway (Docker)

## ✅ 最新の修正 (2025/11/07)

### WebRTCストリーミング復旧成功！

- ✅ `/data/play_v2/index.php` 復元
- ✅ テンプレート選択ロジック修正
- ✅ HTMLパスを絶対パスに修正
- ✅ WebRTC接続成功
- ✅ 映像表示成功

## 📝 重要な注意事項

### HTMLテンプレートのパス

**❌ 間違い**:
```html
<link href="vendor/bootstrap/css/bootstrap.min.css">
```

**✅ 正しい**:
```html
<link href="/data/play_v2/vendor/bootstrap/css/bootstrap.min.css">
```

### Apache RewriteRule

`/vendor/` は除外されているため、実際のパス `/data/play_v2/vendor/` を使用する必要があります。

詳細は [WebRTCシステムドキュメント](./WEBRTC_SYSTEM_DOCUMENTATION.md) を参照。

## 🐛 トラブルシューティング

問題が発生した場合は、まず [WEBRTC_SYSTEM_DOCUMENTATION.md](./WEBRTC_SYSTEM_DOCUMENTATION.md) の「トラブルシューティング」セクションを確認してください。

## 📞 サポート

技術的な質問や問題報告は、開発チームにお問い合わせください。
