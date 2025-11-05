# NET8 プロジェクト - 現在の状況（ミリオンゴッド登録作業）

**最終更新**: 2025-11-06 03:35 JST
**最終コミット**: `9480bdb` - fix: Remove non-existent category_no column from api_check_models
**本番URL**: https://mgg-webservice-production.up.railway.app/

---

## ✅ 完了した作業

### 1. play_v2ページのエラー修正（コミット: 0cb896e, 9480bdb）

**問題**:
- play_v2ページで `reload_error.html` が見つからないエラー
- ユーザーが3秒以内に連続アクセスした場合のエラーページが存在しない

**解決策**:
- `/02.ソースファイル/net8_html/_html/ja/play/reload_error.html` を作成
- 自動リトライ機能付きのエラーページを実装
- 本番デプロイ完了

**ファイル**:
- `02.ソースファイル/net8_html/_html/ja/play/reload_error.html`
- `02.ソースファイル/net8_html/data/api_check_models.php`

### 2. 機種マスタ確認API作成

**作成したAPI**: `/data/api_check_models.php`

**機能**:
- 全機種一覧取得
- キーワード検索（例: `?keyword=ミリオンゴッド`）
- JSON形式で返却

**使用例**:
```bash
curl "https://mgg-webservice-production.up.railway.app/data/api_check_models.php"
curl "https://mgg-webservice-production.up.railway.app/data/api_check_models.php?keyword=ミリオンゴッド"
```

**レスポンス例**:
```json
{
    "success": true,
    "models": [
        {
            "model_no": 1,
            "model_name": "北斗の拳　初号機",
            "model_roman": "hokutonoken No1",
            "maker_no": 17,
            "image_list": "hokuto4go.jpg"
        },
        {
            "model_no": 2,
            "model_name": "主役は銭形",
            "model_roman": "Mr.ZENIGATA",
            "maker_no": 13,
            "image_list": "34848389ba434438e685dc4d35adf8370a90d297.jpg"
        }
    ],
    "count": 2,
    "total": 2
}
```

---

## 🔄 進行中の作業

### ミリオンゴッドの機種登録（70%完了）

**目的**: マシン3を「ミリオンゴッド～神々の凱旋～」として登録・稼働

**判明した情報**:
- **メーカー**: ユニバーサルエンターテインメント（maker_no: **86**）
- **カテゴリー**: スロット（category: **2**）
- **機種名**: ミリオンゴッド～神々の凱旋～
- **ローマ字**: MILLION GOD KAMIGAMI NO GAISEN
- **画像**: milliongod_gaisen.jpg

**DBスキーマ** (mst_model):
```sql
CREATE TABLE mst_model (
    model_no INT AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(255),
    model_roman VARCHAR(255),
    maker_no INT,
    category INT NOT NULL,  -- 1:パチンコ, 2:スロット
    image_list VARCHAR(255),
    add_dt DATETIME,
    upd_dt DATETIME
);
```

**既存データ**:
```
model_no | model_name          | maker_no | category
---------|---------------------|----------|----------
1        | 北斗の拳　初号機    | 17       | 2 (slot)
2        | 主役は銭形          | 13       | 2 (slot)
```

**メーカーマスタ（抜粋）**:
```
maker_no | maker_name
---------|----------------------------------
13       | オリンピア
17       | サミー
86       | ユニバーサルエンターテインメント
```

---

## 📋 次のステップ（優先順）

### 1. ミリオンゴッドをmst_modelに登録

**SQL実行** (`/net8/insert_milliongod.sql`):
```sql
INSERT INTO mst_model (
    model_name,
    model_roman,
    maker_no,
    category,
    image_list,
    add_dt,
    upd_dt
) VALUES (
    'ミリオンゴッド～神々の凱旋～',
    'MILLION GOD KAMIGAMI NO GAISEN',
    86,
    2,
    'milliongod_gaisen.jpg',
    NOW(),
    NOW()
);
```

**実行方法**:

#### A. ローカルでテスト:
```bash
cd "/net8/02.ソースファイル/net8_html"
php -r "
require_once('./_etc/require_files.php');
\$pdo = get_db_connection();
\$pdo->exec(\"INSERT INTO mst_model (model_name, model_roman, maker_no, category, image_list, add_dt, upd_dt) VALUES ('ミリオンゴッド～神々の凱旋～', 'MILLION GOD KAMIGAMI NO GAISEN', 86, 2, 'milliongod_gaisen.jpg', NOW(), NOW())\");
echo \"✅ 登録完了\n\";
"
```

#### B. 本番DBで実行（ローカルテスト後）:
APIを作成して実行するか、Railway経由で直接SQL実行

**確認SQL**:
```sql
SELECT model_no, model_name, maker_no, category
FROM mst_model
ORDER BY model_no;
```

---

### 2. マシン3をミリオンゴッドに変更

**現在のマシン3の状態**:
```json
{
    "machine_no": 3,
    "machine_cd": "HOKUTO002",
    "model_no": 1,  // ← 北斗の拳（変更が必要）
    "model_name": "北斗の拳　初号機",
    "machine_status": 1,
    "release_date": "2025-11-03"
}
```

