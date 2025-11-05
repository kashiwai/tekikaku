# Railway完全セットアップガイド（DB初期化版）

## 🚀 最新版：ワンクリックセットアップ対応！

**おすすめ方法:**
```
https://mgg-webservice-production.up.railway.app/xxxadmin/auto_setup.php
```
↑ このURL1つで全STEP自動実行！（STEP 1-6を一括処理）

---

## 概要
Railwayデプロイ後、DBが完全にリセットされるため、以下のいずれかの方法でセットアップしてください。

---

## 📋 完全セットアップURL（STEP 1-7）

### ⚠️ 重要な注意事項
1. **必ずSTEP 1から順番に実行**してください（スキップ厳禁）
2. 各STEPで「✅」マークが表示されることを確認
3. エラーが出た場合は次のSTEPに進まない

---

### **STEP 1: 基本セットアップ**
```
https://mgg-webservice-production.up.railway.app/complete_setup.php
```

**実行内容:**
- mst_model（機種マスタ）に北斗の拳4号機を登録
- mst_maker（メーカーマスタ）にSANKYOを登録
- mst_type（タイプマスタ）にスロットを登録
- mst_unit（ユニット/号機マスタ）に4号機を登録
- mst_admin（管理者アカウント）を作成（ID: admin, PASS: admin123）

**期待される表示:**
- ✅ セットアップ完了
- 各テーブルに登録件数が表示される

---

### **STEP 2: ポイント変換テーブル確認**
```
https://mgg-webservice-production.up.railway.app/check_convertpoint_table.php
```

**実行内容:**
- mst_convertPointテーブルにサンプルデータを自動登録
  - convert_no = 1
  - point = 1

**期待される表示:**
- ✅ サンプルデータを登録しました（convert_no=1, point=1）
- または「登録件数: 1件」

---

### **STEP 3: カメラマスター登録**
```
https://mgg-webservice-production.up.railway.app/register_camera.php
```

**実行内容:**
- mst_cameraテーブルに3台のカメラを登録
  - camera_no: 1, MAC: 00:00:00:00:00:01, 名前: HOKUTO_CAMERA_1
  - camera_no: 2, MAC: 00:00:00:00:00:02, 名前: HOKUTO_CAMERA_2
  - camera_no: 3, MAC: 00:00:00:00:00:03, 名前: HOKUTO_CAMERA_3

**期待される表示:**
- ✅ カメラ3台の登録完了

---

### **STEP 4: 実機完全登録（machine_corner対応版）**
```
https://mgg-webservice-production.up.railway.app/register_machine_complete.php
```

**実行内容:**
- dat_machine（実機マスタ）に3台を登録
  - HOKUTO001: camera_no=1, signaling_id=PEER001
  - HOKUTO002: camera_no=2, signaling_id=PEER002
  - HOKUTO003: camera_no=3, signaling_id=PEER003
  - **machine_corner='1'を設定（重要！）**
  - machine_status=1（稼働中）
- dat_machinePlay（実機プレイデータ）に3台分を登録
- lnk_machine（実機接続状況）に3台分を登録

**期待される表示:**
- ✅ 新規登録: 3台（または「更新: 3台」）
- 北斗の拳の実機総数: 3台（稼働中: 3台）
- テーブルに3台の詳細が表示される

---

### **STEP 5: 北斗の拳 画像パス登録（NEW）**
```
https://mgg-webservice-production.up.railway.app/update_hokuto_image.php
```

**実行内容:**
- mst_modelテーブルの北斗の拳レコードを更新
  - image_list = 'img/model/hokuto4go.jpg'（リスト用画像パス）

**期待される表示:**
- ✅ 画像パス更新完了
- 設定した画像パス: img/model/hokuto4go.jpg
- 画像プレビューが表示される（北斗の拳の画像）

---

### **STEP 6: デバッグSQL確認（重要）**
```
https://mgg-webservice-production.up.railway.app/debug_index_sql.php
```

**実行内容:**
- index.phpと同じSQLを実行して、データが正しく取得できるか確認

**期待される表示:**
- ✅ 実行結果
- **取得件数: 3件** ← これが重要！
- テーブルに3台の詳細データが表示される
- ✅ 結論: SQLは正常に実行され、3件のデータが取得できています。

