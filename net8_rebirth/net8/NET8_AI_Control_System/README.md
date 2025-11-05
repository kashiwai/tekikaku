# NET8 AI統合管理システム - セットアップ手順

## 📋 システム概要

40台のWindows PCを本社Macから一括管理・AI制御するシステム

```
本社Mac ←→ Railway WebSocket Hub ←→ 各Windows PC（40台）
```

---

## 🚀 セットアップ手順（30分で完了）

### **ステップ1: Railway WebSocket Hubをデプロイ（10分）**

```bash
# ディレクトリ移動
cd NET8_AI_Control_System/railway-websocket-hub

# 依存関係インストール
npm install

# Railwayにログイン
railway login

# 新しいプロジェクト作成
railway init

# デプロイ
railway up

# URLを確認（メモしておく）
railway domain
# 例: net8-websocket-hub.up.railway.app
```

**Railway Dashboardで環境変数設定（オプション）:**
```
AUTH_TOKEN=your-secure-token-here
```

---

### **ステップ2: 本社Macでダッシュボードをセットアップ（10分）**

```bash
# ディレクトリ移動
cd NET8_AI_Control_System/central-dashboard

# 依存関係インストール
npm install

# .envファイル作成
cat > .env <<EOF
CLAUDE_API_KEY=sk-ant-your-api-key-here
HUB_URL=wss://net8-websocket-hub.up.railway.app
PORT=3000
EOF

# サーバー起動
npm start

# ブラウザでダッシュボードを開く
open http://localhost:3000
```

---

### **ステップ3: Windows PC 1台目にエージェントインストール（10分）**

#### 3-1. Node.jsインストール（未インストールの場合）

```powershell
# PowerShell（管理者権限）で実行
winget install OpenJS.NodeJS.LTS

# または https://nodejs.org/ からダウンロード
```

#### 3-2. エージェントファイルをコピー

```
windows-agent フォルダを Windows PCにコピー
例: C:\Users\pcuser\Downloads\windows-agent\
```

#### 3-3. インストーラーを実行

```powershell
# PowerShell（管理者権限）で実行
cd C:\Users\pcuser\Downloads\windows-agent
.\install.bat

# マシン番号を入力（例: 1）
マシン番号を入力してください (1-40): 1
```

#### 3-4. .envファイルを編集（Railway URLを設定）

```
C:\NET8\agent\.env を編集

MACHINE_NO=1
MACHINE_NAME=MACHINE-01
HUB_URL=wss://net8-websocket-hub.up.railway.app  ← Railway URLに変更
AUTH_TOKEN=
```

#### 3-5. エージェント起動

```powershell
cd C:\NET8\agent
node agent.js

# 以下が表示されれば成功
# ✅ Connected to WebSocket Hub
# ✅ Registered successfully
```

---

### **ステップ4: 動作確認（5分）**

#### 4-1. 本社ダッシュボードで確認

```
http://localhost:3000 をブラウザで開く

→ マシン一覧に「MACHINE-1」が🟢接続中で表示される
```

#### 4-2. AI指示テスト

```
ダッシュボードで:
1. マシン選択: MACHINE-1
2. 指示内容: システムの状態を確認してください
3. [📤 指示を送信] クリック

→ リアルタイムログに実行結果が表示される
```

#### 4-3. カメラ再起動テスト

```
ダッシュボードで:
1. MACHINE-1のカードで [🎥 カメラ再起動] クリック

→ Windows PC側でslotserver.exe, chrome.exeが再起動される
→ ログに「✅ MACHINE-1: 実行成功」と表示される
```

---

### **ステップ5: 残り39台への展開（2時間）**

#### 方法1: Windows Agentフォルダを配布

1. `windows-agent` フォルダをUSBメモリにコピー
2. 各Windows PCでインストーラー実行
3. マシン番号のみ変更（2～40）

#### 方法2: ネットワーク共有からインストール

```powershell
# 共有フォルダから実行
\\MASTER-PC\share\windows-agent\install.bat

# マシン番号を入力
マシン番号を入力してください (1-40): 13
```

---

## 📊 ディレクトリ構成

```
NET8_AI_Control_System/
├── railway-websocket-hub/      # Railway中継サーバー
│   ├── server.js               # WebSocket Hub本体
│   ├── package.json
│   └── README.md
│
├── central-dashboard/          # 本社ダッシュボード（Mac）
│   ├── server.js               # ダッシュボードサーバー
│   ├── public/
│   │   └── index.html          # ダッシュボードUI
│   ├── package.json
│   └── .env.example
│
└── windows-agent/              # Windows PCエージェント
    ├── agent.js                # 軽量エージェント
    ├── install.bat             # インストーラー
    ├── package.json
    └── .env.example
```

---

## 🔧 環境変数一覧

### Railway WebSocket Hub
```
PORT=3001
AUTH_TOKEN=（オプション）
```

### 本社ダッシュボード
```
CLAUDE_API_KEY=sk-ant-xxx
HUB_URL=wss://net8-websocket-hub.up.railway.app
PORT=3000
```

### Windows PCエージェント
```
MACHINE_NO=1
MACHINE_NAME=MACHINE-01
HUB_URL=wss://net8-websocket-hub.up.railway.app
AUTH_TOKEN=
```

---

## 🎯 使い方

### 基本的なAI指示

```
「カメラ配信を再起動してください」
「システムの状態を確認してください」
「slotserverを再起動してください」
「60秒後にPCを再起動してください」
```

### クイックコマンドボタン

- 🎥 カメラ再起動
- 📊 状態確認
- 🔄 slotserver再起動
- 🔌 PC再起動(60秒後)

### 一括指示（全台）

```javascript
// 将来実装予定
ダッシュボード > 全台選択 > 指示送信
```

---

## 🔍 トラブルシューティング

### エージェントが接続できない

**確認事項:**
1. HUB_URLが正しいか（.envファイル）
2. インターネット接続があるか
3. ファイアウォールでNode.jsがブロックされていないか

**解決方法:**
```powershell
# ファイアウォール許可を追加
netsh advfirewall firewall add rule name="Node.js" dir=in action=allow program="C:\Program Files\nodejs\node.exe"
```

### ダッシュボードにマシンが表示されない

**確認事項:**
1. エージェントが起動しているか（`C:\NET8\agent\start.bat`）
2. Railway Hubが稼働しているか（`https://net8-websocket-hub.up.railway.app/`）
3. 本社ダッシュボードが起動しているか（`http://localhost:3000`）

### Claude APIエラー

**エラーメッセージ:**
```
Error: Invalid API key
```

**解決方法:**
```bash
# .envファイルを確認
cat central-dashboard/.env

# Claude API Keyを取得
# https://console.anthropic.com/
```

---

## 📞 サポート

問題が解決しない場合は、ログファイルを確認してください：

- **Railway Hub**: Railwayダッシュボード > Logs
- **本社ダッシュボード**: ターミナル出力
- **Windows PCエージェント**: `C:\NET8\agent\` のターミナル出力

---

## ✅ セットアップ完了チェックリスト

- [ ] Railway WebSocket Hubデプロイ完了
- [ ] Railway URLを取得（wss://xxx.up.railway.app）
- [ ] 本社ダッシュボード起動（http://localhost:3000）
- [ ] Windows PC 1台目エージェントインストール
- [ ] ダッシュボードにMACHINE-1が🟢接続中で表示
- [ ] AI指示テスト成功
- [ ] カメラ再起動テスト成功
- [ ] 残り39台展開計画確定

---

**作成日:** 2025-11-05
**バージョン:** 1.0.0
**ステータス:** 実装完了 ✅
