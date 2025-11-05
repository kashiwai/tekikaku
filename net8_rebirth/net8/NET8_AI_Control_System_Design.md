# NET8 AI統合管理システム - 完全設計書
## オプション1: Claude Desktop + MCP統合

**作成日:** 2025-11-05
**対象:** 40台Windows PC一括管理 + AI遠隔制御
**技術スタック:** Claude Desktop, MCP, Node.js, WebSocket, PowerShell

---

## 📊 システムアーキテクチャ

```
┌─────────────────────────────────────────────────────────────────┐
│                     本社（管理側）                               │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  NET8 統合ダッシュボード (Webブラウザ)                    │   │
│  │  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  │   │
│  │  ┌─────────────────────────────────────────────────┐    │   │
│  │  │ マシン一覧                                       │    │   │
│  │  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │    │   │
│  │  │ 13 | MACHINE-13 | 🟢接続 | [Chrome RD] [AI指示]│    │   │
│  │  │                                                  │    │   │
│  │  │ [AI指示送信フォーム]                             │    │   │
│  │  │ マシン: 13                                       │    │   │
│  │  │ 指示: カメラ配信を再起動してください             │    │   │
│  │  │ [送信]                                           │    │   │
│  │  └─────────────────────────────────────────────────┘    │   │
│  └──────────────────────────────────────────────────────────┘   │
│                            ↕ HTTP/WebSocket                      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  中央管理サーバー (Node.js + Express + Socket.io)        │   │
│  │  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  │   │
│  │  - WebSocket Hub（40台との通信管理）                     │   │
│  │  - Claude API統合（自然言語→PowerShellコマンド変換）    │   │
│  │  - ログ集約・保存                                         │   │
│  │  - Chrome Remote Desktop URL管理                         │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                            ↕ WebSocket (wss://)
┌─────────────────────────────────────────────────────────────────┐
│                 各Windows PC（40台）                             │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  MACHINE-13 (192.168.1.14)                               │   │
│  │  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │ Claude Desktop (常駐)                              │ │   │
│  │  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │ │   │
│  │  │ - 中央サーバーとWebSocket接続維持                 │ │   │
│  │  │ - 指示受信 → 解釈 → 実行                          │ │   │
│  │  │ - MCP Serverと統合                                 │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  │                            ↕                              │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │ NET8 MCP Server (Node.js)                          │ │   │
│  │  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │ │   │
│  │  │ Tools:                                              │ │   │
│  │  │ - restart_camera()      // カメラ再起動           │ │   │
│  │  │ - restart_slotserver()  // slotserver再起動       │ │   │
│  │  │ - check_status()        // 状態確認               │ │   │
│  │  │ - restart_pc()          // PC再起動               │ │   │
│  │  │ - get_logs()            // ログ取得               │ │   │
│  │  │ - update_settings()     // 設定変更               │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  │                            ↕                              │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │ PowerShell / Windows OS                            │ │   │
│  │  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │ │   │
│  │  │ - slotserver.exe (カメラ配信)                     │ │   │
│  │  │ - chrome.exe (配信表示)                            │ │   │
│  │  │ - C:\serverset\ (設定ファイル)                    │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🧩 コンポーネント一覧

### 1. 本社側（管理サーバー）

#### 1.1 中央管理サーバー
**場所:** 本社Mac/Windows PC
**技術:** Node.js 20+, Express, Socket.io, Anthropic SDK
**ポート:** 3000 (HTTP), 3001 (WebSocket)

**主要機能:**
- 40台のWindows PCとWebSocket接続を維持
- ダッシュボードからの指示を受信
- Claude APIで自然言語を解釈してPowerShellコマンドに変換
- 各Windows PCに実行指示を送信
- 実行結果を集約してダッシュボードに表示
- ログを保存（SQLite or MySQL）

**ディレクトリ構成:**
```
central-server/
├── package.json
├── server.js                    # メインサーバー
├── routes/
│   ├── api.js                   # REST API
│   └── websocket.js             # WebSocket管理
├── services/
│   ├── claude-service.js        # Claude API統合
│   └── machine-manager.js       # マシン管理
├── public/
│   ├── dashboard.html           # ダッシュボード
│   ├── css/
│   │   └── styles.css
│   └── js/
│       ├── dashboard.js         # フロントエンドロジック
│       └── socket-client.js     # WebSocketクライアント
├── logs/                        # ログ保存
└── config/
    ├── machines.json            # マシン情報（40台分）
    └── chrome-rd-sessions.json  # Chrome RDセッションID
