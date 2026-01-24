<?php
/*
 * withdrawal_admin.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * 出金申請管理画面
 *
 * 管理者が出金申請を承認・却下・送金完了処理する画面
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
        $mode = isset($_GET['M']) ? $_GET['M'] : 'list';

        switch ($mode) {
            case 'list':
                DispList($template);
                break;
            case 'approve':
                ProcApprove($template);
                break;
            case 'reject':
                ProcReject($template);
                break;
            case 'complete':
                ProcComplete($template);
                break;
            default:
                DispList($template);
        }

    } catch (Exception $e) {
        $template->dispProcError($e->getMessage());
    }
}

/**
 * 一覧画面表示
 */
function DispList($template) {
    // ページング設定
    $page = isset($_GET['P']) && is_numeric($_GET['P']) && $_GET['P'] > 0 ? intval($_GET['P']) : 1;
    $per_page = 50;
    $offset = ($page - 1) * $per_page;

    // ステータスフィルター
    $status_filter = isset($_GET['STATUS']) && in_array($_GET['STATUS'], ['0', '1', '2', '9']) ? $_GET['STATUS'] : '0';

    // 総件数取得
    $count_sql = "SELECT COUNT(*) as cnt FROM his_withdrawal WHERE status = ?";
    $stmt = $template->DB->prepare($count_sql);
    $stmt->execute([$status_filter]);
    $count_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_count = $count_result['cnt'];

    // 出金申請取得
    $sql = "SELECT
                w.withdrawal_no,
                w.member_no,
                w.request_dt,
                w.point,
                w.amount,
                w.status,
                w.bank_name,
                w.branch_name,
                w.account_type,
                w.account_number,
                w.account_holder,
                w.reject_reason,
                w.process_dt,
                m.disp_name,
                m.mail,
                m.point as member_point
            FROM his_withdrawal w
            INNER JOIN mst_member m ON w.member_no = m.member_no
            WHERE w.status = ?
            ORDER BY w.request_dt ASC
            LIMIT {$per_page} OFFSET {$offset}";

    $stmt = $template->DB->prepare($sql);
    $stmt->execute([$status_filter]);
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ステータス表示名
    $status_labels = [
        '0' => '申請中',
        '1' => '承認済',
        '2' => '却下',
        '9' => '送金完了'
    ];

    // テンプレート変数設定
    $template->assignCommon();
    $template->assign("STATUS_FILTER", $status_filter, true);
    $template->assign("TOTAL_COUNT", $total_count, true);
    $template->assign("WITHDRAWALS", json_encode($withdrawals), true);
    $template->assign("STATUS_LABELS", json_encode($status_labels), true);

    // HTML生成
    $withdrawal_html = '';
    if (count($withdrawals) > 0) {
        foreach ($withdrawals as $w) {
            $status_label = $status_labels[$w['status']];
            $request_date = date('Y/m/d H:i', strtotime($w['request_dt']));
            $point_formatted = number_format($w['point']);
            $amount_formatted = number_format($w['amount']);
            $member_point_formatted = number_format($w['member_point']);

            $action_buttons = '';
            if ($w['status'] == '0') {
                // 申請中：承認・却下ボタン表示
                $action_buttons = <<<HTML
                <button class="btn btn-approve" onclick="approveWithdrawal({$w['withdrawal_no']})">承認</button>
                <button class="btn btn-reject" onclick="rejectWithdrawal({$w['withdrawal_no']})">却下</button>
HTML;
            } elseif ($w['status'] == '1') {
                // 承認済：送金完了ボタン表示
                $action_buttons = <<<HTML
                <button class="btn btn-complete" onclick="completeWithdrawal({$w['withdrawal_no']})">送金完了</button>
HTML;
            }

            $withdrawal_html .= <<<HTML
            <tr>
                <td>#{$w['withdrawal_no']}</td>
                <td>{$request_date}</td>
                <td>{$w['disp_name']}<br><small>{$w['mail']}</small></td>
                <td class="text-right">{$point_formatted} pt<br><small>残高: {$member_point_formatted} pt</small></td>
                <td class="text-right">¥ {$amount_formatted}</td>
                <td>{$w['bank_name']}<br>{$w['branch_name']}<br>{$w['account_type']} {$w['account_number']}<br>{$w['account_holder']}</td>
                <td>{$status_label}</td>
                <td class="text-center">{$action_buttons}</td>
            </tr>
HTML;
        }
    } else {
        $withdrawal_html = '<tr><td colspan="8" class="text-center">該当するデータがありません</td></tr>';
    }

    $template->assign("WITHDRAWAL_HTML", $withdrawal_html, true);

    // ページネーション
    $total_pages = ceil($total_count / $per_page);
    $pagination_html = '';

    if ($total_pages > 1) {
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = $i == $page ? 'active' : '';
            $pagination_html .= "<a href=\"withdrawal_admin.php?STATUS={$status_filter}&P={$i}\" class=\"page-link {$active}\">{$i}</a>";
        }
    }

    $template->assign("PAGINATION_HTML", $pagination_html, true);

    $template->flush(DIR_HTML . PRE_HTML . ".html");
}

