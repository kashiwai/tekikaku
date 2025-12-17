# play_embed 実装記録

最終更新: 2025-12-17

## 概要

korea_net8front（Next.js）からiframeで埋め込んで使用するNET8パチンコ・スロットプレイヤー

## 実装完了した機能

### 1. play_embed 基本ファイル

| ファイル | パス |
|---------|------|
| メインPHP | `/net8/02.ソースファイル/net8_html/data/play_embed/index.php` |
| CSS | `/net8/02.ソースファイル/net8_html/data/play_embed/css/embed.css` |
| 参照JS | `/net8/02.ソースファイル/net8_html/data/play_v2/js/view_auth.js` |

### 2. URLパラメータ

```
/data/play_embed/?NO={machineNo}&sessionId={sessionId}&userId={userId}&cameraId={cameraId}&points={koreanPoints}&credit={credit}
```

| パラメータ | 説明 |
|-----------|------|
| NO | 台番号 |
| sessionId | game_startで取得したセッションID |
| userId | NET8側のユーザーID |
| cameraId | カメラのPeerID（URL encode必須） |
| points | 韓国側のポイント残高（初期残高） |
| credit | 初期クレジット |

### 3. 韓国側ポイント統合

```javascript
// index.php の <head> セクションで定義（view_auth.jsより先）
var game = {
    'credit'      : initialCredit,
    'playpoint'   : initialPoints,  // 韓国側のポイント残高
    'drawpoint'   : 0,
    'total_count' : 0,
    'bb_count'    : 0,
    'rb_count'    : 0,
    'count'       : 0,
    'min_credit'  : 2,
    'ccc_status'  : '',
    'in_credit'   : 0
};
```

### 4. 2列コンパクトレイアウト

```
┌─────────────────────────────────────────────────┐
│ [残高 10000] [CR 0] [変換] [精算]              │  ← 1列目
├─────────────────────────────────────────────────┤
│ [BET] [START] [1] [2] [3] [AUTO]               │  ← 2列目
└─────────────────────────────────────────────────┘
```

### 5. クレジット変換機能

```javascript
// 金額指定変換
function convertCredit(amount) {
    dataConnection.send(_sendStr('cca', amount));
}

// 全額変換
function convertAllCredit() {
    dataConnection.send(_sendStr('ccc', ''));
}
```

### 6. 精算機能

```javascript
function confirmPay() {
    dataConnection.send(_sendStr('pay', ''));
}
```

---

## 作成したAPI

### /api/v1/models（更新）

**エンドポイント:**
```
GET https://mgg-webservice-production.up.railway.app/api/v1/models
GET https://mgg-webservice-production.up.railway.app/api/v1/models?category=slot
GET https://mgg-webservice-production.up.railway.app/api/v1/models?category=pachinko
GET https://mgg-webservice-production.up.railway.app/api/v1/models?onlyAvailable=true
```

**レスポンス例:**
```json
{
  "success": true,
  "count": 5,
  "isOpen": true,
  "timestamp": "2025-12-17T10:00:00+09:00",
  "models": [
    {
      "id": "HKT001",
      "modelNo": 1,
      "name": "北斗の拳",
      "nameEn": "Hokuto no Ken",
      "category": "slot",
      "maker": "サミー",
      "totalMachines": 3,
      "availableMachines": 2,
      "inUseMachines": 1,
      "preparingMachines": 0,
      "hasAvailable": true,
      "status": "available",
      "statusName": "利用可能",
      "thumbnail": "https://mgg-webservice-production.up.railway.app/data/img/model/xxx.jpg",
      "detailImage": "https://...",
      "reelImage": "https://..."
    }
  ]
}
```

### /api/v1/machines（新規作成）

**ファイル:** `/net8/02.ソースファイル/net8_html/api/v1/machines.php`

**エンドポイント:**
```
GET https://mgg-webservice-production.up.railway.app/api/v1/machines
GET https://mgg-webservice-production.up.railway.app/api/v1/machines?modelId=HKT001
GET https://mgg-webservice-production.up.railway.app/api/v1/machines?status=available
GET https://mgg-webservice-production.up.railway.app/api/v1/machines?category=slot
```

**レスポンス例:**
```json
{
  "success": true,
  "count": 10,
  "isOpen": true,
  "timestamp": "2025-12-17T10:00:00+09:00",
  "machines": [
    {
      "machineNo": 1,
      "modelId": "HKT001",
      "modelNo": 1,
      "modelName": "北斗の拳",
      "modelNameEn": "Hokuto no Ken",
      "status": "available",
      "statusName": "利用可能",
      "canPlay": true,
      "images": {
        "thumbnail": "https://...",
        "detail": "https://...",
        "reel": "https://..."
      },
      "category": "slot",
      "cameraId": "camera_001"
    }
  ]
}
```

---

## ステータス判定ロジック

### DBテーブル構造

