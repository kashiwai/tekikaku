# SNS自動化システム (snsmkplan)

## 概要

Claude Codeを使ったショート動画自動生成・投稿システム。
6つのAIエージェントがリレー形式で動作し、商品アフィリエイト動画を自動で量産する。

## アーキテクチャ

### 6つのエージェント

| エージェント | ファイル | 役割 |
|---|---|---|
| 台本作成くん | `src/agents/script-creator.ts` | 商品情報→ナレーション+テロップ台本生成 |
| 音声生成くん | `src/agents/voice-generator.ts` | 台本→音声ファイル生成（TTS API） |
| 投稿文生成くん | `src/agents/post-text-generator.ts` | キャプション+ハッシュタグ生成 |
| 動画編集くん | `src/agents/video-editor.ts` | 素材+音声+テロップ→動画合成（Remotion/FFmpeg） |
| 自動投稿くん | `src/agents/auto-poster.ts` | Instagram/TikTokへ自動投稿 |
| 改善提案くん | `src/agents/improvement-suggester.ts` | 数字分析→改善レポート→Slack通知 |

### TTS（音声合成）対応プロバイダー

`.env`の`TTS_PROVIDER`で切り替え:
- `fishaudio` — FishAudio API（デフォルト）
- `voicevox` — VOICEVOX（ローカル無料）
- `elevenlabs` — ElevenLabs API

## フォルダ構造

```
snsmkplan/
├── src/
│   ├── agents/          # 6つのAIエージェント
│   ├── tts/             # TTSアダプター（FishAudio/VOICEVOX/ElevenLabs）
│   ├── config/          # 設定ローダー・型定義
│   ├── social/          # Instagram/TikTok APIクライアント
│   ├── notification/    # Slack通知
│   ├── pipeline.ts      # メインパイプライン
│   └── scheduler.ts     # cronスケジューラー
├── config/
│   ├── products/        # 商品設定YAML（人間が準備）
│   ├── scenes/          # シーン構成YAML（人間が準備）
│   └── captions/        # 台本テンプレートYAML（人間が準備）
├── materials/
│   ├── scene1/〜scene7/ # 各シーンの素材（番号付きで管理）
│   └── bgm/             # BGM音楽ファイル
├── output/              # 生成物（自動生成）
│   ├── scripts/         # 生成された台本JSON
│   ├── audio/           # 生成された音声ファイル
│   ├── video/           # 完成動画
│   ├── reports/         # 改善レポート
│   └── state.json       # 使用済み商品・テンプレート管理
└── remotion/            # Remotion動画合成コンポーネント
```

## セットアップ

### 1. 環境変数設定

```bash
cp .env.example .env
# .envを編集して各APIキーを設定
```

必須:
- `ANTHROPIC_API_KEY` — Claude API
- `TTS_PROVIDER` + 対応するAPIキー
- `SLACK_WEBHOOK_URL` — 通知用

投稿する場合（snsautodash.biz）:
- `SNSAUTODASH_EMAIL` — snsautodash.biz のログインメール
- `SNSAUTODASH_PASSWORD` — snsautodash.biz のパスワード
- `SNSAUTODASH_URL` — デフォルト `https://snsautodash.biz`

snsautodash.biz は自前のSNS管理システム（Manus上で開発）。
`mediaUpload.upload` tRPC でBase64動画をアップロード、`mediaUpload.postNow` で即時投稿。
内部で LateAPI 経由でInstagram/TikTok/X に実際に投稿される。

### 2. 商品登録

`config/products/` にYAMLファイルを追加:
```yaml
products:
  - id: unique_id
    name: "商品名"
    category: "コスメ"   # ← sceneテンプレートのcategoryと一致させる
    affiliate_url: "https://..."
    reward: 518
    hashtags:
      instagram: ["#タグ1", "#タグ2"]
      tiktok: ["#タグ1"]
    active: true
```

### 3. 素材準備

`materials/scene1/〜scene7/` に画像・動画を配置。
ファイル名に番号を付けると動画ごとに異なる素材が使われる:
- `1_商品.jpg`, `2_商品.jpg` → 毎回違う素材セットが選択される

BGMは `materials/bgm/` に `.mp3` または `.wav` で配置。

### 4. 実行

```bash
# テスト実行（投稿なし）
bun run dry-run

# 今すぐ実行（投稿あり）
bun run run

# スケジューラー起動（毎日自動実行）
bun run schedule

# 改善分析のみ
bun run analyze
```

## 環境変数一覧

| 変数 | デフォルト | 説明 |
|---|---|---|
| `TTS_PROVIDER` | `fishaudio` | TTSプロバイダー |
| `DAILY_CRON` | `0 9 * * *` | 動画生成の実行時刻（JST） |
| `ANALYSIS_CRON` | `0 20 * * *` | 分析の実行時刻（JST） |
| `VIDEOS_PER_DAY` | `3` | 1日の動画生成本数 |
| `PLATFORMS` | `instagram,tiktok` | 投稿先プラットフォーム |
| `DRY_RUN` | `false` | trueにすると投稿をスキップ |
| `OUTPUT_DIR` | `./output` | 出力ディレクトリ |

## 重要な設計原則

1. **AIとプログラムの役割分離**
   - AI: 台本生成・改善提案（創造的判断）
   - プログラム: テロップ改行・素材割当・動画合成（精密なルール処理）

2. **素材は事前準備・使い回し**
   - 毎回AI生成しない（コスト・一貫性のため）
   - 番号管理で自動ローテーション

3. **状態管理**
   - `output/state.json` で使用済み商品・テンプレートを追跡
   - 同じ組み合わせが連続しないよう自動調整

## Skill routing

When the user's request matches an available skill, ALWAYS invoke it using the Skill
tool as your FIRST action. Do NOT answer directly, do NOT use other tools first.
The skill has specialized workflows that produce better results than ad-hoc answers.

Key routing rules:
- Product ideas, "is this worth building", brainstorming → invoke office-hours
- Bugs, errors, "why is this broken", 500 errors → invoke investigate
- Ship, deploy, push, create PR → invoke ship
- QA, test the site, find bugs → invoke qa
- Code review, check my diff → invoke review
- Update docs after shipping → invoke document-release
- Weekly retro → invoke retro
- Design system, brand → invoke design-consultation
- Visual audit, design polish → invoke design-review
- Architecture review → invoke plan-eng-review
- Save progress, checkpoint, resume → invoke checkpoint
- Code quality, health check → invoke health
