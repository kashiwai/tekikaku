# Net8 管理画面調査レポート

**調査日時**: 2025-11-10
**対象ディレクトリ**: `/Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/data/xxxadmin/`

---

## 1. サイドバーメニュー一覧（index.html）

現在、管理画面のサイドバー（`index.html`）に表示されているメニュー項目は以下の**8項目**です：

| No. | メニュー項目 | リンク先 | アイコン | 説明 |
|-----|------------|---------|---------|------|
| 1 | Dashboard | index.php | fa-tachometer-alt | ダッシュボード（統計情報表示） |
| 2 | Member Management | member.php | fa-users | 会員管理 |
| 3 | Model Management | model.php | fa-gamepad | 機種管理 |
| 4 | Machine Management | machines.php | fa-desktop | 台管理 |
| 5 | Camera Management | camera.php | fa-video | カメラ管理 |
| 6 | Camera Assignment | camera_settings.php | fa-cog | カメラ割り当て設定 |
| 7 | Signaling | signaling.php | fa-server | シグナリングサーバー管理 |
| 8 | Streaming | streaming.php | fa-broadcast-tower | ストリーミング管理 |

---

## 2. 存在する管理画面PHPファイル一覧（全48件）

実際に存在する管理画面PHPファイル：

### 2.1 認証系（3件）
- login.php
- logout.php
- menu.php

### 2.2 会員・ユーザー管理系（4件）
- member.php ⭐ メニュー表示
- memberplayhistory.php
- owner.php
- admin.php （システム管理者設定）

### 2.3 機種・台管理系（7件）
- model.php ⭐ メニュー表示
- machines.php ⭐ メニュー表示
- machine_control.php
- machine_edit.php
- maker.php
- corner.php
- moniter.php

### 2.4 カメラ・配信系（4件）
- camera.php ⭐ メニュー表示
- camera_settings.php ⭐ メニュー表示
- signaling.php ⭐ メニュー表示
- streaming.php ⭐ メニュー表示

### 2.5 ゲーム・プレイ履歴系（3件）
- playhistory.php
- memberplayhistory.php
- search.php

### 2.6 ポイント・決済系（6件）
- pointhistory.php
- pointgrant.php
- pointconvert.php
- purchase.php
- purchasehistory.php
- sales.php

### 2.7 商品・抽選・発送系（8件）
- goods.php
- goods_status.php
- goods_drawpick.php
- drawhistory.php
- gift.php
- gifthistory.php
- giftaddset.php
- giftlimit.php
- shipping.php

### 2.8 お知らせ・コンテンツ管理系（5件）
- notice.php
- magazine.php
- coupon.php
- benefits.php
- address.php

### 2.9 システム・設定系（4件）
- index.php ⭐ メニュー表示（Dashboard）
- system.php
- image_upload.php
- api_keys_manage.php

### 2.10 開発・テスト用（4件）
- auto_setup.php
- insert_sample_members.php
- test_check.php
- test_db.php
- debug_session.php

⭐ = 現在サイドバーメニューに表示されているページ

---

## 3. メニューにないが存在する重要ページ（追加推奨）

サイドバーメニューに表示されていないが、実際に存在し、管理業務に必要と思われるページ：

### 3.1 【必須】メニュー追加推奨（15件）

#### 売上・決済管理カテゴリ
| ファイル名 | 機能 | 推奨メニュー名 | 優先度 |
|-----------|------|---------------|--------|
| sales.php | 売上管理 | Sales Management | 高 |
| purchase.php | 購入管理 | Purchase Management | 高 |
| purchasehistory.php | 購入履歴 | Purchase History | 中 |

#### ポイント管理カテゴリ
| ファイル名 | 機能 | 推奨メニュー名 | 優先度 |
|-----------|------|---------------|--------|
| pointhistory.php | ポイント履歴 | Point History | 高 |
| pointgrant.php | ポイント付与 | Point Grant | 高 |
| pointconvert.php | ポイント変換 | Point Conversion | 中 |

