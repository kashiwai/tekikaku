<?php
/**
 * 管理画面ファイル存在チェックスクリプト
 * index.phpから参照される全PHPファイルの存在確認
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>管理画面ファイルチェック</title>";
echo "<style>
    body { font-family: sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .exists { color: green; font-weight: bold; }
    .not-exists { color: red; font-weight: bold; }
    .created { color: blue; font-weight: bold; }
    h2 { color: #333; border-bottom: 2px solid #333; padding-bottom: 5px; }
</style></head><body>";

echo "<h1>🔍 NET8 管理画面ファイル存在チェック</h1>";

// チェック対象ファイルリスト（index.phpから抽出）
$adminFiles = [
    // メインメニュー
    'index.php' => 'ダッシュボード（TOP）',
    'menu.php' => 'メニュー一覧',
    'logout.php' => 'ログアウト',
    
    // 会員管理
    'member.php' => '会員管理',
    'memberplayhistory.php' => '会員プレイ履歴',
    'owner.php' => 'オーナー管理',
    'admin.php' => '管理者管理',
    
    // マシン管理
    'machines.php' => 'マシン管理',
    'model.php' => 'モデル管理',
    'maker.php' => 'メーカー管理',
    'corner.php' => 'コーナー管理',
    'machine_control.php' => 'マシン制御',
    'moniter.php' => 'モニター',
    
    // カメラ・配信
    'camera.php' => 'カメラ管理',
    'camera_settings.php' => 'カメラ設定',
    'signaling.php' => 'シグナリング',
    'streaming.php' => 'ストリーミング',
    
    // ポイント管理
    'pointgrant.php' => 'ポイント付与',
    'pointhistory.php' => 'ポイント履歴',
    'pointconvert.php' => 'ポイント変換',
    
    // 売上・購入管理
    'sales.php' => '売上管理',
    'purchase.php' => '購入管理',
    'purchasehistory.php' => '購入履歴',
    
    // プレイ履歴
    'playhistory.php' => 'プレイ履歴',
    'search.php' => '検索',
    
    // 商品管理
    'goods.php' => '商品管理',
    'goods_status.php' => '商品ステータス',
    'goods_drawpick.php' => '商品抽選',
    'drawhistory.php' => '抽選履歴',
    'gift.php' => 'ギフト管理',
    'gifthistory.php' => 'ギフト履歴',
    'giftaddset.php' => 'ギフト追加設定',
    'giftlimit.php' => 'ギフト制限',
    'shipping.php' => '配送管理',
    
    // お知らせ・マーケティング
    'notice.php' => 'お知らせ管理',
    'magazine.php' => 'マガジン管理',
    'coupon.php' => 'クーポン管理',
    'benefits.php' => '特典管理',
    'address.php' => 'アドレス管理',
    
    // システム管理
    'system.php' => 'システム設定',
    'image_upload.php' => '画像アップロード',
    'api_keys_manage.php' => 'APIキー管理',
    
    // 認証関連
    'login.php' => 'ログイン画面'
];

// 現在のディレクトリ
$currentDir = __DIR__ . '/data/xxxadmin/';

echo "<h2>📁 チェック対象ディレクトリ</h2>";
echo "<p><code>" . htmlspecialchars($currentDir) . "</code></p>";

echo "<h2>📋 ファイル存在チェック結果</h2>";
echo "<table>";
echo "<tr><th>No.</th><th>ファイル名</th><th>機能</th><th>存在</th><th>サイズ</th><th>最終更新</th></tr>";

$notExistsFiles = [];
$existsCount = 0;
$notExistsCount = 0;
$i = 1;

foreach ($adminFiles as $file => $description) {
    $filePath = $currentDir . $file;
    echo "<tr>";
    echo "<td>{$i}</td>";
    echo "<td>{$file}</td>";
    echo "<td>{$description}</td>";
    
    if (file_exists($filePath)) {
        $existsCount++;
        $size = filesize($filePath);
        $modified = date('Y-m-d H:i:s', filemtime($filePath));
        echo "<td class='exists'>✅ 存在</td>";
        echo "<td>" . number_format($size) . " bytes</td>";
        echo "<td>{$modified}</td>";
    } else {
        $notExistsCount++;
        $notExistsFiles[] = ['file' => $file, 'description' => $description];
        echo "<td class='not-exists'>❌ 不存在</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
    }
    echo "</tr>";
    $i++;
}

echo "</table>";

// サマリー表示
echo "<h2>📊 チェック結果サマリー</h2>";
echo "<table>";
echo "<tr><th>項目</th><th>数</th></tr>";
echo "<tr><td>✅ 存在するファイル</td><td>{$existsCount}</td></tr>";
echo "<tr><td>❌ 存在しないファイル</td><td>{$notExistsCount}</td></tr>";
echo "<tr><td>合計チェックファイル</td><td>" . count($adminFiles) . "</td></tr>";
echo "</table>";

// 存在しないファイルのリスト
if ($notExistsCount > 0) {
    echo "<h2>⚠️ 存在しないファイル一覧</h2>";
    echo "<div style='background-color: #fee; padding: 15px; border-radius: 5px;'>";
    echo "<p>以下のファイルが存在しません。作成が必要です：</p>";
    echo "<ul>";
    foreach ($notExistsFiles as $missing) {
        echo "<li><strong>{$missing['file']}</strong> - {$missing['description']}</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 基本テンプレート作成オファー
    echo "<h3>🔧 基本テンプレート作成</h3>";
    echo "<p>存在しないファイルの基本テンプレートを作成しますか？</p>";
    echo "<form method='POST' action='create_admin_templates.php'>";
    echo "<input type='hidden' name='create' value='1'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "基本テンプレートを作成する";
    echo "</button>";
    echo "</form>";
}

// 関連ファイルチェック
echo "<h2>🔧 関連システムファイル</h2>";
$systemFiles = [
    '../../_etc/require_files_admin.php' => 'Admin共通設定',
    '../../_sys/TemplateAdmin.php' => 'Adminテンプレート',
    '../../_lib/SmartDB_MySQL.php' => 'DB接続クラス',
    '../../_lib/SqlString.php' => 'SQLビルダー',
    '../../_lib/SmartAutoCheck.php' => '入力チェック',
    'assets/admin_modern.css' => 'CSS（モダンデザイン）'
];

echo "<table>";
echo "<tr><th>ファイル</th><th>説明</th><th>存在</th></tr>";

foreach ($systemFiles as $file => $description) {
    $filePath = $currentDir . $file;
    echo "<tr>";
    echo "<td>{$file}</td>";
    echo "<td>{$description}</td>";
    if (file_exists($filePath)) {
        echo "<td class='exists'>✅ 存在</td>";
    } else {
        echo "<td class='not-exists'>❌ 不存在</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<h2>🚀 アクション</h2>";
echo "<div style='background-color: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<h3>管理画面へのアクセス</h3>";
echo "<p><a href='/xxxadmin/login.php' target='_blank'>ログイン画面を開く →</a></p>";
echo "<p><a href='/xxxadmin/index.php' target='_blank'>ダッシュボードを開く →</a></p>";
echo "</div>";

echo "</body></html>";
?>