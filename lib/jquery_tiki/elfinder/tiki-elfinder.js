/**
 * Tiki wrapper for elFinder
 *
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project

 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 *
 */

(function() {
    var supportedExtensions = /\.(docx|xlsx|pptx|odt|ods|odp)$/i;
    var supportedMimes = /^(application\/vnd\.(openxmlformats|oasis\.opendocument)|application\/vnd\.(ms-excel|ms-powerpoint|msword))/;

    function isSupportedFile(file) {
        return file && file.mime !== 'directory' &&
               (supportedExtensions.test(file.name) || supportedMimes.test(file.mime));
    }

    function buildCryptPadUrl(fileId, galleryId) {
        var url = 'tiki-edit_cryptpad.php?fileId=' + fileId + '&edit';
        if (galleryId) {
            url += '&galleryId=' + galleryId;
        }
        return url;
    }

    function extractFileIdFromHash(fm, hash) {
        if (hash && hash.indexOf('_') !== -1) {
            var pathPart = hash.split('_')[1];
            try {
                var base64 = pathPart.replace(/-/g, '+').replace(/_/g, '/').replace(/\./g, '=');
                while (base64.length % 4) {
                    base64 += '=';
                }
                var decoded = atob(base64);
                if (decoded && decoded.indexOf('f_') === 0) {
                    return decoded.replace('f_', '');
                }
            } catch (e) {
                // ignore
            }
        }
        return null;
    }

    function registerCryptPadCommand() {
        if (typeof elFinder === 'undefined' || typeof elFinder.prototype === 'undefined') {
            setTimeout(registerCryptPadCommand, 100);
            return;
        }

        if (typeof elFinder.prototype.commands.cryptpad !== 'undefined') {
            return;
        }

        elFinder.prototype.commands.cryptpad = function() {
            this.exec = function(hashes) {
                var fm = this.fm;
                var file = fm.file(hashes[0]);

                if (!isSupportedFile(file)) {
                    return $.Deferred().reject();
                }

                fm.request({
                    data: {cmd: 'tikiFileFromHash', 'hash[]': hashes[0] },
                    preventDefault: true,
                    raw: true
                })
                .always(function(response) {
                    var fileId, galleryId = null;
                    var data = (response && Array.isArray(response)) ? response[0] : response;

                    if (data && data.fileId) {
                        fileId = data.fileId;
                        galleryId = data.galleryId;
                    } else {
                        fileId = extractFileIdFromHash(fm, hashes[0]);
                    }

                    if (fileId) {
                        window.location.href = buildCryptPadUrl(fileId, galleryId);
                    }
                });

                return $.Deferred().resolve();
            };

            this.getstate = function(select) {
                var sel = this.files(select);
                if (sel.length !== 1) {
                    return -1;
                }
                return isSupportedFile(sel[0]) ? 0 : -1;
            };
        };

        if (typeof elFinder.prototype.i18 !== 'undefined') {
            var lang = (typeof jqueryTiki !== 'undefined' && jqueryTiki.language) ? jqueryTiki.language : 'en';
            if (lang === 'cn') {
                lang = 'zh_CN';
            } else if (lang === 'pt-br') {
                lang = 'pt_BR';
            }

            if (typeof elFinder.prototype.i18[lang] === 'undefined') {
                lang = 'en';
            }

            if (typeof elFinder.prototype.i18[lang].messages === 'undefined') {
                elFinder.prototype.i18[lang].messages = {};
            }

            elFinder.prototype.i18[lang].messages['cmdcryptpad'] = (typeof tr !== 'undefined') ? tr('Edit in CryptPad') : 'Edit in CryptPad';
        }

        if (typeof jQuery !== 'undefined') {
            $(document).ready(function() {
                if ($('#elfinder-cryptpad-icon-style').length === 0) {
                    var iconStyle = '<style id="elfinder-cryptpad-icon-style">' +
                        '.elfinder-contextmenu .elfinder-contextmenu-item.cryptpad .elfinder-contextmenu-icon, ' +
                        '.elfinder-button-icon-cryptpad { ' +
                        'background: none !important; font-size: 16px; line-height: 16px; ' +
                        'width: 16px; height: 16px; text-align: center; display: inline-block; ' +
                        '}' +
                        '.elfinder-contextmenu .elfinder-contextmenu-item.cryptpad .elfinder-contextmenu-icon:before, ' +
                        '.elfinder-button-icon-cryptpad:before { ' +
                        'content: "✎"; font-size: 16px; line-height: 16px; display: block; ' +
                        '}' +
                        '</style>';
                    $('head').append(iconStyle);
                }
            });
        }
    }

    if (typeof jQuery !== 'undefined') {
        $(document).ready(registerCryptPadCommand);
    } else {
        registerCryptPadCommand();
    }
})();

