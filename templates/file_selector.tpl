<div class="file-selector">
    <input type="hidden" name="{$file_selector.name|escape}" value="{$file_selector.value|join:','}">
    <a class="btn btn-primary" href="{service controller=file action=browse galleryId=$file_selector.galleryId type=$file_selector.type limit=$file_selector.limit}">{tr _0=$file_selector.value|count}Select Files (%0){/tr}</a>
</div>
