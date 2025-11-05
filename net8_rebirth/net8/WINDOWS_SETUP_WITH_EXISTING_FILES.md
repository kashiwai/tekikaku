# Windows PC セットアップ（既存ファイル使用版）

## 🎯 目的

既存の `server_localpc/serverset/` ディレクトリをWindows PCに転送し、ngrok接続用に設定を更新します。

---

## 📋 前提条件

- Windows 10/11
- Claude Codeがインストール済み
- Mac側から `server_localpc/serverset/` フォルダ全体を受け取る

---

## 🌐 サーバー接続情報（ngrok）

**Mac側ngrok URL:**
- Webサーバー: `https://aicrypto.ngrok.dev`
- PeerJSサーバー: `https://aimoderation.ngrok-free.app`

**テストカメラ情報:**
- MAC Address: `00:00:00:00:00:01`
- License ID: `IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=`

---

## 📁 ステップ1: serversetフォルダの配置

### Mac側で実行（zipファイル作成）

```bash
cd /Users/kotarokashiwai/net8_rebirth/net8/server_localpc
zip -r serverset.zip serverset/
```

### Windows側で実行

1. `serverset.zip` をWindows PCにコピー
2. `C:\` 直下に展開
3. 結果: `C:\serverset\` ディレクトリができる

**確認:**
```powershell
dir C:\serverset
```

以下のファイルがあることを確認：
- slotserver.exe
- chromeCameraV2.exe
- camera_ctrl.exe
- slotserver_localhost.ini

---

## 📝 ステップ2: ngrok用iniファイルの作成

### ファイル名
`C:\serverset\slotserver_ngrok.ini`

### ファイル内容

```ini
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c
domain = aicrypto.ngrok.dev

[PatchServer]
filesurl =
url =

[API]
url = https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=

[Chrome]
url = https://aicrypto.ngrok.dev/server_v2/

[Monitor]
url = wss://aicrypto.ngrok.dev/ws

[Credit]
playmin = 3
```

### Claude Codeで実行

```
C:\serverset\slotserver_ngrok.ini を上記の内容で作成してください
```

---

## 📝 ステップ3: 起動用バッチファイルの作成

### ファイル名
`C:\serverset\start_ngrok.bat`

### ファイル内容

```batch
@echo off
echo ================================================
echo NET8 Camera Client - ngrok Connection
echo ================================================
echo.
echo Server: https://aicrypto.ngrok.dev
echo PeerJS: https://aimoderation.ngrok-free.app
echo MAC Address: 00:00:00:00:00:01
echo.
echo Press any key to start...
pause

REM slotserver_ngrok.iniを使用
copy /Y slotserver_ngrok.ini slotserver.ini

echo.
echo Starting slotserver.exe...
start slotserver.exe

echo.
echo Slotserver started. Check the console window for status.
echo Press any key to exit this window...
pause
```

---

## 🔍 ステップ4: 接続テスト

### 4-1: API疎通確認

PowerShellで実行：

```powershell
# Mac側サーバーへの接続テスト
curl "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
```

**期待されるレスポンス:**
```json
{"status":"ok","machine_no":1,"category":1,...}
```

### 4-2: PeerJSサーバー確認

```powershell
curl https://aimoderation.ngrok-free.app/peerjs/id
```

ランダムなIDが返ってくれば成功

---

## 🚀 ステップ5: プログラム起動

### 起動手順

1. `C:\serverset\start_ngrok.bat` をダブルクリック
2. 黒いコンソールウィンドウが開く
3. "Press any key to start..." と表示されたらEnterキーを押す
4. `slotserver.exe` が起動
5. 自動的にChromeが開く

### 確認項目

**slotserver.exeのコンソール:**
- ✅ "API接続成功" のようなメッセージ
- ✅ "カメラ番号: 1" のような表示
- ✅ "Chrome起動" の表示

**Chrome:**
- ✅ `https://aicrypto.ngrok.dev/server_v2/?MAC=00:00:00:00:00:01` が開く
- ✅ カメラアクセス許可のダイアログが表示
- ✅ カメラのプレビューが表示される

