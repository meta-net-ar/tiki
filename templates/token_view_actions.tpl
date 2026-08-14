{$safe_is_token_access=$is_token_access|default:false}
{$safe_view_as_visitor=$view_as_visitor|default:false}
{if $safe_is_token_access}
    {remarksbox type="tip" title="{tr}Preview{/tr}"}
        <div class="actions d-flex flex-column align-items-start">
            {if !$safe_view_as_visitor}
                <p class="">{tr}You are previewing this content as yourself{/tr}</p>
                <button id="preview_visitor" class="btn btn-primary  align-self-start" title="{tr}View Page as Guest{/tr}">{tr}View Page as Guest{/tr}</button>
            {else}
                <p>{tr}You are previewing this content as a Guest with the token access{/tr}</p>
                <button id="preview_owner" class="btn btn-success  align-self-start"  title="{tr}View as Myself{/tr}">{tr}View as Myself{/tr}</button>
            {/if}

            {jq}
                const params = new URLSearchParams(window.location.search);
                const updateSearchParam = () => {
                    window.location.search = params.toString();
                };
                $("#preview_visitor").on("click", function () {
                    if (! params.has('view_as_visitor')) {
                        params.set('view_as_visitor', 'true');
                        updateSearchParam();
                    }
                });

                $("#preview_owner").on("click", function () {
                    if (params.has('view_as_visitor')) {
                        params.delete('view_as_visitor');
                        updateSearchParam();
                    }
                });
            {/jq}
        </div>
    {/remarksbox}
{/if}
