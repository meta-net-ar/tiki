{title}{tr}Categorize Objects{/tr}{/title}
<div class="t_navbar mb-4 clearfix">
    {button href="tiki-browse_categories.php?parentId=$parentId" _type="link" _icon_name="view" _text="{tr}Browse Categories{/tr}" _title="{tr}Browse the category system{/tr}"}
    {if $tiki_p_admin_categories eq 'y'}
        {button href="tiki-admin_categories.php?parentId=$parentId" _type="link" _icon_name="settings" _text="{tr}Admin Categories{/tr}" _title="{tr}Admin the Category System{/tr}"}
    {/if}
</div>
{remarksbox title="{tr}Move objects between categories{/tr}"}
    <ol>
        <li>{tr}Select category to display the list of objects in that category.{/tr}</li>
        <li>{tr}Select the objects to affect{/tr}</li>
        <li>{tr}Select action to perform remove or add (first select){/tr}</li>
        <li>{tr}Select the destination categories (second select){/tr}</li>
    </ol>
{/remarksbox}
{filter action="tiki-edit_categories.php" filter=$filter}{/filter}
<hr>
<div class="row">
    <div class="mb-3 row">
        <label class="col-sm-5 col-form-label sr-only" for="toId">
            {tr}Select Objects from{/tr}
        </label>
        <div class="col-sm-7">
            <select name="toId" id="toId" class="form-select">
                <option value="" selected disabled>{tr}Filter objects by category{/tr}</option>
                <option value="orphan" data-href="tiki-edit_categories.php?filter~categories=orphan" data-categ="orphan"
                        {if ! empty($filter['categories']) and $filter['categories'] eq 'orphan'}selected{/if}>
                    {tr}Orphans{/tr}
                </option>
                {foreach $categories as $category}
                    {if $category.categId neq $parentId}
                        <option value="{$category.categId}" data-categ="{$category['categId']}" data-href="{$category['url']}" {if ! empty($filter['categories']) and $filter['categories'] eq $category['categId']}selected{/if}>
                            {$category.categpath|escape} - {$category.objects} {tr} Objects {/tr}
                        </option>
                    {/if}
                {/foreach}
            </select>
        </div>
    </div>
</div>
<form id="categorize-object" method="post" action="tiki-edit_categories.php">
    <div class="object-list">
        {if $result && count($result)}
            <table class="table normal table-striped table-hover" data-count="{count($result)|escape}">
                <thead>
                <tr>
                    <th scope="col">
                        {select_all checkbox_names='objects[]'}
                    </th>
                    <th scope="col">{tr}Object type{/tr}</th>
                    <th scope="col">{tr}Object Title{/tr}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$result item=object}
                    <tr {permission type=$object.type object=$object.object_id name="modify_object_categories"} class="available my-1"{/permission}>
                        <td>
                            <input class="ms-20 obj-item form-check-input " type="checkbox" name="objects[]" value="{$object.object_type|escape}:{$object.object_id|escape}">
                        </td>
                        <td>
                            {$object.object_type}
                        </td>
                        <td>
                            {object_link type=$object.object_type id=$object.object_id}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
            <div class="mb-3 row d-flex">
                <div class="col-sm-5">
                    <label class="col-sm-4 col-form-label sr-only" for="object_action">
                        {tr}Action to perform with checked{/tr}
                    </label>
                    <div class="row form-group">
                        <label for="object-check-action" class="sr-only">{tr}Select action to perform with checked{/tr}</label>
                        {*Note: placeholder attribute isn’t valid on a native <select>, but Element Plus uses it to show the default placeholder text.*}
                        <select id="object-check-action" class="form-select" name="object_action" required placeholder="{tr}Select action to perform with checked{/tr}">
                            <option value="no_action" selected disabled>
                                {tr}Select action to perform with checked{/tr}
                            </option>
                            <option value="categorize">
                                {tr}Add objects to categories{/tr}
                            </option>
                            <option value="uncategorize">
                                {tr}Remove objects from categories{/tr}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="col-form-label sr-only" for="categIds">
                        {tr}Copy to selected category{/tr}
                    </label>
                    <div class="row form-group">
                        <label for="category-select" class="sr-only">{tr}Destination categories{/tr}</label>
                       {*   Note: placeholder attribute isn’t valid on a native <select>, but Element Plus uses it to show the default placeholder text.                     *}
                        <select id="category-select" name="categIds[]" class="form-select" multiple required placeholder="{tr}Select destination categories{/tr}">
                            <option value="" disabled class="fw-bold">{tr}Select destination categories{/tr}</option>
                            {foreach $categories as $category}
                                {if $category.categId neq $parentId and ($category.can_add neq '' or $category.can_remove neq '')}
                                    <option value="{$category.categpath|escape}-{$category.categId}">
                                        {$category.categpath|escape}
                                    </option>
                                {/if}
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="input- col-sm-1">
                    <button
                            type="submit"
                            form="categorize-object"
                            formaction="{bootstrap_modal controller=category action=categorize}"
                            class="btn btn-primary"
                            onclick="confirmPopup()"
                    >
                        {tr}OK{/tr}
                    </button>
                </div>
            </div>
            {if $result->hasMore()}
                <p>{tr}More results are available. Please refine the search criteria.{/tr}</p>
            {/if}
        {else}
            {remarksbox type="tip" title="{tr}No results{/tr}"}
            {tr}There are no objects under this category {/tr}
            {/remarksbox}
        {/if}
    </div>
</form>
{jq}
    $('.object-list tr:not(.available) .obj-item:checkbox').attr('disabled', true);

    $("#toId").on("change", function () {
    const url = $(this).find("option:selected").attr('data-href');
    window.location = url;
    });
{/jq}