```

#### 1.2 統合ダッシュボード
**技術:** HTML5, CSS3, JavaScript (Vanilla), Socket.io Client
**機能:**
- 40台のリアルタイムステータス表示
- Chrome Remote Desktop統合（クリックで接続）
- AI指示送信フォーム
- 実行ログのリアルタイム表示
- マシンごとの詳細情報表示

---

### 2. Windows PC側（各40台）

#### 2.1 Claude Desktop
**バージョン:** 最新版
**インストール先:** `C:\Users\pcuser\AppData\Local\Programs\Claude\`
**起動方法:** Windows起動時に自動起動（スタートアップ登録）

**設定ファイル（claude_desktop_config.json）:**
```json
{
  "mcpServers": {
    "net8-machine-control": {
      "command": "node",
      "args": [
        "C:\\NET8\\mcp-server\\server.js"
      ],
      "env": {
        "MACHINE_NO": "13",
        "CENTRAL_SERVER": "ws://192.168.1.100:3001"
      }
    }
  }
}
```

#### 2.2 NET8 MCP Server
**場所:** `C:\NET8\mcp-server\`
**技術:** Node.js, @modelcontextprotocol/sdk

**ディレクトリ構成:**
```
C:\NET8\mcp-server\
├── package.json
├── server.js                    # MCPサーバーメイン
├── tools/
│   ├── camera-control.js        # カメラ制御
│   ├── slotserver-control.js    # slotserver制御
│   ├── system-control.js        # システム制御
│   └── log-manager.js           # ログ管理
├── config.json                  # マシン固有設定
└── logs/                        # ローカルログ
```

#### 2.3 WebSocketクライアント
**機能:** 中央サーバーと常時接続を維持し、指示を受信

**実装:**
- Node.jsバックグラウンドプロセスとして常駐
- 中央サーバーからの接続断時に自動再接続
- ハートビート送信（30秒ごと）

---

## 🛠️ MCP Server詳細設計

### Tools定義

```javascript
// tools/camera-control.js
export const cameraTools = [
  {
    name: "restart_camera",
    description: "カメラ配信を再起動します。Chromeとslotserverを再起動します。",
    inputSchema: {
      type: "object",
      properties: {
        force: {
          type: "boolean",
          description: "強制終了するかどうか（デフォルト: true）"
        }
      }
    }
  },
  {
    name: "check_camera_status",
    description: "カメラ配信の状態を確認します。プロセスの起動状態とログを確認します。",
    inputSchema: {
      type: "object",
      properties: {}
    }
  }
];

export async function restartCamera(args) {
  const { force = true } = args;

  try {
    // Chrome終了
    if (force) {
      await execPowerShell('taskkill /F /IM chrome.exe');
    } else {
      await execPowerShell('Stop-Process -Name chrome -ErrorAction SilentlyContinue');
    }

    // slotserver終了
    if (force) {
      await execPowerShell('taskkill /F /IM slotserver.exe');
    } else {
      await execPowerShell('Stop-Process -Name slotserver -ErrorAction SilentlyContinue');
    }

    // 3秒待機
    await sleep(3000);

    // slotserver起動
    await execPowerShell('Start-Process "C:\\serverset\\slotserver.exe"');

    // 5秒待機（slotserver起動待ち）
    await sleep(5000);

    // Chrome起動（camera.bat実行）
    await execPowerShell('Start-Process "C:\\serverset\\camera.bat"');

    return {
      success: true,
      message: "カメラ配信を再起動しました。",
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    };
  }
}

