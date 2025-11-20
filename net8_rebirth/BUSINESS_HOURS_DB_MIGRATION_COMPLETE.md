# 営業時間設定DB管理化 - 完了記録

**作業日**: 2025-11-18
**担当**: Claude Code
**ステータス**: ✅ 完了・デプロイ済み

---

## 📋 概要

営業時間設定（GLOBAL_OPEN_TIME, GLOBAL_CLOSE_TIME, REFERENCE_TIME）をPHPファイルのハードコードから、データベース（mst_settingテーブル）管理に移行しました。

### 目的
- 営業時間変更時にコード修正・デプロイ不要に
- 管理画面から設定を変更可能に
- システムの柔軟性向上

---

## 🔧 変更内容

### 1. データベース設定追加

**テーブル**: `mst_setting`

| setting_key | setting_val | 説明 |
|------------|-------------|------|
| GLOBAL_OPEN_TIME | 10:00 | 営業開始時刻 |
| GLOBAL_CLOSE_TIME | 22:00 | 営業終了時刻 |
| REFERENCE_TIME | 04:00 | 基準時刻（日跨ぎ判定） |

**マイグレーションスクリプト**:
- `net8/add_business_hours_settings.sql`

---

## 📝 変更ファイル一覧

### ファイル1: `_etc/require_files.php`

**変更箇所**: ファイル末尾に追加

```php
// 営業時間設定をDBから読み込み
$GLOBALS['RUNTIME_CONFIG'] = [];
try {
    if (class_exists('NetDB')) {
        $db = new NetDB();
        $sql = "SELECT setting_key, setting_val
                FROM mst_setting
                WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
                  AND del_flg = 0";
        $result = $db->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $GLOBALS['RUNTIME_CONFIG'][$row['setting_key']] = $row['setting_val'];
        }
    }
} catch (Exception $e) {
    error_log('[営業時間設定] DB読み込みエラー: ' . $e->getMessage());
}

function get_business_hours_config($key) {
    // 1. DBから読み込んだ値
    if (isset($GLOBALS['RUNTIME_CONFIG'][$key])) {
        return $GLOBALS['RUNTIME_CONFIG'][$key];
    }
    // 2. 定数定義（フォールバック）
    if (defined($key)) {
        return constant($key);
    }
    // 3. デフォルト値
    $defaults = [
        'GLOBAL_OPEN_TIME' => '10:00',
        'GLOBAL_CLOSE_TIME' => '22:00',
        'REFERENCE_TIME' => '04:00'
    ];
    return $defaults[$key] ?? '';
}
```

**影響範囲**: 全ユーザー画面（フロントエンド）

---

### ファイル2: `_sys/TemplateUser.php`

**変更箇所**: 223-225行目

**変更前**:
```php
$this->assign("GLOBAL_OPEN_TIME" , GLOBAL_OPEN_TIME, true);
$this->assign("GLOBAL_CLOSE_TIME", GLOBAL_CLOSE_TIME, true);
```

**変更後**:
```php
$this->assign("GLOBAL_OPEN_TIME" , get_business_hours_config('GLOBAL_OPEN_TIME'), true);
$this->assign("GLOBAL_CLOSE_TIME", get_business_hours_config('GLOBAL_CLOSE_TIME'), true);
```

**影響範囲**: 全テンプレート変数

---

### ファイル3: `_sys/RefTimeFunc.php`

**変更箇所**: 5つの関数

1. **GetRefTimeToday()** - 39, 40行目
2. **GetRefTimeTodayExt()** - 59, 60, 61行目
3. **GetRefTimeStart()** - 85行目
4. **GetRefTimeEnd()** - 103行目
5. **GetRefTimeOffsetStart()** - 121行目

**変更内容**:
```php
// 変更前
$referenceTime = REFERENCE_TIME;

// 変更後
$referenceTime = get_business_hours_config('REFERENCE_TIME');
```

**影響範囲**:
- 日付計算
- ログ・集計処理
- プレイ履歴の日付判定

---

### ファイル4: `_etc/require_files_admin.php` ⚠️

**変更箇所**: ファイル末尾に追加

**理由**: Admin画面エラー修正
- Admin画面は `require_files.php` ではなく `require_files_admin.php` を使用
- `get_business_hours_config()` 関数が未定義でエラー発生

