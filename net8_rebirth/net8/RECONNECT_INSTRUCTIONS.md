# Windows側 - PeerJS自動再接続の設定

現在、ngrok経由のWebSocket接続がタイムアウトで切断されています。
自動再接続機能を追加するための簡易的な方法を提示します。

## 現在の問題

```
PeerJS: Socket open
→ しばらく時間が経つと...
PeerJS: Socket closed.
PeerJS: ERROR Error: Lost connection to server.
```

## 解決策：ブラウザConsoleから手動で再接続

接続が切れた時、Windows側のF12コンソールで以下を実行してください：

```javascript
// PeerJSの再接続を試みる
if (_peer && !_peer.destroyed) {
    console.log('🔄 Reconnecting...');
    _peer.reconnect();
} else {
    console.log('❌ Peer is destroyed, reload page');
    location.reload();
}
```

## 自動再接続を有効にする（一時的）

Windows側のF12コンソールで以下を実行すると、自動再接続が有効になります：

```javascript
// 自動再接続ハンドラーを追加
if (_peer) {
    _peer.on('disconnected', function() {
        console.log('🔄 Disconnected! Attempting to reconnect in 2 seconds...');
        setTimeout(function() {
            if (_peer && !_peer.destroyed) {
                try {
                    _peer.reconnect();
                    console.log('✅ Reconnect attempted');
                } catch(err) {
                    console.error('❌ Reconnect failed:', err);
                    // 再試行
                    setTimeout(function() {
                        console.log('🔄 Retrying reconnect...');
                        try {
                            _peer.reconnect();
                        } catch(err2) {
                            console.error('❌ Reconnect retry failed, please reload page');
                        }
                    }, 5000);
                }
            }
        }, 2000);
    });
    console.log('✅ Auto-reconnect handler added');
}
```

## 接続状態の監視

接続状態を確認するには：

```javascript
// 現在のPeerJS接続状態を表示
console.log('Peer ID:', _peer ? _peer.id : 'not set');
console.log('Destroyed:', _peer ? _peer.destroyed : 'N/A');
console.log('Disconnected:', _peer ? _peer.disconnected : 'N/A');
```

## ngrokの制限について

ngrokの無料プランでは：
- WebSocket接続に制限がある
- 長時間の接続を維持できない場合がある
- 定期的に切断される可能性がある

### 対策オプション

1. **定期的にページをリロード**（最もシンプル）
   - 5-10分ごとにページをリロード

2. **ローカルネットワークでテスト**（開発時）
   - MacのローカルIP: 192.168.1.4
   - Windows側: http://192.168.1.4:8080/server_v2/?MAC=34-a6-ef-35-73-73
   - ※ただしPeerJSサーバーの設定変更が必要

3. **ngrok有料プランを使用**（本番環境）
   - より長いタイムアウト
   - より安定した接続

## 現在の推奨対応

1. **Windows側で上記の自動再接続コードを実行**（F12コンソール）
2. **接続が切れたら、再度同じコードを実行**
3. **Mac側でtest_simple.htmlを開き、カメラIDを入力して接続テスト**

## Mac側のテスト

Mac側で以下のURLを開く：
```
https://aicrypto.ngrok.dev/play_v2/test_simple.html
```

Windows側のF12コンソールで現在のカメラIDを確認：
```javascript
console.log('Camera ID:', cameraid);
```

そのカメラIDをMac側のtest_simple.htmlに入力して「Start Connection」をクリック。

---

## まとめ

現在の状況：
- ✅ PeerJSサーバー：正常動作
- ✅ 認証：バイパス成功
- ✅ Windows側：カメラストリーム取得成功
- ❌ 問題：ngrok経由のWebSocket接続が切断される

対策：
- 一時的：F12コンソールで再接続コードを実行
- 恒久的：cameraServer_slotv2.jsに再接続ロジックを追加（コード変更必要）
