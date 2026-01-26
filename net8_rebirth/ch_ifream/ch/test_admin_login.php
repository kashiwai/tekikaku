<?php
/**
 * 管理者ログインテスト
 * mst_adminテーブルの確認と管理者アカウント作成
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>管理者ログインテスト</title></head><body>";
echo "<h1>管理者ログインテスト</h1>";

// DB接続設定を読み込み
require_once(__DIR__ . '/_etc/require_files_admin.php');

try {
    // DB接続
    $db = new SmartDB_MySQL();
    echo "<p>✅ データベース接続成功</p>";
    
    // 既存の管理者アカウントを確認
    echo "<h2>1. 既存の管理者アカウント確認</h2>";
    $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg, del_flg 
            FROM mst_admin 
            WHERE del_flg = 0 
            ORDER BY admin_no";
    
    $result = $db->select($sql);
    
    if ($result && count($result) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>admin_no</th><th>admin_id</th><th>admin_name</th><th>admin_pass</th><th>auth_flg</th></tr>";
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>{$row['admin_no']}</td>";
            echo "<td>{$row['admin_id']}</td>";
            echo "<td>{$row['admin_name']}</td>";
            echo "<td>" . substr($row['admin_pass'], 0, 10) . "...</td>";
            echo "<td>{$row['auth_flg']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ 管理者アカウントが存在しません</p>";
    }
    
    // デフォルト管理者アカウントの存在確認
    echo "<h2>2. デフォルト管理者アカウントの確認</h2>";
    $defaultAccounts = [
        ['id' => 'admin', 'pass' => 'admin'],
        ['id' => 'admin', 'pass' => 'admin1234'],
        ['id' => 'net8admin', 'pass' => 'net8admin'],
        ['id' => 'superadmin', 'pass' => 'superadmin']
    ];
    
    foreach ($defaultAccounts as $account) {
        $sql = "SELECT admin_no, admin_name FROM mst_admin 
                WHERE admin_id = '" . $db->conv_sql($account['id']) . "' 
                AND admin_pass = '" . $db->conv_sql($account['pass']) . "' 
                AND del_flg = 0";
        
        $result = $db->select($sql);
        if ($result && count($result) > 0) {
            echo "<p>✅ アカウント発見: ID={$account['id']}, PASS={$account['pass']}</p>";
        }
    }
    
    // テスト用管理者アカウントを作成（必要な場合）
    echo "<h2>3. テスト用管理者アカウント作成</h2>";
    
    // 既存のテストアカウントを確認
    $sql = "SELECT admin_no FROM mst_admin WHERE admin_id = 'testadmin' AND del_flg = 0";
    $result = $db->select($sql);
    
    if (!$result || count($result) == 0) {
        // テストアカウントが存在しない場合は作成
        echo "<p>テスト用管理者アカウントを作成します...</p>";
        
        // 最大のadmin_noを取得
        $sql = "SELECT MAX(admin_no) as max_no FROM mst_admin";
        $result = $db->select($sql);
        $nextNo = ($result && $result[0]['max_no']) ? $result[0]['max_no'] + 1 : 1;
        
        // アカウント作成
        $sql = "INSERT INTO mst_admin (
                    admin_no, admin_id, admin_pass, admin_name, 
                    auth_flg, del_flg, deny_menu, created_at, updated_at
                ) VALUES (
                    $nextNo, 'testadmin', 'testpass123', 'テスト管理者',
                    1, 0, '', NOW(), NOW()
                )";
        
        $insertResult = $db->execute($sql);
        if ($insertResult) {
            echo "<p>✅ テスト管理者アカウント作成成功</p>";
            echo "<div style='background-color: #e0f2fe; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>ログイン情報</h3>";
            echo "<p><strong>ID:</strong> testadmin</p>";
            echo "<p><strong>パスワード:</strong> testpass123</p>";
            echo "<p><strong>ログインURL:</strong> <a href='/xxxadmin/login.php' target='_blank'>/xxxadmin/login.php</a></p>";
            echo "</div>";
        } else {
            echo "<p>❌ テスト管理者アカウント作成失敗</p>";
        }
    } else {
        echo "<p>✅ テスト管理者アカウントは既に存在します（ID: testadmin, PASS: testpass123）</p>";
        echo "<div style='background-color: #e0f2fe; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>ログインURL:</strong> <a href='/xxxadmin/login.php' target='_blank'>/xxxadmin/login.php</a></p>";
        echo "</div>";
    }
    
    // require_files_admin.phpの存在確認
    echo "<h2>4. 設定ファイルの確認</h2>";
    $requiredFiles = [
        __DIR__ . '/_etc/require_files_admin.php',
        __DIR__ . '/_sys/TemplateAdmin.php',
        __DIR__ . '/_lib/SmartDB_MySQL.php',
        __DIR__ . '/_lib/SqlString.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "<p>✅ " . basename(dirname($file)) . "/" . basename($file) . " - 存在</p>";
        } else {
            echo "<p>❌ " . basename(dirname($file)) . "/" . basename($file) . " - 不存在</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>