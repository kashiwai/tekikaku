<?php
/**
 * NET8 SDK API - Machine & Model Diagnostic Endpoint
 * Version: 1.0.0
 * Created: 2025-01-27
 *
 * データベースのマシン・モデル登録状況を確認する診断用エンドポイント
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 既存の設定ファイル読み込み
require_once('../../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    $diagnostics = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'database' => [
            'host' => DB_HOST ?? 'unknown',
            'name' => DB_NAME ?? 'unknown',
            'connected' => true
        ]
    ];

    // 1. モデル情報を取得
    $modelStmt = $pdo->query("
        SELECT
            model_no,
            model_cd,
            model_name,
            category,
            del_flg
        FROM mst_model
        WHERE del_flg = 0
        ORDER BY model_cd
        LIMIT 20
    ");
    $diagnostics['models'] = $modelStmt->fetchAll(PDO::FETCH_ASSOC);
    $diagnostics['models_count'] = count($diagnostics['models']);

    // 2. KAIJI01モデルの確認
    $kaiji01Stmt = $pdo->prepare("
        SELECT model_no, model_cd, model_name, category
        FROM mst_model
        WHERE model_cd = 'KAIJI01'
        AND del_flg = 0
    ");
    $kaiji01Stmt->execute();
    $kaiji01Model = $kaiji01Stmt->fetch(PDO::FETCH_ASSOC);
    $diagnostics['kaiji01_model'] = $kaiji01Model ?: null;

    // 3. マシン情報を取得
    $machineStmt = $pdo->query("
        SELECT
            m.machine_no,
            m.model_no,
            mm.model_cd,
            mm.model_name,
            m.camera_no,
            mc.camera_name,
            m.signaling_id,
            m.machine_status,
            m.del_flg,
            m.end_date,
            lm.assign_flg,
            lm.member_no as assigned_member
        FROM dat_machine m
        LEFT JOIN mst_model mm ON m.model_no = mm.model_no
        LEFT JOIN mst_camera mc ON m.camera_no = mc.camera_no
        LEFT JOIN lnk_machine lm ON m.machine_no = lm.machine_no
        WHERE m.del_flg = 0
        ORDER BY m.machine_no
        LIMIT 20
    ");
    $diagnostics['machines'] = $machineStmt->fetchAll(PDO::FETCH_ASSOC);
    $diagnostics['machines_count'] = count($diagnostics['machines']);

    // 4. KAIJI01用のマシン確認
    if ($kaiji01Model) {
        $kaiji01MachineStmt = $pdo->prepare("
            SELECT
                m.machine_no,
                m.camera_no,
                mc.camera_name,
                m.signaling_id,
                m.machine_status,
                m.end_date,
                lm.assign_flg,
                CASE
                    WHEN m.del_flg = 1 THEN 'deleted'
                    WHEN m.end_date < CURDATE() THEN 'expired'
                    WHEN lm.assign_flg = 1 THEN 'assigned'
                    WHEN m.machine_status = 0 THEN 'stopped'
                    WHEN m.machine_status = 2 THEN 'maintenance'
                    WHEN m.machine_status = 1 THEN 'available'
                    ELSE 'unknown'
                END as status_text
            FROM dat_machine m
            LEFT JOIN mst_camera mc ON m.camera_no = mc.camera_no
            LEFT JOIN lnk_machine lm ON m.machine_no = lm.machine_no
            WHERE m.model_no = :model_no
            ORDER BY m.machine_no
        ");
        $kaiji01MachineStmt->execute(['model_no' => $kaiji01Model['model_no']]);
        $diagnostics['kaiji01_machines'] = $kaiji01MachineStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $diagnostics['kaiji01_machines'] = [];
    }

    // 5. 利用可能なマシン数を計算
    $availableStmt = $pdo->query("
        SELECT COUNT(*) as available_count
        FROM dat_machine m
        WHERE m.del_flg = 0
        AND m.end_date >= CURDATE()
        AND m.machine_status = 1
        AND NOT EXISTS (
            SELECT 1 FROM lnk_machine lm
            WHERE lm.machine_no = m.machine_no
            AND lm.assign_flg = 1
        )
    ");
    $availableData = $availableStmt->fetch(PDO::FETCH_ASSOC);
    $diagnostics['available_machines_count'] = (int)$availableData['available_count'];

    // 6. カメラ情報
    $cameraStmt = $pdo->query("
        SELECT
            camera_no,
            camera_name,
            camera_mac,
            created_at,
            updated_at
        FROM mst_camera
        ORDER BY camera_no
        LIMIT 20
    ");
    $diagnostics['cameras'] = $cameraStmt->fetchAll(PDO::FETCH_ASSOC);
    $diagnostics['cameras_count'] = count($diagnostics['cameras']);

    // 7. APIキー情報（パラメータがあれば）
    if (isset($_GET['checkApiKey'])) {
        $apiKeyValue = $_GET['checkApiKey'];
        $apiKeyStmt = $pdo->prepare("
            SELECT id, key_value, environment, is_active, created_at
            FROM api_keys
            WHERE key_value = :key_value
        ");
        $apiKeyStmt->execute(['key_value' => $apiKeyValue]);
        $diagnostics['api_key_check'] = $apiKeyStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // 8. 推奨事項
    $diagnostics['recommendations'] = [];

    if (!$kaiji01Model) {
        $diagnostics['recommendations'][] = [
            'type' => 'warning',
            'message' => 'KAIJI01モデルがmst_modelテーブルに存在しません。モデルを登録してください。'
        ];
    }

    if ($kaiji01Model && count($diagnostics['kaiji01_machines']) === 0) {
        $diagnostics['recommendations'][] = [
            'type' => 'warning',
            'message' => 'KAIJI01モデル用のマシンがdat_machineテーブルに存在しません。マシンを登録してください。'
        ];
    }

    if ($diagnostics['available_machines_count'] === 0) {
        $diagnostics['recommendations'][] = [
            'type' => 'error',
            'message' => '利用可能なマシンが0台です。マシンのステータスを確認してください。'
        ];
    }

    http_response_code(200);
    echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
