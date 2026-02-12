
(function ($) {
    var handleFiles, ProgressBar, FileList, FileListInline;

    $.fileTypeIcon = function (fileId, file) {
        var themeIconset = jqueryTiki.themeIconset;

        if (typeof file.type == 'undefined') {
            return getIcon(themeIconset, "undefined");
        } else if(file.type.substring(0,6) == 'image/') {
            return $('<img src="tiki-download_file.php?fileId=' + fileId + '&display&y=24" height="24">');
        } else if(file.type == 'application/pdf') {
            return getIcon(themeIconset, file.type, "pdf", "pdf");
        } else if(file.type.indexOf("sheet") != -1 || file.type.indexOf("excel") != -1) {
            return getIcon(themeIconset, file.type, "excel", "xls");
        } else if(file.type.indexOf("zip") != -1) {
            return getIcon(themeIconset, file.type, "zip", "zip");
        } else if (file.type.substring(0,6) == 'video/') {
            return getIcon(themeIconset, file.type, "video", "flv");
        } else if (file.type.indexOf("word") != -1) {
            return getIcon(themeIconset, file.type, "word", "doc");
        } else {
            return getIcon(themeIconset, file.type);
        }
    };

    ProgressBar = function (options) {
        var bar = this;
        this.segments = [];
        this.updateSegment = function (number, current, total) {
            bar.segments[number] = [current, total];
            bar.update();
        };
        this.update = function () {
            var total = 0, current = 0;
            $.each(bar.segments, function (k, item) {
                current += item[0];
                total += item[1];
            });

            options.progress(current, total);

            if (current === total) {
                options.done();
            }
        };
    };

    FileList = function (options) {
        var $list = $(options.list);

        this.list = $list;
        this.clearErrors = function () {
            $list.find('.text-danger').remove();
        };
        this.addError = function (file, message) {
            var $li = $('<li>').appendTo($list);

            $li.text(file.name);
            $li.addClass('text-danger');
            $li.append(' (' + (message ? message : tr('uploading failed')) + ')');
        };
        this.addFile = function (fileId, file, title) {
            // called for "upload in modal" tracker Files fields when file dropped or picked from the file dialog
            var $li = $('<li>').addClass("row mb-4").appendTo($list);

            var label = file.name;
            if (title) {
                label = title;
            }
            if ($list.data("changefilename")) {
                $("<div>").addClass("col-2 change-file")
                    .append($.fileTypeIcon(fileId, file).css("margin-right", "1em"))
                    .appendTo($li);
                var labelNewFileName = title ? title : label.replace(/\.[^.]*$/,''); //Remove extension from file name
                $("<div>").addClass("col-10 input-text" + fileId + "").append($('<input type="text" class="form-control newfilename ' + fileId + '" name="newfilename[]">')
                .attr('value', labelNewFileName))
                    .appendTo($li);
            } else {
                $("<div>").addClass("col-sm-12").text(label)
                .prepend($.fileTypeIcon(fileId, file).css("margin-right", "1em"))
                .appendTo($li);
            }

            if ($list.parents(".inline").length) {
                const $fileInput = $list.parents(".files-field").find("> input.input");
                let val = $fileInput.val();
                val = val ? val + "," + fileId : fileId;
                $fileInput.val(val);
            } else {
                $('<input type="hidden" name="file[]">')
                    .attr('value', fileId)
                    .appendTo($li);

                if ($list.data("adddescription")) {
                    $("<label>")
                        .addClass("col-form-label col-sm-4")
                        .text(tr("Description"))
                        .appendTo($li);
                    $("<div>").addClass("col-sm-12")
                        .append($('<textarea name="description[]">')
                            .addClass("form-control col-sm-8")
                        )
                        .appendTo($li);
                }
            }

        };
    };

    handleFiles = function (input) {

        var files = input.files,
            accept = input.accept,
            title = input.title,
            galleryId = input.galleryId,
            image_max_dimension = input.image_max_dimension,
            directoryPattern = input.directoryPattern,
            progressBar = input.progress,
            ticket = input.ticket,
            fileList = input.list,
            $container = input.form,    // sometimes a div when inline
            $modal = $container.parents(".modal-dialog").first();

        if (! $modal.length) {
            $modal = $container;
        }

        var uploadUrl = $.service('file', 'upload');

        $.each(files, function (k, file) {
            var reader = new FileReader();

            progressBar.updateSegment(k, 0, file.size);
            window.lastFile = file;

            $(window).queue('process-upload', function () {
                reader.onloadend = function (e) {
                    var xhr, provider, sendData, data, valid = true;

                    $modal.tikiModal(tr("Uploading file..."));

                    xhr = jQuery.ajaxSettings.xhr();
                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                progressBar.updateSegment(k, e.loaded, e.total);
                            }
                        }, false);
                    }
                    provider = function () {
                        return xhr;
                    };

                    sendData = {

                        type: 'POST',
                        url: uploadUrl,
                        xhr: provider,
                        dataType: 'json',
                        global: false,     // prevent ajaxError in tiki-jquery.js from closing the dialogs, leading to lost edits
                        success: function (data) {
                            fileList.addFile(data.fileId, file, title);
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            progressBar.updateSegment(k, 0, 0);
                            fileList.addError(file, errorThrown);
                            const tikiFeedback = jqXHR.getResponseHeader('X-Tiki-Feedback');
                            if (tikiFeedback) {
                                fileList.list.append(decodeURIComponent(tikiFeedback));
                            }
                        },
                        complete: function (data, textStatus) {
                            $(window).dequeue('process-upload');

                            if (data.responseJSON) {
                                ticket = data.responseJSON.ticket;
                                // for modal
                                if ($modal !== $container) {
                                    $('form.file-uploader-result input[name=ticket]').val(ticket);
                                }
                                // for both (form when in modal, div when inline)
                                $container.data('ticket', ticket);
                            } else {
                                alert(tr("There was an error: " + textStatus));
                            }
                        }
                    };

                    $modal.tikiModal();

                    if (window.FormData) {
                        sendData.processData = false;
                        sendData.contentType = false;
                        sendData.cache = false;

                        sendData.data = new FormData;
                        sendData.data.append('galleryId', galleryId);
                        sendData.data.append('image_max_dimension', image_max_dimension);
                        sendData.data.append('ticket', ticket);
                        sendData.data.append('name', file.name);
                        sendData.data.append('title', title);
                        sendData.data.append('directoryPattern', directoryPattern);
                        if (file.directory) {
                            sendData.data.append('directory', file.directory);
                        }

                        // Server-side ImageTransformer.php handles all compression and resizing
                        sendData.data.append('data', file);
                    } else {
                        data = e.target.result;
                        sendData.data = {
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            title: title,
                            data: data.substr(data.indexOf('base64') + 7),
                            galleryId: galleryId,
                            image_max_dimension: image_max_dimension,
                            directoryPattern: directoryPattern
                        };
                        if (file.directory) {
                            sendData.data.directory = file.directory;
                        }
                    }

                    if (accept) {
                        valid = file.type.match(new RegExp(".?(" + accept.replace('*', '.*') + ")$", "i"));
                    }

                    if (valid) {
                        $.ajax(sendData);
                    } else {
                        if (!valid) {
                            sendData.error(null);
                        }
                    }
                };
                reader.readAsDataURL(file);
            });
        });
        $(window).dequeue('process-upload');
    };

    $(document).on('submit', 'form.file-uploader', function (e) {
        e.preventDefault();
    });

    function getIcon(themeIconset, mimeType, type = "", extension = "") {
        if (mimeType == undefined) {
            switch (themeIconset) {
                case "legacy":
                    return $('<img height="16" width="16" src="img/icons/mime/default.png">');
                case "bootstrap_icon_font":
                    return $('<span class="bi bi-file" style="font-size:200%;"></span>');
                case "theme_specific_iconset":
                    return $('<span class="far fa-file" style="font-size:200%;"></span>');
                default:
                    return $('<span class="far fa-file" style="font-size:200%;"></span>');
            }
        } else {
            type = type != "" ? `-${type}` : '';
            switch (themeIconset) {
                case "legacy":
                    return $('<img height="16" width="16" title="'+ mimeType +'" alt="'+ mimeType +'" src="img/icons/mime/' + extension + '.png">');
                case "bootstrap_icon_font":
                    return $('<span class="bi bi-file' + type + '" style="font-size:200%;"></span>');
                case "theme_specific_iconset":
                    return $('<span class="far fa-file' + type + '" style="font-size:200%;"></span>');
                default:
                    return $('<span class="far fa-file' + type + '" style="font-size:200%;"></span>');
            }
        }
    }

    function doUpload($form, files) {
        var progress, list, title = "";

        progress = new ProgressBar({
            progress: function (current, total) {
                var percentage = Math.round(current / total * 100);

                $form.find('.progress').removeClass("invisible");
                $form.find('.progress-bar')
                    .attr('aria-valuenow', percentage)
                    .width(percentage + '%');
                $form.find('.progress-bar .sr-only .count')
                    .text(percentage);
            },
            done: function () {
                $form.find('.progress').addClass("invisible");
                $form.find('.progress-bar')
                    .attr('aria-valuenow', 0)
                    .width('0%');
                $form.find('.progress-bar .sr-only .count')
                    .text('0%');
                // Clear required title in preparation for next file
                $('.custom-file-title-input', $form).val('');
            }
        });

        list = new FileList({
            list: $form.parent().find('.file-uploader-result ul')[0]
        });

        list.clearErrors();

        if ($form.find('.custom-file-title-input').length) {
            title = $form.find('.custom-file-title-input').val();
        }

        handleFiles({
            accept: $form.find(':file').attr('accept'),
            galleryId: $form.data('gallery-id'),
            title: title,
            ticket: $form.data('ticket'),
            image_max_dimension:$form.data('image_max_dimension'),
            directoryPattern: $form.data('directory-pattern'),
            files: files,
            progress: progress,
            list: list,
            form: $form
        });
    }

    function getDirectoryFiles(directory) {
        let directoryReader = directory.createReader();
        return new Promise((resolve) => {
            directoryReader.readEntries(async function (entries) {
                let files = [];
                for (let i = 0, l = entries.length; i < l; i++) {
                    let directoryFiles = await readDirectoryEntry(entries[i], directory);
                    files = files.concat(directoryFiles);
                };
                resolve(files);
            });
        });
    }

    function readDirectoryEntry(entry, directory) {
        if (entry.isDirectory) {
            return getDirectoryFiles(entry);
        } else {
            return new Promise((resolve) => {
                entry.file((file) => {
                    file.directory = directory ? directory.fullPath : null;
                    resolve([file]);
                });
            });
        }
    }

    function validateTitleProvided(form) {
        if ($('.custom-file-title', form).is(':visible')) {
            if (!$('.custom-file-title-input', form).val()) {
                $('.custom-file-title .feedback-required-title', form).show();
                $('.custom-file-label', form).text('Choose file');
                form.trigger('reset');
                return false;
            } else {
                $('.custom-file-title .feedback-required-title', form).hide();
                return true;
            }
        }
        return true;
    }

    function validateOneAtTime(form, files) {
        if ($('.custom-file-title', form).is(':visible')) {
            if (files.length > 1) {
                $('.custom-file-title .feedback-one-at-time', form).show();
                $('.custom-file-label', form).text('Choose file');
                form.trigger('reset');
                return false;
            } else {
                $('.custom-file-title .feedback-one-at-time', form).hide();
                return true;
            }
        }
        return true;
    }

    $(document).on('change', '.file-uploader input[type=file]', function () {

        var $clone, $form = $(this).closest("form"), progress, list;
        if (this.files) {
            if (!validateTitleProvided($form)) {
                return false;
            }
            if (!validateOneAtTime($form, this.files)) {
                return false;
            }

            let $inlineDiv = $(this).parents(".file-uploader.inline");

            if ($inlineDiv.length) {
                doUpload($inlineDiv, this.files);
            } else {
                doUpload($form, this.files);
            }

            $(this).val('');
            $clone = $(this).clone(true);
            $(this).replaceWith($clone);
        }
    });

    if (window.FileReader) {
        $(function () {
            $('.drop-message').show();
        });
        $(document).on('dragenter', '.file-uploader', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass("active");
            return false;
        });
        $(document).on('dragexit dragleave', '.file-uploader', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass("active");
            return false;
        });
        $(document).on('dragover', '.file-uploader', function (e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        $(document).on('drop', '.file-uploader', async function (e) {
            var $form = $(this);

            if (!validateTitleProvided($form)) {
                return false;
            }

            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass("active");

            var dataTransfer = e.dataTransfer;
            if (! dataTransfer) {
                dataTransfer = e.originalEvent.dataTransfer;
            }

            if (dataTransfer && dataTransfer.items) {
                let files = [];
                let items = [];
                for (let item of dataTransfer.items) {
                    items.push(item.webkitGetAsEntry());
                }
                for (let item of items) {
                    if (item) {
                        let itemFiles = await readDirectoryEntry(item, null);
                        files = files.concat(itemFiles);
                    }
                }
                if (!validateOneAtTime($form, files)) {
                    return false;
                }
                doUpload($form, files);
            }
            return false;
        });
    }

    $(document).on('click', '.file-browser .gallery-list .pagination a', function (e) {
        e.preventDefault();
        $(this).closest('.modal').animate({ scrollTop: 0 }, 'slow');
        $(this).closest('.gallery-list')
            .tikiModal(tr('Loading...'))
            .load($(this).attr('href'), function () {
                $(this).tikiModal('');
            });
    });

    $(document).on('submit', '.file-browser form.no-ajax[method="get"]', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $fileBrowser = $form.closest('.file-browser');
        const $galleryList = $fileBrowser.find('.gallery-list');
        const url = $form.attr('action');
        const data = $form.serialize();

        $galleryList.tikiModal(tr('Loading...'));

        $.get(url, data)
            .done(function (response) {
                $galleryList.html(response);
                $galleryList.tikiModal('');
            })
            .fail(function () {
                $galleryList.html('<div class="alert alert-danger">' + tr('Error loading results.') + '</div>');
                $galleryList.tikiModal('');
            });
    });

    $(document).on('click', '.file-browser .submit .upload-files', function (e) {
        var $list = $(this).closest('.file-browser').find('.selection ul'),
            handler = $.clickModal({
                success: function (data) {
                    $.each(data.files, function (k, file) {
                        $('<li>')
                            .append($('<a href="#">')
                                .data('object', file.fileId)
                                .data('type', 'file')
                                .text(file.label))
                            .append($('<input type="hidden" name="file[]">')
                                .attr('value', file.fileId))
                            .appendTo($list);

                    });
                    $.closeModal();
                }
            });

        handler.apply(this, arguments);
    });

    // File selector component
    $(document).on('click', '.file-selector a', function () {
        if (! $(this).data('initial-href')) {
            $(this).data('initial-href', $(this).attr('href'));
        }

        // Before the dialog handler triggers, replace the href with one including current files
        $(this).attr('href', $(this).data('initial-href') + '&file=' + $(this).parent().children('input').val());
    });
    $(document).on('click', '.file-selector a', $.clickModal({
        size: 'modal-lg',
        success: function (data) {
            var files = [];
            $.each(data.files, function (k, f) {
                files.push(f.fileId);
            });
            $(this).parent().children('input').val(files.join(','));
            $(this).text($(this).text().replace(/\d+/, files.length));
            $.closeModal();
        }
    }));

    $(document).on('change', '#fgal_type', function () {
        var $type = $(this),
            $form = $type.closest('form');

        $form.find('.fgal_type_dependent').hide();
        $form.find('.fgal_type_dependent.' + $type.val() + '_childcontainer').show();
        $form.find('#direct_adapter').trigger('change');
    });
    $(document).on('change', '#direct_adapter', function () {
        var $adapter = $(this),
            $form = $adapter.closest('form'),
            $directContainer = $adapter.closest('.fgal_type_dependent.direct_childcontainer');

        $form.find('.direct_adapter_dependent').hide();

        if ($directContainer.length && ! $directContainer.is(':visible')) {
            return;
        }

        $form.find('.direct_adapter_dependent.' + $adapter.val() + '_childcontainer').show();
    });

    $(document).ready(function() {
        $('input[type="file"]').on('change', function() {
            updateFileName(this);
        });
    });

    $(document).on('click', '.file-browser .gallery-list .pagination a', function (e) {
        e.preventDefault();
        $('#select-all-files').prop('checked', false);
        $(this).closest('.modal').animate({ scrollTop: 0 }, 'slow');
        $(this).closest('.gallery-list')
            .tikiModal(tr('Loading...'))
            .load($(this).attr('href'), function () {
                $(this).tikiModal('');
            });
    });

    $(document).on('click', '.file-title-toggle', function (e) {
        e.preventDefault();
        const targetId = $(this).data('checkbox-id');
        const checkbox = document.getElementById(targetId);
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
        }
    });

})(jQuery);

