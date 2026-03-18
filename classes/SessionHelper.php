<?php

class SessionHelper
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getCart(): array
    {
        self::start();
        return $_SESSION['cart'] ?? [];
    }

    public static function setCart(array $cart): void
    {
        self::start();
        $_SESSION['cart'] = $cart;
    }

    public static function clear(): void
    {
        self::start();
        unset($_SESSION['cart']);
    }
}
