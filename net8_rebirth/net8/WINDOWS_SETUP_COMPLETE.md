# Windows PC セットアップ完全ガイド（WebRTC接続対応）

## 🎯 目的
- chromeCameraV2.exe（カメラ映像配信）
- slotserver.exe（スロット台制御）
を正しく起動して、ブラウザからWebRTC接続を確立する

---

## 📋 必須要件

### 1. Python環境
```bash
# Python 3.7-3.9推奨（3.10以降は一部ライブラリが非対応）
python --version
```

### 2. 必須ライブラリのインストール
```bash
cd "C:\path\to\net8\02.ソースファイル\net8_html\data\server_v2"

# requirements.txtから一括インストール
pip install -r requirements.txt

# 個別に確認してインストール
pip install altgraph==0.17.4
pip install certifi==2024.2.2
pip install gevent==24.2.1
pip install greenlet==3.0.3
pip install websocket-client==1.7.0
pip install websocket_server==0.4
pip install pyserial==3.5
pip install requests==2.22.0
pip install PyYAML==5.1.2
```

**重要な依存関係:**
- `websocket-client==1.7.0` - シグナリングサーバー接続に必須
- `websocket_server==0.4` - ローカルWebSocketサーバーに必須
- `gevent==24.2.1` - 非同期処理に必須
- `pyserial==3.5` - スロット台シリアル通信に必須

---

## 🔧 設定ファイル確認

### 1. slotserver.ini の設定
```ini
[DEFAULT]
# シグナリングサーバーURL（Railwayデプロイ後）
signalingServer = wss://mgg-signaling-production-c1bd.up.railway.app

# ローカルテスト用
# signalingServer = wss://localhost:9000

# カメラ設定
cameraNo = 10000023
macAddress = 34-a6-ef-35-73-73

# スロット台シリアル通信
comPort = COM3
baudRate = 9600

# デバッグモード
debugMode = True
```

### 2. camera.ini の設定
```ini
[camera_10000023]
mac_address = 34-a6-ef-35-73-73
signaling_server = wss://mgg-signaling-production-c1bd.up.railway.app
peer_key = peerjs
camera_device = 0  # Webカメラデバイス番号（0が通常デフォルト）
```

---

## 🚀 起動手順

### ステップ1: シグナリングサーバー接続確認
```bash
# コマンドプロンプトまたはPowerShellで実行
cd "C:\path\to\net8\02.ソースファイル\net8_html\data\server_v2"

# Python環境確認
python --version
pip list | findstr "websocket gevent"
```

### ステップ2: chromeCameraV2.exe 起動
```bash
# コマンドプロンプトで実行（管理者権限推奨）
cd "C:\path\to\net8\02.ソースファイル\net8_html\data\server_v2"

# .exeファイルが存在する場合
chromeCameraV2.exe

# または、Pythonスクリプトから直接実行
python chromeCameraV2.py
```

**起動時の確認事項:**
```
[OK] WebSocket接続成功: wss://mgg-signaling-production-c1bd.up.railway.app
[OK] Peer ID登録: camera_10000023_1234567890
[OK] カメラデバイス起動: Device 0
```

**エラーが出る場合:**
```
[ERROR] WebSocket connection failed
→ シグナリングサーバーのURLを確認
→ ファイアウォールでポート443が開いているか確認

[ERROR] Camera device not found
→ camera_device番号を変更（0, 1, 2...と試す）
→ USBカメラが正しく接続されているか確認

[ERROR] Module not found: websocket
→ pip install websocket-client==1.7.0
```

### ステップ3: slotserver.exe 起動
```bash
# 別のコマンドプロンプトで実行
cd "C:\path\to\net8\02.ソースファイル\net8_html\data\server_v2"

# .exeファイルが存在する場合
slotserver.exe

# または、Pythonスクリプトから直接実行
python slotserver.py
```

**起動時の確認事項:**
```
[OK] シリアルポート接続: COM3 (9600bps)
[OK] スロット台通信確立
[OK] WebSocket接続成功: localhost:9001
```

**エラーが出る場合:**
```
[ERROR] Serial port COM3 not found
→ デバイスマネージャーでCOMポート番号を確認
→ slotserver.iniのcomPort設定を修正

[ERROR] Permission denied
→ 管理者権限でコマンドプロンプトを起動

[ERROR] Module not found: serial
→ pip install pyserial==3.5
```

