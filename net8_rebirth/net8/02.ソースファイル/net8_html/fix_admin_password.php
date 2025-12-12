<?php
/**
 * admin/admin123 パスワード修正スクリプト
 * password_hash()を使用して正しいハッシュを生成
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// データベース接続
$DB_HOST = '136.116.70.86';
$DB_USER = 'net8tech001';
$DB_PASS = 'Nene11091108!!';
$DB_NAME = 'net8_dev';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>管理者パスワード修正</title></head><body>";
echo "<h1>管理者パスワード修正</h1>";

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    
    if ($mysqli->connect_error) {
        die("接続失敗: " . $mysqli->connect_error);
    }
    
    echo "<p>✅ データベース接続成功</p>";
    
    // admin123のハッシュを生成
    $plainPassword = 'admin123';
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    echo "<h2>パスワードハッシュ生成</h2>";
    echo "<p>プレーンパスワード: <strong>$plainPassword</strong></p>";
    echo "<p>ハッシュ化パスワード: <code>$hashedPassword</code></p>";
    
    // adminアカウントの存在確認
    $checkSql = "SELECT admin_no, admin_id, admin_pass FROM mst_admin WHERE admin_id = 'admin' AND del_flg = 0";
    $result = $mysqli->query($checkSql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<h2>既存のadminアカウント</h2>";
        echo "<p>admin_no: " . $row['admin_no'] . "</p>";
        echo "<p>現在のパスワードハッシュ: <code>" . htmlspecialchars($row['admin_pass']) . "</code></p>";
        
        // パスワード更新
        $updateSql = "UPDATE mst_admin SET admin_pass = ? WHERE admin_id = 'admin' AND del_flg = 0";
        $stmt = $mysqli->prepare($updateSql);
        $stmt->bind_param("s", $hashedPassword);
        
        if ($stmt->execute()) {
            echo "<p>✅ <strong>adminアカウントのパスワードをadmin123に更新しました</strong></p>";
        } else {
            echo "<p>❌ 更新エラー: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        // adminアカウントが存在しない場合は作成
        echo "<h2>新規adminアカウント作成</h2>";
        
        // 最大のadmin_noを取得
        $maxNoSql = "SELECT COALESCE(MAX(admin_no), 0) + 1 as next_no FROM mst_admin";
        $maxResult = $mysqli->query($maxNoSql);
        $maxRow = $maxResult->fetch_assoc();
        $nextNo = $maxRow['next_no'];
        
        // adminアカウント作成（ハッシュ化パスワードで）
        $insertSql = "INSERT INTO mst_admin (
            admin_no, admin_id, admin_pass, admin_name, 
            auth_flg, del_flg, deny_menu, created_at
        ) VALUES (
            ?, 'admin', ?, 'システム管理者',
            1, 0, '', NOW()
        )";
        
        $stmt = $mysqli->prepare($insertSql);
        $stmt->bind_param("is", $nextNo, $hashedPassword);
        
        if ($stmt->execute()) {
            echo "<p>✅ <strong>admin/admin123 アカウントを作成しました</strong></p>";
        } else {
            echo "<p>❌ 作成エラー: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    
    // パスワード検証テスト
    echo "<h2>パスワード検証テスト</h2>";
    $verifySql = "SELECT admin_pass FROM mst_admin WHERE admin_id = 'admin' AND del_flg = 0";
    $verifyResult = $mysqli->query($verifySql);
    
    if ($verifyResult && $verifyResult->num_rows > 0) {
        $verifyRow = $verifyResult->fetch_assoc();
        $storedHash = $verifyRow['admin_pass'];
        
        echo "<p>保存されたハッシュ: <code>" . htmlspecialchars($storedHash) . "</code></p>";
        
        if (password_verify('admin123', $storedHash)) {
            echo "<p>✅ <strong>パスワード検証成功！ admin123で認証できます</strong></p>";
        } else {
            echo "<p>❌ パスワード検証失敗</p>";
        }
    }
    
    // 全管理者アカウントの確認
    echo "<h2>利用可能な管理者アカウント</h2>";
    $allSql = "SELECT admin_no, admin_id, admin_name, admin_pass 
               FROM mst_admin 
               WHERE del_flg = 0
               ORDER BY admin_no";
    
    $allResult = $mysqli->query($allSql);
    
    if ($allResult && $allResult->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>No</th><th>ID</th><th>名前</th><th>パスワード検証</th></tr>";
        
        $testPasswords = [
            'admin' => ['admin123', 'admin', 'admin1234'],
            'testadmin' => ['testpass123', 'test123', 'testadmin'],
            'net8admin' => ['net8admin', 'net8pass'],
            'superadmin' => ['superadmin', 'super123']
        ];
        
        while ($row = $allResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['admin_no'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['admin_id']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
            echo "<td>";
            
            // 既知のパスワードでテスト
            $found = false;
            if (isset($testPasswords[$row['admin_id']])) {
                foreach ($testPasswords[$row['admin_id']] as $testPass) {
                    if (password_verify($testPass, $row['admin_pass'])) {
                        echo "✅ パスワード: <strong>$testPass</strong>";
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                echo "⚠️ パスワード不明";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<h2>🚀 ログインテスト</h2>";
    echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ 利用可能なアカウント:</h3>";
    echo "<ul style='font-size: 18px;'>";
    echo "<li>ID: <strong>admin</strong> / パスワード: <strong>admin123</strong></li>";
    echo "</ul>";
    echo "<p><a href='/xxxadmin/login.php' target='_blank' style='font-size: 18px;'>→ 管理画面ログインページへ</a></p>";
    echo "<p><a href='/test_login_simple.php' target='_blank'>→ シンプルログインテスト（デバッグ用）</a></p>";
    echo "</div>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>