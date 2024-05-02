{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}{$title}{/block}

{block name="content"}
    <form class="form simple import-fields" action="{service controller=tracker action=import_fields}" method="post" role="form">
        <div class="mb-3 row mx-0">
            <label class="col-form-label">
                {tr}Raw Fields{/tr}
            </label>
            <textarea class="form-control" name="raw" rows="20"></textarea>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="preserve_ids" id="preserve_ids" value="1">
            <label class="form-check-label" for="preserve_ids">
                {tr}Preserve Field IDs{/tr}
            </label>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="last_position" id="last_position" checked="checked" value="1">
            <label class="form-check-label" for="last_position">
                {tr}Imported fields at the bottom of the list{/tr}
            </label>
        </div>
        <div class="mb-3 submit">
            <input type="hidden" name="trackerId" value="{$trackerId|escape}">
            <input type="submit" class="btn btn-primary" value="{tr}Import{/tr}">
        </div>
    </form>
{/block}
