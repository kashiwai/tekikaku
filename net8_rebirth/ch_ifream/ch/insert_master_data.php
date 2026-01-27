<?php
/**
 * マスタデータ投入スクリプト（Railway用）
 * このスクリプトは一度だけ実行してください
 */

header('Content-Type: text/html; charset=UTF-8');

// データベース接続情報を環境変数から取得
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_DATABASE');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');

echo "<pre>";
echo "=== マスタデータ投入開始 ===\n\n";

try {
    // PDO接続
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ データベース接続成功\n\n";

    // トランザクション開始
    $pdo->beginTransaction();

    // オーナーマスタ
    echo "=== mst_owner（オーナーマスタ）===\n";
    $sql = "INSERT INTO `mst_owner`
      (`owner_cd`, `owner_name`, `owner_nickname`, `owner_pref`, `mail`, `machine_count`, `remarks`, `dummy_flg`, `del_flg`, `add_dt`, `upd_dt`)
    VALUES
      ('MGG001', 'MGGオーナー', 'MGG本店', 13, 'mgg@example.com', 0, 'メインオーナー', 0, 0, NOW(), NOW()),
      ('TEST001', 'テストオーナー', 'テスト店', 27, 'test@example.com', 0, 'テスト用オーナー', 0, 0, NOW(), NOW()),
      ('DEMO001', 'デモオーナー', 'デモ店', 13, 'demo@example.com', 0, 'デモ用オーナー', 0, 0, NOW(), NOW())";
    $pdo->exec($sql);
    echo "✅ オーナー 3件 登録完了\n\n";

    // カメラマスタ
    echo "=== mst_camera（カメラマスタ）===\n";
    $sql = "INSERT INTO `mst_camera`
      (`camera_mac`, `camera_name`, `del_flg`, `add_dt`, `upd_dt`)
    VALUES
      ('00:00:00:00:00:01', 'カメラ001', 0, NOW(), NOW()),
      ('00:00:00:00:00:02', 'カメラ002', 0, NOW(), NOW()),
      ('00:00:00:00:00:03', 'カメラ003', 0, NOW(), NOW()),
      ('00:00:00:00:00:04', 'カメラ004', 0, NOW(), NOW()),
      ('00:00:00:00:00:05', 'カメラ005', 0, NOW(), NOW()),
      ('00:00:00:00:00:06', 'カメラ006', 0, NOW(), NOW()),
      ('00:00:00:00:00:07', 'カメラ007', 0, NOW(), NOW()),
      ('00:00:00:00:00:08', 'カメラ008', 0, NOW(), NOW()),
      ('00:00:00:00:00:09', 'カメラ009', 0, NOW(), NOW()),
      ('00:00:00:00:00:0A', 'カメラ010', 0, NOW(), NOW()),
      ('00:00:00:00:00:0B', 'カメラ011', 0, NOW(), NOW()),
      ('00:00:00:00:00:0C', 'カメラ012', 0, NOW(), NOW()),
      ('00:00:00:00:00:0D', 'カメラ013', 0, NOW(), NOW()),
      ('00:00:00:00:00:0E', 'カメラ014', 0, NOW(), NOW()),
      ('00:00:00:00:00:0F', 'カメラ015', 0, NOW(), NOW()),
      ('00:00:00:00:00:10', 'カメラ016', 0, NOW(), NOW()),
      ('00:00:00:00:00:11', 'カメラ017', 0, NOW(), NOW()),
      ('00:00:00:00:00:12', 'カメラ018', 0, NOW(), NOW()),
      ('00:00:00:00:00:13', 'カメラ019', 0, NOW(), NOW()),
      ('00:00:00:00:00:14', 'カメラ020', 0, NOW(), NOW())";
    $pdo->exec($sql);
    echo "✅ カメラ 20件 登録完了\n\n";

    // 変換レートマスタ
    echo "=== mst_convertPoint（変換レートマスタ）===\n";
    $sql = "INSERT INTO `mst_convertPoint`
      (`convert_name`, `point`, `credit`, `draw_point`, `del_flg`, `add_dt`, `upd_dt`)
    VALUES
      ('1玉1円', 1, 1, 1, 0, NOW(), NOW()),
      ('1玉2円', 2, 1, 2, 0, NOW(), NOW()),
      ('1玉4円', 4, 1, 4, 0, NOW(), NOW()),
      ('2.5円スロット', 25, 10, 25, 0, NOW(), NOW()),
      ('5円スロット', 5, 1, 5, 0, NOW(), NOW()),
      ('20円スロット', 20, 1, 20, 0, NOW(), NOW())";
    $pdo->exec($sql);
    echo "✅ 変換レート 6件 登録完了\n\n";

    // コミット
    $pdo->commit();
    echo "✅ 全てのデータを正常に登録しました\n\n";

    // 確認用データ表示
    echo "=== 登録データ確認 ===\n\n";

    echo "--- mst_owner ---\n";
    $stmt = $pdo->query("SELECT owner_no, owner_nickname FROM mst_owner WHERE del_flg = 0");
    foreach ($stmt->fetchAll() as $row) {
        echo "ID: {$row['owner_no']}, Name: {$row['owner_nickname']}\n";
    }
    echo "\n";

    echo "--- mst_camera ---\n";
    $stmt = $pdo->query("SELECT camera_no, camera_name FROM mst_camera WHERE del_flg = 0 LIMIT 10");
    foreach ($stmt->fetchAll() as $row) {
        echo "ID: {$row['camera_no']}, Name: {$row['camera_name']}\n";
    }
    echo "...他10件\n\n";

    echo "--- mst_convertPoint ---\n";
    $stmt = $pdo->query("SELECT convert_no, convert_name, point, credit FROM mst_convertPoint WHERE del_flg = 0");
    foreach ($stmt->fetchAll() as $row) {
        echo "ID: {$row['convert_no']}, Name: {$row['convert_name']}, Point: {$row['point']}, Credit: {$row['credit']}\n";
    }

    echo "\n✅ マスタデータ投入完了！\n";
    echo "\n次のURLで管理画面の選択フィールドが表示されることを確認してください：\n";
    echo "https://mgg-webservice-production.up.railway.app/xxxadmin/search.php?M=detail\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "\n既にデータが登録済みの可能性があります。\n";
}

echo "</pre>";
