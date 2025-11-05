# Windows側 最終実行指示

Mac側でデータベースの設定を完了しました。
Windows側で以下を実行してください。

## 実行コマンド

```powershell
cd C:\serverset
.\slotserver.exe -c COM4
```

## 期待される出力

以下のような出力が表示されるはずです：

```
version 1.4.8 - Remove logic check NG License
start slotserver.py
MAC:34-a6-ef-35-73-73
{"status":"ok","machine_no":4,"category":2,"leavetime":180,"renchan_games":0,"tenjo_games":9999,"version":"1","max":10,"max_rate":1,"navel":3,"tulip":null,"attacker1":null,"attacker2":null}
status:ok
version:1
start MainLoop
port:COM4
WebSocket Server started on port 59007
```

**重要な確認ポイント：**
1. ✅ `category:2` になっている（スロット用）
2. ✅ `start MainLoop` が表示される
3. ✅ `port:COM4` が表示される
4. ✅ `WebSocket Server started on port 59007` が表示される

## もし問題が発生したら

### エラー1: 「NG License」エラーが出る
→ Mac側に報告してください

### エラー2: `category:1` のままで終了する
→ Mac側に報告してください（データベース確認が必要）

### エラー3: COMポートが見つからない
→ 以下を実行して結果を報告：
```powershell
Get-WmiObject Win32_SerialPort | Select-Object DeviceID, Description, Status
```

### エラー4: WebSocketサーバーが起動しない
→ ログファイルの最後50行を確認：
```powershell
cd C:\serverset\logs
Get-Content (Get-ChildItem | Sort-Object LastWriteTime -Descending | Select-Object -First 1).Name -Tail 50
```

## 成功したら

WebSocketサーバーが起動したら、Chromeで以下にアクセス：

```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

カメラアクセス許可ダイアログが表示されたら「許可」をクリックしてください。

## 出力を全文報告

slotserver.exeの出力を全文コピーして、Mac側に報告してください。
