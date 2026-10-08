<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class CategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findForHome(): array
    {
        return $this->pdo->query(
            'SELECT c.id, c.name, c.description
             FROM categories c
             WHERE EXISTS (
                 SELECT 1 FROM article_categories ac WHERE ac.category_id = c.id
             )
             ORDER BY c.name, c.id'
        )->fetchAll();
    }

    public function findAll(): array
    {
        return $this->pdo->query('SELECT id, name, description FROM categories ORDER BY name, id')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT id, name, description FROM categories WHERE id = ?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function findByArticleId(int $id): array
    {
        $query = $this->pdo->prepare(
            'SELECT c.id, c.name FROM categories c
             JOIN article_categories ac ON ac.category_id = c.id WHERE ac.article_id = ? ORDER BY c.name, c.id'
        );
        $query->execute([$id]);
        return $query->fetchAll();
    }
}