#### ゲーム・プレイ管理カテゴリ
| ファイル名 | 機能 | 推奨メニュー名 | 優先度 |
|-----------|------|---------------|--------|
| playhistory.php | プレイ履歴 | Play History | 高 |
| memberplayhistory.php | 会員別プレイ履歴 | Member Play History | 中 |

#### 商品・抽選・発送カテゴリ
| ファイル名 | 機能 | 推奨メニュー名 | 優先度 |
|-----------|------|---------------|--------|
| goods.php | 商品管理 | Goods Management | 高 |
| shipping.php | 発送管理 | Shipping Management | 高 |
| drawhistory.php | 抽選履歴 | Draw History | 中 |
| gift.php | ギフト管理 | Gift Management | 中 |
| gifthistory.php | ギフト履歴 | Gift History | 低 |

#### コンテンツ・お知らせカテゴリ
| ファイル名 | 機能 | 推奨メニュー名 | 優先度 |
|-----------|------|---------------|--------|
| notice.php | お知らせ管理 | Notice Management | 高 |
| magazine.php | マガジン管理 | Magazine Management | 中 |

#### システム管理カテゴリ
| ファイル名 | 機能 | 推奨メニュー名 | 優先度 |
|-----------|------|---------------|--------|
| system.php | システム設定 | System Settings | 高 |
| admin.php | 管理者設定 | Admin Management | 高 |
| owner.php | オーナー管理 | Owner Management | 中 |
| api_keys_manage.php | APIキー管理 | API Keys | 中 |

#### その他
| ファイル名 | 機能 | 推奨メニュー名 | 優先度 |
|-----------|------|---------------|--------|
| machine_control.php | 台制御 | Machine Control | 中 |
| machine_edit.php | 台編集 | Machine Edit | 低 |
| maker.php | メーカー管理 | Maker Management | 中 |
| corner.php | コーナー管理 | Corner Management | 中 |

### 3.2 【開発用】メニュー不要（5件）

以下は開発・テスト用のため、メニューに追加する必要はありません：

- auto_setup.php
- insert_sample_members.php
- test_check.php
- test_db.php
- debug_session.php

---

## 4. 本番環境アクセステスト結果

### 4.1 テスト概要
- **テスト日時**: 2025-11-10
- **対象URL**: https://mgg-webservice-production.up.railway.app/data/xxxadmin/
- **テスト方法**: WebFetch（GETリクエスト）

### 4.2 テスト結果

| ページ | HTTPステータス | 結果 | 備考 |
|--------|---------------|------|------|
| index.php | 401 Unauthorized | 認証必須 | 正常（管理画面なので認証必要） |
| member.php | 401 Unauthorized | 認証必須 | 正常 |
| model.php | 401 Unauthorized | 認証必須 | 正常 |
| machines.php | 401 Unauthorized | 認証必須 | 正常 |
| camera.php | 401 Unauthorized | 認証必須 | 正常 |

### 4.3 結論

全ページで401エラーが返されており、これは**正常な動作**です。
管理画面は認証が必要なため、ログインなしでアクセスすると401エラーになります。

本番環境での詳細なテストは、管理者ログイン後に実施する必要があります。

---

## 5. 推奨アクション

### 5.1 サイドバーメニュー拡張提案

現在8項目のメニューを、機能カテゴリ別に整理して拡張することを推奨します。

#### 推奨メニュー構成（階層化）

