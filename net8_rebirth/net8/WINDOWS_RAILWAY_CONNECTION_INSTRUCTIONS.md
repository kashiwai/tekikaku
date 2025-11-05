# Windows PC - Railway接続設定指示書

## 🎯 目的
Windows PCのカメラストリーミングをRailwayサーバーに接続する

---

## 📋 修正が必要なファイル

### 1. slotserver.ini の更新

**ファイルパス:** `slotserver.ini` (slotserver.exeと同じディレクトリ)

**修正内容:**
```ini
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c
domain = mgg-webservice-production.up.railway.app

[PatchServer]
filesurl =
url =

[API]
url = https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=

[Chrome]
url = https://mgg-webservice-production.up.railway.app/server_v2/

[Monitor]
url = wss://mgg-webservice-production.up.railway.app/ws

[Credit]
playmin = 3
```

**重要な変更点:**
- `domain` を `mgg-webservice-production.up.railway.app` に変更
- すべてのURL を Railway のドメインに変更

---

## 🚀 slotserver.exe の起動

### 手順:

1. **既存プロセスの終了**
   - タスクマネージャーを開く（Ctrl+Shift+Esc）
   - `slotserver.exe` が実行中なら終了させる

2. **slotserver.ini の配置確認**
   - `slotserver.ini` が `slotserver.exe` と同じフォルダにあることを確認

3. **slotserver.exe を実行**
   - 管理者権限で実行
   - コマンドプロンプトまたは PowerShell で実行すると、エラーメッセージが見える

4. **実行確認**
   ```powershell
   # PowerShellで確認
   Get-Process | Where-Object {$_.Name -eq "slotserver"}
   ```

---

## 📹 カメラサーバーの設定

### カメラストリーミング用のファイル

カメラストリーミングには以下のいずれかを使用:

1. **pachiserver.py** (Python版)
   - ファイルパス: `カメラ端末設置ファイル/02.実行ファイルソース/pachi_v2/pachiserver.py`

2. **ブラウザベース** (簡単)
   - Windows PCのブラウザで以下のURLを開く:
     ```
     https://mgg-webservice-production.up.railway.app/
     ```
   - 北斗の拳を選択
   - 「配信開始」ボタンをクリック
   - カメラアクセスを許可

---

## 🔧 トラブルシューティング

### slotserver.exe が起動しない場合

1. **slotserver.ini の確認**
   - ファイルが正しい場所にあるか
   - 文字コードが UTF-8 (BOM無し) になっているか

2. **ライセンスエラー**
   - `id` と `cd` が正しいか確認
   - `domain` が `mgg-webservice-production.up.railway.app` になっているか

3. **ポート競合**
   - 他のプロセスがポートを使用していないか確認

### カメラ接続ができない場合

1. **ネットワーク確認**
   ```powershell
   # Railway サーバーに接続できるか確認
   Test-NetConnection mgg-webservice-production.up.railway.app -Port 443
   ```

2. **ファイアウォール**
   - Windows Defender ファイアウォールで `slotserver.exe` を許可

3. **ブラウザのカメラ許可**
   - ブラウザの設定でカメラアクセスが許可されているか確認

---

## 📊 接続確認

### 1. slotserver.exe のログ確認
- slotserver.exe のログファイルを確認
- エラーメッセージがないか確認

### 2. Railway サーバー側で確認
- Railwayのログを確認
- カメラからの接続リクエストが来ているか確認

### 3. ブラウザで確認
```
https://mgg-webservice-production.up.railway.app/
```
- トップページで北斗の拳3台が表示されることを確認
- カメラアイコンをクリックして配信が始まるか確認

---

## 🎬 接続フロー

```
Windows PC (カメラ)
    ↓
slotserver.exe (slotserver.ini を読み込み)
    ↓
Railway Server (mgg-webservice-production.up.railway.app)
    ↓
    ├─ /api/cameraListAPI.php (カメラ登録API)
    ├─ /_api/signaling.php (WebRTC シグナリング)
    └─ /_api/turn_server.php (TURN サーバー)
    ↓
スマホ/PC ブラウザ (視聴側)
```

---

## 📝 登録済み実機情報

Railwayサーバーには以下の実機が登録されています：

- **HOKUTO001**: camera_no=1, signaling_id=PEER001
- **HOKUTO002**: camera_no=2, signaling_id=PEER002
- **HOKUTO003**: camera_no=3, signaling_id=PEER003

---

## ⚠️ 重要な注意事項

1. **slotserver.ini の文字コード**
   - 必ず UTF-8 (BOM無し) で保存してください
   - Windows のメモ帳で保存すると文字コードが変わる可能性があるので注意

2. **管理者権限**
   - slotserver.exe は管理者権限で実行してください

3. **ファイアウォール**
   - Windows Defender ファイアウォールで slotserver.exe を許可してください

4. **HTTPS接続**
   - すべてのURLは https:// で始まります（wss:// はWebSocket用）

---

## 🆘 エラーが出た場合

以下の情報を教えてください：
1. slotserver.exe のログ内容
2. エラーメッセージ（あれば）
3. タスクマネージャーで slotserver.exe が実行中か
4. ブラウザのコンソールエラー（F12で開発者ツールを開く）

---

**作成日:** 2025-11-01
**対象環境:** Windows PC
**サーバー:** Railway (mgg-webservice-production.up.railway.app)
