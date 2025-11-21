# X-Frame-Options と CORS 設定修正 - 検証ガイド

## 📋 修正内容サマリー

### 1. X-Frame-Options 対応完了 ✅

**問題**:
- `frame_security.php` ヘルパーは作成済みだったが、`play_v2/index.php` にインクルードされていなかった
- そのため、iFrame埋め込み制御が機能していなかった

**修正**:
```php
// play_v2/index.php に追加
require_once(__DIR__ . '/frame_security.php');
```

**動作**:
- パートナーの `allowed_domains` に基づいて動的にiFrame埋め込みを制御
- 許可されたドメインには `Content-Security-Policy: frame-ancestors` ヘッダーを設定
- 未登録ドメインには `X-Frame-Options: SAMEORIGIN` を設定

---

### 2. CORS 設定強化 ✅

#### api/v1/.htaccess の強化

**追加されたヘッダー**:
```apache
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Length, Content-Type, Authorization, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset
Access-Control-Max-Age: 3600
```

**拡張されたヘッダー許可リスト**:
```apache
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH
```

#### play_v2/.htaccess の新規作成

**ゲーム画面用のCORS設定**:
```apache
# CORS headers - iFrame埋め込み対応
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept, Origin"
Header always set Access-Control-Allow-Credentials "true"
Header always set Access-Control-Max-Age "3600"

# postMessage通信のため、Referrer-Policyを緩和
Header always set Referrer-Policy "no-referrer-when-downgrade"

# Security headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
```

---

### 3. frame_security.php の改善

**getApiKeyIdFromMachine() の実装**:
```php
function getApiKeyIdFromMachine($pdo, $machineNo) {
    // 最新のゲームセッションからAPIキーIDを取得
    $stmt = $pdo->prepare("
        SELECT api_key_id
        FROM game_sessions
        WHERE machine_no = :machine_no
        AND status IN ('playing', 'pending')
        ORDER BY started_at DESC
        LIMIT 1
    ");

    // ... 実装
}
```

---

## 🧪 検証手順

### ステップ1: CORS ヘッダーの確認

#### テスト1: API エンドポイント

```bash
curl -I https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Origin: https://example.com" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization"
```

**期待される結果**:
```
access-control-allow-origin: *
access-control-allow-methods: GET, POST, PUT, DELETE, OPTIONS, PATCH
access-control-allow-headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma
access-control-expose-headers: Content-Length, Content-Type, Authorization, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset
access-control-allow-credentials: true
access-control-max-age: 3600
```

#### テスト2: ゲーム画面

```bash
curl -I https://mgg-webservice-production.up.railway.app/data/play_v2/index.php?NO=9999 \
  -H "Origin: https://example.com"
```

**期待される結果**:
```
access-control-allow-origin: *
access-control-allow-credentials: true
x-content-type-options: nosniff
x-xss-protection: 1; mode=block
referrer-policy: no-referrer-when-downgrade
```

---

### ステップ2: X-Frame-Options の動作確認

#### 準備: 管理画面でドメインを登録

1. https://mgg-webservice-production.up.railway.app/data/xxxadmin/partner_domains.php にアクセス
2. テスト用パートナー（pk_demo_12345）を選択
3. 許可ドメインを追加（例: `https://test-partner.com`）
4. 保存

#### テスト1: 許可されたドメインからのアクセス

```bash
# 許可されたドメインでゲーム開始
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Origin: https://test-partner.com" \
  -d '{"modelId":"HOKUTO4GO","userId":"test_user_001"}' \
  -i
```

**期待される結果**:
- ゲームセッション作成成功
- `sessionId` と `machineNo` が返される

#### テスト2: ゲーム画面のX-Frame-Options確認

```bash
# ゲーム画面にアクセス（sessionIdから取得したmachineNo使用）
curl -I https://mgg-webservice-production.up.railway.app/data/play_v2/index.php?NO=9999 \
  -H "Referer: https://test-partner.com/game"
```

**期待される結果**:
```
# 許可されたドメインの場合
content-security-policy: frame-ancestors https://test-partner.com
access-control-allow-origin: https://test-partner.com
# X-Frame-Options ヘッダーは設定されない（CSPと競合するため）

# 未登録ドメインの場合
x-frame-options: SAMEORIGIN
content-security-policy: frame-ancestors 'self'
```

---

### ステップ3: iFrame 埋め込みテスト

#### テストHTML作成

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>NET8 iFrame埋め込みテスト</title>
</head>
<body>
    <h1>NET8 iFrame埋め込みテスト</h1>

    <!-- SDK経由でゲーム開始 -->
    <div id="game-container" style="width:800px; height:600px;"></div>

    <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
    <script>
        (async function() {
            await Net8.init('pk_demo_12345');

            const game = Net8.createGame({
                model: 'HOKUTO4GO',
                userId: 'test_user_001',
                container: '#game-container'
            });

            await game.start();
            console.log('✅ ゲーム起動成功');
        })();
    </script>
