<div id="calscreen" class="my-3">
    {title}{tr}Tiki Action Calendar{/tr}{/title}
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-2">
        {if $viewlist neq 'list'}
            {if $group_by_item neq 'n'}
                {button href="?gbi=n" _text="{tr}Do not group by item{/tr}"}
            {else}
                {button href="?gbi=y" _text="{tr}Group by item{/tr}"}
            {/if}
        {/if}

        {button href="#" _onclick="toggle('filtercal');" _text="{tr}Filter{/tr}"}

        {if $viewlist eq 'list'}
            {button href="?viewlist=table" _text="{tr}Calendar View{/tr}"}
        {else}
            {button href="?viewlist=list" _text="{tr}List View{/tr}"}
        {/if}
    </div>
    <div class="row d-flex flex-md-nowrap mt-4 align-items-start no-gutters">
        <form id="filtercal" method="get" action="{$myurl}" name="f"
              class="col-12 col-md-2 flex-shrink-0 p-3 d-none">
            <h5 class="caltitle font-weight-bold mb-2">{tr}Tools Calendars{/tr}</h5>
            <ul class="list-unstyled ps-0 mb-1">
                <li class="mb-2 form-check">
                    {select_all checkbox_names='tikicals[]' label="{tr}Check / Uncheck All{/tr}"}
                </li>
                {foreach from=$tikiItems key=ki item=vi}
                    {if $vi.feature eq 'y' and $vi.right eq 'y'}
                        <li class="form-check">
                            <input type="checkbox" class="form-check-input" name="tikicals[]" value="{$ki|escape}"
                                   id="tikical_{$ki}" {if in_array($ki,$tikicals)}checked="checked"{/if}>
                            <label for="tikical_{$ki}" class="form-check-label Cal{$ki}"> = {$vi.label}</label>
                        </li>
                    {/if}
                {/foreach}
            </ul>
            <div class="calinput mt-3">
                <input type="submit" class="btn btn-primary btn-sm w-100" name="refresh" value="{tr}Refresh{/tr}">
            </div>
        </form>
        <div class="w-100 flex-grow-1 flex-shrink-1">
        {include file='tiki-calendar_nav.tpl'}

        {if $viewlist eq 'list'}
            {include file='tiki-calendar_listmode.tpl'}
        {/if}
        </div>
    </div>
</div>
