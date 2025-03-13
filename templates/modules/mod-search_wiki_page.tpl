{tikimodule error=$module_params.error title=$tpl_module_title name="search_wiki_page" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {autocomplete element=".pagename" type="pagename"}
    <form class="forms" method="post" action="tiki-listpages.php">
        <input type="hidden" name="lang" value=""/>
        <div class="input-group">
            <input name="find" size="14" type="text" accesskey="s" aria-label="{tr}Find{/tr}" class="pagename form-control"{if isset($find)} value="{$find|escape}"{/if} />
            <button type="submit" class="wikiaction btn btn-info" name="search" value="{tr}Go{/tr}">{tr}Go{/tr}</button>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="exact_match" name="exact_match"{if $exact eq 'y'} checked="checked"{/if}>
            <label class="form-check-label" for="exact_match"><span style="white-space: nowrap">{tr}Exact match{/tr}</span></label>
        </div>
    </form>
{/tikimodule}
