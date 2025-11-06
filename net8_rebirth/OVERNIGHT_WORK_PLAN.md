# NET8 SDK - 夜間自動作業計画書

**作業時間帯**: 23:00 〜 08:00（9時間）
**作業モード**: 完全自律実行（承認不要）
**目標**: ベータ版を完全に動作する状態まで仕上げる

---

## 🎯 夜間作業の目標

### 最優先ゴール
1. ✅ 全APIエンドポイントが動作する
2. ✅ SDK Demoが完全に動作する
3. ✅ APIキー管理が動作する
4. ✅ ドキュメントが完全に整備される
5. ✅ Railwayデプロイが成功する

---

## ⏰ タイムライン（9時間計画）

### Phase 1: データベース＆デプロイ準備（23:00-00:00）

#### タスク
```
□ setup_api_keys_table.sql をローカルDBで実行
□ テストデータ挿入
□ git add → commit → push
□ Railway自動デプロイ開始（5分待機）
□ デプロイログ確認
```

#### 実行コマンド
```bash
# データベースセットアップ
mysql -h 136.116.70.86 -u net8tech001 -p'Nene11091108!!' net8_dev < net8/02.ソースファイル/net8_html/api/setup_api_keys_table.sql

# Git操作
git add .
git commit -m "feat: NET8 SDK Beta - Complete implementation"
git push origin main

# デプロイ確認
# Railway Dashboard でログ確認
```

---

### Phase 2: API動作テスト（00:00-01:30）

#### 2-1. 認証APIテスト
```bash
# テスト実行
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_demo_12345"}' | jq

# 期待: {"success":true,"token":"...","expiresIn":3600}
```

**エラーが発生した場合**:
1. PHPエラーログを確認
2. CORS設定を確認
3. データベース接続を確認
4. 修正 → commit → push → 再テスト

---

#### 2-2. 機種一覧APIテスト
```bash
curl https://mgg-webservice-production.up.railway.app/api/v1/models.php | jq
```

**チェックポイント**:
- ✅ 機種データが正しく取得できるか
- ✅ 画像URLが正しいか
- ✅ カテゴリー変換が正しいか

**問題があれば**:
- `mst_model`テーブルのデータを確認
- SQLクエリを修正
- 再デプロイ

---

#### 2-3. ゲーム開始APIテスト
```bash
TOKEN=$(curl -s -X POST https://mgg-webservice-production.up.railway.app/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_demo_12345"}' | jq -r '.token')

curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"modelId": "milliongod"}' | jq
```

**チェックポイント**:
- ✅ 利用可能なマシンが見つかるか
- ✅ WebRTC Signaling情報が正しいか
- ✅ playUrlが正しいか

**問題があれば**:
- `dat_machine`テーブルを確認
- `lnk_machine`テーブルをリセット
- マシンステータスを修正

---

### Phase 3: SDK動作テスト（01:30-03:00）

#### 3-1. デモページアクセス
```
URL: https://mgg-webservice-production.up.railway.app/sdk/demo.html
```

**手動テスト項目**:
1. ✅ 「1. SDK初期化」ボタンをクリック
   - コンソールログ確認
   - ステータス表示確認

2. ✅ 「2. 機種一覧を読み込み」ボタンをクリック
   - 機種カードが表示されるか
   - 12機種が表示されるか

3. ✅ 「3. ゲーム開始」ボタンをクリック
   - ゲーム画面がiframeで表示されるか
   - WebRTC接続が確立するか

**問題があれば**:
- ブラウザのコンソールログを確認
- ネットワークタブでAPI呼び出しを確認
- SDK JavaScript コードをデバッグ
- 修正 → デプロイ → 再テスト

---

#### 3-2. JavaScript SDKのバグ修正

**よくあるバグ**:
```javascript
// 問題: コンテナが見つからない
// 修正前
this.container = document.querySelector(container);

// 修正後
_resolveContainer(container) {
  if (typeof container === 'string') {
    const element = document.querySelector(container);
    if (!element) {
      throw new Error(`Container not found: ${container}`);
    }
    return element;
  }
  return container;
}
```

---

### Phase 4: APIキー管理画面テスト（03:00-04:00）

#### 4-1. 管理画面アクセス
```
URL: https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php
```

**テスト項目**:
1. ✅ ログインできるか
2. ✅ APIキー一覧が表示されるか
3. ✅ 新規APIキーを生成できるか
4. ✅ APIキーを有効化/無効化できるか
5. ✅ 使用統計が表示されるか

**問題があれば**:
- PHPエラーを確認
- SQLクエリを確認
- SmartDB接続を確認

---

### Phase 5: 追加機能実装（04:00-06:00）

#### 5-1. API使用ログ記録機能
```php
// 各APIエンドポイントにログ記録を追加
$logSql = "INSERT INTO api_usage_logs
    (api_key_id, endpoint, method, status_code, response_time_ms, ip_address, user_agent)
    VALUES (?, ?, ?, ?, ?, ?, ?)";
```

#### 5-2. レート制限機能
```php
// 1時間あたりのリクエスト数をチェック
$rateSql = "SELECT COUNT(*) as count
            FROM api_usage_logs
            WHERE api_key_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";

if ($count >= $rateLimit) {
    http_response_code(429);
    echo json_encode(['error' => 'RATE_LIMIT_EXCEEDED']);
    exit;
}
```

