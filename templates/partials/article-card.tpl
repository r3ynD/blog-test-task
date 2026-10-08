<article class="article-card">
    <a href="/article?id={$article.id}" tabindex="-1" aria-hidden="true">
        <img class="card-image" src="{$article.image}" alt="" loading="lazy" width="960" height="540">
    </a>
    <time datetime="{$article.published_at|date_format:'%Y-%m-%d'}">{$article.published_at|date_format:'%d.%m.%Y'}</time>
    <h3><a href="/article?id={$article.id}">{$article.title}</a></h3>
    <p>{$article.description}</p>
    {if $article.author_username|default:''}
        <div class="card-author">
            <a href="/profile?id={$article.author_id}">@{$article.author_username}</a>
            <span class="hint">Просмотры: {$article.views}</span>
        </div>
    {/if}
</article>
