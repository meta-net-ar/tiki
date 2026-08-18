{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {remarksbox type="warning" title="{tr}Copy this token now{/tr}"}
        {tr}For security, this token is stored as a one-way verifier and will not be shown again.{/tr}
    {/remarksbox}
    <div class="input-group">
        <input id="new-api-token" type="text" class="form-control" value="{$token|escape}" readonly>
        <button type="button" class="btn btn-secondary copy" data-clipboard-target="#new-api-token">
            {icon name="clipboard"} {tr}Copy{/tr}
        </button>
    </div>
{/block}
