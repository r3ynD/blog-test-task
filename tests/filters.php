<?php

declare(strict_types=1);

use App\Controller\ArticleListController;
use App\Dto\ArticleFilter;
use App\Exception\ValidationException;
use App\Http\HttpException;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\ArticleListService;

if ((int) ini_get('zend.assertions') !== 1 || getenv('DB_HOST') !== 'mysql_test' || getenv('DB_NAME') !== 'blog_test') {
    throw new RuntimeException('Expected assertions and the disposable blog_test database on mysql_test.');
}
$smarty = require dirname(__DIR__) . '/bootstrap.php';
$pdo = require dirname(__DIR__) . '/config/database.php';
$articles = new ArticleRepository($pdo);
$categories = new CategoryRepository($pdo);
$service = new ArticleListService($articles, $categories);

foreach ([['from' => '2026-02-29'], ['from' => "2026-01\0-01"], ['from' => '2026-07-01', 'to' => '2026-06-01'], ['author' => []], ['category' => '-1']] as $invalid) {
    try {
        ArticleFilter::fromQuery($invalid);
        throw new RuntimeException('Invalid filter accepted.');
    } catch (ValidationException $exception) {
        assert($exception->getMessage() !== '');
    }
}
assert(ArticleFilter::fromQuery(['sort' => 'views; DROP TABLE users'])->sort === 'date');
$pdo->beginTransaction();
try {
    $insertUser = $pdo->prepare(
        "INSERT INTO users (username, name, password_hash, role_id)
         SELECT ?, ?, password_hash, role_id FROM users WHERE username = 'writer'"
    );
    $insertUser->execute(['filter_writer', 'Filter author']);
    $authorId = (int) $pdo->lastInsertId();
    $insertUser->execute(['filterXwriter', 'Other author']);
    $otherAuthorId = (int) $pdo->lastInsertId();
    $categoryIds = array_column($categories->findAll(), 'id');
    $insertArticle = $pdo->prepare(
        'INSERT INTO articles (author_id, image, title, description, text, views, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insertRelation = $pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
    $ids = [];
    for ($day = 1; $day <= 8; $day++) {
        $insertArticle->execute([
            $authorId, '/assets/images/notebook.svg', 'Filter%_Note ' . $day,
            'Test description', 'Test text', 10, sprintf('2026-06-%02d 23:59:59', $day),
        ]);
        $id = (int) $pdo->lastInsertId();
        $ids[] = $id;
        $insertRelation->execute([$id, $categoryIds[0]]);
        $insertRelation->execute([$id, $categoryIds[1]]);
    }
    foreach ([[$otherAuthorId, 'Filter%_Note other author'], [$authorId, 'FilterXXNote wildcard collision']] as [$userId, $title]) {
        $insertArticle->execute([$userId, '/assets/images/notebook.svg', $title, 'Description', 'Text', 10, '2026-06-05 12:00:00']);
        $insertRelation->execute([(int) $pdo->lastInsertId(), $categoryIds[0]]);
    }
    $query = [
        'q' => 'Filter%_Note', 'author' => 'FILTER_', 'category' => (string) $categoryIds[0],
        'from' => '2026-06-01', 'to' => '2026-06-07', 'sort' => 'views',
    ];
    $filter = ArticleFilter::fromQuery($query);
    $first = $service->getPage($filter, 1);
    $second = $service->getPage($filter, 2);
    assert($first['total'] === 7 && $first['pages'] === 2);
    assert(array_column($first['articles'], 'id') === array_reverse(array_slice($ids, 1, 6)));
    assert(array_column($second['articles'], 'id') === [$ids[0]]);
    parse_str(parse_url($first['nextUrl'], PHP_URL_QUERY), $nextQuery);
    assert($nextQuery['page'] === '2' && $nextQuery['author'] === 'filter_' && $nextQuery['q'] === 'Filter%_Note');
    assert($nextQuery['from'] === $query['from'] && $nextQuery['to'] === $query['to']);
    assert($nextQuery['category'] === $query['category'] && $nextQuery['sort'] === 'views');
    $sameDay = ArticleFilter::fromQuery(array_replace($query, ['from' => '2026-06-07']));
    assert(array_column($service->getPage($sameDay, 1)['articles'], 'id') === [$ids[6]]);
    assert($articles->countFiltered(ArticleFilter::fromQuery(['q' => "' OR 1=1 --"])) === 0);
    try {
        $service->getPage($filter, 3);
        throw new RuntimeException('Out-of-range page accepted.');
    } catch (HttpException $exception) {
        assert($exception->status === 404);
    }
    ob_start();
    (new ArticleListController($service, $categories, $smarty, null))->index(['q' => '<script>alert(1)</script>'], 1);
    $html = ob_get_clean();
    assert(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'));
    assert(!str_contains($html, '<script>alert(1)</script>'));
    assert(str_contains($html, 'Ничего не найдено'));
} finally {
    $pdo->rollBack();
}
echo 'Article filter, inclusive dates, literal LIKE search and pagination checks passed.' . PHP_EOL;
