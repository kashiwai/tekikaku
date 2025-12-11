<?php
/**
 * 北斗の拳機種データ登録スクリプト
 * ブラウザからアクセスして実行
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__ . '/_etc/setting.php');

echo "<h1>北斗の拳機種データ登録</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<p>✅ データベース接続成功</p>";

    // 既存の北斗の拳データを確認
    $check_sql = "SELECT model_no, model_cd, model_name FROM mst_model WHERE model_cd = 'HOKUTO4GO'";
    $existing = $pdo->query($check_sql)->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo "<h2>⚠️ すでに登録済み</h2>";
        echo "<p>機種No: {$existing['model_no']}</p>";
        echo "<p>機種CD: {$existing['model_cd']}</p>";
        echo "<p>機種名: {$existing['model_name']}</p>";
        echo "<p><a href='/xxxadmin/model.php'>機種管理画面へ</a></p>";
        exit;
    }

    // メーカー番号を取得（最初のメーカーを使用）
    $maker_sql = "SELECT maker_no, maker_name FROM mst_maker WHERE del_flg = 0 ORDER BY maker_no LIMIT 1";
    $maker = $pdo->query($maker_sql)->fetch(PDO::FETCH_ASSOC);

    if (!$maker) {
        die("<p>❌ メーカーが登録されていません。先にメーカーを登録してください。</p>");
    }

    echo "<p>使用メーカー: {$maker['maker_name']} (No.{$maker['maker_no']})</p>";

    // 機種データ挿入
    $insert_sql = "
        INSERT INTO `mst_model` (
            `category`,
            `model_cd`,
            `model_name`,
            `model_roman`,
            `type_no`,
            `unit_no`,
            `maker_no`,
            `renchan_games`,
            `tenjo_games`,
            `image_list`,
            `image_detail`,
            `image_reel`,
            `prizeball_data`,
            `layout_data`,
            `setting_list`,
            `remarks`,
            `del_flg`,
            `add_no`,
            `add_dt`,
            `upd_no`,
            `upd_dt`
        ) VALUES (
            2,
            'HOKUTO4GO',
            '北斗の拳',
            'Fist of the North Star',
            5,
            4,
            :maker_no,
            0,
            1280,
            '',
            '',
            '',
            '',
            '{\"video_portrait\":0,\"video_mode\":4,\"drum\":0,\"bonus_push\":[],\"version\":2,\"hide\":[]}',
            '',
            '4号機北斗の拳',
            0,
            1,
            NOW(),
            1,
            NOW()
        )
    ";

    $stmt = $pdo->prepare($insert_sql);
    $stmt->execute(['maker_no' => $maker['maker_no']]);

    $model_no = $pdo->lastInsertId();

    echo "<h2>✅ 登録完了</h2>";
    echo "<p><strong>機種No:</strong> {$model_no}</p>";
    echo "<p><strong>機種CD:</strong> HOKUTO4GO</p>";
    echo "<p><strong>機種名:</strong> 北斗の拳</p>";
    echo "<p><strong>機種名（英語）:</strong> Fist of the North Star</p>";
    echo "<p><strong>タイプ:</strong> AT</p>";
    echo "<p><strong>号機:</strong> 4号機</p>";
    echo "<p><strong>天井ゲーム数:</strong> 1280G</p>";
    echo "<p><strong>メーカー:</strong> {$maker['maker_name']}</p>";

    // 登録確認
    $verify_sql = "SELECT * FROM mst_model WHERE model_no = :model_no";
    $verify_stmt = $pdo->prepare($verify_sql);
    $verify_stmt->execute(['model_no' => $model_no]);
    $verify_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>📋 登録内容確認</h3>";
    echo "<pre>";
    print_r($verify_data);
    echo "</pre>";

    echo "<p><a href='/xxxadmin/model.php'>機種管理画面で確認</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
