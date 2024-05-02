{compact}
    {if $tiki_p_search eq 'y'}
        {tikimodule error=$module_error title=$smod_params.title name="search" flip=$smod_params.flip decorations=$smod_params.decorations nobox=$smod_params.nobox notitle=$smod_params.notitle}
            {if $smod_params.tiki_search neq 'none'}
                <form id="search-module-form{$search_mod_usage_counter}" method="get" action="{$smod_params.search_action}"{if $smod_params.use_autocomplete eq 'y'} onsubmit="return submitSearch{$search_mod_usage_counter}()"{/if} style="position: relative;">
                    <div class="{if $smod_params.compact eq 'y'}input-group{else}mb-3{/if}{*d-flex flex-wrap mx-0 align-items-center*}">
                    {*    <div class="{*col-auto*}{*me-2"> *}
                            <input style="min-width: 4rem;{if $smod_params.compact eq "y"}{*width:72%;border-bottom-right-radius:0;border-top-right-radius: 0;*}{/if}" placeholder="{tr}Find{/tr}" class="form-control my-1" id="search_mod_input_{$search_mod_usage_counter}" name="{if $smod_params.search_action eq 'tiki-searchindex.php'}filter~content{else}find{/if}" {if !empty($smod_params.input_size)}size="{$smod_params.input_size}" style="width: auto"{/if} type="text" accesskey="s" value="{$smod_params.input_value|escape}">
                            <label class="sr-only" for="search_mod_input_{$search_mod_usage_counter}">Find</label>
                    {*    </div>*}
                            {if $smod_params.show_object_filter eq 'y'}
                                <label class="col-form-label" for="filterType">
                                    {tr}in{/tr}&nbsp;
                                </label>
                                <div class="col-auto mb-3">
                                    {if $smod_params.search_action eq 'tiki-searchindex.php'}
                                        <select id="filterType" name="filter~type" class="form-select my-1" {*style="width:{$smod_params.select_size}em;"*}>
                                            <option value="">{tr}Entire Site{/tr}</option>
                                            {if $prefs.feature_wiki eq 'y'}<option value="wiki page"{if $smod_params.where eq "wiki page"} selected="selected"{/if}>{tr}Wiki Pages{/tr}</option>{/if}
                                            {if $prefs.feature_blogs eq 'y'}<option value="blog post"{if $smod_params.where eq "blog post"} selected="selected"{/if}>{tr}Blog Posts{/tr}</option>{/if}
                                            {if $prefs.feature_articles eq 'y'}<option value="article"{if $smod_params.where eq "article"} selected="selected"{/if}>{tr}Articles{/tr}</option>{/if}
                                            {if $prefs.feature_file_galleries eq 'y'}<option value="file"{if $smod_params.where eq "file"} selected="selected"{/if}>{tr}Files{/tr}</option>{/if}
                                            {if $prefs.feature_forums eq 'y'}<option value="forum post"{if $smod_params.where eq "forum post"} selected="selected"{/if}>{tr}Forums{/tr}</option>{/if}
                                            {if $prefs.feature_trackers eq 'y'}<option value="trackeritem"{if $smod_params.where eq "trackeritem"} selected="selected"{/if}>{tr}Trackers{/tr}</option>{/if}
                                            {if $prefs.feature_sheet eq 'y'}<option value="sheet"{if $smod_params.where eq "sheet"} selected="selected"{/if}>{tr}Spreadsheets{/tr}</option>{/if}
                                        </select>
                                    {else}
                                        <select class="form-select my-1" name="where" {*style="width:{$smod_params.select_size}em;"*}>
                                            <option value="pages">{tr}Entire Site{/tr}</option>
                                            {if $prefs.feature_wiki eq 'y'}<option value="wikis"{if $smod_params.where eq "wikis"} selected="selected"{/if}>{tr}Wiki Pages{/tr}</option>{/if}
                                            {if $prefs.feature_directory eq 'y'}<option value="directory"{if $smod_params.where eq "directory"} selected="selected"{/if}>{tr}Directory{/tr}</option>{/if}
                                            {if $prefs.feature_file_galleries eq 'y'}<option value="files"{if $smod_params.where eq "files"} selected="selected"{/if}>{tr}Files{/tr}</option>{/if}
                                            {if $prefs.feature_articles eq 'y'}<option value="articles"{if $smod_params.where eq "articles"} selected="selected"{/if}>{tr}Articles{/tr}</option>{/if}
                                            {if $prefs.feature_forums eq 'y'}<option value="forums"{if $smod_params.where eq "forums"} selected="selected"{/if}>{tr}Forums{/tr}</option>{/if}
                                            {if $prefs.feature_blogs eq 'y'}
                                                <option value="blogs"{if $smod_params.where eq "blogs"} selected="selected"{/if}>{tr}Blogs{/tr}</option>
                                                <option value="posts"{if $smod_params.where eq "posts"} selected="selected"{/if}>{tr}Blog Posts{/tr}</option>
                                            {/if}
                                            {if $prefs.feature_faqs eq 'y'}<option value="faqs"{if $smod_params.where eq "faqs"} selected="selected"{/if}>{tr}FAQs{/tr}</option>{/if}
                                            {if $prefs.feature_trackers eq 'y'}<option value="trackers"{if $smod_params.where eq "trackers"} selected="selected"{/if}>{tr}Trackers{/tr}</option>{/if}
                                        </select>
                                    {/if}

                                </div>
                            {elseif !empty($prefs.search_default_where)}
                                {if is_array($prefs.search_default_where)}
                                    {foreach from=$prefs.search_default_where item=t}
                                        <input type="hidden" name="{if $smod_params.search_action eq 'tiki-searchindex.php'}filter~type[]{else}where[]{/if}" value="{$t|escape}" />
                                    {/foreach}
                                {else}
                                    <input type="hidden" name="{if $smod_params.search_action eq 'tiki-searchindex.php'}filter~type{else}where{/if}" value="{$prefs.search_default_where|escape}" />
                                {/if}
                            {/if}

                            {if $smod_params.compact eq "y"}
                                <button type="submit" class="btn btn-info my-1 search_mod_magnifier" aria-label="{tr}Search{/tr}">
                                    {icon name="search"}
                                </button>
                            </div>

                            <div class="btn-group search_mod_buttons box">
                            {else}
                                <div class="btn-group my-1">
                            {/if}
                            {foreach $smod_params.additional_filters as $key => $filter}
                                <input type="hidden" name="filter~{$key|escape}" value="{$filter|escape}"/>
                            {/foreach}
                            {if $smod_params.show_search_button eq 'y'}
                                <input type = "submit" class="btn btn-info btn-sm tips{if $smod_params.default_button eq 'search'} btn-primary button_default{/if}{if $smod_params.compact eq "y"} bottom{/if}"
                                    name = "search" value = "{$smod_params.search_submit|escape}"
                                    title="{tr}Search{/tr}|{tr}Search for text throughout the site.{/tr}"
                                    {if $smod_params.compact eq "y"}data-bs-placement="bottom"{/if}
                                    onclick = "$('#search-module-form{$search_mod_usage_counter}').attr('action', '{$smod_params.search_action|escape:javascript}').attr('page_selected','');"
                                />
                            {/if}
                            {if $smod_params.show_go_button eq 'y'}
                                <input type="hidden" name="exact_match" value="" />
                                <input type = "submit" class="btn btn-info btn-sm tips{if $smod_params.compact eq "y"} bottom{/if}{if $smod_params.default_button eq 'go'} btn-primary button_default{/if}"
                                    name = "go" value = "{$smod_params.go_submit|escape}"
                                    title="{tr}Go{/tr}|{tr}Go directly to a page, or search in page titles if exact match is not found.{/tr}"
                                    {if $smod_params.compact eq "y"}data-bs-placement="bottom"{/if}
                                    onclick = "$('#search-module-form{$search_mod_usage_counter}').attr('action', '{$smod_params.go_action|escape:javascript}').attr('page_selected','');
                                        {if $smod_params.search_action eq 'tiki-searchindex.php'}
                                            $('#search-module-form{$search_mod_usage_counter} input[name=\'filter~content\']').attr('name', 'find');
                                        {/if}
                                    "
                                >
                            {/if}
                            {if $smod_params.show_edit_button eq 'y' and $tiki_p_edit eq 'y'}
                                <input type = "submit" class = "btn btn-primary btn-sm tips{if $smod_params.compact eq "y"} bottom{/if}{if $smod_params.default_button eq 'edit'} btn-primary button_default{/if}"
                                    name = "edit" value = "{$smod_params.edit_submit|escape}"
                                    title="{tr}Edit{/tr}|{tr}Edit existing page or create a new one.{/tr}"
                                    {if $smod_params.compact eq "y"}data-bs-placement="bottom"{/if}
                                    onclick = "$('#search-module-form{$search_mod_usage_counter} input[name!={if $smod_params.search_action eq 'tiki-searchindex.php'}\'filter~content\'{else}\'find\'{/if}]').attr('name', '');
                                            $('#search-module-form{$search_mod_usage_counter} input[name={if $smod_params.search_action eq 'tiki-searchindex.php'}\'filter~content\'{else}\'find\'{/if}]').attr('name', 'page');
                                            $('#search-module-form{$search_mod_usage_counter}').attr('action', '{$smod_params.edit_action|escape:javascript}').attr('page_selected','');
                                    "
                                >
                            {/if}
                    {*    </div>*}
                    </div>


                    {if $smod_params.tiki_search neq 'y'}
                        {if $smod_params.advanced_search eq "y"}<input type="hidden" name="boolean" value="on" />{/if}
                        <input type="hidden" name="boolean_last" value="{$smod_params.advanced_search}" />
                    {/if}

                    {if $smod_params.compact eq "y"}
                        {jq}
