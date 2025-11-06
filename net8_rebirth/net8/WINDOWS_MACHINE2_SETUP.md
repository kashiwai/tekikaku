# Windows PC セットアップ手順 - マシン2（銭形）

**最終更新**: 2025-11-06 10:30 JST
**対象マシン**: マシン2（銭形 - 主役は銭形）
**MACアドレス**: E0-51-D8-16-13-3D
**ネットワークアダプタ**: Realtek PCIe GbE Family Controller

---

## 🎯 概要

Windows PCを再起動後、カメラシステムを起動してマシン2に接続するための手順です。

---

## ✅ 事前確認

### 必要な情報
- **サーバーURL**: https://mgg-webservice-production.up.railway.app
- **マシン番号**: 2
- **機種名**: 主役は銭形
- **MACアドレス**: E0-51-D8-16-13-3D
- **slotserver.ini**: 既に正しく設定済み（変更不要）

### 動作確認済みの環境
- ✅ PeerJSシグナリングサーバー: 正常稼働中
- ✅ Webサーバー: 正常稼働中
- ✅ データベース: GCP Cloud SQL接続済み

---

## 📋 Windows側セットアップ手順

### ステップ1: カメラシステムの起動

#### 方法A: server_v2フォルダから起動（推奨）

1. **フォルダを開く**
   ```
   C:\path\to\server_v2\
   ```

2. **index.htmlをChromeで開く**
   - `index.html` をダブルクリック
   - または Chrome で直接開く

3. **URLパラメータを確認**
   ```
   https://mgg-webservice-production.up.railway.app/server_v2/?MAC=E0-51-D8-16-13-3D
   ```
   ※ MACアドレスが正しいことを確認

#### 方法B: 直接URLアクセス（推奨）

Chromeのアドレスバーに以下を入力：
```
https://mgg-webservice-production.up.railway.app/server_v2/?MAC=E0-51-D8-16-13-3D
```

---

### ステップ2: カメラシステムの初期設定

1. **カメラへのアクセス許可**
   - ブラウザがカメラへのアクセスを求めたら「許可」をクリック

2. **マシン番号の設定**
   - 画面に表示される設定画面で「マシン番号: 2」を選択
   - または自動的にマシン2に接続される

3. **接続確認**
   - カメラ映像が表示されることを確認
   - 「接続中」または「オンライン」ステータスが表示される

---

### ステップ3: 動作確認

#### ✓ カメラシステムが正常に動作しているか確認

以下のいずれかの方法で確認：

**確認方法1: Railwayログで確認**
```
GET /api/cameraAPI.php?M=reset&MACHINE_NO=2
GET /api/cameraAPI.php?M=end&MACHINE_NO=2
```
上記のようなログが60秒ごとに表示されればOK

**確認方法2: 画面上で確認**
- カメラ映像が表示されている
- ステータスが「オンライン」または「接続中」
- エラーメッセージが出ていない

---

## 🖥️ Mac側からのテスト手順

Windows側のセットアップが完了したら、Mac側から以下の手順でテストします：

### 1. ログイン
**URL**: https://mgg-webservice-production.up.railway.app/data/login.php

**テストアカウント**:
- メールアドレス: `test1@example.com`
- パスワード: `admin123`

### 2. マシン2を選択

**方法A**: 検索ページから選択
1. https://mgg-webservice-production.up.railway.app/data/search.php
2. 「主役は銭形」のカードをクリック
3. 「プレイする」ボタンをクリック

**方法B**: 直接プレイページにアクセス
```
https://mgg-webservice-production.up.railway.app/data/play_v2/?NO=2
```

### 3. 確認項目

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

**原因と解決策**:

1. **カメラのアクセス許可がない**
   - Chromeの設定 → プライバシーとセキュリティ → サイトの設定 → カメラ
   - mgg-webservice-production.up.railway.app を「許可」に変更

2. **MACアドレスが間違っている**
   - URLの `MAC=` パラメータを確認
   - 正しいMACアドレス: `E0-51-D8-16-13-3D`

3. **WebRTC接続エラー**
   - PeerJSシグナリングサーバーの状態を確認
   - https://mgg-signaling-production-c1bd.up.railway.app/ にアクセスして応答があるか確認

### エラー: "reload_error.html" が表示される

**原因**: 3秒以内に連続アクセスした
**解決**: 3秒待ってから再アクセス（自動リトライ機能あり）

### エラー: ログインできない

**確認事項**:
- メールアドレスとパスワードが正しいか
- test1@example.com / admin123 を使用しているか

---

## 📞 サポート情報

### 重要なURL

- **本番環境**: https://mgg-webservice-production.up.railway.app/
- **ログインページ**: https://mgg-webservice-production.up.railway.app/data/login.php
- **検索ページ**: https://mgg-webservice-production.up.railway.app/data/search.php
- **マシン2プレイ**: https://mgg-webservice-production.up.railway.app/data/play_v2/?NO=2
- **カメラシステム**: https://mgg-webservice-production.up.railway.app/server_v2/?MAC=E0-51-D8-16-13-3D
- **PeerJSサーバー**: https://mgg-signaling-production-c1bd.up.railway.app/

### システム情報

- **データベース**: GCP Cloud SQL (136.116.70.86:3306, net8_dev)
- **シグナリングサーバー**: mgg-signaling-production-c1bd.up.railway.app:443
- **PeerJS APIキー**: peerjs

---

## 🚨 重要な注意事項

1. **カメラアクセスの許可は必須**
   - 初回アクセス時に必ず「許可」をクリックしてください

2. **MACアドレスは固定**
   - マシン2のMACアドレスは `E0-51-D8-16-13-3D` です
   - 変更しないでください

3. **slotserver.iniファイル**
   - 既に正しく設定済みです
   - 変更する必要はありません

4. **連続アクセスに注意**
   - 3秒以内の連続アクセスはエラーになります
   - リロード時は3秒待ってください

---

## ✅ セットアップ完了の確認

以下が全て確認できれば、セットアップ完了です：

- [ ] Windows側でカメラシステムが起動している
- [ ] カメラ映像がブラウザに表示されている
- [ ] Railwayログに `/api/cameraAPI.php?M=reset&MACHINE_NO=2` が表示されている
- [ ] Mac側からログインできる
- [ ] Mac側でマシン2の検索結果が表示される
- [ ] Mac側でプレイページが開く
- [ ] Mac側でカメラ映像が表示される

---

**準備完了！Windows側のカメラシステムを起動してください。**
