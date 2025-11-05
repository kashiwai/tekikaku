# Windows側Claude Codeへの引き継ぎ（文字化け対策済み）

## ✅ 文字化け対策完了

**対策内容:**
- ✅ 英語版バッチファイル作成（1_Office_AutoRun.bat, 2_Site_AutoRun.bat）
- ✅ 英語版完全指示書作成（SETUP_INSTRUCTIONS_EN.md）
- ✅ 日本語版指示書も併載（WINDOWS_CLAUDE_CODE_INSTRUCTIONS.md）
- ✅ zip再作成完了

**結果:** Windows側で展開しても文字化けしません

---

## 📦 転送するファイル

**ファイル名:** `WorksetClientSetup_ngrok.zip` (92MB)
**場所:** `/Users/kotarokashiwai/net8_rebirth/net8/WorksetClientSetup_ngrok.zip`

**含まれる指示書（2言語）:**
- `SETUP_INSTRUCTIONS_EN.md` - English version (recommended)
- `WINDOWS_CLAUDE_CODE_INSTRUCTIONS.md` - Japanese version

---

## 🤖 Windows側Claude Codeへの指示（コピペ用）

Windows PC上のClaude Codeに、以下をそのまま送信してください：

```
Please help me set up the NET8 Camera Client System.

Received file: WorksetClientSetup_ngrok.zip (92MB)

INSTRUCTIONS:

1. Extract the zip file:
   Expand-Archive -Path ".\WorksetClientSetup_ngrok.zip" -DestinationPath "." -Force

2. Read the setup instructions:
   camera_localpcsetup\WorksetClientSetup_ngrok\SETUP_INSTRUCTIONS_EN.md

3. Follow the instructions step by step and execute the setup.

IMPORTANT INFORMATION:

Connection endpoints (Mac server):
- Web server: https://aicrypto.ngrok.dev
- PeerJS server: https://aimoderation.ngrok-free.app

Test license:
- MAC Address: 00:00:00:00:00:01
- License ID: IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=

CRITICAL NOTES:
- ❌ Do NOT set fixed IP (for ngrok environment)
- ✅ Always run PowerShell as Administrator
- ✅ Reboot when instructed
- ✅ Do NOT skip connection tests

After setup completion, report:

✅ Windows PC Setup Completed

[Verification]
- C:\serverset installed: ✅
- Auto-login configured: ✅
- camera.bat auto-start: ✅
- slotserver.exe running: ✅
- Chrome auto-opened: ✅
- Camera streaming started: ✅

[Viewing Verification Request]
Please verify video at:
https://aicrypto.ngrok.dev/play_v2/?NO=1

Please begin the setup now.
```

---

## 📊 ファイル構成（zip内）

```
camera_localpcsetup/WorksetClientSetup_ngrok/ (31 files)
├── 📝 Setup Instructions (2 languages)
│   ├── SETUP_INSTRUCTIONS_EN.md          ✅ English (recommended)
│   └── WINDOWS_CLAUDE_CODE_INSTRUCTIONS.md  (Japanese)
│
├── 🔧 Batch Files (English version)
│   ├── 1_Office_AutoRun.bat              ✅ No character encoding issues
│   ├── 2_Site_AutoRun.bat                ✅ No character encoding issues
│   ├── 3_Last_AutoRun.bat                (Original)
│   ├── 4_DisableUpdate_AutoRun.bat       (Original)
│   └── Other batch files...
│
├── ⚙️ ngrok Configuration Files
│   ├── slotserver_ngrok.ini              ✅ ngrok settings
│   ├── start_ngrok.bat                   ✅ Manual startup
│   └── README_ngrok_setup.md             (Japanese)
│
├── 🔧 PowerShell Scripts (18 files)
│   ├── DisableWindowsUpdate.ps1
│   ├── Net8AutoLogin.ps1
│   └── Other scripts...
│
└── 📦 Installers
    ├── setupapp.exe (34MB)
    ├── GoogleChromeStandaloneEnterprise64.msi (50MB)
    ├── amcap v3.0.9.exe
    └── shortcut.exe
```

---

## 🎯 セットアップフロー（概要）

### **Windows側の作業:**

```
1. Extract zip
   ↓
2. Read SETUP_INSTRUCTIONS_EN.md
   ↓
3. Execute 1_Office_AutoRun.bat
   Input: Camera #1, Host #1
   ↓
4. Reboot
   ↓
5. Execute 2_Site_AutoRun.bat
   Input: Host #1
   Installs to C:\serverset\
   ↓
6. Execute 3_Last_AutoRun.bat
   Input: pcuser/pcpass, 09:00:00
   ↓
7. Create slotserver_ngrok.ini
   (PowerShell command in instructions)
   ↓
8. Connection tests
   - API test
   - PeerJS test
   - Camera page access test
   ↓
9. Final reboot
   ↓
10. Auto-login → camera.bat auto-start
    ↓
11. Report to Mac side
```

### **Mac側の準備（現在）:**

