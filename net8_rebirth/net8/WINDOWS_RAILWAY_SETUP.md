# 🖥️ Windows PC - Claude Code への指示書

**重要:** この指示書をWindows PCのClaude Codeにコピー＆ペーストして実行してください

---

## 🎯 ミッション
WebRTC接続のためのWindows側サーバー（chromeCameraV2 / slotserver）を起動し、Mac側ブラウザとの接続を確立する

---

## 📍 前提条件

### 作業ディレクトリ
```bash
# 実際のパスに置き換えて移動
cd "C:\Users\YourName\net8_rebirth\net8\02.ソースファイル\net8_html\data\server_v2"
```

### Python バージョン
- **推奨:** Python 3.7～3.9
- **非推奨:** Python 3.10以降（一部ライブラリが非対応）

---

## ✅ ステップ1: 環境確認

### 1-1. Python バージョン確認
```bash
python --version
```

### 1-2. 現在のディレクトリとファイル確認
```bash
# 現在地確認
cd

# 必要なファイル確認
dir chromeCameraV2.py
dir slotserver.py
dir requirements.txt
```

### 1-3. requirements.txt 内容確認
```bash
type requirements.txt
```

**期待される内容:**
```
websocket-client==1.7.0
websocket_server==0.4
gevent==24.2.1
greenlet==3.0.3
pyserial==3.5
requests==2.22.0
PyYAML==5.1.2
```

---

## ✅ ステップ2: 依存ライブラリのインストール

### 2-1. 一括インストール
```bash
pip install -r requirements.txt
```

### 2-2. 重要ライブラリの確認
```bash
pip list | findstr "websocket gevent pyserial"
```

**期待される出力:**
```
gevent                    24.2.1
greenlet                  3.0.3
pyserial                  3.5
websocket-client          1.7.0
websocket-server          0.4
```

### 2-3. 不足している場合の個別インストール
```bash
pip install websocket-client==1.7.0
pip install websocket-server==0.4
pip install gevent==24.2.1
pip install pyserial==3.5
```

---

## ✅ ステップ3: 設定ファイルの確認

### 3-1. slotserver.ini の内容確認
```bash
type slotserver.ini
```

### 3-2. 必須設定項目
```ini
[DEFAULT]
# シグナリングサーバー（Railway本番）
signalingServer = wss://mgg-signaling-production-c1bd.up.railway.app

# カメラ設定
cameraNo = 10000023
macAddress = 34-a6-ef-35-73-73

# シリアルポート
comPort = COM3
baudRate = 9600

# デバッグモード
debugMode = True
```

### 3-3. COMポート番号の確認（PowerShell）
```powershell
Get-WmiObject Win32_SerialPort | Select Name, DeviceID
```

**出力例:**
```
Name                          DeviceID
----                          --------
USB Serial Port (COM3)        COM3
```

↑ この `DeviceID` を `slotserver.ini` の `comPort` に設定

### 3-4. 設定ファイルが存在しない場合の作成
```bash
echo [DEFAULT] > slotserver.ini
echo signalingServer = wss://mgg-signaling-production-c1bd.up.railway.app >> slotserver.ini
echo cameraNo = 10000023 >> slotserver.ini
echo macAddress = 34-a6-ef-35-73-73 >> slotserver.ini
echo comPort = COM3 >> slotserver.ini
echo baudRate = 9600 >> slotserver.ini
echo debugMode = True >> slotserver.ini
```

---

## ✅ ステップ4: chromeCameraV2 の起動

### 4-1. 新しいコマンドプロンプトを開く
```
スタートメニュー → 「cmd」と入力 → Enter
```

### 4-2. ディレクトリに移動
```bash
cd "C:\Users\YourName\net8_rebirth\net8\02.ソースファイル\net8_html\data\server_v2"
```

### 4-3. 起動コマンド

**オプションA: .exeファイルが存在する場合**
```bash
chromeCameraV2.exe
```

**オプションB: Pythonスクリプトから実行**
```bash
python chromeCameraV2.py
```

### 4-4. ✅ 期待されるコンソール出力
```
[INFO] カメラサーバー起動中...
[INFO] シグナリングサーバー接続: wss://mgg-signaling-production-c1bd.up.railway.app
[OK] WebSocket接続成功
[OK] Peer ID登録完了: camera_10000023_1762164669
[INFO] カメラデバイス起動: Device 0
[INFO] 映像配信待機中...
```

### 4-5. ⚠️ Peer ID を記録
```
camera_10000023_XXXXXXXXXX
          ↑
    この部分をコピーしてMac側に伝える
```

### 4-6. ❌ エラーが出た場合

#### エラー1: `ModuleNotFoundError: No module named 'websocket'`
```bash
pip install websocket-client==1.7.0
```

#### エラー2: `WebSocket connection failed`
```bash
# ファイアウォール設定確認
# Windows Defender → 許可されたアプリ → Python または chromeCameraV2.exe を追加
```

#### エラー3: `Camera device not found`
```python
# chromeCameraV2.py の camera_device 番号を変更
# 0 → 1 → 2 と順番に試す
```

