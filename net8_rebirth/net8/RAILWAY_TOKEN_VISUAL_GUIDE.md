# Railway APIトークン取得 - 視覚的ガイド

## ❌ これまで提供されたID（すべてAPIトークンではありません）

```
7df95f36-062f-4409-83f1-31bc8d58f22f  ← UUID形式（36文字）
89e18cd9-ac80-4e09-bb1b-42eb24a86e5f  ← UUID形式（36文字）
c6cec141-f332-41b2-8633-1b0de3cad2da  ← UUID形式（36文字）
```

これらはすべて**プロジェクトID、チームID、またはアカウントID**です。

---

## ✅ 正しいRailway APIトークンの形式

```
rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*ZvxYYdIfLIGhQN83LRo59g*OJ_RMGezUWjrKheHHLI4kzZWfpaDJeSvVjv1WeuQUzQ0_ZHbrZyHPb3jcdBTY0POw5cBLxw8XLdwNxoc-Onp8w*1756945667239*dda61c80e42193c37e439e42e5dabf2c092c6e4d92c4c10b031fce0907d59bb1*8xxg1hZjw6L6xVwT04krqSKuZRSKh342NUBmmJ-d8Fs
```

**特徴**:
- ✅ 先頭が `rw_` で始まる
- ✅ 200文字以上（非常に長い）
- ✅ `Fe26.2**` が含まれる
- ✅ `*` で区切られた複数のセグメント

---

## 📸 正しいAPIトークン取得手順（詳細）

### Step 1: Railway Tokensページに直接アクセス

**URL**: https://railway.com/account/tokens

⚠️ **重要**: この URL を直接開いてください

**または**:
1. https://railway.app/ にアクセス
2. 右上のアカウントアイコン（自分の名前またはアバター）をクリック
3. ドロップダウンメニューから "Account Settings" を選択
4. 左サイドバーの "Tokens" をクリック

---

### Step 2: トークン作成画面を確認

**画面に以下が表示されているはずです**:

```
Tokens
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Personal tokens allow you to interact with the Railway API.

[Create New Token] ← このボタンをクリック
```

**注意**:
- プロジェクト一覧画面ではありません
- チーム設定画面でもありません
- 「Tokens」と書かれたページです

---

### Step 3: トークンを作成

1. **"Create New Token" ボタンをクリック**

2. **ダイアログが表示されます**:
   ```
   Create Token
   ━━━━━━━━━━━━━━━━━━━━━━━━━━

   Name: [入力欄]

   [Cancel] [Create]
   ```

3. **トークン名を入力**:
   ```
   net8-deployment
   ```

4. **"Create" ボタンをクリック**

---

### Step 4: トークンをコピー

**重要**: トークンは**一度しか表示されません**

**表示される画面**:
```
Token Created
━━━━━━━━━━━━━━━━━━━━━━━━━━

Copy and save this token. You will not be able to see it again.

rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*...
                    ↑ この長い文字列全体をコピー

[Copy] [Close]
```

**正しいコピー方法**:
1. "Copy" ボタンをクリック
2. または、テキストを全選択してコピー（Ctrl+A → Ctrl+C）

**コピーするもの**:
- ✅ `rw_` から始まる
- ✅ 末尾まで全部
- ✅ 改行なし、スペースなし

---

## 🔍 取得したものが正しいか確認

### ❌ 間違い（UUID形式）

```
c6cec141-f332-41b2-8633-1b0de3cad2da
```

- 36文字
- ハイフンで区切られている
- 先頭が `rw_` ではない

**これはプロジェクトID/チームID/アカウントIDです**

---

### ✅ 正しい（APIトークン）

```
rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*ZvxYYdIfLIGhQN83LRo59g*OJ_RMGezUWjrKheHHLI4kzZWfpaDJeSvVjv1WeuQUzQ0_ZHbrZyHPb3jcdBTY0POw5cBLxw8XLdwNxoc-Onp8w*1756945667239*dda61c80e42193c37e439e42e5dabf2c092c6e4d92c4c10b031fce0907d59bb1*8xxg1hZjw6L6xVwT04krqSKuZRSKh342NUBmmJ-d8Fs
```

- 200文字以上
- 先頭が `rw_` で始まる
- `*` で区切られている

**これが正しいAPIトークンです**

---

## 🆘 トラブルシューティング

### Q: "Tokens" メニューが見つからない

**A**: 以下を確認してください
- Railway にログインしていますか？
- 個人アカウントですか？（チームアカウントではないか）
- https://railway.com/account/tokens に直接アクセスしてください

---

### Q: トークン作成画面が違う

**A**: プロジェクトトークンではなく、**個人トークン**を作成してください
- プロジェクト設定画面 → ❌ 間違い
- アカウント設定 → Tokens → ✅ 正しい

---

### Q: どうしてもAPIトークンが取得できない

**A**: 手動デプロイに切り替えましょう

Railway Dashboard から直接デプロイする方法：
1. https://railway.app/ にアクセス
2. "+ New Project" をクリック
3. "Deploy from GitHub repo" を選択
4. "mgg00123mg-prog/mgg001" を選択

詳細: `RAILWAY_QUICK_START.md` を参照

**所要時間**: 15-20分

---

## 📋 取得したトークンの送信方法

取得したトークン（`rw_Fe26.2**...`）を**全文**そのままコピーして送信してください。

**良い例**:
```
rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*ZvxYYdIfLIGhQN83LRo59g*OJ_RMGezUWjrKheHHLI4kzZWfpaDJeSvVjv1WeuQUzQ0_ZHbrZyHPb3jcdBTY0POw5cBLxw8XLdwNxoc-Onp8w*1756945667239*dda61c80e42193c37e439e42e5dabf2c092c6e4d92c4c10b031fce0907d59bb1*8xxg1hZjw6L6xVwT04krqSKuZRSKh342NUBmmJ-d8Fs
```

**悪い例**:
```
c6cec141-f332-41b2-8633-1b0de3cad2da
```

---

## 🎯 次のアクション

### 選択肢1: APIトークンを再取得（推奨）

1. **https://railway.com/account/tokens** を開く
2. "Create New Token" をクリック
3. 生成されたトークン（`rw_` で始まる長い文字列）をコピー
4. **全文**をこちらに送信

→ 全自動デプロイを実行します

---

### 選択肢2: 手動デプロイ（APIトークン不要）

`RAILWAY_QUICK_START.md` の手順に従って、Railway Dashboard から手動でデプロイ

**メリット**:
- ブラウザ操作のみ
- APIトークン不要
- 所要時間: 15-20分

---

**推奨**: 選択肢1（API自動デプロイ）の方が早くて確実です

**APIトークン取得ページ**: https://railway.com/account/tokens
