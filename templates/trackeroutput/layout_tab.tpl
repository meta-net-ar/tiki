{tabset name="tracker_section_select"}
    {foreach $sections as $pos => $sect}
        {tab name=$sect.heading}
            <dl class="row mx-0">
                {if ! $pos}
                    {if $tracker_info.showStatus eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')}
                        {$ustatus=$info.status|default:"p"}
                        <dt title="{tr}Status{/tr}" class="col-sm-3">{tr}Status{/tr}</dt>
                        <dd class="col-sm-9">
                            {icon name=$status_types.$ustatus.iconname}
                            {$status_types.$ustatus.label}
                        </dd>
                    {/if}
                {/if}
                {foreach from=$sect.fields item=field}
                    <dt title="{$field.name|tra|escape}" class="col-sm-3">{$field.name|tra|escape}</dt>
                    <dd class="col-sm-9">{trackeroutput field=$field item=$item_info showlinks=n list_mode=n preview=$preview|default:false}</dd>
                {/foreach}
                {if $pos eq 0 and ($tracker_info.showCreatedView eq 'y' or $tracker_info.showLastModifView eq 'y')}
                    <hr class="my-3">
                    {if $tracker_info.showCreatedView eq 'y'}
                        <dt title="{tr}Created{/tr}" class="col-sm-3">{tr}Created{/tr}</dt>
                        <dd class="col-sm-9">{$info.created|tiki_long_datetime}{if $tracker_info.showCreatedBy eq 'y'}<br>{tr}by{/tr} {if $prefs.user_show_realnames eq 'y'}{if empty($info.createdBy)}Unknown{else}{$info.createdBy|username}{/if}{else}{if empty($info.createdBy)}Unknown{else}{$info.createdBy}{/if}{/if}{/if}</dd>
                    {/if}
                    {if $tracker_info.showLastModifView eq 'y'}
                        <dt title="{tr}LastModif{/tr}" class="col-sm-3">{tr}LastModif{/tr}</dt>
                        <dd class="col-sm-9">{$info.lastModif|tiki_long_datetime}{if $tracker_info.showLastModifBy eq 'y'}<br>{tr}by{/tr} {if $prefs.user_show_realnames eq 'y'}{if empty($info.lastModifBy)}Unknown{else}{$info.lastModifBy|username}{/if}{else}{if empty($info.lastModifBy)}Unknown{else}{$info.lastModifBy}{/if}{/if}{/if}</dd>
                    {/if}
                {/if}
            </dl>
        {/tab}
    {/foreach}
{/tabset}
