# NET8 プロジェクト開発ログ - 2025-11-15

## 📋 実装サマリー

### 完了したタスク
1. ブランディング変更 (Ryujin8 → MillionNet8)
2. ロゴ・トップ画像差し替え
3. 台一覧の状態管理検証とバグ修正
4. お知らせ・コーナー管理のフロントエンド統合修正
5. デプロイ構造の理解と確認

---

## 🔧 実装詳細

### 1. ブランディング変更 (Ryujin8 → MillionNet8)

**変更内容**: 全18ファイルで "竜神8" / "ryujin8" を "MillionNet8" に置換

**変更ファイル**:
- `_html/ja/admin/camera.html`
- `_html/ja/admin/index.html`
- `_html/ja/admin/model.html`
- `_html/ja/admin/user.html`
- `_html/ja/model.html`
- `_html/ja/play/index.html`
- `_html/ja/play/index_public_ryujin8.html` → `index_public_millionnet8.html` (ファイル名変更)
- `_html/en/admin/camera.html`
- `_html/en/admin/index.html`
- `_html/en/admin/model.html`
- `_html/en/admin/user.html`
- `_html/en/model.html`
- `_html/en/play/index.html`
- `_html/en/play/index_public_ryujin8.html` → `index_public_millionnet8.html` (ファイル名変更)
- `data/play_v2/index.php`
- `_sys/TemplateUser.php`
- `_etc/setting_base.php` (定数 TPNAME_PUBLIC_RYUJIN8 → TPNAME_PUBLIC_MILLIONNET8)

**重要な例外処理**:
- CSSパス、プログラム内部パスは変更なし
- URL表示される `play/index_public_ryujin8` のみ `play/index_public_millionnet8` に変更

**Git Commit**:
```
dc456e2 fix: Restore SmartTemplate API calls and fix loop syntax
```

---

### 2. ロゴ・トップ画像差し替え

**変更内容**:
- `ryujin8_logo.png` → `net8_logo.jpg`
- `top_image.png` → `top.jpg`

**差し替え元ファイル**:
- `/Users/kotarokashiwai/net8_rebirth/top.jpg`
- `/Users/kotarokashiwai/net8_rebirth/net8_logo.jpg`

**差し替え先（各3箇所）**:
```
content/images/net8_logo.jpg
content/images/top.jpg
ryujin8_assets/content/images/net8_logo.jpg
ryujin8_assets/content/images/top.jpg
data/content/images/net8_logo.jpg
data/content/images/top.jpg
```

**HTML参照更新**:
- `_html/ja/admin/*.html` - ロゴ参照を `net8_logo.jpg` に変更
- `_html/en/admin/*.html` - ロゴ参照を `net8_logo.jpg` に変更

---

### 3. 台一覧の状態管理検証とバグ修正

#### 状態管理フロー確認

**データベーステーブル**: `lnk_machine`
- `machine_status`: 0=準備中, 1=稼働中, 2=メンテナンス中
- `assign_flg`: 0=空き, 1=使用中, 9=メンテナンス中

**状態管理の流れ**:
1. **トップページ** (`data/index.php`) - 機種選択
2. **機種詳細** (`data/model.php`) - 台一覧表示
3. **状態判定** (`_sys/TemplateUser.php::AssignMachineList()`) - DBから状態取得
4. **テンプレート** (`_html/ja/parts_machine_list_v2.html`) - 状態表示
5. **プレイ画面** (`data/play_v2/index.php`) - 最終状態チェック

#### 発見した重大なバグ

**ファイル**: `_html/ja/parts_machine_list_v2.html`
**行番号**: 323 (修正前)

**問題点**:
プレイボタンが機械の状態に関わらず常に有効で、クリック可能だった

**修正前のコード**:
```html
<div class="machine-actions">
    <button class="btn-play" onclick="event.stopPropagation(); location.href='{%SITE_SSL_URL%}play_v2/?NO={%NO%}'">
        <i class="fas fa-play"></i> プレイする
    </button>
    <button class="btn-details" onclick="...">
        <i class="fas fa-info-circle"></i>
    </button>
</div>
```