/**
 * Open a dialog with elFinder in it
 * @param element    unused?
 * @param options    object containing jquery-ui and elFinder dialog options
 * @return {Boolean}
 */

openElFinderDialog = function(element, options = {}) {
    options = $.extend({
        height : 500,
        eventOrigin: this,
        uploadCallback: null
    }, options);


    if (options.eventOrigin) {    // save it for later
        $("body").data("eventOrigin", options.eventOrigin);    // sadly adding data to the dialog kills elfinder :(
        delete options.eventOrigin;
    }

    const elfoptions = initElFinder(options);

    $.openModal({
        title: tr("Browse Files"),
        size: "modal-lg",
        dialogVariants: ["centered", "scrollable"],
        open: function () {
            $(window).data('elFinderDialog', this);
            const modalBody = $(this).find('.modal-body');
            modalBody.html('<div class="elFinderDialog" />');
            const elf = modalBody.find('.elFinderDialog');
            elf.elfinder(elfoptions).elfinder('instance');
            if (options.uploadCallback) {
                // note: elfinder('instance') is not a jQuery object and still uses bind for events
                elf.elfinder('instance').bind("upload", options.uploadCallback);
            }
        }
    });

    return false;
};

/**
 * Set up elFinder for tiki use
 *
 * @param options {Object} Tiki ones: defaultGalleryId, deepGallerySearch & getFileCallback
 *             also see https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
 * @return {Object}
 */

