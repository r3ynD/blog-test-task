<?php

declare(strict_types=1);

try {
    $pdo = require dirname(__DIR__) . '/config/database.php';

    if (
        $pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn() !== false
        || $pdo->query('SELECT id FROM articles LIMIT 1')->fetchColumn() !== false
    ) {
        echo 'Database already contains blog data. Nothing changed.' . PHP_EOL;
        return;
    }

    $data = require dirname(__DIR__) . '/database/seeds/blog.php';
    $pdo->beginTransaction();
    $categoryIds = [];
    $insertCategory = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');

    foreach ($data['categories'] as $key => $category) {
        $insertCategory->execute([$category['name'], $category['description']]);
        $categoryIds[$key] = $pdo->lastInsertId();
    }

    $insertArticle = $pdo->prepare(
        'INSERT INTO articles (image, title, description, text, views, published_at) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertRelation = $pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
    $firstPublication = new DateTimeImmutable('2026-01-10 09:00:00');

    foreach ($data['articles'] as $index => $article) {
        $insertArticle->execute([
            '/assets/images/notebook.svg',
            $article['title'],
            $article['description'],
            $article['text'],
            ($index * 37) % 400,
            $firstPublication->modify('+' . $index . ' days')->format('Y-m-d H:i:s'),
        ]);
        $articleId = $pdo->lastInsertId();

        foreach ($article['categories'] as $key) {
            $insertRelation->execute([$articleId, $categoryIds[$key]]);
        }
    }

    $pdo->commit();
    echo 'Seeded 4 categories and 12 articles.' . PHP_EOL;
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