/**
 * 承認処理
 */
function ProcApprove($template) {
    $withdrawal_no = isset($_POST['withdrawal_no']) ? intval($_POST['withdrawal_no']) : 0;

    if ($withdrawal_no <= 0) {
        throw new Exception("不正なパラメータです");
    }

    try {
        $template->DB->beginTransaction();

        // 出金申請取得（FOR UPDATE）
        $sql = "SELECT * FROM his_withdrawal WHERE withdrawal_no = ? FOR UPDATE";
        $stmt = $template->DB->prepare($sql);
        $stmt->execute([$withdrawal_no]);
        $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$withdrawal) {
            throw new Exception("出金申請が見つかりません");
        }

        if ($withdrawal['status'] != '0') {
            throw new Exception("この申請は既に処理されています");
        }

        // ステータスを承認済(1)に更新
        $admin_no = $template->Session->UserInfo['admin_no'];
        $sql = "UPDATE his_withdrawal
                SET status = 1,
                    process_dt = NOW(),
                    process_admin_no = ?,
                    upd_no = ?,
                    upd_dt = NOW()
                WHERE withdrawal_no = ?";

        $stmt = $template->DB->prepare($sql);
        $stmt->execute([$admin_no, $admin_no, $withdrawal_no]);

        $template->DB->commit();

        // 一覧画面へリダイレクト
        header('Location: withdrawal_admin.php?STATUS=0&msg=approved');
        exit;

    } catch (Exception $e) {
        $template->DB->rollBack();
        throw $e;
    }
}

/**
 * 却下処理
 */
function ProcReject($template) {
    $withdrawal_no = isset($_POST['withdrawal_no']) ? intval($_POST['withdrawal_no']) : 0;
    $reject_reason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : '';

    if ($withdrawal_no <= 0) {
        throw new Exception("不正なパラメータです");
    }

    if (empty($reject_reason)) {
        throw new Exception("却下理由を入力してください");
    }

    try {
        $template->DB->beginTransaction();

        // 出金申請取得（FOR UPDATE）
        $sql = "SELECT * FROM his_withdrawal WHERE withdrawal_no = ? FOR UPDATE";
        $stmt = $template->DB->prepare($sql);
        $stmt->execute([$withdrawal_no]);
        $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$withdrawal) {
            throw new Exception("出金申請が見つかりません");
        }

        if ($withdrawal['status'] != '0') {
            throw new Exception("この申請は既に処理されています");
        }

        // ポイントを返却
        $PPOINT = new PlayPoint($template->DB, false);
        $PPOINT->addPoint(
            $withdrawal['member_no'],
            '91', // proc_cd: 管理者調整
            $withdrawal['point'],
            $withdrawal_no,
            '',
            '出金申請却下によるポイント返却',
            $template->Session->UserInfo['admin_no']
        );

        // ステータスを却下(2)に更新
        $admin_no = $template->Session->UserInfo['admin_no'];
        $sql = "UPDATE his_withdrawal
                SET status = 2,
                    reject_reason = ?,
                    process_dt = NOW(),
                    process_admin_no = ?,
                    upd_no = ?,
                    upd_dt = NOW()
                WHERE withdrawal_no = ?";

        $stmt = $template->DB->prepare($sql);
        $stmt->execute([$reject_reason, $admin_no, $admin_no, $withdrawal_no]);

        $template->DB->commit();

        // 一覧画面へリダイレクト
        header('Location: withdrawal_admin.php?STATUS=0&msg=rejected');
        exit;

    } catch (Exception $e) {
        $template->DB->rollBack();
        throw $e;
    }
}

/**
 * 送金完了処理
 */
function ProcComplete($template) {
    $withdrawal_no = isset($_POST['withdrawal_no']) ? intval($_POST['withdrawal_no']) : 0;

    if ($withdrawal_no <= 0) {
        throw new Exception("不正なパラメータです");
    }

    try {
        $template->DB->beginTransaction();

        // 出金申請取得（FOR UPDATE）
        $sql = "SELECT * FROM his_withdrawal WHERE withdrawal_no = ? FOR UPDATE";
        $stmt = $template->DB->prepare($sql);
        $stmt->execute([$withdrawal_no]);
        $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$withdrawal) {
            throw new Exception("出金申請が見つかりません");
        }

        if ($withdrawal['status'] != '1') {
            throw new Exception("この申請は承認済みではありません");
        }

        // ステータスを送金完了(9)に更新
        $admin_no = $template->Session->UserInfo['admin_no'];
        $sql = "UPDATE his_withdrawal
                SET status = 9,
                    process_dt = NOW(),
                    process_admin_no = ?,
                    upd_no = ?,
                    upd_dt = NOW()
                WHERE withdrawal_no = ?";

        $stmt = $template->DB->prepare($sql);
        $stmt->execute([$admin_no, $admin_no, $withdrawal_no]);

        $template->DB->commit();

        // 一覧画面へリダイレクト
        header('Location: withdrawal_admin.php?STATUS=1&msg=completed');
        exit;

    } catch (Exception $e) {
        $template->DB->rollBack();
        throw $e;
    }
}