#### 5-3. 売上・収益管理ダッシュボード（重要！）
```php
// API提供先向けの売上確認ダッシュボード
// ファイル: data/xxxadmin/revenue_dashboard.php

// 売上集計
$revenueSql = "SELECT
    DATE(created_at) as date,
    COUNT(*) as play_count,
    SUM(amount) as total_revenue,
    SUM(amount) * 0.25 as net8_share
FROM game_transactions
WHERE api_key_id = ?
GROUP BY DATE(created_at)
ORDER BY date DESC";

// グラフ表示用のデータ生成
// レベニューシェア（25-30%）の計算
// API提供先の純利益の表示
```

#### 5-4. エラーログ改善
```php
// 詳細なエラーログ
error_log(sprintf(
    '[NET8 API] %s - %s: %s (File: %s, Line: %d)',
    date('Y-m-d H:i:s'),
    $endpoint,
    $error->getMessage(),
    $error->getFile(),
    $error->getLine()
));
```

---

### Phase 6: ドキュメント整備（06:00-07:00）

#### 6-1. README.md作成
```markdown
# NET8 SDK Beta

3行でパチスロゲームを組み込み可能なJavaScript SDK

## クイックスタート
[コード例]

## ドキュメント
- [クイックスタートガイド](NET8_SDK_QUICKSTART.md)
- [JavaScript SDK仕様](NET8_JAVASCRIPT_SDK_SPEC.md)
- [デプロイガイド](NET8_SDK_BETA_DEPLOYMENT_GUIDE.md)
```

#### 6-2. APIドキュメント作成
```markdown
# NET8 API Reference

## Authentication
POST /api/v1/auth.php
[詳細]

## Models
GET /api/v1/models.php
[詳細]

## Game
POST /api/v1/game_start.php
[詳細]
```

---

### Phase 7: 最終確認＆レポート作成（07:00-08:00）

#### 7-1. 全機能の動作確認
```
□ API認証が動作する
□ 機種一覧が取得できる
□ ゲームが起動する
□ APIキー管理が動作する
□ デモページが完全に動作する
```

#### 7-2. パフォーマンステスト
```bash
# API レスポンスタイム測定
for i in {1..10}; do
  curl -w "\nTime: %{time_total}s\n" \
    -X POST https://mgg-webservice-production.up.railway.app/api/v1/auth.php \
    -H "Content-Type: application/json" \
    -d '{"apiKey": "pk_demo_12345"}'
done
```

**目標**: < 200ms

---

#### 7-3. 完了レポート作成

```markdown
# NET8 SDK Beta版 - 夜間作業完了レポート

## 実施日時
2025-11-06 23:00 〜 2025-11-07 08:00

## 完了項目
✅ データベースセットアップ
✅ API実装（3エンドポイント）
✅ JavaScript SDK実装
✅ デモページ実装
✅ APIキー管理画面実装
✅ Railway デプロイ
✅ 動作確認完了

## テスト結果
- API認証: ✅ 成功（平均 XXms）
- 機種一覧: ✅ 成功（12機種取得）
- ゲーム開始: ✅ 成功
- SDK動作: ✅ 完全動作

## 公開URL
- デモページ: https://mgg-webservice-production.up.railway.app/sdk/demo.html
- API管理: https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php

## 次のタスク
[未完了項目があれば記載]

## 備考
[特記事項]
```

---

## 🚨 エラー対応プロトコル

### Level 1: 軽微なエラー
- ログに記録
- 自動修正を試みる
- 修正できない場合は次のタスクへ

### Level 2: 中程度のエラー
- ログに記録
- 複数の修正方法を試す
- 30分以内に解決できない場合はスキップ

### Level 3: 致命的なエラー
- ログに記録
- ロールバックを検討
- 朝のレポートで報告

---

## 📝 作業ログ

### 実行ログの保存先
```
/Users/kotarokashiwai/net8_rebirth/.claude/workspace/overnight_work_log_20251106.md
```

### ログフォーマット
```
[23:05] Phase 1開始 - データベースセットアップ
[23:12] ✅ setup_api_keys_table.sql実行完了
[23:15] git push完了
[23:20] Railway デプロイ開始
[23:25] ⚠️ CORS エラー発生 - 修正中
[23:30] ✅ CORS エラー修正完了
...
```

---

## ✅ 完了条件

以下がすべて達成された時点で作業完了：

1. ✅ デモページが完全に動作する
2. ✅ すべてのAPIエンドポイントが200を返す
3. ✅ APIキー管理画面が動作する
4. ✅ ドキュメントが完全に整備される
5. ✅ 完了レポートが作成される

---

## 🌙 就寝前の最終チェックリスト

```bash
# 1. Gitの状態確認
git status

# 2. プッシュ確認
git log -1

# 3. Railway デプロイ確認
# https://railway.app/ でステータス確認

# 4. デモページアクセス確認
curl -I https://mgg-webservice-production.up.railway.app/sdk/demo.html
```

すべてOKなら就寝。

朝起きたら完成しています。

---

**作成日**: 2025-11-06
**実行開始**: 2025-11-06 23:00（予定）
**実行終了**: 2025-11-07 08:00（予定）
