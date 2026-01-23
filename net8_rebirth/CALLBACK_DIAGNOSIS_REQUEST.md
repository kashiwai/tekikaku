# リアルタイムCallback診断依頼

**送信先**: Korean Noa様
**優先度**: 🔴 緊急
**日時**: 2026-01-23

---

## 🔍 問題の診断が必要です

game.ended callbackのみ受信されており、リアルタイムcallback（game.bet、game.win）が受信されていない問題について、診断を実施しました。

**診断結果**:
- ✅ コードは正しく実装されている
- ✅ Korean側のフロントエンドは正しくtotalBets/totalWinsを抽出している
- ❓ **ブラウザでcallback関数が実行されているか不明**

---

## 📋 至急確認していただきたいこと

### 1. ブラウザのコンソールログ確認 🔴 最優先

ゲームプレイ中に、ブラウザの開発者ツール（F12）を開いて、コンソールタブを確認してください。

**期待されるログ**:
```javascript
💰 [Korea] Syncing playpoint to camera: 53090
💰 [Korea] Korea mode enabled for AUTO
🎲 Bet callback sent: { success: true, sessionId: '...', betAmount: 100, ... }
🎉 Win callback sent: { success: true, sessionId: '...', winAmount: 200, ... }
💱 Point converted callback sent: { success: true, ... }
```

**もし以下のログが表示されている場合**:
```javascript
❌ Bet callback failed: TypeError: ...
❌ Win callback failed: NetworkError: ...
```
→ エラーメッセージの全文をお送りください

**もしログが何も表示されない場合**:
→ callback関数が呼び出されていません（原因調査が必要）

---

### 2. Network タブ確認

ブラウザの開発者ツールで、**Network**タブを開いて、以下のリクエストが送信されているか確認してください：

**期待されるリクエスト**:
```
POST /api/v1/game_bet.php
POST /api/v1/game_win.php
POST /api/v1/game_point_converted.php
```

**確認項目**:
- これらのリクエストが表示されますか？
  - → YES: レスポンスのステータスコードは何ですか？（200, 404, 500など）
  - → NO: リクエストが送信されていません（原因調査が必要）

---

### 3. iframe内のJavaScript変数確認

ブラウザのコンソールで、以下のコマンドを実行してください：

```javascript
// iframe要素を取得
const iframe = document.querySelector('iframe'); // または適切なセレクタ

// iframe内のwindowにアクセス
const iframeWindow = iframe.contentWindow;

// 変数確認
console.log('koreaMode:', iframeWindow.koreaMode);
console.log('sessionId:', iframeWindow.sessionId);
console.log('game.totalBets:', iframeWindow.game?.totalBets);
console.log('game.totalWins:', iframeWindow.game?.totalWins);
```

**期待される結果**:
```javascript
koreaMode: true
sessionId: "gs_xxx..."
game.totalBets: 500   // または実際のベット額
game.totalWins: 800   // または実際の勝利額
```

**もし以下のような結果の場合**:
```javascript
koreaMode: false  // ❌ 問題: koreatModeがfalse
sessionId: undefined  // ❌ 問題: sessionIdが未定義
game.totalBets: 0  // ❌ 問題: callback関数が呼び出されていない
```
→ 問題の原因を特定できます

---

### 4. view_auth_pachi.js の内容確認（最終手段）

ブラウザのデバッガーで、以下のファイルを開いて、**行131-196**を確認してください：

```
/data/play_v2/js/view_auth_pachi.js
```

**確認項目**:
以下のコードが存在しますか？

```javascript
// 行131-152付近
function sendBetCallback(betAmount, creditBefore, creditAfter) {
    if (typeof sessionId === 'undefined' || !sessionId || !koreaMode) return;

    game.totalBets = (game.totalBets || 0) + betAmount;

    fetch('/api/v1/game_bet.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sessionId: sessionId,
            betAmount: betAmount,
            creditBefore: creditBefore,
            creditAfter: creditAfter
        })
    }).then(function(res) {
        return res.json();
    }).then(function(data) {
        console.log('🎲 Bet callback sent:', data);
    }).catch(function(err) {
        console.error('❌ Bet callback failed:', err);
    });
}
```