**修正後のコード** (lines 322-360):
```html
<div class="machine-actions">
    <!--if:{CLOSED}-->
    <button class="btn-play btn-disabled" disabled style="opacity: 0.5; cursor: not-allowed;">
        <i class="fas fa-clock"></i> 営業時間外
    </button>
    <!--/if:{CLOSED}-->
    <!--if:{LINK_MAINTENANCE}-->
    <button class="btn-play btn-disabled" disabled style="opacity: 0.5; cursor: not-allowed;">
        <i class="fas fa-tools"></i> メンテナンス中
    </button>
    <!--/if:{LINK_MAINTENANCE}-->
    <!--if:{MAINTENANCE}-->
    <button class="btn-play btn-disabled" disabled style="opacity: 0.5; cursor: not-allowed;">
        <i class="fas fa-tools"></i> メンテナンス中
    </button>
    <!--/if:{MAINTENANCE}-->
    <!--if:{IN_PREPARATION}-->
    <button class="btn-play btn-disabled" disabled style="opacity: 0.5; cursor: not-allowed;">
        <i class="fas fa-pause"></i> 準備中
    </button>
    <!--/if:{IN_PREPARATION}-->
    <!--if:{IS_OPEN}-->
        <!--if:{IS_NORMAL}-->
            <!--if:{AVAILABLE}-->
            <button class="btn-play" onclick="event.stopPropagation(); location.href='{%SITE_SSL_URL%}play_v2/?NO={%NO%}'">
                <i class="fas fa-play"></i> プレイする
            </button>
            <!--/if:{AVAILABLE}-->
            <!--if:{NOAVAIABLE}-->
            <button class="btn-play btn-disabled" disabled style="opacity: 0.5; cursor: not-allowed;">
                <i class="fas fa-user</i> 使用中
            </button>
            <!--/if:{NOAVAIABLE}-->
        <!--/if:{IS_NORMAL}-->
    <!--/if:{IS_OPEN}-->
    <button class="btn-details" onclick="event.stopPropagation(); showMachineDetails({%NO%}, '{%MODEL_NAME%}', '{%DIR_IMG_MODEL_DIR%}{%IMAGE_LIST%}')">
        <i class="fas fa-info-circle"></i>
    </button>
</div>
```

**影響**:
- セキュリティ: 営業時間外/メンテナンス中/準備中の台でもプレイが試行可能だった
- UX: ユーザーが使用不可の台をクリックしてエラーになる可能性

**修正結果**:
状態に応じてボタンが適切に無効化され、理由が表示される

**Git Commit**:
```
a6ebaad fix: Add machine status validation to play button in machine list
```

---

### 4. お知らせ・コーナー管理のフロントエンド統合修正

#### 問題1: お知らせ登録でエラーコード表示

**URL**: `https://mgg-webservice-production.up.railway.app/xxxadmin/notice.php`
**症状**: 登録時に "%20005" などのエラーコードが表示される

**原因**: エラーメッセージが日本語に翻訳されていなかった

#### 問題2: コーナー登録で画面遷移しない

**URL**: `https://mgg-webservice-production.up.railway.app/xxxadmin/corner.php`
**症状**:
- 「操作」ボタンクリック後、別画面が表示される
- 登録ボタンを押すと下に白枠が出現
- 画面遷移が発生しない

**原因**: エラーメッセージが翻訳されておらず、検証エラーが適切に表示されていなかった

#### 修正1: noticeTypeLang 変数未定義エラー

**ファイル**: `_etc/setting_base.php`
**問題**: `data/information.php` (フロントエンド) が `$noticeTypeLang` を参照しているが、定義されていなかった

**追加コード** (lines 427-437):
```php
// お知らせタイプ言語設定
$GLOBALS["noticeTypeLang"] = array(
    "ja" => array(
        "notice" => "お知らせ",
        "corner" => "新規コーナー"
    ),
    "en" => array(
        "notice" => "Notice",
        "corner" => "New Corner"
    )
);
```

#### 修正2: corner.php エラーメッセージ翻訳

**ファイル**: `data/xxxadmin/corner.php`
**行番号**: 400-408

