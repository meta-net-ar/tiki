{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <div class="row file-browser">
        <div class="col-md-12">
            <form class="row g-2 align-items-center no-ajax" method="get" action="{service controller=file action=$list_view plain=1}">
                <div class="col">
                    <input class="form-control " name="search" type="search" id="search-field" placeholder="{tr}Search...{/tr}"/>
                    <input type="hidden" name="galleryId" value="{$galleryId|escape}"/>
                    <input type="hidden" name="limit" value="{$limit|default:20}">
                    <input type="hidden" name="type" value="{$typeFilter|escape}"/>
                </div>
                <div class="col-auto">
                    <button class="btn btn-info" type="submit">{tr}Search{/tr}</button>
                </div>
            </form>

            <div class="d-flex justify-content-between align-items-center my-2">
                <h4 class="mb-0">{tr}Select files{/tr}</h4>
                <div>
                    <input type="checkbox" id="select-all-files" class="form-check-input me-2"/>
                    <label for="select-all-files" class="form-label mb-0">{tr}Select all{/tr}</label>
                </div>
            </div>

            <form method="post" action="{service controller=file action=browse galleryId=$galleryId}">
                <div class="gallery-list flex-grow-1 overflow-auto border" style="max-height: 60vh;">
                    {service_inline controller=file action=$list_view galleryId=$galleryId plain=1 type=$typeFilter limit=$limit}
                </div>

                <div class="submit sticky-bottom bg-light py-3 mt-3">
                    {if $canUpload}
                        <a class="btn btn-secondary upload-files custom-handling me-2" href="{service controller=file action=uploader galleryId=$galleryId limit=$limit type=$typeFilter}">
                            {tr}Upload Files{/tr}
                        </a>
                    {/if}
                    <input type="submit" class="btn btn-primary" value="{tr}Attach selected file(s){/tr}">
                </div>
            </form>
        </div>
    </div>

    {jq}
        $('#select-all-files').on('change', function () {
            $('.gallery-list .file-select').prop('checked', this.checked).trigger('change');
        });
    {/jq}
{/block}
