<?php
/**
 * コーナーデータ登録スクリプト
 *
 * 初期セットアップ用: パチスロと新台のコーナーを登録
 * ブラウザから直接アクセスして実行: https://your-domain.com/data/setup_corner.php
 */

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    // データベース接続
    $pdo = get_db_connection();

    echo "<h1>コーナーデータ登録</h1>";
    echo "<pre>";

    // パチスロコーナー登録
    $sql1 = "INSERT INTO mst_corner (corner_no, corner_name, corner_roman, del_flg, ins_dt, upd_dt)
             VALUES (1, 'パチスロ', 'pachislot', 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
               corner_name = VALUES(corner_name),
               corner_roman = VALUES(corner_roman),
               upd_dt = NOW()";

    $pdo->exec($sql1);
    echo "✓ パチスロコーナー登録完了\n";

    // 新台コーナー登録
    $sql2 = "INSERT INTO mst_corner (corner_no, corner_name, corner_roman, del_flg, ins_dt, upd_dt)
             VALUES (2, '新台', 'shindai', 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
               corner_name = VALUES(corner_name),
               corner_roman = VALUES(corner_roman),
               upd_dt = NOW()";

    $pdo->exec($sql2);
    echo "✓ 新台コーナー登録完了\n";

    // 登録結果確認
    $result = $pdo->query("SELECT * FROM mst_corner WHERE del_flg = 0 ORDER BY corner_no");
    echo "\n【登録されたコーナー一覧】\n";
    echo "corner_no | corner_name | corner_roman\n";
    echo "----------|-------------|-------------\n";

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        printf("%-10s| %-12s| %s\n",
            $row['corner_no'],
            $row['corner_name'],
            $row['corner_roman']
        );
    }

    echo "\n✅ コーナーデータ登録が完了しました！\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "<h1>エラーが発生しました</h1>";
    echo "<pre>";
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "</pre>";
}
?>
