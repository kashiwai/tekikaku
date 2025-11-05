# Windows側 シグナリングサーバー接続問題 診断レポート

## 🔍 問題の特定

### 現在の状況
Windows側のカメラ配信システムがRailway上のPeerJSシグナリングサーバーに接続できない状態です。

## 📊 シグナリングサーバー設定

### Railway本番環境のシグナリングサーバー
```
ホスト: mgg-signaling-production-c1bd.up.railway.app
ポート: 443 (HTTPS)
プロトコル: wss:// (WebSocket Secure)
```

### 接続URL
```
wss://mgg-signaling-production-c1bd.up.railway.app:443/peerjs
```

## ❌ 発見した問題点

### 1. ポート番号の不一致（最重要）
**クライアント側コード** (`view_auth.js` 64行):
```javascript
var peersetting = {
    host: sigHost,
    port: 9000,  // ← ローカル開発環境用のポート！
    key: peerjskey,
    token: authID,
    config: {
        'iceServers': iceServers,
        "iceTransportPolicy":"all",
        "iceCandidatePoolSize":"0"
    },
    debug: 0
};
```

**サーバー設定** (`setting_base.php` 316-322行):
```php
$signaling_host = 'mgg-signaling-production-c1bd.up.railway.app';
$signaling_port = '443';  // ← Railway本番環境用！
```

**問題**: クライアントは9000番ポートに接続しようとしているが、Railwayサーバーは443番ポートでリッスンしている。

### 2. IPホワイトリスト制限
`_api/sig/.htaccess` でIP制限がかかっている:
```apache
order deny,allow
deny from all
allow from xxx.xxx.xxx.xxx  # 許可されたIPのみ
```

Windows側のグローバルIPアドレスが許可リストに含まれていない可能性があります。

### 3. HTTPS/WSS プロトコル要件
Railway環境ではHTTPSが強制されるため、WebSocket接続も `wss://` を使用する必要があります。

## 🔧 Windows側で確認すべき項目

### A. PeerJSクライアント設定の確認

Windows側のカメラ配信スクリプトで以下を確認してください：

```javascript
// 正しい設定（Railway本番環境用）
var peer = new Peer({
    host: 'mgg-signaling-production-c1bd.up.railway.app',
    port: 443,
    path: '/peerjs',
    secure: true,  // HTTPS/WSSを使用
    config: {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    },
    debug: 2  // デバッグログを有効化
});
```

### B. 接続テスト方法

#### 1. シグナリングサーバーの疎通確認
```bash
# PowerShell または CMD で実行
curl -I https://mgg-signaling-production-c1bd.up.railway.app/peerjs
```

**期待される応答**:
```
HTTP/2 200
```

#### 2. WebSocket接続テスト（ブラウザのDevToolsで確認）
```javascript
// ブラウザのコンソールで実行
const ws = new WebSocket('wss://mgg-signaling-production-c1bd.up.railway.app:443/peerjs');
ws.onopen = () => console.log('✅ WebSocket接続成功');
ws.onerror = (e) => console.error('❌ WebSocket接続失敗', e);
```

#### 3. PeerJS接続テスト
```javascript
const testPeer = new Peer({
    host: 'mgg-signaling-production-c1bd.up.railway.app',
    port: 443,
    path: '/peerjs',
    secure: true,
    debug: 3  // 最大デバッグレベル
});

testPeer.on('open', (id) => {
    console.log('✅ PeerJS接続成功 ID:', id);
});

testPeer.on('error', (err) => {
    console.error('❌ PeerJS接続エラー:', err);
});
```

### C. ファイアウォール設定の確認

Windows側で以下のポートが許可されているか確認：

1. **アウトバウンド接続**:
   - ポート 443 (HTTPS/WSS)
   - ポート 19302 (STUN - Google)

2. **ファイアウォール許可コマンド** (管理者権限で実行):
```powershell
# HTTPS/WSS アウトバウンド許可
New-NetFirewallRule -DisplayName "PeerJS HTTPS Out" -Direction Outbound -Protocol TCP -RemotePort 443 -Action Allow

# STUN アウトバウンド許可
New-NetFirewallRule -DisplayName "STUN Out" -Direction Outbound -Protocol UDP -RemotePort 19302 -Action Allow
```

