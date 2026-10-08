<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Session;
use App\Service\AuthService;
use App\Exception\ValidationException;
use App\Model\User;
use Smarty\Smarty;

class AuthController extends BaseController
{
    public function __construct(private AuthService $auth, Smarty $smarty, ?User $user)
    {
        parent::__construct($smarty, $user);
    }

    public function form(bool $register): void
    {
        $username = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
        $name = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $register
                    ? $this->auth->register($username, $name, $password)
                    : $this->auth->login($username, $password);
                Session::login($id);
                $this->redirect('/');
                return;
            } catch (ValidationException $exception) {
                http_response_code(422);
                $error = $exception->getMessage();
            }
        }
        $this->render('auth.tpl', [
            'pageTitle' => $register ? 'Регистрация' : 'Вход',
            'register' => $register, 'username' => $username, 'name' => $name, 'error' => $error,
        ]);
    }
}
