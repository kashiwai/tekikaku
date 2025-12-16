# ビルドチェック

プロジェクトのビルド状態を確認してください。

## 確認項目

1. TypeScript型チェック
   ```bash
   npx tsc --noEmit
   ```

2. ESLint
   ```bash
   npm run lint
   ```

3. ビルド
   ```bash
   npm run build
   ```

## エラーがある場合

- エラー内容を分析
- 修正提案を提示
- 可能であれば自動修正
