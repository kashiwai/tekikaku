# Net8 SDK ドキュメント

Net8パチンコゲームサービスを外部サービスに統合するためのドキュメント集です。

---

## ドキュメント一覧

| ドキュメント | 対象者 | 内容 |
|-------------|--------|------|
| [QUICKSTART.md](./QUICKSTART.md) | 全員 | 5分で始める最速ガイド |
| [NET8_SDK_INTEGRATION_GUIDE.md](./NET8_SDK_INTEGRATION_GUIDE.md) | 開発者 | 完全統合ガイド（API仕様含む） |
| [PAYMENT_INTEGRATION.md](./PAYMENT_INTEGRATION.md) | 開発者 | 決済システム連携ガイド |

---

## 読む順番

### 初めての方

```
1. QUICKSTART.md          → まず動かしてみる
2. NET8_SDK_INTEGRATION_GUIDE.md → 詳細を理解する
3. PAYMENT_INTEGRATION.md → 決済を連携する
```

### 決済連携だけ知りたい方

```
1. PAYMENT_INTEGRATION.md のみ
```

---

## クイックリンク

- **APIエンドポイント一覧**: [SDK統合ガイド - 付録](./NET8_SDK_INTEGRATION_GUIDE.md#付録-api一覧)
- **トラブルシューティング**: [SDK統合ガイド - セクション7](./NET8_SDK_INTEGRATION_GUIDE.md#7-トラブルシューティング)
- **Stripe連携**: [決済連携ガイド - セクション1](./PAYMENT_INTEGRATION.md#1-stripe連携推奨)

---

## 本番環境情報

| 項目 | URL |
|------|-----|
| API Base | `https://mgg-webservice-production.up.railway.app` |
| ゲーム画面 | `https://mgg-webservice-production.up.railway.app/play_embed/` |
| シグナリング | `wss://mgg-signaling-production-c1bd.up.railway.app` |

---

## サポート

- 技術的な質問: support@net8.example.com
- APIキー発行: api@net8.example.com
- 緊急連絡: emergency@net8.example.com

---

*Version 1.0.0 | 2025-12-18*
