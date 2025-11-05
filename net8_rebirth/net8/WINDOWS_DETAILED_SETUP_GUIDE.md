# Windows PC - Railway接続 完全セットアップガイド（超詳細版）

## 🎯 このガイドの目的
Windows PCのカメラストリーミングをRailwayサーバー（https://mgg-webservice-production.up.railway.app）に接続します。

---

## 📍 STEP 1: 現在の状況確認

### 1-1. slotserver.exeの場所を探す

**方法1: エクスプローラーで検索**
1. エクスプローラーを開く（Windowsキー + E）
2. 左上の検索ボックスに「slotserver.exe」と入力
3. 見つかったファイルを右クリック → 「ファイルの場所を開く」

**方法2: PowerShellで検索**
```powershell
# PowerShellを開いて以下を実行
Get-ChildItem -Path C:\ -Filter slotserver.exe -Recurse -ErrorAction SilentlyContinue
```

**見つかるはずのパス例:**
- `C:\Users\[ユーザー名]\net8\slotserver.exe`
- `C:\net8\slotserver.exe`
- `D:\projects\net8\slotserver.exe`

→ **見つかったパスをメモしてください！**

---

### 1-2. slotserver.iniの場所を確認

slotserver.iniは**slotserver.exeと同じフォルダ**にあるはずです。

**確認方法:**
1. slotserver.exeがあるフォルダを開く
2. 「slotserver.ini」というファイルがあるか確認

**もしslotserver.iniが無い場合:**
- 新規作成する必要があります（後述）

---

### 1-3. 現在実行中のslotserver.exeを確認

**タスクマネージャーで確認:**
1. Ctrl + Shift + Esc でタスクマネージャーを開く
2. 「プロセス」タブをクリック
3. 「名前」列で「slotserver.exe」を探す

**PowerShellで確認:**
```powershell
Get-Process | Where-Object {$_.Name -eq "slotserver"}
```

**実行中の場合:**
```
ProcessName: slotserver
Id: 1234
```

**実行中でない場合:**
何も表示されない

---

## 📝 STEP 2: slotserver.iniの編集

### 2-1. 既存のslotserver.iniをバックアップ

**重要:** 必ずバックアップを作成してください！

1. slotserver.iniを右クリック
2. 「コピー」を選択
3. 同じフォルダで右クリック → 「貼り付け」
4. 「slotserver - コピー.ini」ができる
5. これを「slotserver_backup.ini」にリネーム

---

### 2-2. slotserver.iniを編集

**使用するエディタ:**
- **推奨:** Visual Studio Code または Notepad++
- **非推奨:** Windowsのメモ帳（文字コードの問題が発生する可能性）

**Visual Studio Codeで開く方法:**
1. slotserver.iniを右クリック
2. 「プログラムから開く」→ 「Visual Studio Code」

**Notepad++で開く方法:**
1. slotserver.iniを右クリック
2. 「Edit with Notepad++」

---

### 2-3. slotserver.iniの内容を以下に置き換え

**完全な内容（コピー&ペースト用）:**

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

**重要なポイント:**
- すべての `domain` と `url` が `mgg-webservice-production.up.railway.app` になっている
- `wss://` はWebSocket用（変更しない）
- `https://` はHTTP用（変更しない）

---

### 2-4. 文字コードの確認（重要！）

**Visual Studio Codeの場合:**
1. 右下のステータスバーを確認
2. 「UTF-8」と表示されていることを確認
3. もし違う場合は、クリックして「UTF-8」を選択

**Notepad++の場合:**
1. メニュー → エンコーディング
2. 「UTF-8 (BOMなし)」を選択

**保存:**
- Ctrl + S で保存
- ファイルを閉じる

---

## 🔧 STEP 3: slotserver.exeの再起動

### 3-1. 既存のslotserver.exeを終了

**方法1: タスクマネージャー（推奨）**
1. Ctrl + Shift + Esc でタスクマネージャーを開く
2. 「プロセス」タブで「slotserver.exe」を探す
3. 右クリック → 「タスクの終了」
4. 確認ダイアログで「プロセスの終了」をクリック

**方法2: PowerShell**
```powershell
# slotserver.exeを強制終了
Get-Process | Where-Object {$_.Name -eq "slotserver"} | Stop-Process -Force
```

**確認:**
```powershell
# 何も表示されなければ成功
Get-Process | Where-Object {$_.Name -eq "slotserver"}
```

---

### 3-2. slotserver.exeを起動

