<?php

declare(strict_types=1);

use App\Controller\HomeController;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\HomeService;

header('Content-Type: text/html; charset=utf-8');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    echo 'Method not allowed.';
    return;
}

try {
    $smarty = require dirname(__DIR__) . '/bootstrap.php';
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    switch ($path) {
        case '/':
            $pdo = require dirname(__DIR__) . '/config/database.php';
            $categoryRepository = new CategoryRepository($pdo);
            $articleRepository = new ArticleRepository($pdo);
            $homeService = new HomeService($categoryRepository, $articleRepository);
            $controller = new HomeController($homeService, $smarty);
            $controller->index();
            break;

        default:
            http_response_code(404);
            $smarty->assign('pageTitle', 'Страница не найдена');
            $smarty->display('404.tpl');
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    echo 'Something went wrong. Please try again later.';
}
