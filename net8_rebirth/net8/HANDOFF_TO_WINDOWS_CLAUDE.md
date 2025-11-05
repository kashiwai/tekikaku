# Windows側Claude Codeへの引き継ぎ

## 📦 転送するファイル

**ファイル名:** `WorksetClientSetup_ngrok.zip` (92MB)
**場所:** `/Users/kotarokashiwai/net8_rebirth/net8/WorksetClientSetup_ngrok.zip`

---

## 📝 Windows側Claude Codeへの指示

Windows PC上のClaude Codeを起動したら、以下のメッセージをそのまま伝えてください：

---

### 🤖 Windows側Claude Codeへ

NET8カメラクライアントシステムのセットアップをお願いします。

#### 受け取ったファイル
`WorksetClientSetup_ngrok.zip` (92MB)

#### セットアップ指示書
zipファイル内に `WINDOWS_CLAUDE_CODE_INSTRUCTIONS.md` が含まれています。
この指示書に従って、以下の作業を順番に実行してください：

1. **zipファイルを展開**
   ```powershell
   Expand-Archive -Path ".\WorksetClientSetup_ngrok.zip" -DestinationPath "." -Force
   ```

2. **指示書を開く**
   ```
   camera_localpcsetup\WorksetClientSetup_ngrok\WINDOWS_CLAUDE_CODE_INSTRUCTIONS.md
   ```

3. **指示書に従ってセットアップ実行**
   - ステップ1から順番に実行
   - 各ステップの結果を報告
   - 問題が発生したら、トラブルシューティングセクションを参照

#### 重要な情報

**接続先（Mac側サーバー）:**
- Webサーバー: `https://aicrypto.ngrok.dev`
- PeerJSサーバー: `https://aimoderation.ngrok-free.app`

**テスト用ライセンス:**
- MAC Address: `00:00:00:00:00:01`
- License ID: `IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=`

#### セットアップ完了後

以下の情報を報告してください：

```
✅ Windows PC側セットアップ完了

【確認事項】
- C:\serverset インストール: ✅
- 自動ログイン設定: ✅
- camera.bat自動起動: ✅
- slotserver.exe実行: ✅
- Chrome自動起動: ✅
- カメラ配信開始: ✅

【視聴確認依頼】
以下のURLで映像確認をお願いします：
https://aicrypto.ngrok.dev/play_v2/?NO=1

【問題があれば】
(エラーメッセージや状況を記載)
```

#### 注意事項

- ❌ 固定IP設定は実行しない（ngrok環境のため）
- ✅ 必ず管理者権限でPowerShellを実行
- ✅ 再起動は指示書の指定箇所で必ず実行
- ✅ 接続テストは省略しない

それでは、セットアップを開始してください。
指示書に詳細な手順が記載されています。

---

## 📊 Mac側の準備状況

### ✅ 完了済み

- [x] ngrokトンネル設定
  - `https://aicrypto.ngrok.dev` → localhost:8080
  - `https://aimoderation.ngrok-free.app` → localhost:59000

- [x] Mac側サーバー設定更新
  - `setting_base.php` - PeerJSサーバーURL更新
  - JavaScript - `secure: true` 追加

- [x] Windows側セットアップファイル準備
  - 全26ファイル互換性分析完了
  - 修正版バッチファイル作成完了
  - ngrok用設定ファイル作成完了
  - 完全指示書作成完了

### 🔄 Mac側で実行中

**ngrokトンネルを起動しておいてください：**

```bash
# ターミナル1
ngrok http 8080 --domain=aicrypto.ngrok.dev

# ターミナル2
ngrok http 59000 --domain=aimoderation.ngrok-free.app
```

**確認コマンド:**
```bash
# ngrokプロセス確認
ps aux | grep ngrok

# Docker確認
docker ps

# 期待される結果:
# - ngrokプロセス2つ実行中
# - web:8080, db:3306, signaling:59000 コンテナ実行中
```

---

## 🎯 Windows側完了後のテスト手順

### 1. API疎通確認（Mac側）

```bash
curl "https://aicrypto.ngrok.dev/api/cameraListAPI.php?M=getno&MAC=00:00:00:00:00:01&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI="
```

**期待される結果:**
```json
{"status":"ok","machine_no":1,"category":1,...}
```

### 2. 視聴確認（Mac側ブラウザ）

