<article class="article-card">
    <a href="/article?id={$article.id}" tabindex="-1" aria-hidden="true">
        <img class="card-image" src="{$article.image}" alt="" loading="{if $eager|default:false}eager{else}lazy{/if}" width="960" height="480">
    </a>
    <div class="card-meta">
        <time datetime="{$article.published_at|date_format:'%Y-%m-%d'}">{$article.published_at|date_format:'%d.%m.%Y'}</time>
        <div class="card-stats">
            <span class="article-stat" role="img" aria-label="Просмотры: {$article.views}" title="Просмотры">
                <svg width="15" height="15" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                {$article.views}
            </span>
            <a class="article-stat" href="/article?id={$article.id}#comments" aria-label="Комментарии: {$article.comment_count}" title="Комментарии">
                <svg width="15" height="15" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round">
                    <path d="M20 15a3 3 0 0 1-3 3H9l-5 3V6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z"/>
                    <path d="M8 8h8M8 12h5"/>
                </svg>
                {$article.comment_count}
            </a>
        </div>
    </div>
    <h3><a href="/article?id={$article.id}">{$article.title}</a></h3>
    <p>{$article.description}</p>
    {if $article.author_username|default:''}
        <div class="card-author">
            <a href="/profile?id={$article.author_id}">@{$article.author_username}</a>
        </div>
    {/if}
</article>
