<?php

declare(strict_types=1);

if ((int) ini_get('zend.assertions') !== 1 || getenv('DB_HOST') !== 'mysql_test' || getenv('DB_NAME') !== 'blog_test') {
    throw new RuntimeException('Expected assertions and the disposable blog_test database on mysql_test.');
}
$pdo = require dirname(__DIR__) . '/config/database.php';

function request(string $path, array &$cookies, ?string $body = null, string $type = 'application/x-www-form-urlencoded'): array
{
    $cookie = [];
    foreach ($cookies as $name => $value) {
        $cookie[] = $name . '=' . $value;
    }
    $context = stream_context_create(['http' => [
        'method' => $body === null ? 'GET' : 'POST',
        'header' => 'Cookie: ' . implode('; ', $cookie) . "\r\nContent-Type: " . $type,
        'content' => $body ?? '', 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10,
    ]]);
    $html = file_get_contents('http://nginx_test' . $path, false, $context);
    if ($html === false) {
        throw new RuntimeException('HTTP request failed: ' . $path);
    }
    foreach ($http_response_header as $header) {
        if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $match)) {
            $cookies[$match[1]] = $match[2];
        }
    }
    preg_match('/^HTTP\/\S+ (\d+)/', $http_response_header[0], $status);
    return ['status' => (int) $status[1], 'html' => $html, 'headers' => $http_response_header];
}

function csrf(array $response): string
{
    if (!preg_match('/name="csrf" value="([a-f0-9]+)"/', $response['html'], $match)) {
        throw new RuntimeException('CSRF token missing from page: ' . substr($response['html'], 0, 200));
    }
    return $match[1];
}

$guest = $admin = $writer = $reader = [];
assert(request('/articles', $guest)['status'] === 200);
assert(request('/articles?author=writer&sort=views&page=2', $guest)['status'] === 200);
assert(request('/articles?from=2026-02-29', $guest)['status'] === 422);
assert(request('/articles?q[]=test', $guest)['status'] === 422);
assert(request('/articles?from=2026-01%00-01', $guest)['status'] === 422);
assert(request('/articles?page=999', $guest)['status'] === 404);
$articleId = (int) $pdo->query('SELECT MIN(id) FROM articles')->fetchColumn();
assert(request('/article?id=' . $articleId, $guest)['status'] === 200);
$profileId = (int) $pdo->query("SELECT id FROM users WHERE username = 'writer'")->fetchColumn();
assert(request('/profile?id=' . $profileId, $guest)['status'] === 200);
assert(request('/article/new', $guest)['status'] === 403);
assert(request('/admin/users', $guest)['status'] === 403);
$token = csrf(request('/register', $reader));
$username = 'http_' . bin2hex(random_bytes(4));
assert(request('/register', $reader, http_build_query([
    'csrf' => $token, 'username' => $username, 'name' => '<b>Reader</b>',
    'password' => 'http-test-password', 'role' => 'ROLE_ADMIN',
]))['status'] === 303);
$query = $pdo->prepare('SELECT u.id, r.code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.username = ?');
$query->execute([$username]);
$newUser = $query->fetch();
assert($newUser['code'] === 'ROLE_USER');
assert(request('/article/new', $reader)['status'] === 403);

