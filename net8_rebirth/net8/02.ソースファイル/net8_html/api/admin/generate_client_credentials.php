<?php
/**
 * NET8 SDK - Client Credentials Generator
 * お客様向けAPIキー・接続情報発行システム
 * Version: 1.0.1
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

// 簡易認証（本番では強化必須）
$admin_password = $_GET['auth'] ?? '';
if ($admin_password !== 'net8_admin_2025') {
    http_response_code(403);
    die('Access Denied');
}

$action = $_POST['action'] ?? 'form';
$client_name = $_POST['client_name'] ?? '';
$environment = $_POST['environment'] ?? 'test';
$rate_limit = (int)($_POST['rate_limit'] ?? 10000);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>NET8 SDK - Client Credentials Generator</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        button:hover {
            opacity: 0.9;
        }
        .result {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            border-left: 4px solid #667eea;
        }
        .credential {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
        .credential-label {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }
        .credential-value {
            font-family: 'Courier New', monospace;
            background: #f1f3f5;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
        }
        .download-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #51cf66;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .download-btn:hover {
            background: #40c057;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 NET8 SDK - Client Credentials Generator</h1>
        <p>お客様向けAPIキー・接続情報発行システム</p>

        <?php if ($action === 'form'): ?>

        <form method="POST">
            <input type="hidden" name="action" value="generate">

            <div class="form-group">
                <label for="client_name">お客様企業名 *</label>
                <input type="text" id="client_name" name="client_name" required
                       placeholder="例: 株式会社サンプル">
            </div>

            <div class="form-group">
                <label for="environment">環境 *</label>
                <select id="environment" name="environment">
                    <option value="test">テスト環境（test）- 実機不要</option>
                    <option value="staging">ステージング環境（staging）- 実機不要</option>
                    <option value="live">本番環境（live）- 実機接続</option>
                </select>
            </div>

            <div class="form-group">
                <label for="rate_limit">レート制限（回/日）</label>
                <input type="number" id="rate_limit" name="rate_limit" value="10000" min="100" max="1000000">
            </div>

            <button type="submit">🚀 APIキー発行</button>
        </form>

        <?php elseif ($action === 'generate' && !empty($client_name)): ?>

        <?php
        try {
            $pdo = get_db_connection();

            // APIキー生成
            $env_prefix = [
                'test' => 'pk_demo_',
                'staging' => 'pk_staging_',
                'live' => 'pk_live_'
            ][$environment];

            $api_key = $env_prefix . bin2hex(random_bytes(16));

            // データベースに保存
            $insertSql = "INSERT INTO api_keys (
                key_value,
                key_type,
                name,
                environment,
                rate_limit,
                is_active,
                expires_at
            ) VALUES (
                :key_value,
                'public',
                :name,
                :environment,
                :rate_limit,
                1,
                DATE_ADD(NOW(), INTERVAL 1 YEAR)
            )";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                'key_value' => $api_key,
                'name' => $client_name . ' - ' . ucfirst($environment),
                'environment' => $environment,
                'rate_limit' => $rate_limit
            ]);

            $api_key_id = $pdo->lastInsertId();

            // 接続情報生成
            $base_url = 'https://mgg-webservice-production.up.railway.app';
            $sdk_url = $base_url . '/sdk/net8-sdk-beta.js';
            $demo_url = $base_url . '/sdk/demo.html';

            $is_mock = ($environment === 'test' || $environment === 'staging');

            ?>

            <div class="result">
                <h2>✅ APIキー発行完了</h2>
                <p>お客様: <strong><?php echo htmlspecialchars($client_name); ?></strong></p>

                <div class="credential">
                    <div class="credential-label">APIキー</div>
                    <div class="credential-value"><?php echo $api_key; ?></div>
                </div>

                <div class="credential">
                    <div class="credential-label">環境</div>
                    <div class="credential-value">
                        <?php echo strtoupper($environment); ?>
                        <?php if ($is_mock): ?>
                        <br><small style="color: #51cf66;">✓ モックモード（実機不要）</small>
                        <?php else: ?>
                        <br><small style="color: #ff6b6b;">⚠ 本番環境（実機接続必要）</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="credential">
                    <div class="credential-label">レート制限</div>
                    <div class="credential-value"><?php echo number_format($rate_limit); ?> リクエスト/日</div>
                </div>

                <div class="credential">
                    <div class="credential-label">有効期限</div>
                    <div class="credential-value">1年間（<?php echo date('Y-m-d', strtotime('+1 year')); ?>まで）</div>
                </div>

                <div class="credential">
                    <div class="credential-label">SDK URL</div>
                    <div class="credential-value"><?php echo $sdk_url; ?></div>
                </div>

                <div class="credential">
                    <div class="credential-label">デモURL</div>
                    <div class="credential-value"><?php echo $demo_url; ?></div>
                </div>

                <h3 style="margin-top: 30px;">📋 次のステップ</h3>
                <ol>
                    <li>下記ボタンから「お客様向け接続ガイド」をダウンロード</li>
                    <li>お客様に接続ガイドとAPIキーを送付</li>
                    <li>お客様が統合テストを実施</li>
                    <?php if ($environment === 'test' || $environment === 'staging'): ?>
                    <li>テスト完了後、本番環境APIキーを発行</li>
                    <?php else: ?>
                    <li>本番サービス開始</li>
                    <?php endif; ?>
                </ol>

                <a href="generate_client_guide.php?auth=net8_admin_2025&key_id=<?php echo $api_key_id; ?>"
                   class="download-btn" target="_blank">
                    📥 お客様向け接続ガイドを生成
                </a>

                <a href="?auth=net8_admin_2025" class="download-btn" style="background: #667eea;">
                    ⬅️ 新規発行に戻る
                </a>
            </div>

        <?php
        } catch (Exception $e) {
            echo '<div class="result" style="border-color: #ff6b6b;">';
            echo '<h2>❌ エラー</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>

        <?php endif; ?>

        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>📚 管理者向けリンク</h3>
            <ul>
                <li><a href="list_clients.php?auth=net8_admin_2025">発行済みAPIキー一覧</a></li>
                <li><a href="../setup_keys_direct.php">データベースセットアップ</a></li>
                <li><a href="../../sdk/demo.html" target="_blank">SDK デモページ</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
