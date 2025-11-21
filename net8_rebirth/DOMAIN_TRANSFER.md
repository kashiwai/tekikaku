# ドメイン移管完全ガイド

---

## 📋 目次
1. [他社 → Cloudflare への移管](#他社からcloudflareへの移管)
2. [Cloudflare → 他社 への移管](#cloudflareから他社への移管)
3. [移管時の注意点](#移管時の注意点)
4. [トラブルシューティング](#トラブルシューティング)

---

## 他社からCloudflareへの移管

### 前提条件チェック
- [ ] ドメイン登録から60日以上経過している
- [ ] ドメインが有効期限内である
- [ ] ドメインがロック解除されている
- [ ] 現在のレジストラで「認証コード（Auth Code / EPP Code）」を取得済み
- [ ] WHOIS情報の管理者メールアドレスが有効

### ステップ1: 現在のレジストラでの準備（10分）

#### お名前.comの場合
```
1. お名前.com Naviにログイン
2. 「ドメイン設定」→「ドメイン移管ロック」→「解除」
3. 「認証鍵（Auth Code）」を取得してメモ
4. WHOIS情報のメールアドレスが有効か確認
```

#### GoDaddyの場合
```
1. GoDaddy アカウントにログイン
2. My Products → Domains → ドメイン選択
3. "Unlock" をクリック
4. "Get authorization code" をクリック → メモ
```

#### Namecheapの場合
```
1. Namecheap Dashboard → Domain List
2. ドメイン横の "Manage" をクリック
3. "Sharing & Transfer" → "Unlock"
4. "Auth Code" を表示してメモ
```

### ステップ2: Cloudflare での移管開始（15分）

```
1. Cloudflare Dashboard にログイン
   https://dash.cloudflare.com/

2. "Domain Registration" → "Transfer Domains" をクリック

3. ドメイン名を入力
   例: net8game.com

4. "Unlock & get authorization code" の確認
   ✅ Unlocked
   ✅ Auth Code 取得済み

5. "Continue" をクリック

6. Auth Code を入力
   例: ABC123XYZ456

7. WHOIS情報の確認・入力
   - 名前
   - メールアドレス
   - 住所
   ※ Cloudflare は WHOIS保護が自動でON

8. 支払い（移管料金）
   .com の場合: $9.77（1年延長分含む）

9. "Initiate Transfer" をクリック
```

### ステップ3: メール承認（5分）

```
1. WHOIS登録メールアドレスに承認メールが届く
   件名: "Transfer Confirmation for net8game.com"

2. メール内の承認リンクをクリック

3. "Approve Transfer" をクリック

4. 完了！
```

### ステップ4: 元のレジストラでの承認（オプション）

**自動承認の場合:**
- 5日間待つと自動的に移管完了

**手動承認で早める場合:**
```
お名前.com:
1. お名前.com Naviにログイン
2. 「移管申請の承認」メールが届く
3. メール内のリンクから承認

GoDaddy/Namecheap:
1. ダッシュボードで "Pending Transfers" を確認
2. "Accept" をクリック
```

### 移管完了までの時間
- **最短**: 数時間（両方で手動承認）
- **通常**: 5-7日（自動承認）

---

## Cloudflareから他社への移管

### ステップ1: 移管ロック解除（Cloudflare側）

```
1. Cloudflare Dashboard にログイン

2. "Domain Registration" → ドメイン選択

3. "Configuration" タブ

4. "Transfer Lock" → "Unlock" をクリック

5. "Authorization Code" を表示してメモ
```

### ステップ2: 移管先レジストラでの手続き

#### お名前.comへ移管する場合
```
1. お名前.com にアクセス
   https://www.onamae.com/

2. 「ドメイン移管」をクリック

3. ドメイン名を入力 → 検索

4. Cloudflareで取得した Auth Code を入力

5. WHOIS情報を入力

6. 支払い（移管料金 + 1年延長）

7. メール承認

8. 完了
```

#### Namecheapへ移管する場合
```
1. Namecheap にログイン
   https://www.namecheap.com/

2. "Transfer" タブ

3. ドメイン名を入力 → 検索

4. Auth Code を入力

5. WHOIS Protection を選択（無料）

6. 支払い

7. メール承認

8. 完了
```

---

## 移管時の注意点

### ⚠️ ダウンタイムを防ぐ方法

**DNS設定を事前にコピー:**
```
移管前に現在のDNSレコードをメモ:
- Aレコード
- CNAMEレコード
- MXレコード（メール使用時）
- TXTレコード

移管後すぐに新しいレジストラで同じ設定を追加
```

**推奨手順:**
```
1. 移管開始前に、移管先でDNSレコードを設定
2. 移管中もサービス継続
3. 移管完了後、DNSの伝播を確認（最大48時間）
```

### 📅 移管できない期間

以下の場合、移管できません：
- ❌ ドメイン登録から60日以内
- ❌ 前回の移管から60日以内
- ❌ ドメイン有効期限まで15日以内
- ❌ ドメインが裁判所命令等でロックされている

### 💰 移管時の料金

| レジストラ | 移管料金（.com） | 延長期間 |
|-----------|----------------|----------|
| Cloudflare | $9.77 | +1年 |
| Namecheap | $13.98 | +1年 |
| お名前.com | ¥1,408 | +1年 |

**重要:** 移管料金には通常1年分の更新料が含まれる

---

## トラブルシューティング

### 問題1: Auth Codeが無効

**原因:**
- Auth Code の有効期限切れ（通常30日）
- コピペミス（スペース混入）

**解決策:**
```
1. 元のレジストラで新しいAuth Codeを再発行
2. コピー時に余分なスペースが入らないよう注意
```

### 問題2: 移管がキャンセルされた

**原因:**
- メール承認を5日以内にしなかった
- WHOIS情報のメールアドレスが無効

**解決策:**
```
1. WHOIS情報のメールアドレスを有効なものに変更
2. 移管を再度開始
3. すぐにメール承認する
```

### 問題3: DNSが切り替わらない

**原因:**
- DNSの伝播に時間がかかっている
- ネームサーバーが正しく設定されていない

**解決策:**
```
1. DNS伝播チェックツールで確認
   https://www.whatsmydns.net/

2. ネームサーバー設定を確認
   Cloudflareの場合:
   - ns1.cloudflare.com
   - ns2.cloudflare.com

3. 最大48時間待つ
```

### 問題4: サイトが表示されない

**原因:**
- 移管後にDNSレコードが消えた

**解決策:**
```
1. 新しいレジストラのDNS設定画面を開く

2. 必要なレコードを追加:
   Type: CNAME
   Name: @
   Target: mgg-webservice-production.up.railway.app

3. 5-10分待ってからアクセス
```

---

## チェックリスト

### 移管前
- [ ] ドメイン登録から60日以上経過
- [ ] ドメインロック解除
- [ ] Auth Code 取得
- [ ] WHOIS メールアドレス確認
- [ ] 現在のDNS設定をメモ
- [ ] サブドメインの設定もメモ

### 移管中
- [ ] 移管開始
- [ ] Auth Code 入力
- [ ] 支払い完了
- [ ] メール承認（24時間以内推奨）
- [ ] 元のレジストラで承認（早めたい場合）

### 移管後
- [ ] 新しいレジストラでDNSレコード設定
- [ ] SSL証明書の再発行確認
- [ ] サイトにアクセスできるか確認
- [ ] メール送受信確認（メール使用時）
- [ ] サブドメインの動作確認

---

## Railway + Cloudflare 移管後の設定例

### 移管完了後のDNS設定（Cloudflare）

```
Type: CNAME
Name: @
Target: mgg-webservice-production.up.railway.app
Proxy: ON (🟠)
TTL: Auto

Type: CNAME
Name: www
Target: mgg-webservice-production.up.railway.app
Proxy: ON (🟠)
TTL: Auto

Type: CNAME
Name: signal
Target: mgg-signaling-production.up.railway.app
Proxy: ON (🟠)
TTL: Auto
```

### Railway側の設定

```
1. Railway Dashboard → サービス選択

2. Settings → Domains → Custom Domain

3. ドメイン入力: net8game.com

4. Add Domain

5. SSL証明書が自動発行される（5-10分）
```

---

作成日: 2025-11-10
最終更新: 2025-11-10

**このガイドは保存用です。いつでも参照してください。**
