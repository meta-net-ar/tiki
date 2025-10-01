{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="mb-3">
        <a class="btn btn-link" href="{bootstrap_modal controller=calendar_availability action=create size='modal-lg'}" role="button">{icon name=create} {tr}New{/tr}</a>
    </div>
{/block}

{block name="content"}
    <div class="table-responsive">
        <table class="table">
            <tr>
                <th>{tr}UID{/tr}</th>
                <th>{tr}Created{/tr}</th>
                <th>{tr}Period{/tr}</th>
                <th>{tr}Summary{/tr}</th>
                <th>{tr}Description{/tr}</th>
                <th>{tr}Priority{/tr}</th>
                <th>{tr}Availability{/tr}</th>
                <th></th>
            </tr>
            {foreach $definitions as $row}
                <tr>
                    <td>{$row.uid|escape}</td>
                    <td>{$row.dtstamp|tiki_short_datetime}</td>
                    <td>
                        {if $row.dtstart && $row.duration}
                            {tr}From:{/tr} {$row.dtstart|tiki_short_datetime}<br/>
                            {tr}Duration:{/tr} {$row.duration|escape}
                        {elseif $row.dtstart && $row.dtend}
                            {$row.dtstart|tiki_short_datetime} - {$row.dtend|tiki_short_datetime}
                        {elseif $row.dtstart}
                            {$row.dtstart|tiki_short_datetime} - {tr}n/a{/tr}
                        {elseif $row.dtend}
                            {tr}n/a{/tr} - {$row.dtend|tiki_short_datetime}
                        {else}
                            {tr}unrecognized period{/tr}
                        {/if}
                    </td>
                    <td>{$row.summary|escape}</td>
                    <td>{$row.description|escape}</td>
                    <td>{$row.priority|escape}</td>
                    <td>{$row.available|count}</td>
                    <td class="action">
                        {actions}{strip}
                            <action>
                                <a href="{service controller=calendar_appointment action=slots user=$user uid=$row.uid}">
                                    {icon name=admin_calendar _menu_text='y' _menu_icon='y' alt="{tr}Schedule{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{bootstrap_modal controller=calendar_availability action=edit uid=$row.uid size='modal-lg'}">
                                    {icon name=edit _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a class="text-danger" href="{bootstrap_modal controller=calendar_availability action=delete uid=$row.uid}">
                                    {icon name=delete _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                </a>
                            </action>
                        {/strip}{/actions}
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="8">{tr}No availability components defined.{/tr}</td>
                </tr>
            {/foreach}
        </table>
    </div>
{/block}
