# Windows側ライセンスエラー診断手順

## 📋 確認してください

### 1. iniファイルの確認

PowerShellで以下を実行：

```powershell
# C:\serversetフォルダの全ファイルを確認
Get-ChildItem C:\serverset\*.ini | Select-Object Name, Length, LastWriteTime

# slotserver_ngrok.iniの内容を表示
Get-Content C:\serverset\slotserver_ngrok.ini
```

**期待される結果**:
- `slotserver_ngrok.ini` が存在すること
- 内容に以下が含まれること：
  ```
  [License]
  id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
  cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c
  domain = aicrypto.ngrok.dev
  ```

---

### 2. slotserver.exeの起動ログ確認

```powershell
# ログフォルダを確認
Get-ChildItem C:\serverset\logs\ | Sort-Object LastWriteTime -Descending | Select-Object -First 5

# 最新のログファイルを表示
Get-Content (Get-ChildItem C:\serverset\logs\*.log | Sort-Object LastWriteTime -Descending | Select-Object -First 1).FullName -Tail 50
```

---

### 3. API接続テスト（手動）

PowerShellで以下を実行：

```powershell
$apiUrl = "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=&IP=127.0.0.1"

$response = Invoke-WebRequest -Uri $apiUrl
$response.Content | ConvertFrom-Json | ConvertTo-Json
```

**期待される結果**:
```json
{
  "status": "ok",
  "machine_no": 1,
  "category": 1,
  "cd": "6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c",
  ...
}
```

---

### 4. slotserver.exeの詳細起動テスト

```powershell
# C:\serversetに移動
cd C:\serverset

# slotserver.exeを直接実行（コンソール出力を見る）
.\slotserver.exe

# 何が表示されるか、全てコピーしてください
```

---

### 5. 代替iniファイルで試す

もし上記で解決しない場合、元のmilliongod.online設定で試してみる：

```powershell
# 元のiniファイルを使用
Copy-Item C:\serverset\slotserver_ngrok.ini C:\serverset\slotserver_ngrok_backup.ini

# 新しいiniファイルを作成
@"
[License]
id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c

[API]
url = https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=

[Chrome]
url = https://aicrypto.ngrok.dev/server_v2/
"@ | Out-File -FilePath C:\serverset\slotserver.ini -Encoding ASCII
```

---

## 💡 重要な確認ポイント

### exeファイルが読むiniファイルの優先順位

slotserver.exeは以下の順序でiniファイルを探します：

1. `slotserver.ini` （第一優先）
2. `slotserver_ngrok.ini` （ngrokモード）
3. `slotserver_localhost.ini` （ローカルモード）

**対処法**: `slotserver.ini`を作成して、ngrok設定を書き込む

---

## 🆘 それでも解決しない場合

以下の情報をMac側に報告してください：

1. slotserver.exeの起動時のコンソール出力（全文）
2. C:\serverset\logs\フォルダ内の最新ログファイルの内容
3. API接続テストの結果
4. iniファイルの内容（全文）

これらの情報があれば、Mac側でさらに詳しく調査できます。
