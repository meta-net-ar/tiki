{tikimodule error=$module_params.error title=$tpl_module_title name="category_transition" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {if ! empty( $mod_transitions )}
        <form method="post" action="">
            {foreach from=$mod_transitions item=trans}
                <div class="form-check">
                    <input class="form-check-input" id="transition-{$trans.transitionId|escape}" type="radio" name="transition" value="{$trans.transitionId|escape}" {if ! $trans.enabled}disabled="disabled"{/if} />
                    <label class="form-check-label" for="transition-{$trans.transitionId|escape}">{$trans.name|escape}</label>
                    {if ! $trans.enabled}
                        <a href="#trans{$trans.transitionId|escape}" class="mouseover">Why?</a>
                        <div id="trans{$trans.transitionId|escape}">
                            {foreach item=reason from=$trans.explain}
                                {if $reason.class eq 'missing'}
                                    <p>{tr _0=$reason.count}Missing %0 of the following categories:{/tr}</p>
                                {elseif $reason.class eq 'extra'}
                                    <p>{tr _0=$reason.count}%0 extra of the following categories:{/tr}</p>
                                {elseif $reason.class eq 'unknown'}
                                    <p>{tr _0=$reason.count}Unknown comparison:{/tr}</p>
                                {elseif $reason.class eq 'invalid'}
                                    <p>{tr _0=$reason.count}Impossible condition, %0 of:{/tr}</p>
                                {/if}
                                <ul>
                                    {foreach from=$reason.set item=state}
                                        <li>{categoryName id=$state}</li>
                                    {/foreach}
                                </ul>
                            {/foreach}
                        </div>
                    {/if}
                </div>
            {/foreach}
            {jq}{literal}
                $('.mouseover:not(.done)')
                    .addClass('done')
                    .each( function( k, e ) {
                        $(e.href.substr(e.href.lastIndexOf('#'))).hide();
                    } )
                    .on("click", function( e ) {
                        e.preventDefault();
                        $(this.href.substr(this.href.lastIndexOf('#'))).toggle('fast');
                    } );
            {/literal}{/jq}
            <div><input type="submit" class="btn btn-primary btn-sm" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}"/></div>
        </form>
    {else}
        <div>
            {tr}There is no categorized object with category transitions available.{/tr}
        </div>
    {/if}
{/tikimodule}
