{if $user}
    {tikimodule error=$module_params.error title=$tpl_module_title name="since_last_visit_new" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        {if $simple_view eq 'y'}
            {* Include the simple view template *}
            {include file='modules/mod-since_last_visit.tpl'}
        {else}
            <div style="margin-bottom: 5px; text-align:center;">
                {if $prefs.feature_calendar eq 'y' && $date_as_link eq 'y'}
                    <a class="linkmodule" href="tiki-calendar.php?todate={$slvn_info.lastLogin}" title="{tr}click to edit{/tr}">
                {/if}
                <b>{$slvn_info.lastLogin|tiki_short_date}</b>
                {if $prefs.feature_calendar eq 'y'}
                    </a>
                {/if}
            </div>
            {if $slvn_info.count == 0}
                <div class="separator">{tr}Nothing has changed{/tr}</div>
            {else}
                {if $use_jquery_ui eq "y"}
                    {$fragment=1}
                    <div class="tabs" data-name="since_last_visit_new_{$moduleId}">
                        <ul class="nav nav-tabs">
                            {foreach key=pos item=slvn_item from=$slvn_info.items}
                                {if $slvn_item.count > 0}
                                    <li class="nav-item text-center">
                                        <a data-bs-toggle="tab" href="#fragment-{$fragment}" class="nav-link">
                                            {if $pos eq "blogs"}
                                                {icon name="bold" size=1 ititle="Blogs"}
                                            {elseif $pos eq "blogPosts"}
                                                {icon name="bold" size=1 ititle="{tr}Blog Posts{/tr}"}
                                            {elseif $pos eq "articles"}
                                                {icon name="newspaper-o" size=1 ititle="{tr}Articles{/tr}"}
                                            {elseif $pos eq "posts"}
                                                {icon name="comments" size=1 ititle="{tr}Forums{/tr}"}
                                            {elseif $pos eq "fileGalleries"}
                                                {icon name="folder-open" size=1 ititle="{tr}File Galleries{/tr}"}
                                            {elseif $pos eq "files"}
                                                {icon name="file-o" size=1 ititle="{tr}Files{/tr}"}
                                            {elseif $pos eq "polls"}
                                                {icon name="tasks" size=1 ititle="{tr}Poll{/tr}"}
                                            {elseif $pos eq "pages"}
                                                {icon name="textfile" size=1 ititle="{tr}Wiki{/tr}"}
                                            {elseif $pos eq "comments"}
                                                {icon name="comments-o" size=1 ititle="{tr}Comments{/tr}"}
                                            {elseif $pos eq "forums"}
                                                {icon name="comments" size=1 ititle="{tr}Forums{/tr}"}
                                            {elseif $pos eq "trackers"}
                                                {icon name="database" size=1 ititle="{tr}Tracker Items{/tr} ({tr}New{/tr})"}
                                            {elseif $pos eq "utrackers"}
                                                {icon name="database" size=1 ititle="{tr}Tracker Items{/tr} ({tr}Updated{/tr})"}
                                            {elseif $pos eq "users"}
                                                {icon name="group" size=1 ititle="{tr}Users{/tr}"}
                                            {elseif $pos eq "calendar"}
                                                {icon name="calendar" size=1 ititle="{tr}Calendars{/tr}"}
                                            {elseif $pos eq "events"}
                                                {icon name="calendar" size=1 ititle="{tr}Events{/tr}"}
                                            {else}
                                                {$pos}
                                            {/if}
                                        </a>
                                    </li>
                                    {$fragment=$fragment+1}
                                {/if}
                            {/foreach}
                        </ul>
                    </div>
                    {$fragment=1}
                    {jq}
                    // Add CSS to hide non-active tabs content by default
                    $('<style>')
                        .text('.tab-pane:not(.active) .collapse-group { display: none !important; }')
                        .appendTo('head');

                    // Initialize - ensure only active tab content is shown
                    $(document).ready(function() {
                        // Find the active tab or set the first tab as active if none is active
                        let $activeLink = $('.nav-tabs .nav-link.active');
                        if ($activeLink.length === 0) {
                            $activeLink = $('.nav-tabs .nav-link').first();
                            $activeLink.addClass('active');
                        }
                        
                        let activeTabId = $activeLink.attr('href');
                        
                        // Hide all tab panes first
                        $('.tab-pane').removeClass('active');
                        
                        // Show active tab pane
                        $(activeTabId).addClass('active');
                        
                        // Hide all collapse groups
                        $('.collapse-group').hide();
                        
                        // Show only the active tab's collapse group
                        $(activeTabId).find('.collapse-group').show();
                    });

                    // Handle tab switching
                    $('.nav-tabs .nav-link').on('shown.bs.tab', function (e) {
                        let targetId = $(e.target).attr("href");
                        
                        // Hide all collapse groups
                        $('.collapse-group').hide();
                        
                        // Show only the active tab's collapse group
                        $(targetId).find('.collapse-group').show();
                    });

                    // Prevent collapse events from bubbling up
                    $('.collapse.tab-dependent').on('show.bs.collapse hide.bs.collapse', function (e) {
                        e.stopPropagation();
                    });
                    {/jq}
                {/if}
                <div class="tab-content">
                {foreach key=pos item=slvn_item from=$slvn_info.items}
                    {if $slvn_item.count > 0}
                        {if $use_jquery_ui eq "y"}
                            <div id="fragment-{$fragment}" class="tab-pane{if $fragment eq 1} active{/if}">
                        {/if}
                        {$cname=$slvn_item.cname}
                        
                        {* Comments section *}
                        {if $pos eq 'comments'}
                            {* Add debug output *}
                            <div class="collapse-group">
                                <div class="separator">
                                    <a class="separator" href="javascript:flip('{$cname|escape}');">
                                        {$slvn_item.count}&nbsp;{tr}new comments{/tr}
                                    </a>
                                </div>
                                <div class="tab-dependent collapse" id="{$cname|escape}" style="display:{if !isset($cookie.$showcname) or $cookie.$showcname eq 'y'}{$default_folding}{else}{$opposite_folding}{/if};">
                                    {if $nonums != 'y'}<ol>{else}<ul>{/if}
                                    {section name=ix loop=$slvn_item.list}
                                        <li>
                                            <a class="linkmodule" 
                                               href="{$slvn_item.list[ix].href|escape}"
                                               title="{$slvn_item.list[ix].title|escape|replace:'&amp;':'&'}"
                                            >
                                                {if $slvn_item.list[ix].label == ''}-{else}{$slvn_item.list[ix].label|escape}{/if}
                                            </a>
                                        </li>
                                    {/section}
                                    {if $nonums != 'y'}</ol>{else}</ul>{/if}
                                </div>
                            </div>
                        {else}
                            <div class="collapse-group">
                                <div class="separator">
                                    <a class="separator" href="javascript:flip('{$cname}');">
                                        {if $slvn_item.count eq $module_rows}
                                            {tr}Multiple{/tr} {$slvn_item.label}, {tr}including{/tr}
                                        {else}
                                            {$slvn_item.count}&nbsp;{$slvn_item.label}
                                        {/if}
                                    </a>
                                </div>
                                <div id="{$cname}" style="display:{if !isset($cookie.$showcname) or $cookie.$showcname eq 'y'}{$default_folding}{else}{$opposite_folding}{/if};">
                                    {if $pos eq 'trackers' or $pos eq 'utrackers'}
                                        {****** Parse out the trackers *****}
                                        {foreach key=tp item=tracker from=$slvn_item.tid}
                                            {$tcname=$tracker.cname}
                                            <div class="separator" style="margin-left: 10px; display:{if !isset($cookie.$showcname) or $cookie.$showcname eq 'y'}{$default_folding}{else}{$opposite_folding}{/if};">
                                                {$showtcname="show_"|cat:$tcname}
                                                <a class="separator" href="javascript:flip('{$tcname}');">{$tracker.count}&nbsp;{$tracker.label|escape}</a>
                                                <div id="{$tcname}" style="display:{if !isset($cookie.$showtcname) or $cookie.$showtcname eq 'y'}{$default_folding}{else}{$opposite_folding}{/if};">
                                                    {if $nonums != 'y'}<ol>{else}<ul>{/if}
                                                    {section name=xx loop=$tracker.list}
                                                        <li><a class="linkmodule"
                                                                    href="{$tracker.list[xx].href|escape}"
                                                                    title="{$tracker.list[xx].title|escape}">{if $tracker.list[xx].label == ''}-{else}{$tracker.list[xx].label|escape}{/if}
                                                                </a>
                                                        </li>
                                                    {/section}
                                                    {if $nonums != 'y'}</ol>{else}</ul>{/if}
                                                </div>
                                            </div>
                                        {/foreach}
                                        {****** End tracker section *****}
                                    {else}
                                        {if $nonums != 'y'}<ol>{else}<ul>{/if}
                                        {section name=ix loop=$slvn_item.list}
                                            <li>
                                                <a class="linkmodule"
                                                    href="{$slvn_item.list[ix].href|escape}"
                                                    title="{$slvn_item.list[ix].title|escape|replace:'&amp;':'&'}"
                                                >
                                                    {if $slvn_item.list[ix].label == ''}-{else}{$slvn_item.list[ix].label|escape}{/if}
                                                </a>
                                            </li>
                                        {/section}
                                        {if $nonums != 'y'}</ol>{else}</ul>{/if}
                                    {/if}
                                </div>
                            </div>
                        {/if}
                        {if $use_jquery_ui eq "y"}
                            </div>
                            {$fragment=$fragment+1}
                        {/if}
                    {/if}
                {/foreach}
                </div>
            {/if}
        {/if}
    {/tikimodule}
{/if}