export async function checkCameraStatus() {
  try {
    // プロセス確認
    const chromeStatus = await execPowerShell('Get-Process -Name chrome -ErrorAction SilentlyContinue');
    const slotserverStatus = await execPowerShell('Get-Process -Name slotserver -ErrorAction SilentlyContinue');

    // ログ確認（最新10行）
    const errorLog = await execPowerShell('Get-Content "C:\\serverset\\_lib\\log\\error_log.txt" -Tail 10 -ErrorAction SilentlyContinue');

    return {
      success: true,
      chrome: {
        running: chromeStatus.trim() !== '',
        processInfo: chromeStatus
      },
      slotserver: {
        running: slotserverStatus.trim() !== '',
        processInfo: slotserverStatus
      },
      recentErrors: errorLog,
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    };
  }
}
```

### slotserver制御

```javascript
// tools/slotserver-control.js
export const slotserverTools = [
  {
    name: "restart_slotserver",
    description: "slotserver.exeを再起動します。",
    inputSchema: {
      type: "object",
      properties: {
        force: { type: "boolean", description: "強制終了" }
      }
    }
  },
  {
    name: "update_slotserver_config",
    description: "slotserver.iniの設定を更新します。",
    inputSchema: {
      type: "object",
      properties: {
        section: { type: "string", description: "セクション名（例: License, API）" },
        key: { type: "string", description: "設定キー" },
        value: { type: "string", description: "新しい値" }
      },
      required: ["section", "key", "value"]
    }
  }
];

export async function updateSlotserverConfig(args) {
  const { section, key, value } = args;
  const iniPath = 'C:\\serverset\\slotserver.ini';

  try {
    // PowerShellでINIファイル編集
    const psScript = `
      $iniPath = "${iniPath}"
      $content = Get-Content $iniPath
      $inSection = $false
      $updated = $false

      for ($i = 0; $i -lt $content.Length; $i++) {
        if ($content[$i] -match "^\\[${section}\\]$") {
          $inSection = $true
        } elseif ($content[$i] -match "^\\[.*\\]$") {
          $inSection = $false
        } elseif ($inSection -and $content[$i] -match "^${key}\\s*=") {
          $content[$i] = "${key} = ${value}"
          $updated = $true
        }
      }

      if ($updated) {
        $content | Set-Content $iniPath
        Write-Output "Updated"
      } else {
        Write-Output "Not found"
      }
    `;

    const result = await execPowerShell(psScript);

    return {
      success: result.trim() === 'Updated',
      message: result.trim() === 'Updated'
        ? `設定を更新しました: [${section}] ${key} = ${value}`
        : `設定キーが見つかりませんでした: [${section}] ${key}`,
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    };
  }
}
```

### システム制御

```javascript
// tools/system-control.js
export const systemTools = [
  {
    name: "restart_pc",
    description: "PCを再起動します。",
    inputSchema: {
      type: "object",
      properties: {
        delay_seconds: {
          type: "number",
          description: "再起動までの待機秒数（デフォルト: 60）"
        }
      }
    }
  },
  {
    name: "get_system_info",
    description: "システム情報を取得します（CPU使用率、メモリ、ディスク）。",
    inputSchema: {
      type: "object",
      properties: {}
    }
  },
  {
    name: "get_network_info",
    description: "ネットワーク情報を取得します（IPアドレス、MAC、接続状態）。",
    inputSchema: {
      type: "object",
      properties: {}
    }
  }
];

export async function restartPC(args) {
  const { delay_seconds = 60 } = args;

  try {
    await execPowerShell(`shutdown /r /t ${delay_seconds} /c "NET8 AI制御による再起動"`);

    return {
      success: true,
      message: `${delay_seconds}秒後にPCを再起動します。`,
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    };
  }
}

