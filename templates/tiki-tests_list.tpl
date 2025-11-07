{title help="tests"}{tr}TikiTests List{/tr}{/title}

{include file='tiki-tests_menubar.tpl'}

{if $prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y'}
    {pagination_links count=$files_number offset=$offset step=$files_per_page}{/pagination_links}
{/if}

<div class="table-responsive">
<table class="table" id="tests-list-table">
<thead>
<tr>
    <th>{tr}File Name{/tr}</th>
    <th>{tr}Actions{/tr}</th>
</tr>
</thead>

<tbody id="tests-list-body">
{foreach from=$files item=file}
<tr>
    <td class="text">{$file}</td>
    <td class="action">
        {self_link action={tr}Remove{/tr} filename="$file" _ajax='n' _icon_name='delete'}{tr}Remove{/tr}{/self_link}
        {self_link _script='tiki_tests/tiki-tests_replay.php' action="{tr}Config{/tr}" filename="$file" _ajax='n' _icon_name='next'}{tr}Replay{/tr}{/self_link}
        {self_link _script='tiki_tests/tiki-tests_edit.php' action="{tr}Show{/tr}" filename="$file" _ajax='n' _icon_next='edit'}{tr}Edit{/tr}{/self_link}
    </td>
</tr>
{/foreach}
</tbody>
</table>
{if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
    <el-infinite-scroll container-id="tests-list-table" target-container-id="tests-list-body" offset="{$offset}" step="{$files_per_page}" total-count="{$files_number}"></el-infinite-scroll>
{/if}
</div>

{if $prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y'}
    {pagination_links count=$files_number offset=$offset step=$files_per_page}{/pagination_links}
{/if}
