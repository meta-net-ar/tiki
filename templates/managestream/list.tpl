{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="navigation"}
    {if $tiki_p_admin eq 'y'}
        <div class="t_navbar mb-4">
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {icon name="create"} {tr}Create{/tr}
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="{bootstrap_modal controller=managestream action=sample}">
                        {tr}Sample Rule{/tr}
                    </a>
                    <a class="dropdown-item" href="{bootstrap_modal controller=managestream action=record}">
                        {tr}Basic Rule (Record Event){/tr}
                    </a>
                    <a class="dropdown-item" href="{bootstrap_modal controller=managestream action=tracker_filter}">
                        {tr}Tracker Rule{/tr}
                    </a>
                    <a class="dropdown-item" href="{bootstrap_modal controller=managestream action=advanced}">
                        {tr}Advanced Rule{/tr}
                    </a>
                </div>
            </div>
            {button href="tiki-admin.php?page=community" _icon_name="settings" _text="{tr}Community{/tr}" _class="tips" _title=":{tr}Community Control Panel{/tr}"}
            {* former add_dracula() *}
            {$headerlib->add_jsfile('lib/dracula/raphael-min.js', true)}
            {$headerlib->add_jsfile('lib/dracula/graffle.js')}
            {$headerlib->add_jsfile('lib/dracula/graph.js')}
            <button href="#" id="graph-draw" class="btn btn-primary">{icon name="image"} {tr}Event Chain Diagram{/tr}</button>
            <div id="graph-canvas" class="graph-canvas" data-graph-nodes="{$event_graph.nodes|@json_encode|escape}" data-graph-edges="{$event_graph.edges|@json_encode|escape}"></div>
    {jq}
        $('#graph-draw').on("click", function(e) {
            const width = $window.width() - 50;
            const height = $window.height() - 130;
            
            $('#graph-canvas')
                .css('width', width)
                .css('height', height)
                .drawGraph();

            $.openModal({
                title: "{tr}Events{/tr}",
                size: 'modal-fullscreen',
                dialogVariants: ["scrollable", "center"],
                content: $('#graph-canvas'),
                open: function() {
                    $(this).on('hidden.bs.modal', function() {
                        // Keep the canvas in the DOM so that the graph can be redrawn on next click.
                        $('body').append($('#graph-canvas').empty());
                    });
                }
            })
            return false;
        });
    {/jq}
        </div>
    {/if}
{/block}
{block name="title"}
    {title}{tr}Activity Rules{/tr}{/title}
{/block}
{block name="content"}
    <div class="styling-element">
    <div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
        <table class="table table-hover">
            <tr>
                <th>{tr}ID{/tr}</th>
                <th>{tr}Status{/tr}</th>
                <th>{tr}Event Type{/tr}</th>
                <th>{tr}Rule Type{/tr}</th>
                <th>{tr}Description{/tr}</th>
                <th></th>
            </tr>
            {foreach from=$rules item=rule}
                <tr>
                    <td class="id">
                        {$rule.ruleId|escape}
                    </td>
                    <td class="text">
                        {if $rule.status eq 'enabled'}
                            <span class="text-success tips" title=":{tr}Enabled{/tr}">{icon name="toggle-on"}</span>
                        {elseif $rule.status eq 'disabled'}
                            <span class="tips" title=":{tr}Disabled{/tr}">{icon name="toggle-off"}</span>
                        {else}
                            <span class="text-warning tips" title=":{tr}Unknown{/tr}">{icon name="warning"}</span>
                        {/if}
                    </td>
                    <td class="text">
                        {$rule.eventType|escape}
                    </td>
                    <td class="text">
                        {$ruleTypes[$rule.ruleType]|escape}
                    </td>
                    <td class="text">
                        {$rule.notes|escape}
                    </td>
                    <td class="action">
                        {actions}
                            {strip}
                                <action>
                                    <a href="{bootstrap_modal controller=managestream action="{if $rule.ruleType eq "sample"}sample{elseif $rule.ruleType eq "record"}record{elseif $rule.ruleType eq "tracker_filter"}tracker_filter{elseif $rule.ruleType eq "advanced"}advanced{/if}" ruleId=$rule.ruleId}" data-rule-id="{$rule.ruleId|escape}">
                                        {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                </action>
                                {if $rule.ruleType eq "record"}
                                    <action>
                                        <a href="{bootstrap_modal controller=managestream action=change_rule_status ruleId=$rule.ruleId}">
                                            {if $rule.status eq "disabled"}
                                                {icon name="toggle-on" _menu_text='y' _menu_icon='y' alt="{tr}Enable{/tr}"}
                                            {elseif $rule.status eq "enabled"}
                                                {icon name="toggle-off" _menu_text='y' _menu_icon='y' alt="{tr}Disable{/tr}"}
                                            {/if}
                                        </a>
                                    </action>
                                {/if}
                                {if $rule.ruleType eq "sample" or $rule.ruleType eq "record"}
                                    <action>
                                        <a href="{bootstrap_modal controller=managestream action=change_rule_type ruleId=$rule.ruleId}">
                                            {icon name="exchange" _menu_text='y' _menu_icon='y' alt="{tr}Change Rule Type{/tr}"}
                                        </a>
                                    </action>
                                {/if}
                                <action>
                                    <a href="{bootstrap_modal controller=managestream action=delete ruleId=$rule.ruleId}">
                                        {icon name="delete" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                    </a>
                                </action>
                            {/strip}
                        {/actions}
                    </td>
                </tr>
            {/foreach}
        </table>
    </div>
    </div>
{/block}
