<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Model\User;

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?User
    {
        $query = $this->pdo->prepare(
            'SELECT u.id, u.username, u.name, r.code AS role, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?'
        );
        $query->execute([$id]);
        $row = $query->fetch();
        return $row ? new User((int) $row['id'], $row['username'], $row['name'], $row['role'], $row['role_name']) : null;
    }

    public function findForLogin(string $username): ?array
    {
        $query = $this->pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
        $query->execute([$username]);
        return $query->fetch() ?: null;
    }

    public function create(string $username, string $name, string $hash): int
    {
        $query = $this->pdo->prepare(
            "INSERT INTO users (username, name, password_hash, role_id)
             SELECT ?, ?, ?, id FROM roles WHERE code = 'ROLE_USER'"
        );
        $query->execute([$username, $name, $hash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findForAdmin(int $offset): array
    {
        $query = $this->pdo->prepare(
            'SELECT u.id, u.username, u.name, r.code AS role, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id LIMIT 10 OFFSET ?'
        );
        $query->bindValue(1, $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function changeRole(int $id, string $role): bool
    {
        $this->pdo->beginTransaction();
        try {
            $admins = $this->pdo->query(
                "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
                 WHERE r.code = 'ROLE_ADMIN' ORDER BY u.id FOR UPDATE"
            )->fetchAll(PDO::FETCH_COLUMN);
            if ($role !== 'ROLE_ADMIN' && count($admins) === 1 && (int) $admins[0] === $id) {
                $this->pdo->rollBack();
                return false;
            }
            $query = $this->pdo->prepare(
                'UPDATE users SET role_id = (SELECT id FROM roles WHERE code = ?) WHERE id = ?'
            );
            $query->execute([$role, $id]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
