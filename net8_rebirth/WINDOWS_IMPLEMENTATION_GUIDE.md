# Windows側実装ガイド - トークン認証対応

## 📌 変更概要

NET8License.pyに**トークン認証機能**を追加しました。
今後、全てのcameraAPI.php呼び出しは`callCameraAPI()`メソッドを使用してください。

---

## 🔧 NET8License.pyに追加された機能

### 1. トークン自動保存機能

**addCameraList()メソッドの変更:**
```python
def addCameraList(self):
    # ... (既存のコード)
    res = self.sendPost(url, data)

    self._info['camera_no'] = res['camera_no']

    # 🆕 トークンを保存（サーバーから返される）
    if 'token' in res:
        self._info['token'] = res['token']
    if 'machine_no' in res:
        self._info['machine_no'] = res['machine_no']

    return res['license_id']
```

**説明:**
- サーバーに登録すると、サーバーから`token`と`machine_no`が返ってくる
- これを自動的にNET8Licenseインスタンスに保存
- 以降のAPI呼び出しで自動的にトークンを送信

---

### 2. 新メソッド: callCameraAPI()

**使い方:**
```python
NLC = NET8License()
# ... 初期化処理 ...

# カメラAPI呼び出し（トークン認証付き）
result = NLC.callCameraAPI(mode='start')
```

**メソッドシグネチャ:**
```python
def callCameraAPI(self, mode, params=None):
    """
    cameraAPI.phpを呼び出す（トークン認証付き）

    Args:
        mode (str): API mode
            - 'start'   : カメラ起動
            - 'end'     : カメラ終了
            - 'reset'   : リンクリセット
            - 'status'  : 状態取得
            - 'setting' : 設定変更
            - 'reboot'  : 再起動

        params (dict, optional): 追加パラメータ
            例: {'LEVEL': '3'}

    Returns:
        dict: APIレスポンス
            - 成功時: {'status': 'ok', ...}
            - 失敗時: {'status': 'ng', 'error': 'エラー内容'}
    """
```

**実装内容:**
- 自動的に`MACHINE_NO`と`TOKEN`をパラメータに追加
- GET形式でcameraAPI.phpを呼び出し
- レスポンスをJSONパースして返却

---

## 🎯 実装変更手順

### ステップ1: 既存のcameraAPI.php呼び出しを探す

**検索パターン:**
```python
# 古い呼び出し方法（これを探す）
url = 'https://****/api/cameraAPI.php?M=start&MACHINE_NO={}'.format(machine_no)
requests.get(url)
```

または

```python
# 手動でリクエストを構築している箇所
data = {'M': 'start', 'MACHINE_NO': machine_no}
requests.get(camera_api_url, params=data)
```

---

### ステップ2: callCameraAPI()に置き換える

#### 例1: カメラ起動 (start)

**Before:**
```python
url = f'https://{domain}/api/cameraAPI.php?M=start&MACHINE_NO={machine_no}'
response = requests.get(url)
result = response.json()
```

**After:**
```python
result = NLC.callCameraAPI('start')
```

---

#### 例2: カメラ終了 (end)

**Before:**
```python
url = f'https://{domain}/api/cameraAPI.php?M=end&MACHINE_NO={machine_no}'
response = requests.get(url)
result = response.json()
```

**After:**
```python
result = NLC.callCameraAPI('end')
```

---

#### 例3: リンクリセット (reset)

**Before:**
```python
url = f'https://{domain}/api/cameraAPI.php?M=reset&MACHINE_NO={machine_no}'
response = requests.get(url)
result = response.json()
```

**After:**
```python
result = NLC.callCameraAPI('reset')
```

---

#### 例4: 設定変更 (setting) - パラメータあり

**Before:**
```python
url = f'https://{domain}/api/cameraAPI.php?M=setting&MACHINE_NO={machine_no}&LEVEL=3'
response = requests.get(url)
result = response.json()
```

**After:**
```python
result = NLC.callCameraAPI('setting', {'LEVEL': '3'})
```

---

#### 例5: 状態取得 (status)

**Before:**
```python
url = f'https://{domain}/api/cameraAPI.php?M=status&MACHINE_NO={machine_no}'
response = requests.get(url)
result = response.json()
```

**After:**
```python
result = NLC.callCameraAPI('status')
```

---

#### 例6: 再起動 (reboot)

**Before:**
```python
url = f'https://{domain}/api/cameraAPI.php?M=reboot&MACHINE_NO={machine_no}'
response = requests.get(url)
result = response.json()
```

**After:**
```python
result = NLC.callCameraAPI('reboot')
```

---

## 📝 slotserver.pyでの実装例

### 修正箇所の特定

**slotserver.pyで以下のような箇所を探してください:**

1. **起動時の処理** (line 700-760あたり)
2. **API呼び出し箇所** (`requests.get`や`requests.post`を使っている場所)
3. **カメラ制御関連の処理**

---

### 実装例: slotserver.py内での使用

```python
# グローバル変数としてNET8Licenseインスタンスを保持
NLC = None

# main処理内（line 703あたり）
if __name__ == "__main__":
    # ... 既存の初期化処理 ...

    # 情報取得
    NLC = NET8License()

    # ... ライセンスチェック等 ...

    # 登録処理（既存）
    NLC.setInfo('domain', config['API']['domain'])  # domainを設定
    license_id = NLC.addCameraList()  # この時点でtokenとmachine_noが保存される

    print(f"📝 Machine No: {NLC.getInfo('machine_no')}")
    print(f"🔑 Token: {NLC.getInfo('token')}")

    # ... 以降の処理で NLC.callCameraAPI() を使用 ...
```

---

### カメラ起動時の処理

**追加すべき箇所（例）:**

