<?php
/**
 * 完全セットアップスクリプト
 *
 * 以下を一括で実行：
 * 1. テーブル作成
 * 2. 管理者アカウント作成
 * 3. メーカーデータ登録
 * 4. 北斗の拳機種データ登録
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🚀 完全セットアップ</h1>";
echo "<hr>";

// 環境変数から直接データベース接続情報を取得
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>✅ データベース接続成功</h2>";
    echo "<p>Host: $db_host / DB: $db_name</p>";

    // ========================================
    // STEP 1: テーブル作成
    // ========================================
    echo "<h2>📋 STEP 1: テーブル作成</h2>";

    $sql_file = __DIR__ . '/01_create.sql';
    if (!file_exists($sql_file)) {
        die("<p>❌ 01_create.sql が見つかりません</p>");
    }

    $sql_content = file_get_contents($sql_file);
    $sql_statements = explode(';', $sql_content);

    $created_tables = 0;
    foreach ($sql_statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;

        try {
            $pdo->exec($statement);
            if (stripos($statement, 'CREATE TABLE') !== false) {
                $created_tables++;
            }
        } catch (PDOException $e) {
            // テーブルが既に存在する場合はスキップ
            if ($e->getCode() != '42S01') {
                throw $e;
            }
        }
    }

    echo "<p>✅ テーブル作成完了（{$created_tables}個）</p>";

    // ========================================
    // STEP 2: 管理者アカウント作成
    // ========================================
    echo "<h2>🔐 STEP 2: 管理者アカウント作成</h2>";

    $admin_id = 'admin';
    $admin_password = 'admin123';
    $admin_name = 'システム管理者';
    $auth_flg = 9;
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

    // 既存チェック
    $stmt = $pdo->prepare("SELECT admin_no FROM mst_admin WHERE admin_id = :admin_id");
    $stmt->execute(['admin_id' => $admin_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE mst_admin
            SET admin_pass = :password,
                admin_name = :name,
                auth_flg = :auth,
                del_flg = 0,
                upd_dt = NOW()
            WHERE admin_id = :admin_id
        ");
        $stmt->execute([
            'password' => $password_hash,
            'name' => $admin_name,
            'auth' => $auth_flg,
            'admin_id' => $admin_id
        ]);
        echo "<p>✅ 管理者アカウント更新: {$admin_id}</p>";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO mst_admin (
                admin_id, admin_pass, admin_name, auth_flg,
                del_flg, add_no, add_dt
            ) VALUES (
                :admin_id, :password, :name, :auth,
                0, 1, NOW()
            )
        ");
        $stmt->execute([
            'admin_id' => $admin_id,
            'password' => $password_hash,
            'name' => $admin_name,
            'auth' => $auth_flg
        ]);
        echo "<p>✅ 管理者アカウント作成: {$admin_id}</p>";
    }

    // ========================================
    // STEP 3: メーカーデータ登録
    // ========================================
    echo "<h2>🏭 STEP 3: メーカーデータ登録</h2>";

    $check_maker = $pdo->query("SELECT COUNT(*) FROM mst_maker WHERE del_flg = 0")->fetchColumn();

    if ($check_maker == 0) {
        // デフォルトメーカーを登録
        $stmt = $pdo->prepare("
            INSERT INTO mst_maker (
                maker_name, maker_roman, disp_flg,
                del_flg, add_no, add_dt
            ) VALUES (
                'サミー', 'SAMMY', 1,
                0, 1, NOW()
            )
        ");
        $stmt->execute();
        echo "<p>✅ メーカー登録: サミー</p>";
    } else {
        echo "<p>✅ メーカーデータ既存（{$check_maker}件）</p>";
    }

    // ========================================
    // STEP 3.5: タイプマスターデータ登録
    // ========================================
    echo "<h2>🎯 STEP 3.5: タイプマスターデータ登録</h2>";

    $check_type = $pdo->query("SELECT COUNT(*) FROM mst_type WHERE del_flg = 0")->fetchColumn();

    if ($check_type == 0) {
        // パチンコタイプ (category=1)
        $pachi_types = [
            ['確変', 'KAKUHEN', 1],
            ['ST', 'ST', 2],
            ['右打ち', 'MIGIUCHI', 3],
            ['一種二種混合', 'MIX', 4],
        ];

        // スロットタイプ (category=2)
        $slot_types = [
            ['AT', 'AT', 1],
            ['ART', 'ART', 2],
            ['RT', 'RT', 3],
            ['Aタイプ', 'A-TYPE', 4],
            ['その他', 'OTHER', 5],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO mst_type (category, type_name, type_roman, sort_no, del_flg)
            VALUES (:category, :name, :roman, :sort, 0)
        ");

        foreach ($pachi_types as $type) {
            $stmt->execute([
                'category' => 1,
                'name' => $type[0],
                'roman' => $type[1],
                'sort' => $type[2]
            ]);
        }

        foreach ($slot_types as $type) {
            $stmt->execute([
                'category' => 2,
                'name' => $type[0],
                'roman' => $type[1],
                'sort' => $type[2]
            ]);
        }

        echo "<p>✅ タイプデータ登録完了（パチンコ: " . count($pachi_types) . "件、スロット: " . count($slot_types) . "件）</p>";
    } else {
        echo "<p>✅ タイプデータ既存（{$check_type}件）</p>";
    }

    // ========================================
    // STEP 3.6: 号機マスターデータ登録
    // ========================================
    echo "<h2>🎰 STEP 3.6: 号機マスターデータ登録</h2>";

    $check_unit = $pdo->query("SELECT COUNT(*) FROM mst_unit WHERE del_flg = 0")->fetchColumn();

    if ($check_unit == 0) {
        $units = [
            ['旧基準機', 'OLD', 1],
            ['現行機', 'CURRENT', 2],
            ['3号機', '3rd', 3],
            ['4号機', '4th', 4],
            ['5号機', '5th', 5],
            ['6号機', '6th', 6],
            ['スマスロ', 'SMART', 7],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO mst_unit (unit_name, unit_roman, sort_no, del_flg)
            VALUES (:name, :roman, :sort, 0)
        ");

        foreach ($units as $unit) {
            $stmt->execute([
                'name' => $unit[0],
                'roman' => $unit[1],
                'sort' => $unit[2]
            ]);
        }

        echo "<p>✅ 号機データ登録完了（" . count($units) . "件）</p>";
    } else {
        echo "<p>✅ 号機データ既存（{$check_unit}件）</p>";
    }

    // ========================================
    // STEP 4: 北斗の拳機種データ登録
    // ========================================
    echo "<h2>🎰 STEP 4: 北斗の拳機種データ登録</h2>";

    // 既存チェック
    $check_model = $pdo->query("SELECT model_no FROM mst_model WHERE model_cd = 'HOKUTO4GO'")->fetch();

    if ($check_model) {
        echo "<p>⚠️ 北斗の拳は既に登録済み（機種No: {$check_model['model_no']}）</p>";
    } else {
        // メーカー番号取得
        $maker = $pdo->query("SELECT maker_no FROM mst_maker WHERE del_flg = 0 ORDER BY maker_no LIMIT 1")->fetch();

        if (!$maker) {
            echo "<p>❌ メーカーが見つかりません</p>";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO mst_model (
                    category, model_cd, model_name, model_roman,
                    type_no, unit_no, maker_no,
                    renchan_games, tenjo_games,
                    image_list, image_detail, image_reel,
                    prizeball_data, layout_data, setting_list, remarks,
                    del_flg, add_no, add_dt, upd_no, upd_dt
                ) VALUES (
                    2, 'HOKUTO4GO', '北斗の拳', 'Fist of the North Star',
                    5, 4, :maker_no,
                    0, 1280,
                    '', '', '',
                    '', :layout_data, '', '4号機北斗の拳',
                    0, 1, NOW(), 1, NOW()
                )
            ");

            $layout_data = json_encode([
                'video_portrait' => 0,
                'video_mode' => 4,
                'drum' => 0,
                'bonus_push' => [],
                'version' => 2,
                'hide' => []
            ]);

            $stmt->execute([
                'maker_no' => $maker['maker_no'],
                'layout_data' => $layout_data
            ]);

            $model_no = $pdo->lastInsertId();
            echo "<p>✅ 北斗の拳登録完了（機種No: {$model_no}）</p>";
        }
    }

    // ========================================
    // 完了
    // ========================================
    echo "<hr>";
    echo "<h2>🎉 セットアップ完了</h2>";

    echo "<h3>📝 登録内容確認</h3>";
    echo "<ul>";
    echo "<li>管理者: {$admin_id} / {$admin_password}</li>";

    $maker_count = $pdo->query("SELECT COUNT(*) FROM mst_maker WHERE del_flg = 0")->fetchColumn();
    echo "<li>メーカー: {$maker_count}件</li>";

    $model_count = $pdo->query("SELECT COUNT(*) FROM mst_model WHERE del_flg = 0")->fetchColumn();
    echo "<li>機種: {$model_count}件</li>";
    echo "</ul>";

    echo "<h3>🔗 次のステップ</h3>";
    echo "<ol>";
    echo "<li><a href='/xxxadmin/login.php'>管理画面にログイン</a> (ID: admin / PASS: admin123)</li>";
    echo "<li><a href='/xxxadmin/model.php'>機種管理</a>で北斗の拳を確認</li>";
    echo "<li><a href='/xxxadmin/search.php'>実機管理</a>で台を登録</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