**修正前**:
```php
$errMessage = (new SmartAutoCheck($template))
    ->item($_POST["CORNER_NAME"])
        ->required("A1201")
        ->maxLength("A1202", 20)
    ->item($_POST["CORNER_ROMAN"])
        ->maxLength("A1203", 50)
->report();
```

**修正後**:
```php
$errMessage = (new SmartAutoCheck($template))
    ->item($_POST["CORNER_NAME"])
        ->required($template->message("A1201"))
        ->maxLength($template->message("A1202"), 20)
    ->item($_POST["CORNER_ROMAN"])
        ->maxLength($template->message("A1203"), 50)
->report();
```

**Git Commit**:
```
5fe8274 fix: Add noticeTypeLang definition and fix corner.php error messages
```

#### フロントエンド連携確認

**お知らせフロントエンド**: `data/information.php`
- `dat_notice` テーブルから通知情報取得
- `mst_corner` テーブルからコーナー情報取得
- UNION ALL で統合して表示
- `$noticeTypeLang` を使用してタイプ名を表示

**コーナーフロントエンド**: 台一覧でタブ表示
- `mst_corner` テーブルから有効なコーナー取得
- 各コーナーに紐づく台をフィルタリング表示

---

## 🗄️ データベース構造理解

### mst_setting テーブル

**用途**: システム全体の設定値を管理

**構造**:
- `setting_no`: 設定番号 (PK)
- `setting_type`: 設定タイプ
- `setting_name`: 設定名
- `setting_key`: 設定キー
- `setting_format`: データ形式
- `setting_val`: 設定値
- `remarks`: 備考

**現状**: PACHI_RATE (パチンコレート) のみ登録されている

**調査スクリプト作成**: `data/api/check_mst_setting.php`
```php
<?php
require_once('../../_etc/require_files.php');

try {
    $db = new NetDB();

    $sql = "SELECT * FROM mst_setting WHERE del_flg = 0 ORDER BY setting_no";
    $result = $db->query($sql);

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "setting_key: {$row['setting_key']}\n";
        echo "setting_val: {$row['setting_val']}\n";
    }

    // 営業時間関連の設定を検索
    $sql2 = "SELECT * FROM mst_setting WHERE setting_key LIKE '%TIME%' OR setting_key LIKE '%OPEN%' OR setting_key LIKE '%CLOSE%'";
    $result2 = $db->query($sql2);
    // ...
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>
```

**Git Commit**:
```
72e04b4 debug: Add mst_setting table check script
```

---

## ⚠️ 未解決の課題

### 1. 営業時間設定の管理

**現状**: `_etc/setting_base.php` でハードコード
```php
define('GLOBAL_OPEN_TIME', getenv('GLOBAL_OPEN_TIME') ?: '10:00');
define('GLOBAL_CLOSE_TIME', getenv('GLOBAL_CLOSE_TIME') ?: '22:00');
define('REFERENCE_TIME', getenv('REFERENCE_TIME') ?: '04:00');
```

**問題点**:
- 環境変数または固定値で管理されている
- 管理画面から変更できない
- ユーザーの指摘: "もともとこれは、その営業時間設定はDBにあったのを無理やり記載した感じだと思いますんで、このあたり急ぎ確認をしてほしいです。"

**調査結果**:
- `mst_setting` テーブルに営業時間関連の設定は存在しない
- データベース初期化スクリプト (`02_init.sql`) にも該当データなし

**必要な対応**:
1. `mst_setting` テーブルに営業時間設定を追加
   - `GLOBAL_OPEN_TIME`
   - `GLOBAL_CLOSE_TIME`
   - `REFERENCE_TIME`
2. `data/xxxadmin/system.php` で編集可能にする
3. `_etc/setting_base.php` をDBから値を読み込むように変更
4. マイグレーションスクリプト作成

### 2. 管理画面の動作確認

**デプロイ待ち**:
- お知らせ登録: エラーメッセージ翻訳修正が反映されるか確認
- コーナー登録: 白枠問題が解決するか確認

**確認URL**:
- https://mgg-webservice-production.up.railway.app/xxxadmin/notice.php
- https://mgg-webservice-production.up.railway.app/xxxadmin/corner.php

---

## 🚀 デプロイ構造理解

### Git リポジトリ構造

