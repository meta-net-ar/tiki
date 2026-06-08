{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
<form method="post" action="{service controller=search_stored action=edit queryId=$queryId}">
    <div class="mb-3 row">
        <label for="label" class="col-md-3 col-form-label">{tr}Label{/tr}</label>
        <div class="col-md-9">
            <input type="text" class="form-control" name="label" value="{$label|escape}"/>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="priority" class="col-md-3 col-form-label">{tr}Priority{/tr}</label>
        <div class="col-md-9">
            <select name="priority" class="form-select">
                {foreach $priorities as $key => $info}
                    <option value="{$key|escape}" {if $priority eq $key}selected{/if}>{$info.label|escape} - {$info.description|escape}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="label" class="col-md-3 col-form-label">{tr}Description{/tr}</label>
        <div class="col-md-9">
            {textarea _simple='y' _toolbars='n' _wysiwyg='n' name='description' id='description' codemirror='true' rows='5' class='form-control'}{$description|escape}{/textarea}
        </div>
    </div>
    <div class="mb-3 submit">
        <div class="col-md-9 offset-md-3">
            <input type="submit" class="btn btn-primary" value="{tr}Update{/tr}"/>
        </div>
    </div>
</form>
{/block}
