    <div class="submit offset-md-3 col-md-9">
            <input
                type="submit"
                class="btn {if !empty($confirmButtonClass)}{$confirmButtonClass}{else}btn-primary{/if}"
                value="{if !empty($confirmButton)}{$confirmButton|escape}{else}{tr}OK{/tr}{/if}"
                onclick="confirmAction(event)"
            >
    </div>
