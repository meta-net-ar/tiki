{if isset($viewTags)}
    {tikimodule error=$module_params.error title=$tpl_module_title name="freetag" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

        {include file="freetag_list.tpl" deleteTag="y"}

        {if $tiki_p_freetags_tag eq 'y'}
            {if !empty($freetag_error)}{$freetag_error}{/if}
            <div class="col-sm-12">
                <form name="addTags" method="post">
                    <div class="mb-3">
                        <input type="text" name="addtags" class="form-control"{if !empty($freetag_msg)} value="{$freetag_msg}"{/if} />
                        {if $prefs.feature_antibot eq 'y' && $user eq ''}
                            <table>{include file="antibot.tpl"}</table>
                        {/if}
                    </div>
                    <input type="submit" class="btn btn-primary btn-sm" name="Add" value="{tr}Add{/tr}" />
                    {help url="Tags" desc="{tr}Put tags separated by spaces. For tags with more than one word, use no spaces and put words together or enclose them with double quotes{/tr}"}

                </form>
            </div>
            {autocomplete element=":text[name=addtags]" type="tag" options="multiple: true, multipleSeparator: ' ';"}
        {/if}
    {/tikimodule}
{/if}