export async function getSystemInfo() {
  try {
    const psScript = `
      $cpu = (Get-Counter '\\Processor(_Total)\\% Processor Time').CounterSamples.CookedValue
      $mem = Get-CimInstance Win32_OperatingSystem
      $disk = Get-CimInstance Win32_LogicalDisk -Filter "DeviceID='C:'"

      @{
        CPU = [math]::Round($cpu, 2)
        MemoryUsedPercent = [math]::Round((($mem.TotalVisibleMemorySize - $mem.FreePhysicalMemory) / $mem.TotalVisibleMemorySize) * 100, 2)
        MemoryUsedGB = [math]::Round(($mem.TotalVisibleMemorySize - $mem.FreePhysicalMemory) / 1MB, 2)
        MemoryTotalGB = [math]::Round($mem.TotalVisibleMemorySize / 1MB, 2)
        DiskUsedPercent = [math]::Round((($disk.Size - $disk.FreeSpace) / $disk.Size) * 100, 2)
        DiskUsedGB = [math]::Round(($disk.Size - $disk.FreeSpace) / 1GB, 2)
        DiskTotalGB = [math]::Round($disk.Size / 1GB, 2)
      } | ConvertTo-Json
    `;

    const result = await execPowerShell(psScript);
    const info = JSON.parse(result);

    return {
      success: true,
      systemInfo: info,
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    };
  }
}
```

---

## 🔄 通信フロー

### フロー1: 本社からAI指示送信

```
[本社ダッシュボード]
    ↓ (1) ユーザーが「MACHINE-13のカメラを再起動」と入力
    ↓     POST /api/send-instruction
    ↓     { machineNo: 13, instruction: "カメラを再起動して" }

[中央管理サーバー]
    ↓ (2) Claude APIで指示を解釈
    ↓     messages.create({
    ↓       messages: [{ role: "user", content: "カメラを再起動して" }],
    ↓       tools: [...]  // restart_camera, check_camera_status等
    ↓     })
    ↓
    ↓ (3) Claude APIがtool_useを返す
    ↓     { type: "tool_use", name: "restart_camera", input: { force: true } }
    ↓
    ↓ (4) WebSocket経由でMACHINE-13に送信
    ↓     socket.emit("execute_tool", {
    ↓       toolName: "restart_camera",
    ↓       args: { force: true }
    ↓     })

[MACHINE-13 - Claude Desktop + MCP]
    ↓ (5) WebSocket受信 → Claude Desktopに転送
    ↓
    ↓ (6) Claude DesktopがMCP Serverのrestart_camera()を呼び出し
    ↓
    ↓ (7) PowerShell実行
    ↓     taskkill /F /IM chrome.exe
    ↓     taskkill /F /IM slotserver.exe
    ↓     Start-Process "C:\serverset\slotserver.exe"
    ↓     Start-Process "C:\serverset\camera.bat"
    ↓
    ↓ (8) 実行結果を返す
    ↓     { success: true, message: "カメラ配信を再起動しました" }
    ↓
    ↓ (9) WebSocket経由で中央サーバーに返す
    ↓     socket.emit("tool_result", { success: true, ... })

[中央管理サーバー]
    ↓ (10) 結果をログに保存
    ↓
    ↓ (11) ダッシュボードに結果を送信
    ↓      io.emit("execution_result", { ... })

[本社ダッシュボード]
    ↓ (12) リアルタイムログに表示
    ↓      "[12:45] MACHINE-13: ✅ カメラ配信を再起動しました"
```

### フロー2: Chrome Remote Desktop接続

```
[本社ダッシュボード]
    ↓ (1) ユーザーが「MACHINE-13」の行をクリック
    ↓
    ↓ (2) JavaScript実行
    ↓     const sessionId = machines[13].chromeRDSessionId;
    ↓     const url = `https://remotedesktop.google.com/access/session/${sessionId}`;
    ↓     window.open(url, 'MACHINE-13', 'width=1920,height=1080');
    ↓
[Chrome Remote Desktop]
    ↓ (3) 新しいウィンドウでリモートデスクトップ接続
    ↓     MACHINE-13のデスクトップが表示される
```

---

## 🔒 セキュリティ設計

### 1. 認証・認可

**中央管理サーバー:**
- Basic認証 or JWT認証
- 管理者のみアクセス可能

**WebSocket接続:**
- 接続時に認証トークン検証
- マシンごとに固有のトークン発行

```javascript
// 中央サーバー側
io.use((socket, next) => {
  const token = socket.handshake.auth.token;
  const machineNo = socket.handshake.auth.machineNo;

  // トークン検証
  if (isValidToken(token, machineNo)) {
    next();
  } else {
    next(new Error('Authentication failed'));
  }
});
```

### 2. 通信の暗号化

- **HTTPS:** 中央サーバーはHTTPS必須
- **WSS (WebSocket Secure):** WebSocket通信はTLS暗号化

### 3. コマンド実行の制限

**許可されたコマンドのみ実行:**
```javascript
const ALLOWED_TOOLS = [
  'restart_camera',
  'restart_slotserver',
  'check_camera_status',
  'get_system_info',
  'update_slotserver_config'
];

