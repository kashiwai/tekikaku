<?php
/**
 * 緊急adminログイン修正
 * パスワード: admin123
 */

// DB接続
$host = '136.116.70.86';
$db   = 'net8_dev';
$user = 'net8tech001';
$pass = 'Nene11091108!!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 新しいパスワードをハッシュ化（admin123）
    $new_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // 既存のadminアカウントを一旦削除
    $pdo->exec("DELETE FROM mst_admin WHERE admin_id = 'admin'");
    
    // 新しいadminアカウントを作成
    $sql = "INSERT INTO mst_admin (
        admin_name, admin_id, admin_pass, auth_flg, del_flg, add_dt, upd_dt
    ) VALUES (
        '管理者', 'admin', :password, 1, 0, NOW(), NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['password' => $new_password]);
    
    echo "✅ adminアカウントを作成しました\n";
    echo "ID: admin\n";
    echo "パスワード: admin123\n";
    echo "URL: http://localhost:8080/data/xxxadmin/login.php\n";
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}