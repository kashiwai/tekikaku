<?php
/**
 * SDKセッションデバッグAPI
 * game_sessionsとmst_memberの内容を確認
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once('../../_etc/require_files.php');

try {
    // データベース接続（get_db_connection関数を使用）
    $pdo = get_db_connection();

    $machineNo = $_GET['machineNo'] ?? $_GET['NO'] ?? null;
    $memberNo = $_GET['memberNo'] ?? null;

    $result = [
        'success' => true,
        'machineNo' => $machineNo,
        'memberNo' => $memberNo,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // 1. game_sessionsテーブルを確認
    if ($machineNo) {
        $stmt = $pdo->prepare("
            SELECT
                session_id,
                user_id,
                member_no,
                partner_user_id,
                machine_no,
                model_cd,
                currency,
                status,
                points_consumed,
                started_at,
                ended_at
            FROM game_sessions
            WHERE machine_no = :machine_no
            ORDER BY started_at DESC
            LIMIT 5
        ");
        $stmt->execute(['machine_no' => $machineNo]);
        $result['game_sessions'] = $stmt->fetchAll();
        $result['game_sessions_count'] = count($result['game_sessions']);
    }

    // 2. mst_memberテーブルを確認
    if ($memberNo) {
        $stmt = $pdo->prepare("
            SELECT
                member_no,
                nickname,
                mail,
                point,
                currency,
                state,
                regist_dt,
                quit_dt
            FROM mst_member
            WHERE member_no = :member_no
        ");
        $stmt->execute(['member_no' => $memberNo]);
        $result['mst_member'] = $stmt->fetch();
    } elseif ($machineNo && !empty($result['game_sessions'])) {
        // machineNoからmember_noを取得して、mst_memberも確認
        $latestSession = $result['game_sessions'][0];
        if ($latestSession['member_no']) {
            $stmt = $pdo->prepare("
                SELECT
                    member_no,
                    nickname,
                    mail,
                    point,
                    currency,
                    state,
                    regist_dt,
                    quit_dt
                FROM mst_member
                WHERE member_no = :member_no
            ");
            $stmt->execute(['member_no' => $latestSession['member_no']]);
            $result['mst_member'] = $stmt->fetch();
        }
    }

    // 3. SDK経由のログイン判定をシミュレート
    if ($machineNo && !empty($result['game_sessions'])) {
        $latestSession = $result['game_sessions'][0];

        $result['sdk_login_check'] = [
            'has_session' => !empty($latestSession),
            'has_member_no' => !empty($latestSession['member_no']),
            'status' => $latestSession['status'] ?? null,
            'status_valid' => in_array($latestSession['status'] ?? '', ['playing', 'pending']),
            'has_member_info' => !empty($result['mst_member']),
            'should_auto_login' => (
                !empty($latestSession) &&
                !empty($latestSession['member_no']) &&
                in_array($latestSession['status'] ?? '', ['playing', 'pending']) &&
                !empty($result['mst_member'])
            )
        ];

        if (!$result['sdk_login_check']['should_auto_login']) {
            $result['sdk_login_check']['failure_reason'] = [];

            if (empty($latestSession)) {
                $result['sdk_login_check']['failure_reason'][] = 'game_sessions record not found';
            }
            if (empty($latestSession['member_no'])) {
                $result['sdk_login_check']['failure_reason'][] = 'member_no is empty';
            }
            if (!in_array($latestSession['status'] ?? '', ['playing', 'pending'])) {
                $result['sdk_login_check']['failure_reason'][] = "status is '{$latestSession['status']}' (expected 'playing' or 'pending')";
            }
            if (empty($result['mst_member'])) {
                $result['sdk_login_check']['failure_reason'][] = 'mst_member record not found';
            }
        }
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
