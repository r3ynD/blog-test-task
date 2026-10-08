<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use App\Exception\ValidationException;
use PDOException;

class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    public function register(string $username, string $name, string $password): int
    {
        $username = strtolower(trim($username));
        $name = trim($name);
        if (!preg_match('/\A[a-z0-9_]{3,32}\z/', $username)) {
            throw new ValidationException('Логин: 3–32 латинские буквы, цифры или знак _.');
        }
        if ($name === '' || !mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 100) {
            throw new ValidationException('Укажите имя длиной до 100 символов.');
        }
        if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new ValidationException('Пароль должен содержать от 8 до 72 байт.');
        }
        try {
            return $this->users->create($username, $name, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new ValidationException('Этот логин уже занят.');
            }
            throw $exception;
        }
    }

    public function login(string $username, string $password): int
    {
        $user = $this->users->findForLogin(strtolower(trim($username)));
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = password_verify($password, $hash);
        if (!$user || !$valid || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new ValidationException('Неверный логин или пароль.');
        }
        return (int) $user['id'];
    }
}
