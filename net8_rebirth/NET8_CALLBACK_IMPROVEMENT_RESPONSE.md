# NET8 API 改善対応レポート

**対応日**: 2026-01-22
**担当**: NET8開発チーム
**対象**: 韓国市場フロントエンドチーム様からの改善要望

---

## 📨 受領した要望

韓国チーム様より以下の改善要望を受領しました：

### 1. pointsフィールドの定義が不明確

```json
{
  "points": {
    "initial": "0",    // ← game_start の initialPoints が反映されていない
    "consumed": "0",   // ← ベット総額が記録されていない
    "won": 0,          // ← 勝利総額が記録されていない
    "final": "53090",  // ← 最終残高（正常）
    "net": 0           // ← 計算式が不明
  }
}
```

### 2. 改善要望内容

1. `points.initial` に `game_start` の `initialPoints` を保存
2. `points.consumed` にベット総額を記録
3. `points.won` に勝利総額を記録

---

## ✅ NET8チームの回答

### 📊 現状のデータ構造の説明

**ご指摘の通り、現在のデータ構造には設計上の問題がございます。**

#### 現在の各フィールドの意味:

| フィールド | 現在の値 | 本来の意味 | 問題点 |
|-----------|---------|-----------|--------|
| `initial` | `$session['points_consumed']` | ゲーム開始時の残高 | ❌ 誤った値を使用 |
| `consumed` | `$session['points_consumed']` | ベット総額（累計） | ❌ 実際のベット累計ではない |
| `won` | `$result['pointsWon']` | 勝利総額（累計） | ❌ game_end.phpのパラメータ頼り |
| `final` | `$result['newBalance']` | 最終残高 | ✅ 正常 |
| `net` | `$result['netProfit']` | 純損益 | ❌ 誤った値から計算 |

#### 根本原因:

1. **テーブル設計の問題**
   - `game_sessions` テーブルに `initial_balance` カラムが存在しない
   - `total_bets` / `total_wins` カラムが存在しない

2. **データ保存ロジックの問題**
   - `game_start.php` がユーザーの開始時残高を保存していない
   - `game_end.php` が累計ベット/勝利を記録していない

3. **コールバックデータ構築の問題**
   - `callback_helper.php` が存在しないカラムから無理やりデータを作成している

---

## 🛠️ 改善実装計画

韓国チーム様のご要望に対応するため、以下の改善を実装いたします。

### 実装内容

#### 1. データベーススキーマ拡張

```sql
ALTER TABLE game_sessions
ADD COLUMN initial_balance INT DEFAULT 0 COMMENT 'ゲーム開始時のユーザー残高',
ADD COLUMN total_bets INT DEFAULT 0 COMMENT 'ゲーム内累計ベット額',
ADD COLUMN total_wins INT DEFAULT 0 COMMENT 'ゲーム内累計勝利額';
```

#### 2. game_start.php 修正

- ユーザーの現在残高を `initial_balance` として保存
- `initialPoints` パラメータを優先的に使用

#### 3. game_end.php 修正

- ゲーム終了時に `total_bets` / `total_wins` を保存
- パチンコ機からの精算データ（`resultData`）を活用

#### 4. callback_helper.php 修正

**修正後のデータ構造**:
```php
'points' => [
    'initial' => (int)$session['initial_balance'],  // ✅ 開始時残高
    'consumed' => (int)$session['total_bets'],      // ✅ 累計ベット額
    'won' => (int)$session['total_wins'],           // ✅ 累計勝利額
    'final' => $result['newBalance'],               // ✅ 最終残高
    'net' => $result['newBalance'] - $session['initial_balance'] // ✅ 正確な純損益
],
```

---

## 📅 実装スケジュール

| ステップ | 内容 | 所要時間 | 状態 |
|---------|------|---------|------|
| 1 | テーブルスキーマ拡張 | 5分 | ⏳ 準備中 |
| 2 | game_start.php 修正 | 10分 | ⏳ 準備中 |
| 3 | game_end.php 修正 | 15分 | ⏳ 準備中 |
| 4 | callback_helper.php 修正 | 10分 | ⏳ 準備中 |
| 5 | テスト実施 | 15分 | ⏳ 準備中 |
| 6 | Railway本番デプロイ | 5分 | ⏳ 準備中 |

**合計所要時間**: 約60分
**実装予定**: 本日中（2026-01-22）

---

## 🧪 テスト計画

### テストシナリオ

1. **シナリオ1: 正常系**
   - initialPoints: 10000 でゲーム開始
   - ゲームプレイ（ベット・勝利）
   - ゲーム終了
   - ✅ 期待: points.initial = 10000

2. **シナリオ2: ベット累計確認**
   - 複数回ベット後にゲーム終了
   - ✅ 期待: points.consumed = ベット総額

3. **シナリオ3: 純損益計算確認**
   - 開始残高 - 最終残高 = net
   - ✅ 期待: 正確な純損益

---

## 📢 韓国チーム様へのお願い

### 実装完了後の確認事項

改善実装完了後、以下の確認をお願いいたします：

1. **game.ended コールバックのデータ構造確認**
   ```json
   {
     "points": {
       "initial": 10000,    // ← initialPoints と一致するか確認
       "consumed": 1500,    // ← 実際のベット総額か確認
       "won": 2000,         // ← 実際の勝利総額か確認
       "final": 10500,      // ← 最終残高（従来通り）
       "net": 500           // ← initial と final の差分か確認
     }
   }
   ```

2. **settlement (postMessage) との一貫性確認**
   - postMessage のデータと game.ended のデータが一致するか
   - どちらを優先データソースとするかご判断ください

---

## 🙏 最後に

この度はご指摘ありがとうございました。
NET8側のデータ構造設計に不備があったことをお詫び申し上げます。

本改善により、韓国チーム様のフロントエンド実装がより堅牢になることを期待しております。

実装完了次第、改めてご連絡いたします。

---

**NET8開発チーム**
Email: net8-dev@example.com
Slack: #net8-integration
GitHub: anthropics/net8_rebirth
