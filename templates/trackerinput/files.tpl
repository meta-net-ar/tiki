<div id="display_f{$field.fieldId|escape}" class="files-field display_f{$field.fieldId|escape} uninitialized {if !empty($data.replaceFile)}replace{/if}" data-galleryid="{$field.galleryId|escape}" data-firstfile="{$field.firstfile|escape}" data-filter="{$field.filter|escape}" data-limit="{$field.limit|escape}" data-item-id="{$item.itemId|escape}" data-field-id="{$field.fieldId|escape}" data-namefilter="{$field.namefilter|escape}" data-namefilter-error="{$field.namefilterError|escape}">
    {if !empty($field.canUpload)}
        {if !empty($field.limit)}
            {if $field.limit == 1}
                {remarksbox _type=info title="{tr}Attached files limitation{/tr}"}
                    {tr }The amount of files that can be attached is limited to <strong>1</strong>. The latest file will be preserved.{/tr}
                {/remarksbox}
            {else}
                {remarksbox _type=info title="{tr}Attached files limitation{/tr}"}
                    {tr _0=$field.limit}The amount of files that can be attached is limited to <strong>%0</strong>. The latest files will be preserved.{/tr}
                {/remarksbox}
            {/if}
        {/if}
        <ol class="tracker-item-files current-list">
            {foreach from=$field.files item=info}
                <li data-file-id="{$info.fileId|escape}" class="m-1">
                    {if $prefs.vimeo_upload eq 'y' and $field.options_map.displayMode eq 'vimeo'}
                        {icon name='vimeo'}
                    {elseif $field.options_map.displayMode eq 'img'}
                        <img src="tiki-download_file.php?fileId={$info.fileId|escape}&display&y=24" height="24">
                        {$info.name|escape}
                    {elseif $field.options_map.displayMode eq 'barelink'}
                        <a href="{$info.fileId|sefurl:'file'}">
                            {$info.name|escape}
                        </a>
                    {else}
                        {$info.fileId|sefurl:'file'|iconify:$info.filetype:$info.fileId:2}
                        <a href="{$info.fileId|sefurl:'file'}" data-box="box">
                            {$info.name|escape}
                        </a>
                    {/if}
                    <div class="file-actions d-inline-block">
                        <div class="d-inline-block">
                            <a href="#" class="file-move-to-tracker-icon text-danger" data-action="copy" title="{tr}Copy to another tracker{/tr}">
                                {icon name='copy'}
                            </a>
                        </div>
                        <div class="d-inline-block">
                            <a href="#" class="file-move-to-tracker-icon text-danger" data-action="move" title="{tr}Move to another tracker{/tr}">
                                {icon name='move'}
                            </a>
                        </div>
                        <a href="#" class="file-hard-delete-icon text-danger" title="{tr}Remove from tracker item and delete permanently from file gallery.{/tr}">
                            {icon name='trash'}
                        </a>
                        <a href="#" class="file-delete-icon text-danger" title="{tr}Remove from tracker item but keep in file gallery.{/tr}">
                            {icon name='delete'}
                        </a>
                    </div>
                </li>
            {/foreach}
        </ol>
        <input class="input" type="text" name="{$field.ins_id|escape}" value="{$field.value|escape}" style="display: none">
        <input class="deleted" type="hidden" name="del_{$field.fieldId|escape}">
        {if $field.options_map.displayMode eq 'vimeo'}
            {wikiplugin _name='vimeo' fromFieldId=$field.fieldId|escape fromItemId=$item.itemId|escape galleryId=$field.galleryId|escape}{/wikiplugin}
        {else}
            {if $field.options_map.uploadInModal neq 'n'}
                <a href="{service controller=file action=uploader uploadInModal=1 galleryId=$field.galleryId limit=$field.limit|default:100 type=$field.filter image_max_size_x=$field.image_x image_max_size_y=$field.image_y addDecriptionOnUpload=$data.addDecriptionOnUpload trackerId=$field.trackerId requireTitle=$field.requireTitle directoryPattern=$field.directoryPattern}" class="btn btn-primary upload-files">
                    {if $field.limit !== 1}{tr}Upload Files{/tr}{else}{tr}Upload File{/tr}{/if}
                </a>
            {else}
                <div class="upload-files-inline-form">
                    {service_inline controller=file action=uploader uploadInModal=0 galleryId=$field.galleryId limit=$field.limit|default:100 type=$field.filter image_max_size_x=$field.image_x image_max_size_y=$field.image_y addDecriptionOnUpload=$data.addDecriptionOnUpload directoryPattern=$field.directoryPattern}
                </div>
            {/if}
        {/if}
        {if !empty($context.canBrowse)}
            {if $prefs.fgal_elfinder_feature eq 'y'}
                {button href='tiki-list_file_gallery.php' _text="{tr}Browse files{/tr}" _onclick=$context.onclick title="{tr}Browse files{/tr}"}
            {else}
                <a href="{service controller=file action=browse galleryId=$context.galleryId limit=$field.limit|default:100 type=$field.filter image_x=$field.image_x image_y=$field.image_y}" class="btn btn-primary browse-files">{tr}Browse Files{/tr}</a>
            {/if}
        {/if}
        {if $prefs.fgal_upload_from_source eq 'y' and $field.canUpload}
            <fieldset>
                <legend class="visually-hidden">{tr}Upload File(s){/tr}</legend>
                {if $prefs.vimeo_upload eq 'y' and $field.options_map.displayMode eq 'vimeo'}
                    <label for="vimeourl_{$field.ins_id|escape}" class="small">
                        {tr}Link to existing Vimeo URL{/tr}
                    </label>
                    <input class="url vimeourl form-control" name="vimeourl" id="vimeourl_{$field.ins_id|escape}" placeholder="https://vimeo.com/..." data-mode="vimeo">
                    <input type="hidden" class="reference" name="reference" value="1">
                {else}
                    <label for="url_{$field.ins_id|escape}" class="small">
                        {tr}Upload from URL{/tr}
                    </label>
                    <input class="url form-control" name="url" id="url_{$field.ins_id|escape}" placeholder="https://">
                    <input type="hidden" class="reference" name="reference" value="0">
                {/if}
                <p class="description">
                    {tr}Type or paste the URL and press ENTER{/tr}
                </p>
            </fieldset>
        {/if}
    {else}
            {remarksbox type="error" close="n" title="{tr}You do not have permission to upload files to this gallery.{/tr}" }
            {/remarksbox}
    {/if}
