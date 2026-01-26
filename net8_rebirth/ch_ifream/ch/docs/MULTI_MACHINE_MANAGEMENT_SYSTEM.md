# Net8 マルチマシン統合管理システム設計書

## 概要

41台のWindows実機を中央サーバーから一括管理するシステム。
現在のChrome Remote Desktop個別接続を排除し、効率的な運用を実現。

---

## システム全体図

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         中央管理サーバー (Railway/VPS)                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ 管理API      │  │ WebSocket    │  │ 映像集約     │  │ 監視ダッシュ  │ │
│  │ (PHP/Node)   │  │ ハブ         │  │ サーバー     │  │ ボード       │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
                │              │              │              │
                └──────────────┼──────────────┼──────────────┘
                               │              │
        ┌──────────────────────┴──────────────┴──────────────────────┐
        │                    インターネット                           │
        └──────────────────────┬──────────────┬──────────────────────┘
                               │              │
     ┌─────────────────────────┼──────────────┼─────────────────────────┐
     │                         │              │                         │
┌────┴────┐ ┌────┴────┐ ┌────┴────┐      ┌────┴────┐ ┌────┴────┐
│ Win PC 1 │ │ Win PC 2 │ │ Win PC 3 │ ... │ Win PC 40│ │ Win PC 41│
│ ┌─────┐  │ │ ┌─────┐  │ │ ┌─────┐  │      │ ┌─────┐  │ │ ┌─────┐  │
│ │Agent│  │ │ │Agent│  │ │ │Agent│  │      │ │Agent│  │ │ │Agent│  │
│ └─────┘  │ │ └─────┘  │ │ └─────┘  │      │ └─────┘  │ │ └─────┘  │
│ ┌─────┐  │ │ ┌─────┐  │ │ ┌─────┐  │      │ ┌─────┐  │ │ ┌─────┐  │
│ │実機  │  │ │ │実機  │  │ │ │実機  │  │      │ │実機  │  │ │ │実機  │  │
│ └─────┘  │ │ └─────┘  │ │ └─────┘  │      │ └─────┘  │ │ └─────┘  │
└─────────┘ └─────────┘ └─────────┘      └─────────┘ └─────────┘
```

---

## コンポーネント詳細

### 1. Windowsエージェント (`net8-agent.exe`)

各Windows PCにインストールする常駐プログラム。

```
機能:
├── 中央サーバーへのWebSocket常時接続
├── コマンド受信・実行
│   ├── Chrome起動/終了
│   ├── カメラサーバー起動
│   ├── 設定変更
│   └── システム再起動
├── 状態レポート（5秒間隔）
│   ├── CPU/メモリ使用率
│   ├── カメラ接続状態
│   ├── ゲーム稼働状態
│   └── エラー情報
└── 自動アップデート機能
```

**技術スタック**:
- Node.js + pkg (EXE化)
- または PowerShell + Windows Service

### 2. 中央管理サーバー

既存のNet8サーバーを拡張。

```javascript
// 新規API: /api/multi-machine/
POST /api/multi-machine/broadcast     // 全台一斉コマンド
POST /api/multi-machine/send/:id      // 個別コマンド
GET  /api/multi-machine/status        // 全台状態取得
POST /api/multi-machine/group/:name   // グループ操作
```

### 3. 監視ダッシュボード

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Net8 マルチマシン監視ダッシュボード                    [全台起動] [全台停止] │
├─────────────────────────────────────────────────────────────────────────┤
│  ページ: [1] [2] [3] [4] [5]  |  表示: [10台] [20台] [全台]  |  フィルタ: [稼働中のみ] │
├─────────────────────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │  #01    │ │  #02    │ │  #03    │ │  #04    │ │  #05    │ │
│ │ ┌─────┐ │ │ ┌─────┐ │ │ ┌─────┐ │ │ ┌─────┐ │ │ ┌─────┐ │ │
│ │ │VIDEO│ │ │ │VIDEO│ │ │ │VIDEO│ │ │ │VIDEO│ │ │ │VIDEO│ │ │
│ │ └─────┘ │ │ └─────┘ │ │ └─────┘ │ │ └─────┘ │ │ └─────┘ │ │
│ │ 🟢稼働中 │ │ 🟢稼働中 │ │ 🔴停止  │ │ 🟡待機  │ │ 🟢稼働中 │ │
│ │ C:5000  │ │ C:3200  │ │ ---     │ │ C:0     │ │ C:1500  │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │  #06    │ │  #07    │ │  #08    │ │  #09    │ │  #10    │ │
│ │ ┌─────┐ │ │ ┌─────┐ │ │ ┌─────┐ │ │ ┌─────┐ │ │ ┌─────┐ │ │
│ │ │VIDEO│ │ │ │VIDEO│ │ │ │VIDEO│ │ │ │VIDEO│ │ │ │VIDEO│ │ │
│ │ └─────┘ │ │ └─────┘ │ │ └─────┘ │ │ └─────┘ │ │ └─────┘ │ │
│ │ 🟢稼働中 │ │ 🟢稼働中 │ │ 🟢稼働中 │ │ 🟢稼働中 │ │ 🟢稼働中 │ │
│ │ C:8000  │ │ C:2100  │ │ C:4500  │ │ C:900   │ │ C:6700  │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 既存システムとの統合

### 現在の構成

```
既存コンポーネント:
├── moniter.js         ← WebSocket監視（拡張して流用）
├── machines.js        ← マシン一覧表示（拡張して流用）
├── cameraServer_*.js  ← カメラサーバー（そのまま利用）
├── WebSocket Server   ← wss://web.smart-gear.jp:59777（拡張）
└── mst_camera/dat_machine  ← DB（そのまま利用）
```

### 拡張ポイント

| 既存機能 | 拡張内容 |
|---------|---------|
| moniter.js | 映像グリッド表示追加、一括操作UI追加 |
| WebSocket Server | エージェント接続受付、ブロードキャスト機能追加 |
| xxxadmin | マルチマシン管理画面追加 |

---

## 実装フェーズ

### Phase 1: Windowsエージェント開発（1週間）

```
1.1 エージェント基盤作成
    ├── WebSocket接続維持
    ├── コマンド受信・実行
    └── 状態レポート送信

