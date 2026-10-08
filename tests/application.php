<?php

declare(strict_types=1);

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Service\ArticleService;
use App\Service\AuthService;
use App\Service\CategoryService;
use App\Service\ImageStorage;

if ((int) ini_get('zend.assertions') !== 1 || getenv('DB_HOST') !== 'mysql_test' || getenv('DB_NAME') !== 'blog_test') {
    throw new RuntimeException('Expected assertions and the disposable blog_test database on mysql_test.');
}
require dirname(__DIR__) . '/vendor/autoload.php';
$pdo = require dirname(__DIR__) . '/config/database.php';
$users = new UserRepository($pdo);
$auth = new AuthService($users);
$articles = new ArticleRepository($pdo);
$categories = new CategoryRepository($pdo);
$comments = new CommentRepository($pdo);
$service = new ArticleService($articles, $categories, $comments, new ImageStorage('/var/www/html/public/uploads'));
$adminId = $auth->login('ADMIN', 'test-admin-password');
$writerId = $auth->login('writer', 'test-writer-password');
$readerId = $auth->login('reader', 'test-reader-password');
assert($users->findById($adminId)->role === 'ROLE_ADMIN');
assert($users->findById($writerId)->role === 'ROLE_WRITER');
assert($users->findById($readerId)->role === 'ROLE_USER');
assert(!$users->changeRole($adminId, 'ROLE_USER'));

$pdo->beginTransaction();
try {
    $userId = $auth->register('New_Reader', 'New reader', 'password-for-test');
    assert($users->findById($userId)->role === 'ROLE_USER');
    try {
        $auth->register('NEW_READER', 'Duplicate', 'password-for-test');
        throw new RuntimeException('Duplicate username accepted.');
    } catch (InvalidArgumentException $exception) {
        assert($exception->getMessage() === 'Этот логин уже занят.');
    }
    $categoryId = (int) $pdo->query('SELECT MIN(id) FROM categories')->fetchColumn();
    $query = $pdo->prepare(
        'INSERT INTO articles (author_id, image, title, description, text, views, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $relation = $pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
    $ids = [];
    foreach ([1, 50, 20, 50, 2, 3, 4] as $views) {
        $query->execute([$writerId, '/assets/images/notebook.svg', 'Test note', 'Description', 'Text', 1000000 + $views, '2026-10-01 12:00:00']);
        $id = (int) $pdo->lastInsertId();
        $ids[] = $id;
        $relation->execute([$id, $categoryId]);
    }
    $page = (new CategoryService($categories, $articles))->getPage($categoryId, 1, 'views; DROP TABLE users');
    assert($page['sort'] === 'date');
    assert($page['pages'] >= 2);
    $byViews = $articles->findByCategory($categoryId, 'views', 0);
    $testIds = array_values(array_filter(array_column($byViews, 'id'), fn ($id) => in_array($id, $ids, true)));
    assert(array_search($ids[3], $testIds, true) < array_search($ids[1], $testIds, true));
    $article = $articles->findById($ids[0]);
    assert($service->canEdit($users->findById($writerId), $article));
    assert($service->canEdit($users->findById($adminId), $article));
    assert(!$service->canEdit($users->findById($readerId), $article));
    assert(!$service->canEdit(null, $article));
    $service->addComment($ids[0], $readerId, '<script>test</script>');
    assert($comments->findByArticle($ids[0], 0)[0]['text'] === '<script>test</script>');
    $similarIds = array_column($articles->findSimilar($ids[0]), 'id');
    assert(count($similarIds) === 3 && !in_array($ids[0], $similarIds, true));
    assert($similarIds === [$ids[6], $ids[5], $ids[4]]);
    $articles->delete($ids[0]);
    assert($comments->countByArticle($ids[0]) === 0);
} finally {
    $pdo->rollBack();
}
echo 'Authentication, permissions, sorting and similar article checks passed.' . PHP_EOL;
