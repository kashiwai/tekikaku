# ミリオンゴッド（マシン3）準備完了 - Windows側待機中

**最終更新**: 2025-11-06 05:20 JST
**ステータス**: ✅ サーバー側準備完了 / ⏳ Windows側カメラ待機中

---

## ✅ 完了した作業

### 1. ミリオンゴッド機種登録（本番DB）
```json
{
  "model_no": 3,
  "model_cd": "MILLIONGOD01",
  "model_name": "ミリオンゴッド～神々の凱旋～",
  "model_roman": "MILLION GOD KAMIGAMI NO GAISEN",
  "maker_no": 86,
  "category": 2
}
```

### 2. マシン3の変更（本番DB）
**変更前**:
- machine_cd: HOKUTO002
- model_no: 1（北斗の拳）

**変更後**:
- machine_cd: **MILLIONGOD01**
- model_no: **3（ミリオンゴッド）**
- upd_dt: 2025-11-05 20:22:47

### 3. 画像ファイル配置（本番環境）
全て `/data/img/model/` に配置済み：
- ✅ hokuto4go.jpg（北斗の拳）- 26KB
- ✅ milliongod_gaisen.jpg（ミリオンゴッド）- 306KB
- ✅ zenigata.jpg（銭形）- 71KB

**確認済み**: 全ファイル HTTP 200 OK

### 4. 表示確認（本番環境）
**検索ページ**: https://mgg-webservice-production.up.railway.app/data/search.php
```html
<img src="/data/img/model/milliongod_gaisen.jpg" class="machine-panel" alt="ミリオンゴッド4号機">
```
✅ ミリオンゴッドが正しく表示されています

---

## ⏳ 待機中の作業

### Windows側カメラシステムの起動

**現在の状況**:
- カメラ#3は過去にAPIリクエストを送信していた（Railwayログで確認）
  ```
  GET /api/cameraAPI.php?M=reset&MACHINE_NO=3
  GET /api/cameraAPI.php?M=end&MACHINE_NO=3
  ```
- しかし、現在はWindows側が停止中

**dat_cameraテーブルの状態**:
```json
{
  "cameras": [],
  "camera_count": 0,
  "note": "Camera table not available or no cameras registered"
}
```

---

## 📋 Windows側起動後のテスト手順

### ステップ1: ログイン
**URL**: https://mgg-webservice-production.up.railway.app/data/login.php

**テストアカウント**:
- **test1@example.com** / **admin123**
- test2@example.com / admin123
- test3@example.com / admin123
- test@admin.com / password123

### ステップ2: マシン3（ミリオンゴッド）を選択

**方法A**: 検索ページから
1. https://mgg-webservice-production.up.railway.app/data/search.php
2. 「ミリオンゴッド～神々の凱旋～」をクリック

**方法B**: 直接プレイページにアクセス
- https://mgg-webservice-production.up.railway.app/data/play_v2/?NO=3

### ステップ3: 確認項目

#### ✓ プレイページが開くか
- [ ] ページが正常に表示される
- [ ] エラーメッセージが出ない

#### ✓ カメラ映像が表示されるか
- [ ] WebRTC接続が確立される
- [ ] カメラの映像が表示される
- [ ] 映像が正常に動いている

#### ✓ ゲーム操作ができるか
- [ ] スタートボタンが機能する
- [ ] ゲームが正常に動作する
- [ ] クレジット表示が正しい

---

## 🔧 トラブルシューティング

### エラー: カメラ映像が表示されない

**考えられる原因**:
1. dat_cameraテーブルにマシン3のカメラ情報が未登録
2. WebRTC接続エラー
3. PeerJSサーバーが停止中（現在停止中と確認済み）

**解決策**:
1. PeerJSサーバーを起動する
2. dat_cameraテーブルにカメラ情報を登録する

### エラー: reload_error.html が表示される

**原因**: 3秒以内に連続アクセスした
**解決**: 3秒待ってから再アクセス（自動リトライ機能あり）

### エラー: ログインできない

**確認事項**:
- メールアドレスとパスワードが正しいか
- アカウントのstate=1（有効）になっているか

---

## 🌐 本番環境情報

**URL**: https://mgg-webservice-production.up.railway.app/
**デプロイ**: Railway (自動デプロイ設定済み)
**DB**: GCP Cloud SQL (136.116.70.86:3306, net8_dev)

**最新コミット**: `ebb4030` - feat: Add direct script to update machine 3 to Million God

**デプロイ済みファイル**:
- api_register_milliongod.php
- api_update_machine3.php
- update_machine3_direct.php
- api_check_models.php
- api_check_machine.php
- reload_error.html
- milliongod_gaisen.jpg
- zenigata.jpg

---

## 🚨 重要な注意事項

### PeerJSサーバーが停止中

**現状**: hooksで確認済み - "PeerJSサーバー: 停止中"

**影響**:
- WebRTC通信ができない可能性
- カメラ映像が表示されない可能性

**対応**: Windows側起動後、PeerJSサーバーも起動する必要があるかもしれません

---

## 📞 次回の継続方法

**Windows側が起動したら**:

1. このファイルを確認:
   ```bash
   cat /Users/kotarokashiwai/net8_rebirth/net8/MILLIONGOD_READY_STATUS.md
   ```

2. テスト手順に従ってプレイテストを実施

3. 問題があれば「トラブルシューティング」セクションを参照

4. 全て正常に動作すれば完了！

---

**準備完了！Windows側の起動をお待ちしています。**
