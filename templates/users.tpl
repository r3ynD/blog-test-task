{extends file="layout.tpl"}
{block name="content"}
    <div class="intro"><p class="eyebrow">Администрирование</p><h1>Пользователи</h1></div>
    {if $error}<p class="form-error" role="alert">{$error}</p>{/if}
    <div class="panel user-list">
        {foreach $users as $user}
            <div class="user-row">
                <div><a href="/profile?id={$user.id}">{$user.name}</a><p class="hint">@{$user.username}</p></div>
                <form class="actions" method="post">
                    <input type="hidden" name="csrf" value="{$csrf}">
                    <input type="hidden" name="user_id" value="{$user.id}">
                    <label>Роль для {$user.username}
                        <select name="role">
                            <option value="ROLE_USER" {if $user.role == 'ROLE_USER'}selected{/if}>Читатель</option>
                            <option value="ROLE_WRITER" {if $user.role == 'ROLE_WRITER'}selected{/if}>Автор</option>
                            <option value="ROLE_ADMIN" {if $user.role == 'ROLE_ADMIN'}selected{/if}>Администратор</option>
                        </select>
                    </label>
                    <button class="button" type="submit">Сохранить</button>
                </form>
            </div>
        {/foreach}
    </div>
    <nav class="pagination" aria-label="Страницы пользователей">
        {if $page > 1}<a href="/admin/users?page={$page-1}">Назад</a>{/if}
        <span>{$page} / {$pages}</span>
        {if $page < $pages}<a href="/admin/users?page={$page+1}">Далее</a>{/if}
    </nav>
{/block}
