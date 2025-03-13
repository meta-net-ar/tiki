<script type="module">
    {literal}
        import { submitNewButton, generateButtonsFromInput } from '@jquery-tiki/plugin-edit';
    {/literal}
    window.submitNewButton = submitNewButton;

    generateButtonsFromInput('{{$paramName}}');
</script>

<div id="{$paramName}">
    <div class="d-flex value-container">
        <input type="hidden" name="params[{$paramName}]" id="label" value="{$pluginArgs[$paramName]}">
        <input type="hidden" name="params[{$paramName}ClassNames]" id="classNames" value="{$pluginArgs[$paramName|cat:'ClassNames']}">
        <input type="hidden" name="params[{$paramName}Actions]" id="action" value="{$pluginArgs[$paramName|cat:'Action']}">
        <div class="btn-container d-flex gap-4 align-items-center flex-wrap">
            <div>
                <button class="btn btn-light"  data-bs-toggle="collapse" data-bs-target="#fields{$paramName}" aria-expanded="false" aria-controls="fields{$paramName}" type="button">
                    {icon name="plus"}
                </button>
            </div>
            <div class="buttons d-flex gap-2 flex-wrap">
                <span class="d-none delete-icon" role="button">{icon name="delete"}</span>
            </div>
        </div>
    </div>

    <div class="collapse" id="fields{$paramName}">
        <div class="card card-body bg-light border-0 button-fields">
            {foreach from=$param.fields item=field key=key}
                <div class="mb-3">
                    <label for="{$field.name}" class="form-label">{$field.name}</label>
                    {if not empty($field.options)}
                        <select class="form-control" name="{$key}" class="field">
                            {foreach from=$field.options item=option}
                                {if is_array($option)}
                                <option value="{$option.value}" {if $field.default eq $option.value}selected{/if}>{$option.text}</option>
                                {else}
                                    <option value="{$option}" {if $field.default eq $option}selected{/if}>{$option}</option>
                                {/if}
                            {/foreach}
                        </select>
                    {else}
                        <input class="form-control" type="text" name="{$key}" value="{$field.value}" class="field">
                    {/if}
                </div>
            {/foreach}
            <button class="btn btn-primary" type="button" onclick="submitNewButton('{{$paramName}}', this)">{icon name="plus"} Add</button>
        </div>
    </div>
</div>