**追加コード**:
```php
// 営業時間設定をDBから読み込み（管理画面用）
$GLOBALS['RUNTIME_CONFIG'] = [];
try {
    if (class_exists('NetDB')) {
        $db = new NetDB();
        $sql = "SELECT setting_key, setting_val
                FROM mst_setting
                WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
                  AND del_flg = 0";
        $result = $db->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $GLOBALS['RUNTIME_CONFIG'][$row['setting_key']] = $row['setting_val'];
        }
    }
} catch (Exception $e) {
    error_log('[Admin営業時間設定] DB読み込みエラー: ' . $e->getMessage());
}

if (!function_exists('get_business_hours_config')) {
    function get_business_hours_config($key) {
        if (isset($GLOBALS['RUNTIME_CONFIG'][$key])) {
            return $GLOBALS['RUNTIME_CONFIG'][$key];
        }
        if (defined($key)) {
            return constant($key);
        }
        $defaults = [
            'GLOBAL_OPEN_TIME' => '10:00',
            'GLOBAL_CLOSE_TIME' => '22:00',
            'REFERENCE_TIME' => '04:00'
        ];
        return $defaults[$key] ?? '';
    }
}
```

**影響範囲**: 管理画面全体

---

## 🚨 発生した問題と解決

### 問題1: Admin画面が表示されない

**現象**:
- Admin画面（data/xxxadmin/index.php）でエラー発生
- `Call to undefined function get_business_hours_config()`

**原因**:
- Admin画面は `require_files_admin.php` を使用
- `get_business_hours_config()` 関数が `require_files.php` にしか定義されていなかった
- `index.php` → `GetRefTimeOffsetStart()` → `get_business_hours_config()` を呼び出してエラー

**解決策**:
- `require_files_admin.php` にも同じロジックと関数を追加

---

## 📦 デプロイ履歴

### Gitコミット

```bash
cbf9136 - feat: 営業時間設定のDB管理化を実装
b6b92ca - test: Add business hours config test script
b8c3831 - feat: ゲームデータ検証スクリプトを追加
7ed3e5d - fix: Admin画面エラー修正
```

### Railway デプロイ

- **デプロイ日時**: 2025-11-18
- **環境**: Production (mgg-webservice-production.up.railway.app)
- **ステータス**: ✅ 自動デプロイ完了

---

## ✅ テスト結果

### テストスクリプト実行

**URL**: `https://mgg-webservice-production.up.railway.app/data/api/test_business_hours_config.php`

**結果**:
```
✅ 営業時間設定が正しくDBから読み込まれています！

GLOBAL_OPEN_TIME  = 10:00
GLOBAL_CLOSE_TIME = 22:00
REFERENCE_TIME    = 04:00

✅ データベースに営業時間設定が3件存在します
```

### 動作確認

- ✅ トップページ: 正常表示
- ✅ 営業時間設定: DB読み込み成功
- ✅ Admin画面: 修正後正常表示
- ✅ 日付計算関数: 正常動作

---

## 📊 影響範囲まとめ

### システム全体への影響

| コンポーネント | 影響 | 状態 |
|--------------|------|-----|
| フロントエンド（ユーザー画面） | 営業時間表示 | ✅ 正常 |
| 管理画面（Admin） | 日付計算、統計表示 | ✅ 修正済み |
| 日付計算関数（RefTimeFunc） | 基準時刻の取得元変更 | ✅ 正常 |
| テンプレート変数 | 営業時間の取得元変更 | ✅ 正常 |
| データベース | mst_settingに3設定追加 | ✅ 完了 |

---

## 🎯 今後の拡張

### 管理画面での設定変更機能

将来的に以下の機能を追加可能：

1. **システム設定画面**に営業時間設定項目を追加
2. 管理者がGUIで営業時間を変更可能に
3. 変更履歴の記録
4. 営業時間の曜日別設定

---

## ⚠️ 重要な教訓

### 開発時の注意点

1. **require_files.php と require_files_admin.php は別ファイル**
   - 両方に変更が必要な場合がある
   - Admin画面用の関数も忘れずに実装

2. **中核ファイルの変更は慎重に**
   - require_files系は全ページから読み込まれる
   - エラーが発生するとシステム全体に影響

3. **デプロイ前のチェック必須**
   - `/quality-gate` で品質確認
   - `/pre-deploy-check` でデプロイ前チェック
   - テスト環境での動作確認

4. **フォールバック機能の実装**
   - DB接続エラー時は定数定義を使用
   - デフォルト値を用意

---

## 📚 関連ドキュメント

- `net8/add_business_hours_settings.sql` - マイグレーションSQL
- `data/api/migrate_business_hours.php` - マイグレーション実行スクリプト
- `data/api/test_business_hours_config.php` - テストスクリプト

---

## 👥 作成者

**Claude Code** - AI開発アシスタント
**監修**: kotarokashiwai

---

**最終更新**: 2025-11-18
**バージョン**: 1.0
**ステータス**: ✅ 完了
