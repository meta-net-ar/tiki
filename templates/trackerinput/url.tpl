<div{if !empty($field.options_map.labelasplaceholder)} class="input-group"{/if}>
    <input type="text" class="form-control{if !empty($field.options_map.labelasplaceholder)} labelasplaceholder{/if}"
           name="{$field.ins_id}" id="{$field.ins_id}" value="{$field.value|escape}" size="60"
           data-url-wiki-wrapper-validation-input=""
           data-url-wiki-wrapper-feedback-id="{$field.ins_id}_wikiSyntaxLive"
           {if !empty($field.options_map.labelasplaceholder)}placeholder="{$field.name}"{/if}
    >
    {if $field.options_map.labelasplaceholder and $field.isMandatory eq 'y'}
        <span class="input-group-text">
            <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}" style="font-size: 100%">{icon name='asterisk'}</strong>
        </span>
    {/if}
</div>
<div id="{$field.ins_id}_wikiSyntaxLive"
     class="form-text text-muted js-url-wiki-syntax-feedback"
     data-default-info="{$data.wikiSyntaxInfo|escape:'htmlattr'}"
>{$data.wikiSyntaxInfo|escape}</div>
