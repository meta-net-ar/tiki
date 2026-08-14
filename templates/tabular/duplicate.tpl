{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="nav d-inline-flex">
        {permission name=admin_trackers}
            <a class="btn btn-link" href="{service controller=tabular action=manage}" role="button">{icon name=list} {tr}Manage{/tr}</a>
        {/permission}
    </div>
{/block}

{block name="content"}
    <form method="post" action="{service controller=tabular action=duplicate tabularId=$tabularId}">
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}Name{/tr}</label>
            <div class="col-sm-9">
                <input class="form-control" type="text" name="name" value="{$name}" required>
            </div>
        </div>
        <div class="mb-3 submit">
            <div class="col-sm-9 offset-sm-3">
                <input type="submit" class="btn btn-primary" value="{tr}Create{/tr}">
            </div>
        </div>
    </form>
{/block}
