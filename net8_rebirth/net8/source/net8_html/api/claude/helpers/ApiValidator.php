<?php
/**
 * NET8 Claude Code API - Validator Helper
 * Version: 1.0.0
 * Created: 2025-12-12
 */

class ApiValidator {

    private $errors = [];
    private $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * 必須チェック
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || $this->data[$field] === '' || $this->data[$field] === null) {
            $this->errors[$field] = $message ?? "{$field}は必須です";
        }
        return $this;
    }

    /**
     * 数値チェック
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "{$field}は数値で入力してください";
        }
        return $this;
    }

    /**
     * 整数チェック
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?? "{$field}は整数で入力してください";
        }
        return $this;
    }

    /**
     * 最小値チェック
     */
    public function min($field, $min, $message = null) {
        if (isset($this->data[$field]) && is_numeric($this->data[$field]) && $this->data[$field] < $min) {
            $this->errors[$field] = $message ?? "{$field}は{$min}以上で入力してください";
        }
        return $this;
    }

    /**
     * 最大値チェック
     */
    public function max($field, $max, $message = null) {
        if (isset($this->data[$field]) && is_numeric($this->data[$field]) && $this->data[$field] > $max) {
            $this->errors[$field] = $message ?? "{$field}は{$max}以下で入力してください";
        }
        return $this;
    }

    /**
     * 文字数最小チェック
     */
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "{$field}は{$length}文字以上で入力してください";
        }
        return $this;
    }

    /**
     * 文字数最大チェック
     */
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "{$field}は{$length}文字以下で入力してください";
        }
        return $this;
    }

    /**
     * メールアドレスチェック
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "{$field}は有効なメールアドレスを入力してください";
        }
        return $this;
    }

    /**
     * 半角英数字チェック
     */
    public function alphaNum($field, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $this->data[$field])) {
            $this->errors[$field] = $message ?? "{$field}は半角英数字で入力してください";
        }
        return $this;
    }

    /**
     * 日付形式チェック
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "{$field}は有効な日付形式で入力してください";
            }
        }
        return $this;
    }

    /**
     * 選択肢チェック
     */
    public function in($field, $options, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !in_array($this->data[$field], $options)) {
            $this->errors[$field] = $message ?? "{$field}は有効な値を選択してください";
        }
        return $this;
    }

    /**
     * バリデーション実行
     */
    public function validate() {
        return empty($this->errors);
    }

    /**
     * エラー取得
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * 値取得（デフォルト値対応）
     */
    public function get($field, $default = null) {
        return $this->data[$field] ?? $default;
    }

    /**
     * 全データ取得
     */
    public function all() {
        return $this->data;
    }

    /**
     * 指定フィールドのみ取得
     */
    public function only($fields) {
        $result = [];
        foreach ($fields as $field) {
            if (isset($this->data[$field])) {
                $result[$field] = $this->data[$field];
            }
        }
        return $result;
    }
}
