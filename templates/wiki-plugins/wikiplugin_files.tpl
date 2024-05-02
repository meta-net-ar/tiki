{if $params.showtitle eq 'y'}
    {if $data}<h4>{$data|escape}</h4>{else}
    <h4>
        {if $category}
            {if is_array($category)}
                {tr}Categories:{/tr}
                {section name=ix loop=$category}
                    {$category[ix]|escape}
                    {if !$smarty.section.ix.last}, {/if}
                {/section}
            {else}
                {tr}Category:{/tr} {$category|escape}
            {/if}
            <br>
        {/if}
        {if empty($params.galleryId[1]) && $gal_info.name}{tr}File Gallery:{/tr} <a href="tiki-list_file_gallery.php?galleryId={$gal_info.galleryId}" title="{tr}list{/tr}">{$gal_info.name|escape}</a>{/if}
    </h4>
    {/if}
{/if}
{if $params.showfind eq 'y'}
    {include file="find.tpl"}
{/if}
{include file="list_file_gallery_content.tpl"}
{if $params.showupload eq 'y' && !empty($gal_info.galleryId)}
    <form enctype="multipart/form-data" action="tiki-upload_file.php" method="post">
        {ticket}
        <input type="hidden" name="submission" value="1">
        <input type="hidden" name="totalSubmissions" value="1">
        <input type="hidden" name="galleryId" value="{$gal_info.galleryId}">
        <input type="hidden" name="returnUrl" value="{$smarty.server.REQUEST_URI|escape}">
        <label for="name">{tr}Title:{/tr}</label> <input type="text" name="name[]" id="name" maxlength="250">
        <label for="description">{tr}Description:{/tr}</label> <input type="text" name="description[]" id="description" maxlength="250">
        <br>
        <input size="16" name="userfile[]" type="file">
        <input type="submit" class="btn btn-primary btn-sm" name="upload" value="{tr}Upload{/tr}">
    </form>
{/if}
