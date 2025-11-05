# 管理画面メンバー登録ガイド

## 🔍 問題の原因分析

管理画面からメンバー登録ができない原因として、以下の可能性があります：

### 1. パスワードの要件を満たしていない

**パスワード要件：**
- 最小8文字
- 英字（A-Z, a-z）を1文字以上含む
- 数字（0-9）を1文字以上含む
- 使用可能文字：英数字のみ（特殊文字不可）

**正規表現パターン：** `^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$`

**良い例：**
- `password123` ✅
- `Admin2024` ✅
- `test1234` ✅

**悪い例：**
- `password` ❌（数字がない）
- `12345678` ❌（英字がない）
- `pass123` ❌（8文字未満）
- `pass@123` ❌（特殊文字が含まれている）

### 2. メールアドレスまたはニックネームの重複

データベースで既に使用されているメールアドレスやニックネームは登録できません。

```sql
-- 既存メールアドレスを確認
SELECT mail, nickname FROM mst_member WHERE mail = 'yourmail@example.com';

-- 既存ニックネームを確認
SELECT nickname, mail FROM mst_member WHERE nickname = 'ニックネーム';
```

### 3. 管理者セッションの問題

管理画面にログインしていない、またはセッションが切れている場合、登録処理が失敗します。

**確認方法：**
- 管理画面ログイン: `https://[your-domain]/data/xxxadmin/`
- ユーザー名: `admin`
- パスワード: `admin123`

## 📝 管理画面での正しい登録手順

### ステップ1: 管理画面にログイン

1. `https://[your-domain]/data/xxxadmin/` にアクセス
2. ユーザー名: `admin`、パスワード: `admin123` でログイン

### ステップ2: メンバー管理画面を開く

1. メニューから「会員管理」を選択
2. 「新規登録」ボタンをクリック

### ステップ3: 必須項目を入力

**必須項目：**
- **ニックネーム**: 未使用のニックネームを入力
- **メールアドレス**: 有効なメールアドレス形式で、未使用のもの
- **パスワード**: 8文字以上、英数字混在（例: `password123`）

**任意項目：**
- 性別
- 生年月日
- 備考
- その他のフィールド

### ステップ4: 登録実行

1. 「登録」ボタンをクリック
2. エラーメッセージが表示された場合は、内容を確認して修正
3. 成功すると、完了画面が表示されます

## 🚀 直接DB登録によるテスト

管理画面での登録がうまくいかない場合、まずDBに直接テストメンバーを登録してシステムが動作するか確認します。

### GCP Cloud SQL Studio で実行

```sql
-- テストメンバー登録（パスワード: password123）
INSERT INTO mst_member (
    nickname,
    mail,
    pass,
    sex,
    point,
    draw_point,
    state,
    regist_id,
    invite_cd,
    join_dt,
    mail_magazine,
    tester_flg,
    add_dt,
    add_no,
    upd_dt,
    upd_no
) VALUES (
    'テストユーザー',
    'test@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1,
    1000,
    0,
    1,
    '0',
    CONCAT('TEST', FLOOR(RAND() * 100000000)),
    NOW(),
    0,
    0,
    NOW(),
    1,
    NOW(),
    1
);
```

### ログインテスト

登録後、ユーザーログイン画面でテスト：
- URL: `https://[your-domain]/data/login.php`
- メール: `test@example.com`
- パスワード: `password123`

## 🐛 デバッグ方法

### エラーログの確認

Railway環境でエラーログを確認：
```bash
railway logs --deployment
```

ローカル環境：
```bash
tail -f /var/www/html/_sys/log/error.log
```

### データベース接続確認

```php
<?php
require_once __DIR__ . '/_etc/setting.php';

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);
    echo "✅ DB接続成功\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM mst_member");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "会員数: " . $result['count'] . "\n";
} catch (PDOException $e) {
    echo "❌ DB接続失敗: " . $e->getMessage() . "\n";
}
?>
```

## 📊 バリデーションエラーコード一覧

| コード | 意味 |
|--------|------|
| A0501 | ニックネームが未入力 |
| A0502 | メールアドレスが未入力 |
| A0503 | メールアドレスの形式が不正 |
| A0504 | パスワードが未入力 |
| A0505 | パスワードが最小文字数未満 |
| A0506 | パスワードが最大文字数超過 |
| A0517 | パスワードが英数字以外を含む |
| A0518 | パスワードが要求パターンに一致しない |
| A0531 | メールアドレスが重複 |
| A0532 | ニックネームが重複 |
| A0533 | ブラックリスト登録済みメールアドレス |

## 🔐 セキュリティ注意事項

- パスワードは `password_hash($pass, PASSWORD_DEFAULT)` でbcrypt暗号化
- MD5やSHA1ではなく、bcryptを使用
- テストアカウントは本番環境では削除すること

## 📞 次のステップ

1. **まずテスト用メンバーをDBに直接登録**
   - `test_member_insert.sql` を GCP Cloud SQL Studio で実行

2. **ログイン機能をテスト**
   - ユーザーログイン画面でテストユーザーでログイン

3. **ログイン成功後、管理画面からの登録を再試行**
   - パスワード要件を満たすパスワードを使用
   - 未使用のメールアドレスとニックネームを使用

4. **それでも失敗する場合**
   - ブラウザの開発者ツールでネットワークタブを開く
   - 登録ボタンをクリックして、サーバーのレスポンスを確認
   - エラーメッセージの詳細を教えてください
