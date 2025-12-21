<?php
/**
 * Agent API 認証ミドルウェア
 * API_KEY認証とエージェント検証
 */

/**
 * APIキー認証
 * @return array|false 認証情報 or false
 */
function authenticateApiKey(): array|false
{
    // Authorizationヘッダーから取得
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        $apiKey = $matches[1];
    } else {
        // クエリパラメータからも許可（開発用）
        $apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
    }

    if (empty($apiKey)) {
        return false;
    }

    global $pdo;

    $sql = "SELECT id, name, role, is_active FROM api_keys WHERE api_key = :key";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':key' => $apiKey]);
    $keyInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$keyInfo || !$keyInfo['is_active']) {
        return false;
    }

    // 最終使用日時を更新
    $pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = :id")
        ->execute([':id' => $keyInfo['id']]);

    return $keyInfo;
}

/**
 * 認証必須チェック
 * @param array $allowedRoles 許可するロール
 */
function requireAuth(array $allowedRoles = ['agent', 'admin', 'ai']): array
{
    $auth = authenticateApiKey();

    if (!$auth) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid or missing API key']);
        exit;
    }

    if (!in_array($auth['role'], $allowedRoles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden', 'message' => 'Insufficient permissions']);
        exit;
    }

    return $auth;
}

/**
 * エージェントの存在確認・自動登録
 * @param string $agentId エージェントID
 * @return bool
 */
function ensureAgent(string $agentId): bool
{
    global $pdo;

    // 既存チェック
    $stmt = $pdo->prepare("SELECT agent_id FROM agents WHERE agent_id = :id");
    $stmt->execute([':id' => $agentId]);

    if ($stmt->fetch()) {
        // 最終通信時刻を更新
        $pdo->prepare("UPDATE agents SET last_seen = NOW(), status = 'online' WHERE agent_id = :id")
            ->execute([':id' => $agentId]);
        return true;
    }

    // 新規登録
    $sql = "INSERT INTO agents (agent_id, status, last_seen) VALUES (:id, 'online', NOW())";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':id' => $agentId]);
}

/**
 * dat_machineとの連携（Net8統合）
 * @param string $agentId エージェントID (CAMERA-001-XXXX形式)
 * @param array $info 追加情報 (ip, mac, hostname)
 */
function syncWithMachine(string $agentId, array $info = []): void
{
    global $pdo;

    // CAMERA-001-XXXX から番号を抽出
    if (preg_match('/CAMERA-\d+-(\d+)/', $agentId, $matches)) {
        $machineNo = intval($matches[1]);

        // dat_machineを更新
        $sql = "UPDATE dat_machine SET
                ip_address = COALESCE(:ip, ip_address),
                mac_address = COALESCE(:mac, mac_address),
                pc_status = 'online',
                last_report = NOW()
                WHERE machine_no = :no";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ip' => $info['ip'] ?? null,
            ':mac' => $info['mac'] ?? null,
            ':no' => $machineNo
        ]);

        // agentsテーブルも更新
        $sql = "UPDATE agents SET
                ip_address = COALESCE(:ip, ip_address),
                mac_address = COALESCE(:mac, mac_address),
                hostname = COALESCE(:host, hostname)
                WHERE agent_id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ip' => $info['ip'] ?? null,
            ':mac' => $info['mac'] ?? null,
            ':host' => $info['hostname'] ?? null,
            ':id' => $agentId
        ]);
    }
}
