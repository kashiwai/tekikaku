# Net8 決済連携ガイド

決済システムとNet8ポイントを連携するためのガイドです。

---

## 概要

```
┌─────────────────────────────────────────────────────────────────┐
│                        決済フロー                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   ユーザー ──▶ 決済選択 ──▶ 決済処理 ──▶ Webhook ──▶ Net8入金   │
│                                                                 │
│   [Stripe]     [金額選択]   [カード決済]  [完了通知]  [ポイント] │
│   [PayPal]                  [コンビニ]               [game_start]│
│   [その他]                  [キャリア]                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 1. Stripe連携（推奨）

### 1.1 環境変数設定

```env
STRIPE_SECRET_KEY=sk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
NET8_API_KEY=pk_live_xxxxx
NET8_API_BASE=https://mgg-webservice-production.up.railway.app
```

### 1.2 チェックアウト作成

```javascript
// pages/api/checkout.js
import Stripe from 'stripe';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY);

export default async function handler(req, res) {
  const { amount, userId } = req.body;

  // ポイントパッケージ（例: 1000円 = 1000ポイント）
  const session = await stripe.checkout.sessions.create({
    payment_method_types: ['card'],
    line_items: [{
      price_data: {
        currency: 'jpy',
        product_data: {
          name: `${amount}ポイント`,
          description: 'Net8ゲームポイント',
        },
        unit_amount: amount,
      },
      quantity: 1,
    }],
    mode: 'payment',
    success_url: `${process.env.NEXT_PUBLIC_URL}/game?success=true&session_id={CHECKOUT_SESSION_ID}`,
    cancel_url: `${process.env.NEXT_PUBLIC_URL}/game?canceled=true`,
    metadata: {
      userId: userId,
      points: amount.toString(),
      type: 'net8_points'
    }
  });

  res.json({ sessionId: session.id, url: session.url });
}
```

### 1.3 Webhook受信

```javascript
// pages/api/webhooks/stripe.js
import { buffer } from 'micro';
import Stripe from 'stripe';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY);

export const config = {
  api: { bodyParser: false }
};

export default async function handler(req, res) {
  const buf = await buffer(req);
  const sig = req.headers['stripe-signature'];

  let event;
  try {
    event = stripe.webhooks.constructEvent(buf, sig, process.env.STRIPE_WEBHOOK_SECRET);
  } catch (err) {
    return res.status(400).send(`Webhook Error: ${err.message}`);
  }

  // 決済完了
  if (event.type === 'checkout.session.completed') {
    const session = event.data.object;

    if (session.metadata.type === 'net8_points') {
      const { userId, points } = session.metadata;

      // Net8にポイントをデポジット
      await depositToNet8(userId, parseInt(points), session.payment_intent);

      console.log(`✅ Deposited ${points} points to user ${userId}`);
    }
  }

  res.json({ received: true });
}