### D. グローバルIPアドレスの確認

Windows PCのグローバルIPアドレスを確認してください：
```bash
curl https://api.ipify.org
```

このIPアドレスを記録して、必要に応じてホワイトリストに追加します。

## 🚀 推奨される解決策

### 即座に試すべき対策（優先順）

#### 1. ポート番号の修正（最優先）
Windows側のPeerJS初期化コードで `port: 443` に変更する。

#### 2. secureオプションの有効化
Railway環境ではHTTPSが必須なので `secure: true` を設定する。

#### 3. デバッグレベルの引き上げ
`debug: 3` に設定して、詳細なエラーログを確認する。

#### 4. ネットワーク接続の確認
```bash
# シグナリングサーバーへの疎通テスト
ping mgg-signaling-production-c1bd.up.railway.app

# HTTPS接続テスト
curl -v https://mgg-signaling-production-c1bd.up.railway.app/peerjs
```

## 📝 エラーログの確認ポイント

Windows側で以下のエラーが出ていないか確認してください：

### よくあるエラーメッセージ

| エラーメッセージ | 原因 | 対策 |
|-----------------|------|------|
| `Could not connect to peer` | シグナリングサーバーに接続できない | ポート・ホスト設定を確認 |
| `WebSocket connection failed` | WSS接続の失敗 | `secure: true` を設定 |
| `SSL certificate problem` | SSL証明書エラー | 証明書の検証を一時的に無効化（開発環境のみ） |
| `Connection timeout` | ファイアウォールまたはネットワークブロック | ファイアウォール設定を確認 |
| `401 Unauthorized` | 認証エラー | authID/tokenを確認 |

## 🔐 セキュリティ対策（IP制限の解除）

もしIPホワイトリストが原因の場合、一時的に制限を解除する必要があります。

### Railway Web側での対応
`_api/sig/.htaccess` を以下のように修正：

```apache
# 開発中は全IP許可（本番環境では要注意！）
order allow,deny
allow from all
```

**⚠️ 注意**: 本番環境では必ずIPホワイトリストを再設定してください。

## 📞 次のステップ

### 1. Windows側で実行すべきコマンド
```bash
# 接続テスト
curl -I https://mgg-signaling-production-c1bd.up.railway.app/peerjs

# グローバルIP確認
curl https://api.ipify.org

# ファイアウォール状態確認
netsh advfirewall show allprofiles
```

### 2. 収集すべき情報
- [ ] Windows PCのグローバルIPアドレス
- [ ] PeerJSクライアントのエラーログ（debug: 3で取得）
- [ ] ブラウザDevToolsのネットワークタブのエラー
- [ ] ファイアウォールログ

### 3. 報告すべき内容
Windows側のClaude Codeから以下の情報をMac側に共有してください：

```
✅ 実行結果:
1. curl https://mgg-signaling-production-c1bd.up.railway.app/peerjs
   → [結果を貼り付け]

2. グローバルIP: [IPアドレス]

3. PeerJSエラーログ:
   → [エラーメッセージを貼り付け]

4. 現在のPeerJS設定:
   → [設定コードを貼り付け]
```

## 🎯 期待される動作

接続が成功すると、以下のログが出力されるはずです：

```javascript
// PeerJS接続成功ログ
PeerJS: Created peer with ID: PEER001
PeerJS: Signaling server connected
PeerJS: ICE connection state: connected
PeerJS: Data channel state: open
✅ カメラ接続確立
```

---

## 📚 参考情報

### PeerJS公式ドキュメント
- https://peerjs.com/docs/

### Railway環境の特性
- デフォルトでHTTPS強制
- ポート443でWebSocketを提供
- 独自ドメイン: `*.up.railway.app`

### WebRTC/PeerJS デバッグツール
- Chrome DevTools → Network → WS（WebSocket接続確認）
- about:webrtc （Firefox）
- chrome://webrtc-internals/ （Chrome）

---

**作成日時**: 2025-11-01
**対象環境**: Railway Production
**ステータス**: 調査中
