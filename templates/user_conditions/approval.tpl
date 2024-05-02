{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <div class="card bg-body-tertiary">
        <div class="card-body">
            <div style="overflow: auto; max-height: 400px;">
            {$content}
            </div>
        </div>
    </div>
    <form method="post" action="{service controller=user_conditions action=approval}">
        <div class="form-check">
            <input name="approve" id="approve" type="checkbox" class="form-check-input" value="{$hash|escape}">
            <label for="approve" class="form-label">
                {tr}I approve the above terms and conditions{/tr}
            </label>
        </div>
        <input class="btn btn-lg btn-primary" type="submit" name="accept" value="{tr}Continue{/tr}">
        <input class="btn btn-sm btn-danger" type="submit" name="decline" value="{tr}I Decline, log out{/tr}">
        <input name="origin" value="{$origin|escape}" type="hidden">
    </form>
{/block}
