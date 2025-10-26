<?php
/**
 * NET8 License Configuration
 *
 * ライセンスコード管理ファイル
 */

// ライセンス情報
define('LICENSE_CODE', getenv('LICENSE_CODE') ?: '');
define('LICENSE_TYPE', getenv('LICENSE_TYPE') ?: 'trial'); // trial, standard, premium, enterprise
define('LICENSE_EXPIRY', getenv('LICENSE_EXPIRY') ?: ''); // YYYY-MM-DD形式
define('LICENSE_MAX_USERS', getenv('LICENSE_MAX_USERS') ?: '10');
define('LICENSE_MAX_CAMERAS', getenv('LICENSE_MAX_CAMERAS') ?: '10');

// ライセンス機能制限
define('FEATURE_API_ACCESS', getenv('FEATURE_API_ACCESS') === 'true' ? true : false);
define('FEATURE_ADVANCED_ANALYTICS', getenv('FEATURE_ADVANCED_ANALYTICS') === 'true' ? true : false);
define('FEATURE_CLOUD_STORAGE', getenv('FEATURE_CLOUD_STORAGE') === 'true' ? true : false);
define('FEATURE_MULTI_SITE', getenv('FEATURE_MULTI_SITE') === 'true' ? true : false);

// ライセンス検証設定
define('LICENSE_CHECK_ENABLED', getenv('LICENSE_CHECK_ENABLED') === 'true' ? true : false);
define('LICENSE_SERVER_URL', getenv('LICENSE_SERVER_URL') ?: '');

/**
 * ライセンス有効性チェック
 *
 * @return bool ライセンスが有効な場合true
 */
function isLicenseValid() {
    if (!LICENSE_CHECK_ENABLED) {
        return true;
    }

    // ライセンスコードが空の場合は無効
    if (empty(LICENSE_CODE)) {
        return false;
    }

    // 有効期限チェック
    if (!empty(LICENSE_EXPIRY)) {
        $expiry = strtotime(LICENSE_EXPIRY);
        if ($expiry && $expiry < time()) {
            return false;
        }
    }

    return true;
}

/**
 * ライセンス制限チェック
 *
 * @param string $feature 機能名
 * @return bool 機能が利用可能な場合true
 */
function isFeatureEnabled($feature) {
    $featureMap = [
        'api_access' => FEATURE_API_ACCESS,
        'advanced_analytics' => FEATURE_ADVANCED_ANALYTICS,
        'cloud_storage' => FEATURE_CLOUD_STORAGE,
        'multi_site' => FEATURE_MULTI_SITE,
    ];

    return isset($featureMap[$feature]) ? $featureMap[$feature] : false;
}
