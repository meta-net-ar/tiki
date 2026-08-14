{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
<p>{tr}Know the username?{/tr}</p>
<form method="post" action="{service controller=social action=add_friend}">
    <p>
        <input type="text" class="form-control" name="username" value="{$username|escape}"/>
    </p>
    <div class="submit">
        <input type="submit" class="btn btn-primary btn-sm" value="{tr}Add{/tr}">
    </div>
</form>
{/block}
