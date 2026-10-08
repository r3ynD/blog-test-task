<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HomeService;
use Smarty\Smarty;

class HomeController
{
    public function __construct(private HomeService $homeService, private Smarty $smarty)
    {
    }

    public function index(): void
    {
        $this->smarty->assign('pageTitle', 'Заметки разработчика');
        $this->smarty->assign('categories', $this->homeService->getCategories());
        $this->smarty->display('home.tpl');
    }
}
