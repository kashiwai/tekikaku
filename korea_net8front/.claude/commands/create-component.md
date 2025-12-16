# コンポーネント作成

新しいコンポーネントを作成してください。

## 引数
- $ARGUMENTS: コンポーネント名とタイプ（例: "GameCard client"）

## 作成ルール

1. ファイル構成
   - `src/components/<category>/<ComponentName>.tsx`

2. テンプレート（Server Component）
   ```tsx
   interface Props {
     // props
   }

   export function ComponentName({ }: Props) {
     return (
       <div>
         {/* content */}
       </div>
     );
   }
   ```

3. テンプレート（Client Component）
   ```tsx
   "use client";

   import { useState } from "react";

   interface Props {
     // props
   }

   export function ComponentName({ }: Props) {
     return (
       <div>
         {/* content */}
       </div>
     );
   }
   ```

4. スタイリング
   - Tailwind CSSを使用
   - 必要に応じてclass-variance-authorityでvariants定義
