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
    {if (isset($field.options_array[3]) and ($field.options_array[3] eq 'blank' or $field.options_array[3] eq 'empty')) or (isset($context.inForm) and $context.inForm eq 'y')}
        {html_select_time prefix=$field.ins_id time=$time display_seconds=false all_empty=" " use_24_hours=$use_24hr_clock}
    {else}
        {html_select_time prefix=$field.ins_id time=$time display_seconds=false use_24_hours=$use_24hr_clock}
    {/if}
{/if}