foreach ([['admin', 'test-admin-password'], ['writer', 'test-writer-password']] as $account) {
    $jar = [];
    $token = csrf(request('/login', $jar));
    assert(request('/login', $jar, http_build_query([
        'csrf' => $token, 'username' => $account[0], 'password' => $account[1],
    ]))['status'] === 303);
    if ($account[0] === 'admin') {
        $admin = $jar;
    } else {
        $writer = $jar;
    }
}
assert(request('/article/delete?id=' . $articleId, $admin, 'csrf=wrong')['status'] === 403);
$token = csrf(request('/login', $guest));
assert(request('/login', $guest, http_build_query(['csrf' => $token, 'username' => 'admin', 'password' => 'wrong-password']))['status'] === 422);
$token = csrf(request('/article/new', $writer));
$categoryId = (int) $pdo->query('SELECT MIN(id) FROM categories')->fetchColumn();
$boundary = 'BlogTestBoundary';
$fields = ['csrf' => $token, 'title' => '<script>Post</script>', 'description' => 'Test description', 'text' => 'A test article.', 'category_ids[]' => $categoryId];
$body = '';
foreach ($fields as $name => $value) {
    $body .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"" . $name . "\"\r\n\r\n" . $value . "\r\n";
}
$prefix = $body . '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"image\"; filename=\"../../cover.php\"\r\nContent-Type: image/png\r\n\r\n";
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aGZkAAAAASUVORK5CYII=');
$end = "\r\n--" . $boundary . "--\r\n";
assert(request('/article/new', $writer, $prefix . '<svg onload="alert(1)"></svg>' . $end, 'multipart/form-data; boundary=' . $boundary)['status'] === 422);
assert(request('/article/new', $writer, $prefix . $png . str_repeat('x', 2 * 1024 * 1024) . $end, 'multipart/form-data; boundary=' . $boundary)['status'] === 422);
$largeImage = $png . str_repeat('x', 1536 * 1024);
assert(request('/article/new', $writer, $prefix . $largeImage . $end, 'multipart/form-data; boundary=' . $boundary)['status'] === 303);
$large = $pdo->query('SELECT id, image FROM articles ORDER BY id DESC LIMIT 1')->fetch();
$deleteToken = csrf(request('/article?id=' . $large['id'], $writer));
assert(request('/article/delete?id=' . $large['id'], $writer, http_build_query(['csrf' => $deleteToken]))['status'] === 303);
$response = request('/article/new', $writer, $prefix . $png . $end, 'multipart/form-data; boundary=' . $boundary);
assert($response['status'] === 303);
$created = $pdo->query('SELECT id, image, author_id FROM articles ORDER BY id DESC LIMIT 1')->fetch();
$id = (int) $created['id'];
assert(preg_match('~^/uploads/[a-f0-9]{32}\.png$~', $created['image']) === 1);
assert(request($created['image'], $guest)['status'] === 200);
$page = request('/article?id=' . $id, $reader);
assert(str_contains($page['html'], '&lt;script&gt;Post&lt;/script&gt;'));
assert(!str_contains($page['html'], '<script>Post</script>'));
assert(request('/article?id=' . $id, $reader, http_build_query(['csrf' => csrf($page), 'text' => '<script>Comment</script>']))['status'] === 303);
$page = request('/article?id=' . $id, $guest);
assert(str_contains($page['html'], '&lt;script&gt;Comment&lt;/script&gt;'));
assert(str_contains($page['html'], '&lt;b&gt;Reader&lt;/b&gt;'));
$guestToken = csrf(request('/login', $guest));
assert(request('/article?id=' . $id, $guest, http_build_query(['csrf' => $guestToken, 'text' => 'Guest comment']))['status'] === 403);
assert(request('/article/edit?id=' . $id, $reader)['status'] === 403);
$editToken = csrf(request('/article/edit?id=' . $id, $writer));
assert(request('/article/edit?id=' . $id, $writer, http_build_query([
    'csrf' => $editToken, 'title' => 'Edited title', 'description' => 'Edited description',
    'text' => 'Edited text', 'category_ids' => [$categoryId], 'author_id' => $newUser['id'],
]))['status'] === 303);
assert((int) $pdo->query('SELECT author_id FROM articles WHERE id = ' . $id)->fetchColumn() === (int) $created['author_id']);
$adminToken = csrf(request('/admin/users', $admin));
assert(request('/admin/users', $admin, http_build_query(['csrf' => $adminToken, 'user_id' => $newUser['id'], 'role' => 'ROLE_WRITER']))['status'] === 303);
assert(request('/article/edit?id=' . $id, $reader)['status'] === 403);
assert(request('/article/new', $reader)['status'] === 200);
assert(request('/admin/users', $admin, http_build_query(['csrf' => $adminToken, 'user_id' => $newUser['id'], 'role' => 'ROLE_USER']))['status'] === 303);
assert(request('/article/new', $reader)['status'] === 403);
$deleteToken = csrf(request('/article?id=' . $id, $admin));
assert(request('/article/delete?id=' . $id, $admin, http_build_query(['csrf' => $deleteToken]))['status'] === 303);
assert(request('/article?id=' . $id, $guest)['status'] === 404);
assert(request($created['image'], $guest)['status'] === 404);
$query = $pdo->prepare('DELETE FROM users WHERE id = ?');
$query->execute([$newUser['id']]);
echo 'HTTP registration, article CRUD, uploads, comments and access checks passed.' . PHP_EOL;
