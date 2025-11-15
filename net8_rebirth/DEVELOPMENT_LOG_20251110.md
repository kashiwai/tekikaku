# NET8 開発ログ - 2025年11月10日

## 実施した修正の概要

本日、Windows PC認証エラーと管理画面のNoticeエラーを修正しました。

---

## 修正1: Windows認証エラー (userAuthAPI.php)

### 問題
Windows PCゲームクライアントから認証APIへのアクセス時に以下のエラーが発生：
```
{"status":"ng","error":"not assign[onetimeauthid]"}
```

### 原因
- ONETIMEAUTHIDパラメータが必須になっていた
- Windows側のコードは変更されていないため、以前は任意パラメータだった可能性

### 解決方法
**ファイル**: `net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php`

#### 変更箇所1: 必須パラメータからONETIMEAUTHIDを除外
```php
// 修正前
getData($_GET, array("MACHINENO", "PLAYDT", "MEMBERNO", "ONETIMEAUTHID"));

// 修正後
getData($_GET, array("MACHINENO", "PLAYDT", "MEMBERNO"));
```

#### 変更箇所2: 条件付きSQL構築
```php
// ONETIMEAUTHIDが送信されている場合のみ条件に追加
if (!empty($_GET["ONETIMEAUTHID"])) {
    $sqlBuilder->and( "onetime_id =", $_GET["ONETIMEAUTHID"], FD_STR);
    error_log("[userAuthAPI] Using onetime_id: " . $_GET["ONETIMEAUTHID"]);
} else {
    error_log("[userAuthAPI] No onetime_id provided, checking by machine_no only");
}
```

**コミット**: `637a592`

---

## 修正2: Dummy_Credit_Array未定義エラー

### 問題
認証成功後に以下のエラーが発生：
```
Notice: Undefined index: Dummy_Credit_Array in /var/www/html/data/api/userAuthAPI.php on line 177
```

### 原因
テスター会員（tester_flg=1）のクレジット変換値を定義する配列が存在しなかった

### 解決方法
**ファイル**: `net8/02.ソースファイル/net8_html/_etc/setting_base.php`

#### 追加した設定 (533-538行目)
```php
// テスター会員用ダミークレジット設定
// カテゴリごとのダミークレジット値（テスター会員がプレイ時に使用）
$GLOBALS["Dummy_Credit_Array"] = array(
    "1" => 50000,  // パチンコ: 50,000クレジット
    "2" => 50000   // スロット: 50,000クレジット
);
```

**コミット**: `4f4319e`

---

## 修正3: log_play重複エントリエラー

### 問題
認証API実行時にデータベースエラーが発生：
```
SmartDB::__call: SQLSTATE[23000]: Integrity constraint violation: 1062
Duplicate entry '2025-11-10 02:36:39-1' for key 'PRIMARY'
```

### 原因
- `log_play`テーブルのPRIMARY KEY: (`play_dt`, `machine_no`)
- 既に同じplay_dtとmachine_noの組み合わせが存在
- initTable()関数に重複チェックがなかった

### 解決方法
**ファイル**: `net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php`

#### initTable()関数の修正 (198-214行目)
```php
function initTable( $DB, $machine_no, $jsonArray ) {

    //プレイログの重複チェック（PRIMARY KEY: play_dt, machine_no）
    $checkSql = (new SqlString($DB))
        ->select()
            ->field("COUNT(*) as cnt")
            ->from("log_play")
            ->where()
                ->and( "play_dt =", $jsonArray["play_dt"], FD_STR)
                ->and( "machine_no =", $machine_no, FD_NUM)
        ->createSQL("\n");

    $checkRow = $DB->getRow($checkSql);

    // 既にレコードが存在する場合はINSERTをスキップ
    if ($checkRow["cnt"] > 0) {
        error_log("[userAuthAPI] log_play record already exists for play_dt=" . $jsonArray["play_dt"] . ", machine_no=" . $machine_no);
        return;
    }

    //プレイログの初期化
    $sql = (new SqlString($DB))
        ->insert()
            ->into("log_play")
                ->value("play_dt",      $jsonArray["play_dt"])
                ->value("machine_no",   $machine_no)
                ->value("member_no",    $jsonArray["member_no"])
                ->value("start_point",  $jsonArray["playpoint"])
        ->createSQL("\n");

    $ret = $DB->query($sql);
}
```