**方法1: エクスプローラーから（簡単）**
1. slotserver.exeがあるフォルダを開く
2. slotserver.exeを右クリック
3. 「管理者として実行」を選択
4. UACダイアログが出たら「はい」をクリック

**方法2: PowerShell（ログが見える）**
```powershell
# slotserver.exeがあるフォルダに移動（パスは適宜変更）
cd "C:\Users\[ユーザー名]\net8"

# 管理者権限で実行
Start-Process -FilePath ".\slotserver.exe" -Verb RunAs
```

**方法3: コマンドプロンプト（ログが見える、推奨）**
1. slotserver.exeがあるフォルダを開く
2. フォルダ内で Shift + 右クリック
3. 「PowerShellウィンドウをここで開く」または「コマンドウィンドウをここで開く」
4. 以下を実行:
```cmd
slotserver.exe
```

**この方法のメリット:**
- エラーメッセージがコンソールに表示される
- ログがリアルタイムで見える
- デバッグしやすい

---

### 3-3. slotserver.exeが起動したか確認

**タスクマネージャーで確認:**
1. Ctrl + Shift + Esc
2. 「プロセス」タブ
3. 「slotserver.exe」があればOK

**PowerShellで確認:**
```powershell
Get-Process | Where-Object {$_.Name -eq "slotserver"}
```

**期待される出力:**
```
Handles  NPM(K)    PM(K)      WS(K)     CPU(s)     Id  SI ProcessName
-------  ------    -----      -----     ------     --  -- -----------
    xxx      xx   xxxxx      xxxxx       x.xx   xxxx   x slotserver
```

---

## 🌐 STEP 4: ネットワーク接続確認

### 4-1. Railwayサーバーに接続できるか確認

**PowerShellで確認:**
```powershell
# HTTPSポート(443)に接続できるか確認
Test-NetConnection mgg-webservice-production.up.railway.app -Port 443
```

**期待される出力:**
```
ComputerName     : mgg-webservice-production.up.railway.app
RemoteAddress    : xxx.xxx.xxx.xxx
RemotePort       : 443
InterfaceAlias   : Ethernet
SourceAddress    : xxx.xxx.xxx.xxx
TcpTestSucceeded : True  ← これがTrueならOK
```

**もしTcpTestSucceeded: Falseの場合:**
- ファイアウォールがブロックしている
- インターネット接続を確認

---

### 4-2. ブラウザでRailwayサーバーにアクセス

**Google Chrome または Microsoft Edgeを開いて:**
```
https://mgg-webservice-production.up.railway.app/
```

**期待される表示:**
- トップページが表示される
- 北斗の拳が3台表示される
- 画像が表示される

**もし表示されない場合:**
1. ブラウザのキャッシュをクリア（Ctrl + Shift + Delete）
2. シークレットモードで試す（Ctrl + Shift + N）
3. 別のブラウザで試す

---

## 📹 STEP 5: カメラストリーミングテスト

### 5-1. ブラウザからカメラ配信

**手順:**
1. ブラウザで `https://mgg-webservice-production.up.railway.app/` を開く
2. 北斗の拳のいずれかをクリック
3. 「配信開始」ボタンをクリック
4. カメラアクセス許可のダイアログが表示される
5. 「許可」をクリック

**期待される動作:**
- Webカメラが起動する
- 自分の顔が映る
- 「配信中」と表示される

**もし許可ダイアログが出ない場合:**
1. ブラウザのアドレスバー左側のアイコンをクリック
2. カメラの設定を「許可」に変更
3. ページをリロード（F5）

---

## 🔍 STEP 6: ログとエラー確認

### 6-1. slotserver.exeのログファイル

**ログファイルの場所:**
- `slotserver.exe` と同じフォルダ
- `logs` フォルダ内
- ファイル名例: `slotserver_YYYYMMDD.log`

**ログファイルを開く:**
```powershell
# 最新のログファイルを開く
notepad (Get-ChildItem -Path . -Filter "*.log" | Sort-Object LastWriteTime -Descending | Select-Object -First 1).FullName
```

**確認すべき内容:**
- エラーメッセージ（ERROR、FAIL など）
- 接続成功メッセージ（Connected、Success など）
- Railway サーバーへの接続試行

---

### 6-2. ブラウザのコンソールエラー

**Google Chrome / Microsoft Edgeの場合:**
1. F12 を押して開発者ツールを開く
2. 「Console」タブをクリック
3. 赤色のエラーメッセージを確認

**よくあるエラー:**
- `Failed to fetch` → サーバーに接続できない
- `WebSocket connection failed` → WebSocketサーバーが起動していない
- `Camera permission denied` → カメラアクセスが拒否された

