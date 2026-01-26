<?php
/**
 * 管理画面用require files
 * Created: 2025-12-12
 */

// エラー表示設定
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0'); // 本番環境では0に設定

// パス設定
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// 基本的なrequire_filesを読み込み
if (file_exists(__DIR__ . '/require_files.php')) {
    require_once(__DIR__ . '/require_files.php');
} else {
    // require_files.phpが見つからない場合、最小限の設定
    
    // データベース接続設定
    if (!defined('DB_HOST')) {
        define('DB_HOST', '136.116.70.86');
        define('DB_USER', 'net8tech001');
        define('DB_PASS', 'Nene11091108!!');
        define('DB_NAME', 'net8_dev');
        define('DB_PORT', 3306);
    }
    
    // 基本定数
    if (!defined('FD_TEXT')) {
        define('FD_TEXT', 1);
        define('FD_NUM', 2);
        define('FD_RAW', 3);
    }
}

// クラスファイルの読み込み
$lib_paths = [
    ROOT_PATH . '/data/_lib/',
    ROOT_PATH . '/_lib/',
];

// 必要なクラスファイル
$required_classes = [
    'Database.class.php',
    'SmartDB_MySQL.class.php',
    'SqlString.class.php',
    'Session.class.php',
    'TemplateAdmin.class.php',
];

foreach ($lib_paths as $lib_path) {
    foreach ($required_classes as $class_file) {
        $file_path = $lib_path . $class_file;
        if (file_exists($file_path)) {
            require_once($file_path);
        }
    }
}

// TemplateAdminクラスが存在しない場合、簡易版を定義
if (!class_exists('TemplateAdmin')) {
    class TemplateAdmin {
        public $DB;
        public $Session;
        public $AdminInfo;
        
        public function __construct() {
            // データベース接続
            $this->DB = new SmartDB_MySQL();
            $this->DB->connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            // セッション管理
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // 簡易セッション管理
            $this->Session = new stdClass();
            if (isset($_SESSION['AdminInfo'])) {
                $this->Session->AdminInfo = $_SESSION['AdminInfo'];
                $this->AdminInfo = $_SESSION['AdminInfo'];
            }
        }
    }
}

// SmartDB_MySQLクラスが存在しない場合、簡易版を定義
if (!class_exists('SmartDB_MySQL')) {
    class SmartDB_MySQL {
        private $connection;
        
        public function connect($host, $user, $pass, $db, $port = 3306) {
            $this->connection = new mysqli($host, $user, $pass, $db, $port);
            if ($this->connection->connect_error) {
                die("Connection failed: " . $this->connection->connect_error);
            }
            $this->connection->set_charset("utf8mb4");
        }
        
        public function query($sql) {
            return $this->connection->query($sql);
        }
        
        public function getAll($sql) {
            $result = $this->query($sql);
            if (!$result) {
                return [];
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        }
        
        public function conv_sql($value, $type = FD_TEXT) {
            if ($type == FD_TEXT) {
                return "'" . $this->connection->real_escape_string($value) . "'";
            } elseif ($type == FD_NUM) {
                return intval($value);
            } elseif ($type == FD_RAW) {
                return $value;
            }
            return "'" . $this->connection->real_escape_string($value) . "'";
        }
        
        public function getInsertId() {
            return $this->connection->insert_id;
        }
    }
}

// SqlStringクラスが存在しない場合、簡易版を定義
if (!class_exists('SqlString')) {
    class SqlString {
        private $sql = '';
        private $db = null;
        
        public function setAutoConvert($db_array) {
            if (is_array($db_array) && count($db_array) >= 2) {
                $this->db = $db_array[0];
            }
            return $this;
        }
        
        public function insert($table) {
            $this->sql = "INSERT INTO $table ";
            return $this;
        }
        
        public function values($data) {
            $columns = [];
            $values = [];
            
            foreach ($data as $column => $value_array) {
                $columns[] = $column;
                if (is_array($value_array)) {
                    $value = $value_array[0];
                    $type = $value_array[1] ?? FD_TEXT;
                    
                    if ($type == FD_RAW) {
                        $values[] = $value;
                    } elseif ($type == FD_NUM) {
                        $values[] = intval($value);
                    } else {
                        if ($this->db) {
                            $values[] = $this->db->conv_sql($value, FD_TEXT);
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                } else {
                    $values[] = "'" . addslashes($value_array) . "'";
                }
            }
            
            $this->sql .= "(" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ")";
            return $this;
        }
        
        public function createSQL($separator = '') {
            return $this->sql;
        }
    }
}

// セッション開始（まだ開始されていない場合）
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>