**❌ エラーの場合:**
- 「取得件数: 0件」→ STEP 4の実行を確認
- SQL実行エラー → STEP 2の実行を確認

---

### **STEP 7: トップページ確認（最終確認）**
```
https://mgg-webservice-production.up.railway.app/
```

**期待される表示:**
- 北斗の拳が**3台表示される**
- **北斗の拳の画像が表示される**
- 機種名、稼働状況などが正しく表示される

**❌ 表示されない場合:**
1. ブラウザのキャッシュをクリア（Ctrl+Shift+R / Cmd+Shift+R）
2. URLに `?CN=` パラメータがついていないか確認
3. STEP 6で「取得件数: 3件」だったか確認

---

## 🔧 トラブルシューティング

### 問題1: STEP 6で「取得件数: 0件」
**原因:**
- machine_cornerがNULL
- FIND_IN_SET()がマッチしない

**解決策:**
1. STEP 4を再実行
2. register_machine_complete.phpでmachine_corner='1'が設定されているか確認

---

### 問題2: STEP 7でトップページに台が表示されない
**原因:**
- SQLは成功しているが、テンプレート表示に問題
- キャッシュの問題
- CNパラメータの問題

**解決策:**
1. ブラウザのキャッシュをクリア
2. URLから `?CN=` パラメータを削除
3. debug_index_sql.phpで3件取得できているか再確認

---

### 問題3: 画像が表示されない
**原因:**
- model_img_pathが未設定
- 画像ファイルがデプロイされていない

**解決策:**
1. STEP 5を再実行
2. update_hokuto_image.phpで画像プレビューが表示されるか確認
3. 画像ファイル（hokuto4go.jpg）がデプロイされているか確認

---

## 📊 各STEP完了後の状態

| STEP | テーブル | 登録件数 | 備考 |
|------|---------|---------|------|
| 1 | mst_model | 1件 | 北斗の拳4号機 |
| 1 | mst_maker | 1件 | SANKYO |
| 1 | mst_type | 1件 | スロット |
| 1 | mst_unit | 1件 | 4号機 |
| 1 | mst_admin | 1件 | admin/admin123 |
| 2 | mst_convertPoint | 1件 | convert_no=1, point=1 |
| 3 | mst_camera | 3件 | カメラ1-3 |
| 4 | dat_machine | 3件 | HOKUTO001-003, machine_corner='1' |
| 4 | dat_machinePlay | 3件 | プレイデータ初期化 |
| 4 | lnk_machine | 3件 | 接続状況 |
| 5 | mst_model | 1件（更新） | image_list設定 |

---

## 🎯 重要なポイント

### machine_corner='1' の重要性
- トップページのURLに `?CN=1` パラメータがある場合、FIND_IN_SET()でフィルタリングされる
- machine_cornerがNULLの場合、FIND_IN_SET()はマッチしない
- register_machine_complete.phpで必ず '1' を設定

### mst_convertPointの重要性
- dat_machineとINNER JOINされる
- テーブルが空の場合、JOINが失敗して0件になる
- convert_no=1のレコードが必須

### カメラマスターの重要性
- dat_machine.camera_noに対応するmst_cameraレコードが必要
- 管理画面で「カメラサーバーが選択されていない」エラーを防ぐ

---

## 📝 次回デプロイ時の手順

1. Railwayデプロイ完了を待つ（2-3分）
2. このガイドのSTEP 1から順番に実行
3. STEP 7でトップページに北斗の拳が3台表示されることを確認
4. 画像が表示されることを確認

---

## 🔗 関連ファイル

- **画像ファイル:** `02.ソースファイル/net8_html/data/img/model/hokuto4go.jpg`
- **セットアップスクリプト:** `02.ソースファイル/net8_html/complete_setup.php`
- **カメラ登録:** `02.ソースファイル/net8_html/register_camera.php`
- **実機登録:** `02.ソースファイル/net8_html/register_machine_complete.php`
- **画像パス登録:** `02.ソースファイル/net8_html/update_hokuto_image.php`
- **デバッグSQL:** `02.ソースファイル/net8_html/debug_index_sql.php`

---

## 📅 作成日時
2025-11-01

## 📌 バージョン
v1.0 - 北斗の拳画像対応版
