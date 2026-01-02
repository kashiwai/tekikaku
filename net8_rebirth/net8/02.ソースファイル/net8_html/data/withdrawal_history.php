<?php
/*
 * withdrawal_history.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * 出金履歴画面
 *
 * ユーザーの出金申請履歴を表示する画面
 *
 * @package
 * @author   Claude Code
 * @version  1.0
 * @since    2025/12/31 初版作成
 */

// インクルード
require_once('../_etc/require_files.php');
define("PRE_HTML", basename(get_self(), ".php"));

// メイン処理
main();

/**
 * メイン処理
 */
function main() {
    try {
        // ユーザー認証
        $template = new TemplateUser(false);
        $template->checkSessionUser(true, true);

        // 実処理
        DispList($template);

    } catch (Exception $e) {
        $template->dispProcError($e->getMessage());
    }
}

/**
 * 一覧画面表示
 */
function DispList($template) {
    $member_no = $template->Session->UserInfo['member_no'];

    // ページング設定
    $page = isset($_GET['P']) && is_numeric($_GET['P']) && $_GET['P'] > 0 ? intval($_GET['P']) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // ステータスフィルター
    $status_filter = isset($_GET['STATUS']) && in_array($_GET['STATUS'], ['0', '1', '2', '9']) ? $_GET['STATUS'] : '';

    // 総件数取得
    $count_sql = "SELECT COUNT(*) as cnt FROM his_withdrawal WHERE member_no = ?";
    $count_params = [$member_no];

    if ($status_filter !== '') {
        $count_sql .= " AND status = ?";
        $count_params[] = $status_filter;
    }

    $stmt = $template->DB->prepare($count_sql);
    $stmt->execute($count_params);
    $count_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_count = $count_result['cnt'];

    // 出金履歴取得
    $sql = "SELECT
                withdrawal_no,
                request_dt,
                point,
                amount,
                status,
                bank_name,
                account_number,
                reject_reason,
                process_dt
            FROM his_withdrawal
            WHERE member_no = ?";

    $params = [$member_no];

    if ($status_filter !== '') {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }

    $sql .= " ORDER BY request_dt DESC LIMIT {$per_page} OFFSET {$offset}";

    $stmt = $template->DB->prepare($sql);
    $stmt->execute($params);
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ステータス表示名
    $status_labels = [
        '0' => '申請中',
        '1' => '承認済',
        '2' => '却下',
        '9' => '送金完了'
    ];

    $status_colors = [
        '0' => '#ffd700',  // 申請中：ゴールド
        '1' => '#00ff00',  // 承認済：緑
        '2' => '#ff0000',  // 却下：赤
        '9' => '#00ffff'   // 送金完了：シアン
    ];

    // HTML生成
    $withdrawal_html = '';
    if (count($withdrawals) > 0) {
        foreach ($withdrawals as $w) {
            $status_label = $status_labels[$w['status']] ?? '不明';
            $status_color = $status_colors[$w['status']] ?? '#fff';
            $request_date = date('Y/m/d H:i', strtotime($w['request_dt']));
            $process_date = $w['process_dt'] ? date('Y/m/d H:i', strtotime($w['process_dt'])) : '-';
            $point_formatted = number_format($w['point']);
            $amount_formatted = number_format($w['amount']);

            $reject_reason_html = '';
            if ($w['status'] == '2' && $w['reject_reason']) {
                $reject_reason_html = '<div class="reject-reason">却下理由: ' . h($w['reject_reason']) . '</div>';
            }

            $withdrawal_html .= <<<HTML
            <div class="withdrawal-card">
                <div class="withdrawal-header">
                    <span class="withdrawal-no">申請番号: #{$w['withdrawal_no']}</span>
                    <span class="withdrawal-status" style="color: {$status_color};">{$status_label}</span>
                </div>
                <div class="withdrawal-body">
                    <div class="withdrawal-row">
                        <span class="label">申請日時</span>
                        <span class="value">{$request_date}</span>
                    </div>
                    <div class="withdrawal-row">
                        <span class="label">出金ポイント</span>
                        <span class="value highlight">{$point_formatted} pt</span>
                    </div>
                    <div class="withdrawal-row">
                        <span class="label">出金金額</span>
                        <span class="value highlight">¥ {$amount_formatted}</span>
                    </div>
                    <div class="withdrawal-row">
                        <span class="label">振込先</span>
                        <span class="value">{$w['bank_name']} ****{$w['account_number']}</span>
                    </div>
                    <div class="withdrawal-row">
                        <span class="label">処理日時</span>
                        <span class="value">{$process_date}</span>
                    </div>
                    {$reject_reason_html}
                </div>
            </div>
HTML;
        }
    } else {
        $withdrawal_html = '<div class="no-data">出金履歴がありません</div>';
    }

    // ページネーション
    $total_pages = ceil($total_count / $per_page);
    $pagination_html = '';

    if ($total_pages > 1) {
        $pagination_html .= '<div class="pagination">';

        // 前へ
        if ($page > 1) {
            $prev_page = $page - 1;
            $status_param = $status_filter !== '' ? "&STATUS={$status_filter}" : '';
            $pagination_html .= "<a href=\"withdrawal_history.php?P={$prev_page}{$status_param}\" class=\"page-link\">« 前へ</a>";
        }

        // ページ番号
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = $i == $page ? 'active' : '';
            $status_param = $status_filter !== '' ? "&STATUS={$status_filter}" : '';
            $pagination_html .= "<a href=\"withdrawal_history.php?P={$i}{$status_param}\" class=\"page-link {$active_class}\">{$i}</a>";
        }

        // 次へ
        if ($page < $total_pages) {
            $next_page = $page + 1;
            $status_param = $status_filter !== '' ? "&STATUS={$status_filter}" : '';
            $pagination_html .= "<a href=\"withdrawal_history.php?P={$next_page}{$status_param}\" class=\"page-link\">次へ »</a>";
        }

        $pagination_html .= '</div>';
    }

    // テンプレート変数設定
    $template->assignCommon();
    $template->assign("WITHDRAWAL_HTML", $withdrawal_html, true);
    $template->assign("PAGINATION_HTML", $pagination_html, true);
    $template->assign("TOTAL_COUNT", $total_count, true);
    $template->assign("STATUS_FILTER", $status_filter, true);

    $template->flush(DIR_HTML . PRE_HTML . ".html");
}
