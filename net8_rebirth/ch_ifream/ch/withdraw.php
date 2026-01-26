<?php
/*
 * withdraw.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * 出金申請画面
 *
 * ユーザーがポイントを出金申請する画面
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

        // ポイントセッション再取得
        $PPOINT = new PlayPoint($template->DB, false);
        $PPOINT->pointReSession();

        // モード判定
        $mode = isset($_GET['M']) ? $_GET['M'] : 'input';

        switch ($mode) {
            case 'input':
                DispInput($template);
                break;
            case 'conf':
                DispConf($template);
                break;
            case 'proc':
                ProcWithdraw($template);
                break;
            case 'end':
                DispEnd($template);
                break;
            default:
                DispInput($template);
        }

    } catch (Exception $e) {
        $template->dispProcError($e->getMessage());
    }
}

/**
 * 入力画面表示
 */
function DispInput($template) {
    // 現在のポイント取得
    $member_no = $template->Session->UserInfo['member_no'];

    $sql = "SELECT point FROM mst_member WHERE member_no = ?";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute([$member_no]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    $current_point = $member ? $member['point'] : 0;

    // 出金可能ポイントのプリセット
    $withdraw_presets = [
        ['point' => 1000, 'enabled' => $current_point >= 1000],
        ['point' => 5000, 'enabled' => $current_point >= 5000],
        ['point' => 10000, 'enabled' => $current_point >= 10000],
        ['point' => 30000, 'enabled' => $current_point >= 30000],
        ['point' => 50000, 'enabled' => $current_point >= 50000],
    ];

    // テンプレート変数設定
    $template->assignCommon();
    $template->assign("CURRENT_POINT", number_format($current_point), true);
    $template->assign("WITHDRAW_PRESETS", json_encode($withdraw_presets), true);

    // プリセットHTMLの生成
    $preset_html = '';
    foreach ($withdraw_presets as $preset) {
        $disabled = $preset['enabled'] ? '' : 'disabled';
        $disabled_class = $preset['enabled'] ? '' : 'preset-disabled';
        $point_formatted = number_format($preset['point']);

        $preset_html .= <<<HTML
        <div class="withdraw-preset-card {$disabled_class}">
            <button type="button" class="btn-withdraw-preset" data-point="{$preset['point']}" {$disabled}>
                <div class="preset-point">{$point_formatted}</div>
                <div class="preset-label">ポイント</div>
            </button>
        </div>
HTML;
    }
    $template->assign("PRESET_HTML", $preset_html, true);

    $template->flush(DIR_HTML . PRE_HTML . ".html");
}

/**
 * 確認画面表示
 */
function DispConf($template) {
    // POSTデータ取得
    $point = isset($_POST['point']) ? intval($_POST['point']) : 0;
    $bank_name = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $branch_name = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
    $account_type = isset($_POST['account_type']) ? trim($_POST['account_type']) : '';
    $account_number = isset($_POST['account_number']) ? trim($_POST['account_number']) : '';
    $account_holder = isset($_POST['account_holder']) ? trim($_POST['account_holder']) : '';

    // バリデーション
    $errors = [];

    if ($point <= 0) {
        $errors[] = "出金ポイント数を指定してください";
    }

    // 現在のポイント確認
    $member_no = $template->Session->UserInfo['member_no'];
    $sql = "SELECT point FROM mst_member WHERE member_no = ?";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute([$member_no]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_point = $member ? $member['point'] : 0;

    if ($point > $current_point) {
        $errors[] = "出金ポイント数が保有ポイントを超えています";
    }

    if (empty($bank_name)) {
        $errors[] = "銀行名を入力してください";
    }

    if (empty($branch_name)) {
        $errors[] = "支店名を入力してください";
    }

    if (empty($account_type)) {
        $errors[] = "口座種別を選択してください";
    }

    if (empty($account_number)) {
        $errors[] = "口座番号を入力してください";
    }

    if (empty($account_holder)) {
        $errors[] = "口座名義を入力してください";
    }

    // エラーがあれば入力画面に戻る
    if (!empty($errors)) {
        $_SESSION['withdraw_errors'] = $errors;
        header('Location: withdraw.php?M=input');
        exit;
    }

    // セッションに保存
    $_SESSION['withdraw_data'] = [
        'point' => $point,
        'bank_name' => $bank_name,
        'branch_name' => $branch_name,
        'account_type' => $account_type,
        'account_number' => $account_number,
        'account_holder' => $account_holder
    ];

    // 金額計算（1ポイント = 1円）
    $amount = $point;

    // テンプレート変数設定
    $template->assignCommon();
    $template->assign("WITHDRAW_POINT", number_format($point), true);
    $template->assign("WITHDRAW_AMOUNT", number_format($amount), true);
    $template->assign("BANK_NAME", h($bank_name), true);
    $template->assign("BRANCH_NAME", h($branch_name), true);
    $template->assign("ACCOUNT_TYPE", h($account_type), true);
    $template->assign("ACCOUNT_NUMBER", h($account_number), true);
    $template->assign("ACCOUNT_HOLDER", h($account_holder), true);

    $template->flush(DIR_HTML . PRE_HTML . "_conf.html");
}

/**
 * 出金申請処理
 */
function ProcWithdraw($template) {
    // セッションデータ取得
    if (!isset($_SESSION['withdraw_data'])) {
        header('Location: withdraw.php?M=input');
        exit;
    }

    $data = $_SESSION['withdraw_data'];
    $member_no = $template->Session->UserInfo['member_no'];

    try {
        $template->DB->beginTransaction();

        // 現在のポイント確認（再度チェック）
        $sql = "SELECT point FROM mst_member WHERE member_no = ? FOR UPDATE";
        $stmt = $template->DB->prepare($sql);
        $stmt->execute([$member_no]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_point = $member ? $member['point'] : 0;

        if ($data['point'] > $current_point) {
            throw new Exception("保有ポイントが不足しています");
        }

        // 金額計算（1ポイント = 1円）
        $amount = $data['point'];

        // 出金申請レコード作成
        $sql = "INSERT INTO his_withdrawal (
            member_no, request_dt, point, amount, status,
            bank_name, branch_name, account_type, account_number, account_holder,
            add_no, add_dt
        ) VALUES (?, NOW(), ?, ?, 0, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $template->DB->prepare($sql);
        $stmt->execute([
            $member_no,
            $data['point'],
            $amount,
            $data['bank_name'],
            $data['branch_name'],
            $data['account_type'],
            $data['account_number'],
            $data['account_holder'],
            $member_no
        ]);

        $withdrawal_no = $template->DB->lastInsertId();

        // ポイント減算（PlayPointクラス使用）
        $PPOINT = new PlayPoint($template->DB, false);
        $result = $PPOINT->usePoint(
            $member_no,
            $data['point'],
            '05', // proc_cd: 景品交換
            $withdrawal_no,
            '出金申請',
            $member_no
        );

        if (!$result) {
            throw new Exception("ポイント減算に失敗しました");
        }

        $template->DB->commit();

        // セッションデータをクリア
        $_SESSION['withdrawal_no'] = $withdrawal_no;
        unset($_SESSION['withdraw_data']);

        // 完了画面へリダイレクト
        header('Location: withdraw.php?M=end');
        exit;

    } catch (Exception $e) {
        $template->DB->rollBack();
        throw $e;
    }
}

/**
 * 完了画面表示
 */
function DispEnd($template) {
    if (!isset($_SESSION['withdrawal_no'])) {
        header('Location: withdraw.php?M=input');
        exit;
    }

    $withdrawal_no = $_SESSION['withdrawal_no'];

    // 申請情報取得
    $sql = "SELECT * FROM his_withdrawal WHERE withdrawal_no = ?";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute([$withdrawal_no]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$withdrawal) {
        header('Location: withdraw.php?M=input');
        exit;
    }

    // テンプレート変数設定
    $template->assignCommon();
    $template->assign("WITHDRAWAL_NO", $withdrawal_no, true);
    $template->assign("WITHDRAW_POINT", number_format($withdrawal['point']), true);
    $template->assign("WITHDRAW_AMOUNT", number_format($withdrawal['amount']), true);
    $template->assign("REQUEST_DT", date('Y年m月d日 H:i', strtotime($withdrawal['request_dt'])), true);

    // セッションクリア
    unset($_SESSION['withdrawal_no']);

    $template->flush(DIR_HTML . PRE_HTML . "_end.html");
}
