<?php
/**
 * api_keysテーブルにパートナー情報とPDF関連カラムを追加
 * Version: 1.0.0
 * Created: 2025-11-24
 */

// GCP Cloud SQL接続情報
$host = '136.116.70.86';
$port = 3306;
$dbname = 'net8_dev';
$username = 'net8_admin';
$password = 'Vm3i55gqDJd21x9kkE9ahiI6';

try {
    echo "🚀 データベースに接続中...\n";

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ データベース接続成功\n\n";

    // 既存カラムを確認
    echo "📋 現在のapi_keysテーブル構造を確認中...\n";
    $stmt = $pdo->query("DESCRIBE api_keys");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "既存カラム:\n";
    foreach ($existingColumns as $col) {
        echo "  - {$col}\n";
    }
    echo "\n";

    // 追加するカラムのリスト
    $columnsToAdd = [
        'partner_company_name' => "VARCHAR(255) NULL COMMENT 'パートナー企業名'",
        'partner_contact_name' => "VARCHAR(255) NULL COMMENT '担当者名'",
        'partner_email' => "VARCHAR(255) NULL COMMENT 'メールアドレス'",
        'partner_phone' => "VARCHAR(50) NULL COMMENT '電話番号'",
        'pdf_generated' => "TINYINT(1) DEFAULT 0 COMMENT 'PDF発行済みフラグ'",
        'pdf_filename' => "VARCHAR(255) NULL COMMENT 'PDF ファイル名'",
        'pdf_generated_at' => "DATETIME NULL COMMENT 'PDF発行日時'"
    ];

    echo "🔧 カラムを追加中...\n\n";

    foreach ($columnsToAdd as $columnName => $definition) {
        if (in_array($columnName, $existingColumns)) {
            echo "⏭️  {$columnName} は既に存在します（スキップ）\n";
            continue;
        }

        $sql = "ALTER TABLE api_keys ADD COLUMN {$columnName} {$definition}";

        try {
            $pdo->exec($sql);
            echo "✅ {$columnName} を追加しました\n";
        } catch (PDOException $e) {
            echo "❌ {$columnName} の追加に失敗: " . $e->getMessage() . "\n";
        }
    }

    echo "\n📋 更新後のテーブル構造:\n";
    $stmt = $pdo->query("DESCRIBE api_keys");
    $updatedColumns = $stmt->fetchAll();

    foreach ($updatedColumns as $col) {
        echo sprintf(
            "  - %-30s %-20s %s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }

    echo "\n✅ 完了！パートナー情報カラムの追加が成功しました。\n";

} catch (PDOException $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    exit(1);
}
