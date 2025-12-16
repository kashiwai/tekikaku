<?php
/**
 * 管理画面用require files
 * Created: 2025-12-12
 * Updated: 2025-12-16 - SmartSession統合版（セッション継続問題解決）
 */

// 出力バッファリング開始（headers already sentエラー防止）
ob_start();

// エラー表示設定
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0'); // 本番環境では0に設定

// セッション保存パスの設定（Railway環境対応）
// session.save_pathが未設定または書き込み不可の場合、/tmpを使用
$sessionPath = ini_get('session.save_path');
if (empty($sessionPath) || !is_writable($sessionPath)) {
    ini_set('session.save_path', '/tmp');
}

// パス設定
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// 基本的なrequire_filesを読み込み（絶対パスで確実に）
$require_files_path = __DIR__ . '/require_files.php';
if (file_exists($require_files_path)) {
    require_once($require_files_path);
} else {
    // フォールバック: setting.phpを直接読み込む
    $setting_path = __DIR__ . '/setting.php';
    if (file_exists($setting_path)) {
        require_once($setting_path);
    }

    // require_files.phpが見つからない場合、最小限の設定
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
$sys_path = __DIR__ . '/../_sys/';
$lib_path = __DIR__ . '/../_lib/';

// SmartSessionを最優先で読み込み（セッション統合のため必須）
if (file_exists($lib_path . 'SmartSession.php')) {
    require_once($lib_path . 'SmartSession.php');
}

// TemplateAdmin.php を _sys/ から読み込み
if (file_exists($sys_path . 'TemplateAdmin.php')) {
    require_once($sys_path . 'TemplateAdmin.php');
}

// その他のクラスファイル
$lib_paths = [
    ROOT_PATH . '/data/_lib/',
    ROOT_PATH . '/_lib/',
    $lib_path,
];

// 必要なクラスファイル
$required_classes = [
    'Database.class.php',
    'SmartDB_MySQL.class.php',
    'SqlString.class.php',
];

foreach ($lib_paths as $path) {
    foreach ($required_classes as $class_file) {
        $file_path = $path . $class_file;
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
            // データベース接続 (DB_PASSWORDとDB_PASSの両方に対応)
            $this->DB = new SmartDB_MySQL();
            $password = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : '');
            $this->DB->connect(DB_HOST, DB_USER, $password, DB_NAME, DB_PORT);

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

// セッション開始は各ページのSmartSessionに任せる
// ※ここでsession_start()を呼ぶとPHPSESSIDで開始され、
//   SmartSessionのsession_name()設定が効かなくなる
?>