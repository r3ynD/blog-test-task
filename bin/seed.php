<?php

declare(strict_types=1);

try {
    $pdo = require dirname(__DIR__) . '/config/database.php';

    $hasBlogData = (
        $pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn() !== false
        || $pdo->query('SELECT id FROM articles LIMIT 1')->fetchColumn() !== false
    );

    $accounts = [
        ['admin', 'Алексей', 'ROLE_ADMIN', 'SEED_ADMIN_PASSWORD'],
        ['writer', 'Мария', 'ROLE_WRITER', 'SEED_WRITER_PASSWORD'],
        ['reader', 'Иван', 'ROLE_USER', 'SEED_READER_PASSWORD'],
    ];
    $findUser = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    foreach ($accounts as $account) {
        $findUser->execute([$account[0]]);
        $password = getenv($account[3]) ?: '';
        if ($findUser->fetchColumn() === false && (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0"))) {
            throw new RuntimeException('Set ' . $account[3] . ' to a password of 8–72 bytes before seeding.');
        }
    }

    $data = require dirname(__DIR__) . '/database/seeds/blog.php';
    $pdo->beginTransaction();
    $userIds = [];
    $newUsers = false;
    $insertUser = $pdo->prepare(
        'INSERT INTO users (username, name, password_hash, role_id) SELECT ?, ?, ?, id FROM roles WHERE code = ?'
    );
    foreach ($accounts as [$username, $name, $role, $variable]) {
        $findUser->execute([$username]);
        $userId = $findUser->fetchColumn();
        if ($userId === false) {
            $insertUser->execute([$username, $name, password_hash(getenv($variable), PASSWORD_DEFAULT), $role]);
            $userId = $pdo->lastInsertId();
            $newUsers = true;
        }
        $userIds[$username] = (int) $userId;
    }
    if (!$hasBlogData) {
        $categoryIds = [];
        $insertCategory = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        foreach ($data['categories'] as $key => $category) {
            $insertCategory->execute([$category['name'], $category['description']]);
            $categoryIds[$key] = $pdo->lastInsertId();
        }

        $insertArticle = $pdo->prepare(
            'INSERT INTO articles (author_id, image, title, description, text, views, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insertRelation = $pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
        $firstPublication = new DateTimeImmutable('2026-01-10 09:00:00');
        foreach ($data['articles'] as $index => $article) {
            $insertArticle->execute([
                $userIds['writer'], '/assets/images/notebook.svg', $article['title'], $article['description'],
                $article['text'], ($index * 37) % 400,
                $firstPublication->modify('+' . $index . ' days')->format('Y-m-d H:i:s'),
            ]);
            $articleId = $pdo->lastInsertId();
            foreach ($article['categories'] as $key) {
                $insertRelation->execute([$articleId, $categoryIds[$key]]);
            }
        }
    }

    $assignAuthor = $pdo->prepare('UPDATE articles SET author_id = ? WHERE author_id IS NULL');
    $assignAuthor->execute([$userIds['writer']]);
    if ($newUsers) {
        $articleId = $pdo->query('SELECT id FROM articles ORDER BY published_at, id LIMIT 1')->fetchColumn();
        if ($articleId !== false) {
            $insertComment = $pdo->prepare('INSERT INTO comments (article_id, user_id, text) VALUES (?, ?, ?)');
            $insertComment->execute([$articleId, $userIds['reader'], 'Спасибо за пример. Так гораздо понятнее, с чего начинать.']);
            $insertComment->execute([$articleId, $userIds['writer'], 'Рада, что пригодилось! Если останутся вопросы, пишите.']);
        }
    }

    $pdo->commit();
    echo 'Demo data ready: admin, writer, reader. Existing articles and passwords preserved.' . PHP_EOL;
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
