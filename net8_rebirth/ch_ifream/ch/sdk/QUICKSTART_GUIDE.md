# NET8 Gaming SDK クイックスタートガイド

## 🚀 5分で始めるNET8ゲーム統合

このガイドでは、あなたのウェブサイトにNET8のパチンコ・スロットゲームを最速で統合する方法を説明します。

---

## ステップ1: APIキーを取得する（2分）

1. NET8管理画面にアクセス: https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php
2. 「新しいAPIキーを発行」ボタンをクリック
3. パートナー名とメールアドレスを入力
4. 環境を選択:
   - **テスト**: 開発・テスト用（無料）
   - **本番**: 実際のサービス提供用
5. APIキーをコピーして保存

**テスト用キー**: デモとして `pk_demo_12345` をご利用いただけます

---

## ステップ2: HTMLにSDKを追加する（1分）

あなたのHTMLファイルに以下のコードを追加してください：

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>NET8 ゲーム</title>
    <style>
        #game-container {
            width: 100%;
            max-width: 800px;
            height: 600px;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <!-- ゲーム表示エリア -->
    <div id="game-container"></div>

    <!-- NET8 SDK読み込み -->
    <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
</body>
</html>
```

---

## ステップ3: ゲームを起動する（2分）

HTMLに以下のJavaScriptコードを追加してください：

```html
<script>
(async function() {
    // 1. SDK初期化（APIキーを設定）
    await Net8.init('pk_demo_12345');  // ← あなたのAPIキーに置き換え

    // 2. ゲーム作成
    const game = Net8.createGame({
        model: 'HOKUTO4GO',           // ゲーム機種ID
        userId: 'user_12345',          // あなたのシステムのユーザーID
        container: '#game-container'   // 表示エリア
    });

    // 3. ゲーム終了時の処理
    game.on('end', (result) => {
        alert(`ゲーム終了！\n獲得ポイント: ${result.pointsWon}\n新残高: ${result.newBalance}`);
    });

    // 4. ゲーム開始
    await game.start();
})();
</script>
```

**完成！** ブラウザでHTMLを開くと、ゲームが起動します。

---

## 完全なコード例

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>NET8 ゲーム</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a2e;
            color: white;
            padding: 20px;
            text-align: center;
        }
        #game-container {
            width: 100%;
            max-width: 800px;
            height: 600px;
            margin: 20px auto;
            border: 2px solid #667eea;
            border-radius: 10px;
        }
        button {
            padding: 15px 30px;
            font-size: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin: 10px;
        }
    </style>
</head>
<body>
    <h1>🎮 NET8 ゲーム</h1>

    <div>
        <button onclick="startGame('HOKUTO4GO')">北斗の拳 起動</button>
        <button onclick="startGame('ZENIGATA01')">銭形 起動</button>
    </div>

    <div id="game-container"></div>

    <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
    <script>
        let currentGame = null;

        // SDK初期化
        Net8.init('pk_demo_12345').then(() => {
            console.log('SDK準備完了！');
        });

        async function startGame(modelId) {
            // 既存のゲームを停止
            if (currentGame) {
                await currentGame.stop();
            }

            // 新しいゲーム作成
            currentGame = Net8.createGame({
                model: modelId,
                userId: 'demo_user_001',
                container: '#game-container'
            });

            // イベントリスナー
            currentGame.on('started', (data) => {
                console.log('ゲーム開始！消費:', data.pointsConsumed + 'P');
            });

            currentGame.on('end', (result) => {
                const profit = result.netProfit >= 0 ? '+' + result.netProfit : result.netProfit;
                alert(
                    `ゲーム終了！\n\n` +
                    `獲得: ${result.pointsWon}P\n` +
                    `純利益: ${profit}P\n` +
                    `新残高: ${result.newBalance}P`
                );
            });

            currentGame.on('error', (error) => {
                alert('エラー: ' + error.message);
            });

            // ゲーム開始
            await currentGame.start();
        }
    </script>
</body>
</html>
```

---

## 利用可能なゲーム機種

| 機種ID | 機種名 | カテゴリ |
|--------|--------|---------|
| `HOKUTO4GO` | 北斗の拳 | スロット |
| `ZENIGATA01` | 主役は銭形 | パチンコ |
| `MILLIONGOD01` | ミリオンゴッド | スロット |
| `HANEMONO01` | CR羽根物 | パチンコ |
| `EVANGELION01` | エヴァンゲリオン | パチンコ |

全機種一覧を取得:
```javascript
const models = await Net8.getModels();
console.log(models);
```

---

## よくある質問

### Q1: ユーザーIDとは何ですか？

**A**: あなたのシステムで管理しているユーザーの識別子です。

- 例: データベースのユーザーID、会員番号、メールアドレスなど
- NET8側で自動的にポイント管理・プレイ履歴を記録します
- 初回プレイ時に自動でユーザー登録され、10,000ポイントが付与されます

### Q2: ポイントはどのように管理されますか？

**A**: SDK側で自動管理されます。

- ゲーム開始時: 自動的に100ポイント消費
- ゲーム終了時: 獲得ポイントを自動払い出し
- 残高はリアルタイムで更新されます

### Q3: ゲーム終了をどうやって検知しますか？

**A**: `end` イベントをリッスンしてください。

```javascript
game.on('end', (result) => {
    console.log('ゲーム終了！');
    console.log('獲得:', result.pointsWon);
    console.log('新残高:', result.newBalance);
});
```

### Q4: エラーが発生したらどうすればいいですか？

**A**: `error` イベントでエラーハンドリングしてください。

```javascript
game.on('error', (error) => {
    console.error('エラー:', error.message);

    // ポイント不足の場合
    if (error.message.includes('INSUFFICIENT_BALANCE')) {
        alert('ポイントが不足しています');
    }
});
```

### Q5: テスト環境と本番環境の違いは？

**A**: APIキーのプレフィックスで自動判定されます。

| 環境 | APIキー | 動作 |
|-----|---------|------|
| テスト | `pk_demo_*` | モック環境（開発用） |
| ステージング | `pk_staging_*` | 検証環境 |
| 本番 | `pk_live_*` | 実機接続 |

---

## 次のステップ

✅ **基本統合が完了しました！**

さらに高度な機能を使いたい場合：

1. **[完全なAPIリファレンス](./README_v1.1.0.md)** - 全機能の詳細
2. **[トラブルシューティングガイド](./TROUBLESHOOTING.md)** - よくある問題と解決方法
3. **[API仕様書](./API_SPECIFICATION.md)** - エンドポイント詳細
4. **[デモページ](https://mgg-webservice-production.up.railway.app/sdk/demo.html)** - 動作確認

---

## サポート

技術的なご質問やバグ報告は、NET8サポートチームまでお問い合わせください。

**NET8 Gaming SDK v1.1.0**
最終更新: 2025-11-21
