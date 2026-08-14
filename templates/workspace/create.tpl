{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=workspace action=create}" role="form" class="form">
        <div class="mb-3 row">
            <label for="template" class="col-form-label">
                {tr}Template{/tr}
            </label>
            <select name="template" class="form-select">
                {foreach from=$templates item=template}
                    <option value="{$template.templateId|escape}">{$template.name|escape}</option>
                {/foreach}
            </select>
        </div>
        <div class="mb-3 row">
            <label for="name" class="col-form-label">
                {tr}Workspace Name{/tr}
            </label>
            <input type="text" name="name" value="" class="form-control"/>
        </div>
        <div class="submit">
            <input type="submit" class="btn btn-primary" value="{tr}Create{/tr}"/>
        </div>
    </form>
{/block}
