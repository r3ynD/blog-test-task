<?php

declare(strict_types=1);

use App\Controller\ArticleController;
use App\Http\HttpException;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Service\ArticleService;
use App\Service\ImageStorage;

if ((int) ini_get('zend.assertions') !== 1 || getenv('DB_HOST') !== 'mysql_test' || getenv('DB_NAME') !== 'blog_test') {
    throw new RuntimeException('Expected assertions and the disposable blog_test database on mysql_test.');
}
$smarty = require dirname(__DIR__) . '/bootstrap.php';
$pdo = require dirname(__DIR__) . '/config/database.php';
$articles = new ArticleRepository($pdo);
$categories = new CategoryRepository($pdo);
$service = new ArticleService($articles, $categories, new CommentRepository($pdo), new ImageStorage('/var/www/html/public/uploads'));
$controller = new ArticleController($service, $articles, $categories, $smarty, null);
$id = (int) $pdo->query('SELECT MIN(id) FROM articles')->fetchColumn();
$before = $articles->findById($id)['views'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION = ['csrf' => 'test-token'];
$pdo->beginTransaction();
try {
    ob_start();
    $controller->show($id, 1);
    ob_end_clean();
    assert($articles->findById($id)['views'] === $before);
    assert(isset($_SESSION['article_views'][$id]));
    try {
        $controller->countView($id);
        throw new RuntimeException('Early view accepted.');
    } catch (HttpException $exception) {
        assert($exception->status === 409);
    }
    $_SESSION['article_views'][$id]['started_at'] = time() - 31;
    for ($attempt = 0; $attempt < 2; $attempt++) {
        ob_start();
        $controller->countView($id);
        $result = json_decode(ob_get_clean(), true, flags: JSON_THROW_ON_ERROR);
        assert($result['views'] === $before + 1);
    }
    assert($articles->findById($id)['views'] === $before + 1);
    $_SESSION['article_views'] = [];
    try {
        $controller->countView($id);
        throw new RuntimeException('Unopened article view accepted.');
    } catch (HttpException $exception) {
        assert($exception->status === 409);
    }
    $_SESSION['article_views'][$id] = ['started_at' => time() - 31, 'counted' => false];
    ob_start();
    $controller->countView($id);
    ob_end_clean();
    assert($articles->findById($id)['views'] === $before + 2);
    assert($articles->incrementViews(4294967295) === null);
} finally {
    $pdo->rollBack();
}
echo 'View timing, session deduplication and atomic counter checks passed.' . PHP_EOL;
