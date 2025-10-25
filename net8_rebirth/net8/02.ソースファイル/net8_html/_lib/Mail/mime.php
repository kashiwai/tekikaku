<?php
/**
 * Dummy Mail/mime.php for development environment
 * 開発環境用のダミーMail_mimeクラス
 *
 * 本番環境ではPEAR Mail_Mimeライブラリに置き換えてください
 */

class Mail_mime {

    protected $headers = array();
    protected $txtbody = '';
    protected $htmlbody = '';

    /**
     * Constructor
     */
    public function __construct($params = null) {
        // ダミーコンストラクタ
    }

    /**
     * Set text body
     */
    public function setTXTBody($txt, $isfile = false, $append = false) {
        $this->txtbody = $txt;
    }

    /**
     * Set HTML body
     */
    public function setHTMLBody($html, $isfile = false) {
        $this->htmlbody = $html;
    }

    /**
     * Add attachment
     */
    public function addAttachment($file, $c_type = 'application/octet-stream', $name = '', $isfile = true) {
        return true;
    }

    /**
     * Get message body
     */
    public function get($params = null) {
        return $this->txtbody;
    }

    /**
     * Get headers
     */
    public function headers($xtra = array()) {
        return array_merge($this->headers, $xtra);
    }

    /**
     * Set From header
     */
    public function setFrom($from) {
        $this->headers['From'] = $from;
    }

    /**
     * Set Subject header
     */
    public function setSubject($subject) {
        $this->headers['Subject'] = $subject;
    }
}
