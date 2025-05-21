<div class="mb-4 nohighlight">
    {if $prefs.feature_search_show_search_box eq 'y'}
        {filter action="tiki-searchindex.php" filter=$filter}{/filter}
    {/if}
</div><!--nohighlight-->
    {* do not change the comment above, since smarty 'highlight' outputfilter is hardcoded to find exactly this... instead you may experience white pages as results *}

{if isset($results)}
    {if $tiki_p_admin eq 'y'}
        {remarksbox type="info" title="{tr}Tip{/tr}"}
            <div>
                <form method="post" action="tiki-admin.php" class="d-flex flex-column ms-auto">
                    <label class="mb-2">{tr}Can’t find it? Click here to search preferences and features!{/tr}</label>
                    <input type="hidden" name="filters">
                    <input type="hidden" name="lm_criteria" value="{$filter['content']}">
                    <div class="col-3">
                        <input type="submit" class="btn btn-info" value="{tr}Search preferences{/tr}"/>
                    </div>
                </form>
            </div>
        {/remarksbox}
    {/if}
    {$results}
{/if}
