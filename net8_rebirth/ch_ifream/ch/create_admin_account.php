<?php
/**
 * admin/admin123 アカウント作成スクリプト
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// データベース接続
$DB_HOST = '136.116.70.86';
$DB_USER = 'net8tech001';
$DB_PASS = 'Nene11091108!!';
$DB_NAME = 'net8_dev';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>管理者アカウント作成</title></head><body>";
echo "<h1>管理者アカウント作成</h1>";

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    
    if ($mysqli->connect_error) {
        die("接続失敗: " . $mysqli->connect_error);
    }
    
    echo "<p>✅ データベース接続成功</p>";
    
    // 既存のadminアカウント確認
    $sql = "SELECT admin_no, admin_id, admin_pass FROM mst_admin WHERE admin_id = 'admin' AND del_flg = 0";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<p>⚠️ adminアカウントは既に存在します</p>";
        echo "<p>現在のパスワード: " . htmlspecialchars($row['admin_pass']) . "</p>";
        
        // パスワードをadmin123に更新
        $updateSql = "UPDATE mst_admin SET admin_pass = 'admin123', updated_at = NOW() WHERE admin_id = 'admin' AND del_flg = 0";
        if ($mysqli->query($updateSql)) {
            echo "<p>✅ パスワードをadmin123に更新しました</p>";
        }
    } else {
        // adminアカウントが存在しない場合は作成
        echo "<p>adminアカウントが存在しないため、作成します...</p>";
        
        // 最大のadmin_noを取得
        $maxNoSql = "SELECT MAX(admin_no) as max_no FROM mst_admin";
        $maxResult = $mysqli->query($maxNoSql);
        $maxRow = $maxResult->fetch_assoc();
        $nextNo = ($maxRow['max_no'] ?? 0) + 1;
        
        // adminアカウント作成
        $insertSql = "INSERT INTO mst_admin (
            admin_no, admin_id, admin_pass, admin_name, 
            auth_flg, del_flg, deny_menu, created_at, updated_at
        ) VALUES (
            ?, 'admin', 'admin123', 'システム管理者',
            1, 0, '', NOW(), NOW()
        )";
        
        $stmt = $mysqli->prepare($insertSql);
        $stmt->bind_param("i", $nextNo);
        
        if ($stmt->execute()) {
            echo "<p>✅ admin/admin123 アカウントを作成しました</p>";
        } else {
            echo "<p>❌ アカウント作成エラー: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    
    // 全管理者アカウントの確認
    echo "<h2>登録済み管理者アカウント一覧</h2>";
    $allSql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg, del_flg 
               FROM mst_admin 
               ORDER BY admin_no";
    
    $allResult = $mysqli->query($allSql);
    
    if ($allResult && $allResult->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>No</th><th>ID</th><th>名前</th><th>パスワード</th><th>権限</th><th>削除フラグ</th></tr>";
        
        while ($row = $allResult->fetch_assoc()) {
            $style = $row['del_flg'] == 0 ? "" : "style='color: #999;'";
            echo "<tr $style>";
            echo "<td>" . $row['admin_no'] . "</td>";
            echo "<td>" . htmlspecialchars($row['admin_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['admin_pass']) . "</td>";
            echo "<td>" . $row['auth_flg'] . "</td>";
            echo "<td>" . ($row['del_flg'] == 0 ? '有効' : '削除済み') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<h2>ログインテスト</h2>";
    echo "<div style='background-color: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>利用可能なアカウント:</strong></p>";
    echo "<ul>";
    echo "<li>ID: <strong>admin</strong> / パスワード: <strong>admin123</strong></li>";
    echo "<li>ID: <strong>testadmin</strong> / パスワード: <strong>testpass123</strong></li>";
    echo "</ul>";
    echo "<p><a href='/xxxadmin/login.php' target='_blank'>→ ログイン画面を開く</a></p>";
    echo "<p><a href='/test_login_simple.php' target='_blank'>→ シンプルログインテストを開く</a></p>";
    echo "</div>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>