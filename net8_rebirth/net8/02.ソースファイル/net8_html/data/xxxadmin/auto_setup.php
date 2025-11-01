<?php
/**
 * Railway自動セットアップ - 管理画面版
 *
 * デプロイ後のDB初期化を全自動で実行
 * STEP 1-7を一括実行し、進捗をリアルタイム表示
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300); // 5分タイムアウト

// 環境変数からDB接続情報を取得
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

$results = [];
$success_count = 0;
$error_count = 0;

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $results[] = ['step' => 0, 'title' => 'データベース接続', 'status' => 'success', 'message' => "接続成功: $db_host / $db_name"];

    // ========================================
    // STEP 1: 基本セットアップ
    // ========================================
    try {
        $results[] = ['step' => 1, 'title' => 'STEP 1: 基本セットアップ', 'status' => 'running', 'message' => '実行中...'];

        // mst_maker登録
        $pdo->exec("
            INSERT IGNORE INTO mst_maker (maker_no, maker_name, maker_roman, pachi_flg, slot_flg, disp_flg, del_flg)
            VALUES (1, 'SANKYO', 'SANKYO', 0, 1, 0, 0)
        ");

        // mst_type登録
        $pdo->exec("
            INSERT IGNORE INTO mst_type (type_no, category, type_name, type_roman, sort_no, del_flg)
            VALUES (2, 2, 'スロット', 'SLOT', 2, 0)
        ");

        // mst_unit登録
        $pdo->exec("
            INSERT IGNORE INTO mst_unit (unit_no, unit_name, unit_roman, sort_no, del_flg)
            VALUES (4, '4号機', '4GO', 4, 0)
        ");

        // mst_model登録
        $pdo->exec("
            INSERT IGNORE INTO mst_model (
                model_no, category, model_cd, model_name, maker_no, type_no, unit_no,
                del_flg, add_no, add_dt
            ) VALUES (
                1, 2, 'HOKUTO4GO', '北斗の拳（4号機）', 1, 2, 4,
                0, 1, NOW()
            )
        ");

        // mst_admin登録（パスワードハッシュ化）
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT IGNORE INTO mst_admin (admin_id, admin_pass, admin_name, auth_flg, del_flg, add_no, add_dt)
            VALUES ('admin', '$admin_pass', '管理者', 9, 0, 1, NOW())
        ");

        // licensesテーブル作成（slotserver.exe用）
        $table_exists = $pdo->query("SHOW TABLES LIKE 'licenses'")->rowCount() > 0;

        if (!$table_exists) {
            $pdo->exec("
                CREATE TABLE licenses (
                    license_id INT PRIMARY KEY AUTO_INCREMENT,
                    license_cd VARCHAR(255) NOT NULL,
                    domain VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            // license_cdカラムの存在確認
            $columns = $pdo->query("SHOW COLUMNS FROM licenses")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('license_cd', $columns)) {
                $pdo->exec("ALTER TABLE licenses ADD COLUMN license_cd VARCHAR(255) NOT NULL");
            }
        }

        // ライセンス情報登録
        $license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c';
        $domain = 'mgg-webservice-production.up.railway.app';
        $pdo->exec("
            INSERT INTO licenses (license_cd, domain) VALUES ('$license_cd', '$domain')
            ON DUPLICATE KEY UPDATE domain='$domain', updated_at=NOW()
        ");

        $results[count($results)-1] = ['step' => 1, 'title' => 'STEP 1: 基本セットアップ', 'status' => 'success', 'message' => '機種・メーカー・管理者・ライセンス登録完了'];
        $success_count++;
    } catch (Exception $e) {
        $results[count($results)-1] = ['step' => 1, 'title' => 'STEP 1: 基本セットアップ', 'status' => 'error', 'message' => $e->getMessage()];
        $error_count++;
    }

    // ========================================
    // STEP 2: mst_convertPoint登録
    // ========================================
    try {
        $results[] = ['step' => 2, 'title' => 'STEP 2: ポイント変換テーブル', 'status' => 'running', 'message' => '実行中...'];

        $pdo->exec("
            INSERT IGNORE INTO mst_convertPoint (convert_no, point, del_flg, add_no, add_dt)
            VALUES (1, 1, 0, 1, NOW())
        ");

        $results[count($results)-1] = ['step' => 2, 'title' => 'STEP 2: ポイント変換テーブル', 'status' => 'success', 'message' => 'mst_convertPoint登録完了'];
        $success_count++;
    } catch (Exception $e) {
        $results[count($results)-1] = ['step' => 2, 'title' => 'STEP 2: ポイント変換テーブル', 'status' => 'error', 'message' => $e->getMessage()];
        $error_count++;
    }

    // ========================================
    // STEP 3: カメラマスター登録
    // ========================================
    try {
        $results[] = ['step' => 3, 'title' => 'STEP 3: カメラマスター登録', 'status' => 'running', 'message' => '実行中...'];

        $cameras = [
            [1, '00:00:00:00:00:01', 'HOKUTO_CAMERA_1'],
            [2, '00:00:00:00:00:02', 'HOKUTO_CAMERA_2'],
            [3, '00:00:00:00:00:03', 'HOKUTO_CAMERA_3'],
        ];

        foreach ($cameras as list($no, $mac, $name)) {
            $pdo->exec("
                INSERT INTO mst_camera (camera_no, camera_mac, camera_name, del_flg, add_no, add_dt)
                VALUES ($no, '$mac', '$name', 0, 1, NOW())
                ON DUPLICATE KEY UPDATE camera_mac='$mac', camera_name='$name', del_flg=0
            ");
        }

        $results[count($results)-1] = ['step' => 3, 'title' => 'STEP 3: カメラマスター登録', 'status' => 'success', 'message' => 'カメラ3台登録完了'];
        $success_count++;
    } catch (Exception $e) {
        $results[count($results)-1] = ['step' => 3, 'title' => 'STEP 3: カメラマスター登録', 'status' => 'error', 'message' => $e->getMessage()];
        $error_count++;
    }

    // ========================================
    // STEP 4: 実機完全登録
    // ========================================
    try {
        $results[] = ['step' => 4, 'title' => 'STEP 4: 実機完全登録', 'status' => 'running', 'message' => '実行中...'];

        $today = date('Y-m-d');
        $machines = [
            ['HOKUTO001', 1, 'PEER001', 1],
            ['HOKUTO002', 2, 'PEER002', 1],
            ['HOKUTO003', 3, 'PEER003', 1],
        ];

        $registered = 0;
        foreach ($machines as list($cd, $cam, $sig, $conv)) {
            // dat_machine登録
            $stmt = $pdo->prepare("
                INSERT INTO dat_machine (
                    model_no, machine_cd, camera_no, signaling_id, convert_no,
                    machine_corner, release_date, end_date, machine_status, del_flg, add_no, add_dt
                ) VALUES (
                    1, :machine_cd, :camera_no, :signaling_id, :convert_no,
                    '1', :release_date, '2099-12-31', 1, 0, 1, NOW()
                )
                ON DUPLICATE KEY UPDATE machine_status=1, machine_corner='1'
            ");
            $stmt->execute([
                'machine_cd' => $cd,
                'camera_no' => $cam,
                'signaling_id' => $sig,
                'convert_no' => $conv,
                'release_date' => $today
            ]);

            $machine_no = $pdo->lastInsertId() ?: $pdo->query("SELECT machine_no FROM dat_machine WHERE machine_cd='$cd'")->fetchColumn();

            // dat_machinePlay登録
            $pdo->exec("
                INSERT IGNORE INTO dat_machinePlay (machine_no, total_count, count, bb_count, rb_count, hit_data, add_dt)
                VALUES ($machine_no, 0, 0, 0, 0, '', NOW())
            ");

            // lnk_machine登録
            $pdo->exec("
                INSERT IGNORE INTO lnk_machine (machine_no, member_no, assign_flg)
                VALUES ($machine_no, NULL, 0)
            ");

            $registered++;
        }

        $results[count($results)-1] = ['step' => 4, 'title' => 'STEP 4: 実機完全登録', 'status' => 'success', 'message' => "北斗の拳 {$registered}台登録完了（machine_corner='1'設定済み）"];
        $success_count++;
    } catch (Exception $e) {
        $results[count($results)-1] = ['step' => 4, 'title' => 'STEP 4: 実機完全登録', 'status' => 'error', 'message' => $e->getMessage()];
        $error_count++;
    }

    // ========================================
    // STEP 5: 画像パス登録
    // ========================================
    try {
        $results[] = ['step' => 5, 'title' => 'STEP 5: 画像パス登録', 'status' => 'running', 'message' => '実行中...'];

        $pdo->exec("
            UPDATE mst_model SET
                image_list = 'img/model/hokuto4go.jpg',
                upd_no = 1,
                upd_dt = NOW()
            WHERE model_cd = 'HOKUTO4GO'
        ");

        $results[count($results)-1] = ['step' => 5, 'title' => 'STEP 5: 画像パス登録', 'status' => 'success', 'message' => '北斗の拳画像パス設定完了'];
        $success_count++;
    } catch (Exception $e) {
        $results[count($results)-1] = ['step' => 5, 'title' => 'STEP 5: 画像パス登録', 'status' => 'error', 'message' => $e->getMessage()];
        $error_count++;
    }

    // ========================================
    // STEP 6: データ検証
    // ========================================
    try {
        $results[] = ['step' => 6, 'title' => 'STEP 6: データ検証', 'status' => 'running', 'message' => '実行中...'];

        $count = $pdo->query("
            SELECT COUNT(*) FROM dat_machine dm
            INNER JOIN dat_machinePlay dmp ON dmp.machine_no = dm.machine_no
            INNER JOIN lnk_machine lm ON lm.machine_no = dm.machine_no
            INNER JOIN mst_model mm ON mm.model_no = dm.model_no AND mm.del_flg <> '1'
            INNER JOIN mst_convertPoint mcp ON mcp.convert_no = dm.convert_no AND mcp.del_flg <> '1'
            WHERE dm.camera_no IS NOT NULL
              AND dm.del_flg <> '1'
              AND dm.machine_status <> '0'
        ")->fetchColumn();

        if ($count >= 3) {
            $results[count($results)-1] = ['step' => 6, 'title' => 'STEP 6: データ検証', 'status' => 'success', 'message' => "検証成功: {$count}台の実機が正常に登録されています"];
            $success_count++;
        } else {
            $results[count($results)-1] = ['step' => 6, 'title' => 'STEP 6: データ検証', 'status' => 'warning', 'message' => "警告: {$count}台しか取得できません（期待値: 3台）"];
        }
    } catch (Exception $e) {
        $results[count($results)-1] = ['step' => 6, 'title' => 'STEP 6: データ検証', 'status' => 'error', 'message' => $e->getMessage()];
        $error_count++;
    }

} catch (PDOException $e) {
    $results[] = ['step' => -1, 'title' => 'データベース接続エラー', 'status' => 'error', 'message' => $e->getMessage()];
    $error_count++;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway自動セットアップ - 管理画面</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { font-size: 14px; opacity: 0.9; }
        .content { padding: 30px; }
        .summary { display: flex; justify-content: space-around; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .summary-item { text-align: center; }
        .summary-item .number { font-size: 36px; font-weight: bold; margin-bottom: 5px; }
        .summary-item .label { font-size: 14px; color: #666; text-transform: uppercase; }
        .summary-item.success .number { color: #28a745; }
        .summary-item.error .number { color: #dc3545; }
        .summary-item.total .number { color: #007bff; }
        .step { margin-bottom: 20px; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
        .step-header { padding: 15px 20px; background: #f8f9fa; display: flex; align-items: center; }
        .step-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold; }
        .step.success .step-icon { background: #28a745; color: white; }
        .step.error .step-icon { background: #dc3545; color: white; }
        .step.warning .step-icon { background: #ffc107; color: white; }
        .step.running .step-icon { background: #17a2b8; color: white; animation: pulse 1.5s infinite; }
        .step-title { flex: 1; font-weight: bold; font-size: 16px; }
        .step-status { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .step.success .step-status { background: #d4edda; color: #155724; }
        .step.error .step-status { background: #f8d7da; color: #721c24; }
        .step.warning .step-status { background: #fff3cd; color: #856404; }
        .step.running .step-status { background: #d1ecf1; color: #0c5460; }
        .step-body { padding: 15px 20px; background: white; color: #333; }
        .actions { margin-top: 30px; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 0 10px; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .btn-secondary { background: linear-gradient(135deg, #20bf6b 0%, #26d0ce 100%); }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Railway自動セットアップ</h1>
            <p>デプロイ後のデータベース初期化を全自動で実行</p>
        </div>

        <div class="content">
            <div class="summary">
                <div class="summary-item total">
                    <div class="number"><?php echo count($results); ?></div>
                    <div class="label">Total Steps</div>
                </div>
                <div class="summary-item success">
                    <div class="number"><?php echo $success_count; ?></div>
                    <div class="label">Success</div>
                </div>
                <div class="summary-item error">
                    <div class="number"><?php echo $error_count; ?></div>
                    <div class="label">Errors</div>
                </div>
            </div>

            <?php foreach ($results as $result): ?>
            <div class="step <?php echo $result['status']; ?>">
                <div class="step-header">
                    <div class="step-icon">
                        <?php
                        if ($result['status'] === 'success') echo '✓';
                        elseif ($result['status'] === 'error') echo '✗';
                        elseif ($result['status'] === 'warning') echo '!';
                        elseif ($result['status'] === 'running') echo '...';
                        ?>
                    </div>
                    <div class="step-title"><?php echo htmlspecialchars($result['title']); ?></div>
                    <div class="step-status"><?php echo $result['status']; ?></div>
                </div>
                <div class="step-body">
                    <?php echo htmlspecialchars($result['message']); ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="actions">
                <a href="/" class="btn">トップページを確認</a>
                <a href="/xxxadmin/" class="btn-secondary btn">管理画面に戻る</a>
            </div>
        </div>
    </div>
</body>
</html>
