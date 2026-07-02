# AI漫画自動生成SaaS - Claude Code開発ガイド

## プロジェクト概要
誰でも、簡単に、プロ品質の漫画を生成できるWebサービス。

**3つの制作方法**:
1. URL貼付 - LPのURLから広告漫画を自動生成
2. キャラ画像 - 画像からストーリーを作成
3. テキスト - あらすじから漫画を生成

## クイックスタート

```bash
# バックエンド
cd backend && python main.py  # port 8000

# フロントエンド
cd frontend && npm run dev -- -p 3002  # port 3002
```

## 技術スタック

- **Backend**: FastAPI + SQLAlchemy + asyncpg
- **Frontend**: Next.js 14 + Tailwind + Zustand
- **AI**: OpenAI GPT-4o (テキスト) + Google Gemini 3 Pro (画像)
- **DB**: Supabase PostgreSQL

## 重要ファイル

| パス | 説明 |
|-----|------|
| `backend/main.py` | FastAPIアプリ、CORS設定 |
| `backend/api/v1/manga.py` | 漫画生成API |
| `backend/services/imagen_service.py` | Gemini画像生成 |
| `frontend/src/stores/generation.ts` | 生成状態管理 |
| `frontend/src/components/editor/GenerationWizard/` | 生成ウィザード |
| `.claude/DEV_STATUS.md` | 詳細な開発状況 |

## データベース

```
Host: db.uutyabkxjdjnhtqtwtpk.supabase.co
User: postgres
Pass: Nene11091108!!
```

## テストユーザー

```
Email: test@example.com
Password: testpassword123
```

## 最近の修正 (2024-12-24)

1. bcrypt 4.0.1にダウングレード
2. Imagen APIレスポンス形式修正
3. AuthInitializer追加
4. CORS localhost:3000-3003許可
5. /subscription ページ新規作成
6. /settings ページ新規作成

## 詳細情報

詳しい開発状況は `.claude/DEV_STATUS.md` を参照。

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
- Author a backlog-ready spec/issue → invoke /spec
