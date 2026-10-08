<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\HttpException;
use App\Model\User;
use Smarty\Smarty;

abstract class BaseController
{
    public function __construct(protected Smarty $smarty, private ?User $currentUser = null)
    {
    }

    protected function render(string $template, array $data): void
    {
        $this->smarty->assign($data + [
            'currentUser' => $this->currentUser,
            'csrf' => $_SESSION['csrf'] ?? '',
        ]);
        $this->smarty->display($template);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303);
    }

    protected function getUser(): ?User
    {
        return $this->currentUser;
    }

    protected function requireUser(): User
    {
        return $this->currentUser ?? throw new HttpException(403, 'Войдите, чтобы выполнить это действие.');
    }
}