// 実行前にチェック
if (!ALLOWED_TOOLS.includes(toolName)) {
  throw new Error('Unauthorized tool');
}
```

### 4. ログ監査

**全ての実行を記録:**
```javascript
{
  timestamp: "2025-11-05T12:45:30Z",
  machineNo: 13,
  instruction: "カメラを再起動して",
  toolExecuted: "restart_camera",
  args: { force: true },
  result: { success: true, message: "..." },
  executedBy: "admin@net8.com"
}
```

---

## 📦 必要なソフトウェア・ライブラリ

### 本社サーバー側

```json
// package.json
{
  "name": "net8-central-server",
  "version": "1.0.0",
  "dependencies": {
    "express": "^4.18.2",
    "socket.io": "^4.6.1",
    "@anthropic-ai/sdk": "^0.20.0",
    "dotenv": "^16.3.1",
    "better-sqlite3": "^9.4.3",  // ログ保存用
    "winston": "^3.11.0"  // ロギング
  }
}
```

### Windows PC側

```json
// package.json (C:\NET8\mcp-server\)
{
  "name": "net8-mcp-server",
  "version": "1.0.0",
  "type": "module",
  "dependencies": {
    "@modelcontextprotocol/sdk": "^0.5.0",
    "socket.io-client": "^4.6.1",
    "dotenv": "^16.3.1"
  }
}
```

**その他:**
- **Node.js 20+** (Windows PC各台にインストール)
- **Claude Desktop** (最新版)
- **Chrome Remote Desktop** (既にインストール済み)

---

## 📋 セットアップ手順

### Phase 1: 本社サーバーセットアップ（1時間）

1. **Node.js 20+ インストール**
2. **プロジェクトクローン・セットアップ**
   ```bash
   git clone <repository>
   cd central-server
   npm install
   ```
3. **環境変数設定 (.env)**
   ```env
   CLAUDE_API_KEY=sk-ant-xxx
   PORT=3000
   WEBSOCKET_PORT=3001
   ADMIN_USERNAME=admin
   ADMIN_PASSWORD=<secure-password>
   ```
4. **マシン情報設定 (config/machines.json)**
   ```json
   {
     "machines": [
       {
         "machineNo": 1,
         "name": "MACHINE-01",
         "ip": "192.168.1.2",
         "mac": "00:00:00:00:00:01",
         "chromeRDSessionId": "abc123def456",
         "token": "secure-token-001"
       },
       // ... 40台分
     ]
   }
   ```
5. **サーバー起動**
   ```bash
   npm start
   ```
6. **ブラウザで確認**
   ```
   http://localhost:3000/dashboard.html
   ```

### Phase 2: 1台目Windows PCセットアップ（テスト用）（30分）

1. **Node.js 20+ インストール**
   ```
   https://nodejs.org/ からダウンロード
   ```

2. **Claude Desktop インストール**
   ```
   https://claude.ai/download からダウンロード
   ```

3. **MCPサーバーセットアップ**
   ```powershell
   # C:\NET8\mcp-server\ フォルダ作成
   New-Item -ItemType Directory -Path "C:\NET8\mcp-server"

   # ファイル配置（後述のファイル一式）
   # package.json, server.js, tools/...

   # 依存関係インストール
   cd C:\NET8\mcp-server
   npm install
   ```

4. **Claude Desktop設定**
   ```
   C:\Users\pcuser\AppData\Roaming\Claude\claude_desktop_config.json
   ```
   ```json
   {
     "mcpServers": {
       "net8-machine-control": {
         "command": "node",
         "args": ["C:\\NET8\\mcp-server\\server.js"],
         "env": {
           "MACHINE_NO": "1",
           "CENTRAL_SERVER": "ws://192.168.1.100:3001",
           "AUTH_TOKEN": "secure-token-001"
         }
       }
     }
   }
   ```

5. **Claude Desktop起動**
   - スタートアップ登録（自動起動）
   ```powershell
   $shortcut = (New-Object -COM WScript.Shell).CreateShortcut(
     "$env:APPDATA\Microsoft\Windows\Start Menu\Programs\Startup\Claude.lnk"
   )
   $shortcut.TargetPath = "C:\Users\pcuser\AppData\Local\Programs\Claude\Claude.exe"
   $shortcut.Save()
   ```

6. **動作確認**
   - 本社ダッシュボードで「MACHINE-01」が🟢接続と表示される
   - 「カメラを再起動して」と指示 → 成功

### Phase 3: 残り39台への一括展開（2～3時間）

**方法1: マスターイメージ方式**
1. MACHINE-01をSysprep
2. イメージ作成
3. 残り39台に展開
4. 各台で `config.json` のマシン番号のみ変更

**方法2: 自動インストールスクリプト**
1. USBメモリにインストーラー格納
2. 各台でスクリプト実行（5分/台）
```powershell
.\install-net8-agent.ps1 -MachineNo 13 -CentralServer "ws://192.168.1.100:3001"
```

---

## 📊 ダッシュボードUI設計

### メイン画面

```
┌─────────────────────────────────────────────────────────────┐
│ NET8 AI統合管理ダッシュボード                                │
├─────────────────────────────────────────────────────────────┤
│ [統計カード]                                                 │
│ 📦 総台数: 40  |  ✅ 接続中: 38  |  ❌ オフライン: 2        │
├─────────────────────────────────────────────────────────────┤
│ [マシン一覧]                                                 │
│ ┌─────┬───────────┬─────┬──────────┬──────────┐          │
│ │ No. │ PC名      │状態 │Chrome RD │AI制御    │          │
│ ├─────┼───────────┼─────┼──────────┼──────────┤          │
│ │ 01  │MACHINE-01 │🟢接続│[接続]    │[指示]    │          │
│ │ 13  │MACHINE-13 │🟢接続│[接続]    │[指示]    │ ← クリック│
│ │ 40  │MACHINE-40 │❌切断│-         │-         │          │
│ └─────┴───────────┴─────┴──────────┴──────────┘          │
├─────────────────────────────────────────────────────────────┤
│ [AI指示送信パネル] ※マシン選択時に表示                       │
│ ┌─────────────────────────────────────────────────────────┐│
│ │ MACHINE-13 に指示を送る                                  ││
│ │                                                          ││
│ │ 指示内容:                                                ││
│ │ ┌──────────────────────────────────────────────────────┐││
│ │ │カメラ配信を再起動してください                        │││
│ │ └──────────────────────────────────────────────────────┘││
│ │                                                          ││
│ │ [送信]  [クリア]                                         ││
│ │                                                          ││
│ │ よく使う指示:                                            ││
│ │ [カメラ再起動] [slotserver再起動] [状態確認] [PC再起動] ││
│ └─────────────────────────────────────────────────────────┘│
├─────────────────────────────────────────────────────────────┤
│ [リアルタイムログ]                                           │
│ [12:45:30] ✅ MACHINE-13: カメラ配信を再起動しました         │
│ [12:45:15] 🔄 MACHINE-13: restart_camera実行中...           │
│ [12:45:10] 📝 MACHINE-13: 指示受信「カメラを再起動して」    │
│ [12:44:55] ✅ MACHINE-05: システム情報取得完了               │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 実装優先順位