```bash
# ngrok tunnels MUST be running
# Terminal 1:
ngrok http 8080 --domain=aicrypto.ngrok.dev

# Terminal 2:
ngrok http 59000 --domain=aimoderation.ngrok-free.app

# Verify:
ps aux | grep ngrok  # Should show 2 processes
```

---

## 🔍 Mac側での確認手順

### 1. ngrokトンネル確認

```bash
# Check ngrok processes
ps aux | grep ngrok

# Expected: 2 ngrok processes running
```

### 2. Docker確認

```bash
# Check Docker containers
docker ps

# Expected:
# - web:8080
# - db:3306
# - signaling:59000
```

### 3. Windows側セットアップ完了後の視聴確認

**ブラウザでアクセス:**
```
https://aicrypto.ngrok.dev/play_v2/?NO=1
```

**期待される動作:**
- ✅ Windows PCのカメラ映像が表示される
- ✅ 映像が滑らかに再生される（30fps目標）
- ✅ 遅延が許容範囲内（<2秒）

**デベロッパーツール確認（F12）:**
```javascript
// Console tab - Expected logs:
// PeerJS: Connecting to peer: camera-XXX-XXX
// PeerJS: Connection established
// MediaStream: Remote stream received
```

---

## 🔧 トラブルシューティング（Mac側）

### Issue: Windows側から「API接続エラー」報告

**Mac側で確認:**
```bash
# ngrok tunnel check
ps aux | grep ngrok

# Restart if needed
pkill ngrok
ngrok http 8080 --domain=aicrypto.ngrok.dev &
ngrok http 59000 --domain=aimoderation.ngrok-free.app &

# Test in browser
open https://aicrypto.ngrok.dev
```

### Issue: Windows側から「PeerJS接続エラー」報告

**Mac側で確認:**
```bash
# PeerJS container check
docker ps | grep signaling

# Restart container
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
docker-compose restart signaling

# Check logs
docker-compose logs -f signaling
```

### Issue: 映像が表示されない

**Mac側で確認:**
```bash
# Check PeerJS server logs
docker-compose logs signaling | grep -i peer

# Check if Windows PC is connecting
# (Should see connection attempts in logs)
```

---

## ✅ チェックリスト

### **Mac側準備（転送前）**

- [x] WorksetClientSetup_ngrok.zip 作成完了 (92MB)
- [x] 英語版指示書を含めた
- [x] 英語版バッチファイルに更新
- [x] 文字化け対策完了
- [ ] ngrokトンネル起動中
- [ ] Dockerコンテナ起動中

### **Windows側作業（Claude Code実行）**

- [ ] WorksetClientSetup_ngrok.zip をWindows PCに転送
- [ ] Windows側Claude Codeに指示を送信
- [ ] zip展開完了報告受信
- [ ] ステップ1実行完了報告受信
- [ ] 再起動1完了報告受信
- [ ] ステップ2-3実行完了報告受信
- [ ] 接続テスト成功報告受信
- [ ] 再起動2完了報告受信
- [ ] 自動起動確認報告受信

### **Mac側確認（セットアップ完了後）**

- [ ] 視聴ページで映像確認成功
- [ ] 映像品質確認（解像度、FPS、遅延）
- [ ] 音声確認（カメラにマイクがある場合）
- [ ] 24時間連続稼働テスト
- [ ] 自動再起動テスト（翌朝09:00）

---

## 📞 サポート情報

### ngrok URL

- **Web Server:** https://aicrypto.ngrok.dev → Mac:8080
- **PeerJS Server:** https://aimoderation.ngrok-free.app → Mac:59000

### Test License

- **MAC Address:** 00:00:00:00:00:01
- **License ID:** IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
- **CD:** 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c

### ドキュメント参照（Mac側）

1. **WINDOWS_SETUP_COMPATIBILITY_ANALYSIS.md** - 技術的詳細
2. **WINDOWS_PC_DEPLOYMENT_MASTER_GUIDE.md** - 完全ガイド
3. **WINDOWS_SETUP_SUMMARY.md** - 作業サマリー
4. **WINDOWS_HANDOFF_FINAL.md** - このファイル（最終版）

---

## 🎉 準備完了

**転送用ファイル:**
```
/Users/kotarokashiwai/net8_rebirth/net8/WorksetClientSetup_ngrok.zip
```

**文字化け対策:** ✅ 完了
- 英語版バッチファイル
- 英語版指示書
- ASCIIエンコーディング

**次のステップ:**
1. zipファイルをWindows PCに転送
2. Windows側Claude Codeに英語の指示を送信
3. Mac側でngrokトンネルを起動して待機
4. Windows側のセットアップ完了を待つ
5. Mac側ブラウザで視聴確認

---

**作成日:** 2025-10-23
**Mac側担当:** Claude Code
**Windows側担当:** Claude Code (Windows PC)
**システム:** NET8 WebRTC Camera System
**ステータス:** ✅ 文字化け対策完了、Windows側への引き継ぎ準備完了
