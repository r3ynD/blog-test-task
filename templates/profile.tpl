{extends file="layout.tpl"}
{block name="content"}
    <div class="intro">
        <p class="eyebrow">{$profile->roleName} · @{$profile->username}</p>
        <h1>{$profile->name}</h1>
        <p>Публикации пользователя</p>
    </div>
    <div class="article-grid">
        {foreach $articles as $article}{include file="partials/article-card.tpl" article=$article}
        {foreachelse}<p>Пока нет опубликованных статей.</p>{/foreach}
    </div>
    <nav class="pagination" aria-label="Страницы публикаций">
        {if $page > 1}<a href="/profile?id={$profile->id}&amp;page={$page-1}">Назад</a>{/if}
        <span>{$page} / {$pages}</span>
        {if $page < $pages}<a href="/profile?id={$profile->id}&amp;page={$page+1}">Далее</a>{/if}
    </nav>
{/block}
