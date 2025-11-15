<?php
/**
 * CloudStorageHelper.php
 *
 * Google Cloud Storage 統合ヘルパー
 *
 * @package NET8
 * @author  System
 * @version 1.0
 * @since   2025/11/14
 */

use Google\Cloud\Storage\StorageClient;

class CloudStorageHelper {
    private $storage;
    private $bucket;
    private $bucketName;
    private $enabled;

    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->enabled = defined('GCS_ENABLED') && GCS_ENABLED;

        if ($this->enabled) {
            try {
                // バケット名
                $this->bucketName = getenv('GCS_BUCKET_NAME') ?: 'avamodb-net8-images';

                // Storage Client初期化
                $storageConfig = [
                    'projectId' => getenv('GCS_PROJECT_ID') ?: 'avamodb'
                ];

                // GCS_KEY_JSON環境変数が設定されている場合はJSONから読み込み
                $keyJson = getenv('GCS_KEY_JSON');
                if (!empty($keyJson)) {
                    // JSON文字列をデコード
                    $keyData = json_decode($keyJson, true);
                    if ($keyData) {
                        $storageConfig['keyFile'] = $keyData;
                    } else {
                        throw new Exception('GCS_KEY_JSON is not valid JSON');
                    }
                } else {
                    // ファイルパスから読み込み
                    $keyFilePath = getenv('GCS_KEY_FILE') ?: __DIR__ . '/../_etc/gcs-key.json';
                    $storageConfig['keyFilePath'] = $keyFilePath;
                }

                $this->storage = new StorageClient($storageConfig);
                $this->bucket = $this->storage->bucket($this->bucketName);

            } catch (Exception $e) {
                error_log('GCS初期化エラー: ' . $e->getMessage());
                $this->enabled = false;
            }
        }
    }

    /**
     * Cloud Storage が有効か確認
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * ファイルをアップロード
     *
     * @param string $localPath ローカルファイルパス
     * @param string $folder フォルダ名（models, machines, banners）
     * @param string $filename ファイル名
     * @return string|false アップロードされたファイルの公開URL、失敗時はfalse
     */
    public function upload($localPath, $folder, $filename) {
        if (!$this->enabled) {
            return false;
        }

        try {
            // GCS内のパス
            $objectName = "{$folder}/{$filename}";

            // ファイルをアップロード
            $object = $this->bucket->upload(
                fopen($localPath, 'r'),
                [
                    'name' => $objectName,
                    'predefinedAcl' => 'publicRead', // 公開設定
                    'metadata' => [
                        'cacheControl' => 'public, max-age=31536000', // 1年キャッシュ
                    ]
                ]
            );

            // 公開URL生成
            $publicUrl = "https://storage.googleapis.com/{$this->bucketName}/{$objectName}";

            return $publicUrl;

        } catch (Exception $e) {
            error_log('GCSアップロードエラー: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ファイルを削除
     *
     * @param string $url 公開URL
     * @return bool 成功時true
     */
    public function delete($url) {
        if (!$this->enabled) {
            return false;
        }

        try {
            // URLからオブジェクト名を抽出
            $pattern = "/https:\/\/storage\.googleapis\.com\/{$this->bucketName}\/(.*)/";
            if (preg_match($pattern, $url, $matches)) {
                $objectName = $matches[1];
                $object = $this->bucket->object($objectName);
                $object->delete();
                return true;
            }
            return false;

        } catch (Exception $e) {
            error_log('GCS削除エラー: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ファイルが存在するか確認
     *
     * @param string $url 公開URL
     * @return bool 存在する場合true
     */
    public function exists($url) {
        if (!$this->enabled) {
            return false;
        }

        try {
            $pattern = "/https:\/\/storage\.googleapis\.com\/{$this->bucketName}\/(.*)/";
            if (preg_match($pattern, $url, $matches)) {
                $objectName = $matches[1];
                $object = $this->bucket->object($objectName);
                return $object->exists();
            }
            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * バケット内のファイル一覧を取得
     *
     * @param string $folder フォルダ名
     * @return array ファイル情報の配列
     */
    public function listFiles($folder = '') {
        if (!$this->enabled) {
            return [];
        }

        try {
            $options = [];
            if (!empty($folder)) {
                $options['prefix'] = $folder . '/';
            }

            $objects = $this->bucket->objects($options);
            $files = [];

            foreach ($objects as $object) {
                $files[] = [
                    'name' => $object->name(),
                    'size' => $object->info()['size'],
                    'contentType' => $object->info()['contentType'],
                    'updated' => $object->info()['updated'],
                    'publicUrl' => "https://storage.googleapis.com/{$this->bucketName}/{$object->name()}"
                ];
            }

            return $files;

        } catch (Exception $e) {
            error_log('GCSリスト取得エラー: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * サムネイルを生成してアップロード
     *
     * @param string $sourcePath 元画像パス
     * @param string $folder フォルダ名
     * @param string $filename ファイル名
     * @param int $maxWidth 最大幅（デフォルト300px）
     * @return string|false サムネイルの公開URL、失敗時はfalse
     */
    public function uploadThumbnail($sourcePath, $folder, $filename, $maxWidth = 300) {
        if (!$this->enabled) {
            return false;
        }

        try {
            // 画像情報取得
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                return false;
            }

            list($width, $height, $type) = $imageInfo;

            // リサイズ比率計算
            if ($width <= $maxWidth) {
                // リサイズ不要
                return $this->upload($sourcePath, "thumbnails/{$folder}", $filename);
            }

            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = (int)($height * $ratio);

            // 元画像読み込み
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($sourcePath);
                    break;
                case IMAGETYPE_WEBP:
                    $source = imagecreatefromwebp($sourcePath);
                    break;
                default:
                    return false;
            }

            // サムネイル作成
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // 透過対応（PNG/GIF用）
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }

            imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // 一時ファイルに保存
            $tempFile = sys_get_temp_dir() . '/' . uniqid('thumb_') . '.jpg';
            imagejpeg($thumbnail, $tempFile, 85);

            imagedestroy($source);
            imagedestroy($thumbnail);

            // GCSにアップロード
            $url = $this->upload($tempFile, "thumbnails/{$folder}", $filename);

            // 一時ファイル削除
            @unlink($tempFile);

            return $url;

        } catch (Exception $e) {
            error_log('サムネイル生成エラー: ' . $e->getMessage());
            return false;
        }
    }
}
?>