// Net8へのデポジット関数
async function depositToNet8(userId, points, paymentId) {
  const response = await fetch(`${process.env.NET8_API_BASE}/api/v1/deposit.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${process.env.NET8_API_KEY}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      amount: points,
      source: 'stripe',
      externalTransactionId: paymentId,
      description: 'Stripe決済によるポイント購入'
    })
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error(`Net8 deposit failed: ${data.message}`);
  }

  return data;
}
```

### 1.4 フロントエンド（購入ボタン）

```jsx
// components/BuyPoints.jsx
import { loadStripe } from '@stripe/stripe-js';

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_KEY);

export function BuyPoints({ userId }) {
  const packages = [
    { amount: 1000, label: '1,000ポイント', bonus: '' },
    { amount: 3000, label: '3,000ポイント', bonus: '+100ボーナス' },
    { amount: 5000, label: '5,000ポイント', bonus: '+300ボーナス' },
    { amount: 10000, label: '10,000ポイント', bonus: '+1000ボーナス' },
  ];

  async function handlePurchase(amount) {
    const response = await fetch('/api/checkout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ amount, userId })
    });

    const { url } = await response.json();
    window.location.href = url;  // Stripeチェックアウトへリダイレクト
  }

  return (
    <div className="buy-points">
      <h2>ポイント購入</h2>
      <div className="packages">
        {packages.map(pkg => (
          <button key={pkg.amount} onClick={() => handlePurchase(pkg.amount)}>
            <span className="amount">{pkg.label}</span>
            <span className="bonus">{pkg.bonus}</span>
            <span className="price">¥{pkg.amount.toLocaleString()}</span>
          </button>
        ))}
      </div>
    </div>
  );
}
```

---

## 2. PayPal連携

### 2.1 PayPal SDK設定

```html
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=JPY"></script>
```

### 2.2 PayPalボタン

```javascript
paypal.Buttons({
  createOrder: (data, actions) => {
    return actions.order.create({
      purchase_units: [{
        amount: { value: '1000' },
        description: 'Net8 ゲームポイント 1000pt'
      }]
    });
  },
  onApprove: async (data, actions) => {
    const order = await actions.order.capture();

    // サーバーでNet8にデポジット
    await fetch('/api/paypal/complete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        orderId: order.id,
        userId: currentUserId,
        amount: 1000
      })
    });

    alert('購入完了！');
  }
}).render('#paypal-button');
```

---

## 3. コンビニ決済連携

### 3.1 GMO Payment Gateway経由

```javascript
// コンビニ決済リクエスト
async function createConvenienceStorePayment(userId, amount) {
  const response = await fetch('/api/gmo/convenience', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      userId,
      amount,
      storeType: 'seven-eleven'  // lawson, family-mart, etc.
    })
  });

  const { paymentNumber, expireDate } = await response.json();

  // ユーザーに支払い番号を表示
  return { paymentNumber, expireDate };
}
```

### 3.2 Webhook（支払い完了通知）

```javascript
// GMOからの入金通知
app.post('/api/gmo/webhook', async (req, res) => {
  const { orderId, status, userId, amount } = req.body;

  if (status === 'PAID') {
    // Net8にポイントをデポジット
    await depositToNet8(userId, amount, orderId);
  }

  res.send('OK');
});
```

---

## 4. 出金（払い戻し）

### 4.1 出金申請フロー

```
ユーザー ──▶ 出金申請 ──▶ 残高確認 ──▶ Net8減算 ──▶ 銀行振込
                │
                ▼
            本人確認（KYC）
```

### 4.2 出金API

```javascript
// pages/api/withdraw.js
export default async function handler(req, res) {
  const { userId, amount, bankAccount } = req.body;

  // 1. 最小出金額チェック
  if (amount < 1000) {
    return res.status(400).json({ error: '最小出金額は1,000ポイントです' });
  }

  // 2. Net8残高確認
  const balanceRes = await fetch(`${NET8_API_BASE}/api/v1/balance.php?userId=${userId}`, {
    headers: { 'Authorization': `Bearer ${NET8_API_KEY}` }
  });
  const { balance } = await balanceRes.json();

  if (balance < amount) {
    return res.status(400).json({ error: '残高不足です' });
  }

  // 3. 出金手数料計算
  const fee = Math.floor(amount * 0.03);  // 3%手数料
  const netAmount = amount - fee;

  // 4. Net8からポイント減算
  const withdrawRes = await fetch(`${NET8_API_BASE}/api/v1/withdraw.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${NET8_API_KEY}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId,
      amount,
      reason: 'user_withdrawal'
    })
  });

  if (!withdrawRes.ok) {
    return res.status(500).json({ error: '出金処理に失敗しました' });
  }

  // 5. 銀行振込をキュー登録（バッチ処理）
  await queueBankTransfer({
    userId,
    amount: netAmount,
    bankAccount,
    fee
  });

  res.json({
    success: true,
    amount: netAmount,
    fee,
    message: '出金申請を受け付けました。3営業日以内に振込予定です。'
  });
}
```

---

## 5. ポイントシステム設計

### 5.1 レート設定

```javascript
// config/points.js
export const POINT_CONFIG = {
  // 購入レート（1円 = 1ポイント）
  PURCHASE_RATE: 1,

  // ボーナスレート
  BONUS_TIERS: [
    { min: 1000, max: 2999, bonus: 0 },
    { min: 3000, max: 4999, bonus: 100 },
    { min: 5000, max: 9999, bonus: 300 },
    { min: 10000, max: Infinity, bonus: 1000 },
  ],

  // 出金レート（1ポイント = 0.97円、3%手数料）
  WITHDRAWAL_RATE: 0.97,

  // 最小出金額
  MIN_WITHDRAWAL: 1000,

  // ゲーム消費レート
  GAME_CONSUME_RATE: 100,  // 1ゲーム100ポイント
};
```

### 5.2 トランザクション管理

```javascript
// すべてのポイント移動を記録
const transactionTypes = {
  PURCHASE: 'purchase',      // 購入
  BONUS: 'bonus',            // ボーナス付与
  GAME_START: 'game_start',  // ゲーム開始（消費）
  GAME_WIN: 'game_win',      // ゲーム獲得
  WITHDRAWAL: 'withdrawal',  // 出金
  REFUND: 'refund',          // 返金
  TRANSFER: 'transfer',      // 送金
  ADMIN: 'admin',            // 管理者操作
};
```

---

## 6. セキュリティチェックリスト

```
□ Webhook署名検証を実装
□ HTTPS通信のみ許可
□ APIキーを環境変数で管理
□ レート制限を設定
□ 不正検知ロジックを実装
□ 取引ログを保存
□ PCI DSS準拠（カード情報非保持）
□ 本人確認（KYC）フロー実装
```

---

## 7. テスト環境

### テスト用カード番号（Stripe）

| カード番号 | 結果 |
|-----------|------|
| 4242 4242 4242 4242 | 成功 |
| 4000 0000 0000 0002 | 拒否 |
| 4000 0000 0000 9995 | 残高不足 |

### テストモード設定

```env
# テスト環境
STRIPE_SECRET_KEY=sk_test_xxxxx
NET8_API_KEY=pk_test_xxxxx
NET8_API_BASE=https://mgg-webservice-staging.up.railway.app
```

---

## 8. 本番チェックリスト

- [ ] 本番APIキーに切り替え
- [ ] Webhook URLを本番に設定
- [ ] SSL証明書確認
- [ ] エラー通知設定（Slack/メール）
- [ ] バックアップ設定
- [ ] 負荷テスト完了
- [ ] 法務確認（利用規約、特商法表記）

---

*最終更新: 2025-12-18*
