<?php

declare(strict_types=1);

use App\Http\HttpException;
use App\Http\Session;
use App\Repository\UserRepository;

header('Content-Type: text/html; charset=utf-8');

try {
    $smarty = require dirname(__DIR__) . '/bootstrap.php';
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $methods = [
        '/' => ['GET', 'HEAD'], '/articles' => ['GET', 'HEAD'], '/category' => ['GET', 'HEAD'], '/login' => ['GET', 'HEAD', 'POST'],
        '/register' => ['GET', 'HEAD', 'POST'], '/logout' => ['POST'],
        '/article' => ['GET', 'HEAD', 'POST'], '/article/new' => ['GET', 'HEAD', 'POST'],
        '/article/edit' => ['GET', 'HEAD', 'POST'], '/article/delete' => ['POST'],
        '/article/view' => ['POST'],
        '/profile' => ['GET', 'HEAD'], '/admin/users' => ['GET', 'HEAD', 'POST'],
    ];
    if (!isset($methods[$path])) {
        throw new HttpException(404, 'Страница не найдена.');
    }
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods[$path], true)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $methods[$path]));
        echo 'Method not allowed.';
        return;
    }
    Session::start();
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    $pdo = require dirname(__DIR__) . '/config/database.php';
    $users = new UserRepository($pdo);
    $user = isset($_SESSION['user_id']) ? $users->findById((int) $_SESSION['user_id']) : null;
    $smarty->assign(['currentUser' => $user, 'csrf' => $_SESSION['csrf'], 'pageTitle' => 'Заметки разработчика']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 3 * 1024 * 1024) {
        throw new HttpException(413, 'Обложка должна быть не больше 2 МБ.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Session::checkCsrf()) {
        throw new HttpException(403, 'Форма устарела. Обновите страницу и отправьте её ещё раз.');
    }
    $writerRoute = in_array($path, ['/article/new', '/article/edit', '/article/delete'], true);
    if (($writerRoute && (!$user || !in_array($user->role, ['ROLE_WRITER', 'ROLE_ADMIN'], true)))
        || ($path === '/admin/users' && (!$user || $user->role !== 'ROLE_ADMIN'))) {
        throw new HttpException(403, 'Для этой страницы нужны соответствующие права.');
    }
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
    $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
    if (!$page || (in_array($path, ['/article', '/article/view', '/article/edit', '/article/delete', '/profile', '/category'], true) && !$id)) {
        throw new HttpException(404, 'Страница не найдена.');
    }
    $controllers = require dirname(__DIR__) . '/config/controllers.php';

    switch ($path) {
        case '/':
            $controllers['home']->index();
            break;
        case '/articles':
            $controllers['articles']->index($_GET, $page);
            break;
        case '/register':
        case '/login':
            $controllers['auth']->form($path === '/register');
            break;
        case '/logout':
            Session::logout();
            header('Location: /', true, 303);
            break;
        case '/article':
            $controllers['article']->show($id, $page);
            break;
        case '/article/view':
            $controllers['article']->countView($id);
            break;
        case '/article/new':
        case '/article/edit':
            $controllers['article']->edit($path === '/article/new' ? null : $id);
            break;
        case '/article/delete':
            $controllers['article']->delete($id);
            break;
        case '/profile':
            $controllers['user']->profile($id, $page);
            break;
        case '/category':
            $sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : 'date';
            $controllers['category']->show($id, $page, $sort);
            break;
        case '/admin/users':
            $controllers['user']->admin($page);
            break;
    }
} catch (HttpException $exception) {
    http_response_code($exception->status);
    $smarty->assign([
        'pageTitle' => $exception->status === 404 ? 'Страница не найдена' : 'Не удалось выполнить запрос',
        'message' => $exception->getMessage(),
    ]);
    $smarty->display($exception->status === 404 ? '404.tpl' : 'message.tpl');
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    echo 'Something went wrong. Please try again later.';
}