$(".search_mod_magnifier").on("mouseover", function () {
    $(".search_mod_buttons", $(this).parents(".module"))
        .show('fast')
        .on("mouseleave", function () {
            $(this).hide('fast');
        });
}).on("click", function () {
    $(this).parents("form").trigger("submit");
});
$("#search_mod_input_{{$search_mod_usage_counter}}")
.on("keydown", function () {
    $(".search_mod_magnifier", $(this).parent()).trigger("mouseover");}
);
                        {/jq}
                    {else}
                        </div>
                    {/if}
                </form>
                {jq notonready=true}
                    function submitSearch{{$search_mod_usage_counter}}() {
                        var $f = $('#search-module-form{{$search_mod_usage_counter}}');
                        if ($f.attr('action') !== "tiki-editpage.php" && $f.data('page_selected') === $("#search_mod_input_{{$search_mod_usage_counter}}").val()) {
                            if ($f.find('input[name="find"]').length) {
                                $f.find('input[name="find"]').val($f.data('page_selected'));
                            } else {
                                $f.append($('<input name="find">').val($f.data('page_selected')));
                            }
                            $f.attr('action', '{{$smod_params.go_action|escape:javascript}}');
                        } else if ($f.attr('action') == "#") {
                            $f.attr('action', '{{$smod_params.search_action|escape:javascript}}');
                        }
                        $exact = $f.find("input[name=exact_match]");
                        if ($exact.val() != "y") {
                            $exact.remove(); // seems exact_match is true even if empty
                        }
                        return true;
                    }
                {/jq}
                {if $smod_params.use_autocomplete eq 'y'}
                    {capture name="selectFn"}select: function(event, item) {ldelim}
                        $('#search-module-form{$search_mod_usage_counter}').data('page_selected', item.item.value).find("input[name=exact_match]").val("y");
                        {rdelim}, open: function(event, item) {ldelim}
                        $(".search_mod_buttons", "#search-module-form{$search_mod_usage_counter}").hide();
                        {rdelim}, close: function(event, item) {ldelim}
                        $(".search_mod_buttons", "#search-module-form{$search_mod_usage_counter}").show();
                        {rdelim}{/capture}
                    {autocomplete element="#search_mod_input_"|cat:$search_mod_usage_counter type="pagename" options=$smarty.capture.selectFn}
                {/if}
            {/if}
        {/tikimodule}
    {/if}
{/compact}