</body>
</html>
```

#### 確認項目

1. **ブラウザのコンソール**:
   - CORSエラーがないこと
   - X-Frame-Options エラーがないこと
   - ゲームが正常に表示されること

2. **ネットワークタブ**:
   - `game_start.php` のレスポンスヘッダーにCORSヘッダーがあること
   - `play_v2/index.php` のレスポンスヘッダーに適切なX-Frame-Optionsがあること

3. **iFrame表示**:
   - ゲーム画面が正常に表示されること
   - postMessage通信が動作すること
   - ゲーム操作が正常に動作すること

---

## 📊 検証チェックリスト

### CORS 検証

- [ ] API v1エンドポイントで全CORSヘッダーが返される
- [ ] `Access-Control-Allow-Credentials: true` が設定されている
- [ ] `Access-Control-Expose-Headers` が設定されている
- [ ] `Access-Control-Max-Age: 3600` が設定されている
- [ ] OPTIONSリクエストが200を返す

### X-Frame-Options 検証

- [ ] `frame_security.php` が `play_v2/index.php` で読み込まれる
- [ ] 管理画面でドメイン登録ができる
- [ ] 許可されたドメインで `Content-Security-Policy: frame-ancestors` が設定される
- [ ] 未登録ドメインで `X-Frame-Options: SAMEORIGIN` が設定される
- [ ] `getApiKeyIdFromMachine()` が正しく動作する

### iFrame 埋め込み検証

- [ ] 許可されたドメインでiFrame埋め込みができる
- [ ] 未登録ドメインでiFrame埋め込みがブロックされる
- [ ] ブラウザコンソールにエラーがない
- [ ] ゲーム画面が正常に表示される
- [ ] postMessage通信が動作する

---

## 🐛 トラブルシューティング

### 問題1: CORSエラーが出る

**症状**:
```
Access to fetch at '...' from origin '...' has been blocked by CORS policy
```

**確認事項**:
1. Railwayのデプロイが完了しているか
2. `.htaccess` ファイルが正しくデプロイされているか
3. Apacheの`mod_headers`が有効になっているか

**解決方法**:
```bash
# Railwayのログを確認
# デプロイが完了してから再テスト
```

### 問題2: X-Frame-Options エラー

**症状**:
```
Refused to display '...' in a frame because it set 'X-Frame-Options' to 'SAMEORIGIN'
```

**確認事項**:
1. `frame_security.php` が読み込まれているか確認
2. 管理画面でドメインが登録されているか確認
3. `game_sessions` テーブルに該当セッションがあるか確認

**解決方法**:
```sql
-- セッションを確認
SELECT * FROM game_sessions WHERE machine_no = 9999 ORDER BY started_at DESC LIMIT 1;

-- APIキーの許可ドメインを確認
SELECT id, partner_name, allowed_domains FROM api_keys WHERE is_active = 1;
```

### 問題3: getApiKeyIdFromMachine() がnullを返す

**症状**: ログに "Frame Security Error" が出力される

**確認事項**:
```sql
-- テスト用APIキーが存在するか確認
SELECT * FROM api_keys WHERE environment IN ('test', 'demo') AND is_active = 1;

-- ゲームセッションが存在するか確認
SELECT * FROM game_sessions WHERE machine_no = 9999 AND status IN ('playing', 'pending');
```

**解決方法**:
- テスト用APIキーを作成
- ゲームを開始してセッションを作成

---

## 📝 検証結果記録テンプレート

```markdown
## 検証日時
2025-11-21 XX:XX

## 検証者
[名前]

## 検証環境
- URL: https://mgg-webservice-production.up.railway.app
- ブラウザ: Chrome 120.0
- APIキー: pk_demo_12345

## 検証結果

### CORS ヘッダー
- [ ] Access-Control-Allow-Origin: ✅/❌
- [ ] Access-Control-Allow-Credentials: ✅/❌
- [ ] Access-Control-Expose-Headers: ✅/❌
- [ ] Access-Control-Max-Age: ✅/❌

### X-Frame-Options
- [ ] 許可ドメインで埋め込み: ✅/❌
- [ ] 未登録ドメインでブロック: ✅/❌
- [ ] CSP frame-ancestors: ✅/❌

### iFrame 埋め込み
- [ ] ゲーム表示: ✅/❌
- [ ] postMessage通信: ✅/❌
- [ ] エラーなし: ✅/❌

## 備考
[問題点や気づいた点]
```

---

**X-Frame-Options と CORS 設定修正 - 検証ガイド**
最終更新: 2025-11-21
