<?php

/**
 * Class ErrorHandler
 * File: app/ErrorHandler.php
 *
 * Xử lý lỗi toàn cục — hiển thị trang 404/500 thay vì trang trắng.
 * Đăng ký tại index.php: ErrorHandler::register(false);
 */
class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
    }

    /** Gọi cuối router khi không có route nào khớp */
    public static function notFound(): void
    {
        http_response_code(404);
        self::render(404, 'Không tìm thấy trang', 'Trang bạn tìm không tồn tại.');
    }

    /** Gọi khi không đủ quyền */
    public static function forbidden(): void
    {
        http_response_code(403);
        self::render(403, 'Không có quyền', 'Bạn không có quyền truy cập trang này.');
    }

    /** Bắt exception chưa được catch */
    public static function handleException(Throwable $e): void
    {
        error_log('[ERROR] ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        self::render(
            500,
            'Lỗi hệ thống',
            self::$debug ? $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')' : 'Đã xảy ra lỗi. Vui lòng thử lại sau.'
        );
    }

    /** Bắt fatal error khi PHP shutdown */
    public static function handleShutdown(): void
    {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log('[FATAL] ' . $e['message'] . ' — ' . $e['file'] . ':' . $e['line']);
            http_response_code(500);
            self::render(500, 'Lỗi nghiêm trọng', 'Vui lòng thử lại sau.');
        }
    }

    /** Ưu tiên view file, fallback HTML inline */
    private static function render(int $code, string $title, string $message): void
    {
        $view = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/..') . "/views/errors/{$code}.php";

        if (file_exists($view)) {
            extract(['code' => $code, 'title' => $title, 'message' => $message]);
            require $view;
        } else {
            echo "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'>
            <title>{$code} — {$title}</title>
            <style>body{font-family:sans-serif;text-align:center;padding:60px}
            h1{font-size:72px;color:#e53935;margin:0}a{color:#1976d2}</style></head>
            <body><h1>{$code}</h1><h2>{$title}</h2><p>{$message}</p>
            <a href='/'>← Về trang chủ</a></body></html>";
        }
        exit;
    }
}