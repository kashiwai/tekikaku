# NET8 SDK Version History

## バージョン管理ポリシー
- 各バージョンのREADMEは別ファイルとして保管
- 下位互換性を維持する場合はマイナーバージョンアップ（1.0 → 1.1）
- 破壊的変更がある場合はメジャーバージョンアップ（1.x → 2.0）

---

## v1.1.0-beta (2025-11-18) ✨ **Latest**

**ドキュメント**: `README_v1.1.0.md`
**ファイル**: `net8-sdk-beta.js`

### 新機能
- ✅ **userId連携**: `createGame()`で`userId`パラメータをサポート
- ✅ **ポイント管理**: ゲーム開始時のポイント消費、終了時の払い出し自動処理
- ✅ **ゲーム終了イベント**: `game.on('end', callback)`で詳細な結果取得
- ✅ **手動停止機能**: `game.stop()`でゲームを途中終了可能
- ✅ **プレイ履歴API**: `/api/v1/play_history.php`でセッション履歴取得
- ✅ **セキュリティ強化**: X-Frame-Options動的設定でiFrame埋め込み制御

### 主要変更
```javascript
// v1.1.0の使い方
const game = Net8.createGame({
    model: 'HOKUTO4GO',
    userId: 'partner_user_12345',  // NEW!
    container: '#game-container'
});

// 新しいイベント
game.on('end', (result) => {
    console.log('Points won:', result.pointsWon);
    console.log('Net profit:', result.netProfit);
    console.log('New balance:', result.newBalance);
});
```

### データベース変更
- `sdk_users` テーブル追加
- `user_balances` テーブル追加
- `point_transactions` テーブル追加
- `game_sessions` テーブル追加
- `api_keys.allowed_domains` カラム追加

### 新規API
- `POST /api/v1/game_end.php` - ゲーム終了とポイント払い出し
- `GET /api/v1/play_history.php` - プレイ履歴取得

### 互換性
- ✅ **下位互換**: v1.0.1のコードもそのまま動作（userIdなしでも利用可能）
- ⚠️ **推奨移行**: ポイント管理機能を使う場合は`userId`の追加を推奨

---

## v1.0.1-beta (2025-11-15)

**ドキュメント**: `README.md`
**ファイル**: `net8-sdk-beta.js` (旧バージョン)

### 機能
- ✅ 基本的なゲーム起動・終了
- ✅ WebRTC通信（PeerJS）
- ✅ JWT認証
- ✅ 機種選択（HOKUTO4GO, YOSHIMUNE等）
- ✅ iFrame埋め込みサポート

### 主要API
```javascript
// v1.0.1の基本的な使い方
await Net8.init('pk_test_xxxxx');

const game = Net8.createGame({
    model: 'HOKUTO4GO',
    container: '#game-container'
});

await game.start();
```

### イベント
- `game.on('ready', callback)` - ゲーム準備完了
- `game.on('started', callback)` - ゲーム開始
- `game.on('error', callback)` - エラー発生

### 制限事項
- ユーザー管理機能なし
- ポイント管理なし
- プレイ履歴なし
- 手動停止機能なし

---

## マイグレーションガイド

### v1.0.1 → v1.1.0

#### 必須作業（データベース）
```bash
mysql -u root -p net8_db < sdk_extension_schema.sql
```

#### コード変更（推奨）
```javascript
// Before (v1.0.1)
const game = Net8.createGame({
    model: 'HOKUTO4GO',
    container: '#game-container'
});

// After (v1.1.0) - userIdを追加
const game = Net8.createGame({
    model: 'HOKUTO4GO',
    userId: 'user_12345',  // ← 追加
    container: '#game-container'
});

// ゲーム終了イベントを追加
game.on('end', (result) => {
    console.log('Game ended:', result);
    // UI更新処理など
});
```

#### サーバー側変更
1. 新規ファイルをアップロード:
   - `/api/v1/game_end.php`
   - `/api/v1/play_history.php`
   - `/api/v1/helpers/user_helper.php`
   - `/api/v1/helpers/frame_security.php`

2. 既存ファイルを更新:
   - `/api/v1/game_start.php`

3. 管理画面を配置:
   - `/data/xxxadmin/partner_domains.php`
   - `/_html/ja/admin/partner_domains.html`

4. Frame Security有効化:
   - `/data/play_v2/index.php` の先頭に `require_once(__DIR__ . '/frame_security.php');` を追加

---

## 次期バージョン予定

### v1.2.0 (計画中)
- 統計情報API (`/api/v1/stats.php`)
- タイムアウト処理強化
- パートナー認証連携（2段階認証）

### v2.0.0 (検討中)
- TypeScript版SDK
- React/Vue対応ラッパー
- リアルタイム残高同期（WebSocket）

---

## サポート

### ドキュメント
- **v1.1.0**: `README_v1.1.0.md`
- **v1.0.1**: `README.md`
- **実装レポート**: `../../SDK_EXTENSION_IMPLEMENTATION_COMPLETE.md`

### デモ
- **v1.1.0デモ**: https://mgg-webservice-production.up.railway.app/sdk/demo_v1.1.0.html (作成予定)
- **v1.0.1デモ**: https://mgg-webservice-production.up.railway.app/sdk/demo.html

### 技術サポート
プロジェクト管理者にお問い合わせください。

---

**NET8 SDK Development Team**
Last Updated: 2025-11-18
