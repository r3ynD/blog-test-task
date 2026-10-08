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
