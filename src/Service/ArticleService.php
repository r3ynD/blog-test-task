<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Exception\ValidationException;
use App\Http\HttpException;
use App\Model\User;

class ArticleService
{
    public function __construct(
        private ArticleRepository $articles,
        private CategoryRepository $categories,
        private CommentRepository $comments,
        private ImageStorage $images
    ) {
    }

    public function getPage(int $id, int $page): ?array
    {
        $article = $this->articles->findById($id);
        if (!$article) {
            return null;
        }
        $pages = max(1, (int) ceil($this->comments->countByArticle($id) / 10));
        if ($page > $pages) {
            return null;
        }
        return [
            'article' => $article,
            'categories' => $this->categories->findByArticleId($id),
            'similar' => $this->articles->findSimilar($id),
            'comments' => $this->comments->findByArticle($id, ($page - 1) * 10),
            'page' => $page, 'pages' => $pages,
        ];
    }

    public function canEdit(?User $user, array $article): bool
    {
        return $user && ($user->role === 'ROLE_ADMIN'
            || ($user->role === 'ROLE_WRITER' && (int) $article['author_id'] === $user->id));
    }

    public function save(?array $article, User $user, array $data, ?array $file): int
    {
        if (!in_array($user->role, ['ROLE_WRITER', 'ROLE_ADMIN'], true)
            || ($article && !$this->canEdit($user, $article))) {
            throw new HttpException(403, 'У вас нет доступа к этой публикации.');
        }
        foreach (['title' => 255, 'description' => 1000, 'text' => 100000] as $field => $limit) {
            if ($data[$field] === '' || !mb_check_encoding($data[$field], 'UTF-8') || mb_strlen($data[$field]) > $limit) {
                throw new ValidationException('Заполните все поля. Лимиты: заголовок 255, описание 1000, текст 100000 символов.');
            }
        }
        $allowed = array_column($this->categories->findAll(), 'id');
        if (!$data['category_ids'] || array_diff($data['category_ids'], $allowed)) {
            throw new ValidationException('Выберите хотя бы одну существующую категорию.');
        }
        $image = $article['image'] ?? '';
        $uploaded = false;
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $image = $this->images->upload($file);
            $uploaded = true;
        }
        if ($image === '') {
            throw new ValidationException('Добавьте обложку статьи.');
        }
        try {
            $id = $this->articles->save($article['id'] ?? null, $user->id, $data, $image, $article['image'] ?? '');
        } catch (\Throwable $exception) {
            if ($uploaded) {
                $this->images->delete($image);
            }
            throw $exception;
        }
        if ($uploaded && $article) {
            $this->images->delete($article['image']);
        }
        return $id;
    }

    public function delete(array $article, User $user): void
    {
        if (!$this->canEdit($user, $article)) {
            throw new HttpException(403, 'У вас нет доступа к этой публикации.');
        }
        $this->articles->delete((int) $article['id']);
        $this->images->delete($article['image']);
    }

    public function addComment(int $articleId, int $userId, string $text): void
    {
        $text = trim($text);
        if ($text === '' || !mb_check_encoding($text, 'UTF-8') || mb_strlen($text) > 2000) {
            throw new ValidationException('Комментарий должен содержать от 1 до 2000 символов.');
        }
        $this->comments->create($articleId, $userId, $text);
    }
}