```
https://aicrypto.ngrok.dev/play_v2/?NO=1
```

**期待される動作:**
- ✅ Windows PCのカメラ映像が表示される
- ✅ 映像が滑らかに再生される
- ✅ 遅延が許容範囲内（<2秒）

### 3. デベロッパーツール確認

**F12 → Consoleタブ:**
```javascript
// 期待されるログ:
// PeerJS: Connecting to peer: camera-XXX-XXX
// PeerJS: Connection established
// MediaStream: Remote stream received
```

---

## 📞 トラブル時の連絡事項

### Windows側から問題報告があった場合

#### 問題: API接続エラー

**Mac側で確認:**
```bash
# ngrokトンネル確認
ps aux | grep ngrok

# トンネル再起動
pkill ngrok
ngrok http 8080 --domain=aicrypto.ngrok.dev &
ngrok http 59000 --domain=aimoderation.ngrok-free.app &

# ブラウザでテスト
open https://aicrypto.ngrok.dev
```

#### 問題: PeerJS接続エラー

**Mac側で確認:**
```bash
# PeerJSコンテナ確認
docker ps | grep signaling

# コンテナ再起動
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
docker-compose restart signaling

# ログ確認
docker-compose logs signaling
```

#### 問題: カメラ映像が表示されない

**Mac側で確認:**
```bash
# ブラウザのコンソールログ確認（F12）
# Network → WS タブでWebSocket接続確認

# PeerJSサーバーのログ確認
docker-compose logs -f signaling
```

---

## 📚 参考ドキュメント（Mac側）

### 作成済みドキュメント一覧

1. **WINDOWS_SETUP_COMPATIBILITY_ANALYSIS.md**
   - 全ファイル互換性分析の詳細

2. **WINDOWS_PC_DEPLOYMENT_MASTER_GUIDE.md**
   - 技術的な詳細ガイド

3. **WINDOWS_SETUP_SUMMARY.md**
   - 作業サマリー

4. **WINDOWS_CLAUDE_CODE_INSTRUCTIONS.md** ⭐
   - Windows側Claude Code用の完全指示書
   - zip内に含まれています

5. **HANDOFF_TO_WINDOWS_CLAUDE.md**
   - このファイル（引き継ぎドキュメント）

---

## ✅ チェックリスト

### Windows側に転送する前（Mac側）

- [x] WorksetClientSetup_ngrok.zip 作成完了 (92MB)
- [x] WINDOWS_CLAUDE_CODE_INSTRUCTIONS.md を zip内に含めた
- [x] ngrokトンネル起動中
- [x] Dockerコンテナ起動中

### Windows側に転送後

- [ ] WorksetClientSetup_ngrok.zip をWindows PCに転送
- [ ] Windows側Claude Codeに上記メッセージを送信
- [ ] Windows側のセットアップ開始を確認

### Windows側セットアップ中

- [ ] ステップ1完了報告受信
- [ ] 再起動1完了報告受信
- [ ] ステップ2-3完了報告受信
- [ ] 接続テスト成功報告受信
- [ ] 再起動2完了報告受信

### Windows側セットアップ完了後

- [ ] 自動起動確認報告受信
- [ ] Mac側で視聴確認成功
- [ ] カメラ映像品質確認
- [ ] システム動作安定確認

---

## 🎉 完了時のアクション

Windows側のセットアップが完全に完了し、Mac側で映像確認ができたら：

### 1. 動作確認テスト

```bash
# Mac側ブラウザで視聴
open https://aicrypto.ngrok.dev/play_v2/?NO=1

# 映像品質確認
# - 解像度
# - フレームレート
# - 遅延
# - 音声（カメラにマイクがある場合）
```

### 2. 長期運用テスト

- **24時間連続稼働テスト**
- **ネットワーク切断→再接続テスト**
- **自動再起動テスト（翌朝09:00）**
- **複数台カメラ接続テスト（将来）**

### 3. ドキュメント整理

- 運用手順書の作成
- トラブルシューティングガイドの更新
- メンテナンス手順の文書化

---

**作成日:** 2025-10-23
**Mac側担当:** Claude Code
**Windows側担当:** Claude Code (Windows PC)
**システム:** NET8 WebRTC Camera System
**ステータス:** Windows側への引き継ぎ準備完了
