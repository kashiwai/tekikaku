# NET8 ローカルPC接続セットアップガイド

このガイドでは、ローカルPCとNET8サーバー間のWebRTC接続を設定する手順を説明します。

## 📋 目次

1. [システム構成](#システム構成)
2. [前提条件](#前提条件)
3. [サーバー側セットアップ](#サーバー側セットアップ)
4. [ローカルPC側セットアップ](#ローカルpc側セットアップ)
5. [接続テスト手順](#接続テスト手順)
6. [トラブルシューティング](#トラブルシューティング)

---

## システム構成

### 全体アーキテクチャ

```
[ローカルPC (カメラ側)]
    │
    ├─ 1. カメラ番号取得
    │    └→ GET http://localhost:8080/api/cameraListAPI.php?M=getno&MAC={MAC}&ID={LICENSE}
    │
    ├─ 2. WebRTC配信開始
    │    └→ http://localhost:8080/server_v2/?MAC={MAC}
    │         │
    │         └→ PeerJS Signaling: localhost:59000
    │
    └─ 3. マシンコントロール (将来実装)
         └→ WebSocket: ws://localhost:59777/ws
```

### 通信フロー

1. **初期化**: ローカルPCがAPIでカメラ番号を取得
2. **WebRTC接続**: PeerJSシグナリングサーバー経由でP2P接続確立
3. **映像配信**: カメラ映像をWebRTCでストリーミング
4. **制御信号**: WebSocketでマシンコントロール信号を送受信

---

## 前提条件

### サーバー側

- ✅ Docker環境が起動していること
- ✅ 以下のサービスが稼働中:
  - Web (Apache + PHP): `localhost:8080`
  - Database (MySQL): `localhost:3306`
  - Signaling (PeerJS): `localhost:59000`

### ローカルPC側

- Windows 10/11
- Webカメラまたはビデオキャプチャデバイス
- ネットワーク接続（サーバーと同じLAN内）

### 確認コマンド

```bash
# Docker環境の確認
docker-compose ps

# サーバーの動作確認
curl http://localhost:8080
curl http://localhost:59000/  # PeerJS Server
```

---

## サーバー側セットアップ

### 1. Docker環境の起動

```bash
cd /Users/kotarokashiwai/net8_rebirth/net8
docker-compose up -d
```

### 2. 設定の確認

以下のファイルが正しく設定されていることを確認：

#### `/02.ソースファイル/net8_html/_etc/setting_base.php`

```php
// RTC Signaling Servers設定
$GLOBALS["RTC_Signaling_Servers"] = array(
    "default" => "localhost:59000",
    "1" => "localhost:59000",
    "2" => "localhost:59000"
);

// WebRTC関連定数
$GLOBALS["RTC_PEER_APIKEY"] = "peerjs";
```

#### `/02.ソースファイル/net8_html/_etc/webRTC_setting.php`

```php
// ICE Servers設定
$GLOBALS["ICE_SERVERS"] = array(
    array('urls' => 'stun:stun.l.google.com:19302')
);
```

### 3. テストデータの確認

データベースにテスト用カメラデータが登録されていることを確認：

```bash
docker-compose exec db mysql -u net8user -pnet8pass net8_dev -e "SELECT * FROM mst_cameralist WHERE mac_address = '00:00:00:00:00:01';"
```

**期待される結果:**
```
mac_address: 00:00:00:00:00:01
license_id: IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
camera_no: 1
```

---

## ローカルPC側セットアップ

### 1. プログラムファイルの配置

```
C:\serverset\
├── camera_ctrl.exe
├── chromeCameraV2.exe
├── pachiserver.exe
├── slotserver.exe
├── getcategory.exe
├── updatefilesV2.exe
├── camera.bat
├── camera.ini
├── slotserver_localhost.ini  ← ローカル開発用
└── slotserver_time.ini
```

### 2. 設定ファイルの作成

#### `C:\serverset\slotserver_localhost.ini`

```ini
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c
domain = localhost

[PatchServer]
# ローカル開発では無効化
filesurl =
url =

[API]
# ローカルサーバーのAPI
url = http://localhost:8080/api/cameraListAPI.php?M=getno&MAC=

[Chrome]
# ローカルサーバーのカメラ配信ページ
url = http://localhost:8080/server_v2/

[Monitor]
# WebSocketマシンコントロール（将来実装）
url = ws://localhost:59777/ws

[Credit]
playmin = 3
```

### 3. プログラムの起動準備

#### 起動用バッチファイル（開発用）

`C:\serverset\start_localhost.bat`

```batch
@echo off
echo NET8 Local Development - Camera Client Starting...
echo.
echo サーバー接続先: localhost:8080
echo シグナリング: localhost:59000
echo.
pause

REM slotserver_localhost.iniを使用してslotserver.exeを起動
copy /Y slotserver_localhost.ini slotserver.ini
slotserver.exe
```

---

## 接続テスト手順

### ステップ1: サーバー側の準備

```bash
# 1. Docker環境を起動
cd /Users/kotarokashiwai/net8_rebirth/net8
docker-compose up -d

# 2. ログを監視（別ターミナル）
docker-compose logs -f web
docker-compose logs -f signaling

# 3. サーバーのアクセス確認
curl http://localhost:8080
curl http://localhost:59000/peerjs
```

### ステップ2: API動作確認

ブラウザまたはcurlでAPIをテスト：

```bash
# カメラリストAPIのテスト
curl "http://localhost:8080/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
```

**期待されるレスポンス:**
```json
{
  "status": "ok",
  "machine_no": 1,
  "category": 1,
  "leavetime": 180,
  "renchan_games": 0,
  "tenjo_games": 9999,
  "version": "1",
  "max": 10,
  "max_rate": 1,
  "navel": 3,
  "tulip": null,
  "attacker1": null,
  "attacker2": null
}
```

**レスポンスフィールド説明:**
- `status`: API実行結果（"ok" = 成功）
- `machine_no`: 実機番号
- `category`: カテゴリ（1=パチンコ, 2=スロット）
- `leavetime`: 自動精算時間（秒）
- `renchan_games`: 連チャンゲーム数
- `tenjo_games`: 天井ゲーム数
- `version`: バージョン情報
- `max`, `max_rate`, `navel`, `tulip`, `attacker1`, `attacker2`: パチンコ固有設定

### ステップ3: WebRTC配信ページの確認

ブラウザで以下のURLにアクセス：

```
http://localhost:8080/server_v2/?MAC=00:00:00:00:00:01
```

**確認項目:**
- ✅ ページが正常に表示される
- ✅ カメラアクセス許可のダイアログが表示される
- ✅ カメラ映像のプレビューが表示される
- ✅ PeerJSサーバーへの接続成功メッセージ
- ✅ ブラウザのコンソールにエラーがない

### ステップ4: ローカルPCプログラムの起動

**Windowsローカ

PC上で:**

1. `C:\serverset\start_localhost.bat` を実行
2. コンソールログを確認：
   - API接続成功
   - カメラ番号取得
   - Chrome起動
   - WebRTC接続確立

### ステップ5: プレイヤー側での視聴テスト

別のブラウザまたはタブで視聴側ページを開く：

```
http://localhost:8080/play_v2/?NO=1
```

**確認項目:**
- ✅ カメラ映像が表示される
- ✅ 映像が滑らかに再生される
- ✅ 音声が聞こえる（オーディオあり）
- ✅ 遅延が許容範囲内（<1秒）

---

## トラブルシューティング

### 問題1: APIが404エラー

**原因:** APIファイルが見つからない

**解決方法:**
```bash
# ファイルの存在確認
ls -la /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/data/api/cameraListAPI.php

# Apacheログを確認
docker-compose logs web | grep cameraListAPI
```

### 問題2: PeerJS接続エラー

**原因:** シグナリングサーバーが起動していない

**解決方法:**
```bash
# シグナリングサーバーの状態確認
docker-compose ps signaling

# シグナリングサーバーのログ確認
docker-compose logs signaling

# 再起動
docker-compose restart signaling
```

### 問題3: カメラ映像が表示されない

**原因:** ブラウザのカメラ許可がない

**解決方法:**
1. ブラウザの設定 → プライバシーとセキュリティ
2. カメラの許可を確認
3. ページをリロード

### 問題4: データベース接続エラー

**原因:** カメラリストテーブルにデータがない

**解決方法:**
```bash
# データの確認
docker-compose exec db mysql -u net8user -pnet8pass net8_dev \
  -e "SELECT * FROM mst_cameralist;"

# データがない場合は再投入
docker-compose exec db mysql -u net8user -pnet8pass net8_dev < insert_camera_test_data.sql
```

### 問題5: CORS エラー

**原因:** クロスオリジンリクエストがブロックされている

**解決方法:**

Apache設定を確認：
```apache
# docker/web/apache-config/000-default.conf
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type"
```

---

## デバッグ用コマンド集

### サーバー側ログ

```bash
# Apacheエラーログ
docker-compose exec web tail -f /var/log/apache2/error.log

# PHPエラーログ
docker-compose exec web tail -f /var/log/apache2/php_error.log

# PeerJSシグナリングログ
docker-compose logs -f signaling
```

### データベース確認

```bash
# カメラリスト
docker-compose exec db mysql -u net8user -pnet8pass net8_dev \
  -e "SELECT * FROM mst_cameralist WHERE del_flg = 0;"

# カメラマスタ
docker-compose exec db mysql -u net8user -pnet8pass net8_dev \
  -e "SELECT * FROM mst_camera WHERE del_flg = 0;"

# 実機マスタ（カメラと紐付け）
docker-compose exec db mysql -u net8user -pnet8pass net8_dev \
  -e "SELECT * FROM dat_machine WHERE camera_no IS NOT NULL;"
```

### ネットワーク確認

```bash
# ポート開放確認
netstat -an | grep 8080
netstat -an | grep 59000

# Docker内部からのアクセス確認
docker-compose exec web curl http://localhost/
```

---

## 次のステップ

1. **WebSocketマシンコントロールの実装**
   - WebSocketサーバーの追加（ポート59777）
   - マシン制御コマンドの送受信
   - リアルタイム状態監視

2. **本番環境へのデプロイ**
   - 本番用iniファイルの作成
   - SSL/TLS対応
   - 認証・認可の強化

3. **パフォーマンス最適化**
   - ビットレート調整
   - コーデック最適化
   - ネットワーク帯域最適化

---

## 参考情報

### 関連ファイル

- サーバー側設定: `/02.ソースファイル/net8_html/_etc/setting_base.php`
- WebRTC設定: `/02.ソースファイル/net8_html/_etc/webRTC_setting.php`
- カメラAPI: `/02.ソースファイル/net8_html/data/api/cameraListAPI.php`
- 配信ページ: `/02.ソースファイル/net8_html/data/server_v2/index.php`
- 視聴ページ: `/02.ソースファイル/net8_html/data/play_v2/index.php`

### ドキュメント

- Docker環境: `README_DOCKER.md`
- 開発ルール: `CLAUDE.md`
- PeerJS公式: https://peerjs.com/docs/

---

## サポート

問題が解決しない場合は、以下の情報を含めて報告してください：

1. エラーメッセージの全文
2. ブラウザのコンソールログ
3. サーバーログ（Apache, PHP, PeerJS）
4. 実行環境情報（OS, ブラウザバージョン）
