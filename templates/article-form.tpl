{extends file="layout.tpl"}
{block name="content"}
    <div class="reading-width">
        <p class="eyebrow">Редактор</p>
        <h1>{$pageTitle}</h1>
        <form class="panel form-stack" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="{$csrf}">
            {if $error}<p class="form-error" role="alert">{$error}</p>{/if}
            <label>Заголовок <input name="title" value="{$form.title}" maxlength="255" required></label>
            <label>Краткое описание <textarea name="description" rows="3" maxlength="1000" required>{$form.description}</textarea></label>
            <label>Текст статьи <textarea class="editor" name="text" rows="16" maxlength="100000" required>{$form.text}</textarea></label>
            <p class="hint">Обычный текст. Разделяйте абзацы пустой строкой; HTML не поддерживается.</p>
            <fieldset>
                <legend>Категории</legend>
                <div class="choices">
                    {foreach $categories as $category}
                        <label><input type="checkbox" name="category_ids[]" value="{$category.id}" {if in_array($category.id, $form.category_ids)}checked{/if}> {$category.name}</label>
                    {/foreach}
                </div>
            </fieldset>
            {if $article}<img class="cover-preview" src="{$article.image}" alt="Текущая обложка">{/if}
            <label>Обложка
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" {if !$article}required{/if}>
            </label>
            <p class="hint">JPEG, PNG или WebP до 2 МБ и 20 мегапикселей.{if $article} Оставьте поле пустым, чтобы сохранить текущую обложку.{/if}</p>
            <div class="actions">
                <button class="button" type="submit">{if $article}Сохранить изменения{else}Опубликовать{/if}</button>
                <a href="{if $article}/article?id={$article.id}{else}/{/if}">Отмена</a>
            </div>
        </form>
    </div>
{/block}
