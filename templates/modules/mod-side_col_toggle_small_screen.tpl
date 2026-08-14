{if isset($zone) && $zone == 'left'}
    <div class="d-block d-lg-none">
        {if $prefs.feature_left_column eq 'user'}
            <div class="side-col-toggle-small-screen">
                <span class='toggle_zone left btn btn-sm btn-secondary'>
                    {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'arrow-up' : 'arrow-down'}
                    {icon class="text-sm text-white" name=$icon_name href='#' title='{tr}Toggle left modules{/tr}'}
                </span>
            </div>
        {/if}
    </div>
{elseif isset($zone) && $zone == 'right'}
    <div class="d-block d-lg-none">
        {if $prefs.feature_right_column eq 'user'}
            <div class="side-col-toggle-small-screen">
                <span class='toggle_zone right btn btn-sm btn-secondary'>
                    {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'arrow-up' : 'arrow-down'}
                    {icon class="text-sm text-white" name=$icon_name href='#' title='{tr}Toggle right modules{/tr}'}
                </span>
            </div>
        {/if}
    </div>
{/if}
