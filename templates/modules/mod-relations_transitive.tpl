{if $mod_transitive_has_results}
    {tikimodule error=$module_params.error title=$tpl_module_title name="relations_transitive" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        <div class="mod-transitive-relations">
            {if ($nonums eq 'y')}<ul class="list-unstyled">{else}<ol class="list-unstyled">{/if}
            {foreach from=$mod_transitive_relations item=level key=depth}
                {foreach from=$level.items item=item}
                    <li class="transitive-item mb-1">
                        {object_link type=$item.type id=$item.itemId title=$item.title}
                        {if $depth > 1}
                            <span class="badge rounded-pill bg-light text-dark border small fw-normal ms-1" style="font-size: 0.75em; vertical-align: middle;">
                                {tr}Indirect{/tr}
                            </span>
                        {/if}
                    </li>
                {/foreach}
            {/foreach}
            {if ($nonums eq 'y')}</ul>{else}</ol>{/if}
            
            {if $mod_transitive_total_count > 0}
                <div class="text-muted small mt-2">
                    {tr _0=$mod_transitive_total_count _1=$mod_transitive_max_depth}Found %0 relations across %1 levels{/tr}
                </div>
            {/if}
        </div>
    {/tikimodule}
{else}
    {* Only show module if there are results *}
{/if}