</div>

{if !empty($field.canUpload)}
    {jq}
        $('.files-field.uninitialized').removeClass('uninitialized').each(function () {
            var $self = $(this);
            var $files = $('.current-list', this);
            var $warning = $('.alert', this);
            var $field = $('.input', this);
            var $deleted = $('.deleted', this);
            var $url = $('.url', this);
            var replaceFile = $(this).is('.replace');

            function toggleWarning() {
                var limit = $self.data('limit');
                if (limit) {
                    if ($files.children().length > limit) {
                        $warning.show();
                        $files.children().css('text-decoration', 'line-through');
                        $files.children().slice(-5).css('text-decoration', '');
                    } else {
                        $files.children().css('text-decoration', '');
                        $warning.hide();
                    }
                }
            }

            function addFile(fileId, type, name) {
                var li = $('<li>').appendTo($files); li.text(name).data('file-id', fileId);

                $field.input_csv('add', ',', fileId);

                li.prepend($.fileTypeIcon(fileId, { type: type, name: name }));
                li.append($('<div class="file-actions d-inline-block"><div class="d-inline-block"><a href="#" class="file-move-to-tracker-icon text-danger" data-action="copy" title="{tr}Copy to another tracker{/tr}">{{icon name='copy'}}</a></div><a class="file-hard-delete-icon text-danger">{{icon name='trash'}}</a><a class="file-delete-icon text-danger">{{icon name='delete'}}</a></div>'));

                if (replaceFile && $self.data('firstfile') > 0) {
                    li.prev('li').remove();
                }

                if (! $self.data('firstfile')) {
                    $self.data('firstfile', fileId);
                }

                $field.trigger("change");

                toggleWarning();
            }

            function checkFile(fileName, $form) {
                if (! $self.data('namefilter')) {
                    return true;
                }
                if (! fileName.match(new RegExp($self.data('namefilter')))) {
                    feedback(tr($self.data('namefilter-error') || 'The uploaded file name doesn\'t match desired pattern.'), 'error');
                    $(this).val("");
                    $("input[type=file]", $form).val("");
                    $(".custom-file-label", $form).text(tr("Choose file"));
                    return false;
                } else {
                    return true;
                }
            }

            function attachFileCheckingOnElement(el) {
                $("input[type=file]", el).change(function () {
                    return checkFile($(this).val(), $(this).closest("form"));
                });
                $(el).on('drop', '.file-uploader', function (e) {
                    const files = e.originalEvent.dataTransfer.files;
                    let badFile =  Array.from(files).find(function (file) {
                        return ! checkFile(file.name, $(this));
                    });
                    if (badFile) {
                        e.stopPropagation();
                        return false;
                    }
                });
            }

            $field.hide();
            toggleWarning();

            $self.find('.btn.upload-files').clickModal({
                success: function (data) {
                    var $ff = $(this).parents(".files-field");
                    $field = $(".input", $ff);
                    $files = $(".current-list", $ff);

                    $.each(data.files, function (k, file) {
                        addFile(file.fileId, file.type, file.label);
                    });

                    $.closeModal();
                }
            });

            attachFileCheckingOnElement($self.find('.upload-files-inline-form'));

            $("a.upload-files").click(function () {
                $(document).one('tiki.modal.redraw', '.modal.fade', function () {
                    attachFileCheckingOnElement(this);
                });
            });

            $self.find('.btn.browse-files').on('click', function () {
                if (! $(this).data('initial-href')) {
                    $(this).data('initial-href', $(this).attr('href'));
                }

                // Before the dialog handler triggers, replace the href with one including current files
                $(this).attr('href', $(this).data('initial-href') + '&file=' + $field.val());
            });
            $self.find('.btn.browse-files').clickModal({
                size: 'modal-lg',
                success: function (data) {
                    var $ff = $(this).parents(".files-field");
                    $field = $(".input", $ff);
                    $files = $(".current-list", $ff);

                    $files.empty();
                    $field.val('');

                    $.each(data.files, function (k, file) {
                        addFile(file.fileId, file.type, file.name);
                    });

                    $.closeModal();
                }
            });

            $files.find('input').hide();
            // Delete for previously existing and to be added files
            $files.parent().on('click', '.file-delete-icon', function (e) {
                var fileId = $(e.target).closest('li').data('file-id');
                if (fileId) {
                    $field.input_csv('delete', ',', fileId);
                    $(e.target).closest('li').remove();
                    $field.trigger("change");
                    toggleWarning();
                }
                return false;
            });

            $files.parent().on('click', '.file-hard-delete-icon', function (e) {
                var fileId = $(e.target).closest('li').data('file-id');
                if (fileId && confirm(tr('Are you sure you want to permanently delete this item'))) {
                    $deleted.input_csv('add', ',', fileId);
                    $field.input_csv('delete', ',', fileId);
                    $(e.target).closest('li').remove();
                    $field.trigger("change");
                    toggleWarning();
                }
            });

            $files.parent().on('change', 'input[name=tracker_item_selector]', function (e) {
                var caller = $(this).parent().prev();
                var action = caller.parent().prev().data('action');
                var target_item_id = $(this).val().replace('trackeritem:', '');
                if (target_item_id == $self.data('item-id') && $self.data('field-id') == caller.data('field')) {
                    feedback(tr('Cannot move to same source'), 'error');
                    return;
                }
                $.ajax({
                    type: 'POST',
                    url: $.service('tracker', 'moveItemFile'),
                    dataType: 'json',
                    data: {
                        targetItemId: target_item_id,
                        fileId: caller.closest('li').data('file-id'),
                        sourceItemId: $self.data('item-id'),
                        sourceFieldId: $self.data('field-id'),
                        targetFieldId: caller.data('field'),
                        doAction: action
                    },
                    success: function (data) {
                        if (action == 'move') {
                            caller.closest('li').remove();
                        }
                        feedback(tr('File move complete'), 'success');
                    },
                    error: function (jqxhr) {
                        $(this).showError(jqxhr);
                    },
                    complete: function () {
                        $('#tracker_selector').remove();
                    }
                });
            });

            $files.parent().on('click', '.file-move-to-tracker-icon', function (e) {
                e.preventDefault();
                var caller = $(this);
                if ($('#tracker_selector').length) {
                    $('#tracker_item_selector').remove();
                    $('#tracker_selector').show().insertAfter(caller);
                    return;
                }
                var url = $.service('tracker', 'fileTrackers');
                $.ajax({
                    url: url,
                    success: function (data) {
                        $(data).insertAfter(caller);
                    }
                });
            });

            $url.keypress(function (e) {
                if (e.which === 13) {
                    var $this = $(this);
                    var url = $this.val();
                    $this.attr('disabled', true).clearError();

                    $.ajax({
                        type: 'POST',
                        url: $.service('file', 'remote'),
                        dataType: 'json',
                        data: {
                            galleryId: $self.data('galleryid'),
                            url: url,
                            reference: $this.next('.reference').val()
                        },
                        success: function (data) {
                            var $ff = $this.parents(".files-field");
                            $field = $(".input", $ff);
                            $files = $(".current-list", $ff);

                            addFile(data.fileId, data.type, data.name);
                            $this.val('');
                        },
                        error: function (jqxhr) {
                            $this.showError(jqxhr);
                        },
                        complete: function () {
                            $this.removeAttr('disabled');
                        }
                    });

                    return false;
                }
            });

            window.handleFinderFile = function (file, elfinder) {
                var hash = "";
                if (typeof file === "string") {
                    var m = file.match(/target=([^&]*)/);
                    if (!m || m.length < 2) {
                        return false;    // error?
                    }
                    hash = m[1];
                } else {
                    hash = file.hash;
                }
                $.ajax({
                    type: 'GET',
                    url: $.service('file_finder', 'finder'),
                    dataType: 'json',
                    data: {
                        cmd: "tikiFileFromHash",
                        hash: hash
                    },
                    success: function (data) {
                        var eventOrigin = $("body").data("eventOrigin");
                        if (eventOrigin) {
                            var $ff = $(eventOrigin).parents(".files-field");
                            $field = $(".input", $ff);
                            $files = $(".current-list", $ff);
                        }

        addFile(data.fileId, data.filetype, data.name);
                    },
                    error: function (jqxhr) {
                    },
                    complete: function () {
                        $(window).data("elFinderDialog").dialog("close");
                        $($(window).data("elFinderDialog")).remove();
                        $(window).data("elFinderDialog", null);
                        return false;
                    }
                });
            };
            handleVimeoFile = function (link, data) {
                var eventOrigin = link;
                if (eventOrigin) {
                    var $ff = $(eventOrigin).parents(".files-field");
                    $field = $(".input", $ff);
                    $files = $(".current-list", $ff);
                }

        addFile(data.fileId, data.filetype, data.name);
            };
        });
    {/jq}

    {if $prefs.vimeo_upload eq 'y' and $field.options_map.displayMode eq 'vimeo' and $prefs.feature_jquery_validation eq 'y'}
        {jq}
            $.validator.addMethod("isVimeoUrl", function(value, element) {
                return this.optional(element) || value.match(/http[s]?\:\/\/(?:www\.)?vimeo\.com.*\/\d{4}/);
            }, tr("* URL format is incorrect. It should start with 'https://vimeo.com/' and contain a video id of at least 4 digits '\nnnnnnn'"));
            $.validator.addClassRules({
                vimeourl : { isVimeoUrl : true }
            });
        {/jq}
    {/if}
{/if}
