# callai プロジェクト — AI電話受電サービス

## プロジェクト概要

Twilio (050番号) + Retell AI を使った**日本語AIコールセンター自動化**サービス。
現在の主要クライアント：**パックライン**（PAL端末デジタルサイネージ保守コールセンター）

## アーキテクチャ

```
電話着信（050-XXXX-XXXX）
    │
    ▼
Twilio（050番号保持）
    │ Elastic SIP Trunk
    ▼
Retell AI（音声AI処理）
    ├── STT: Deepgram
    ├── LLM: GPT-4o
    ├── TTS: ElevenLabs Turbo v2.5
    │
    ├── Webhook → retell-dashboard（通話後処理）
    │                 ├── GPT要約・分析
    │                 ├── LINE通知
    │                 └── DB保存
    │
    └── 通話転送（必要時）→ 担当者
```

## ディレクトリ構成

```
callai/
├── src/                        # Twilio + Retell AI セットアップスクリプト
│   ├── setup-all.mjs           # メインオーケストレーター
│   ├── setup-twilio-sip.mjs    # Twilio SIP Trunk 設定
│   ├── setup-retell-agent.mjs  # Retell AI エージェント作成
│   ├── import-number.mjs       # 番号インポート
│   ├── check-status.mjs        # 状態確認
│   ├── list-calls.mjs          # 通話履歴
│   └── test-call.mjs           # テスト発信
│
├── retell-dashboard/           # Next.js 15 管理ダッシュボード
│   ├── app/                    # App Router
│   ├── components/             # UIコンポーネント
│   ├── config/
│   │   └── phone-tenant-mapping.ts  # テナント識別ロジック
│   ├── lib/                    # ユーティリティ
│   ├── prisma/                 # DB スキーマ
│   └── scripts/                # 管理スクリプト
│
└── retell_ai_PAL_agent_settings.md  # PALエージェント設定書（プロンプト・Function定義）
```

## 技術スタック

- **Backend/Scripts**: Node.js 18+, ESM modules
- **Dashboard**: Next.js 15 (App Router), TypeScript, Tailwind CSS
- **DB**: Prisma（SQLite → 本番はPostgres想定）
- **AI**: Retell AI SDK v4, OpenAI GPT-3.5-turbo（要約）
- **電話**: Twilio Elastic SIP Trunk
- **通知**: LINE Messaging API
- **Deploy**: Vercel（ダッシュボード）

## PALエージェント構成

### エージェント①：インバウンド（店舗問い合わせ受付）
- 名前：「白石」
- 役割：PAL端末故障の受付・切り分け
- Function: `create_ticket`, `escalate_to_magee`, `transfer_call`

### エージェント②：アウトバウンド（TTSS対応指示連絡）
- 名前：「樋口」
- 役割：東芝テックソリューションサービスへの対応指示連絡
- Function: `send_instruction_email`, `log_outbound_result`

## 現在の実装状況

| 機能 | 状況 |
|---|---|
| Twilio SIP Trunk 接続 | ✅ 完了 |
| Retell AI エージェント基本設定 | ✅ 完了 |
| ダッシュボード 通話管理・GPT要約 | ✅ 完了 |
| マルチテナント基盤（電話番号ベース識別） | ✅ 70% |
| 管理者向けアカウント発行UI | ❌ 未実装 |
| テナント別ダッシュボード表示 | ❌ 未実装 |
| テナント切り替えUI | ❌ 未実装 |

## 開発コマンド

```bash
# セットアップスクリプト
npm run setup          # 全自動セットアップ
npm run status         # 設定状態確認
npm run list:calls     # 通話履歴確認

# ダッシュボード（retell-dashboard/）
bun dev                # 開発サーバー起動
bun run build          # ビルド
```

## 環境変数（.envで管理 — コードに書かない）

- `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_PHONE_NUMBER`
- `RETELL_API_KEY`, `RETELL_AGENT_ID`
- `N8N_WEBHOOK_URL`
- `TENANT_PHONE_MAPPING`（JSON形式）
- LINE 関連トークン

## 重要な注意事項

- APIキーは絶対にコードやREADMEに直接書かない（.envのみ）
- gitにはAPIキーをコミットしない
- deploy: Vercelへのpushは git push で自動（GitHub連携）
- ブランチ `main` がVercel本番

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
