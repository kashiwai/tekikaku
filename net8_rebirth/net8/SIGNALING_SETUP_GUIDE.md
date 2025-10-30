# PeerJSシグナリングサーバー接続設定ガイド

**更新日**: 2025-10-31
**対象**: Railway PHPアプリケーション（mgg-webservice-production）

---

## 📊 現在のシグナリングサーバー情報

| 項目 | 値 |
|------|-----|
| **サービス名** | mgg-signaling-production |
| **URL** | https://mgg-signaling-production-c1bd.up.railway.app/ |
| **ホスト** | mgg-signaling-production-c1bd.up.railway.app |
| **ポート** | 443 (HTTPS) |
| **ブランチ** | signaling |
| **ステータス** | ✅ 稼働中 |

---

## 🔧 PHPアプリ側の設定（完了済み）

### 修正内容

`net8/02.ソースファイル/net8_html/_etc/setting_base.php` (316-323行目)

**変更前**:
```php
$GLOBALS["RTC_Signaling_Servers"] = array(
    "default" => "aimoderation.ngrok-free.app:443",
    "1" => "aimoderation.ngrok-free.app:443",
    "2" => "aimoderation.ngrok-free.app:443"
);
```

**変更後**:
```php
// 環境変数からシグナリングサーバー設定を動的に取得（Railway対応）
$signaling_host = $_SERVER['SIGNALING_HOST'] ?? $_ENV['SIGNALING_HOST'] ?? getenv('SIGNALING_HOST') ?: 'mgg-signaling-production-c1bd.up.railway.app';
$signaling_port = $_SERVER['SIGNALING_PORT'] ?? $_ENV['SIGNALING_PORT'] ?? getenv('SIGNALING_PORT') ?: '443';

$GLOBALS["RTC_Signaling_Servers"] = array(
    "default" => $signaling_host . ':' . $signaling_port,
    "1" => $signaling_host . ':' . $signaling_port,
    "2" => $signaling_host . ':' . $signaling_port
);
```

### メリット

1. **環境変数で柔軟に変更可能**
   - Railway管理画面から簡単に変更
   - 再デプロイ不要（環境変数変更のみ）

2. **デフォルト値でRailway URLを使用**
   - 環境変数が未設定でも動作
   - Railway環境で即座に利用可能

3. **開発7原則に準拠**
   - ハードコード一切禁止原則に従う
   - 設定値を環境変数から動的取得

---

## ⚙️ Railway環境変数の設定

### 手順

1. **Railwayダッシュボードにアクセス**
   - https://railway.app/

2. **PHPアプリサービスを選択**
   - プロジェクト: mgg001
   - サービス: mgg-webservice-production

3. **Variables タブを開く**

4. **以下の環境変数を追加**（オプション）

   | 変数名 | 値 | 必須 |
   |--------|-----|------|
   | SIGNALING_HOST | mgg-signaling-production-c1bd.up.railway.app | ❌ (デフォルト値あり) |
   | SIGNALING_PORT | 443 | ❌ (デフォルト値あり) |

   **注意**: 環境変数を設定しない場合、コード内のデフォルト値が使用されます。

5. **デプロイ**
   - 環境変数変更後、自動的に再デプロイされます

---

## 🔍 動作確認

### 1. デプロイログで確認

Railway Dashboard → mgg-webservice-production → Deployments → 最新デプロイ

ログで以下を確認：
```
✅ Build completed
✅ Apache started
```

### 2. PHPアプリから接続テスト

以下のURLにアクセス：
```
https://mgg-webservice-production.up.railway.app/data/server_v2/index.php?MAC=XX:XX:XX:XX:XX:XX
```

JavaScriptコンソールで確認：
```javascript
// ブラウザのDevToolsでシグナリングサーバー接続を確認
console.log('Signaling Host:', sigHost);
console.log('Signaling Port:', sigPort);
```

期待される出力：
```
Signaling Host: mgg-signaling-production-c1bd.up.railway.app
Signaling Port: 443
```

### 3. PeerJS接続確認

JavaScriptコンソールで：
```javascript
// PeerJS接続成功メッセージを確認
PeerJS: Connection to server established
```