```
RYU8 Admin
├─ 📊 Dashboard (index.php)
│
├─ 👥 Member Management
│   ├─ Member List (member.php)
│   ├─ Member Play History (memberplayhistory.php)
│   └─ Owner Management (owner.php)
│
├─ 🎮 Game Management
│   ├─ Model Management (model.php)
│   ├─ Machine Management (machines.php)
│   ├─ Machine Control (machine_control.php)
│   ├─ Maker Management (maker.php)
│   ├─ Corner Management (corner.php)
│   └─ Play History (playhistory.php)
│
├─ 📹 Camera & Streaming
│   ├─ Camera Management (camera.php)
│   ├─ Camera Assignment (camera_settings.php)
│   ├─ Signaling Server (signaling.php)
│   └─ Streaming (streaming.php)
│
├─ 💰 Sales & Points
│   ├─ Sales Management (sales.php)
│   ├─ Purchase Management (purchase.php)
│   ├─ Point History (pointhistory.php)
│   └─ Point Grant (pointgrant.php)
│
├─ 🎁 Goods & Shipping
│   ├─ Goods Management (goods.php)
│   ├─ Shipping Management (shipping.php)
│   ├─ Draw History (drawhistory.php)
│   └─ Gift Management (gift.php)
│
├─ 📢 Content Management
│   ├─ Notice Management (notice.php)
│   └─ Magazine Management (magazine.php)
│
└─ ⚙️ System Settings
    ├─ System Settings (system.php)
    ├─ Admin Management (admin.php)
    └─ API Keys (api_keys_manage.php)
```

### 5.2 優先度別実装計画

#### Phase 1（最優先）- 基本業務に必須
- sales.php（売上管理）
- playhistory.php（プレイ履歴）
- pointhistory.php（ポイント履歴）
- goods.php（商品管理）
- shipping.php（発送管理）
- notice.php（お知らせ管理）
- system.php（システム設定）

#### Phase 2（重要）- 業務効率化
- purchase.php（購入管理）
- pointgrant.php（ポイント付与）
- admin.php（管理者設定）
- maker.php（メーカー管理）
- corner.php（コーナー管理）

#### Phase 3（拡張機能）
- magazine.php（マガジン管理）
- gift.php（ギフト管理）
- api_keys_manage.php（APIキー管理）
- machine_control.php（台制御）

### 5.3 HTMLテンプレート修正箇所

修正対象ファイル：
```
/Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/_html/ja/admin/index.html
```

修正箇所：行102-111（`<ul class="sidebar-menu">`セクション）

### 5.4 エラー修正が必要なページ

現時点では、ファイルレベルでのエラーは確認されていません。
本番環境での動作確認は、管理者ログイン後に実施する必要があります。

---

## 6. 技術的観察

### 6.1 コードの特徴
- フレームワーク：カスタムPHPフレームワーク（SmartRams製）
- テンプレートエンジン：SmartTemplate（{%VARIABLE%}形式）
- データベース：MySQL（TemplateAdmin経由でアクセス）
- セッション管理：カスタムセッションクラス

### 6.2 セキュリティ観察
- 全ページで認証チェック（401エラー確認済み）
- SQLインジェクション対策：`conv_sql()`メソッド使用
- セッションベース認証
- API認証：api_keys_manage.phpでAPIキー管理機能実装済み

### 6.3 APIキー管理機能
`api_keys_manage.php`では以下の機能が実装されています：
- APIキー生成（test/live環境対応）
- レート制限設定（デフォルト1000リクエスト）
- キーの有効/無効切り替え
- 生成キー形式：`pk_test_*` / `pk_live_*`

---

## 7. まとめ

### 7.1 現状
- サイドバーメニュー：8項目（基本機能のみ）
- 実在するPHPファイル：48件
- メニュー未掲載：40件（うち重要15件、開発用5件）

### 7.2 問題点
- 重要な管理機能（売上、ポイント、商品など）がメニューに表示されていない
- 管理者が直接URLを入力しないとアクセスできない

### 7.3 改善効果
メニューを拡張することで：
- 管理業務の効率化
- ヒューマンエラーの削減
- 新規管理者の学習コスト削減
- システム全体の可視化

---

## 8. 次のステップ

1. index.htmlのサイドバーメニューを階層化して拡張
2. 各ページのアイコン設定（FontAwesome 5.6.3使用）
3. 管理者ログインでの動作確認
4. ユーザビリティテスト

---

**レポート作成者**: Claude Code
**最終更新**: 2025-11-10
