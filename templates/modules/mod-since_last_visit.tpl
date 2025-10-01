{if $user}
{tikimodule error=$module_params.error title=$tpl_module_title name="since_last_visit" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
<div class="since-last-visit-wrapper">
    <div class="welcome-message">
        {tr}Welcome back!{/tr}
        <div class="last-visit-date">
            {tr}Your last visit was{/tr}
            <span class="date">{$nvi_info.lastVisit|tiki_short_datetime|replace:"[":""|replace:"]":""}</span>
        </div>
    </div>
    
    <div class="updates-grid">
        <div class="update-item">
            <div class="icon-wrapper">
                {icon name="image"}
            </div>
            <span class="count">{$nvi_info.images}</span>
            <span class="label">{tr}New Images{/tr}</span>
        </div>
        
        <div class="update-item">
            <div class="icon-wrapper">
                {icon name="file-text"}
            </div>
            <span class="count">{$nvi_info.pages}</span>
            <span class="label">{tr}Wiki Updates{/tr}</span>
        </div>
        
        <div class="update-item">
            <div class="icon-wrapper">
                {icon name="file"}
            </div>
            <span class="count">{$nvi_info.files}</span>
            <span class="label">{tr}New Files{/tr}</span>
        </div>
        
        <div class="update-item">
            <div class="icon-wrapper">
                {icon name="comments"}
            </div>
            <span class="count">{$nvi_info.comments}</span>
            <span class="label">{tr}New Comments{/tr}</span>
        </div>
        
        <div class="update-item">
            <div class="icon-wrapper">
                {icon name="tasks"}
            </div>
            <span class="count">{$nvi_info.trackers}</span>
            <span class="label">{tr}Tracker Updates{/tr}</span>
        </div>
        
        <div class="update-item">
            <div class="icon-wrapper">
                {icon name="calendar"}
            </div>
            <span class="count">{$nvi_info.calendar}</span>
            <span class="label">{tr}New Events{/tr}</span>
        </div>
        
        <div class="update-item">
            <div class="icon-wrapper">
                {icon name="group"}
            </div>
            <span class="count">{$nvi_info.users}</span>
            <span class="label">{tr}New Users{/tr}</span>
        </div>
    </div>
</div>
{/tikimodule}
{/if}
