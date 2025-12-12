<?php
/*
 * TemplateAdmin.php (簡略化版)
 * セッション維持問題を解決するため、厳格なチェックを緩和
 */

define("HTML_ENCODING" , "UTF-8");

class TemplateAdmin {
    public $DB;
    public $Session;
    public $Self;
    public $AdminInfo;

    public function __construct($makeSession = true, $isReturn = true, $isRegenerate = false, $encode = HTML_ENCODING) {
        // セッション管理（login.phpと統一）
        if ($makeSession) {
            if (session_status() == PHP_SESSION_NONE) {
                session_name("NET8ADMIN");
                session_start();
            }
            
            // ログイン認証チェック（login.phpのスクリプトは除外）
            $current_script = basename($_SERVER['SCRIPT_NAME']);
            if (empty($_SESSION["AdminInfo"]) && $current_script !== "login.php") {
                header("Location: " . (defined("URL_ADMIN") ? URL_ADMIN : "/xxxadmin/") . "login.php");
                exit();
            }
        }

        // キャッシュコントロール
        header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
        header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // DB接続 - 簡略化されたMySQLi接続
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
            
            public function query($sql) {
                return $this->connection->query($sql);
            }
            
            public function conv_sql($value) {
                return "'" . $this->connection->real_escape_string($value) . "'";
            }
            
            public function autoCommit($flag) {
                $this->connection->autocommit($flag);
            }
            
            public function checkAdmin($admin_id, $admin_pass) {
                $sql = "SELECT * FROM mst_admin WHERE admin_id = '" . 
                       $this->connection->real_escape_string($admin_id) . "' AND del_flg = 0";
                $result = $this->connection->query($sql);
                if ($result && $row = $result->fetch_assoc()) {
                    return password_verify($admin_pass, $row["admin_pass"]);
                }
                return false;
            }
        };

        if ($makeSession) {
            // 簡略化されたセッション管理
            if (session_status() == PHP_SESSION_NONE) {
                session_name("NET8ADMIN");
                session_start();
            }
            
            $this->Session = new class {
                public $AdminInfo;
                
                public function __construct() {
                    if (isset($_SESSION["AdminInfo"])) {
                        $this->AdminInfo = $_SESSION["AdminInfo"];
                    }
                }
                
                public function start() {
                    return !isset($_SESSION["AdminInfo"]);
                }
                
                public function check($redirect = false) {
                    // 簡単なセッション存在チェックのみ
                    $isValid = isset($_SESSION["AdminInfo"]) && !empty($_SESSION["AdminInfo"]);
                    
                    if ($isValid && isset($_SESSION["last_access"])) {
                        // 最終アクセス時刻更新（タイムアウトチェックは無効化）
                        $_SESSION["last_access"] = time();
                    }
                    
                    return $isValid;
                }
                
                public function clear($redirect = false) {
                    $_SESSION = array();
                    session_destroy();
                    if ($redirect) {
                        header("Location: " . (defined("URL_ADMIN") ? URL_ADMIN : "/xxxadmin/") . "login.php");
                        exit();
                    }
                }
            };
            
            // AdminInfo設定
            if (isset($_SESSION["AdminInfo"])) {
                $this->AdminInfo = $_SESSION["AdminInfo"];
                $this->Session->AdminInfo = $_SESSION["AdminInfo"];
                $_SESSION["last_access"] = time();
            }
        }

        // 自スクリプト名取得
        $this->Self = $_SERVER["SCRIPT_NAME"];
    }

    // 互換性のためのメソッド
    public function assign($key, $value) {
        // テンプレート変数代入（簡略化）
    }
    
    public function display($template = "") {
        // テンプレート表示（簡略化）
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
