<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CategoryService;
use Smarty\Smarty;
use App\Http\HttpException;
use App\Model\User;

class CategoryController extends BaseController
{
    public function __construct(private CategoryService $service, Smarty $smarty, ?User $user)
    {
        parent::__construct($smarty, $user);
    }

    public function show(int $id, int $page, string $sort): void
    {
        $data = $this->service->getPage($id, $page, $sort);
        if (!$data) {
            throw new HttpException(404, 'Категория не найдена.');
        }
        $this->render('category.tpl', $data + ['pageTitle' => $data['category']['name']]);
    }
}
