<?php
/**
 * ログインデバッグテスト
 */

// DB接続情報
define("DB_HOST", "136.116.70.86");
define("DB_USER", "net8tech001");
define("DB_PASS", "Nene11091108!!");
define("DB_NAME", "net8_dev");

// テスト用パスワード
$test_password = "Net8Admin@2024#Secure";

echo "=== ログインデバッグテスト ===\n\n";

try {
    // DB接続
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        die("❌ DB接続失敗: " . $mysqli->connect_error . "\n");
    }
    echo "✅ DB接続成功\n\n";
    
    // adminアカウント確認
    $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, del_flg 
            FROM mst_admin 
            WHERE admin_id = 'admin' 
            ORDER BY admin_no DESC";
    
    $result = $mysqli->query($sql);
    
    echo "📊 adminアカウント一覧:\n";
    echo str_repeat("-", 80) . "\n";
    
    if ($result && $result->num_rows > 0) {
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $count++;
            echo "アカウント #{$count}:\n";
            echo "  - admin_no: {$row['admin_no']}\n";
            echo "  - admin_id: {$row['admin_id']}\n";
            echo "  - admin_name: {$row['admin_name']}\n";
            echo "  - del_flg: {$row['del_flg']}\n";
            echo "  - パスワードハッシュ長: " . strlen($row['admin_pass']) . "\n";
            echo "  - パスワード先頭20文字: " . substr($row['admin_pass'], 0, 20) . "...\n";
            
            // パスワード検証テスト
            if (password_verify($test_password, $row['admin_pass'])) {
                echo "  ✅ パスワード検証: 成功\n";
            } else {
                echo "  ❌ パスワード検証: 失敗\n";
                
                // 古いMD5形式のチェック
                if (strlen($row['admin_pass']) == 32) {
                    echo "  ⚠️ 古いMD5形式のパスワードの可能性があります\n";
                }
            }
            echo "\n";
        }
        
        // 有効なアカウントのみ取得
        echo "📊 有効なadminアカウント (del_flg = 0):\n";
        echo str_repeat("-", 80) . "\n";
        
        $sql_active = "SELECT admin_no, admin_id, admin_name, admin_pass 
                       FROM mst_admin 
                       WHERE admin_id = 'admin' AND del_flg = 0 
                       ORDER BY admin_no DESC 
                       LIMIT 1";
        
        $result_active = $mysqli->query($sql_active);
        if ($result_active && $row_active = $result_active->fetch_assoc()) {
            echo "最新の有効なadminアカウント:\n";
            echo "  - admin_no: {$row_active['admin_no']}\n";
            echo "  - admin_id: {$row_active['admin_id']}\n";
            echo "  - admin_name: {$row_active['admin_name']}\n";
            
            if (password_verify($test_password, $row_active['admin_pass'])) {
                echo "  ✅ このアカウントでログイン可能です\n";
            } else {
                echo "  ❌ パスワードが一致しません。パスワードをリセットします...\n\n";
                
                // パスワードリセット
                $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE mst_admin 
                              SET admin_pass = ?, upd_dt = NOW() 
                              WHERE admin_no = ?";
                $stmt = $mysqli->prepare($update_sql);
                $stmt->bind_param("si", $new_hash, $row_active['admin_no']);
                if ($stmt->execute()) {
                    echo "  ✅ パスワードをリセットしました\n";
                    echo "  新しいパスワード: {$test_password}\n";
                } else {
                    echo "  ❌ パスワードリセット失敗: " . $stmt->error . "\n";
                }
                $stmt->close();
            }
        } else {
            echo "❌ 有効なadminアカウントが見つかりません\n";
        }
        
    } else {
        echo "❌ adminアカウントが存在しません\n\n";
        
        // 新規作成
        echo "新しいadminアカウントを作成します...\n";
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO mst_admin 
                       (admin_name, admin_id, admin_pass, auth_flg, del_flg, add_dt, upd_dt) 
                       VALUES ('管理者', 'admin', ?, 1, 0, NOW(), NOW())";
        $stmt = $mysqli->prepare($insert_sql);
        $stmt->bind_param("s", $new_hash);
        if ($stmt->execute()) {
            echo "✅ adminアカウントを作成しました\n";
            echo "  ID: admin\n";
            echo "  パスワード: {$test_password}\n";
        } else {
            echo "❌ アカウント作成失敗: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
    
    $mysqli->close();
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "ログイン情報:\n";
    echo "  URL: https://net8games.win/data/xxxadmin/login.php\n";
    echo "  ID: admin\n";
    echo "  パスワード: {$test_password}\n";
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>