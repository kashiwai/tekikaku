# Net8 Windows マシン一括管理ツール

41台のWindowsマシンを一括で管理するためのPowerShellスクリプト群です。

## ファイル構成

```
windows_deploy/
├── config/
│   └── machines.csv          # マシン一覧（IP, 名前等）
├── scripts/
│   ├── 1_deploy_all.ps1      # 全台にファイル配布
│   ├── 2_start_batch.ps1     # 3台ずつ起動
│   ├── 3_check_status.ps1    # 全台状態確認
│   └── 4_send_command.ps1    # コマンド送信
├── client/
│   ├── start_camera.bat      # カメラサーバー起動
│   ├── stop_camera.bat       # カメラサーバー停止
│   └── config.json           # クライアント設定テンプレート
└── logs/
    └── (実行ログが出力される)
```

## 事前準備

### 1. 管理PCでの準備

PowerShell 5.1以上がインストールされていることを確認：
```powershell
$PSVersionTable.PSVersion
```

### 2. 各WindowsマシンでPowerShell Remotingを有効化

各マシンで管理者権限で実行：
```powershell
Enable-PSRemoting -Force
Set-Item WSMan:\localhost\Client\TrustedHosts -Value "*" -Force
```

### 3. ファイアウォール設定

WinRM (Windows Remote Management) を許可：
```powershell
Enable-NetFirewallRule -Name "WINRM-HTTP-In-TCP-PUBLIC"
```

### 4. machines.csv の編集

`config/machines.csv` に実際のマシン情報を入力：
```csv
machine_no,hostname,ip_address,model_name,status
1,WIN-PC001,192.168.1.101,吉宗(ピンク),
2,WIN-PC002,192.168.1.102,番長,
...
```

## 使い方

### 1. 一括配布

全マシンにプログラムとBATファイルを配布：

```powershell
cd windows_deploy
.\scripts\1_deploy_all.ps1
```

**処理内容:**
- 各マシンに `C:\Net8` フォルダを作成
- `client/` 内のファイルをコピー
- マシン番号に応じた `config.json` を自動生成

### 2. 3台ずつ起動

3台ずつ順番にカメラサーバーを起動：

```powershell
.\scripts\2_start_batch.ps1
```

**処理内容:**
- 3台を起動
- 10秒待機
- 起動確認（Chromeプロセス確認）
- Y/N/A で次に進むか選択
  - Y = 次の3台へ
  - N = ここで終了
  - A = 残り全台を自動実行

### 3. 状態確認

全マシンの状態をテキストで確認：

```powershell
.\scripts\3_check_status.ps1
```

**出力例:**
```
[1] ON  | Chrome:RUN | Folder:OK | 吉宗(ピンク) | Up:2d 5h 30m
[2] ON  | Chrome:--- | Folder:OK | 番長
[3] OFF | Chrome:--- | Folder:-- | 吉宗(ピンク) | ERR:PC_OFFLINE
```

**色分け:**
- 緑: PC ON + Chrome起動中
- 黄: PC ON + Chrome停止
- 灰: PC OFF

### 4. コマンド送信

特定マシンにコマンドを送信：

```powershell
.\scripts\4_send_command.ps1
```

**対象選択:**
- A = 全台
- S = 特定のマシン番号（カンマ区切り）
- P = 問題があるマシンのみ

**コマンド一覧:**
1. Chrome再起動
2. Chrome停止
3. PC再起動
4. 設定確認
5. プロセス一覧
6. Net8フォルダ内容
C. カスタムコマンド

## ログファイル

すべての実行結果は `logs/` フォルダに保存されます：
- `deploy_YYYYMMDD_HHMMSS.txt` - 配布ログ
- `start_YYYYMMDD_HHMMSS.txt` - 起動ログ
- `status_YYYYMMDD_HHMMSS.txt` - 状態確認ログ
- `command_YYYYMMDD_HHMMSS.txt` - コマンド送信ログ
- `*.csv` - 結果のCSVエクスポート

## トラブルシューティング

### 接続できない場合

1. Pingが通るか確認：
   ```powershell
   Test-Connection -ComputerName 192.168.1.101
   ```

2. WinRMサービスが起動しているか確認：
   ```powershell
   Invoke-Command -ComputerName 192.168.1.101 -ScriptBlock { hostname }
   ```

3. ファイアウォールを確認

### 認証エラーの場合

管理者アカウントで実行しているか確認。
ドメイン環境でない場合は、各マシンで同じローカル管理者アカウントを設定：
```powershell
# 各マシンで実行
net user admin PASSWORD /add
net localgroup Administrators admin /add
```

### Chromeが起動しない場合

1. Chromeがインストールされているか確認
2. `C:\Net8\config.json` の設定を確認
3. `C:\Net8\start_camera.bat` を手動で実行してエラーを確認

## セキュリティ注意事項

- 本ツールはローカルネットワーク内での使用を前提としています
- 管理者権限が必要です
- パスワードはスクリプト内にハードコードしないでください
- 本番環境では適切なファイアウォール設定を行ってください
