<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\ArticleService;
use App\Exception\ValidationException;
use App\Http\HttpException;
use App\Model\User;
use Smarty\Smarty;

class ArticleController extends BaseController
{
    public function __construct(
        private ArticleService $service,
        private ArticleRepository $articles,
        private CategoryRepository $categories,
        Smarty $smarty,
        ?User $user
    ) {
        parent::__construct($smarty, $user);
    }

    public function show(int $id, int $page): void
    {
        $data = $this->service->getPage($id, $page);
        if (!$data) {
            throw new HttpException(404, 'Статья не найдена.');
        }
        $error = '';
        $text = is_string($_POST['text'] ?? null) ? $_POST['text'] : '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $this->requireUser();
            try {
                $this->service->addComment($id, $user->id, $text);
                $this->redirect('/article?id=' . $id . '#comments');
                return;
            } catch (ValidationException $exception) {
                http_response_code(422);
                $error = $exception->getMessage();
            }
        }
        $this->render('article.tpl', $data + [
            'pageTitle' => $data['article']['title'],
            'canEdit' => $this->service->canEdit($this->getUser(), $data['article']),
            'error' => $error, 'commentText' => $text,
        ]);
    }

    public function edit(?int $id): void
    {
        $user = $this->requireUser();
        $article = $id === null ? null : $this->articles->findById($id);
        if ($id !== null && !$article) {
            throw new HttpException(404, 'Статья не найдена.');
        }
        if ($article && !$this->service->canEdit($user, $article)) {
            throw new HttpException(403, 'Эту статью может изменить её автор или администратор.');
        }
        $data = [
            'title' => $article['title'] ?? '', 'description' => $article['description'] ?? '',
            'text' => $article['text'] ?? '',
            'category_ids' => $id === null ? [] : array_column($this->categories->findByArticleId($id), 'id'),
        ];
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['title', 'description', 'text'] as $field) {
                $data[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
            }
            $data['category_ids'] = [];
            foreach (is_array($_POST['category_ids'] ?? null) ? $_POST['category_ids'] : [] as $value) {
                $categoryId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
                if ($categoryId !== false && !in_array($categoryId, $data['category_ids'], true)) {
                    $data['category_ids'][] = $categoryId;
                } else {
                    $error = 'Некорректный список категорий.';
                }
            }
            try {
                if ($error !== '') {
                    throw new ValidationException($error);
                }
                $savedId = $this->service->save($article, $user, $data, $_FILES['image'] ?? null);
                $this->redirect('/article?id=' . $savedId);
                return;
            } catch (ValidationException $exception) {
                http_response_code(422);
                $error = $exception->getMessage();
            }
        }
        $this->render('article-form.tpl', [
            'pageTitle' => $article ? 'Редактирование статьи' : 'Новая статья',
            'article' => $article, 'form' => $data, 'error' => $error,
            'categories' => $this->categories->findAll(),
        ]);
    }

    public function delete(int $id): void
    {
        $user = $this->requireUser();
        $article = $this->articles->findById($id);
        if (!$article) {
            throw new HttpException(404, 'Статья не найдена.');
        }
        if (!$this->service->canEdit($user, $article)) {
            throw new HttpException(403, 'Вы не можете удалить эту статью.');
        }
        $this->service->delete($article, $user);
        $this->redirect('/');
    }
}