---

## 📺 ステップ6: Mac側で視聴テスト

Mac側のブラウザで以下のURLにアクセス：

```
https://aicrypto.ngrok.dev/play_v2/?NO=1
```

**期待される動作:**
- Windows PCのカメラ映像が表示される
- リアルタイムで映像が更新される
- 音声も聞こえる（カメラがマイク付きの場合）

---

## 🔧 トラブルシューティング

### 問題1: slotserver.exeが起動しない

**対処法:**
1. `C:\serverset\slotserver.exe` を右クリック → プロパティ
2. 「互換性」タブ → 「管理者としてこのプログラムを実行する」をチェック
3. 「適用」→「OK」
4. `start_ngrok.bat` を右クリック → 「管理者として実行」

### 問題2: Chromeが起動しない

**対処法:**
1. `C:\serverset\` に `chromeCameraV2.exe` があるか確認
2. Chromeがインストールされているか確認
3. slotserver.exeのコンソールにエラーメッセージがないか確認

### 問題3: カメラが認識されない

**対処法:**
1. Windowsの設定 → プライバシーとセキュリティ → カメラ
2. 「アプリがカメラにアクセスできるようにする」をオンに
3. Chromeを再起動

### 問題4: PeerJS接続エラー

**症状:** Chromeのコンソールに "PeerJS connection failed"

**対処法:**
1. Mac側のngrokトンネルが起動しているか確認
2. `https://aimoderation.ngrok-free.app/peerjs/id` にブラウザでアクセス
3. IDが返ってくるか確認

### 問題5: "This site can't be reached"

**原因:** Mac側のngrokトンネルが停止している

**対処法:**
Mac側で以下を確認：
```bash
# ngrokプロセスが動いているか
ps aux | grep ngrok

# 必要に応じて再起動
ngrok http 8080 --domain=aicrypto.ngrok.dev
ngrok http 59000  # 別ターミナル
```

---

## 📊 ディレクトリ構成（完成形）

```
C:\serverset\
├── camera_ctrl.exe          # カメラ制御プログラム
├── chromeCameraV2.exe       # Chrome起動プログラム
├── slotserver.exe           # メインサーバープログラム
├── pachiserver.exe          # パチンコ用サーバー
├── getcategory.exe          # カテゴリ取得
├── updatefilesV2.exe        # 更新チェック
├── camera.bat               # カメラ起動バッチ
├── camera.ini               # カメラ設定
├── slotserver_localhost.ini # ローカル用設定（元）
├── slotserver_ngrok.ini     # ngrok用設定（新規作成）
├── slotserver.ini           # 実行時使用（コピーされる）
├── start_ngrok.bat          # 起動バッチ（新規作成）
└── logs\                    # ログディレクトリ
```

---

## ✅ 完了チェックリスト

- [ ] `C:\serverset\` ディレクトリ配置完了
- [ ] `slotserver_ngrok.ini` 作成完了
- [ ] `start_ngrok.bat` 作成完了
- [ ] API疎通テスト成功
- [ ] PeerJS疎通テスト成功
- [ ] `slotserver.exe` 起動成功
- [ ] Chrome自動起動確認
- [ ] カメラ映像配信開始
- [ ] Mac側で視聴成功

---

## 🔄 次回起動時

2回目以降は簡単です：

1. Mac側のngrokトンネルが起動していることを確認
2. Windows側で `C:\serverset\start_ngrok.bat` をダブルクリック
3. Enterキーを押すだけ

---

## 📞 サポート情報

問題が発生した場合：

1. **slotserver.exeのコンソール出力**をスクリーンショット
2. **Chromeのコンソールログ**（F12 → Consoleタブ）をコピー
3. **エラーメッセージの全文**を記録

---

**作成日:** 2025-10-23
**バージョン:** 2.0（既存ファイル使用版）
**ベースディレクトリ:** server_localpc/serverset/
**ngrok設定:** aicrypto.ngrok.dev / aimoderation.ngrok-free.app
