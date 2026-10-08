<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ArticleFilter;
use App\Exception\ValidationException;
use App\Http\HttpException;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

class ArticleListService
{
    public function __construct(private ArticleRepository $articles, private CategoryRepository $categories)
    {
    }

    public function getPage(ArticleFilter $filter, int $page): array
    {
        if ($filter->categoryId !== null && !$this->categories->findById($filter->categoryId)) {
            throw new ValidationException('Выберите существующую категорию.');
        }
        $total = $this->articles->countFiltered($filter);
        $pages = max(1, (int) ceil($total / 6));
        if ($page > $pages) {
            throw new HttpException(404, 'Страница не найдена.');
        }
        $parameters = $filter->queryParameters();
        return [
            'articles' => $this->articles->findFiltered($filter, ($page - 1) * 6),
            'total' => $total, 'page' => $page, 'pages' => $pages,
            'previousUrl' => $page > 1 ? '/articles?' . http_build_query($parameters + ['page' => $page - 1]) : '',
            'nextUrl' => $page < $pages ? '/articles?' . http_build_query($parameters + ['page' => $page + 1]) : '',
        ];
    }
}
