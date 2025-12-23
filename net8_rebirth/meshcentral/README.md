# NET8 MeshCentral Remote Management

Windows 41台を一元管理するためのMeshCentralサーバー

## デプロイ方法（Railway）

### 1. Railwayプロジェクト作成

```bash
# Railway CLIインストール（未インストールの場合）
npm install -g @railway/cli

# ログイン
railway login

# 新規プロジェクト作成
cd meshcentral
railway init
```

### 2. 環境変数設定

Railway Dashboardで以下を設定：

| 変数名 | 値 | 説明 |
|--------|-----|------|
| `SESSION_KEY` | `ランダムな文字列32文字以上` | セッション暗号化キー |
| `NODE_ENV` | `production` | 本番モード |

### 3. デプロイ

```bash
railway up
```

### 4. 初回セットアップ

1. デプロイ完了後、URLにアクセス
2. 「Create Account」で管理者アカウント作成
3. 「Add Device Group」でグループ作成（例: `NET8-Machines`）
4. エージェントインストーラーをダウンロード

## Windows PCへのエージェントインストール

### 方法1: インストーラー使用

1. MeshCentralコンソールにログイン
2. デバイスグループを選択
3. 「Add Agent」→「Windows」→インストーラーダウンロード
4. Windows PCで実行

### 方法2: コマンドライン

```powershell
# PowerShell（管理者権限）
$url = "https://[YOUR_MESHCENTRAL_URL]/meshagents?id=3&meshid=[MESH_ID]&installflags=0"
Invoke-WebRequest -Uri $url -OutFile "meshagent.exe"
.\meshagent.exe -install
```

## 管理画面との統合

`machine_control_v2.php` から MeshCentral API を呼び出してリモート操作を実行。

### API使用例（PHP）

```php
// MeshCentral API経由でコマンド送信
$response = meshcentralAPI([
    'action' => 'runcommand',
    'nodeid' => $nodeId,
    'command' => 'C:\\serverset\\camera.bat'
]);
```

## ポート

- 443: HTTPS（Railway経由）
- WebSocket: 同一ポート（HTTPSアップグレード）

## 参考

- [MeshCentral Guide](https://ylianst.github.io/MeshCentral/meshcentral/)
- [MeshCentral GitHub](https://github.com/Ylianst/MeshCentral)
