<div class="navbar mb-4 clearfix">
    {button _text='{tr}Save Options{/tr}' _class='save_menu btn btn-sm disabled float-start mb-2' _type='primary'
    _ajax='n' _auto_args='save_menu,page_ref_id'}
    <ol class="new-option">
        <li id="node_new" class="new p-2">
            <div class="options--group-container">
                {if $editable_menu_info.use_items_icons eq 'y'}
                    <div class="label-group">
                        <input name="menu-icon" class="field-icon form-control" placeholder="Icon" />
                        {autocomplete type="icon" element=".field-icon"}
                    </div>
                {/if}
                <div class="label-group">
                    <div class="input-group input-group-sm" style="max-width: 100%">
                        <span class="input-group-text">{icon name='sort'}</span>
                        <input type="text" class="field-label form-control" value="" placeholder="{tr}New option{/tr}"
                            readonly="readonly">
                        <span class="tips input-group-text option-edit"
                            title="|{tr}Check this if the option is an alternative to the previous one.{/tr}">
                            <input type="checkbox" class="samepos">
                        </span>
                        <a href="javascript:void(0)" class="tips input-group-text "
                            title="{tr}New option{/tr}|{tr}Drag this on to the menu area below{/tr}">
                            {icon name='info'}
                        </a>
                    </div>
                </div>
                <div class="flex-grow-1 d-inline-block url-group">
                    <div class="input-group input-group-sm">
                        <a href="javascript:void(0)" class="input-group-text tips confirm" onclick='return false;'>
                            {icon name='link'}
                        </a>
                        <input type="text" class="field-url form-control" value=""
                            placeholder="{tr}URL or ((page name)){/tr}">
                        <a href="javascript:void(0)" class="input-group-text  option-edit">
                            {icon name='edit' _menu_icon='y' alt="{tr}Details{/tr}"}
                        </a>
                        <a href="javascript:void(0)" class="input-group-text text-danger option-remove"
                            disabled="disabled">
                            {icon name='remove' _menu_icon='y' alt="{tr}Remove{/tr}"}
                        </a>
                    </div>
                </div>
            </div>
        </li>
    </ol>
</div>
