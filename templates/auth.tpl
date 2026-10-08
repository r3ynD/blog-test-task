{extends file="layout.tpl"}
{block name="content"}
    <div class="auth-layout">
        <div class="auth-intro">
            <p class="eyebrow">Заметки разработчика</p>
            <h1>{if $register}Присоединяйтесь к разговору{else}С возвращением{/if}</h1>
            <p>Читайте без регистрации. Аккаунт нужен, чтобы обсуждать статьи; авторы могут публиковать свои заметки.</p>
        </div>
        <form class="panel form-stack" method="post">
            <h2>{if $register}Создать аккаунт{else}Войти в аккаунт{/if}</h2>
            {if $error}<p class="form-error" role="alert">{$error}</p>{/if}
            <input type="hidden" name="csrf" value="{$csrf}">
            <label>Логин
                <input name="username" value="{$username}" autocomplete="username" minlength="3" maxlength="32" pattern="[A-Za-z0-9_]+" required>
            </label>
            {if $register}
                <label>Имя <input name="name" value="{$name}" autocomplete="name" maxlength="100" required></label>
            {/if}
            <label>Пароль
                <input type="password" name="password" autocomplete="{if $register}new-password{else}current-password{/if}" required {if $register}minlength="8"{/if}>
            </label>
            {if $register}<p class="hint">От 8 до 72 байт. Для латиницы один символ равен одному байту.</p>{/if}
            <button class="button" type="submit">{if $register}Зарегистрироваться{else}Войти{/if}</button>
            <p>{if $register}Уже есть аккаунт? <a href="/login">Войти</a>{else}Первый раз здесь? <a href="/register">Регистрация</a>{/if}</p>
        </form>
    </div>
{/block}
