<?php
/**
 * TemplateAdmin.php - 管理画面テンプレートクラス
 *
 * SmartSession統合版（2025-12-16）
 * - 通常ユーザーと同じSmartSessionクラスを使用
 * - セッション名: NET8ADMIN（管理画面専用）
 * - セッション継続時間: SESSION_SEC_ADMIN（3600秒）
 */

if (!defined("HTML_ENCODING")) {
    define("HTML_ENCODING", "UTF-8");
}

class TemplateAdmin {
    public $DB;
    public $Session;
    public $Self;
    public $AdminInfo;

    /**
     * コンストラクタ
     * @param bool $makeSession セッション管理を行うか
     * @param bool $isReturn セッションエラー時にリダイレクトするか
     * @param bool $isRegenerate セッションIDを再生成するか
     * @param string $encode 文字エンコーディング
     */
    public function __construct($makeSession = true, $isReturn = true, $isRegenerate = false, $encode = HTML_ENCODING) {
        // キャッシュコントロール
        header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
        header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // DB接続
        $this->initDatabase();

        // セッション管理
        if ($makeSession) {
            $this->initSession($isReturn);
        }

        // 自スクリプト名取得
        $this->Self = $_SERVER["SCRIPT_NAME"];
    }

    /**
     * データベース接続初期化
     */
    private function initDatabase() {
        $this->DB = new class {
            private $connection;

            public function __construct() {
                $host = defined("DB_HOST") ? DB_HOST : "136.116.70.86";
                $user = defined("DB_USER") ? DB_USER : "net8tech001";
                $pass = defined("DB_PASS") ? DB_PASS : "Nene11091108!!";
                $name = defined("DB_NAME") ? DB_NAME : "net8_dev";

                $this->connection = new mysqli($host, $user, $pass, $name);
                if ($this->connection->connect_error) {
                    throw new Exception("DB接続エラー: " . $this->connection->connect_error);
                }
                $this->connection->set_charset("utf8mb4");
            }

            public function getRow($sql, $fetchType = null) {
                $result = $this->connection->query($sql);
                if (!$result) return null;
                return $result->fetch_assoc();
            }

            public function getAll($sql, $fetchType = null) {
                $result = $this->connection->query($sql);
                if (!$result) return [];
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                return $data;
            }

            public function query($sql) {
                return $this->connection->query($sql);
            }

            public function conv_sql($value, $type = 1) {
                if ($type == 2) { // FD_NUM
                    return intval($value);
                } elseif ($type == 3) { // FD_RAW
                    return $value;
                }
                return "'" . $this->connection->real_escape_string($value) . "'";
            }

            public function autoCommit($flag) {
                $this->connection->autocommit($flag);
            }

            public function commit() {
                $this->connection->commit();
            }

            public function rollback() {
                $this->connection->rollback();
            }

            public function getInsertId() {
                return $this->connection->insert_id;
            }

            public function escape($value) {
                return $this->connection->real_escape_string($value);
            }
        };
    }

    /**
     * セッション初期化（SmartSession使用）
     * @param bool $isReturn セッションエラー時にリダイレクトするか
     */
    private function initSession($isReturn) {
        // セッション設定値
        $sessionSec = defined('SESSION_SEC_ADMIN') ? SESSION_SEC_ADMIN : 3600;
        $sessionSid = defined('SESSION_SID_ADMIN') ? SESSION_SID_ADMIN : 'NET8ADMIN';
        $domain = defined('DOMAIN') ? DOMAIN : $_SERVER["SERVER_NAME"];
        $adminUrl = defined('URL_ADMIN') ? URL_ADMIN : '/xxxadmin/';
        $loginUrl = $adminUrl . 'login.php';

        // SmartSessionインスタンス作成
        $this->Session = new SmartSession(
            $loginUrl,      // セッション消失時のリダイレクト先
            $sessionSec,    // セッション継続時間（秒）
            $sessionSid,    // セッション名
            $domain,        // ドメイン
            $isReturn       // セッションエラー時にリダイレクトするか
        );

        // セッション開始（既存セッションがあれば継続）
        $this->Session->start();

        // ログインページ以外ではセッションチェック
        $current_script = basename($_SERVER['SCRIPT_NAME']);
        if ($current_script !== "login.php") {
            // セッションチェック（AdminInfoの存在確認）
            if (!isset($this->Session->AdminInfo) || empty($this->Session->AdminInfo)) {
                if ($isReturn) {
                    header("Location: " . $loginUrl);
                    exit();
                }
            } else {
                // セッションタイムアウトチェック
                if (!$this->Session->check(false)) {
                    // タイムアウトした場合はクリアしてリダイレクト
                    if ($isReturn) {
                        $this->Session->clear(true);
                    }
                }
            }
        }

        // AdminInfo設定
        if (isset($this->Session->AdminInfo) && !empty($this->Session->AdminInfo)) {
            $this->AdminInfo = $this->Session->AdminInfo;
        }
    }

    /**
     * ログアウト処理
     * @param bool $redirect リダイレクトするか
     */
    public function logout($redirect = true) {
        if ($this->Session) {
            $this->Session->clear($redirect);
        }
    }

    /**
     * テンプレート変数代入（互換性用）
     */
    public function assign($key, $value, $htmlEncode = false) {
        // テンプレートエンジン用（必要に応じて実装）
    }

    /**
     * テンプレート表示（互換性用）
     */
    public function display($template = "") {
        // テンプレートエンジン用（必要に応じて実装）
    }

    /**
     * テンプレートファイルオープン（互換性用）
     */
    public function open($template) {
        // テンプレートエンジン用（必要に応じて実装）
    }

    /**
     * テンプレート出力（互換性用）
     */
    public function flush() {
        // テンプレートエンジン用（必要に応じて実装）
    }

    /**
     * 共通変数代入（互換性用）
     */
    public function assignCommon() {
        // テンプレートエンジン用（必要に応じて実装）
    }

    /**
     * 条件分岐表示（互換性用）
     */
    public function if_enable($key, $condition) {
        // テンプレートエンジン用（必要に応じて実装）
    }

    /**
     * エラー表示
     */
    public function dispProcError($message) {
        echo "<h1>エラー</h1><p>" . htmlspecialchars($message) . "</p>";
    }
}

// データ取得関数（互換性のため）
if (!function_exists("getData")) {
    function getData(&$data, $keys) {
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = "";
            }
        }
    }
}

// 自スクリプト名取得関数
if (!function_exists("get_self")) {
    function get_self() {
        return $_SERVER["SCRIPT_NAME"];
    }
}

// 基準時間オフセット計算関数
if (!function_exists("GetRefTimeOffsetStart")) {
    function GetRefTimeOffsetStart($offset = 0) {
        $refTime = defined('REFERENCE_TIME') ? REFERENCE_TIME : '04:00';
        $baseTime = strtotime(date('Y-m-d') . ' ' . $refTime);

        // 現在時刻が基準時刻より前の場合、前日扱い
        if (time() < $baseTime) {
            $baseTime = strtotime('-1 day', $baseTime);
        }

        // オフセット適用
        $targetTime = strtotime(($offset >= 0 ? '+' : '') . $offset . ' day', $baseTime);

        return date('Y/m/d H:i:s', $targetTime);
    }
}
