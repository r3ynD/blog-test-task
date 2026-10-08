<?php

declare(strict_types=1);

use App\Controller\ArticleController;
use App\Controller\ArticleListController;
use App\Controller\AuthController;
use App\Controller\CategoryController;
use App\Controller\HomeController;
use App\Controller\UserController;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Service\ArticleService;
use App\Service\ArticleListService;
use App\Service\AuthService;
use App\Service\CategoryService;
use App\Service\HomeService;
use App\Service\ImageStorage;

$articles = new ArticleRepository($pdo);
$categories = new CategoryRepository($pdo);
$articleService = new ArticleService(
    $articles, $categories, new CommentRepository($pdo),
    new ImageStorage(dirname(__DIR__) . '/public/uploads')
);

return [
    'home' => new HomeController(new HomeService($categories, $articles), $smarty, $user),
    'auth' => new AuthController(new AuthService($users), $smarty, $user),
    'article' => new ArticleController($articleService, $articles, $categories, $smarty, $user),
    'articles' => new ArticleListController(new ArticleListService($articles, $categories), $categories, $smarty, $user),
    'category' => new CategoryController(new CategoryService($categories, $articles), $smarty, $user),
    'user' => new UserController($users, $articles, $smarty, $user),
];
