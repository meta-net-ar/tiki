{if $field.options_map.overridable}
    <div class="math-field-override">
        <span class="form-text text-muted">
            {tr _0=$field.value}Value will be re-calculated on save. Current value: %0{/tr}
            <a href="#" class="math-override-toggle ms-1" title="{tr}Override value{/tr}"><span class="fas fa-pen fa-sm"></span></a>
        </span>
        <div class="input-group mt-1" style="display:none;">
            <input type="text" class="form-control" id="{$field.ins_id|replace:'[':'_'|replace:']':''}" name="{$field.ins_id}" value="{$field.value|escape}" data-original-value="{$field.value|escape}" disabled>
            <button type="button" class="btn btn-outline-secondary btn-sm math-override-cancel" title="{tr}Cancel override{/tr}">
                <span class="fas fa-times"></span>
            </button>
        </div>
    </div>
{else}
    {tr _0=$field.value}Value will be re-calculated on save. Current value: %0{/tr}
{/if}