---

## 🔍 トラブルシューティング

### 問題1: ブラウザで「接続中」のまま止まる

**原因:** Windows側のPeer IDが一致していない

**解決策:**
1. chromeCameraV2.exeのコンソールで表示されているPeer IDをコピー
   ```
   例: camera_10000023_1762164669
   ```

2. ブラウザのコンソール（F12 → Console）で確認
   ```javascript
   // 接続先Peer IDを確認
   console.log("Connecting to:", cameraid);
   ```

3. 一致しない場合、データベースのcamera_nameを更新
   ```sql
   UPDATE dat_machine
   SET camera_name = 'camera_10000023_1762164669'
   WHERE machine_no = '10000023';
   ```

### 問題2: WebSocket接続エラー

**原因:** シグナリングサーバーのURLが間違っている

**解決策:**
1. slotserver.iniを編集
   ```ini
   signalingServer = wss://mgg-signaling-production-c1bd.up.railway.app
   ```

2. ファイアウォール設定確認
   ```powershell
   # PowerShellで実行（管理者権限）
   New-NetFirewallRule -DisplayName "Allow Port 443" -Direction Outbound -LocalPort 443 -Protocol TCP -Action Allow
   ```

### 問題3: 映像が表示されない

**原因:** カメラデバイスが取得できていない

**解決策:**
1. camera.iniのcamera_device番号を変更
   ```ini
   camera_device = 1  # 0で動かない場合は1, 2と試す
   ```

2. Webカメラのドライバーを更新
   - デバイスマネージャー → イメージングデバイス
   - Webカメラを右クリック → ドライバーの更新

3. 別のUSBポートに接続してみる

### 問題4: スロット台制御が動かない

**原因:** シリアルポートの設定が間違っている

**解決策:**
1. COMポート番号確認
   ```powershell
   # PowerShellで実行
   Get-WmiObject Win32_SerialPort | Select Name, DeviceID
   ```

2. slotserver.iniを修正
   ```ini
   comPort = COM3  # 上記コマンドで確認したポート番号
   baudRate = 9600
   ```

3. USBシリアル変換ケーブルのドライバー確認
   - デバイスマネージャー → ポート (COMとLPT)
   - ドライバーが正しくインストールされているか確認

---

## 📊 デバッグモード

### コンソール出力を詳細にする
```python
# chromeCameraV2.py または slotserver.py の先頭に追加
import logging
logging.basicConfig(level=logging.DEBUG)
```

### ネットワーク接続確認
```bash
# シグナリングサーバーへの接続テスト
curl -I https://mgg-signaling-production-c1bd.up.railway.app

# または
telnet mgg-signaling-production-c1bd.up.railway.app 443
```

---

## ✅ 動作確認チェックリスト

- [ ] Python 3.7-3.9がインストールされている
- [ ] requirements.txtの全ライブラリがインストールされている
- [ ] slotserver.iniの設定が正しい
- [ ] chromeCameraV2.exeが起動している
- [ ] コンソールにPeer IDが表示されている
- [ ] slotserver.exeが起動している
- [ ] シリアルポート通信が確立している
- [ ] ブラウザで http://localhost:8000/data/play_test_noauth.php?NO=10000023 にアクセス
- [ ] 映像が表示される
- [ ] ゲームボタン（BET, START）が動作する

---

## 🎯 次のステップ

全てが正常に動作したら：

1. **ローカルテスト完了** → todo更新
2. **Railway本番環境デプロイ**
3. **本番環境での動作確認**
4. **管理画面UI確認**

---

## 📝 補足情報

### Windows環境変数設定
```powershell
# PowerShellで実行
$env:PYTHONPATH = "C:\path\to\net8\02.ソースファイル\net8_html\data\server_v2"
```

### サービスとして自動起動設定（オプション）
NSSM（Non-Sucking Service Manager）を使用：
```powershell
# NSSMをインストール
choco install nssm

# サービス登録
nssm install ChromeCamera "C:\path\to\chromeCameraV2.exe"
nssm install SlotServer "C:\path\to\slotserver.exe"

# サービス開始
nssm start ChromeCamera
nssm start SlotServer
```

---

**作成日:** 2025-11-03
**対象バージョン:** Net8 WebRTC Version 2
**連絡先:** プロジェクト管理者
