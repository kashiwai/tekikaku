<?php
/**
 * Dummy Mail.php for development environment
 * 開発環境用のダミーMailクラス
 *
 * 本番環境ではPEAR Mailライブラリに置き換えてください
 */

class Mail {

    /**
     * Factory method for creating mail instances
     */
    public static function factory($driver, $params = array()) {
        return new Mail_mock();
    }
}

class Mail_mock {

    /**
     * Send method (does nothing in development)
     */
    public function send($recipients, $headers, $body) {
        // 開発環境ではメール送信をスキップ
        error_log("DEVELOPMENT MODE: Mail送信スキップ - To: " . (is_array($recipients) ? implode(', ', $recipients) : $recipients));
        error_log("Subject: " . (isset($headers['Subject']) ? $headers['Subject'] : 'No subject'));
        return true;
    }
}

class PEAR {
    /**
     * Check if object is a PEAR error
     */
    public static function isError($obj) {
        return is_a($obj, 'PEAR_Error');
    }
}

class PEAR_Error {
    protected $message;

    public function __construct($message = '') {
        $this->message = $message;
    }

    public function getMessage() {
        return $this->message;
    }
}
