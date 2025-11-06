# MACHINE-03（新PC）セットアップ手順

## ✅ DB登録完了（Mac側）

以下の情報がDBに登録されました：

```
Machine No: 3
Model No: 3 (ミリオンゴッド4号機)
Camera No: 2
MAC Address: E0-51-D8-16-13-3D
IP Address: 192.168.11.13
License ID: IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
Signaling ID: PEER002
```

---

## 🔧 Windows PC側の設定

### 1. slotserver.iniの設定

slotserver.iniファイルを以下のように設定してください：

```ini
[API]
API_ENDPOINT = https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php
MAC_ADDRESS = E0-51-D8-16-13-3D
LICENSE_ID = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=

[SIGNALING]
SIGNALING_SERVER = https://dockerfilesignaling-production.up.railway.app
PEER_ID = PEER002

[MACHINE]
MACHINE_NO = 3
```

### 2. APIテスト

以下のURLでAPIが正しく応答するか確認：

```
https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=E0-51-D8-16-13-3D&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=&IP=192.168.11.13
```

期待されるレスポンス：
```json
{
  "status": "ok",
  "machine_no": 3,
  "category": 2,
  "leavetime": 180,
  "renchan_games": 0,
  "tenjo_games": 9999,
  "version": "1"
}
```

### 3. slotserver.pyの起動

```bash
python slotserver.py
```

または

```bash
slotserver.exe
```

### 4. 動作確認

- PeerJS接続が確立されるか（PEER002）
- 機種「ミリオンゴッド4号機」が選択できるか
- ゲームがプレイできるか
- 動画が再生されるか

---

## 🐛 トラブルシューティング

### エラー: "macアドレスが登録されていません"

→ slotserver.iniのMAC_ADDRESSが正しいか確認
→ `E0-51-D8-16-13-3D` になっているか

### エラー: "version: invalid literal for int()"

→ すでに修正済み（cameraListAPI.php）
→ 最新版がデプロイされているか確認

### PeerJS接続できない

→ Signaling Serverが動作しているか確認
→ PEER_IDが正しいか確認（PEER002）

---

## 📝 重要な情報

**絶対に変更しないでください：**
- MAC_ADDRESS: E0-51-D8-16-13-3D
- LICENSE_ID: IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
- PEER_ID: PEER002

---

**作成日**: 2025/11/06
**最終更新**: 2025/11/06
