{$showdescriptions = false}
{foreach from=$data.files item=file}
    {if not empty($file.description|escape)}
        {$showdescriptions = true}
    {/if}
{/foreach}
<div id="display_f{$field.fieldId|escape}" class="files-field display_f{$field.fieldId|escape}">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th>{tr}File{/tr}</th>
            <th>{tr}Date{/tr}</th>
            {if $showdescriptions}<th>{tr}Description{/tr}</th>{/if}
            <th>{tr}Actions{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$data.files item=file}
            <tr>
                <td>
                    {$file.fileId|sefurl:'file'|iconify:$file.filetype:$file.fileId:2}
                    {if $file.filetype|strpos:'image' === 0}
                        {object_link type="display" id=$file.fileId title=$file.name}
                    {else}
                        {object_link type="file" id=$file.fileId title=$file.name}
                    {/if}
                </td>
                <td>{$file.lastModif|tiki_short_datetime}</td>
                {if $showdescriptions}<td>{$file.description|escape}</td>{/if}
                <td>
                    <a class="fgalname tips" title="{tr}Actions{/tr}" href="#" role="button" {popup fullhtml="1" text={include file='tracker/fileTrackerContextMenu.tpl' menu_icon=$prefs.use_context_menu_icon menu_text=$prefs.use_context_menu_text changes=$smarty.section.changes.index} trigger="click"}>
                        {icon name='wrench' alt="{tr}Actions{/tr}"}
                    </a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
