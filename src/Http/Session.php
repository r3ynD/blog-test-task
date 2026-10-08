<?php

declare(strict_types=1);

namespace App\Http;

class Session
{
    public static function start(): void
    {
        session_start([
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function checkCsrf(): bool
    {
        return isset($_POST['csrf']) && is_string($_POST['csrf'])
            && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION = ['user_id' => $userId, 'csrf' => bin2hex(random_bytes(32))];
    }

    public static function logout(): void
    {
        session_regenerate_id(true);
        $_SESSION = ['csrf' => bin2hex(random_bytes(32))];
    }
}
