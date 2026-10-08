<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

class CategoryService
{
    public function __construct(private CategoryRepository $categories, private ArticleRepository $articles)
    {
    }

    public function getPage(int $id, int $page, string $sort): ?array
    {
        $category = $this->categories->findById($id);
        if (!$category) {
            return null;
        }
        $sort = in_array($sort, ['date', 'views'], true) ? $sort : 'date';
        $pages = max(1, (int) ceil($this->articles->countByCategory($id) / 6));
        if ($page > $pages) {
            return null;
        }
        return [
            'category' => $category, 'sort' => $sort, 'page' => $page, 'pages' => $pages,
            'articles' => $this->articles->findByCategory($id, $sort, ($page - 1) * 6),
        ];
    }
}
