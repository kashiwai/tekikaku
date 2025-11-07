# NET8 WebRTCストリーミングシステム 完全ドキュメント

## 📋 目次

1. [システム概要](#システム概要)
2. [アーキテクチャ](#アーキテクチャ)
3. [サーバー構成](#サーバー構成)
4. [接続フロー](#接続フロー)
5. [プレイヤー側実装](#プレイヤー側実装)
6. [Windows PC側実装](#windows-pc側実装)
7. [トラブルシューティング](#トラブルシューティング)
8. [デバッグ方法](#デバッグ方法)

---

## システム概要

### プロジェクト名
NET8 オンラインパチンコ・スロットゲームシステム

### 目的
実機のパチンコ・スロット台をWebRTC経由でブラウザから遠隔操作可能にする

### 主要技術スタック
- **フロントエンド**: HTML5, JavaScript (jQuery), PeerJS
- **バックエンド**: PHP 7.4+, Apache
- **WebRTC**: PeerJS (シグナリング), STUN/TURN (ICE)
- **データベース**: MySQL 8.0 (GCP Cloud SQL)
- **デプロイ**: Railway (Docker)
- **シグナリングサーバー**: Railway (Node.js)

---

## アーキテクチャ

### システム全体図

```
[ユーザー(ブラウザ)]
        ↓ HTTPS
[Railwayサーバー (Webサーバー + PHPアプリ)]
        ↓ WebSocket (PeerJS)
[Railwayシグナリングサーバー]
        ↓ WebRTC (P2P)
[Windows PC (カメラ配信 + ゲーム制御)]
        ↓ 物理接続
[実機パチンコ・スロット台]
```

### コンポーネント構成

#### 1. Webサーバー (Railway)
- **URL**: `https://mgg-webservice-production.up.railway.app`
- **役割**:
  - Webアプリケーションホスティング
  - 認証・ユーザー管理
  - ゲームロジック処理
  - WebRTCクライアント配信

#### 2. シグナリングサーバー (Railway)
- **URL**: `mgg-signaling-production-c1bd.up.railway.app:443`
- **役割**:
  - PeerJS シグナリング
  - WebRTC接続の仲介
  - Peer ID管理

#### 3. データベース (GCP Cloud SQL)
- **役割**:
  - ユーザー情報管理
  - マシン状態管理
  - ゲーム履歴保存
  - ポイント管理

#### 4. Windows PC (カメラ配信側)
- **役割**:
  - Webカメラで実機を撮影
  - WebRTC経由で映像配信
  - ブラウザからの操作コマンド受信
  - 実機への物理操作実行

---

## サーバー構成

### 1. Railwayサーバー設定

#### Docker構成
```
DocumentRoot: /var/www/html/data
```

#### Apache設定 (`docker/web/apache-config/000-default.conf`)

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    # DirectoryIndex設定
    DirectoryIndex index.php index.html

    <Directory /var/www/html>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # RewriteRuleでルートアクセスを /data/ へ転送
    RewriteEngine On

    # 静的リソースを除外
    RewriteCond %{REQUEST_URI} !^/_api/
    RewriteCond %{REQUEST_URI} !^/api/
    RewriteCond %{REQUEST_URI} !^/sdk/
    RewriteCond %{REQUEST_URI} !^/data/
    RewriteCond %{REQUEST_URI} !^/css/
    RewriteCond %{REQUEST_URI} !^/js/
    RewriteCond %{REQUEST_URI} !^/vendor/
    RewriteCond %{REQUEST_URI} !^/content/
    RewriteCond %{REQUEST_URI} !^/img/
    RewriteCond %{REQUEST_URI} !^/ryujin8_assets/

    # それ以外は /data/ へ内部リダイレクト
    RewriteRule ^(.*)$ /data/$1 [L]
</VirtualHost>
```

**重要ポイント**:
- `/vendor/` は除外されているが、実際のファイルは `/data/play_v2/vendor/` にある
- HTMLテンプレートでは **絶対パス** `/data/play_v2/vendor/` を使用する必要がある

### 2. 環境変数設定

```bash
# Webサーバー URL
URL_SSL_SITE=https://mgg-webservice-production.up.railway.app/data/

# シグナリングサーバー
SIGNALING_HOST=mgg-signaling-production-c1bd.up.railway.app
SIGNALING_PORT=443

# データベース (GCP Cloud SQL)
DB_HOST=<GCP Cloud SQL IP>
DB_NAME=net8_db
DB_USER=net8_user
DB_PASS=<パスワード>
```

### 3. ディレクトリ構造

```
/var/www/html/
├── data/                          # DocumentRoot
│   ├── play/                      # プレイ画面（エントリーポイント）
│   │   └── index.php              # 認証 + リダイレクト
│   ├── play_v2/                   # プレイ画面（メイン処理）
│   │   ├── index.php              # 認証 + テンプレート選択
│   │   ├── checkpoint.php         # ポイント期限チェック
│   │   ├── css/                   # CSS
│   │   ├── js/                    # JavaScript
│   │   │   ├── view_auth.js       # WebRTC接続 + 認証
│   │   │   ├── view_functions.js  # ゲームロジック
│   │   │   ├── playground.js      # UI制御
│   │   │   └── peer_ie.js         # PeerJS IEポリフィル
│   │   └── vendor/                # 外部ライブラリ
│   │       ├── jquery/
│   │       ├── bootstrap/
│   │       └── velocity/
│   ├── api/                       # API
│   │   ├── debug_play_check.php   # マシン状態確認
│   │   ├── reset_machine_mode.php # マシンリセット
│   │   ├── charge_playpoint.php   # ポイントチャージ
│   │   └── list_members.php       # ユーザー一覧
│   └── login/                     # ログイン
│       └── index.php
├── _html/                         # HTMLテンプレート
│   └── ja/
│       └── play/
│           ├── index_slot_ls_v2.html      # スロット横画面
│           ├── index_pachi_ls_v2.html     # パチンコ横画面
│           └── ...
├── _sys/                          # システムクラス
│   ├── TemplateUser.php
│   ├── WebRTCAPI.php
│   └── ...
└── _etc/                          # 設定ファイル
    ├── setting_base.php
    ├── webRTC_setting.php
    └── ...
```

---

## 接続フロー

### 1. ユーザーアクセスから映像表示までの完全フロー

```
Step 1: ユーザーがブラウザでアクセス
  URL: https://mgg-webservice-production.up.railway.app/data/play/?NO=1
  ↓

Step 2: Apache RewriteRule による内部リダイレクト
  (すでに /data/ で始まるので RewriteRule は適用されない)
  ↓

Step 3: /data/play/index.php 実行
  - Line 71-72: リダイレクト
    header("Location: /data/play_v2/?NO=1");
    return;
  ↓

Step 4: ブラウザリダイレクト
  URL: https://mgg-webservice-production.up.railway.app/data/play_v2/?NO=1
  ↓

Step 5: /data/play_v2/index.php 実行
  ├─ Line 81-86: ログインチェック
  ├─ Line 88-100: テスターフラグ確認
  ├─ Line 102-109: 営業時間チェック
  ├─ Line 111-127: マシン状態チェック (dat_machine)
  ├─ Line 129-153: マシン割り当てチェック (lnk_machine)
  ├─ Line 155-179: ブラウザチェック
  ├─ Line 181-235: カメラ・シグナリングサーバー情報取得
  ├─ Line 237-261: WebRTC認証トークン生成
  ├─ Line 263-295: レイアウト・機種情報取得
  ├─ Line 317-330: テンプレート選択
  │   if (category == "1") {  // パチンコ
  │       if (video_portrait == "1") {
  │           template = "play/index_pachi.html";
  │       } else {
  │           template = "play/index_pachi_ls_v2.html";
  │       }
  │   } else {  // スロット
  │       if (video_portrait == "1") {
  │           template = "play/index_slot.html";
  │       } else {
  │           template = "play/index_slot_ls_v2.html";
  │       }
  │   }
  └─ Line 332-392: HTMLに変数を代入してレンダリング
  ↓

Step 6: HTMLレンダリング (index_slot_ls_v2.html)
  - Line 11: Bootstrap CSS読み込み
    <link href="/data/play_v2/vendor/bootstrap/css/bootstrap.min.css">
  - Line 16: スタイルCSS読み込み
    <link href="/data/play_v2/css/styles_ls_v001.css">
  - Line 19-38: PHP変数をJavaScriptに代入
    var cameraid = '{%CAMERA_ID%}';
    var sigHost = '{%SIGHOST%}';
    var sigPort = '{%SIGPORT%}';
    var iceServers = {%ICESERVERS%};
    var memberno = '{%MEMBERNO%}';
    var authID = '{%AUTHID%}';
  - Line 519-526: JavaScript読み込み
    <script src="/data/play_v2/vendor/jquery/jquery-3.3.1.min.js">
    <script src="/data/play_v2/js/view_auth.js">
  ↓

Step 7: JavaScript実行 (view_auth.js)
  ├─ Line 108-120: PeerJS設定
  │   var peersetting = {
  │       host: sigHost,
  │       port: sigPort,
  │       key: peerjskey,
  │       token: authID,
  │       config: {
  │           'iceServers': iceServers,
  │           "iceTransportPolicy":"all"
  │       }
  │   };
  ├─ Line 143-154: Peer接続開始
  │   var peer = new Peer(memberno, peersetting);
  │   peer.on('open', function() {
  │       // シグナリングサーバー接続成功
  │       dataConnection = peer.connect(cameraid, {
  │           'metadata': memberno + ':' + authID
  │       });
  │   });
  └─ Line 949-972: 映像ストリーム受信
      peer.on('call', function(call) {
          call.answer();  // 通話応答
          call.on('stream', function(stream) {
              document.getElementById('video').srcObject = stream;
              $('#video').show();
          });
      });
  ↓

Step 8: シグナリングサーバーで接続確立
  - Peer ID: memberno (例: "8a15ca25c36d74bcc7c4ad77f284e0a2551d0344")
  - Camera ID: cameraid (例: "camera_10000021_1762489262")
  - メタデータで認証: memberno + ':' + authID
  ↓

Step 9: Windows PC側とP2P接続
  - ICE候補交換
  - STUN/TURNサーバー経由でNAT越え
  - WebRTC P2P接続確立
  ↓

Step 10: 映像ストリーム配信開始
  - Windows PC → ブラウザ: 映像データ (WebRTC)
  - ブラウザ → Windows PC: 操作コマンド (DataChannel)
  ↓

Step 11: ゲームプレイ開始
  ✅ 映像表示
  ✅ ボタン操作可能
  ✅ ゲームカウンター更新
```

### 2. WebRTC接続シーケンス図

```
[ブラウザ]              [シグナリングサーバー]        [Windows PC]
    |                            |                          |
    |---(1) new Peer(memberno)-->|                          |
    |<--(2) open event-----------|                          |
    |                            |                          |
    |---(3) connect(cameraid)--->|                          |
    |                            |---(4) offer------------>|
    |                            |<--(5) answer------------|
    |<--(6) ICE candidates-------|---(7) ICE candidates--->|
    |                            |                          |
    |<-----------(8) P2P接続確立 (WebRTC)----------------->|
    |                            |                          |
    |<-----------(9) 映像ストリーム配信--------------------|
    |------------(10) 操作コマンド送信--------------------->|
```

### 3. 認証・チェック項目

#### ログインチェック (Line 81-86)
```php
if (!isset($template->Session->UserInfo)) {
    DispError($template, "U5001");  // ログインしていません
    return;
}
```

#### テスターフラグ確認 (Line 88-100)
```php
$sql = "SELECT member_no, tester_flg FROM mst_member WHERE member_no = ?";
$testerRow = $template->DB->getRow($sql);
// tester_flg = '1' の場合、営業時間・マシン状態チェックをスキップ
```

#### 営業時間チェック (Line 102-109)
```php
if ($testerRow["tester_flg"] == "0") {
    $nowTime = date("H:i");
    if (GLOBAL_CLOSE_TIME <= $nowTime && GLOBAL_OPEN_TIME > $nowTime) {
        DispError($template, "U5004");  // 営業時間外
        return;
    }
}
```

#### マシン状態チェック (Line 111-127)
```php
$sql = "SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = ?";
$datmachineRow = $template->DB->getRow($sql);
// machine_status = '1' (稼働中) であること
// テスターの場合はスキップ
```

#### マシン割り当てチェック (Line 129-153)
```php
$sql = "SELECT machine_no, assign_flg, member_no FROM lnk_machine WHERE machine_no = ?";
$lnkRow = $template->DB->getRow($sql);
// assign_flg:
//   0 = 空き (割り当て可能)
//   1 = 使用中
//   9 = 視聴専用 (操作不可)
```

---

## プレイヤー側実装

### 1. HTMLテンプレート (`_html/ja/play/index_slot_ls_v2.html`)

#### 重要なパス設定

**❌ 間違い (相対パス)**:
```html
<link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<script src="js/playground.js"></script>
```

**✅ 正しい (絶対パス)**:
```html
<link href="/data/play_v2/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<script src="/data/play_v2/js/playground.js"></script>
```

**理由**:
- Apache RewriteRuleで `/vendor/` は除外されている
- しかし実際のファイルは `/data/play_v2/vendor/` にある
- 絶対パスで指定しないと404エラーになる

#### PHP変数の埋め込み

```html
<script>
var languageMode   = '{%LANG%}';           // 言語モード
var machineno      = '{%MACHINE_NO%}';     // マシン番号
var cameraid       = '{%CAMERA_ID%}';      // カメラID
var peerjskey      = '{%PEERJSKEY%}';      // PeerJS APIキー
var memberno       = '{%MEMBERNO%}';       // メンバー番号 (Peer ID)
var authID         = '{%AUTHID%}';         // 認証ID (ワンタイム)
var sigHost        = '{%SIGHOST%}';        // シグナリングサーバーホスト
var sigPort        = '{%SIGPORT%}';        // シグナリングサーバーポート
var iceServers     = {%ICESERVERS%};       // STUN/TURNサーバー配列
var autopush       = '{%AUTO_PUSH%}';      // 自動押し機能
var purchase       = {%PURCHASE%};         // 購入情報JSON
var errorMessages  = {%ERRORMESSAGES%};    // エラーメッセージJSON
var layoutOption   = {%LAYOUTOPTION%};     // レイアウト設定JSON
var convCredit     = {%CONVCREDIT%};       // クレジット変換率
var convPlaypoint  = {%CONVPLAYPOINT%};    // プレイポイント変換率
var browserVersion = '{%BROWSERVERSION%}'; // ブラウザ情報
var username       = '{%USERNAME%}';       // ユーザー名
</script>
```

### 2. JavaScript (`data/play_v2/js/view_auth.js`)

#### PeerJS設定

```javascript
var peersetting = {
    host: sigHost,                          // シグナリングサーバーホスト
    port: sigPort,                          // ポート (443)
    key: peerjskey,                         // APIキー
    token: authID,                          // 認証トークン
    config: {
        'iceServers': iceServers,           // STUN/TURNサーバー
        "iceTransportPolicy": "all",        // すべてのICE候補を使用
        "iceCandidatePoolSize": "0"         // ICE候補プールサイズ
    },
    debug: 0                                 // デバッグレベル (0-3)
};
```

#### Peer接続開始

```javascript
var peer = new Peer(memberno, peersetting);

peer.on('open', function() {
    console.log('✅ Peer ID:', peer.id);

    // データチャンネル接続
    dataConnection = peer.connect(cameraid, {
        'metadata': memberno + ':' + authID
    });

    dataConnection.on('open', function() {
        console.log('✅ Data connection opened');
    });

    dataConnection.on('data', function(data) {
        // Windows PCからのデータ受信
        console.log('📨 Received:', data);
    });
});

peer.on('call', function(call) {
    console.log('📞 Receiving call from:', call.peer);

    // 通話応答 (映像受信)
    call.answer();

    call.on('stream', function(stream) {
        console.log('🎥 Received stream');
        document.getElementById('video').srcObject = stream;
        $('#video').show();
    });
});

peer.on('error', function(err) {
    console.error('❌ Peer error:', err);
});
```

#### データ送信 (ボタン操作)

```javascript
// ボタン押下時
function sendButtonCommand(buttonId) {
    if (dataConnection && dataConnection.open) {
        dataConnection.send({
            type: 'button',
            id: buttonId,
            timestamp: Date.now()
        });
    }
}
```

### 3. ゲームロジック (`data/play_v2/js/view_functions.js`)

#### ポイント期限チェック

```javascript
function checkPoint() {
    var dt = new Date();
    var tmsp = dt.getTime();
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: './checkpoint.php?no=' + machineno + '&in_credit=' + inCreditCount + '&ts=' + tmsp,
            type: 'GET'
        })
        .done((data) => {
            resolve(data);
        })
        .fail((data) => {
            reject({status: 'fail'});
        });
    });
}
```

#### カウンター更新

```javascript
function updateCounter(data) {
    $('#bb-count').text(data.bb);
    $('#rb-count').text(data.rb);
    $('#game-count').text(data.games);
    $('#playpoint').text(data.playpoint);
}
```

---

## Windows PC側実装

### 1. 必要なコンポーネント

- **Webカメラ**: 実機を撮影
- **PeerJSクライアント**: ブラウザと同じPeerJS使用
- **操作制御プログラム**: ボタン押下コマンド受信 → 物理操作

### 2. カメラ配信プログラム構成

```javascript
// Windows PC側 PeerJS設定
const peer = new Peer(cameraId, {
    host: 'mgg-signaling-production-c1bd.up.railway.app',
    port: 443,
    secure: true,
    key: peerJsApiKey,
    config: {
        iceServers: iceServers
    }
});

// カメラストリーム取得
navigator.mediaDevices.getUserMedia({
    video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        frameRate: { ideal: 30 }
    },
    audio: false
}).then(stream => {
    localStream = stream;

    // データ接続を受信
    peer.on('connection', function(conn) {
        console.log('📱 Player connected:', conn.peer);

        // プレイヤーに映像を送信
        const call = peer.call(conn.peer, stream);

        // プレイヤーからの操作コマンド受信
        conn.on('data', function(data) {
            console.log('🎮 Command received:', data);
            executeCommand(data);
        });
    });
});

// 物理操作実行
function executeCommand(data) {
    if (data.type === 'button') {
        // Arduino/シリアル通信で実機を操作
        sendToArduino(data.id);
    }
}
```

### 3. カメラIDの命名規則

```
camera_[machine_no]_[timestamp]
例: camera_10000021_1762489262
```

### 4. 映像品質設定

```javascript
// 現在の設定 (デフォルト)
video: {
    width: { ideal: 1280 },    // 推奨: 1280px
    height: { ideal: 720 },    // 推奨: 720px
    frameRate: { ideal: 30 }   // 推奨: 30fps
}

// 高画質設定 (遅延増加)
video: {
    width: { ideal: 1920 },
    height: { ideal: 1080 },
    frameRate: { ideal: 30 }
}

// 低遅延設定 (画質低下)
video: {
    width: { ideal: 640 },
    height: { ideal: 480 },
    frameRate: { ideal: 15 }
}
```

---

## トラブルシューティング

### よくあるエラーと解決策

#### 1. `Peer error: ID is taken`

**原因**: 同じPeer IDが既に使用されている

**解決策**:
```javascript
// ブラウザをリロード
// または別のユーザーでログイン
// またはシグナリングサーバーを再起動
```

#### 2. `404 Not Found: /vendor/bootstrap/css/bootstrap.min.css`

**原因**: 相対パスが間違っている

**解決策**: HTMLテンプレートで絶対パスに修正
```html
<!-- ❌ 間違い -->
<link href="vendor/bootstrap/css/bootstrap.min.css">

<!-- ✅ 正しい -->
<link href="/data/play_v2/vendor/bootstrap/css/bootstrap.min.css">
```

#### 3. `$ is not defined`

**原因**: jQueryが読み込まれていない

**解決策**: jQueryのパスを確認
```html
<script src="/data/play_v2/vendor/jquery/jquery-3.3.1.min.js"></script>
```

#### 4. 映像が表示されない

**チェック項目**:
1. Windows PCが起動しているか？
2. カメラIDが正しいか？ (コンソールログ確認)
3. シグナリングサーバーに接続できているか？
4. ICE候補が交換されているか？
5. ファイアウォールでWebRTCがブロックされていないか？

**デバッグコマンド**:
```javascript
// コンソールで確認
console.log('Camera ID:', cameraid);
console.log('Signaling Host:', sigHost);
console.log('Peer ID:', peer.id);
```

#### 5. `/data/play_v2/index.php` が見つからない

**原因**: ファイルが削除されている

**解決策**: gitから復元
```bash
git restore data/play_v2/index.php
```

---

## デバッグ方法

### 1. マシン状態確認API

```bash
# マシン1番の状態確認
curl "https://mgg-webservice-production.up.railway.app/data/api/debug_play_check.php?machine_no=1"
```

**レスポンス例**:
```json
{
  "machine_no": 1,
  "checks": {
    "login": {
      "check": "ログイン状態",
      "status": "OK",
      "user_info": {
        "member_no": 4,
        "mail": "user@example.com"
      }
    },
    "tester": {
      "check": "テスターフラグ",
      "tester_flg": "1",
      "status": "テスター（時間チェック無し）"
    },
    "business_hours": {
      "check": "営業時間",
      "current_time": "14:30",
      "status": "OK"
    },
    "machine_status": {
      "check": "dat_machine.machine_status",
      "machine_status": "1",
      "status": "OK"
    },
    "assign_status": {
      "check": "lnk_machine.assign_flg",
      "assign_flg": "0",
      "status": "OK"
    }
  },
  "final_result": "プレイ可能"
}
```

### 2. マシンリセットAPI

```bash
# マシン1番をリセット（空き状態に）
curl "https://mgg-webservice-production.up.railway.app/data/api/reset_machine_mode.php?machine_no=1"
```

### 3. ブラウザコンソールログ

```javascript
// view_auth.js に追加されているログ
✅ Peer ID: 8a15ca25c36d74bcc7c4ad77f284e0a2551d0344
📡 Connecting to camera: camera_10000021_1762489262
👤 User: こうすけ2
✅ Data connection opened with camera
📞 Receiving call from: camera_10000021_1762489262
🎥 Received stream
```

### 4. データベース直接確認

```sql
-- マシン状態確認
SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = 1;

-- マシン割り当て確認
SELECT machine_no, assign_flg, member_no FROM lnk_machine WHERE machine_no = 1;

-- ユーザー情報確認
SELECT member_no, mail, playpoint, tester_flg FROM mst_member WHERE mail = 'user@example.com';
```

---

## データベーススキーマ

### 1. mst_member (ユーザー情報)

```sql
CREATE TABLE mst_member (
    member_no INT PRIMARY KEY AUTO_INCREMENT,
    mail VARCHAR(255) NOT NULL,
    nickname VARCHAR(100),
    playpoint INT DEFAULT 0,              -- プレイポイント
    deadline_point INT DEFAULT 0,         -- 期限付きポイント
    tester_flg CHAR(1) DEFAULT '0',       -- テスターフラグ (0=通常, 1=テスター)
    regist_dt DATETIME,
    update_dt DATETIME,
    INDEX idx_mail (mail)
);
```

### 2. dat_machine (マシンマスター)

```sql
CREATE TABLE dat_machine (
    machine_no INT PRIMARY KEY,
    model_cd VARCHAR(50),
    model_name VARCHAR(100),
    category CHAR(1),                     -- 1=パチンコ, 2=スロット
    machine_status CHAR(1) DEFAULT '0',   -- 0=準備中, 1=稼働中
    camera_name VARCHAR(100),             -- カメラID
    signaling_id INT,                     -- シグナリングサーバーID
    convcredit INT,                       -- クレジット変換率
    convplaypoint INT,                    -- プレイポイント変換率
    INDEX idx_status (machine_status)
);
```

### 3. lnk_machine (マシン割り当て状態)

```sql
CREATE TABLE lnk_machine (
    machine_no INT PRIMARY KEY,
    member_no INT DEFAULT 0,
    assign_flg CHAR(1) DEFAULT '0',       -- 0=空き, 1=使用中, 9=視聴専用
    exit_flg CHAR(1) DEFAULT '0',
    onetime_id VARCHAR(255),              -- ワンタイム認証ID
    start_dt DATETIME,
    INDEX idx_assign (assign_flg)
);
```

### 4. mst_layout (レイアウト設定)

```sql
CREATE TABLE mst_layout (
    layout_cd VARCHAR(50) PRIMARY KEY,
    layout_name VARCHAR(100),
    version VARCHAR(10),                  -- バージョン ("2", "3" など)
    video_portrait CHAR(1),               -- 0=横, 1=縦
    -- ... その他のレイアウト設定
);
```

---

## 重要な設定ファイル

### 1. `_etc/setting_base.php`

```php
// サイトURL
define('URL_SSL_SITE', getenv('URL_SSL_SITE') ?: 'https://mgg-webservice-production.up.railway.app/data/');
define('URL_SITE', getenv('URL_SITE') ?: 'https://mgg-webservice-production.up.railway.app/');

// 営業時間
define('GLOBAL_OPEN_TIME', '09:00');
define('GLOBAL_CLOSE_TIME', '05:00');

// 決済URL
define('PAYMENT_URL', URL_SSL_SITE . 'payment/');
```

### 2. `_etc/webRTC_setting.php`

```php
// PeerJS APIキー
$GLOBALS["RTC_PEER_APIKEY"] = "peerjs";

// シグナリングサーバー
$GLOBALS["RTC_Signaling_Servers"] = [
    1 => "mgg-signaling-production-c1bd.up.railway.app:443"
];

// STUN/TURNサーバー
$GLOBALS["RTC_STUN_Servers"] = [
    ["urls" => "stun:stun.l.google.com:19302"],
    ["urls" => "stun:stun1.l.google.com:19302"]
];

$GLOBALS["RTC_TURN_Servers"] = [
    [
        "urls" => "turn:turn.example.com:3478",
        "username" => "user",
        "credential" => "pass"
    ]
];
```

---

## 成功した修正履歴

### 2025/11/07 - WebRTCストリーミング復旧

#### 問題
- `/data/play_v2/index.php` が削除されていた
- HTMLテンプレートのパスが相対パスで404エラー
- 映像が表示されない

#### 修正内容

1. **play_v2/index.php の復元**
```bash
git restore data/play_v2/index.php
```

2. **テンプレート選択ロジックの修正**
```php
// 変更前: Ryujin8固定
$template->open("play/index_v2_ryujin8.html");

// 変更後: 動的選択
if ($machineRow["category"] == "1") {
    if ($layout_data["video_portrait"] == "1") {
        $template->open(PRE_1p_HTML . ".html");
    } else {
        $template->open(PRE_1l_HTML . ".html");
    }
} else {
    if ($layout_data["video_portrait"] == "1") {
        $template->open(PRE_2p_HTML . ".html");
    } else {
        $template->open(PRE_2l_HTML . ".html");
    }
}
```

3. **HTMLパスの修正**
```html
<!-- 変更前 -->
<link href="vendor/bootstrap/css/bootstrap.min.css">
<script src="js/playground.js"></script>

<!-- 変更後 -->
<link href="/data/play_v2/vendor/bootstrap/css/bootstrap.min.css">
<script src="/data/play_v2/js/playground.js"></script>
```

#### 結果
✅ WebRTC接続成功
✅ 映像表示成功
✅ ゲームプレイ可能

---

## 今後の改善項目

### 1. ポイント管理機能
- [x] ポイントチャージAPI作成
- [ ] ポイント交換機能
- [ ] ポイント履歴表示

### 2. ローディングアニメーション
- [ ] admin系からローディング削除
- [ ] ユーザー側にローディング追加
  - index.html
  - login.php
  - search.php
  - play系

### 3. 映像品質改善
- [ ] 解像度設定UI追加
- [ ] ビットレート調整機能
- [ ] フレームレート選択機能

### 4. マルチユーザー対応
- [ ] 複数人同時視聴機能
- [ ] 視聴専用モード (assign_flg=9)

---

## 連絡先・サポート

- **プロジェクト**: NET8 WebRTCストリーミングシステム
- **作成日**: 2025/11/07
- **最終更新**: 2025/11/07

---

## 付録

### A. 便利なコマンド集

```bash
# マシン状態確認
curl "https://mgg-webservice-production.up.railway.app/data/api/debug_play_check.php?machine_no=1"

# マシンリセット
curl "https://mgg-webservice-production.up.railway.app/data/api/reset_machine_mode.php?machine_no=1"

# ユーザー一覧
curl "https://mgg-webservice-production.up.railway.app/data/api/list_members.php"

# ポイントチャージ (1000ポイント)
curl "https://mgg-webservice-production.up.railway.app/data/api/charge_playpoint.php?member_no=4&amount=1000"

# Railwayデプロイ
git add .
git commit -m "feat: 修正内容"
git push origin main
```

### B. テンプレート変数一覧

| 変数名 | 説明 | 例 |
|--------|------|-----|
| `{%LANG%}` | 言語コード | `ja` |
| `{%MACHINE_NO%}` | マシン番号 | `1` |
| `{%CAMERA_ID%}` | カメラID | `camera_10000021_1762489262` |
| `{%PEERJSKEY%}` | PeerJS APIキー | `peerjs` |
| `{%MEMBERNO%}` | メンバー番号 | `4` |
| `{%AUTHID%}` | 認証ID | `8a15ca25...` |
| `{%SIGHOST%}` | シグナリングホスト | `mgg-signaling...` |
| `{%SIGPORT%}` | シグナリングポート | `443` |
| `{%ICESERVERS%}` | STUNサーバー配列 | `[{urls: "stun:..."}]` |
| `{%USERNAME%}` | ユーザー名 | `こうすけ2` |
| `{%MODEL_NAME%}` | 機種名 | `北斗の拳` |
| `{%TIMESTAMP%}` | タイムスタンプ | `ts=1762521465` |

---

**📝 このドキュメントは、NET8 WebRTCストリーミングシステムの完全な技術仕様書です。**

**✅ 動作確認済み: 2025/11/07**
