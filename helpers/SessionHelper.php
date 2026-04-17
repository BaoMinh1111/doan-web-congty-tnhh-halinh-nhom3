<?php
class SessionHelper {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        session_destroy();
        $_SESSION = [];
    }

    // Kiểm tra xem User đã đăng nhập chưa
    public static function isLoggedIn() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    // Lấy Role của User hiện tại (admin/customer)
    public static function getUserRole() {
        $user = self::get('user');
        return $user['role'] ?? null;
    }
}