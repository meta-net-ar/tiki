{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
<form method="post" action="{service controller="calendar_availability" action="delete" uid=$uid}">
    <p>{tr}Are you sure you want to delete this item?{/tr}</p>
    <div class="submit">
        <input type="submit" class="btn btn-danger" value="{tr}Delete item{/tr}">
    </div>
</form>
{/block}
