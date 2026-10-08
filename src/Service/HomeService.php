<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

class HomeService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ArticleRepository $articleRepository
    ) {
    }

    public function getCategories(): array
    {
        $articlesByCategory = [];

        foreach ($this->articleRepository->findLatestForHome() as $article) {
            $articlesByCategory[$article['category_id']][] = $article;
        }

        $categories = [];

        foreach ($this->categoryRepository->findForHome() as $category) {
            $category['articles'] = $articlesByCategory[$category['id']] ?? [];
            $categories[] = $category;
        }

        return $categories;
    }
}