```python
def startCamera():
    """カメラを起動する"""
    global NLC

    print("📹 カメラ起動中...")
    result = NLC.callCameraAPI('start')

    if result.get('status') == 'ok':
        print("✅ カメラ起動成功")
        return True
    else:
        print(f"❌ カメラ起動失敗: {result.get('error')}")
        return False

def endCamera():
    """カメラを終了する"""
    global NLC

    print("📹 カメラ終了中...")
    result = NLC.callCameraAPI('end')

    if result.get('status') == 'ok':
        print("✅ カメラ終了成功")
        return True
    else:
        print(f"❌ カメラ終了失敗: {result.get('error')}")
        return False
```

---

### リンクリセット処理

**定期的なリセット処理がある場合:**

```python
def resetMachineLink():
    """マシンリンクをリセット"""
    global NLC

    result = NLC.callCameraAPI('reset')

    if result.get('status') == 'ok':
        print("🔄 リンクリセット成功")

        # サーバーから設定情報を取得
        if 'setting' in result:
            print(f"⚙️ 設定切替: {result['setting']}")
        if 'reboot' in result:
            print(f"🔄 再起動フラグ: {result['reboot']}")

        return result
    else:
        print(f"❌ リンクリセット失敗: {result.get('error')}")
        return None
```

---

## 🚨 重要な注意点

### 1. NET8Licenseインスタンスは1つだけ

```python
# ✅ Good: グローバルで1つのインスタンスを使い回す
NLC = NET8License()
NLC.setInfo('domain', 'mgg-webservice-production.up.railway.app')
NLC.addCameraList()  # トークン取得

# 以降、同じNLCインスタンスを使う
NLC.callCameraAPI('start')
NLC.callCameraAPI('end')

# ❌ Bad: 毎回新しいインスタンスを作成しない
NLC = NET8License()  # トークンが消える！
```

---

### 2. domain設定を忘れずに

```python
NLC = NET8License()
NLC.setInfo('domain', 'mgg-webservice-production.up.railway.app')  # 必須！
NLC.addCameraList()  # この後にAPI呼び出し可能
```

---

### 3. エラーハンドリング

```python
result = NLC.callCameraAPI('start')

if result.get('status') == 'ok':
    print("✅ 成功")
else:
    error_msg = result.get('error', '不明なエラー')
    print(f"❌ エラー: {error_msg}")

    # 認証エラーの場合は再登録が必要
    if '認証エラー' in error_msg:
        print("🔄 再登録を試行中...")
        NLC.addCameraList()  # トークン再取得
```

---

## 📋 チェックリスト

実装変更時に以下を確認してください：

- [ ] NET8License.pyファイルを最新版に更新
- [ ] slotserver.py内の全てのcameraAPI.php呼び出しを`callCameraAPI()`に変更
- [ ] NLCインスタンスをグローバル変数として定義
- [ ] domain設定を追加
- [ ] エラーハンドリングを追加
- [ ] ログ出力を追加（デバッグ用）
- [ ] 動作テストを実施

---

## 🧪 テスト手順

### 1. 登録テスト

```python
NLC = NET8License()
NLC.setInfo('domain', 'mgg-webservice-production.up.railway.app')
license_id = NLC.addCameraList()

print(f"License ID: {license_id}")
print(f"Machine No: {NLC.getInfo('machine_no')}")
print(f"Token: {NLC.getInfo('token')}")
```

**期待結果:**
- license_idが返ってくる
- machine_noが取得できる
- tokenが取得できる（32文字のhex文字列）

---

### 2. API呼び出しテスト

```python
# カメラ起動テスト
result = NLC.callCameraAPI('start')
print(f"Start Result: {result}")

# 状態取得テスト
result = NLC.callCameraAPI('status')
print(f"Status Result: {result}")

# カメラ終了テスト
result = NLC.callCameraAPI('end')
print(f"End Result: {result}")
```

**期待結果:**
- 全て `{'status': 'ok'}` が返る
- エラーがある場合は `{'status': 'ng', 'error': 'エラー内容'}` が返る

---

### 3. トークン認証エラーのテスト

```python
# わざと間違ったトークンをセット
NLC.setInfo('token', 'invalid_token_12345')

result = NLC.callCameraAPI('start')
print(f"Result: {result}")
```

**期待結果:**
```json
{
    "status": "ng",
    "error": "認証エラー: トークンが無効です"
}
```

---

## 💡 トラブルシューティング

### エラー1: `'token'がありません`

**原因:** addCameraList()を呼ぶ前にcallCameraAPI()を呼んでいる

**解決策:**
```python
NLC = NET8License()
NLC.setInfo('domain', '...')
NLC.addCameraList()  # これを先に呼ぶ！
NLC.callCameraAPI('start')  # その後にAPI呼び出し
```

---

### エラー2: `認証エラー: トークンが無効です`

**原因:**
- データベース上のトークンと一致していない
- machine_noが間違っている

**解決策:**
```python
# 再登録してトークンを取得し直す
NLC.addCameraList()
```

---

### エラー3: `'machine_no'がありません`

**原因:** addCameraList()のレスポンスにmachine_noが含まれていない

**解決策:**
```python
# サーバー側のcameraListAPI.phpが正しく実装されているか確認
# machine_noを手動で設定（一時的な対処）
NLC.setInfo('machine_no', 1)
```

---

## 📞 サポート

問題が発生した場合は、以下の情報を含めて報告してください：

1. エラーメッセージ
2. 実行したコード
3. NLC.getInfo()の出力
4. サーバーからのレスポンス（result変数の内容）

---

**実装日:** 2025年11月13日
**サーバー側実装:** 完了済み
**Windows側実装:** このガイドに従って実装してください
