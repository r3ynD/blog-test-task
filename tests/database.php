<?php

declare(strict_types=1);

if ((int) ini_get('zend.assertions') !== 1) {
    throw new RuntimeException('Run with php -d zend.assertions=1 tests/database.php');
}

if (getenv('DB_HOST') !== 'mysql_test' || getenv('DB_NAME') !== 'blog_test') {
    throw new RuntimeException('Expected the disposable blog_test database on mysql_test.');
}

function runMigrations(): void
{
    require dirname(__DIR__) . '/bin/migrate.php';
}

$password = getenv('DB_PASSWORD');
putenv('DB_PASSWORD');

try {
    require dirname(__DIR__) . '/config/database.php';
    throw new RuntimeException('Missing database password was accepted.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'Missing environment variable: DB_PASSWORD');
} finally {
    putenv('DB_PASSWORD=' . $password);
}

runMigrations();
runMigrations();

$pdo = require dirname(__DIR__) . '/config/database.php';
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
sort($tables);
assert($tables === ['article_categories', 'articles', 'categories', 'schema_migrations']);
assert((int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn() === 1);
assert($pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES) === false);

$pdo->beginTransaction();

try {
    $categoryIds = [];
    $insertCategory = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');

    foreach (['Databases', 'PHP'] as $name) {
        $insertCategory->execute([$name, 'Practical notes about ' . $name]);
        $categoryIds[] = $pdo->lastInsertId();
    }

    $insertArticle = $pdo->prepare(
        'INSERT INTO articles (image, title, description, text, published_at) VALUES (?, ?, ?, ?, ?)'
    );
    $insertArticle->execute([
        '/assets/images/database.jpg',
        'Understanding indexes',
        'Choosing indexes for common queries.',
        'Start with the queries your application actually runs.',
        '2026-01-10 12:00:00',
    ]);
    $articleId = $pdo->lastInsertId();
    $article = $pdo->query('SELECT title, views FROM articles')->fetch();
    assert($article === ['title' => 'Understanding indexes', 'views' => 0]);

    $insertRelation = $pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
    $insertRelation->execute([$articleId, $categoryIds[0]]);
    $insertRelation->execute([$articleId, $categoryIds[1]]);

    try {
        $insertRelation->execute([$articleId, $categoryIds[0]]);
        throw new RuntimeException('Duplicate article/category relation was accepted.');
    } catch (PDOException $exception) {
        assert($exception->errorInfo[1] === 1062);
    }

    foreach ([[$articleId, 0], [0, $categoryIds[0]]] as $invalidRelation) {
        try {
            $insertRelation->execute($invalidRelation);
            throw new RuntimeException('Relation to a missing article or category was accepted.');
        } catch (PDOException $exception) {
            assert($exception->errorInfo[1] === 1452);
        }
    }

    $deleteCategory = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $deleteCategory->execute([$categoryIds[0]]);
    assert((int) $pdo->query('SELECT COUNT(*) FROM article_categories')->fetchColumn() === 1);
    assert((int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn() === 1);

    $deleteArticle = $pdo->prepare('DELETE FROM articles WHERE id = ?');
    $deleteArticle->execute([$articleId]);
    assert((int) $pdo->query('SELECT COUNT(*) FROM article_categories')->fetchColumn() === 0);
    assert((int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 1);
} finally {
    $pdo->rollBack();
}

echo 'Database checks passed.' . PHP_EOL;
