# SQLSTATE[HY093]バグ修正の技術詳細

## 問題の概要

**エラーメッセージ**:
```
System error SmartDB::__call: SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters
```

**発生場所**: 管理画面の新規登録機能（M=regist）
- model.php (機種登録)
- maker.php (メーカー登録)
- member.php (会員登録)
- owner.php (オーナー登録)
- pointconvert.php (ポイント変換登録)

---

## 根本原因

### SqlStringクラスの仕様
`SqlString` クラスは、空文字列 (`""`) をパラメータとして渡されると、SQL文に空の条件を追加してしまう。

**問題のコード例** (model.php 修正前):
```php
// ❌ BAD: $_POST["MODEL_NAME"] が空文字列の場合でもSQL構築される
$sqlNameDupli = (new SqlString())
    ->setAutoConvert( [$template->DB,"conv_sql"] )
    ->select()
    ->field( "count(*)" )
    ->from( "mst_model" )
    ->where()
        ->and( "model_name = ", $_POST["MODEL_NAME"], FD_STR)  // ← 空文字列でもSQL生成
        ->and( "del_flg = ", "0", FD_NUM)
        ->and( "model_no <> ", $_POST["MODEL_NO"], FD_NUM)    // ← 空文字列でもSQL生成
    ->createSql();

// SmartAutoCheckで使用
->item($_POST["MODEL_NAME"])
    ->countSQL("A1451", $sqlNameDupli)  // ← 不正なSQLが渡される
```

**生成されるSQL（不正）**:
```sql
SELECT count(*) FROM mst_model
WHERE model_name = ? AND del_flg = ? AND model_no <> ?
-- パラメータ: ['', 0, '']  ← 空文字列が混在してPDOエラー
```

---

## 修正方法

### パターン1: 条件付きSQL構築（推奨）

**修正後のコード**:
```php
// ✅ GOOD: 値が入力されている場合のみSQL構築
$sqlNameDupli = null;  // ← まずnullで初期化
if (mb_strlen($_POST["MODEL_NAME"]) > 0) {  // ← 値チェック
    $sqlNameDupli = (new SqlString())
        ->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
        ->field( "count(*)" )
        ->from( "mst_model" )
        ->where()
            ->and( "model_name = ", $_POST["MODEL_NAME"], FD_STR)
            ->and( "del_flg = ", "0", FD_NUM);

    // 更新の場合のみ追加条件
    if (mb_strlen($_POST["MODEL_NO"]) > 0) {
        $sqlNameDupli->and( "model_no <> ", $_POST["MODEL_NO"], FD_NUM);
    }

    $sqlNameDupli = $sqlNameDupli->createSql();
}

// SmartAutoCheckでnullチェック
->item($_POST["MODEL_NAME"])
    ->required("A1402")
    ->maxLength("A1403", 200)
    ->case($sqlNameDupli !== null)  // ← nullでない場合のみ実行
        ->countSQL("A1451", $sqlNameDupli)
```

---

## 修正済みファイル一覧

### 1. model.php (3箇所修正)

#### 修正箇所1: checkInput() - 機種名重複チェック (行1100-1117)
```php
$sqlNameDupli = null;
if (mb_strlen($_POST["MODEL_NAME"]) > 0) {
    $sqlNameDupli = (new SqlString())
        ->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
        ->field( "count(*)" )
        ->from( "mst_model" )
        ->where()
            ->and( "model_name = ", $_POST["MODEL_NAME"], FD_STR)
            ->and( "del_flg = ", "0", FD_NUM);
    if (mb_strlen($_POST["MODEL_NO"]) > 0) {
        $sqlNameDupli->and( "model_no <> ", $_POST["MODEL_NO"], FD_NUM);
    }
    $sqlNameDupli = $sqlNameDupli->createSql();
}
```

#### 修正箇所2: checkInput() - 機種名（英語）重複チェック (行1119-1134)
```php
$sqlRomanDupli = null;
if (mb_strlen($_POST["MODEL_ROMAN"]) > 0) {
    $sqlRomanDupli = (new SqlString())
        ->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
        ->field( "count(*)" )
        ->from( "mst_model" )
        ->where()
            ->and( "model_roman = ", $_POST["MODEL_ROMAN"], FD_STR)
            ->and( "del_flg = ", "0", FD_NUM);
    if (mb_strlen($_POST["MODEL_NO"]) > 0) {
        $sqlRomanDupli->and( "model_no <> ", $_POST["MODEL_NO"], FD_NUM);
    }
    $sqlRomanDupli = $sqlRomanDupli->createSql();
}
```

#### 修正箇所3: SmartAutoCheckでのnull-safeガード (行1166-1173)
```php
->case($sqlNameDupli !== null)
    ->countSQL("A1451", $sqlNameDupli)
//機種名（英語）
->item($_POST["MODEL_ROMAN"])
    ->required("A1404")
    ->maxLength("A1405", 200)
    ->case($sqlRomanDupli !== null)
        ->countSQL("A1456", $sqlRomanDupli)
```

### 2. maker.php (2箇所修正)

