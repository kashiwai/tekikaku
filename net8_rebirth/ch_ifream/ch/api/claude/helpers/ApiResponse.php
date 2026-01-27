<?php
/**
 * NET8 Claude Code API - Response Helper
 * Version: 1.0.0
 * Created: 2025-12-12
 */

class ApiResponse {

    /**
     * 成功レスポンス
     */
    public static function success($data = null, $message = null, $code = 200) {
        http_response_code($code);
        $response = [
            'success' => true,
            'timestamp' => date('c')
        ];
        if ($message !== null) {
            $response['message'] = $message;
        }
        if ($data !== null) {
            $response['data'] = $data;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * エラーレスポンス
     */
    public static function error($message, $code = 400, $errorCode = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $errorCode ?? 'ERROR_' . $code
            ],
            'timestamp' => date('c')
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * 一覧レスポンス（ページネーション対応）
     */
    public static function list($data, $total, $page = 1, $perPage = 20) {
        http_response_code(200);
        $response = [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'page' => (int)$page,
                'per_page' => (int)$perPage,
                'total_pages' => ceil($total / $perPage)
            ],
            'timestamp' => date('c')
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * 認証エラー
     */
    public static function unauthorized($message = '認証が必要です') {
        self::error($message, 401, 'UNAUTHORIZED');
    }

    /**
     * 権限エラー
     */
    public static function forbidden($message = 'アクセス権限がありません') {
        self::error($message, 403, 'FORBIDDEN');
    }

    /**
     * Not Found
     */
    public static function notFound($message = 'リソースが見つかりません') {
        self::error($message, 404, 'NOT_FOUND');
    }

    /**
     * バリデーションエラー
     */
    public static function validationError($errors) {
        http_response_code(422);
        $response = [
            'success' => false,
            'error' => [
                'message' => 'バリデーションエラー',
                'code' => 'VALIDATION_ERROR',
                'details' => $errors
            ],
            'timestamp' => date('c')
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * メソッド不許可
     */
    public static function methodNotAllowed($allowed = ['GET', 'POST', 'PUT', 'DELETE']) {
        header('Allow: ' . implode(', ', $allowed));
        self::error('許可されていないHTTPメソッドです', 405, 'METHOD_NOT_ALLOWED');
    }

    /**
     * サーバーエラー
     */
    public static function serverError($message = 'サーバーエラーが発生しました') {
        self::error($message, 500, 'SERVER_ERROR');
    }
}
