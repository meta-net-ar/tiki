<div{if !empty($field.options_map.labelasplaceholder)} class="input-group"{/if}>
    <input type="email" name="{$field.ins_id}" id="{$field.ins_id}" value="{$field.value|escape}"
        class="form-control{if !empty($field.options_map.labelasplaceholder)} labelasplaceholder{/if}"
        {if !empty($field.options_map.labelasplaceholder)}placeholder="{$field.name}"{/if}
    >
    {if $field.options_map.labelasplaceholder and $field.isMandatory eq 'y'}
        <span class="input-group-text">
            <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}" style="font-size: 100%">{icon name='asterisk'}</strong>
        </span>
    {/if}
</div>
{if $field.options_map.confirmed eq 'y'}
<div class="{if !empty($field.options_map.labelasplaceholder)} input-group {/if} mt-1">
        <input type="email"  class="form-control emailCheck" placeholder="{tr}Please, write your email address again{/tr}" name="{$field.ins_id}_confirm" required >
        <div id="myEmailCheck">
            <div id="match" style="display:none">
                    {icon name='ok' istyle='color:#0ca908'} {tr}Email matches{/tr}
            </div>
            <div id="nomatch" style="display:none">
                {icon name='error' istyle='color:#ff0000'} {tr}Email does not match{/tr}
            </div>
        </div>
</div>
{/if}
{jq}
    $(".emailCheck").keyup(function(){
        var $email = $(".emailVerify").val();
        var $email_2 = $(".emailCheck").val();
        if ($email == $email_2) {
            $("#myEmailCheck").children("#match").show();
            $("#myEmailCheck").children("#nomatch").hide();
        } else {
            $("#myEmailCheck").children("#nomatch").show();
            $("#myEmailCheck").children("#match").hide();
        }

    })
{/jq}
