{* ----- Start year --- *}
{if isset($field.options_array[1]) and $field.options_array[1] ne ''}
    {$start=$field.options_array[1]}
{elseif isset($prefs.display_start_year)}
    {$start=$prefs.display_start_year}
{else}
    {$start='-4'}
{/if}
{if $field.year > 0 and $field.year < $start}
    {$start=$field.year}
{/if}

{* ----- End year --- *}
{if isset($field.options_array[2]) and $field.options_array[2] ne ''}
    {$end=$field.options_array[2]}
{elseif isset($prefs.display_end_year)}
    {$end=$prefs.display_end_year}
{else}
    {$end='+4'}
{/if}
{if $field.year > $end}
    {$end=$field.year}
{/if}

{if $field.value eq ''}
    {$time="--"}
{elseif isset($context.timestamp)}
    {$time=$context.timestamp}
{else}
    {$time=$field.value}
{/if}
{$inForm}

{* Always use minute_step for UI display, enforcement is handled separately in validation *}
{assign var=minute_interval value=$minute_step|default:1}

{if $field.options_array[0] ne 't'}
    {if ((isset($field.options_array[3]) and ($field.options_array[3] eq 'blank' or $field.options_array[3] eq 'empty'))) or (isset($context.inForm) and $context.inForm eq 'y')}
        {html_select_date prefix=$field.ins_id time=$time start_year=$start end_year=$end field_order=$prefs.display_field_order all_empty=" "}
    {else}
        {html_select_date prefix=$field.ins_id time=$time start_year=$start end_year=$end field_order=$prefs.display_field_order}
    {/if}
{/if}
{if $field.options_array[0] eq 'dt'}
    {tr}at{/tr}
{/if}
{if $field.options_array[0] ne 'd'}
    {if $enforce_step|default:0}
        {* Standard time picker for strict enforcement mode *}
        {if (isset($field.options_array[3]) and ($field.options_array[3] eq 'blank' or $field.options_array[3] eq 'empty')) or (isset($context.inForm) and $context.inForm eq 'y')}
            {html_select_time prefix=$field.ins_id time=$time display_seconds=false all_empty=" " use_24_hours=$use_24hr_clock minute_interval=$minute_interval add_end_minute=false}
        {else}
            {html_select_time prefix=$field.ins_id time=$time display_seconds=false use_24_hours=$use_24hr_clock minute_interval=$minute_interval add_end_minute=false}
        {/if}
    {else}
        {* Flexible mode: hours + custom minutes with "Other" option *}
        
        {* Initialize and validate step_size to prevent division by zero *}
        {assign var=step_size value=$minute_step|default:1}
        {if $step_size <= 0}{assign var=step_size value=1}{/if}
        
        {* Parse current minute and check if it matches a step *}
        {if $time !== '--'}
            {assign var=current_minute value=$time|date_format:'%M'}
        {else}
            {assign var=current_minute value=''}
        {/if}
        
        {assign var=minute_in_steps value=false}
        {if $current_minute !== ''}
            {assign var=current_minute_num value=$current_minute+0}
            {math equation="x % y" x=$current_minute_num y=$step_size assign=remainder}
            {if $remainder == 0}
                {assign var=minute_in_steps value=true}
            {/if}
        {/if}

        <div class="d-flex align-items-center gap-2">
            {* Hours only (standard selector) *}
            {if (isset($field.options_array[3]) and ($field.options_array[3] eq 'blank' or $field.options_array[3] eq 'empty')) or (isset($context.inForm) and $context.inForm eq 'y')}
                {html_select_time prefix=$field.ins_id time=$time display_minutes=false display_seconds=false all_empty=" " use_24_hours=$use_24hr_clock}
            {else}
                {html_select_time prefix=$field.ins_id time=$time display_minutes=false display_seconds=false use_24_hours=$use_24hr_clock}
            {/if}

            {* Custom minutes with "Other" option *}
            <div class="flex-fill d-flex align-items-center gap-1">
                <select class="form-control date" name="{$field.ins_id}Minute" id="{$field.ins_id}MinuteSelect" onchange="toggleCustomMinute_{$field.ins_id}()" style="flex: 1;">
                    {if (isset($field.options_array[3]) and ($field.options_array[3] eq 'blank' or $field.options_array[3] eq 'empty')) or (isset($context.inForm) and $context.inForm eq 'y')}
                        <option value=""> </option>
                    {/if}
                    
                    {* Generate step options *}
                    {for $i=0 to 59 step=$step_size}
                        {if $i < 10}
                            {assign var=formatted_minute value="0`$i`"}
                        {else}
                            {assign var=formatted_minute value=$i}
                        {/if}
                        <option value="{$formatted_minute}"{if $minute_in_steps && $current_minute == $formatted_minute} selected{/if}>
                            {$formatted_minute}
                        </option>
                    {/for}
                    
                    <option value="custom"{if !$minute_in_steps && $current_minute !== ''} selected{/if}>
                        {tr}Other...{/tr}
                    </option>
                </select>
                
                {* Custom input field *}
                <input type="text" 
                       class="form-control date" 
                       name="{$field.ins_id}MinuteCustom" 
                       id="{$field.ins_id}MinuteCustom"
                       maxlength="2"
                       pattern="[0-5]?[0-9]"
                       value="{if !$minute_in_steps}{$current_minute}{/if}"
                       placeholder="MM"
                       title="{tr}Enter minutes (0-59){/tr}"
                       style="width: 60px; display: {if !$minute_in_steps && $current_minute !== ''}inline-block{else}none{/if};">
            </div>
        </div>

        <script>
        function toggleCustomMinute_{$field.ins_id}() {
            var select = document.getElementById('{$field.ins_id}MinuteSelect');
            var customInput = document.getElementById('{$field.ins_id}MinuteCustom');
            
            if (select.value === 'custom') {
                customInput.style.display = 'inline-block';
                customInput.focus();
                select.name = '{$field.ins_id}MinuteSelect_disabled';
                customInput.name = '{$field.ins_id}Minute';
            } else {
                customInput.style.display = 'none';
                customInput.name = '{$field.ins_id}MinuteCustom';
                select.name = '{$field.ins_id}Minute';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomMinute_{$field.ins_id}();
            
            var customInput = document.getElementById('{$field.ins_id}MinuteCustom');
            if (customInput) {
                customInput.addEventListener('input', function() {
                    var value = parseInt(this.value);
                    var isValid = this.value === '' || (value >= 0 && value <= 59);
                    this.classList.toggle('is-invalid', !isValid);
                });
            }
        });
        </script>
    {/if}
{/if}
