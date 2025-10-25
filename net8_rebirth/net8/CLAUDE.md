# プロジェクト開発ルール（AI運用5原則統合）

## 🛡️ 絶対ルール（AI運用5原則）
1. **事前確認必須**: ファイル変更前に必ずy/n確認
2. **迂回禁止**: 失敗時は次の計画を確認
3. **ユーザー最優先**: 指示通りに実行
4. **ルール厳守**: 解釈変更禁止
5. **毎回表示**: 全チャット冒頭で原則表示

## 🧠 記憶システム統合
- **長期記憶**: Cipher で設計思想・決定事項を記録
- **状態保存**: ClaudePoint でチェックポイント管理
- **自動記録**: .claude/workspace/で完全ログ

## 開発フロー
1. `/launch-task` でタスク開始
2. 実装 → `/quality-gate` で品質確認
3. `/pre-deploy-check` でデプロイ準備確認
4. デプロイ → `/retrospect` で振り返り

## コーディング規約
- TypeScript strict mode必須
- ESLint/Prettier必須
- テストカバレッジ80%以上
- 全コミット前にLinter実行

## 🎯 開発7原則（技術実装ルール）

### 1. 🚫 ハードコード一切禁止
**原則**: 価格・プラン名・設定値は必ずAPI/metadataから動的取得

**実装ルール**:
- 定数・設定値は環境変数または設定ファイルで管理
- データベースまたはAPIから取得した値を使用
- フロントエンドで直接値を記述しない

**良い例**:
```typescript
// ✅ Good: APIから取得
const prices = await api.getPricing();
const plan = metadata.plans.find(p => p.id === planId);
```

**悪い例**:
```typescript
// ❌ Bad: ハードコード
const price = 9.99;
const planName = "Premium Plan";
```

### 2. 🥇 Tencent API First原則
**原則**: データソースの優先順位を厳守

**優先順位**:
1. Tencent API（最優先）
2. ローカルキャッシュ（有効期限内）
3. フォールバック値（最終手段）

**実装ルール**:
```typescript
// ✅ Good: 優先順位に従った取得
try {
  const data = await tencentAPI.getData();
  cache.set('data', data);
  return data;
} catch (error) {
  const cached = cache.get('data');
  if (cached && !cache.isExpired('data')) {
    return cached;
  }
  return fallbackData;
}
```

### 3. 💾 自動保存とgit管理
**原則**: 破壊的変更前は手動コミット必須

**実装ルール**:
- 大規模リファクタリング前：必ずコミット
- データベーススキーマ変更前：必ずバックアップ
- 自動保存機能の実装（定期的なスナップショット）

**gitワークフロー**:
```bash
# 破壊的変更前
git add .
git commit -m "feat: 変更前のチェックポイント"

# 変更実行
# ...変更作業...

# 検証後にコミット
git add .
git commit -m "feat: 機能実装完了"
```

### 4. ⚠️ エラーハンドリング必須
**原則**: 全API呼び出しをtry-catchで保護

**実装ルール**:
```typescript
// ✅ Good: 完全なエラーハンドリング
async function fetchData() {
  try {
    const response = await api.getData();
    return { success: true, data: response };
  } catch (error) {
    logger.error('❌ Data fetch failed', {
      error: error.message,
      stack: error.stack,
      timestamp: new Date().toISOString()
    });
    return { success: false, error: error.message };
  }
}
```

**エラーログの構造化**:
```typescript
interface ErrorLog {
  level: 'error' | 'warn' | 'info';
  message: string;
  context: Record<string, any>;
  timestamp: string;
  stack?: string;
}
```

### 5. 📝 ログ出力ルール
**原則**: 構造化ログと絵文字プレフィックス使用

**絵文字プレフィックス標準**:
- 🚀 起動・開始
- ✅ 成功
- ❌ エラー
- ⚠️ 警告
- 📝 情報
- 🔍 デバッグ
- 💾 データベース操作
- 🌐 API呼び出し
- 🔐 認証・セキュリティ

**実装例**:
```typescript
logger.info('🚀 アプリケーション起動', { port: 3000, env: 'production' });
logger.success('✅ データベース接続成功', { host: dbHost });
logger.error('❌ API呼び出し失敗', { endpoint: '/api/users', error });
logger.warn('⚠️ レート制限接近', { current: 95, limit: 100 });
```

### 6. 🛡️ TypeScript型安全
**原則**: any型完全禁止、型定義必須

**実装ルール**:
```typescript
// ✅ Good: 厳密な型定義
interface User {
  id: string;
  name: string;
  email: string;
  role: 'admin' | 'user' | 'guest';
  metadata?: Record<string, unknown>;
}

async function getUser(id: string): Promise<User> {
  const response = await api.get(`/users/${id}`);
  return response.data as User;
}

// ❌ Bad: any型の使用
function getUser(id: any): any {
  return api.get(`/users/${id}`);
}
```

**tsconfig.json必須設定**:
```json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "strictFunctionTypes": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true
  }
}
```

### 7. 🎨 フロントエンド開発ルール
**原則**: metadataから動的表示、コンポーネント再利用性最大化

**実装ルール**:
```typescript
// ✅ Good: metadataから動的生成
interface PlanMetadata {
  id: string;
  name: string;
  price: number;
  features: string[];
  highlighted: boolean;
}

const PlanCard: React.FC<{ plan: PlanMetadata }> = ({ plan }) => (
  <div className={plan.highlighted ? 'featured' : 'standard'}>
    <h3>{plan.name}</h3>
    <p>{plan.price}円/月</p>
    <ul>
      {plan.features.map(f => <li key={f}>{f}</li>)}
    </ul>
  </div>
);

// metadataから全プランを生成
const plans = await api.getPlans();
return plans.map(plan => <PlanCard key={plan.id} plan={plan} />);
```

**アクセシビリティ対応**:
- ARIA属性の適切な使用
- キーボードナビゲーション対応
- スクリーンリーダー対応
- コントラスト比の確保

## 📋 開発チェックリスト
実装前に確認すべき項目：
- [ ] ハードコードされた値がないか
- [ ] API呼び出しにエラーハンドリングがあるか
- [ ] ログ出力に適切な絵文字プレフィックスがあるか
- [ ] any型を使用していないか
- [ ] metadataから動的に値を取得しているか
- [ ] 破壊的変更前にコミットしたか
- [ ] データソースの優先順位を守っているか

## プロジェクト構成
- `/src` - ソースコード
- `/tests` - テストコード
- `/docs` - ドキュメント
- `/.claude` - AI記憶システム

## カスタムコマンド
- `/onboard` - 新プロジェクト開始時の完全オンボーディング
- `/launch-task` - タスク実行開始（計画確認フロー統合）
- `/quality-gate` - コード品質自動検証
- `/pre-deploy-check` - デプロイ前完全チェックリスト
- `/retrospect` - プロジェクト振り返りと自動記録
- `/update-index` - プロジェクトインデックス更新
- `/search-memory` - Cipher記憶検索
- `/memory-stats` - 記憶システム統計