---

## ✅ ステップ5: slotserver の起動

### 5-1. 別の新しいコマンドプロンプトを開く
```
スタートメニュー → 「cmd」と入力 → Enter
```

### 5-2. ディレクトリに移動
```bash
cd "C:\Users\YourName\net8_rebirth\net8\02.ソースファイル\net8_html\data\server_v2"
```

### 5-3. 起動コマンド

**オプションA: .exeファイルが存在する場合**
```bash
slotserver.exe
```

**オプションB: Pythonスクリプトから実行**
```bash
python slotserver.py
```

### 5-4. ✅ 期待されるコンソール出力
```
[INFO] スロットサーバー起動中...
[INFO] シリアルポート接続: COM3 (9600bps)
[OK] スロット台通信確立
[INFO] コマンド受付待機中...
```

### 5-5. ❌ エラーが出た場合

#### エラー1: `Serial port COM3 not found`
```bash
# PowerShellで正しいCOMポート番号を確認
Get-WmiObject Win32_SerialPort | Select Name, DeviceID

# slotserver.ini の comPort を修正
```

#### エラー2: `Permission denied`
```bash
# コマンドプロンプトを管理者権限で起動
# スタートメニュー → cmd → 右クリック → 管理者として実行
```

#### エラー3: `ModuleNotFoundError: No module named 'serial'`
```bash
pip install pyserial==3.5
```

---

## ✅ ステップ6: 接続確認

### 6-1. chromeCameraV2 のコンソールで確認すべきこと
- ✅ Peer ID: `camera_10000023_XXXXXXXXXX` が表示されている
- ✅ WebSocket接続: `[OK] WebSocket接続成功` と表示されている
- ✅ カメラ起動: `[INFO] カメラデバイス起動` と表示されている

### 6-2. slotserver のコンソールで確認すべきこと
- ✅ シリアルポート: `[OK] スロット台通信確立` と表示されている
- ✅ 待機状態: `[INFO] コマンド受付待機中` と表示されている

### 6-3. Mac側への報告

**この情報をテキストでコピーして、Mac側のClaude Codeに貼り付けてください:**

```
【Windows側起動完了報告】

✅ Python バージョン: (python --version の結果)
✅ 依存ライブラリ: インストール完了
✅ chromeCameraV2 起動: 成功
✅ Peer ID: camera_10000023_XXXXXXXXXX
✅ slotserver 起動: 成功
✅ シリアルポート: COM3接続成功

chromeCameraV2とslotserverが正常に起動しました。
Mac側ブラウザからの接続テストを開始してください。
```

---

## ✅ ステップ7: Mac側でのテスト

Mac側で以下のURLにアクセス:
```
http://localhost:8000/data/play_test_noauth.php?NO=10000023
```

### Windows側 chromeCameraV2 で確認すべきログ
```
[INFO] 着信接続: XXXXXXXXXXXX
[INFO] 認証チェック中...
[OK] 認証成功: member_no=1
[INFO] 映像ストリーム送信開始
```

### ブラウザ（Mac側）で確認すべきこと
- ✅ 「接続中」の表示が消える
- ✅ カメラ映像が表示される
- ✅ ゲームボタン（BET, START）が表示される

---

## ❌ トラブルシューティング

### 問題1: 「接続中」のまま止まる

**原因:** Peer IDが一致していない

**解決策:**
1. chromeCameraV2 のコンソールで Peer ID を確認
2. Mac側に正確なPeer IDを伝える
3. Mac側でデータベース更新が必要

### 問題2: 映像が表示されない

**原因:** カメラデバイスが取得できていない

**解決策:**
```python
# chromeCameraV2.py を編集
# camera_device = 0 を camera_device = 1 に変更
# または camera_device = 2 と試す
```

### 問題3: スロット台制御が動かない

**原因:** COMポート設定が間違っている

**解決策:**
```powershell
# PowerShellで正しいCOMポートを確認
Get-WmiObject Win32_SerialPort | Select Name, DeviceID

# slotserver.ini を修正
```

---

## 🎯 完了確認チェックリスト

- [ ] Python 3.7-3.9がインストールされている
- [ ] requirements.txtの全ライブラリがインストール完了
- [ ] slotserver.iniの設定が正しい
- [ ] chromeCameraV2.exeが起動し、Peer IDが表示されている
- [ ] slotserver.exeが起動し、シリアルポート接続成功
- [ ] Mac側ブラウザで映像が表示される
- [ ] ゲームボタン（BET, START）が動作する

---

## 📤 Mac側への最終報告

全てのチェックリストが完了したら、以下をMac側に報告:

```
【Windows側セットアップ完了】

すべての確認項目が完了しました。
- chromeCameraV2: 起動中、Peer ID登録完了
- slotserver: 起動中、シリアルポート接続完了
- Mac側ブラウザ: 映像表示成功

次のステップ（Railwayデプロイ）の準備が整いました。
```

---

**作成日:** 2025-11-03
**対象:** Windows PC - Claude Code
**タスク:** WebRTC接続確立のためのサーバー起動