**Gitルート**: `/Users/kotarokashiwai` (ホームディレクトリ全体)

**プロジェクト構造**:
```
/Users/kotarokashiwai/
└── net8_rebirth/
    ├── Dockerfile  ← Railwayが使用
    ├── DEPLOY.md  ← デプロイマニュアル
    ├── top.jpg  ← 新トップ画像
    ├── net8_logo.jpg  ← 新ロゴ
    └── net8/
        ├── 01.サーバ構築手順/
        │   └── net8peerjs-server/  ← PeerJSサーバー
        ├── 02.ソースファイル/
        │   └── net8_html/  ← PHPアプリケーション本体
        │       ├── data/api/  ← API
        │       ├── data/xxxadmin/  ← 管理画面
        │       ├── _html/  ← HTMLテンプレート
        │       ├── _sys/  ← システムクラス
        │       └── _etc/  ← 設定ファイル
        └── docker/
            ├── web/Dockerfile  ← Webサーバー用
            └── signaling/Dockerfile  ← シグナリングサーバー用
```

### Railway デプロイ構成

**プロジェクト名**: `mmg2501`

**サービス構成**:

| サービス名 | 種類 | URL | 用途 |
|-----------|------|-----|------|
| mgg-webservice | PHP/Apache | mgg-webservice-production.up.railway.app | メインアプリ |
| mgg-signaling | PeerJS | mgg-signaling-production.up.railway.app | WebRTC シグナリング |
| mysql | MySQL 5.7 | (内部) | データベース |

**mgg-webservice の詳細**:
- **ソースコード**: `/Users/kotarokashiwai` (ホームディレクトリ全体)
- **Dockerfile**: `/Users/kotarokashiwai/net8_rebirth/Dockerfile`
- **ビルドコンテキスト**: ホームディレクトリ全体
- **コピー対象**: `net8/02.ソースファイル/net8_html` → `/var/www/html`
- **ポート**: 80 (Apache)

### データベース接続

**GCP Cloud SQL**:
- **ホスト**: `136.116.70.86`
- **データベース**: `net8_dev`
- **ユーザー**: `net8tech001`
- **パスワード**: `Nene11091108!!`
- **ポート**: 3306

### デプロイフロー

**推奨方法: Git Push**
```bash
# ホームディレクトリで実行
cd /Users/kotarokashiwai

# 変更をコミット
git add net8_rebirth/net8/02.ソースファイル/net8_html/
git commit -m "fix: メッセージ"
git push origin main
```

**自動デプロイ**:
1. GitHub へ push
2. GitHub Webhook が Railway に通知
3. Railway が自動的にビルド・デプロイ
4. 2-5分で完了

**デプロイ確認**:
```bash
railway logs
```

---

## 📊 Git Commit 履歴

```
dc456e2 fix: Restore SmartTemplate API calls and fix loop syntax
d0dd3a4 chore: Force redeploy - camera.html SmartTemplate fix
85bd56b fix: Change camera_count to SmartTemplate syntax
ee4b9d6 fix: Convert camera.php DispList() to SmartTemplate API
3616576 fix: Convert camera.html from Smarty to SmartTemplate syntax
5fe8274 fix: Add noticeTypeLang definition and fix corner.php error messages
72e04b4 debug: Add mst_setting table check script
a6ebaad fix: Add machine status validation to play button in machine list
```

---

## 💡 技術的な学び

### 1. SmartTemplate システムの理解

**特徴**:
- カスタムPHPテンプレートエンジン
- プレースホルダ構文: `{%VARIABLE%}`
- 条件分岐: `<!--if:{FLAG}-->...<!--/if:{FLAG}-->`
- ループ: `<!--loop:{LIST}-->...<!--/loop:{LIST}-->`

**状態フラグの設定**:
```php
// _sys/TemplateUser.php
$this->if_enable("CLOSED", !$isOpen);
$this->if_enable("IS_OPEN", $isOpen);
$this->if_enable("AVAILABLE", ($assignFlg == 0));
$this->if_enable("NOAVAIABLE", ($assignFlg == 1));
$this->if_enable("LINK_MAINTENANCE", $isLinkMainte);
$this->if_enable("IN_PREPARATION", ($row["machine_status"] == 0 && !$isLinkMainte));
$this->if_enable("IS_NORMAL", ($row["machine_status"] == 1 && !$isLinkMainte));
$this->if_enable("MAINTENANCE", ($row["machine_status"] == 2 && !$isLinkMainte));
```