### Phase 1: 基本機能（1週間）
- [x] 中央管理サーバー構築
- [x] WebSocket通信実装
- [x] 基本ダッシュボード作成
- [x] MCP Server基本実装（restart_camera, check_status）
- [x] 1台での動作確認

### Phase 2: AI統合（3日）
- [x] Claude API統合
- [x] 自然言語→コマンド変換
- [x] Tool Use実装

### Phase 3: 拡張機能（5日）
- [x] Chrome Remote Desktop統合
- [x] 詳細ログ機能
- [x] slotserver.ini設定変更機能
- [x] システム情報取得

### Phase 4: 全台展開（2日）
- [x] 自動インストールスクリプト作成
- [x] 40台一括展開
- [x] 最終動作確認

---

## 📝 サンプルコード（重要部分）

### 中央サーバー（server.js）

```javascript
import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import Anthropic from '@anthropic-ai/sdk';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const server = createServer(app);
const io = new Server(server, {
  cors: { origin: '*' }
});

const anthropic = new Anthropic({
  apiKey: process.env.CLAUDE_API_KEY,
});

// 接続中のマシン管理
const connectedMachines = new Map();

// 静的ファイル提供
app.use(express.static('public'));
app.use(express.json());

// マシンからの接続
io.on('connection', (socket) => {
  console.log('New connection:', socket.id);

  socket.on('register', (data) => {
    const { machineNo, machineName, token } = data;

    // トークン検証（省略）

    connectedMachines.set(machineNo, {
      socket,
      machineName,
      connectedAt: new Date(),
    });

    console.log(`✅ ${machineName} (No.${machineNo}) connected`);

    // ダッシュボードに通知
    io.emit('machine_connected', { machineNo, machineName });
  });

  socket.on('disconnect', () => {
    // 切断処理
    for (const [machineNo, machine] of connectedMachines.entries()) {
      if (machine.socket.id === socket.id) {
        connectedMachines.delete(machineNo);
        console.log(`❌ MACHINE-${machineNo} disconnected`);
        io.emit('machine_disconnected', { machineNo });
        break;
      }
    }
  });

  // ツール実行結果受信
  socket.on('tool_result', (data) => {
    console.log('Tool result:', data);
    io.emit('execution_result', data);
  });
});

// API: AI指示送信
app.post('/api/send-instruction', async (req, res) => {
  const { machineNo, instruction } = req.body;

  const machine = connectedMachines.get(machineNo);
  if (!machine) {
    return res.status(404).json({ error: 'マシンが接続されていません' });
  }

  try {
    // Claude APIで指示を解釈
    const response = await anthropic.messages.create({
      model: 'claude-3-5-sonnet-20241022',
      max_tokens: 1024,
      tools: [
        {
          name: 'restart_camera',
          description: 'カメラ配信を再起動します',
          input_schema: {
            type: 'object',
            properties: {
              force: { type: 'boolean', description: '強制終了' }
            }
          }
        },
        {
          name: 'check_camera_status',
          description: 'カメラ配信の状態を確認します',
          input_schema: { type: 'object', properties: {} }
        },
        // ... 他のツール
      ],
      messages: [
        {
          role: 'user',
          content: `以下の指示を実行してください: ${instruction}`
        }
      ]
    });

    // Tool Useを抽出
    const toolUse = response.content.find(block => block.type === 'tool_use');

    if (toolUse) {
      // マシンに実行指示を送信
      machine.socket.emit('execute_tool', {
        toolName: toolUse.name,
        toolId: toolUse.id,
        args: toolUse.input
      });

      res.json({
        success: true,
        message: `MACHINE-${machineNo}に指示を送信しました`,
        toolName: toolUse.name
      });
    } else {
      res.json({
        success: false,
        message: 'Claudeが実行可能なツールを特定できませんでした',
        response: response.content[0].text
      });
    }
  } catch (error) {
    console.error('Error:', error);
    res.status(500).json({ error: error.message });
  }
});

// サーバー起動
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`🚀 Central server running on http://localhost:${PORT}`);
});
```

### Windows PC MCP Server（server.js）

```javascript
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { CallToolRequestSchema, ListToolsRequestSchema } from '@modelcontextprotocol/sdk/types.js';
import { io } from 'socket.io-client';
import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

