<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class CommentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByArticle(int $articleId, int $offset): array
    {
        $query = $this->pdo->prepare(
            'SELECT c.id, c.text, c.created_at, c.user_id, u.name, r.code AS role
             FROM comments c JOIN users u ON u.id = c.user_id JOIN roles r ON r.id = u.role_id
             WHERE c.article_id = ? ORDER BY c.created_at DESC, c.id DESC LIMIT 10 OFFSET ?'
        );
        $query->bindValue(1, $articleId, PDO::PARAM_INT);
        $query->bindValue(2, $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countByArticle(int $articleId): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM comments WHERE article_id = ?');
        $query->execute([$articleId]);
        return (int) $query->fetchColumn();
    }

    public function create(int $articleId, int $userId, string $text): void
    {
        $query = $this->pdo->prepare('INSERT INTO comments (article_id, user_id, text) VALUES (?, ?, ?)');
        $query->execute([$articleId, $userId, $text]);
    }
}
