-- =====================================================
-- NET8 出金申請テーブル
-- =====================================================
-- 作成日: 2025-12-31
-- 説明: ユーザーのポイント出金申請を管理するテーブル
-- =====================================================

CREATE TABLE IF NOT EXISTS `his_withdrawal` (
  `withdrawal_no` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '出金申請番号',
  `member_no` int(10) unsigned NOT NULL COMMENT '会員番号',
  `request_dt` datetime NOT NULL COMMENT '申請日時',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '出金ポイント数',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '出金金額（円）',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0:申請中, 1:承認済, 2:却下, 9:送金完了',
  `bank_name` varchar(100) DEFAULT NULL COMMENT '銀行名',
  `branch_name` varchar(100) DEFAULT NULL COMMENT '支店名',
  `account_type` varchar(20) DEFAULT NULL COMMENT '口座種別（普通/当座）',
  `account_number` varchar(50) DEFAULT NULL COMMENT '口座番号',
  `account_holder` varchar(100) DEFAULT NULL COMMENT '口座名義',
  `reject_reason` text DEFAULT NULL COMMENT '却下理由',
  `admin_memo` text DEFAULT NULL COMMENT '管理者メモ',
  `process_dt` datetime DEFAULT NULL COMMENT '処理日時（承認/却下/送金完了）',
  `process_admin_no` int(10) unsigned DEFAULT NULL COMMENT '処理した管理者番号',
  `add_no` int(10) unsigned DEFAULT NULL COMMENT '登録者番号',
  `add_dt` datetime DEFAULT NULL COMMENT '登録日時',
  `upd_no` int(10) unsigned DEFAULT NULL COMMENT '更新者番号',
  `upd_dt` datetime DEFAULT NULL COMMENT '更新日時',
  PRIMARY KEY (`withdrawal_no`),
  KEY `idx_member_no` (`member_no`),
  KEY `idx_status` (`status`),
  KEY `idx_request_dt` (`request_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='出金申請履歴';

-- =====================================================
-- インデックス説明
-- =====================================================
-- idx_member_no: 会員別の出金履歴検索用
-- idx_status: ステータス別の検索用（管理画面で申請中のみ表示等）
-- idx_request_dt: 日付範囲検索用
-- =====================================================