**更新SQL**:
```sql
-- ステップ1で登録したmodel_noを使用（おそらく3）
UPDATE dat_machine
SET
    model_no = 3,  -- ミリオンゴッドのmodel_no
    machine_cd = 'MILLIONGOD001',  -- 適切なコードに変更
    upd_dt = NOW()
WHERE machine_no = 3;
```

**確認**:
```bash
curl "https://mgg-webservice-production.up.railway.app/data/api_check_machine.php?machine_no=3"
```

---

### 3. カメラとの紐付け確認

**現状**:
- カメラ#3は稼働中（Railwayログで確認）
- `dat_camera`テーブルが存在しないか、データが未登録

**Railwayログでの確認内容**:
```
GET /api/cameraAPI.php?M=reset&MACHINE_NO=3&ts=1762362926798
GET /api/cameraAPI.php?M=end&MACHINE_NO=3&ts=1762362927809
```
→ カメラは定期的にreset/end信号を送信中

**確認が必要な項目**:
- [ ] dat_cameraテーブルにマシン3のカメラ情報が登録されているか
- [ ] カメラのURLやstreamキーが正しいか
- [ ] play_v2ページでカメラ映像が表示されるか

**テーブル構造** (推定):
```sql
CREATE TABLE dat_camera (
    camera_id INT AUTO_INCREMENT PRIMARY KEY,
    machine_no INT,
    camera_name VARCHAR(255),
    camera_url VARCHAR(255),
    status INT
);
```

---

## 🔧 トラブルシューティング

### エラー: "ログインページにアクセスできない"
**原因**: 不明（ユーザー環境の問題かもしれない）
**本番環境での確認**: HTTP 200 OK（正常動作）

### エラー: "Field 'category' doesn't have a default value"
**原因**: mst_modelテーブルのcategoryフィールドは必須
**解決**: category=2（スロット）を明示的に指定

---

## 📁 重要ファイル一覧

### 作成・修正済みファイル
```
02.ソースファイル/net8_html/
├── _html/ja/play/
│   └── reload_error.html               # NEW: エラーページ
├── data/
│   ├── api_check_models.php            # NEW: 機種マスタAPI
│   ├── api_check_machine.php           # 既存: マシン情報API
│   ├── api_update_image.php            # 既存: 画像更新API
│   ├── login.php                       # ログイン処理
│   ├── search.php                      # 機種検索ページ
│   └── play_v2/
│       └── index.php                   # プレイページ（reload_error.html使用）
└── _etc/
    ├── setting_base.php                # 設定ファイル
    └── require_files.php               # 共通インクルード

net8/
├── insert_milliongod.sql               # NEW: SQL（未実行）
└── CURRENT_STATUS_MILLIONGOD.md        # NEW: この記録ファイル
```

### SQLファイル（参考）
```
net8/
├── insert_hokuto_model.sql             # 北斗の拳登録SQL
├── insert_sample_members.sql           # テストメンバー登録SQL
├── test_member_insert.sql              # テストメンバー登録SQL#2
└── insert_milliongod.sql               # ミリオンゴッド登録SQL（未実行）
```

---

## 🌐 本番環境情報

**Railway プロジェクト**: mgg-webservice-production
**URL**: https://mgg-webservice-production.up.railway.app/
**DB接続**: GCP Cloud SQL (136.116.70.86:3306, net8_dev)

**最新デプロイ**:
- コミット: `9480bdb`
- ブランチ: main
- デプロイ日時: 2025-11-06 03:20頃

**テストアカウント**:
- やまちゃん: `test1@example.com` / `admin123`
- はなちゃん: `test2@example.com` / `admin123`
- いっちゃん: `test3@example.com` / `admin123`
- テストユーザー: `test@admin.com` / `password123`

---

## 🎯 最終ゴール

**目標**: マシン3（ミリオンゴッド）でログイン→カメラ接続→プレイまで動作確認

**チェックリスト**:
- [ ] ミリオンゴッドをmst_modelに登録
- [ ] マシン3をmodel_no=3（ミリオンゴッド）に更新
- [ ] 管理画面でマシン3が「ミリオンゴッド」として表示されるか確認
- [ ] トップページ/検索ページでミリオンゴッドが表示されるか確認
- [ ] ログイン後、マシン3を選択してplay_v2ページにアクセス
- [ ] カメラ映像が表示されるか確認
- [ ] ゲームプレイ機能が動作するか確認

---

## 🚨 注意事項

1. **デプロイ前に必ずローカルでテスト**
   - ユーザーから「ローカルでテストしてからあげて」と指示あり
   - 特にSQL実行は慎重に

2. **PeerJSサーバーが停止中**
   - hooksで確認済み: "PeerJSサーバー: 停止中"
   - WebRTC通信に影響する可能性

3. **画像ファイル**
   - milliongod_gaisen.jpg は `/data/img/model/` に配置必要
   - 画像がない場合はデフォルト画像かプレースホルダーを使用

4. **カテゴリー値**
   - 1: パチンコ
   - 2: スロット
   - 必須フィールド

---

## 📞 次回の継続方法

1. このファイルを読んで状況確認
2. 「次のステップ」セクションの手順1から実行
3. ローカルでテスト→本番デプロイの順で進める
4. 各ステップ完了後にチェックリストを更新

**コマンド例**（次回最初に実行）:
```bash
cd /Users/kotarokashiwai/net8_rebirth/net8
cat CURRENT_STATUS_MILLIONGOD.md
```
