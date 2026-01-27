<?php
/**
 * パートナードメイン管理画面
 * APIキーごとにiFrame埋め込み許可ドメインを管理
 *
 * Version: 1.0.0
 * Created: 2025-11-18
 */

// インクルード
require_once('../../_etc/require_files_admin.php');

// PDO接続を初期化
$pdo = null;
function getDbConnection() {
    global $pdo;
    if ($pdo === null) {
        $host = defined("DB_HOST") ? DB_HOST : "136.116.70.86";
        $user = defined("DB_USER") ? DB_USER : "net8tech001";
        $pass = defined("DB_PASS") ? DB_PASS : "Nene11091108!!";
        $name = defined("DB_NAME") ? DB_NAME : "net8_dev";

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $pdo;
}

// メイン処理
main();

/**
 * メイン処理
 */
function main() {
    try {
        $template = new TemplateAdmin();

        // データ取得
        getData($_GET, array("M"));

        // 実処理
        $mainWin = true;
        $subWin = false;

        // ビューの振り分け
        if (isset($_POST['mode'])) {
            switch ($_POST['mode']) {
                case 'add_domain':
                    addDomain();
                    break;
                case 'remove_domain':
                    removeDomain();
                    break;
                case 'update_domains':
                    updateDomains();
                    break;
            }
        }

        // APIキー一覧取得
        $apiKeys = getApiKeysWithDomains();

        // テンプレートに値を設定
        $template->setTplVal("API_KEYS", $apiKeys);

        // 表示処理
        $template->setSubDir("admin");
        $template->makeHtmlBody("partner_domains");
        $template->displayHtmlBody();

    } catch (Exception $e) {
        error_log("Partner Domains Error: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * APIキー一覧とドメイン情報を取得
 */
function getApiKeysWithDomains() {
    $pdo = getDbConnection();

    $sql = "SELECT
                id,
                key_name,
                key_value,
                environment,
                allowed_domains,
                is_active,
                created_at,
                last_used_at
            FROM api_keys
            ORDER BY created_at DESC";

    $stmt = $pdo->query($sql);
    $apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // JSONをデコード
    foreach ($apiKeys as &$key) {
        $key['domains'] = json_decode($key['allowed_domains'] ?? '[]', true);
        if (!is_array($key['domains'])) {
            $key['domains'] = [];
        }
    }

    return $apiKeys;
}

/**
 * ドメイン追加
 */
function addDomain() {
    $pdo = getDbConnection();

    $apiKeyId = $_POST['api_key_id'] ?? null;
    $domain = $_POST['domain'] ?? '';

    if (!$apiKeyId || empty($domain)) {
        throw new Exception('APIキーIDとドメインは必須です');
    }

    // ドメインのバリデーション
    $domain = trim($domain);
    if (!isValidDomain($domain)) {
        throw new Exception('無効なドメイン形式です');
    }

    // 既存のドメイン取得
    $stmt = $pdo->prepare("SELECT allowed_domains FROM api_keys WHERE id = :id");
    $stmt->execute(['id' => $apiKeyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('APIキーが見つかりません');
    }

    $domains = json_decode($row['allowed_domains'] ?? '[]', true);
    if (!is_array($domains)) {
        $domains = [];
    }

    // 重複チェック
    if (in_array($domain, $domains)) {
        throw new Exception('このドメインは既に登録されています');
    }

    // ドメイン追加
    $domains[] = $domain;

    // 更新
    $stmt = $pdo->prepare("UPDATE api_keys SET allowed_domains = :domains WHERE id = :id");
    $stmt->execute([
        'domains' => json_encode($domains),
        'id' => $apiKeyId
    ]);

    // 成功レスポンス
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'ドメインを追加しました',
        'domains' => $domains
    ]);
    exit;
}

/**
 * ドメイン削除
 */
function removeDomain() {
    $pdo = getDbConnection();

    $apiKeyId = $_POST['api_key_id'] ?? null;
    $domain = $_POST['domain'] ?? '';

    if (!$apiKeyId || empty($domain)) {
        throw new Exception('APIキーIDとドメインは必須です');
    }

    // 既存のドメイン取得
    $stmt = $pdo->prepare("SELECT allowed_domains FROM api_keys WHERE id = :id");
    $stmt->execute(['id' => $apiKeyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('APIキーが見つかりません');
    }

    $domains = json_decode($row['allowed_domains'] ?? '[]', true);
    if (!is_array($domains)) {
        $domains = [];
    }

    // ドメイン削除
    $domains = array_values(array_filter($domains, function($d) use ($domain) {
        return $d !== $domain;
    }));

    // 更新
    $stmt = $pdo->prepare("UPDATE api_keys SET allowed_domains = :domains WHERE id = :id");
    $stmt->execute([
        'domains' => json_encode($domains),
        'id' => $apiKeyId
    ]);

    // 成功レスポンス
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'ドメインを削除しました',
        'domains' => $domains
    ]);
    exit;
}

/**
 * ドメインバリデーション
 */
function isValidDomain($domain) {
    // http:// または https:// で始まる必要がある
    if (!preg_match('/^https?:\/\//', $domain)) {
        return false;
    }

    // URLとして妥当かチェック
    $parsed = parse_url($domain);
    if (!$parsed || !isset($parsed['host'])) {
        return false;
    }

    return true;
}

// getData関数はSmartGeneral.phpで定義済みのため、ここでは定義しない