#### 修正箇所1: checkInput() - メーカー名重複チェック (行393-407)
```php
$sqlNameDupli = null;
if (mb_strlen($_POST["MAKER_NAME"]) > 0) {
    $sqlNameDupli = (new SqlString())
        ->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
        ->field( "count(*)" )
        ->from( "mst_maker" )
        ->where()
            ->and( "maker_name = ", $_POST["MAKER_NAME"], FD_STR)
            ->and( "del_flg != ", "1", FD_NUM)
            ->and( true, "maker_no <> ", $_POST["MAKER_NO"], FD_NUM)
    ->createSql();
}
```

#### 修正箇所2: SmartAutoCheckでのnull-safeガード (行471-474)
```php
->item($_POST["MAKER_NAME"])
    ->required("A1301")
    ->maxLength("A1302", 50)
    ->case($sqlNameDupli !== null)
        ->countSQL("A1307", $sqlNameDupli)
```

### 3. member.php (7箇所修正)

#### 修正箇所1: メールアドレス重複チェック (行947-961)
#### 修正箇所2: ニックネーム重複チェック (行963-977)
#### 修正箇所3: 会員番号重複チェック (行979-993)
#### 修正箇所4: 電話番号重複チェック (行995-1009)
#### 修正箇所5: SSID重複チェック (行1011-1020)
#### 修正箇所6: SmartAutoCheckのnull-safeガード (行1044-1068)

### 4. owner.php (1箇所修正)

#### ニックネーム重複チェック (行600-614)
```php
$sqlNkNameDupli = null;
if (mb_strlen($_POST["OWNER_NICKNAME"]) > 0) {
    $sqlNkNameDupli = (new SqlString())
        ->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
        ->field( "count(*)" )
        ->from( "mst_owner" )
        ->where()
            ->and( "owner_nickname = ", $_POST["OWNER_NICKNAME"], FD_STR)
            ->and( "del_flg != ", "1", FD_NUM)
            ->and( true, "owner_no <> ", $_POST["OWNER_NO"], FD_NUM)
    ->createSql();
}
```

### 5. pointconvert.php (1箇所修正)

#### 名称重複チェック (行361-375)
```php
$sqlNameDupli = null;
if (mb_strlen($_POST["CONVERT_NAME"]) > 0) {
    $sqlNameDupli = (new SqlString())
        ->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
        ->field("count(*)")
        ->from("mst_convertPoint")
        ->where()
            ->and( "convert_name = ", $_POST["CONVERT_NAME"], FD_STR)
            ->and( "del_flg != ", "1", FD_NUM)
            ->and( true, "convert_no <> ", $_POST["CONVERT_NO"], FD_NUM)
    ->createSql("\n");
}
```

---

## テスト方法

### 1. ローカルテスト
```bash
# PHPサーバー起動
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
php -S localhost:8888

# 別ターミナルでテスト
curl -X POST http://localhost:8888/data/xxxadmin/model.php \
  -d "M=regist" \
  -d "MODEL_NAME=テスト機種" \
  -d "MODEL_ROMAN=TEST01" \
  -d "CATEGORY=1" \
  # ... 他の必須項目
```

### 2. 本番テスト
1. 管理画面にアクセス: https://mgg-webservice-production.up.railway.app/xxxadmin/
2. ログイン（admin）
3. 各登録画面で新規登録を試す:
   - 機種登録
   - メーカー登録
   - 会員登録
   - オーナー登録
   - ポイント変換登録
4. 全項目入力せずに「登録」ボタン → エラーメッセージが正しく表示されるか
5. 全項目入力して「登録」ボタン → 成功すればOK

---

## Git履歴

```bash
# 修正コミット
ddab88a - fix: 全admin画面のSQLSTATE[HY093]バグを一括修正

# 修正内容を確認
git show ddab88a
```

---

## 参考: SmartAutoCheckクラスの仕様

### countSQL()メソッド
```php
/**
 * SQLのCOUNT結果をチェック
 * @param string $errorCode エラーコード
 * @param string $sql 実行するSQL
 * @return self
 */
public function countSQL($errorCode, $sql) {
    $count = $this->template->DB->getOne($sql);
    if ($count > 0) {
        $this->errors[] = $this->template->message($errorCode);
    }
    return $this;
}
```

**重要**: `$sql` に不正なSQLが渡されると、`getOne()` でPDOExceptionが発生する。

---

## 今後の予防策

1. **SqlString使用時のチェックリスト**:
   - [ ] パラメータが空文字列の可能性があるか？
   - [ ] 条件付きSQL構築を使っているか？
   - [ ] SmartAutoCheckで`case()`を使っているか？

2. **コードレビュー時の確認項目**:
   - 新規登録機能の追加時は必ず同様のパターンを確認
   - SqlStringクラスの使用箇所を検索: `grep -r "SqlString" data/xxxadmin/`

3. **自動テストの追加**:
   - 空文字列パラメータでの登録テスト
   - 重複チェックのテスト

---

**作成日**: 2025/11/06
**関連コミット**: ddab88a, 6fe4409, c3a094b
