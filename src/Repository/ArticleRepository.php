<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Exception\ValidationException;
use App\Dto\ArticleFilter;

class ArticleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findLatestForHome(): array
    {
        return $this->pdo->query(
            'SELECT category_id, id, image, title, description, published_at
             FROM (
                 SELECT ac.category_id, a.id, a.image, a.title, a.description, a.published_at,
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

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT a.id, a.author_id, a.image, a.title, a.description, a.text, a.views, a.published_at,
                    u.name AS author_name
             FROM articles a LEFT JOIN users u ON u.id = a.author_id WHERE a.id = ?'
        );
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function findPopular(): array
    {
        return $this->pdo->query(
            'SELECT id, image, title, description, published_at, views FROM articles
             ORDER BY views DESC, published_at DESC, id DESC LIMIT 3'
        )->fetchAll();
    }

    public function incrementViews(int $id): ?int
    {
        $query = $this->pdo->prepare('UPDATE articles SET views = views + 1 WHERE id = ?');
        $query->execute([$id]);
        $query = $this->pdo->prepare('SELECT views FROM articles WHERE id = ?');
        $query->execute([$id]);
        $views = $query->fetchColumn();
        return $views === false ? null : (int) $views;
    }

    public function save(?int $id, int $authorId, array $data, string $image, string $previousImage): int
    {
        $this->pdo->beginTransaction();
        try {
            if ($id === null) {
                $query = $this->pdo->prepare(
                    'INSERT INTO articles (author_id, image, title, description, text, published_at)
                     VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)'
                );
                $query->execute([$authorId, $image, $data['title'], $data['description'], $data['text']]);
                $id = (int) $this->pdo->lastInsertId();
            } else {
                $lock = $this->pdo->prepare('SELECT image FROM articles WHERE id = ? FOR UPDATE');
                $lock->execute([$id]);
                if ($lock->fetchColumn() !== $previousImage) {
                    throw new ValidationException('Статья уже изменена или удалена. Обновите страницу.');
                }
                $query = $this->pdo->prepare(
                    'UPDATE articles SET image = ?, title = ?, description = ?, text = ? WHERE id = ?'
                );
                $query->execute([$image, $data['title'], $data['description'], $data['text'], $id]);
                $query = $this->pdo->prepare('DELETE FROM article_categories WHERE article_id = ?');
                $query->execute([$id]);
            }
            $query = $this->pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
            foreach ($data['category_ids'] as $categoryId) {
                $query->execute([$id, $categoryId]);
            }
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $query = $this->pdo->prepare('DELETE FROM articles WHERE id = ?');
        $query->execute([$id]);
    }

    public function findByAuthor(int $authorId, int $offset): array
    {
        $query = $this->pdo->prepare(
            'SELECT id, image, title, description, published_at FROM articles
             WHERE author_id = ? ORDER BY published_at DESC, id DESC LIMIT 6 OFFSET ?'
        );
        $query->bindValue(1, $authorId, PDO::PARAM_INT);
        $query->bindValue(2, $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countByAuthor(int $authorId): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM articles WHERE author_id = ?');
        $query->execute([$authorId]);
        return (int) $query->fetchColumn();
    }

    public function findSimilar(int $id): array
    {
        $query = $this->pdo->prepare(
            'SELECT a.id, a.image, a.title, a.description, a.published_at
             FROM articles a JOIN article_categories ac ON ac.article_id = a.id
             JOIN article_categories current_categories ON current_categories.category_id = ac.category_id
             WHERE current_categories.article_id = ? AND a.id <> ?
             GROUP BY a.id, a.image, a.title, a.description, a.published_at
             ORDER BY COUNT(*) DESC, a.published_at DESC, a.id DESC LIMIT 3'
        );
        $query->execute([$id, $id]);
        return $query->fetchAll();
    }

    public function findByCategory(int $id, string $sort, int $offset): array
    {
        $order = $sort === 'views' ? 'a.views DESC, a.published_at DESC, a.id DESC' : 'a.published_at DESC, a.id DESC';
        $query = $this->pdo->prepare(
            'SELECT a.id, a.image, a.title, a.description, a.published_at
             FROM articles a JOIN article_categories ac ON ac.article_id = a.id
             WHERE ac.category_id = ? ORDER BY ' . $order . ' LIMIT 6 OFFSET ?'
        );
        $query->bindValue(1, $id, PDO::PARAM_INT);
        $query->bindValue(2, $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countByCategory(int $id): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM article_categories WHERE category_id = ?');
        $query->execute([$id]);
        return (int) $query->fetchColumn();
    }

    public function findFiltered(ArticleFilter $filter, int $offset): array
    {
        [$where, $parameters] = $this->filterConditions($filter);
        $order = $filter->sort === 'views' ? 'a.views DESC, a.published_at DESC, a.id DESC' : 'a.published_at DESC, a.id DESC';
        $query = $this->pdo->prepare(
            'SELECT a.id, a.image, a.title, a.description, a.published_at, a.views,
                    a.author_id, u.username AS author_username
             FROM articles a LEFT JOIN users u ON u.id = a.author_id ' . $where . '
             ORDER BY ' . $order . ' LIMIT 6 OFFSET :offset'
        );
        foreach ($parameters as $name => $value) {
            $query->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $query->bindValue('offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countFiltered(ArticleFilter $filter): int
    {
        [$where, $parameters] = $this->filterConditions($filter);
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM articles a LEFT JOIN users u ON u.id = a.author_id ' . $where);
        $query->execute($parameters);
        return (int) $query->fetchColumn();
    }

    private function filterConditions(ArticleFilter $filter): array
    {
        $conditions = [];
        $parameters = [];
        if ($filter->search !== '') {
            $conditions[] = "a.title LIKE :search ESCAPE '!'";
            $parameters['search'] = '%' . strtr($filter->search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        }
        if ($filter->author !== '') {
            $conditions[] = "u.username LIKE :author ESCAPE '!'";
            $parameters['author'] = strtr($filter->author, ['_' => '!_']) . '%';
        }
        if ($filter->categoryId !== null) {
            $conditions[] = 'EXISTS (SELECT 1 FROM article_categories ac WHERE ac.article_id = a.id AND ac.category_id = :category)';
            $parameters['category'] = $filter->categoryId;
        }
        if ($filter->dateFrom !== '') {
            $conditions[] = 'a.published_at >= :date_from';
            $parameters['date_from'] = $filter->dateFrom . ' 00:00:00';
        }
        if ($filter->dateTo !== '') {
            $conditions[] = 'a.published_at <= :date_to';
            $parameters['date_to'] = $filter->dateTo . ' 23:59:59';
        }
        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $parameters];
    }
}
