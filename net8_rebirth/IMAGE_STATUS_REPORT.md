# 📸 NET8 画像システム調査レポート

**作成日時**: 2024年12月12日 08:15 JST

---

## 🔍 調査結果サマリー

### 画像の現状

| 項目 | 状態 | 詳細 |
|------|------|------|
| **画像ファイル保存場所** | ✅ ローカルに存在 | `/data/img/model/` ディレクトリ |
| **本番環境アクセス** | ✅ アクセス可能 | HTTPステータス200で正常応答 |
| **GCP移行状況** | ⚠️ 準備済みだが未使用 | CloudStorageHelper.phpは実装済み |
| **画像表示問題** | 🔍 調査中 | CSSまたはHTML構造の問題の可能性 |

---

## 📂 画像ファイル構成

### 1. 機種画像 (`/data/img/model/`)
```
✅ hokuto4go.jpg      - 26KB  - 北斗の拳4号機
✅ jagger01.jpg       - 308KB - ジャガー
✅ milliongod_gaisen.jpg - 57KB - ミリオンゴッド凱旋
✅ yoshimune.png      - 1MB   - 吉宗
✅ zenigata.jpg       - 72KB  - 銭形
```

### 2. トップページ画像 (`/content/images/index/`)
```
✅ top.jpg        - 912KB - メインビジュアル
✅ madomagi.png    - 821KB - まどマギ
✅ osubancyo.png   - 763KB - お坊ちゃん
✅ top_image.png   - 2.3MB - トップ画像（大）
✅ yosimune.png    - 642KB - 吉宗
```

---

## 🌐 GCP Cloud Storage 設定

### 現在の設定状態
```php
// setting.php より
define('GCS_ENABLED', false);  // 現在無効
define('GCS_PROJECT_ID', 'avamodb');
define('GCS_BUCKET_NAME', 'avamodb-net8-images');
```

### CloudStorageHelper クラス
- ✅ アップロード機能実装済み
- ✅ サムネイル生成機能あり
- ✅ 削除・存在確認機能あり
- ⚠️ GCS_ENABLEDがfalseのため未使用

---

## 🖼️ 画像表示の仕組み

### HTMLテンプレートでの画像参照
```html
<!-- index.html より -->
<img src="{%DIR_IMG_MODEL_DIR%}{%IMAGE_LIST%}" alt="{%MODEL_NAME%}">
```

### PHPでの変数展開
```php
// TemplateUser.php より
$this->assign("DIR_IMG_MODEL_DIR", '/data/img/model/');
$this->assign("IMAGE_LIST", $imageList);
```

---

## 🎮 管理画面での画像管理

### 機種画像アップロード (`/admin/model_detail.html`)
1. **アップロード先**: `/img/base/` ディレクトリ
2. **対応形式**: JPG, PNG, GIF, WEBP
3. **アップロード方法**: 
   - 管理画面から直接アップロード
   - ファイル選択後、自動でプレビュー表示

### アクセス方法
```
URL: https://net8games.win/admin/
機能: 機種管理 → 機種詳細 → リスト画像/詳細画像アップロード
```

---

## 🔧 画像が表示されない問題の対処法

### 1. トップ画像が潰れている問題

**原因**: CSSで高さが固定されている可能性
```css
#index-visual {
    height: 60vh;  /* この設定が画像を潰している可能性 */
}
```

**解決策**: 
```css
#index-visual {
    position: relative;
    overflow: hidden;
    /* heightを削除してaspect-ratioを使用 */
}

#index-visual img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover; /* または contain */
}
```

### 2. 機種画像が表示されない問題

**確認ポイント**:
1. データベースの`image_list`カラムに正しいファイル名が入っているか
2. PHPの変数展開が正しく動作しているか
3. 画像ファイルが実際に存在するか

---

## 📝 推奨アクション

### 短期対策（即座に実施可能）
1. **CSS修正**: トップ画像の高さ設定を調整
2. **画像パス確認**: データベースの画像パスを確認
3. **キャッシュクリア**: ブラウザとCDNのキャッシュをクリア

### 中期対策（計画的に実施）
1. **GCS移行検討**: 
   - 画像配信の高速化
   - 自動サムネイル生成
   - CDN統合

2. **画像最適化**:
   - WebP形式への変換
   - レスポンシブ画像の実装
   - 遅延読み込みの導入

---

## 🚀 管理画面での画像設定手順

### 新規画像アップロード
1. 管理画面にログイン: `https://net8games.win/admin/`
2. サイドメニューから「機種管理」を選択
3. 対象機種の「詳細」ボタンをクリック
4. 「リスト画像」または「詳細画像」セクションで「ファイルを選択」
5. 画像を選択してアップロード
6. 「更新」ボタンで保存

### 既存画像の変更
1. 同じ手順で機種詳細画面へ
2. 現在の画像の下にある「新しい画像を選択」
3. 新しい画像をアップロード
4. 自動的に古い画像と置き換わる

---

## 📊 技術詳細

### 画像処理フロー
```
1. ユーザーアップロード
   ↓
2. /img/base/ に保存
   ↓
3. データベースにファイル名記録
   ↓
4. PHPテンプレートで変数展開
   ↓
5. HTMLに画像パス出力
```

### 今後のGCS移行時のフロー
```
1. ユーザーアップロード
   ↓
2. CloudStorageHelper::upload()
   ↓
3. GCSに保存 + CDN URL生成
   ↓
4. データベースにCDN URLを記録
   ↓
5. 高速配信
```

---

## ✅ まとめ

1. **画像ファイルは正常に存在し、アクセス可能**
2. **管理画面から画像の追加・変更が可能**
3. **GCS移行の準備は整っているが未実施**
4. **表示問題はCSS調整で解決可能**

ご不明な点があれば、お知らせください。