<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Smarty\Smarty;
use App\Http\HttpException;
use App\Model\User;

class UserController extends BaseController
{
    public function __construct(
        private UserRepository $users,
        private ArticleRepository $articles,
        Smarty $smarty,
        ?User $user
    ) {
        parent::__construct($smarty, $user);
    }

    public function profile(int $id, int $page): void
    {
        $profile = $this->users->findById($id);
        $pages = max(1, (int) ceil($this->articles->countByAuthor($id) / 6));
        if (!$profile || $page > $pages) {
            throw new HttpException(404, 'Профиль не найден.');
        }
        $this->render('profile.tpl', [
            'pageTitle' => $profile->name, 'profile' => $profile,
            'articles' => $this->articles->findByAuthor($id, ($page - 1) * 6),
            'page' => $page, 'pages' => $pages,
        ]);
    }

    public function admin(int $page): void
    {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $role = $_POST['role'] ?? null;
            if (!$id || !in_array($role, ['ROLE_USER', 'ROLE_WRITER', 'ROLE_ADMIN'], true) || !$this->users->findById($id)) {
                http_response_code(422);
                $error = 'Проверьте пользователя и роль.';
            } elseif (!$this->users->changeRole($id, $role)) {
                http_response_code(422);
                $error = 'Нельзя понизить роль последнего администратора.';
            } else {
                $this->redirect('/admin/users?page=' . $page);
                return;
            }
        }
        $pages = max(1, (int) ceil($this->users->count() / 10));
        if ($page > $pages) {
            throw new HttpException(404, 'Страница не найдена.');
        }
        $this->render('users.tpl', [
            'pageTitle' => 'Пользователи', 'users' => $this->users->findForAdmin(($page - 1) * 10),
            'page' => $page, 'pages' => $pages, 'error' => $error,
        ]);
    }
}
