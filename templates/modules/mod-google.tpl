{tikimodule error=$module_params.error title=$tpl_module_title name="google" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

    <form method="get" action="http://www.google.com/search" target="Google" role="form">
        <div class="mb-3 pe-5 row gy-2 gx-3 align-items-center flex-nowrap">
            <div class="col-auto">
                <input type="text" name="q" class="form-control" aria-labelledby="google" maxlength="100" />
            </div>
            <div class="col-auto">
                <input type="hidden" name="hl" value="en"/>
                <input type="hidden" name="oe" value="UTF-8"/>
                <input type="hidden" name="ie" value="UTF-8"/>
                <input type="hidden" name="btnG" value="Google Search"/>
                <input name="googles" class="form-control-plaintext" type="image" src="img/googleg.gif" alt="Google" id="google" style="width: 30px; height: auto" />
            </div>
        </div>
        {if $url_host ne ''}
            <div class="form-check">
                <input type="hidden" name="domains" value="{$url_host}" />
                <input class="form-check-input" type="radio" name="sitesearch" id="sitesearch" value="{$url_host}" checked="checked" />
                <label class="form-check-label" for="sitesearch">{$url_host}</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" id="sitesearch_www" type="radio" name="sitesearch" value="" />
                <label class="form-check-label" for="sitesearch_www">WWW</label>
            </div>
        {/if}
    </form>

{/tikimodule}
