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
}
