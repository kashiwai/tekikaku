# NET8 SDK接続テスト

NET8 SDKの接続テストを実行してください。

## テスト手順

1. 環境変数の確認
   - `NET8_API_KEY` が設定されているか確認
   - `NET8_API_BASE_URL` が正しいか確認

2. 接続テスト
   - game_start APIへのテストリクエストを作成
   - レスポンスの検証

3. 結果レポート
   - 成功/失敗の判定
   - エラーがある場合は原因と対処法を提示

## 期待する出力

```json
{
  "success": true,
  "environment": "test",
  "sessionId": "gs_xxx..."
}
```