**もし存在しない場合**:
→ Railwayにデプロイされていません（再デプロイが必要）

**もし存在する場合**:
→ コードは正しくデプロイされています（別の原因を調査）

---

## 🎯 診断フロー

```
[ゲーム開始]
    ↓
[コンソールに "💰 [Korea] Korea mode enabled" が表示される？]
    YES → koreaMode = true ✅
    NO  → koreaMode = false ❌ 問題①
    ↓
[ベット時にコンソールに "🎲 Bet callback sent" が表示される？]
    YES → callback関数が実行されている ✅
    NO  → callback関数が実行されていない ❌ 問題②
    ↓
[Network タブに "POST /api/v1/game_bet.php" が表示される？]
    YES → リクエスト送信成功 ✅
    NO  → リクエスト送信失敗 ❌ 問題③
    ↓
[レスポンスステータスは 200 OK？]
    YES → API正常動作 ✅
    NO  → API エラー（404, 500など） ❌ 問題④
```

---

## 📸 スクリーンショット送付のお願い

以下のスクリーンショットを送っていただけますと、迅速に問題を解決できます：

1. **ブラウザのコンソールタブ**（ゲームプレイ中のすべてのログ）
2. **ブラウザのNetworkタブ**（/api/v1/ で絞り込み）
3. **iframe内の変数確認結果**（上記コマンド実行後）

---

## 🚨 暫定回避策（テスト用）

診断中、以下の方法でtotalBets/totalWinsの値を確認できます：

### ブラウザのコンソールで手動実行

```javascript
// iframe取得
const iframe = document.querySelector('iframe');
const iframeWindow = iframe.contentWindow;

// 手動でtotalBets/totalWinsを設定（テスト用）
iframeWindow.game.totalBets = 500;
iframeWindow.game.totalWins = 800;

// 確認
console.log('Set totalBets:', iframeWindow.game.totalBets);
console.log('Set totalWins:', iframeWindow.game.totalWins);

// ゲーム終了時にこれらの値がpostMessageに含まれるか確認
```

---

## 🔧 想定される原因と対処法

### 原因① koreaMode が false のまま

**症状**: コンソールに "💰 [Korea] Korea mode enabled" が表示されない

**原因**: game.playpoint が0、または接続が確立されていない

**対処法**:
- game_start APIのレスポンスに initial_balance が含まれているか確認
- play_embed の初期化処理を確認

### 原因② sessionId が undefined

**症状**: コンソールにエラーログがない、callback関数が呼び出されていない

**原因**: play_embed/index.php でsessionIdが設定されていない

**対処法**:
- play_embed のURLパラメータに session_id が含まれているか確認
- ブラウザのコンソールで `iframe.contentWindow.sessionId` を確認

### 原因③ CORS エラー

**症状**: コンソールに "CORS policy" エラーが表示される

**原因**: APIエンドポイントのCORS設定が不足

**対処法**:
- .htaccess のCORS設定を確認（すでに設定済みのはず）

### 原因④ APIエンドポイントが404

**症状**: Network タブで404エラーが表示される

**原因**: Railwayにデプロイされていない、またはファイルパスが間違っている

**対処法**:
- Railway デプロイログを確認
- 直接 https://mgg-webservice-production.up.railway.app/api/v1/game_bet.php にアクセスして確認

---

## 📞 次のステップ

上記の診断結果を共有していただければ、即座に問題を解決できます。

**共有していただきたい情報**:
1. ブラウザのコンソールログ（全文）
2. ブラウザのNetworkタブのスクリーンショット
3. iframe内の変数値（koreaMode, sessionId, game.totalBets, game.totalWins）
4. view_auth_pachi.js の確認結果（行131-196が存在するか）

よろしくお願いいたします。

---

**診断作成者**: Claude Sonnet 4.5
**作成日時**: 2026-01-23
**ドキュメントバージョン**: 1.0