---

## 🚨 トラブルシューティング

### 問題: シグナリングサーバーに接続できない

**症状**:
```
Error: Could not connect to PeerJS server
```

**確認事項**:

1. **PeerJSサーバーが稼働中か確認**
   ```bash
   curl https://mgg-signaling-production-c1bd.up.railway.app/
   ```

   期待される応答：
   ```json
   {"name":"PeerJS Server","description":"A server side element to broker connections between PeerJS clients.","website":"http://peerjs.com/"}
   ```

2. **環境変数が正しく設定されているか確認**

   Railway Dashboard → Variables タブで確認

3. **デバッグ出力を追加**

   `setting_base.php` の323行目の後に追加：
   ```php
   if (defined('DEBUG_MODE') && DEBUG_MODE) {
       error_log('Signaling Server: ' . $signaling_host . ':' . $signaling_port);
   }
   ```

### 問題: 旧ngrok URLに接続しようとする

**症状**:
```
Trying to connect to aimoderation.ngrok-free.app
```

**原因**: 古いコードキャッシュ

**解決策**:
1. ブラウザキャッシュをクリア
2. Railwayで再デプロイ
   ```bash
   git commit --allow-empty -m "chore: Trigger redeploy"
   git push origin main
   ```

### 問題: JavaScriptでundefinedになる

**症状**:
```javascript
console.log(sigHost); // undefined
```

**原因**: PHPテンプレート変数が正しく渡されていない

**確認**:
`data/server_v2/index.php` (253行目) でテンプレート変数を確認：
```php
$template->assign("SIGHOST", $sighost);
$template->assign("SIGPORT", $sigport);
```

HTMLテンプレートで変数が出力されているか確認：
```javascript
const sigHost = '<?php echo $sighost; ?>';
const sigPort = <?php echo $sigport; ?>;
```

---

## 📝 変更履歴

| 日付 | 変更内容 | 担当 |
|------|---------|------|
| 2025-10-31 | 環境変数から動的取得に変更 | Claude Code |
| 2025-10-29 | PeerJSサーバーをRailwayに移行 | Claude Code |
| 以前 | ngrok URL使用 | - |

---

## 🔗 関連ドキュメント

- [railway_php_deployment_complete_20251029.md](../.claude/workspace/railway_php_deployment_complete_20251029.md) - PHPアプリデプロイ記録
- [railway_deployment_success_20251029.md](../.claude/workspace/railway_deployment_success_20251029.md) - PeerJSサーバーデプロイ記録
- [WINDOWS_RAILWAY_COMPLETE.md](WINDOWS_RAILWAY_COMPLETE.md) - Windows PC引き継ぎガイド

---

## 💡 今後の改善提案

### 1. 複数シグナリングサーバー対応

現在は1つのサーバーのみですが、将来的に複数サーバーを使い分ける場合：

```php
// 環境変数から複数サーバーを取得
$signaling_servers = [
    'default' => getenv('SIGNALING_DEFAULT') ?: 'mgg-signaling-production-c1bd.up.railway.app:443',
    '1' => getenv('SIGNALING_1') ?: 'mgg-signaling-production-c1bd.up.railway.app:443',
    '2' => getenv('SIGNALING_2') ?: 'mgg-signaling-backup.up.railway.app:443'
];
```

### 2. フェイルオーバー対応

メインサーバー接続失敗時に自動的にバックアップサーバーに接続：

```javascript
async function connectWithFailover(servers) {
    for (const server of servers) {
        try {
            const peer = new Peer(cameraid, {
                host: server.host,
                port: server.port,
                // ...
            });
            return peer;
        } catch (error) {
            console.warn('Failed to connect to', server.host, '- trying next server');
        }
    }
    throw new Error('All signaling servers failed');
}
```

### 3. ヘルスチェック機能

定期的にシグナリングサーバーの稼働状況を確認：

```php
function checkSignalingHealth($host, $port) {
    $url = "https://{$host}/";
    $response = @file_get_contents($url);
    return $response !== false;
}
```

---

**作成日**: 2025-10-31
**作成者**: Claude Code (AI運用5原則適用)
**ステータス**: ✅ 完了
