# ログインテストガイド

## ✅ 修正完了内容

### 1. TemplateUser.php修正
- **エラー**: `Undefined array key "LANG"`
- **修正**: isset()チェック追加
- **状態**: ✅ デプロイ完了

### 2. テストユーザー登録済み
- **メール**: test@example.com
- **パスワード**: password
- **member_no**: 1
- **状態**: 有効 (state=1)
- **テスターフラグ**: 1

## 🔍 ログインテスト手順

### 方法1: ブラウザでテスト（推奨）

1. **ログインページにアクセス**:
   ```
   https://mgg-webservice-production.up.railway.app/data/login.php
   ```

2. **テストユーザーでログイン**:
   - メールアドレス: `test@example.com`
   - パスワード: `password`

3. **期待される動作**:
   - ✅ ログイン成功 → トップページ (https://mgg-webservice-production.up.railway.app/)
   - または台選択画面にリダイレクト

### 方法2: curlでテスト

```bash
# セッションクッキーを保存してログインテスト
curl -X POST "https://mgg-webservice-production.up.railway.app/data/login.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "MAIL=test@example.com&PASS=password" \
  -c cookies.txt \
  -L -v 2>&1 | grep -E "Location|HTTP/2"
```

## 📊 データベース状態

### テストユーザー
```sql
SELECT member_no, nickname, mail, state, tester_flg, point
FROM mst_member
WHERE mail = 'test@example.com';
```

**結果**:
| member_no | nickname | mail | state | tester_flg | point |
|-----------|----------|------|-------|------------|-------|
| 1 | testuser | test@example.com | 1 | 1 | 100000 |

### 台情報
```sql
SELECT machine_no, model_no, machine_cd, camera_no, signaling_id
FROM dat_machine
WHERE machine_no = 1;
```

**結果**:
| machine_no | model_no | machine_cd | camera_no | signaling_id |
|------------|----------|------------|-----------|--------------|
| 1 | 1 | HOKUTO04GO | 10000023 | default |

## 🎯 次のステップ

### ログイン成功後:
1. トップページで台一覧を表示確認
2. 台を選択 → プレイ画面 (`/data/play_v2/?NO=1`)
3. PeerJS接続確認 → Windows slotserver.exeと通信

### プレイ画面URL:
```
https://mgg-webservice-production.up.railway.app/data/play_v2/?NO=1
```

## 🐛 トラブルシューティング

### エラー: "macアドレスが登録されていません"
→ Windows側のslotserver.exeからのAPI接続エラー
→ 解決済み (SLOTSERVER_API_FIX_COMPLETE.md参照)

### エラー: 500エラー (Undefined array key "LANG")
→ TemplateUser.phpの修正で解決済み
→ デプロイ完了

### エラー: ログインできない
**確認事項**:
1. メールアドレス・パスワードが正しいか
2. ユーザーが有効か (state=1)
3. PHPエラーログ確認
4. セッションクッキーが正しく設定されているか

## 🔐 接続情報

### 本番サイト
- **トップ**: https://mgg-webservice-production.up.railway.app/
- **ログイン**: https://mgg-webservice-production.up.railway.app/data/login.php
- **管理画面**: https://mgg-webservice-production.up.railway.app/data/xxxadmin/

### データベース
- **Host**: 136.116.70.86
- **Port**: 3306
- **Database**: net8_dev
- **User**: net8tech001
- **Password**: `CaD?7&Bi+_:`QKb*`

### WebSocket/PeerJS
- **Signaling Server**: wss://mgg-signaling-production-c1bd.up.railway.app/
- **PeerJS Host**: mgg-signaling-production-c1bd.up.railway.app
- **PeerJS Port**: 443
- **PeerJS Path**: /
