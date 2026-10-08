<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HomeService;
use Smarty\Smarty;
use App\Model\User;

class HomeController extends BaseController
{
    public function __construct(private HomeService $homeService, Smarty $smarty, ?User $user = null)
    {
        parent::__construct($smarty, $user);
    }

    public function index(): void
    {
        $this->render('home.tpl', [
            'pageTitle' => 'Заметки разработчика', 'categories' => $this->homeService->getCategories(),
        ]);
    }
}
