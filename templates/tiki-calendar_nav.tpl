{strip}
    {if !isset($ajax)}{$ajax='y'}{/if}
    {if !isset($module)}{$module='n'}{/if}
    {if !isset($viewmode) && isset($calendarViewMode)}{$viewmode=$calendarViewMode}{/if}

    {if empty($module_params.viewnavbar) || $module_params.viewnavbar eq 'y'}
        {if $viewlist eq 'list'}
            <div class="calendar-goto-container mb-3">
                <input type="date" class="form-control date-calendar w-50" value="{$focusdate|date_format:'%Y-%m-%d'}"
                    onchange="location.href='{query _type='relative' viewmode=$viewmode viewlist=$viewlist todate='__DATE__'}'.replace('__DATE__', Math.floor(new Date(this.value + 'T00:00:00').getTime() / 1000));">
            </div>
        {/if}
        <div class="tabrow mb-3 {if $module eq 'y'}p-0{/if}">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="calendar-nav d-flex gap-2 align-items-center">
                    {strip}
                        {*previous*}
                        {if $viewlist eq 'list'}
                            <a class="tips btn btn-outline-primary btn-sm align-self-center flex-shrink-0" href="{query _type='relative' _ajax=$ajax _class='prev' todate=$focus_prev viewmode=$viewmode viewlist=$viewlist}"
                               title=":{tr _0=$viewmode|escape}Previous %0 {/tr}">
                                {icon name="previous"}
                            </a>
                        {else}
                            <a class="tips btn btn-outline-primary btn-sm align-self-center flex-shrink-0" href="{query _type='relative' _ajax=$ajax _class='prev' todate=$focus_prev viewmode=$viewmode}"
                               title=":{tr _0=$viewmode|escape}Previous %0 {/tr}">
                                {icon name="previous"}
                            </a>
                        {/if}
                        {*viewmodes*}
                        <div class="d-flex gap-2 flex-wrap" role="toolbar" aria-label="...">
                        {if $module neq 'y'}
                            {button _ajax=$ajax href="?viewmode=week" _title=":{tr}Week{/tr}" _text="{tr}Week{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'week'}y{else}n{/if}"}
                            {button _ajax=$ajax href="?viewmode=month" _title=":{tr}Month{/tr}" _text="{tr}Month{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'month'}y{else}n{/if}"}
                        {else}
                            {button _ajax=$ajax viewmode='week' _auto_args="viewmode" _keepall='y' _title=":{tr}Week{/tr}" _text="{tr}W{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'week'}y{else}n{/if}"}
                            {button _ajax=$ajax viewmode='month' _auto_args="viewmode" _keepall='y' _title=":{tr}Month{/tr}" _text="{tr}M{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'month'}y{else}n{/if}"}
                        {/if}

                        {if $module neq 'y'}
                            {button _ajax=$ajax href="?viewmode=quarter" _title=":{tr}Quarter{/tr}" _text="{tr}Quarter{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'quarter'}y{else}n{/if}"}
                            {button href="?viewmode=semester" _title=":{tr}Semester{/tr}" _text="{tr}Semester{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'semester'}y{else}n{/if}"}
                            {button href="?viewmode=year" _ajax=$ajax viewmode=year _title=":{tr}Year{/tr}" _text="{tr}Year{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'year'}y{else}n{/if}"}
                        {else}
                            {button _ajax=$ajax viewmode='quarter' _auto_args="viewmode" _keepall='y' _title=":{tr}Quarter{/tr}" _text="{tr}Q{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'quarter'}y{else}n{/if}"}
                            {button _ajax=$ajax viewmode='semester' _auto_args="viewmode" _keepall='y' _title=":{tr}Semester{/tr}" _text="{tr}S{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'semester'}y{else}n{/if}"}
                            {button _ajax=$ajax viewmode='year' _auto_args="viewmode" _keepall='y' _title=":{tr}Year{/tr}" _text="{tr}Y{/tr}" _selected_class="buttonon" _type="xs btn-primary tips" _selected="{if $viewmode == 'year'}y{else}n{/if}"}
                        {/if}
                        </div>

                        {*next*}
                        {if $viewlist eq 'list'}
                            <a class="tips btn btn-outline-primary btn-sm align-self-center flex-shrink-0" href="{query _type='relative' _ajax=$ajax _class='next' todate=$focus_next viewmode=$viewmode viewlist=$viewlist}"
                               title=":{tr _0=$viewmode|escape}Next %0{/tr}">
                                {icon name="next"}
                            </a>
                        {else}
                            <a class="tips btn btn-outline-primary btn-sm align-self-center flex-shrink-0" href="{query _type='relative' _ajax=$ajax _class='next' todate=$focus_next viewmode=$viewmode}"
                               title=":{tr _0=$viewmode|escape}Next %0{/tr}">
                                {icon name="next"}
                            </a>
                        {/if}
                    {/strip}
                </div>
            </div>
        </div>
    {/if}

    {if $viewmode ne 'day'}
        <div class="text-center my-3">
            {*previous*}
            {if !empty($module_params.viewnavbar) && $module_params.viewnavbar eq 'partial'}
                {self_link _ajax=$ajax _class="prev tips text-info" todate=$focus_prev viewmode=$viewmode _title=":{tr _0=$viewmode|escape}Previous %0{/tr}" _icon_name="previous"}{/self_link}
            {/if}

            {if $viewlist ne 'list' or $prefs.calendar_list_begins_focus ne 'y'}
                {if $viewmode eq 'month'}
                    {$daystart|tiki_date_format:"%B %Y"}
                {elseif $viewmode eq 'week'}
                    {* test display_field_order and use %d/%m or %m/%d *}
                    {if ($prefs.display_field_order eq 'DMY') || ($prefs.display_field_order eq 'DYM') || ($prefs.display_field_order eq 'YDM')}
                        {$daystart|tiki_date_format:"{tr}%d/%m{/tr}/%Y"} - {$dayend|tiki_date_format:"{tr}%d/%m{/tr}/%Y"}
                    {else}
                        {$daystart|tiki_date_format:"{tr}%m/%d{/tr}/%Y"} - {$dayend|tiki_date_format:"{tr}%m/%d{/tr}/%Y"}
                    {/if}
                {else}
                    {$daystart|tiki_date_format:"%B %Y"} - {$dayend|tiki_date_format:"%B %Y"}
                {/if}
            {else}
                {$daystart|tiki_date_format:"{tr}%m/%d{/tr}/%Y"} - {$dayend|tiki_date_format:"{tr}%m/%d{/tr}/%Y"}
            {/if}

            {*next*}
            {if !empty($module_params.viewnavbar) && $module_params.viewnavbar eq 'partial'}
                {self_link _ajax=$ajax _class="next tips text-info" todate=$focus_next viewmode=$viewmode _title=":{tr _0=$viewmode|escape}Next %0{/tr}" _icon_name="next"}{/self_link}
            {/if}
        </div>
    {/if}
{/strip}