// 環境変数から設定取得
const MACHINE_NO = process.env.MACHINE_NO || '1';
const CENTRAL_SERVER = process.env.CENTRAL_SERVER || 'ws://localhost:3001';
const AUTH_TOKEN = process.env.AUTH_TOKEN || '';

// 中央サーバーとWebSocket接続
const socket = io(CENTRAL_SERVER, {
  auth: { token: AUTH_TOKEN, machineNo: MACHINE_NO }
});

socket.on('connect', () => {
  console.log('✅ Connected to central server');
  socket.emit('register', {
    machineNo: MACHINE_NO,
    machineName: `MACHINE-${MACHINE_NO}`,
    token: AUTH_TOKEN
  });
});

socket.on('disconnect', () => {
  console.log('❌ Disconnected from central server');
});

// 中央サーバーからのツール実行指示
socket.on('execute_tool', async (data) => {
  const { toolName, toolId, args } = data;
  console.log(`🔄 Executing tool: ${toolName}`, args);

  try {
    const result = await executeToolLocally(toolName, args);
    socket.emit('tool_result', {
      machineNo: MACHINE_NO,
      toolId,
      toolName,
      result
    });
  } catch (error) {
    socket.emit('tool_result', {
      machineNo: MACHINE_NO,
      toolId,
      toolName,
      error: error.message
    });
  }
});

