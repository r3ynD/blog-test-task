{extends file="layout.tpl"}
{block name="content"}
    <article class="reading-width full-article">
        <div class="tags">
            {foreach $categories as $category}<a href="/category?id={$category.id}">{$category.name}</a>{/foreach}
        </div>
        <h1>{$article.title}</h1>
        <p class="article-description">{$article.description}</p>
        <div class="article-meta">
            {if $article.author_id}<a href="/profile?id={$article.author_id}">{$article.author_name}</a>{else}<span>Редакция</span>{/if}
            <time datetime="{$article.published_at|date_format:'%Y-%m-%d'}">{$article.published_at|date_format:'%d.%m.%Y'}</time>
            <span>Просмотры: {$article.views}</span>
        </div>
        <img class="article-cover" src="{$article.image}" alt="Обложка статьи «{$article.title}»">
        <div class="article-text">{$article.text}</div>
        {if $canEdit}
            <div class="actions panel">
                <a href="/article/edit?id={$article.id}">Редактировать</a>
                <details class="delete-action">
                    <summary>Удалить статью</summary>
                    <form action="/article/delete?id={$article.id}" method="post">
                        <input type="hidden" name="csrf" value="{$csrf}">
                        <p>Статья и её комментарии будут удалены.</p>
                        <button class="button danger" type="submit">Подтвердить удаление</button>
                    </form>
                </details>
            </div>
        {/if}
    </article>
    {if $similar}
        <section class="related-section">
            <h2>Похожие статьи</h2>
            <div class="article-grid">{foreach $similar as $related}{include file="partials/article-card.tpl" article=$related}{/foreach}</div>
        </section>
    {/if}
    <section class="reading-width" id="comments">
        <h2>Обсуждение</h2>
        {if $error}<p class="form-error" role="alert">{$error}</p>{/if}
        {if $currentUser}
            <form class="panel form-stack" action="/article?id={$article.id}#comments" method="post">
                <input type="hidden" name="csrf" value="{$csrf}">
                <label>Ваш комментарий <textarea name="text" rows="3" maxlength="2000" required>{$commentText}</textarea></label>
                <button class="button" type="submit">Отправить</button>
            </form>
        {else}<p><a href="/login">Войдите</a> или <a href="/register">зарегистрируйтесь</a>, чтобы оставить комментарий.</p>{/if}
        {foreach $comments as $comment}
            <article class="comment">
                <div class="article-meta">
                    <a href="/profile?id={$comment.user_id}">{$comment.name}</a>
                    {if $comment.role == 'ROLE_WRITER'}<span class="badge">Автор</span>{/if}
                    {if $comment.role == 'ROLE_ADMIN'}<span class="badge">Администратор</span>{/if}
                    <time datetime="{$comment.created_at|date_format:'%Y-%m-%dT%H:%M:%S'}">{$comment.created_at|date_format:'%d.%m.%Y %H:%M'}</time>
                </div>
                <p class="comment-text">{$comment.text}</p>
            </article>
        {foreachelse}<p class="hint">Пока никто не оставил комментарий.</p>{/foreach}
        <nav class="pagination" aria-label="Страницы комментариев">
            {if $page > 1}<a href="/article?id={$article.id}&amp;page={$page-1}#comments">Назад</a>{/if}
            <span>{$page} / {$pages}</span>
            {if $page < $pages}<a href="/article?id={$article.id}&amp;page={$page+1}#comments">Далее</a>{/if}
        </nav>
    </section>
{/block}
