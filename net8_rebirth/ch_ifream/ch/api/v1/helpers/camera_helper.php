<?php
/**
 * Camera Helper Functions
 *
 * カメラ関連のヘルパー関数
 * - シグナリングサーバーからリアルタイムでPeerIDを取得
 * - データベースへのフォールバック
 */

/**
 * アクティブなカメラのPeerIDをシグナリングサーバーから取得
 *
 * @param int $camera_no カメラ番号
 * @param string $signaling_host シグナリングサーバーのホスト
 * @param string $signaling_port シグナリングサーバーのポート
 * @param PDO $pdo データベース接続（フォールバック用）
 * @return array|null カメラ情報 ['peerId' => string, 'source' => 'signaling'|'database']
 */
function getActiveCameraPeerId($camera_no, $signaling_host, $signaling_port, $pdo) {
    error_log("🔍 Getting active PeerID for camera_no={$camera_no}");

    // シグナリングサーバーのURL構築
    $protocol = ($signaling_port == '443') ? 'https' : 'http';
    $signaling_url = "{$protocol}://{$signaling_host}:{$signaling_port}/peerjs/peers";

    error_log("📡 Fetching peers from: {$signaling_url}");

    try {
        // シグナリングサーバーから接続中のPeerIDリストを取得
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,  // 3秒タイムアウト
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,  // 開発環境用（本番では true 推奨）
                'verify_peer_name' => false
            ]
        ]);

        $response = @file_get_contents($signaling_url, false, $context);

        if ($response !== false) {
            $peers = json_decode($response, true);

            if (is_array($peers)) {
                error_log("✅ Got " . count($peers) . " peers from signaling server");
                error_log("   Peers: " . implode(', ', array_slice($peers, 0, 10)) . (count($peers) > 10 ? '...' : ''));

                // camera_{camera_no}_ で始まるPeerIDを検索
                $prefix = "camera_{$camera_no}_";
                foreach ($peers as $peer_id) {
                    if (strpos($peer_id, $prefix) === 0) {
                        error_log("✅ Found active camera PeerID: {$peer_id}");
                        return [
                            'peerId' => $peer_id,
                            'source' => 'signaling',
                            'active' => true
                        ];
                    }
                }

                error_log("⚠️  No active peer found for camera_{$camera_no}_");
            } else {
                error_log("⚠️  Invalid response from signaling server (not array)");
            }
        } else {
            error_log("⚠️  Failed to fetch peers from signaling server");
        }
    } catch (Exception $e) {
        error_log("❌ Error fetching from signaling server: " . $e->getMessage());
    }

    // フォールバック: データベースから取得
    error_log("📊 Falling back to database for camera_no={$camera_no}");

    try {
        $stmt = $pdo->prepare("
            SELECT camera_no, camera_name, camera_mac
            FROM mst_camera
            WHERE camera_no = :camera_no AND del_flg = 0
        ");
        $stmt->execute(['camera_no' => $camera_no]);
        $camera = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($camera && $camera['camera_name']) {
            error_log("✅ Database fallback: {$camera['camera_name']} (may be stale)");
            return [
                'peerId' => $camera['camera_name'],
                'source' => 'database',
                'active' => false,  // シグナリングサーバーで確認できなかったのでactiveとは限らない
                'cameraMac' => $camera['camera_mac']
            ];
        }
    } catch (Exception $e) {
        error_log("❌ Database fallback error: " . $e->getMessage());
    }

    error_log("❌ No PeerID found for camera_no={$camera_no}");
    return null;
}

/**
 * カメラ情報を完全に取得（PeerID + MACアドレス）
 *
 * @param int $camera_no カメラ番号
 * @param string $signaling_host シグナリングサーバーのホスト
 * @param string $signaling_port シグナリングサーバーのポート
 * @param PDO $pdo データベース接続
 * @return array|null カメラ情報
 */
function getCameraInfo($camera_no, $signaling_host, $signaling_port, $pdo) {
    // アクティブなPeerIDを取得
    $peerInfo = getActiveCameraPeerId($camera_no, $signaling_host, $signaling_port, $pdo);

    if (!$peerInfo) {
        return null;
    }

    // MACアドレスをDBから取得（シグナリングサーバーにはない）
    if (!isset($peerInfo['cameraMac'])) {
        try {
            $stmt = $pdo->prepare("SELECT camera_mac FROM mst_camera WHERE camera_no = :camera_no AND del_flg = 0");
            $stmt->execute(['camera_no' => $camera_no]);
            $camera = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($camera) {
                $peerInfo['cameraMac'] = $camera['camera_mac'];
            }
        } catch (Exception $e) {
            error_log("⚠️  Failed to get MAC address: " . $e->getMessage());
        }
    }

    return [
        'cameraNo' => $camera_no,
        'peerId' => $peerInfo['peerId'],
        'cameraName' => $peerInfo['peerId'],  // 互換性のため
        'cameraMac' => $peerInfo['cameraMac'] ?? null,
        'source' => $peerInfo['source'],
        'active' => $peerInfo['active']
    ];
}
?>
