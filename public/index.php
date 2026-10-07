<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    echo 'Method not allowed.';
    return;
}

try {
    $smarty = require dirname(__DIR__) . '/bootstrap.php';
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    switch ($path) {
        case '/':
            $smarty->assign('pageTitle', 'Blog');
            $smarty->display('home.tpl');
            break;

        default:
            http_response_code(404);
            $smarty->assign('pageTitle', 'Page not found');
            $smarty->display('404.tpl');
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    echo 'Something went wrong. Please try again later.';
}