function updateFileName(inputFile) {
    const fileNameField = inputFile.previousElementSibling;
    if (! fileNameField) { return; }
    if (inputFile.files.length > 0) {
        fileNameField.value = inputFile.files[0].name;
    } else {
        fileNameField.value = fileNameField.getAttribute('placeholder');
    }
}

function processFgalSyntax(fileInfo, theSyntax) {
    if (Array.isArray(fileInfo)) {
        let syntax = "";
        for (var i = 0; i < fileInfo.length; i++) {
            if (i > 0) {
                syntax += "\n";
            }
            syntax += fgalSyntax(fileInfo[i], theSyntax);
        }
        return syntax;
    } else {
        return fgalSyntax(fileInfo, theSyntax);
    }
}

function fgalSyntax(fileInfo, theSyntax) {
    var key, part, syntax = "";
    if (typeof fileInfo === "string") {
        fileInfo = JSON.parse(fileInfo);
    }
    syntax = syntax ? decodeURIComponent(syntax[1]) : (theSyntax || fileInfo.syntax || fileInfo.wiki_syntax);
    for (key in fileInfo) {
        if (fileInfo.hasOwnProperty(key)) {
            part = "{" + key + "}";
            if (syntax.indexOf(part) > -1) {
                syntax = syntax.replace(part, fileInfo[key]);
            }
        }
    }
    return syntax;
}
