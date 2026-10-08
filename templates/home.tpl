{extends file="layout.tpl"}

{block name="content"}
    <div class="intro">
        <h1>Заметки о разработке</h1>
        <p>Небольшие статьи о коде, инструментах и решениях, которые пригодились в работе.</p>
    </div>

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
                    <article class="article-card">
                        <time datetime="{$article.published_at|date_format:'%Y-%m-%d'}">{$article.published_at|date_format:'%d.%m.%Y'}</time>
                        <h3><a href="/article?id={$article.id}">{$article.title}</a></h3>
                        <p>{$article.description}</p>
                    </article>
                {/foreach}
            </div>
        </section>
    {foreachelse}
        <p class="empty-state">Пока нет опубликованных статей. Загляните позже.</p>
    {/foreach}
{/block}
