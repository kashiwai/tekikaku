<?php
/**
 * NET8 SDK API - Credit Conversion Endpoint
 * play_embed専用：ポイント→クレジット変換
 *
 * Version: 1.0.0
 * Created: 2025-12-18
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 設定ファイル読み込み
require_once('../../_etc/require_files.php');

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

$sessionId = $input['sessionId'] ?? null;
$memberNo = $input['memberNo'] ?? null;
$amount = isset($input['amount']) ? (int)$input['amount'] : 0; // 変換するクレジット量
$convertAll = $input['convertAll'] ?? false; // 全額変換フラグ

// 必須パラメータチェック
if (!$sessionId && !$memberNo) {
    http_response_code(400);
    echo json_encode([
        'error' => 'MISSING_PARAMS',
        'message' => 'sessionId or memberNo is required'
    ]);
    exit;
}

try {
    $pdo = get_db_connection();

    // sessionIdからmember_noを取得（sessionIdが提供された場合）
    if ($sessionId && !$memberNo) {
        $stmt = $pdo->prepare("
            SELECT member_no, machine_no
            FROM game_sessions
            WHERE session_id = :session_id
            AND status IN ('active', 'playing', 'pending')
        ");
        $stmt->execute(['session_id' => $sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            http_response_code(404);
            echo json_encode([
                'error' => 'SESSION_NOT_FOUND',
                'message' => 'Game session not found or expired'
            ]);
            exit;
        }

        $memberNo = $session['member_no'];
    }

    if (!$memberNo) {
        http_response_code(400);
        echo json_encode([
            'error' => 'MEMBER_NOT_FOUND',
            'message' => 'Could not determine member_no'
        ]);
        exit;
    }

    // 変換レートを取得（dat_machine → mst_convertPoint）
    $convRate = ['point' => 5, 'credit' => 1]; // デフォルト: 5ポイント = 1クレジット

    if (isset($session['machine_no'])) {
        $rateStmt = $pdo->prepare("
            SELECT cp.point, cp.credit
            FROM dat_machine dm
            JOIN mst_convertPoint cp ON dm.convert_no = cp.convert_no
            WHERE dm.machine_no = :machine_no
        ");
        $rateStmt->execute(['machine_no' => $session['machine_no']]);
        $rateData = $rateStmt->fetch(PDO::FETCH_ASSOC);
        if ($rateData) {
            $convRate = $rateData;
        }
    }

    error_log("💱 Credit conversion: member_no={$memberNo}, rate={$convRate['point']}pt={$convRate['credit']}cr");

    // トランザクション開始
    $pdo->beginTransaction();

    try {
        // 現在のポイント残高を取得（FOR UPDATE でロック）
        $stmt = $pdo->prepare("
            SELECT point FROM mst_member
            WHERE member_no = :member_no
            FOR UPDATE
        ");
        $stmt->execute(['member_no' => $memberNo]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode([
                'error' => 'MEMBER_NOT_FOUND',
                'message' => 'Member not found'
            ]);
            exit;
        }

        $currentPoint = (int)$member['point'];
        error_log("💰 Current points: member_no={$memberNo}, point={$currentPoint}");

        // 変換に必要なポイントを計算
        if ($convertAll) {
            // 全額変換: 現在のポイントで変換できる最大クレジット
            $creditToAdd = floor($currentPoint / $convRate['point']) * $convRate['credit'];
            $pointsToConsume = floor($currentPoint / $convRate['point']) * $convRate['point'];
        } else {
            // 指定額変換
            $pointsToConsume = ($amount / $convRate['credit']) * $convRate['point'];
            $creditToAdd = $amount;
        }

        // 残高チェック
        if ($currentPoint < $pointsToConsume) {
            $pdo->rollBack();

            // 変換可能な最大クレジットを計算
            $maxCredit = floor($currentPoint / $convRate['point']) * $convRate['credit'];

            http_response_code(400);
            echo json_encode([
                'error' => 'INSUFFICIENT_BALANCE',
                'message' => '残高が不足しています',
                'currentPoint' => $currentPoint,
                'required' => $pointsToConsume,
                'maxConvertible' => $maxCredit,
                'conversionRate' => $convRate
            ]);
            exit;
        }

        // ポイントを減算
        $newPoint = $currentPoint - $pointsToConsume;
        $stmt = $pdo->prepare("
            UPDATE mst_member
            SET point = :new_point
            WHERE member_no = :member_no
        ");
        $stmt->execute([
            'new_point' => $newPoint,
            'member_no' => $memberNo
        ]);

        // トランザクションコミット
        $pdo->commit();

        error_log("✅ Credit conversion success: member_no={$memberNo}, consumed={$pointsToConsume}pt, credit={$creditToAdd}cr, remaining={$newPoint}pt");

        // 成功レスポンス
        echo json_encode([
            'success' => true,
            'memberNo' => $memberNo,
            'pointsConsumed' => $pointsToConsume,
            'creditAdded' => $creditToAdd,
            'previousBalance' => $currentPoint,
            'newBalance' => $newPoint,
            'conversionRate' => $convRate
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("❌ Credit conversion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to convert credit: ' . $e->getMessage()
    ]);
}
