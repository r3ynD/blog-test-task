<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ArticleFilter;
use App\Exception\ValidationException;
use App\Model\User;
use App\Repository\CategoryRepository;
use App\Service\ArticleListService;
use Smarty\Smarty;

class ArticleListController extends BaseController
{
    public function __construct(
        private ArticleListService $service,
        private CategoryRepository $categories,
        Smarty $smarty,
        ?User $user
    ) {
        parent::__construct($smarty, $user);
    }

    public function index(array $query, int $page): void
    {
        $values = [];
        foreach (['q', 'author', 'category', 'from', 'to', 'sort'] as $field) {
            $values[$field] = is_string($query[$field] ?? null) ? trim($query[$field]) : '';
        }
        $error = '';
        $data = [];
        try {
            $filter = ArticleFilter::fromQuery($query);
            $data = $this->service->getPage($filter, $page);
        } catch (ValidationException $exception) {
            http_response_code(422);
            $error = $exception->getMessage();
        }
        $this->render('articles.tpl', $data + [
            'pageTitle' => 'Все статьи', 'filters' => $values,
            'filterCategories' => $this->categories->findAll(), 'error' => $error,
        ]);
    }
}
