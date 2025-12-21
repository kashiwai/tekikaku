<?php
/**
 * Agent Result API
 * POST /api/agent/result
 *
 * エージェントがコマンド実行結果を返す
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/auth.php';

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 認証
$auth = requireAuth(['agent', 'admin', 'ai']);

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

$agentId = $input['agentId'] ?? '';
$commandId = intval($input['commandId'] ?? 0);
$output = $input['output'] ?? '';
$exitCode = isset($input['exitCode']) ? intval($input['exitCode']) : null;
$executionTimeMs = isset($input['executionTimeMs']) ? intval($input['executionTimeMs']) : null;

if (empty($agentId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing agentId']);
    exit;
}

// エージェント情報更新
ensureAgent($agentId);

// 追加情報があれば同期
if (!empty($input['ip']) || !empty($input['mac'])) {
    syncWithMachine($agentId, [
        'ip' => $input['ip'] ?? null,
        'mac' => $input['mac'] ?? null,
        'hostname' => $input['hostname'] ?? null
    ]);
}

$pdo->beginTransaction();

try {
    // commandIdが指定されている場合
    if ($commandId > 0) {
        // command_queue の状態を更新
        $sql = "UPDATE command_queue
                SET status = 'done', completed_at = NOW()
                WHERE id = :id AND agent_id = :agent_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $commandId, ':agent_id' => $agentId]);

        // 結果を保存
        $sql = "INSERT INTO command_results
                (command_id, agent_id, output, exit_code, execution_time_ms)
                VALUES (:cmd_id, :agent_id, :output, :exit_code, :exec_time)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cmd_id' => $commandId,
            ':agent_id' => $agentId,
            ':output' => $output,
            ':exit_code' => $exitCode,
            ':exec_time' => $executionTimeMs
        ]);

        $resultId = $pdo->lastInsertId();
    } else {
        // commandIdなしの場合（自発的な報告）
        // 最新のsentコマンドを探して完了にする
        $sql = "SELECT id FROM command_queue
                WHERE agent_id = :agent_id AND status = 'sent'
                ORDER BY sent_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':agent_id' => $agentId]);
        $latestCmd = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($latestCmd) {
            $commandId = $latestCmd['id'];

            $sql = "UPDATE command_queue SET status = 'done', completed_at = NOW() WHERE id = :id";
            $pdo->prepare($sql)->execute([':id' => $commandId]);
        }

        // 結果を保存
        $sql = "INSERT INTO command_results
                (command_id, agent_id, output, exit_code, execution_time_ms)
                VALUES (:cmd_id, :agent_id, :output, :exit_code, :exec_time)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cmd_id' => $commandId ?: null,
            ':agent_id' => $agentId,
            ':output' => $output,
            ':exit_code' => $exitCode,
            ':exec_time' => $executionTimeMs
        ]);

        $resultId = $pdo->lastInsertId();
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'resultId' => $resultId,
        'commandId' => $commandId,
        'message' => 'Result saved successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save result', 'message' => $e->getMessage()]);
}
