{extends file="layout.tpl"}
{block name="content"}
    <div class="intro">
        <p class="eyebrow">Архив журнала</p>
        <h1>Все статьи</h1>
        <p>Найдите публикацию по теме, автору или дате.</p>
    </div>
    <div class="article-catalog">
        <aside aria-label="Фильтры статей">
            {include file="partials/article-filters.tpl"}
        </aside>
        <section aria-label="Результаты поиска">
            {if $error}
                <p class="form-error" role="alert">{$error}</p>
            {else}
                <div class="catalog-heading">
                    <h2>Публикации</h2>
                    <p class="hint">Найдено: {$total}</p>
                </div>
                <div class="article-grid">
                    {foreach $articles as $article}
                        {include file="partials/article-card.tpl" article=$article}
                    {foreachelse}
                        <div class="panel empty-state">
                            <h3>Ничего не найдено</h3>
                            <p>Попробуйте другой запрос или расширьте период публикации.</p>
                            <a href="/articles">Показать все статьи</a>
                        </div>
                    {/foreach}
                </div>
                {if $pages > 1}
                    <nav class="pagination" aria-label="Страницы результатов поиска">
                        {if $previousUrl}<a href="{$previousUrl}">Назад</a>{/if}
                        <span>Страница {$page} из {$pages}</span>
                        {if $nextUrl}<a href="{$nextUrl}">Далее</a>{/if}
                    </nav>
                {/if}
            {/if}
        </section>
    </div>
{/block}
