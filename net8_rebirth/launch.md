# プロジェクト設定

## プロジェクト名

Net8 Rebirth - パチンコゲームプラットフォーム

## 概要

リアルタイム通信対応のパチンコゲームプラットフォーム。
WebSocket、PeerJSを使用したマルチプレイヤー対応。
Railway上でのデプロイ、MySQL + PHP + JavaScript構成。

## 主要技術

- **Backend**: PHP 7.4+（カスタムフレームワーク）
- **Database**: MySQL 8.0
- **Frontend**: JavaScript (ES6+), jQuery, Canvas API
- **Real-time**: WebSocket, PeerJS
- **Deployment**: Railway (Docker), Apache/Nginx

## 開発環境セットアップ

```bash
# ローカル環境
# MAMP/XAMPP または Docker を使用

# Railwayデプロイ
railway up

# データベースマイグレーション
# net8/02.ソースファイル/net8_html/data/server/*.sql を実行
```

## 重要な決定事項

- セッション管理: SESSION_SEC = 3600秒（整数値）
- 画像ストレージ: BLOB形式でDB保存
- ログインボーナス: PlayPoint.php でポイント付与処理
- Railway環境: WebSocket + PeerJS 統合デプロイ

## 開発フロー

1. `/launch-task` でタスク開始
2. 実装 → `/quality-gate` で品質確認
3. `/pre-deploy-check` でデプロイ準備確認
4. Railway デプロイ

## プロジェクト構造

```
net8_rebirth/
├── net8/
│   ├── 02.ソースファイル/
│   │   └── net8_html/          # メインアプリケーション
│   │       ├── _lib/           # コアライブラリ
│   │       ├── data/           # データ・SQL
│   │       ├── user/           # ユーザー向け画面
│   │       └── admin/          # 管理画面
├── .claude/
│   ├── hooks/                  # Claude Code フック
│   └── workspace/              # 作業ログ・記憶
├── CLAUDE.md                   # AI運用ルール
├── task.md                     # タスク管理
└── launch.md                   # このファイル
```

## カスタムコマンド

- `/onboard` - 新プロジェクト開始時の完全オンボーディング
- `/launch-task` - タスク実行開始（計画確認フロー統合）
- `/quality-gate` - コード品質自動検証
- `/pre-deploy-check` - デプロイ前完全チェックリスト
- `/retrospect` - プロジェクト振り返りと自動記録
- `/update-index` - プロジェクトインデックス更新
- `/search-memory` - Cipher記憶検索
- `/memory-stats` - 記憶システム統計

## セットアップ完了日

2025-11-05（最新更新）