1.2 基本コマンド実装
    ├── START_CAMERA  - カメラサーバー起動
    ├── STOP_CAMERA   - カメラサーバー停止
    ├── RESTART_CHROME - Chrome再起動
    └── GET_STATUS    - 状態取得
```

### Phase 2: 中央サーバー拡張（1週間）

```
2.1 WebSocket Hub拡張
    ├── エージェント接続管理
    ├── ブロードキャスト機能
    └── 個別送信機能

2.2 管理API作成
    ├── /api/multi-machine/* エンドポイント
    └── 認証・権限管理
```

### Phase 3: 一括デプロイシステム（3日）

```
3.1 インストーラー作成
    ├── エージェントEXE
    ├── 設定ファイル生成
    └── サービス登録

3.2 デプロイスクリプト
    ├── PowerShell Remote実行
    └── 一括設定適用
```

### Phase 4: 監視ダッシュボード（1週間）

```
4.1 フロントエンド
    ├── 映像グリッド表示（WebRTC）
    ├── 状態インジケーター
    └── 一括操作ボタン

4.2 バックエンド
    ├── 映像ストリーム中継
    └── リアルタイム状態更新
```

---

## ファイル構成

```
net8_html/
├── data/
│   ├── multi-machine/              # 新規作成
│   │   ├── agent/                  # Windowsエージェント
│   │   │   ├── src/
│   │   │   │   ├── index.js        # メインエントリ
│   │   │   │   ├── websocket.js    # WebSocket通信
│   │   │   │   ├── commands.js     # コマンド実行
│   │   │   │   └── reporter.js     # 状態レポート
│   │   │   ├── config.json         # 設定ファイル
│   │   │   └── package.json
│   │   │
│   │   ├── server/                 # サーバー側
│   │   │   ├── hub.js              # WebSocket Hub
│   │   │   ├── api.php             # 管理API
│   │   │   └── broadcast.php       # ブロードキャスト
│   │   │
│   │   ├── dashboard/              # 監視ダッシュボード
│   │   │   ├── index.php
│   │   │   ├── js/
│   │   │   │   ├── grid.js         # 映像グリッド
│   │   │   │   ├── controls.js     # 操作ボタン
│   │   │   │   └── monitor.js      # 状態監視
│   │   │   └── css/
│   │   │       └── dashboard.css
│   │   │
│   │   └── deploy/                 # デプロイスクリプト
│   │       ├── install.ps1         # インストーラー
│   │       ├── deploy-all.ps1      # 一括デプロイ
│   │       └── machines.csv        # 対象マシン一覧
```

---

## 通信プロトコル

### エージェント → サーバー

```json
// 接続時
{
  "type": "agent_connect",
  "machine_no": 1,
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "version": "1.0.0"
}

// 状態レポート（5秒間隔）
{
  "type": "status_report",
  "machine_no": 1,
  "status": {
    "camera": "connected",
    "game": "playing",
    "credit": 5000,
    "member_no": 12345,
    "cpu": 45,
    "memory": 60,
    "errors": []
  }
}
```

### サーバー → エージェント

```json
// 個別コマンド
{
  "type": "command",
  "target": 1,
  "command": "START_CAMERA",
  "params": {}
}

// ブロードキャスト
{
  "type": "broadcast",
  "command": "RESTART_ALL",
  "params": {}
}
```

### コマンド一覧

| コマンド | 説明 | パラメータ |
|---------|------|-----------|
| `START_CAMERA` | カメラサーバー起動 | - |
| `STOP_CAMERA` | カメラサーバー停止 | - |
| `RESTART_CHROME` | Chrome再起動 | - |
| `UPDATE_CONFIG` | 設定更新 | `{config: {...}}` |
| `REBOOT_PC` | PC再起動 | `{delay: 0}` |
| `GET_STATUS` | 状態取得要求 | - |
| `OPEN_URL` | URL開く | `{url: "..."}` |

---

## 一括デプロイ手順

### 事前準備

1. **対象マシン一覧作成** (`machines.csv`)
```csv
machine_no,hostname,ip_address,mac_address,username,password
1,WIN-PC001,192.168.1.101,AA:BB:CC:DD:EE:01,admin,****
2,WIN-PC002,192.168.1.102,AA:BB:CC:DD:EE:02,admin,****
...
41,WIN-PC041,192.168.1.141,AA:BB:CC:DD:EE:41,admin,****
```

2. **PowerShell Remote有効化**（各PC）
```powershell
Enable-PSRemoting -Force
Set-Item WSMan:\localhost\Client\TrustedHosts -Value "*" -Force
```

### デプロイ実行

```powershell
# deploy-all.ps1
$machines = Import-Csv "machines.csv"

foreach ($machine in $machines) {
    Write-Host "🚀 Deploying to $($machine.hostname)..."

    # エージェントコピー
    Copy-Item -Path ".\agent\*" `
              -Destination "\\$($machine.ip_address)\C$\Net8Agent\" `
              -Recurse -Force

    # 設定ファイル生成
    $config = @{
        machine_no = $machine.machine_no
        mac_address = $machine.mac_address
        server_url = "wss://web.smart-gear.jp:59777/agent"
    } | ConvertTo-Json

    $config | Out-File "\\$($machine.ip_address)\C$\Net8Agent\config.json"

    # サービス登録・起動
    Invoke-Command -ComputerName $machine.ip_address -ScriptBlock {
        & "C:\Net8Agent\install-service.bat"
        Start-Service "Net8Agent"
    }

    Write-Host "✅ $($machine.hostname) completed"
}
```

---

## 監視ダッシュボード詳細設計

### 映像グリッド表示

```javascript
// grid.js
class VideoGrid {
  constructor(container, options = {}) {
    this.container = container;
    this.columns = options.columns || 5;
    this.machines = [];
    this.peers = {};
  }

  // 全マシンの映像を取得・表示
  async loadAll() {
    const response = await fetch('/api/multi-machine/status');
    const machines = await response.json();

    machines.forEach(machine => {
      this.addMachine(machine);
    });
  }

  addMachine(machine) {
    const cell = this.createCell(machine);
    this.container.appendChild(cell);

    // PeerJS接続
    if (machine.camera_name) {
      this.connectPeer(machine);
    }
  }

  connectPeer(machine) {
    const peer = new Peer(null, {
      host: machine.sig_host,
      port: machine.sig_port,
      path: '/'
    });

    peer.on('open', (id) => {
      const conn = peer.call(machine.camera_name, null);
      conn.on('stream', (stream) => {
        document.getElementById(`video-${machine.machine_no}`).srcObject = stream;
      });
    });

    this.peers[machine.machine_no] = peer;
  }
}
```

### 一括操作ボタン

```javascript
// controls.js
class MachineControls {
  async startAll() {
    await fetch('/api/multi-machine/broadcast', {
      method: 'POST',
      body: JSON.stringify({ command: 'START_CAMERA' })
    });
    showToast('✅ 全台起動コマンド送信');
  }

  async stopAll() {
    if (!confirm('全台を停止しますか？')) return;
    await fetch('/api/multi-machine/broadcast', {
      method: 'POST',
      body: JSON.stringify({ command: 'STOP_CAMERA' })
    });
    showToast('⏹️ 全台停止コマンド送信');
  }

  async restartAll() {
    if (!confirm('全台を再起動しますか？')) return;
    await fetch('/api/multi-machine/broadcast', {
      method: 'POST',
      body: JSON.stringify({ command: 'RESTART_CHROME' })
    });
    showToast('🔄 全台再起動コマンド送信');
  }
}
```

---

## セキュリティ考慮事項

1. **エージェント認証**
   - MACアドレスによる識別
   - APIキーによる認証
   - IP制限（必要に応じて）

2. **通信暗号化**
   - WSS（WebSocket over TLS）必須
   - 設定ファイル内の機密情報暗号化

3. **権限管理**
   - 管理者のみブロードキャスト可能
   - 操作ログの記録

---

## 運用フロー

### 毎日の起動手順（自動化後）

```
1. 管理者がダッシュボードにログイン
2. [全台起動] ボタンをクリック
3. 全41台のエージェントがカメラサーバーを起動
4. ダッシュボードで映像確認（10台×4ページ + 1台）
5. 問題のあるマシンは個別操作で対応
```

### トラブルシューティング

| 状況 | 対応 |
|------|------|
| エージェント未接続 | PCの電源・ネットワーク確認 |
| カメラ映像なし | `RESTART_CHROME` 送信 |
| 応答なし | `REBOOT_PC` 送信 |
| 全台異常 | サーバー側確認 |

---

## 次のステップ

この設計書を基に実装を進めますか？

**優先順位の提案:**
1. ⭐ Phase 1: エージェント開発（最重要）
2. Phase 2: サーバー拡張
3. Phase 3: デプロイシステム
4. Phase 4: ダッシュボード

どのフェーズから着手しますか？