---

## 🆘 トラブルシューティング

### 問題1: slotserver.exeが起動しない

**原因1: slotserver.iniが見つからない**
- slotserver.exeと同じフォルダにslotserver.iniがあるか確認

**原因2: ライセンスエラー**
- slotserver.iniの`id`と`cd`が正しいか確認
- `domain`が`mgg-webservice-production.up.railway.app`になっているか確認

**原因3: ポートが既に使用されている**
```powershell
# ポート80を使用しているプロセスを確認
netstat -ano | findstr :80
```

**解決策:**
- 表示されたPIDのプロセスを終了
- または別のポートを使用

---

### 問題2: Railwayサーバーに接続できない

**確認事項:**
1. インターネット接続を確認
```powershell
ping google.com
```

2. ファイアウォール設定を確認
   - Windows Defender ファイアウォール → 詳細設定
   - 送信の規則で slotserver.exe が許可されているか

3. プロキシ設定を確認
   - 会社のネットワークの場合、プロキシ設定が必要な場合がある

---

### 問題3: カメラが映らない

**原因1: カメラがブロックされている**
- Windows 設定 → プライバシー → カメラ
- 「アプリがカメラにアクセスできるようにする」がオンになっているか確認

**原因2: 他のアプリがカメラを使用中**
- Zoom、Teams、Skypeなどを終了
- タスクマネージャーでカメラを使用しているアプリを確認

**原因3: ブラウザの許可が必要**
- ブラウザの設定 → サイトの設定 → カメラ
- Railwayサーバーのドメインが許可されているか確認

---

## 📊 期待される最終状態

### slotserver.exe
- タスクマネージャーで実行中
- ログにエラーが無い
- Railwayサーバーに接続している

### ブラウザ
- https://mgg-webservice-production.up.railway.app/ が表示される
- 北斗の拳3台が表示される
- カメラアイコンをクリックすると配信できる

### ネットワーク
- Railway サーバー(443ポート)に接続できる
- WebSocket接続が確立している

---

## 📝 報告が必要な情報

もしエラーが出た場合、以下の情報を報告してください：

1. **slotserver.exeのログ**
   ```powershell
   # 最新のログを出力
   Get-Content (Get-ChildItem -Path . -Filter "*.log" | Sort-Object LastWriteTime -Descending | Select-Object -First 1).FullName -Tail 50
   ```

2. **タスクマネージャーの状態**
   ```powershell
   Get-Process | Where-Object {$_.Name -eq "slotserver"}
   ```

3. **ネットワーク接続状態**
   ```powershell
   Test-NetConnection mgg-webservice-production.up.railway.app -Port 443
   ```

4. **ブラウザのコンソールエラー**
   - F12 → Console タブのスクリーンショット

5. **slotserver.iniの内容**
   ```powershell
   Get-Content slotserver.ini
   ```

---

## ✅ チェックリスト

完了したらチェック:

- [ ] slotserver.exeの場所を確認した
- [ ] slotserver.iniをバックアップした
- [ ] slotserver.iniをRailway用に更新した
- [ ] 文字コードをUTF-8にした
- [ ] 既存のslotserver.exeを終了した
- [ ] 新しいslotserver.exeを起動した
- [ ] タスクマネージャーでslotserver.exeが実行中
- [ ] Railwayサーバーに接続できる（Test-NetConnection成功）
- [ ] ブラウザでトップページが表示される
- [ ] 北斗の拳3台が表示される
- [ ] カメラ配信をテストした

---

**作成日:** 2025-11-01
**対象:** Windows PC
**サーバー:** Railway (https://mgg-webservice-production.up.railway.app)

---

## 🎓 補足: よくある質問

### Q1: slotserver.exeは何をするプログラムですか？
A1: カメラストリーミングとRailwayサーバーとの通信を管理するプログラムです。

### Q2: slotserver.iniを変更したら毎回再起動が必要ですか？
A2: はい、slotserver.iniを変更した場合は必ずslotserver.exeを再起動してください。

### Q3: 管理者権限で実行する必要がありますか？
A3: はい、ネットワーク通信やカメラアクセスのために管理者権限が必要です。

### Q4: ファイアウォールの設定は必要ですか？
A4: 初回起動時にWindows Defender ファイアウォールの許可ダイアログが表示されます。「アクセスを許可する」をクリックしてください。

### Q5: ngrokは不要ですか？
A5: はい、Railwayを使用する場合はngrokは不要です。
