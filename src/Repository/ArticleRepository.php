<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class ArticleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findLatestForHome(): array
    {
        return $this->pdo->query(
            'SELECT category_id, id, title, description, published_at
             FROM (
                 SELECT ac.category_id, a.id, a.title, a.description, a.published_at,
                        ROW_NUMBER() OVER (
                            PARTITION BY ac.category_id
                            ORDER BY a.published_at DESC, a.id DESC
                        ) AS article_rank
                 FROM article_categories ac
                 JOIN articles a ON a.id = ac.article_id
             ) ranked
             WHERE article_rank <= 3
             ORDER BY category_id, published_at DESC, id DESC'
        )->fetchAll();
    }
}