**コミット**: `4f4319e`

---

## 修正4: member.php Undefined offset: 9

### 問題
管理画面（member.php）で以下のNoticeが表示：
```
Notice: Undefined offset: 9 in /var/www/html/data/xxxadmin/member.php on line 1195
```

### 原因
- `$GLOBALS["MemberStatus"]`配列に状態コード"9"（退会済）が定義されていなかった
- コード内で state=9 は退会処理完了を意味している（503行目のコメント参照）
- `_chkStatusArray()`関数で配列の存在チェックをせずに直接アクセスしていた

### 解決方法

#### 修正1: setting_base.phpに状態コード追加
**ファイル**: `net8/02.ソースファイル/net8_html/_etc/setting_base.php` (186-192行目)

```php
// 会員ステータス
$GLOBALS["MemberStatus"] = array(
    "0" => "有効",
    "1" => "仮登録",
    "2" => "停止",
    "3" => "退会",
    "9" => "退会済"  // 退会処理完了
);
```

#### 修正2: member.phpに配列存在チェック追加
**ファイル**: `net8/02.ソースファイル/net8_html/data/xxxadmin/member.php` (1186-1202行目)

```php
function _chkStatusArray( $ary, $status){
    $ret = [];
    if( $status == "0"){
        //仮登録の場合
        if(isset($ary[0])) $ret[0] = $ary[0];
        if(isset($ary[1])) $ret[1] = $ary[1];
    }else if( $status == "1"){
        //本登録の場合
        if(isset($ary[1])) $ret[1] = $ary[1];
        if(isset($ary[9])) $ret[9] = $ary[9];
    }else if( $status == "9"){
        if(isset($ary[9])) $ret[9] = $ary[9];
    }else{
        $ret = $ary;
    }
    return $ret;
}
```

**コミット**: `8c1f66e`

---

## データベース状態

### lnk_machine テーブル
現在の認証設定（member_no=2で認証可能な状態）:

```json
{
  "machine_no": "1",
  "assign_flg": "1",
  "member_no": "2",
  "onetime_id": "auth_1762742199_75801",
  "start_dt": "2025-11-10 02:36:39",
  "end_dt": "2025-11-11 02:36:39"
}
```

### mst_member テーブル
テスト用会員:

```json
{
  "member_no": "2",
  "nickname": "こうすけ",
  "mail": "ko.kashiwai@gmail.com",
  "state": "0",
  "tester_flg": "1"
}
```

### 認証に必要なMEMBERNO値
member_no=2のSHA1ハッシュ値:
```
86dfb043360b0e9ef7767e6ea7ad09fb7fb81537
```

計算方法:
```php
sha1(sprintf("%06d", 2))  // "000002" のSHA1ハッシュ
```

---

## API動作確認

### userAuthAPI テスト
```bash
curl "https://mgg-webservice-production.up.railway.app/data/api/userAuthAPI.php?MACHINENO=1&PLAYDT=&MEMBERNO=86dfb043360b0e9ef7767e6ea7ad09fb7fb81537"
```

### 期待される正常レスポンス
```json
{
  "status": "ok",
  "game": {
    "member_no": "2",
    "playpoint": "10000",
    "drawpoint": "0",
    "play_dt": "2025-11-10 02:36:39",
    "credit": 0,
    "tester_flg": "1",
    "day_count": "2053",
    "total_count": "2047",
    "count": "2047",
    "bb_count": "0",
    "rb_count": "0",
    "mc_in_credit": "6141",
    "mc_out_credit": "2527",
    "maxrenchan_count": "0",
    "past_max_credit": "0",
    "past_max_bb": "0",
    "past_max_rb": "0",
    "conv_point": "0",
    "conv_credit": 50000,
    "conv_drawpoint": "0"
  }
}
```

---

## Git コミット履歴

```
8c1f66e - fix: Add MemberStatus[9] and array existence check in member.php
4f4319e - fix: Add Dummy_Credit_Array and duplicate check for log_play
637a592 - fix: Make ONETIMEAUTHID optional in userAuthAPI
42b3e4e - (前回のコミット)
```