### 2. データベース駆動設計の重要性

**問題点**: ハードコードされた設定値
- 営業時間が `setting_base.php` に直接記述
- 管理画面から変更不可

**正しいアプローチ**:
- 設定値は `mst_setting` テーブルで管理
- 管理画面 (`xxxadmin/system.php`) で編集可能
- アプリケーション起動時にDBから読み込み

### 3. フロントエンド・バックエンド統合の重要性

**教訓**:
- 管理画面 (`xxxadmin/`) の変更は必ずフロントエンド (`data/`) への影響を確認
- グローバル変数の定義漏れがフロントエンドエラーの原因になる
- エラーメッセージの翻訳は UX に直結する

### 4. セキュリティとUXの両立

**プレイボタン修正の意義**:
- **セキュリティ**: 不正な状態での操作を防止
- **UX**: ユーザーに明確な理由を提示 (「営業時間外」「メンテナンス中」など)
- **データ整合性**: バックエンドとフロントエンドの状態チェック二重化

---

## 🎯 次のステップ

### 優先度: 高

1. **営業時間設定のDB管理化**
   - [ ] `mst_setting` テーブルに営業時間レコード追加
   - [ ] `data/xxxadmin/system.php` で編集可能にする
   - [ ] `_etc/setting_base.php` を DB読み込みに変更
   - [ ] マイグレーションスクリプト作成

2. **管理画面動作確認**
   - [ ] デプロイ完了後、お知らせ登録動作確認
   - [ ] コーナー登録動作確認
   - [ ] フロントエンドへの反映確認

### 優先度: 中

3. **Git リポジトリ構造改善**
   - [ ] `net8_rebirth/` を独立リポジトリに分離
   - [ ] `.dockerignore` で不要ファイル除外
   - [ ] Dockerfile 重複解消

4. **コード品質向上**
   - [ ] PHP PSR-12 準拠チェック
   - [ ] セキュリティスキャン実施
   - [ ] パフォーマンステスト

### 優先度: 低

5. **ドキュメント整備**
   - [ ] API ドキュメント作成
   - [ ] データベーススキーマドキュメント更新
   - [ ] 管理画面操作マニュアル作成

---

## 📝 重要な設計決定

### 決定1: ブランディング変更の範囲

**決定内容**: 内部パス・CSS参照は変更せず、ユーザー表示のみ変更

**理由**:
- 既存の CSS/JS 参照を壊さない
- デプロイリスク最小化
- URL表示のみユーザーに見える部分を変更

### 決定2: プレイボタンの状態管理

**決定内容**: テンプレートレベルで状態に応じたボタン表示を制御

**理由**:
- JavaScript での制御よりもサーバーサイドで確実
- SmartTemplate の条件分岐機能を活用
- バックエンドの状態判定ロジックと一致

### 決定3: デプロイ方法

**決定内容**: Git push による自動デプロイ

**理由**:
- Railway の自動デプロイ機能を活用
- 手動デプロイのミスを防止
- Git履歴として変更が記録される

---

## 🔐 セキュリティ考慮事項

### データベース認証情報

**注意**: `DEPLOY.md` にGCP Cloud SQLの認証情報が平文で記載されている

**推奨対応**:
- [ ] 環境変数に移行
- [ ] DEPLOY.md から認証情報を削除
- [ ] .env.example を作成

### XSS対策

**現状確認**: SmartTemplate でのエスケープ処理

**要確認箇所**:
- ユーザー入力値の表示
- お知らせ・コーナー名の表示
- エラーメッセージの表示

---

## 📞 連絡事項

### ユーザーへの確認事項

1. **営業時間設定**: DB管理化の実装方法確認
2. **管理画面動作**: デプロイ後の動作確認結果報告
3. **Git リポジトリ**: 構造改善の実施タイミング

---

作成日: 2025-11-15
作成者: Claude Code
プロジェクト: NET8 (MillionNet8)
バージョン: 1.0