function initElFinder(options) {

    options = $.extend({
        getFileCallback: null,
        defaultGalleryId: 0,
        defaultVolumeId: 0,
        deepGallerySearch: true,
        url: $.service('file_finder', 'finder'), // connector URL
        // lang: 'ru',                                // language (TODO)
        customData: {
            defaultGalleryId: options.defaultGalleryId,
            deepGallerySearch: options.deepGallerySearch,
            ticket: options.ticket
        },
        commandsOptions: {
            getfile: {
                multiple: true,
            },
            info: {                // tiki specific additions for the file info dialog
                custom: {
                    hits: {
                        label: tr("Hits"),
                        tpl: '<div class="elfinder-info-hits"><span class="elfinder-info-spinner"></span></div>',
                        action: function (file, fm, dialog) {
                            fm.request({
                                data: {cmd: 'info', target: file.hash, content: ""},    // get all the info in one call
                                preventDefault: true
                            })
                                .fail(function () {
                                    dialog.find('div.elfinder-info-hits').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-fileid').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-user').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-description').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-syntax').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-edit').html(fm.i18n('unknown'));
                                })
                                .done(function (data) {
                                    var edit, id;
                                    if (file.mime === "directory") {
                                        id = data.info.galleryId;
                                        edit = "tiki-list_file_gallery.php?view=list&edit_mode=1&galleryId=" + id;
                                    } else {
                                        id = data.info.fileId;
                                        edit = "tiki-upload_file.php?fileId=" + id;
                                    }

                                    edit =  '<a href="' + edit + '">' + tr("Edit Properties") + "</a>";

                                    dialog.find('a').first().parent().html(data.info.link);
                                    dialog.find('div.elfinder-info-hits').html(data.info.hits);
                                    dialog.find('div.elfinder-info-fileid').html(id);
                                    dialog.find('div.elfinder-info-user').html(data.info.user || "");
                                    dialog.find('div.elfinder-info-description').html(data.info.description || "");
                                    dialog.find('div.elfinder-info-syntax').html(data.info.wiki_syntax || "");
                                    dialog.find('div.elfinder-info-edit').html(edit);
                                });
                        }
                    },
                    fileId: {
                        label: tr("ID"),
                        tpl: '<div class="elfinder-info-fileid"><span class="elfinder-info-spinner"></span></div>',
                    },
                    user: {
                        label: tr("User"),
                        tpl: '<div class="elfinder-info-user"><span class="elfinder-info-spinner"></span></div>',
                    },
                    syntax: {
                        label: tr("syntax"),
                        tpl: '<div class="elfinder-info-syntax"><span class="elfinder-info-spinner"></span></div>',
                    },
                    description: {
                        label: tr("Description"),
                        tpl: '<div class="elfinder-info-description"><span class="elfinder-info-spinner"></span></div>',
                    },
                    edit: {
                        label: tr("Properties"),
                        tpl: '<div class="elfinder-info-edit"><span class="elfinder-info-spinner"></span></div>',
                    }
                }
            }

        }
    }, options);

    var lang = jqueryTiki.language;
    if (lang && typeof elFinder.prototype.i18[lang] !== "undefined" && !options.lang) {
        if (lang === 'cn') {
            lang = 'zh_CN';
        } else if (lang === 'pt-br') {
            lang = 'pt_BR';
        }
        options.lang = lang;
    }

    if (options.defaultGalleryId > 0) {
        // reset the url hash just in case
        location.hash = "";
        // if it's the "root" gallery then adding the prefix makes elFinder hang
        var prefix = (options.defaultGalleryId === options.defaultVolumeId) ? "" : "d_";
        options.startPathHash = 'f' + options.defaultVolumeId + '_' + btoa(prefix + options.defaultGalleryId)
            .replace(/\+/g, '-').replace(/\//g, '_')
            .replace(/=/g, '.').replace(/\.+$/, '');
    }

    delete options.defaultGalleryId;        // moved into customData
    delete options.defaultVolumeId;
    delete options.deepGallerySearch;
    delete options.ticket;

    var remainingCommands = elFinder.prototype._options.commands;
    var disabled = ['mkfile', 'edit', 'archive', 'resize'];
    var idx;

    $.each(disabled, function (i, cmd) {
        idx = $.inArray(cmd, remainingCommands);
        if (idx !== -1) {
            remainingCommands.splice(idx, 1);
        }
    });

    if ($.inArray('cryptpad', remainingCommands) === -1) {
        remainingCommands.push('cryptpad');
    }

    if (!options.contextmenu) {
        options.contextmenu = {};
    }

    var defaultFilesMenu = ['getfile', '|', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'rename', '|', 'archive', 'extract', '|', 'info'];

    if (!options.contextmenu.files) {
        options.contextmenu.files = defaultFilesMenu.slice();
    }

    var filesMenu = options.contextmenu.files;
    var downloadIdx = $.inArray('download', filesMenu);
    var insertIdx;

    if (downloadIdx !== -1) {
        insertIdx = downloadIdx + 1;
        if (filesMenu[insertIdx] === '|') {
            insertIdx++;
        }
        filesMenu.splice(insertIdx, 0, 'cryptpad', '|');
    } else {
        var quicklookIdx = $.inArray('quicklook', filesMenu);
        if (quicklookIdx !== -1) {
            filesMenu.splice(quicklookIdx + 2, 0, 'cryptpad', '|');
        } else {
            filesMenu.unshift('cryptpad', '|');
        }
    }

    return options;
}

