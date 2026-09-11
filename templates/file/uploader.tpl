{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {if $uploadInModal}{title}{$title}{/title}{/if}
{/block}

{block name="content"}
    {if $uploadInModal}

        <form class="file-uploader" enctype="multipart/form-data" method="post" action="{service controller=file action=upload galleryId=$galleryId image_max_size_x=$image_max_size_x image_max_size_y=$image_max_size_y}" data-gallery-id="{$galleryId|escape}" data-image_max_size_x="{$image_max_size_x|escape}" data-image_max_size_y="{$image_max_size_y|escape}" data-ticket="{ticket mode=get}" data-directory-pattern="{$directoryPattern|escape}" data-subdirectory-behavior="{$subdirectoryBehavior|default:'subgalleries'|escape}">
            <input type="hidden" name="directoryPattern" value="{$directoryPattern|escape}">
            <input type="hidden" name="subdirectoryBehavior" value="{$subdirectoryBehavior|default:'subgalleries'|escape}">
            {if $image_max_size_x || $image_max_size_y}
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    {if $image_max_size_x and $image_max_size_y}{tr _0=$image_max_size_x _1=$image_max_size_y}Images will be resized to %0px in width and %1px in height{/tr}
                    {elseif $image_max_size_x}{tr _0=$image_max_size_x}Images will be resized to %0px in width{/tr}
                    {elseif $image_max_size_y}{tr _0=$image_max_size_y}Images will be resized to %0px in height{/tr}
                    {/if}
                {/remarksbox}
            {elseif not empty($admin_trackers)}
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    {tr}Images will not be resized, for resizing edit this tracker field and set image max width and height in "Options for files" section.{/tr}
                {/remarksbox}
            {/if}
            {ticket}
            <div class="progress invisible mb-2">
                <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
                    <span class="sr-only"><span class="count">0</span>{tr}% Complete{/tr}</span>
                </div>
            </div>
            <div class="custom-file-title mb-3" style="display: none;">
                <label class="form-label custom-file-title-label" for="inputFileTitle">{tr}Title{/tr}</label> <span class="text-danger">*</span>
                <input id="inputFileTitle" class="custom-file-title-input form-control" type="text" name="title" />
                <label class="form-label invalid-feedback feedback-required-title">{tr}This field is required before file can be uploaded.{/tr}</label>
                <label class="form-label invalid-feedback feedback-one-at-time">{tr}Only one file can be uploaded at a time{/tr}</label>
            </div>
            <div class="input-group">
                <span class="input-group-text" id="inputGroupText">{if $limit !== 1}{tr}Upload Files{/tr}{else}{tr}Upload File{/tr}{/if}</span>
                <input type="text" class="form-control" id="fileName" aria-describedby="inputGroupText" placeholder="{tr}Choose file{/tr}" readonly onclick="document.getElementById('inputFile').click();" style="cursor: pointer;">
                <input type="file" name="file[]" {if $limit !== 1}multiple{/if} {if $typeFilter}accept="{$typeFilter|escape}"{/if}
                        class="d-none" id="inputFile"
                        onchange="updateFileName(this);">
                <label class="input-group-text" for="inputFile" style="cursor: pointer;">{tr}Browse{/tr}</label>
            </div>
            <p class="drop-message text-center">
                {if $limit !== 1}{tr}Or drop files here from your file manager.{/tr}{else}{tr}Or drop file here from your file manager.{/tr}{/if}
            </p>
        </form>
        <form class="file-uploader-result" method="post" action="{service controller=file action=uploader galleryId=$galleryId}">
            <ul class="list-unstyled" data-adddescription="{$addDecriptionOnUpload}" data-changefilename="{$changeFileNameOnUpload}"></ul>

            <div class="submit">
                {ticket}
                <input type="submit" class="btn btn-primary" value="{tr}Select{/tr}">
            </div>
        </form>

    {else}{* not $uploadInModal *}

        <div class="file-uploader inline" data-action="{service controller=file action=upload galleryId=$galleryId image_max_size_x=$image_max_size_x image_max_size_y=$image_max_size_y}" data-gallery-id="{$galleryId|escape}" data-image_max_size_x="{$image_max_size_x|escape}" data-image_max_size_y="{$image_max_size_y|escape}" data-ticket="{ticket mode=get}" data-directory-pattern="{$directoryPattern|escape}" data-subdirectory-behavior="{$subdirectoryBehavior|default:'subgalleries'|escape}">
            {if $image_max_size_x || $image_max_size_y}
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    {if $image_max_size_x and $image_max_size_y}{tr _0=$image_max_size_x _1=$image_max_size_y}Images will be resized to %0px in width and %1px in height{/tr}
                    {elseif $image_max_size_x}{tr _0=$image_max_size_x}Images will be resized to %0px in width{/tr}
                    {elseif $image_max_size_y}{tr _0=$image_max_size_y}Images will be resized to %0px in height{/tr}
                    {/if}
                {/remarksbox}
            {elseif not empty($admin_trackers)}
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    {tr}Images will not be resized, for resizing edit this tracker field and set image max width and height in "Options for files" section.{/tr}
                {/remarksbox}
            {/if}
            <div class="progress invisible mb-2">
                <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
                    <span class="sr-only"><span class="count">0</span>{tr}% Complete{/tr}</span>
                </div>
            </div>
            <div class="input-group">
                <span class="input-group-text" id="inputGroupText">{if $limit !== 1}{tr}Upload Files{/tr}{else}{tr}Upload File{/tr}{/if}</span>
                <input type="text" class="form-control" id="fileName-n" aria-describedby="inputGroupText" placeholder="{tr}Choose file{/tr}" readonly onclick="document.getElementById('inputFile-n').click();" style="cursor: pointer;">
                <input type="file" name="file[]" {if $limit !== 1}multiple{/if} {if $typeFilter}accept="{$typeFilter|escape}"{/if}
                        class="d-none" id="inputFile-n"
                        onchange="updateFileName(this);">
                <label class="input-group-text" for="inputFile-n" style="cursor: pointer;">{tr}Browse{/tr}</label>
            </div>

            <p class="drop-message text-center">
                {if $limit !== 1}{tr}Or drop files here from your file manager.{/tr}{else}{tr}Or drop file here from your file manager.{/tr}{/if}
            </p>

            <div class="file-uploader-result" method="post" action="{service controller=file action=uploader galleryId=$galleryId}">
                <ul class="list-unstyled" data-adddescription="{$addDecriptionOnUpload}"></ul>
            </div>
        </div>

    {/if}
{/block}
{if $requireTitle == 'y'}
    {jq}
        $('.custom-file-title').show();
    {/jq}
{/if}
