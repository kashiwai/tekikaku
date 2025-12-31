<?php
/*
 * install_withdrawal.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * 出金システムインストーラー
 *
 * his_withdrawalテーブルを作成する管理画面
 *
 * @package
 * @author   Claude Code
 * @version  1.0
 * @since    2025/12/31 初版作成
 */

// インクルード
require_once('../../_etc/require_files.php');
define("PRE_HTML", basename(get_self(), ".php"));

// メイン処理
main();

/**
 * メイン処理
 */
function main() {
    try {
        // 管理者認証
        $template = new TemplateUser(true);
        $template->checkSessionUser(true, true);

        // モード判定
        $mode = isset($_GET['M']) ? $_GET['M'] : 'check';

        switch ($mode) {
            case 'check':
                DispCheck($template);
                break;
            case 'install':
                ProcInstall($template);
                break;
            default:
                DispCheck($template);
        }

    } catch (Exception $e) {
        echo '<!DOCTYPE html>';
        echo '<html><head><meta charset="UTF-8"><title>エラー</title></head><body>';
        echo '<h1>エラー</h1>';
        echo '<p>' . h($e->getMessage()) . '</p>';
        echo '<p><a href="install_withdrawal.php">戻る</a></p>';
        echo '</body></html>';
    }
}

/**
 * チェック画面表示
 */
function DispCheck($template) {
    // テーブル存在チェック
    $sql = "SHOW TABLES LIKE 'his_withdrawal'";
    $stmt = $template->DB->query($sql);
    $exists = $stmt->rowCount() > 0;

    $table_status = '';
    $install_button = '';

    if ($exists) {
        // テーブル構造確認
        $sql = "SHOW CREATE TABLE his_withdrawal";
        $stmt = $template->DB->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $table_status = '<div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;">';
        $table_status .= '<h2 style="color: #155724; margin-top: 0;">✅ テーブルは既に存在します</h2>';
        $table_status .= '<p style="color: #155724;">his_withdrawalテーブルは既にデータベースに存在しています。</p>';
        $table_status .= '<details style="margin-top: 15px;">';
        $table_status .= '<summary style="cursor: pointer; color: #155724; font-weight: bold;">テーブル構造を表示</summary>';
        $table_status .= '<pre style="background: #fff; padding: 15px; overflow: auto; margin-top: 10px;">' . h($result['Create Table']) . '</pre>';
        $table_status .= '</details>';
        $table_status .= '</div>';

        // レコード数確認
        $sql = "SELECT COUNT(*) as cnt FROM his_withdrawal";
        $stmt = $template->DB->query($sql);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);

        $table_status .= '<div style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0;">';
        $table_status .= '<p style="margin: 0;"><strong>現在のレコード数:</strong> ' . number_format($count['cnt']) . ' 件</p>';
        $table_status .= '</div>';

    } else {
        $table_status = '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;">';
        $table_status .= '<h2 style="color: #721c24; margin-top: 0;">⚠️ テーブルが存在しません</h2>';
        $table_status .= '<p style="color: #721c24;">his_withdrawalテーブルがデータベースに存在しません。</p>';
        $table_status .= '<p style="color: #721c24;">出金機能を使用するには、テーブルを作成する必要があります。</p>';
        $table_status .= '</div>';

        $install_button = '<form method="POST" action="install_withdrawal.php?M=install" style="margin: 30px 0;">';
        $install_button .= '<button type="submit" onclick="return confirm(\'his_withdrawalテーブルを作成します。よろしいですか？\')" ';
        $install_button .= 'style="padding: 15px 30px; background: #007bff; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer;">';
        $install_button .= '📦 テーブルを作成する';
        $install_button .= '</button>';
        $install_button .= '</form>';
    }

    // HTML出力
    echo '<!DOCTYPE html>';
    echo '<html lang="ja">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>出金システムインストーラー</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }';
    echo '.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
    echo 'h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }';
    echo 'a { color: #007bff; text-decoration: none; }';
    echo 'a:hover { text-decoration: underline; }';
    echo '.back-link { display: inline-block; margin-top: 30px; padding: 10px 20px; background: #6c757d; color: white; border-radius: 5px; }';
    echo '.back-link:hover { background: #5a6268; text-decoration: none; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="container">';
    echo '<h1>💰 出金システムインストーラー</h1>';

    echo $table_status;
    echo $install_button;

    echo '<div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff;">';
    echo '<h3 style="margin-top: 0;">📝 このインストーラーについて</h3>';
    echo '<p>このページは出金機能に必要なデータベーステーブル（his_withdrawal）を作成します。</p>';
    echo '<ul>';
    echo '<li>テーブル名: <code>his_withdrawal</code></li>';
    echo '<li>機能: ユーザーの出金申請を管理</li>';
    echo '<li>関連ページ: withdraw.php, withdrawal_history.php, withdrawal_admin.php</li>';
    echo '</ul>';
    echo '</div>';

    echo '<a href="withdrawal_admin.php" class="back-link">出金管理画面へ</a>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}

/**
 * インストール実行
 */
function ProcInstall($template) {
    try {
        // テーブル存在チェック
        $sql = "SHOW TABLES LIKE 'his_withdrawal'";
        $stmt = $template->DB->query($sql);
        if ($stmt->rowCount() > 0) {
            throw new Exception("テーブルは既に存在します");
        }

        // テーブル作成SQL
        $create_sql = "
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='出金申請履歴'
        ";

        // テーブル作成実行
        $template->DB->exec($create_sql);

        // 成功メッセージ
        echo '<!DOCTYPE html>';
        echo '<html lang="ja">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>インストール完了</title>';
        echo '<style>';
        echo 'body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }';
        echo '.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
        echo 'h1 { color: #155724; border-bottom: 3px solid #28a745; padding-bottom: 10px; }';
        echo '.success { padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0; }';
        echo 'a { display: inline-block; margin: 10px 10px 10px 0; padding: 12px 24px; background: #007bff; color: white; border-radius: 5px; text-decoration: none; }';
        echo 'a:hover { background: #0056b3; }';
        echo '.secondary { background: #6c757d; }';
        echo '.secondary:hover { background: #5a6268; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<div class="container">';
        echo '<h1>✅ インストール完了</h1>';
        echo '<div class="success">';
        echo '<p style="font-size: 18px; margin: 0;"><strong>his_withdrawalテーブルの作成が完了しました！</strong></p>';
        echo '</div>';
        echo '<p>出金機能が使用可能になりました。以下のページから機能を確認できます：</p>';
        echo '<div style="margin: 30px 0;">';
        echo '<a href="withdrawal_admin.php">📋 出金管理画面</a>';
        echo '<a href="../withdraw.php" class="secondary">💳 出金申請（ユーザー画面）</a>';
        echo '<a href="install_withdrawal.php" class="secondary">🔍 テーブル確認</a>';
        echo '</div>';
        echo '</div>';
        echo '</body>';
        echo '</html>';

    } catch (Exception $e) {
        throw new Exception("テーブル作成エラー: " . $e->getMessage());
    }
}
