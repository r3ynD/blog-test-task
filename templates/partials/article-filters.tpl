<form class="panel form-stack article-filters" action="/articles" method="get">
    <h2>Найти статью</h2>
    <label>По заголовку
        <input type="search" name="q" value="{$filters.q}" maxlength="150" placeholder="Например, MySQL">
    </label>
    <label>Категория
        <select name="category">
            <option value="">Все категории</option>
            {foreach $filterCategories as $category}
                <option value="{$category.id}" {if $filters.category == $category.id}selected{/if}>{$category.name}</option>
            {/foreach}
        </select>
    </label>
    <label>Ник автора
        <input name="author" value="{$filters.author}" maxlength="32" placeholder="Например, writer">
    </label>
    <p class="hint">Можно ввести начало ника.</p>
    <fieldset class="filter-period">
        <legend>Дата публикации</legend>
        <label>С <input type="date" name="from" value="{$filters.from}"></label>
        <label>По <input type="date" name="to" value="{$filters.to}"></label>
    </fieldset>
    <label>Сначала
        <select name="sort">
            <option value="date" {if $filters.sort != 'views'}selected{/if}>Новые</option>
            <option value="views" {if $filters.sort == 'views'}selected{/if}>Популярные</option>
        </select>
    </label>
    <div class="actions">
        <button type="submit" class="button">Применить</button>
        <a href="/articles">Сбросить</a>
    </div>
</form>