| テーブル | フィールド | 値 | 意味 |
|---------|-----------|-----|------|
| lnk_machine | assign_flg | 0 | 未使用 |
| lnk_machine | assign_flg | 1 | 使用中 |
| lnk_machine | assign_flg | 9 | メンテナンス |
| dat_machine | machine_status | 0 | 準備中 |
| dat_machine | machine_status | 1 | 稼働中 |
| dat_machine | machine_status | 2 | メンテナンス |

### ユーザー向け3状態

```
assign_flg=1 → in_use（使用中）← クリック不可
assign_flg=0 && machine_status=1 && 営業時間内 → available（利用可能）← クリック可能
その他 → preparing（準備中）← クリック不可
```

---

## korea_net8front側の実装

### Net8GamePlayerIframe.tsx

**ファイル:** `/korea_net8front/client-pachinko/src/components/net8/Net8GamePlayerIframe.tsx`

**主要機能:**
1. game_start APIを呼び出してセッション取得
2. play_embed URLを構築してiframeに表示
3. postMessageでplay_embedからのイベントを受信

**URL構築:**
```typescript
const url = `https://mgg-webservice-production.up.railway.app/data/play_embed/?NO=${result.machineNo}&sessionId=${result.sessionId}&userId=${net8UserId}&cameraId=${encodeURIComponent(cameraId)}&points=${userPoints}&credit=${userCredit}`;
```

**postMessageイベント:**
- `NET8_PLAYER_READY` - プレイヤー準備完了
- `NET8_INITIALIZED` - 初期化完了
- `NET8_CONNECTED` - カメラ接続完了
- `NET8_STATUS` - ステータス更新
- `NET8_GAME_END` - ゲーム終了
- `NET8_ERROR` - エラー発生

---

## デプロイ情報

### 環境

| 項目 | 値 |
|------|-----|
| リポジトリ | https://github.com/mgg00123mg-prog/mgg001.git |
| ブランチ | main |
| デプロイ先 | Railway（自動デプロイ） |
| 本番URL | https://mgg-webservice-production.up.railway.app |

### デプロイ手順

```bash
# 1. ファイルをステージング（-f は gitignore を無視）
git add -f "net8/02.ソースファイル/net8_html/data/play_embed/index.php"
git add -f "net8/02.ソースファイル/net8_html/data/play_embed/css/embed.css"
git add -f "net8/02.ソースファイル/net8_html/api/v1/machines.php"
git add -f "net8/02.ソースファイル/net8_html/api/v1/models.php"

# 2. コミット
git commit -m "コミットメッセージ"

# 3. プッシュ（Railway自動デプロイ）
git push origin main

# 4. デプロイ完了まで2-3分待機
```

### Dockerビルド

- `Dockerfile` でPHP 7.2-apache環境を構築
- キャッシュバスト用に `FORCE-REBUILD-YYYY-MM-DD-vXX` を更新

---

## 修正履歴（最新順）

| コミット | 内容 |
|---------|------|
| 55f411e | 2列コンパクトレイアウト |
| 1544cec | 機種状況・画像API追加、韓国側ポイント統合 |
| 031a167 | 韓国側ポイントを残高として統合 |
| 271846c | クレジット変換モーダル追加 |
| ba7e2b1 | CSSでcontrol_panelのdisplay:none修正 |
| b2ce774 | HTMLでcontrol_panelのdisplay:none削除 |

---

## 重要な修正ポイント

### 1. URL.createObjectURL エラー修正

**問題:** `URL.createObjectURL(stream)` は MediaStream に対して非推奨

**修正箇所:** `/net8/02.ソースファイル/net8_html/data/play_v2/js/view_auth.js`

```javascript
// 修正前（エラー）
_savestream = URL.createObjectURL(stream);

// 修正後
_savestream = stream;
```

### 2. control_panel が表示されない

**問題:** CSSで `display: none !important` が設定されていた

**修正箇所:** `embed.css`

```css
/* 修正前 */
.embed-mode #control_panel.playing-controls {
    display: none !important;
}

/* 修正後 */
.embed-mode #control_panel.playing-controls {
    display: flex !important;
}
```

### 3. gameオブジェクトの初期化タイミング

**問題:** view_auth.js で game オブジェクトが0で初期化されてしまう

**解決:** index.php の `<head>` セクションで view_auth.js より先に game オブジェクトを定義

---

## 未実装・今後の課題

1. [ ] play_embed のボタンが実際に動作するかの本番テスト
2. [ ] korea_net8front の機種選択ページで /api/v1/models API を使用
3. [ ] 画像の実際の表示確認
4. [ ] クレジット変換時のエラーハンドリング強化
5. [ ] オートプレイ機能のテスト
6. [ ] パチンコ用コントロールのテスト

---

## テスト用URL

```
# play_embed 直接アクセス（テスト用）
https://mgg-webservice-production.up.railway.app/data/play_embed/?NO=1&sessionId=test&cameraId=test&points=10000&credit=0

# モデル一覧API
https://mgg-webservice-production.up.railway.app/api/v1/models

# 台一覧API
https://mgg-webservice-production.up.railway.app/api/v1/machines
```
