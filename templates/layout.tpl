<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$pageTitle}</title>
    <script src="/assets/theme.js"></script>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <header>
        <a class="brand" href="/">Заметки разработчика</a>
        <nav aria-label="Основная навигация">
            <a href="/">Журнал</a>
            <a href="/articles">Все статьи</a>
            {if $currentUser|default:null}
                <a href="/profile?id={$currentUser->id}">{$currentUser->name}</a>
                {if $currentUser->role == 'ROLE_WRITER' || $currentUser->role == 'ROLE_ADMIN'}
                    <a class="button" href="/article/new">Написать статью</a>
                {/if}
                {if $currentUser->role == 'ROLE_ADMIN'}<a href="/admin/users">Пользователи</a>{/if}
                <form action="/logout" method="post">
                    <input type="hidden" name="csrf" value="{$csrf}">
                    <button class="text-button" type="submit">Выйти</button>
                </form>
            {else}
                <a href="/login">Вход</a>
                <a href="/register">Регистрация</a>
            {/if}
        </nav>
        <button class="theme-toggle" id="theme-toggle" type="button" aria-pressed="false" hidden>
            Тёмная тема
        </button>
    </header>
    <main>
        {block name="content"}{/block}
    </main>
    <footer>О PHP, базах данных и повседневной разработке.</footer>
</body>
</html>