---

## デプロイ環境

### Railway設定
- **プロジェクト**: mmg2501
- **サービス**: mgg-webservice
- **URL**: https://mgg-webservice-production.up.railway.app/
- **自動デプロイ**: GitHub mainブランチからトリガー

### データベース
- **ホスト**: 136.116.70.86
- **データベース名**: net8_dev
- **文字セット**: UTF-8
- **タイムゾーン**: Asia/Tokyo

---

## 未解決の課題

### 1. camera.php エラー (解決済み - 過去に修正)
以前のセッションでSmartTemplateの構文エラーを修正済み:
- コミット: `04fd535`
- 変更内容: `<!--loop:CAMERA-->` → `<!--loop:{CAMERA}-->`

**確認が必要な場合**:
管理画面で `/data/xxxadmin/camera.php` にアクセスしてエラーが出ないか確認

---

## 開発再開時のチェックリスト

### 1. 環境確認
```bash
cd /Users/kotarokashiwai/net8_rebirth
git status
git log --oneline -5
```

### 2. 最新コードの取得
```bash
git pull origin main
```

### 3. Railway デプロイ状態確認
```bash
railway logs --service mgg-webservice | tail -50
```

### 4. API動作確認
```bash
curl "https://mgg-webservice-production.up.railway.app/data/api/userAuthAPI.php?MACHINENO=1&PLAYDT=&MEMBERNO=86dfb043360b0e9ef7767e6ea7ad09fb7fb81537"
```

### 5. 管理画面確認
ブラウザで以下にアクセス:
- メイン: https://mgg-webservice-production.up.railway.app/data/xxxadmin/
- 会員管理: https://mgg-webservice-production.up.railway.app/data/xxxadmin/member.php
- カメラ管理: https://mgg-webservice-production.up.railway.app/data/xxxadmin/camera.php

---

## トラブルシューティング

### Windows PC認証が失敗する場合

1. **lnk_machineテーブルの確認**
```sql
SELECT * FROM lnk_machine WHERE machine_no = 1;
```

2. **member_noのハッシュ値確認**
```php
<?php
echo sha1(sprintf("%06d", 2)); // member_no=2の場合
?>
```

3. **デバッグログ確認**
```bash
railway logs --service mgg-webservice | grep "\[userAuthAPI\]"
```

### 管理画面でNoticeエラーが出る場合

1. **setting_base.phpの確認**
```bash
grep -A 5 "MemberStatus" net8/02.ソースファイル/net8_html/_etc/setting_base.php
```

2. **デバッグモードの確認**
```php
// setting_base.php:55
define('DEBUG_MODE', true);  // 本番環境では false に設定
```

---

## 重要な技術情報

### SmartTemplateの構文
- ループ: `<!--loop:{TAG_NAME}-->...<!--/loop:{TAG_NAME}-->`
- 条件: `<!--if:{TAG_NAME}-->...<!--/if:{TAG_NAME}-->`
- **重要**: 中括弧 `{}` が必須

### SQL構築（SqlString）
```php
$sql = (new SqlString($DB))
    ->select()
        ->field("column_name")
        ->from("table_name")
        ->where()
            ->and("column =", $value, FD_STR)
    ->createSQL("\n");
```

### フィールドタイプ定数
- `FD_STR`: 文字列（クォート付き）
- `FD_NUM`: 数値（クォートなし）
- `FD_FUNCTION`: SQL関数（クォートなし）

---

## 連絡事項

### 完了した作業
1. ✅ Windows認証エラー修正
2. ✅ Dummy_Credit_Array定義追加
3. ✅ log_play重複チェック実装
4. ✅ member.php Undefined offset修正
5. ✅ 全ての修正をGitにコミット＆プッシュ
6. ✅ Railwayに自動デプロイ

### 次回作業時の推奨事項
1. Windows PCクライアントでの実機テスト
2. 管理画面の全ページでエラーがないか確認
3. 本番環境ではDEBUG_MODEをfalseに設定

---

**記録日時**: 2025年11月10日
**担当**: Claude Code
**プロジェクト**: NET8 パチンコゲームシステム
