{extends file="layout.tpl"}

{block name="content"}
    <div class="intro">
        <p class="eyebrow">Код · данные · рабочий процесс</p>
        <h1>Заметки о разработке</h1>
        <p>Небольшие статьи о коде, инструментах и решениях, которые пригодились в работе.</p>
    </div>

    {if $popular}
        <section class="popular-section" aria-labelledby="popular-heading">
            <div class="category-heading">
                <div><p class="eyebrow">Выбор читателей</p><h2 id="popular-heading">Читают чаще всего</h2></div>
                <a class="all-articles" href="/articles?sort=views">Все популярные <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="article-grid">
                {foreach $popular as $article}{include file="partials/article-card.tpl" article=$article eager=true}{/foreach}
            </div>
        </section>
    {/if}

    {foreach $categories as $category}
        <section class="category" aria-labelledby="category-{$category.id}">
            <div class="category-heading">
                <div>
                    <h2 id="category-{$category.id}">{$category.name}</h2>
                    <p>{$category.description}</p>
                </div>
                <a class="all-articles" href="/category?id={$category.id}">Все статьи <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="article-grid">
                {foreach $category.articles as $article}
                    {include file="partials/article-card.tpl" article=$article}
                {/foreach}
            </div>
        </section>
    {foreachelse}
        <p class="empty-state">Пока нет опубликованных статей. Загляните позже.</p>
    {/foreach}
{/block}
