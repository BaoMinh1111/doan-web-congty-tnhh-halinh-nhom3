<?php

/**
 * Class ErrorHandler
 * Xử lý lỗi toàn cục cho ứng dụng (404, 403, 500)
 * Hỗ trợ view riêng và fallback HTML
 */

class ErrorHandler
{
    private static bool $debug = false;

    /**
     * Đăng ký error handler toàn cục
     */
    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);

        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
    }

    /**
     * Hiển thị trang 404
     */
    public static function notFound(): void
    {
        http_response_code(404);
        self::render(404, 'Không tìm thấy trang', 'Trang bạn tìm không tồn tại hoặc đã bị di chuyển.');
    }

    /**
     * Hiển thị trang 403 - Không có quyền truy cập
     */
    public static function forbidden(): void
    {
        http_response_code(403);
        self::render(403, 'Không có quyền truy cập', 'Bạn không có quyền truy cập vào trang này.');
    }

    /**
     * Xử lý lỗi PHP (Warning, Notice, Error...)
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false; // Bỏ qua lỗi bị suppress bằng @
        }

        error_log("[ERROR $errno] $errstr in $errfile:$errline");
        http_response_code(500);
        self::render(
            500,
            'Lỗi hệ thống',
            self::$debug ? "$errstr in $errfile line $errline" : 'Đã xảy ra lỗi. Vui lòng thử lại sau.'
        );
        return true;
    }

    /**
     * Xử lý Exception không được catch
     */
    public static function handleException(Throwable $e): void
    {
        error_log('[EXCEPTION] ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
        
        http_response_code(500);
        self::render(
            500,
            'Lỗi hệ thống',
            self::$debug 
                ? $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')' 
                : 'Đã xảy ra lỗi. Vui lòng thử lại sau.'
        );
    }

    /**
     * Xử lý Fatal Error
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log('[FATAL] ' . $error['message'] . ' — ' . $error['file'] . ':' . $error['line']);
            http_response_code(500);
            self::render(500, 'Lỗi nghiêm trọng', 'Vui lòng thử lại sau.');
        }
    }

    /**
     * Render trang lỗi
     */
    private static function render(int $code, string $title, string $message): void
    {
        $viewPath = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/..') 
                  . "/views/errors/{$code}.php";

        if (file_exists($viewPath)) {
            extract(['code' => $code, 'title' => $title, 'message' => $message]);
            require $viewPath;
        } else {
            // Fallback HTML nếu chưa có file view
            echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$code} — {$title}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .error-container { text-align: center; max-width: 500px; }
        h1 { font-size: 120px; font-weight: bold; margin-bottom: 10px; line-height: 1; }
        h2 { font-size: 24px; margin-bottom: 15px; color: #555; }
        p { font-size: 17px; color: #777; margin-bottom: 30px; }
        a {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 16px;
        }
        a:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>{$code}</h1>
        <h2>{$title}</h2>
        <p>{$message}</p>
        <a href='/'>← Về Trang Chủ</a>
    </div>
</body>
</html>";
        }
        exit;
    }
}