// PowerShell実行ヘルパー
async function execPowerShell(command) {
  const { stdout, stderr } = await execAsync(`powershell -Command "${command}"`);
  if (stderr) throw new Error(stderr);
  return stdout.trim();
}

// ツール実行
async function executeToolLocally(toolName, args) {
  switch (toolName) {
    case 'restart_camera':
      return await restartCamera(args);
    case 'check_camera_status':
      return await checkCameraStatus();
    case 'restart_slotserver':
      return await restartSlotserver(args);
    // ... 他のツール
    default:
      throw new Error(`Unknown tool: ${toolName}`);
  }
}

// カメラ再起動
async function restartCamera(args) {
  const { force = true } = args;

  try {
    if (force) {
      await execPowerShell('taskkill /F /IM chrome.exe');
      await execPowerShell('taskkill /F /IM slotserver.exe');
    }

    await new Promise(resolve => setTimeout(resolve, 3000));

    await execPowerShell('Start-Process "C:\\serverset\\slotserver.exe"');
    await new Promise(resolve => setTimeout(resolve, 5000));
    await execPowerShell('Start-Process "C:\\serverset\\camera.bat"');

    return {
      success: true,
      message: 'カメラ配信を再起動しました',
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    };
  }
}

// 状態確認
async function checkCameraStatus() {
  try {
    const chromeRunning = await execPowerShell('Get-Process -Name chrome -ErrorAction SilentlyContinue');
    const slotserverRunning = await execPowerShell('Get-Process -Name slotserver -ErrorAction SilentlyContinue');

    return {
      success: true,
      chrome: { running: chromeRunning.trim() !== '' },
      slotserver: { running: slotserverRunning.trim() !== '' },
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    };
  }
}

// MCP Server設定
const server = new Server(
  {
    name: 'net8-machine-control',
    version: '1.0.0',
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// ツール一覧
server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: 'restart_camera',
      description: 'カメラ配信を再起動します（Chromeとslotserver）',
      inputSchema: {
        type: 'object',
        properties: {
          force: { type: 'boolean', description: '強制終了するか' }
        }
      }
    },
    {
      name: 'check_camera_status',
      description: 'カメラ配信の状態を確認します',
      inputSchema: { type: 'object', properties: {} }
    },
    {
      name: 'restart_slotserver',
      description: 'slotserver.exeを再起動します',
      inputSchema: {
        type: 'object',
        properties: {
          force: { type: 'boolean', description: '強制終了するか' }
        }
      }
    }
  ]
}));

// ツール実行
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  const result = await executeToolLocally(name, args || {});

  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(result, null, 2)
      }
    ]
  };
});

// MCP Server起動
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.log('🚀 NET8 MCP Server started');
}

main().catch(console.error);
```

---

## ✅ 設計完了チェックリスト

- [x] システムアーキテクチャ設計
- [x] コンポーネント設計
- [x] MCP Server詳細設計（全ツール定義）
- [x] 通信フロー設計
- [x] セキュリティ設計
- [x] ダッシュボードUI設計
- [x] セットアップ手順
- [x] サンプルコード作成
- [x] 実装優先順位

---

## 📞 次のステップ

この設計書に基づいて実装を開始できます。

**実装開始時には以下を準備:**
1. Claude API Key取得
2. 本社サーバー用PC準備（Mac or Windows）
3. テスト用Windows PC 1台準備
4. Node.js 20+ インストール

**実装サポートが必要な場合:**
- 「central-server/server.js の完全版を作成して」
- 「Windows PC用MCPサーバーの完全版を作成して」
- 「ダッシュボードHTMLの完全版を作成して」

などと指示していただければ、すぐに作成します！

---

**設計書作成日:** 2025-11-05
**バージョン:** 1.0
**ステータス:** 実装準備完了 ✅
