{if $prefs.feature_categories eq 'y' and $tiki_p_modify_object_categories eq 'y' and ((is_array($categories) and count($categories) gt 0) or $tiki_p_admin_categories eq 'y')}
    {if !isset($labelcol)}
        {$labelcol = '4'}
    {/if}
    {if !isset($inputcol)}
        {if !isset($notable) || $notable neq 'y'}
            {$inputcol = '8'}
        {else}
            {$inputcol = '12'}
        {/if}
    {/if}
    {if !isset($notable) || $notable neq 'y'}
        <div class="mb-3 row">
            <div class="col-sm-{$labelcol} text-nowrap col-form-label pb-3{if !empty($labelclass)} {$labelclass}{/if}">
                {tr}Categorize{/tr}
            </div>
            <div class="col-sm-{$inputcol} pt-1">
                {button href="#" _flip_id='categorizator' _class='btn btn-primary btn-sm tips' _text="{tr}Select Categories{/tr}" _flip_default_open='n'}
            </div>
        </div>
    {/if}
    {if !isset($notable) || $notable neq 'y'}
        <div class="mb-3 row">
    {/if}
    {if $mandatory_category >= 0 or $prefs.javascript_enabled neq 'y' or (isset($auto) and $auto eq 'y')}
        <div id="categorizator">&nbsp;<div>
    {else}
        {if !isset($notable) || $notable neq 'y'}
            <div class="col-sm-{$labelcol}{if isset($inputgroup) && $inputgroup === 'y'} input-group{/if}">&nbsp;</div>
        {/if}
        <div id="categorizator" class="col-sm-{$inputcol}" style="display:{if isset($smarty.session.tiki_cookie_jar.show_categorizator) and $smarty.session.tiki_cookie_jar.show_categorizator eq 'y' or (isset($notable) && $notable eq 'y')}block{else}none{/if};">
    {/if}
    <div class="multiselect">
        {if is_array($categories) and count($categories) gt 0}
            {$cat_tree}
            <input type="hidden" name="cat_categorize" value="on">
            <div class="clearfix">
                {if $tiki_p_admin_categories eq 'y'}
                    <div class="float-sm-end">
                        <a class="btn btn-link btn-sm tips" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}">
                            {icon name="cog"} {tr}Categories{/tr}
                        </a>
                    </div>
                {/if}
                {select_all checkbox_names='cat_categories[]' label="{tr}Select/deselect all categories{/tr}"}
            </div> {* end .clear *}
        {else}
            <div class="clearfix">
                {if $tiki_p_admin_categories eq 'y'}
                    <div class="float-sm-end">
                        <a class="btn btn-link" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}">
                            {icon name="cog"} {tr}Categories{/tr}
                        </a>
                    </div>
                {/if}
            </div> {* end .clear *}
            {tr}No categories defined{/tr}
        {/if}
    </div> {* end #multiselect *}
    </div>{* end #categorizator *}
    {if !isset($notable) || $notable neq 'y'}
        </div> {* end .mb-3 *}
    {/if}
{/if}
{if $prefs.feature_theme_control_autocategorize eq 'y'}
    {jq}
        if ('{{$smarty.session.tc_theme_cat}}') {
            if ($('.tree ul[data-id="{{$prefs.feature_theme_control_parentcategory}}"] input[type=checkbox]:checked').length == 0) {
                $('#categ-{{$smarty.session.tc_theme_cat}}').prop("checked", true);
            }
        }
    {/jq}
{/if}
