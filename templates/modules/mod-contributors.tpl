{if isset($contributors_details)}
{tikimodule title=$tpl_module_title name="contributors" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle error=$module_params.error}
    <div class="contributors w-100">
        {foreach from=$contributors_details item=contributor name=contributors}
            <div class="mb-3 p-1 border rounded w-100">
                <div class="d-flex align-items-start w-100">
                    <div class="flex-grow-1 text-break">
                        <div class="d-flex align-items-start mb-1">
                            <div class="fw-semibold small">
                                {$contributor.login|userlink}
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                {$contributor.avatar}
                            </div>
                        </div>
                        {if !empty($contributor.realName)}
                            <div class="text-body small">{$contributor.realName|escape}</div>
                        {/if}

                        {if isset($contributor.country)}
                            <div class="text-muted small">
                                {$contributor.login|countryflag}
                                {tr}{$contributor.country|stringfix}{/tr}
                            </div>
                        {/if}

                        {if isset($contributor.email)}
                            <div class="text-muted small">
                                {$contributor.scrambledEmail}
                            </div>
                        {/if}

                        {if !empty($contributor.homePage)}
                            <div class="small">
                                <a href="{$contributor.homePage|escape}" class="link" target="_blank">
                                    {tr}Homepage{/tr}
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>

        {/foreach}

        {if isset($hiddenContributors)}
            <a href="#" class="show-more">
                {if $hiddenContributors eq 1}
                    {tr}1 more contributor{/tr}
                {else}
                    {tr _0=$hiddenContributors}%0 more contributors{/tr}
                {/if}
            </a>

            {jq}
                $('div.contributors').each(function() {
                $(this).children('div:gt(4)').hide();
                });

                $('div.contributors > .show-more').on("click", function() {
                $(this).siblings('div').show();
                $(this).hide();
                return false;
                });
            {/jq}
        {/if}
    </div>
{/tikimodule}
{/if}
