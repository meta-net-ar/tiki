{strip}
    {capture assign='charCount'}
        {if $field.options_map.max}
            <div class="charCount form-text">
                {if $prefs.javascript_enabled eq 'y'}
                    {tr}Character Count:{/tr}
                    <input class="d-inline-block form-control-plaintext mx-1 w-auto" type="text" id="ccpt_{$field.fieldId}" size="4" readonly{if !empty($field.value)} value="{$field.value|count_characters:true}"{/if}>
                {/if}
                {tr}Max:{/tr} {$field.options_map.max}
            </div>
        {/if}
    {/capture}
    {capture assign='wordCount'}
        {if $field.options_map.wordmax}
            <div class="wordCount form-text">
                {if $prefs.javascript_enabled eq 'y'}
                    {tr}Word Count:{/tr}
                    <input class="d-inline-block form-control-plaintext mx-1 w-auto" type="text" id="wcpt_{$field.fieldId}" size="4" readonly{if !empty($field.value)} value="{$field.value|count_words}"{/if}>
                {/if}
                {tr}Max:{/tr} {$field.options_map.wordmax}
            </div>
        {/if}
    {/capture}

    {if $field.isMultilingual ne 'y'}
        {if $field.options_map.height == 1}
            {if $field.options_map.toolbars}
                <div class='textarea-toolbar nav-justified' id='trackerinout_{$field.ins_id}_toolbar'>
                    {toolbars qtnum=$field.fieldId area_id=$data.element_id section="trackers"}
                </div>
            {/if}
            <input type="text" id="{$data.element_id|escape}" name="{$field.ins_id}"{if $field.options_map.width > 0} size="{$field.options_map.width}"{/if}{if $field.options_map.max gt 0} maxlength="{$field.options_map.max}"{/if} value="{$field.value|escape}" onkeyup={$data.keyup} />
        {else}
            {if $field.options_map.wysiwyg == 'y'}
                {textarea _class='form-control' id=$data.element_id name=$field.ins_id rows=$data.rows _toolbars=$data.toolbar onkeyup=$data.keyup _wysiwyg='y' section='trackers' switcheditor='n' _preview=$prefs.ajax_edit_previews}
                    {$field.value}
                {/textarea}
            {else}
                {textarea _class='form-control' id=$data.element_id name=$field.ins_id _toolbars=$data.toolbar rows=$data.rows onkeyup=$data.keyup _wysiwyg='n' section="trackers" switcheditor='n' _preview=$prefs.ajax_edit_previews}
                    {$field.value}
                {/textarea}
            {/if}
        {/if}
        {$charCount}
        {$wordCount}
    {else}
        {foreach name=lg from=$field.lingualvalue item=ling}
            <label for="{$data.element_id|escape}_{$ling.lang}">{$ling.lang|langname}</label>
            {if $field.options_map.wysiwyg == 'y'}
                {textarea _class='form-control' id="{$data.element_id}_{$ling.lang}" name="{$field.ins_id}[{$ling.lang}]" rows=$data.rows onkeyup=$data.keyup _wysiwyg='y' cols="{if $field.options_map.width gt 1}{$field.options_map.width}{else}50{/if}" section="trackers"  switcheditor='n' _preview=$prefs.ajax_edit_previews}
                    {$ling.value}
                {/textarea}
            {else}
                {if $field.options_map.toolbars}
                    <div class='textarea-toolbar nav-justified' id='trackerinout_{$field.ins_id}_toolbar'>
                        {toolbars qtnum=$field.id area_id=$data.element_id|cat:'_'|cat:$ling.lang}
                    </div>
                {/if}
                <textarea class='form-control' id="{$data.element_id|escape}_{$ling.lang}" name="{$field.ins_id}[{$ling.lang}]" cols="{if $field.options_map.width gt 1}{$field.options_map.width}{else}50{/if}" rows="{if $field.options_map.height gt 1}{$field.options_map.height}{else}6{/if}"{if $field.options_map.wordmax > 0} onkeyup="wordCount({$field.options_map.wordmax}, this, 'cpt_{$field.fieldId}_{$ling.lang}', '{tr}Word Limit Exceeded{/tr}')"{/if}>
                    {$ling.value|escape}
                </textarea>
            {/if}
            {$charCount}
            {if $field.options_map.wordmax}
                {* not working for wysiwyg *}
                {$wordCount}
            {elseif not $smarty.foreach.lg.last}
                <br>
            {/if}
        {/foreach}
    {/if}
{/strip}
