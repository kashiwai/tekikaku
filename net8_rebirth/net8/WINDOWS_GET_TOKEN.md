# Windows側 - PeerJS Token取得手順

PeerJS認証エラーを解決するために、現在使用されているtokenを確認します。

## 手順

### 1. Chromeでページを開く

既に開いている場合はそのまま使用：
```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

### 2. F12開発者ツールを開く

**F12キー**を押して開発者ツールを開きます。

### 3. Consoleタブでtokenを確認

Consoleタブで以下のコマンドを実行してtokenを取得：

```javascript
console.log('authID:', authID);
authID
```

### 4. 出力されたtokenをコピー

以下のような形式で表示されます：
```
27719a917ea9decf9dcb80a3f213e4bfd2cadef451411dd405f0e6b9411b5870
```

この40桁または64桁の英数字をコピーしてください。

### 5. Mac側に報告

コピーしたtoken値を以下の形式でMac側に報告してください：

```
【Token値】
27719a917ea9decf9dcb80a3f213e4bfd2cadef451411dd405f0e6b9411b5870
```

## 注意事項

- tokenは毎回異なる値が生成されます
- 現在ブラウザで使用されているtokenを確認してください
- ページをリロードするとtokenが変わるので、リロード前に取得してください

## Mac側での作業

Token値を受け取ったら、Mac側でPeerJSサーバーのSQLite3データベースに登録します。
