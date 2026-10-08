{extends file="layout.tpl"}
{block name="content"}
    <div class="category-heading intro">
        <div><p class="eyebrow">Рубрика</p><h1>{$category.name}</h1><p>{$category.description}</p></div>
    </div>
    <form class="sort-form" action="/category" method="get">
        <input type="hidden" name="id" value="{$category.id}">
        <label for="sort">Сначала</label>
        <select name="sort" id="sort">
            <option value="date" {if $sort == 'date'}selected{/if}>Новые</option>
            <option value="views" {if $sort == 'views'}selected{/if}>Популярные</option>
        </select>
        <button type="submit" class="theme-toggle">Показать</button>
    </form>
    <div class="article-grid">
        {foreach $articles as $article}{include file="partials/article-card.tpl" article=$article}
        {foreachelse}<p>В этой категории пока нет статей.</p>{/foreach}
    </div>
    <nav class="pagination" aria-label="Страницы категории">
        {if $page > 1}<a href="/category?id={$category.id}&amp;sort={$sort}&amp;page={$page-1}">Назад</a>{/if}
        <span>{$page} / {$pages}</span>
        {if $page < $pages}<a href="/category?id={$category.id}&amp;sort={$sort}&amp;page={$page+1}">Далее</a>{/if}
    </nav>
{/block}
