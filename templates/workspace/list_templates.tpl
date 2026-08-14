{$listTemplate=$list}
{*
    We assign $list to another name before calling the extends line below because
    the extends line below comes with another variable $list, which is also an array
    but contains the elements of the main menu. Therefore, we reassign it to avoid a collision
    between the two.
*}
{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title help="" admpage="workspace"}{$title|escape}{/title}
{/block}
<div class="styling-element">
{block name="navigation"}
    <div class="navbar">
        <a class="btn btn-primary" role="button" href="{bootstrap_modal controller=workspace action=add_template}">
            {icon name="create"} {tr}Create Workspace Template{/tr}
        </a>
        <a class="btn btn-primary" role="button" href="{bootstrap_modal controller=workspace action=create}">
            {icon name="create"} {tr}Create Workspace{/tr}
        </a>
    </div>
{/block}

{block name="content"}
    <div class="table-responsive">
        <table class="table table-hover">
            <tr>
                <th>{self_link _sort_arg='sort_mode' _sort_field='id'}{tr}Id{/tr}{/self_link}</th>
                <th>{self_link _sort_arg='sort_mode' _sort_field='name'}{tr}Name{/tr}{/self_link}</th>
                <th>{tr}Action{/tr}</th>
            </tr>
            {foreach from=$listTemplate item=template}
                <tr>
                    <td>
                        {$template.templateId}
                    </td>
                    <td>
                        {$template.name|escape}
                    </td>
                    <td>
                        {permission name=admin}
                            <a title="{tr}Edit{/tr}" class="btn btn-primary btn-sm service-dialog reload" role="button" href="{service controller=workspace action=edit_template id=$template.templateId}">{icon name="edit"} {tr}Edit{/tr}</a>
                            <span class="btn btn-link btn-sm">
                                {permission_link mode=text type=workspace id=$template.templateId title=$template.name}
                            </span>
                        {/permission}
                    </td>
                </tr>
            {/foreach}
        </table>
    </div>
{/block}
</div>
