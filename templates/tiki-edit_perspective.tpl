{title help="Perspectives"}{tr}Perspectives{/tr}{/title}
{tabset}

    {tab name="{tr}List{/tr}"}
        <h2>{tr}List{/tr}</h2>
        <a href="tiki-switch_perspective.php">{tr}Return to the default perspective{/tr}</a>
        <div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
            <table class="table table-striped table-hover">
                <tr>
                    <th>{tr}Perspective{/tr}</th>
                    <th>{tr}Preferences{/tr}</th>
                    <th></th>
                </tr>

                {foreach from=$perspectives item=persp}
                    <tr>
                        <td class="text">
                            {if !empty($persp.can_edit)}
                                {self_link _icon_name='edit' action=edit _ajax='y' _menu_text='y' _menu_icon='y' id=$persp.perspectiveId cookietab=3}
                                    {$persp.name|escape}
                                {/self_link}
                            {else}
                                <a href="tiki-switch_perspective.php?perspective={$persp.perspectiveId|escape:url}">
                                    {icon name='move' _menu_icon='y' alt="{tr}Switch to{/tr}"} {$persp.name|escape}
                                </a>
                            {/if}
                        </td>
                        <td style="font-size:smaller;">
                            {foreach from=$persp.preferences key=name item=val}
                                {if is_array($val)}
                                    {$name}={$val|join:','}<br>
                                {else}
                                    {$name}={$val}<br>
                                {/if}
                            {/foreach}
                        </td>
                        <td class="action">
                            {actions}
                                {strip}
                                    <action>
                                        <a href="tiki-switch_perspective.php?perspective={$persp.perspectiveId|escape:url}">
                                            {icon name='move' _menu_text='y' _menu_icon='y' alt="{tr}Switch to{/tr}"}
                                        </a>
                                    </action>
                                    {if !empty($persp.can_perms)}
                                        <action>
                                            {permission_link mode=text type="perspective" id=$persp.perspectiveId title=$persp.name}
                                        </action>
                                    {/if}
                                        {if !empty($persp.can_edit)}
                                        <action>
                                            {self_link _icon_name='edit' action=edit _ajax='y' _menu_text='y' _menu_icon='y' id=$persp.perspectiveId cookietab=3}
                                                {tr}Edit{/tr}
                                            {/self_link}
                                        </action>
                                    {/if}
                                    {if !empty($persp.can_remove)}
                                        <action>
                                            <form action="tiki-edit_perspective.php" method="post">
                                                {ticket}
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="id" value="{$persp.perspectiveId}">
                                                <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to remove this perspective?{/tr}')" aria-label="{tr}Delete{/tr}">
                                                    {icon name='delete' _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                                </button>
                                            </form>
                                        </action>
                                    {/if}
                                {/strip}
                            {/actions}
                        </td>
                    </tr>
                {/foreach}
            </table>
        </div>
        {pagination_links offset=$offset step=$prefs.maxRecords count=$count}{/pagination_links}
    {/tab}

    {if $tiki_p_perspective_create eq 'y'}
        {tab name="{tr}Create{/tr}"}
            <h2>{tr}Create{/tr}</h2>
            <form method="post" action="tiki-edit_perspective.php" class="d-flex flex-row flex-wrap align-items-center">
                    <label for="name" class="col-form-label me-2">{tr}Name:{/tr} </label>
                    <input type="text" name="name" class="form-control me-2">
                <input type="submit" class="btn btn-primary me-2" name="create" value="{tr}Create{/tr}">
            </form>
        {/tab}
    {/if}

    {if $perspective_info && $perspective_info.can_edit}
        {tab name="{tr}Edit{/tr}"}
            <h2>{tr}Edit{/tr}</h2>
            <form method="post" action="tiki-edit_perspective.php">
                <div class="mb-3 row clearfix">
                    <label for="name" class="col-sm-2 col-form-label">{tr}Name{/tr}</label>
                    <div class="col-sm-10">
                        <input type="text" name="name" id="name" value="{$perspective_info.name|escape}" class="form-control">
                    </div>
                    <input type="hidden" name="id" value="{$perspective_info.perspectiveId|escape}">
                </div>
                <div class="col-sm-10 offset-sm-2">
                    <fieldset id="preferences" class="card dropzone mb-4">
                        <div class="card-header">{tr}Preference List{/tr}</div>
                        <div class="card-body mb-4">
                            {foreach from=$perspective_info.preferences key=name item=val}
                                <div class="d-flex justify-content-between align-items-center wrapper">
                                    <div class="w-100">
                                    {preference name=$name source=$perspective_info.preferences}
                                    </div>
                                    <button type="button" class="btn btn-link text-danger" onclick="$('.{$name}').closest('.wrapper').remove()" aria-label="{tr}Delete{/tr}">{icon name='delete'}</button>
                                </div>
                            {/foreach}
                    </fieldset>
                </div>
                <div class="col-sm-10 offset-sm-2 text-center mb-4">
                    <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
                </div>
            </form>
            <form method="post" id="searchform" action="tiki-edit_perspective.php" class="form offset-sm-2 clearfix">
                {remarksbox type="info" title="{tr}Hint{/tr}"}
                    {tr}Search preferences below and drag them into the preference list above.{/tr}
                {/remarksbox}
                <div class="card">
                    <input type="hidden" name="id" value="{$perspective_info.perspectiveId|escape}">
                    <div class="card-body">
                        <div class="input-group">
                            <span class="input-group-text">
                                {icon name="search"}
                            </span>
                            <input id="criteria" type="text" name="criteria" class="form-control" placeholder="{tr}Search preferences{/tr}...">
                            <input type="submit" class="btn btn-info" value="{tr}Search{/tr}">
                        </div>
                    </div>
                    <div class="card-footer">
                        <fieldset id="resultzone" class="dropzone"></fieldset>
                    </div>
                </div>
            </form>
            {jq}
                Sortable.create($('#preferences .card-body')[0], {
                    group: 'preferences',
                    animation: 150,
                    sort: false
                });
                $('#searchform').on("submit", function(e) {
                    e.preventDefault();
                    if (typeof ajaxLoadingShow == 'function') { ajaxLoadingShow('resultzone'); }
                    $('#resultzone').load( this.action, $(this).serialize(), function() {
                         Sortable.create($('#resultzone')[0], {
                            group: 'preferences',
                            animation: 150,
                            sort: false
                         });
                        $(this).tiki_popover();
                        if (typeof ajaxLoadingHide == 'function') { ajaxLoadingHide(); }
                    } );
                } );
            {/jq}
        {/tab}
    {/if}
{/tabset}
