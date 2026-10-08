<?php

declare(strict_types=1);

use App\Controller\HomeController;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\HomeService;

if ((int) ini_get('zend.assertions') !== 1) {
    throw new RuntimeException('Run with php -d zend.assertions=1 tests/home.php');
}

if (getenv('DB_HOST') !== 'mysql_test' || getenv('DB_NAME') !== 'blog_test') {
    throw new RuntimeException('Expected the disposable blog_test database on mysql_test.');
}

function runSeed(array $argv = []): void
{
    require dirname(__DIR__) . '/bin/seed.php';
}

$smarty = require dirname(__DIR__) . '/bootstrap.php';
$pdo = require dirname(__DIR__) . '/config/database.php';
$service = new HomeService(new CategoryRepository($pdo), new ArticleRepository($pdo));
assert($service->getCategories() === []);
$pdo->beginTransaction();

try {
    $categoryIds = [];
    $insertCategory = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');

    foreach (['<b>PHP</b>', 'Databases', 'Empty'] as $name) {
        $insertCategory->execute([$name, 'Category description']);
        $categoryIds[] = (int) $pdo->lastInsertId();
    }

    $insertArticle = $pdo->prepare(
        'INSERT INTO articles (image, title, description, text, published_at) VALUES (?, ?, ?, ?, ?)'
    );
    $insertRelation = $pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
    $articleIds = [];
    $unsafeTitle = '<script>alert("title")</script>';
    $dates = ['2026-03-01', '2026-03-04', '2026-03-03', '2026-03-04', '2026-03-02'];

    foreach ($dates as $index => $date) {
        $insertArticle->execute([
            '/assets/images/notebook.svg',
            $index === 3 ? $unsafeTitle : 'PHP note from ' . $date,
            'A practical example.',
            'Full article text.',
            $date . ' 12:00:00',
        ]);
        $articleId = (int) $pdo->lastInsertId();
        $articleIds[] = $articleId;
        $insertRelation->execute([$articleId, $categoryIds[0]]);

        if ($index === 0 || $index === 3) {
            $insertRelation->execute([$articleId, $categoryIds[1]]);
        }
    }

    $categories = array_column($service->getCategories(), null, 'id');
    $setViews = $pdo->prepare('UPDATE articles SET views = 10 WHERE id IN (?, ?, ?)');
    $setViews->execute([$articleIds[0], $articleIds[1], $articleIds[3]]);
    assert(array_column($service->getPage()['popular'], 'id') === [$articleIds[3], $articleIds[1], $articleIds[0]]);
    assert(count($categories) === 2);
    assert(!isset($categories[$categoryIds[2]]));
    assert(array_column($categories[$categoryIds[0]]['articles'], 'id') === [
        $articleIds[3], $articleIds[1], $articleIds[2],
    ]);
    assert(array_column($categories[$categoryIds[1]]['articles'], 'id') === [
        $articleIds[3], $articleIds[0],
    ]);

    ob_start();
    (new HomeController($service, $smarty))->index();
    $html = ob_get_clean();
    assert(!str_contains($html, $unsafeTitle));
    assert(str_contains($html, '&lt;script&gt;alert(&quot;title&quot;)&lt;/script&gt;'));
    assert(!str_contains($html, '<b>PHP</b>'));
    assert(str_contains($html, '&lt;b&gt;PHP&lt;/b&gt;'));
} finally {
    $pdo->rollBack();
}

runSeed();
assert((int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn() === 12);
assert((int) $pdo->query("SELECT COUNT(DISTINCT image) FROM articles")->fetchColumn() === 3);
assert((int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 4);
assert(count($service->getCategories()) === 3);

$firstId = $pdo->query('SELECT MIN(id) FROM articles')->fetchColumn();
$editArticle = $pdo->prepare('UPDATE articles SET title = ? WHERE id = ?');
$editArticle->execute(['Manually edited article', $firstId]);
runSeed();
$findArticle = $pdo->prepare('SELECT title FROM articles WHERE id = ?');
$findArticle->execute([$firstId]);
assert($findArticle->fetchColumn() === 'Manually edited article');
assert((int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn() === 12);

$data = require dirname(__DIR__) . '/database/seeds/blog.php';
$oldCover = $pdo->prepare('UPDATE articles SET image = ? WHERE title = ?');
$oldCover->execute(['/assets/images/notebook.svg', $data['articles'][1]['title']]);
$oldCover->execute(['/assets/images/workflow.svg', $data['articles'][2]['title']]);
runSeed(['--refresh-covers']);
$findCover = $pdo->prepare('SELECT image FROM articles WHERE title = ?');
$findCover->execute([$data['articles'][1]['title']]);
assert($findCover->fetchColumn() === '/assets/images/database.svg');
$findCover->execute([$data['articles'][2]['title']]);
assert($findCover->fetchColumn() === '/assets/images/workflow.svg');
$findArticle->execute([$firstId]);
assert($findArticle->fetchColumn() === 'Manually edited article');

echo 'Home and seed checks passed.' . PHP_EOL;
