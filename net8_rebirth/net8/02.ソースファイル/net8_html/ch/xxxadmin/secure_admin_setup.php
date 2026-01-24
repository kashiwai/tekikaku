<?php
/**
 * 本番環境用セキュアadminアカウント設定
 * より安全なパスワードに変更
 */

// DB接続
$host = '136.116.70.86';
$db   = 'net8_dev';
$user = 'net8tech001';
$pass = 'Nene11091108!!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // より強力なパスワードを生成
    $secure_password = 'Net8Admin@2024#Secure';
    $hashed_password = password_hash($secure_password, PASSWORD_DEFAULT);
    
    // adminアカウントを更新
    $sql = "UPDATE mst_admin SET 
            admin_pass = :password,
            upd_dt = NOW()
            WHERE admin_id = 'admin' 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['password' => $hashed_password]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ adminアカウントのパスワードを更新しました\n\n";
        echo "=== 本番環境アクセス情報 ===\n";
        echo "URL: https://mgg-webservice-production-final.up.railway.app/data/xxxadmin/login.php\n";
        echo "ID: admin\n";
        echo "パスワード: Net8Admin@2024#Secure\n\n";
        echo "カスタムドメイン: https://net8games.win/data/xxxadmin/login.php\n";
    } else {
        echo "⚠️ adminアカウントが見つかりません。作成します...\n";
        
        // adminアカウントを新規作成
        $sql = "INSERT INTO mst_admin (
            admin_name, admin_id, admin_pass, auth_flg, del_flg, add_dt, upd_dt
        ) VALUES (
            '管理者', 'admin', :password, 1, 0, NOW(), NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['password' => $hashed_password]);
        
        echo "✅ adminアカウントを作成しました\n\n";
        echo "=== 本番環境アクセス情報 ===\n";
        echo "URL: https://mgg-webservice-production-final.up.railway.app/data/xxxadmin/login.php\n";
        echo "ID: admin\n";
        echo "パスワード: Net8Admin@2024#Secure\n\n";
        echo "カスタムドメイン: https://net8games.win/data/xxxadmin/login.php\n";
    }
    
    echo "\n⚠️ 重要: このパスワードは安全に保管してください\n";
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}