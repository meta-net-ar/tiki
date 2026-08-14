function initializeEditorUI(tiki={})
{
    function TikiDiagram (diagram={})
    {
        var csrfTickets = [];
        var csrfTicketsBirth = 0;
        var csrfTicketsMaxAge = 1440000;

        if (window.jqueryTiki && jqueryTiki.securityTimeout) {
            csrfTicketsMaxAge = parseInt(jqueryTiki.securityTimeout, 10) * 1000;
        }

        this.constructor = function (params)
        {
            csrfTickets = params.tickets;
            csrfTicketsBirth = Date.now();

            this.backLocation = params.backLocation;
            this.compressXml = params.compressXml;
            this.fileId = params.fileId;
            this.fileName = params.fileName || 'New Diagram';
            this.index = params.index;
            this.template = params.template;
            this.galleryId = params.galleryId;
            this.newDiagram = params.newDiagram == true;
            this.page = params.page;
            this.saveModalHTML = params.saveModal;
        };

        this.buildSaveModal = function()
        {
            var el = document.createElement('div');
            el.innerHTML = this.saveModalHTML;
            return el.firstElementChild;
        };

        this.fetch = async function (url, config={})
        {
            config = {
                'headers': {
                    'accept': 'application/json, text/javascript, */*; q=0.01',
                    'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'pragma': 'no-cache',
                    'cache-control': 'no-cache',
                    'x-requested-with': 'XMLHttpRequest'
                },
                'method': 'POST',
                ...config
            };
            response = await fetch(url, config);
            if (! response || response.status >= 400) {
                return Promise.reject(response);
            }
            return response;
        };

        this.uploadThumbnails = async function(diagrams={}, content='')
        {
            var body = new URLSearchParams();
            body.append('controller', 'diagram');
            body.append('action', 'image');
            body.append('ticket', await this.getTicket());
            body.append('name', 'Preview');
            body.append('type', 'image/png');
            body.append('fileId', this.fileId);
            body.append('content', content);

            for (var id in diagrams) {
                body.append(`data[${id}]`, diagrams[id]);
            }

            var response = await this.fetch('tiki-ajax_services.php', {
                'body': body.toString()
            });
            return response.json();
        };

        this.updateWikiPlugin = async function(content='', params={}) {
            var body = new URLSearchParams();
            body.append('controller', 'plugin');
            body.append('action', 'replace');
            body.append('message', 'Modified by mxGraph');
            body.append('type', 'diagram');
            body.append('ticket', await this.getTicket());

            this.page   && body.append('page', this.page);
            content     && body.append('content', content);
            this.index  && body.append('index', this.index);

            for (var att in params) {
                body.append(`params[${att}]`, params[att]);
            }

            var response = await this.fetch('tiki-ajax_services.php', {
                'body': body.toString()
            });
            return response.json();
        };

        this.uploadContent = async function(content)
        {
            if(!this.galleryId && !this.fileId){ //Skip file creation when
                return {content:content};
            }

            var blob = new Blob([ content ]);
            content = window.btoa(content);

            var body = new URLSearchParams();
            body.append('action', 'upload');
            body.append('controller', 'file');
            body.append('data', content);
            body.append('fileId', this.fileId);
            body.append('name', this.fileName);
            body.append('size', blob.size);
            body.append('ticket', await this.getTicket());
            body.append('type', 'application/mxgraph');
            this.galleryId && body.append('galleryId', this.galleryId);

            var response = await this.fetch("tiki-ajax_services.php", {
                'body': body.toString()
            });
            return response.json();
        };

        this.getTicket = async function()
        {
            if (this.shouldReloadTickets()) {
                await this.reloadTickets();
            }
            return csrfTickets.pop();
        };

        this.shouldReloadTickets = function ()
        {
            return csrfTickets.length < 1
                || ( Date.now() - csrfTicketsBirth ) > csrfTicketsMaxAge
            ;
        };

        this.reloadTickets = async function (numTickets=3)
        {
            var tickets = await this.fetchTickets(numTickets);
            csrfTickets = tickets;
            csrfTicketsBirth = Date.now();
            return csrfTickets;
        };

        this.fetchTickets = async function (numTickets=3)
        {
            var body = new URLSearchParams();
            body.append('controller', 'diagram');
            body.append('action', 'tickets');
            body.append('ticketsAmount', numTickets);

            var response = await this.fetch('tiki-ajax_services.php', {
                'body': body.toString()
            });

            var json = await response.json();
            if (json && json.new_tickets) {
                return json.new_tickets;
            }
            throw new Error('Failed to fetch new CSRF tickets');
        };

        this.getTikiErrorFromResponse = async function(response)
        {
            var message = null;
            var content = null;
            if (response && response.constructor === Response) {
                message = response.statusText;
                if (response.headers.get('Content-Type') === 'application/json') {
                    content = await response.json();
                    if (content.message) {
                        message = content.message;
                    }
                    else if(content.errortitle) {
                        message = content.errortitle;
                    }
                }
            }
            else if(response && response.constructor === String) {
                message = response;
            }
            return message;
        };

        this.constructor(diagram);
    }

    // Disable communication to external services
    urlParams['stealth'] = 1;
    urlParams['embed'] = 1;

    // TODO: find out where to properly setup this
    window.mxIsElectron = false;

    var tikiDiagram = new TikiDiagram(tiki);
    var editorUiInit = EditorUi.prototype.init;

    EditorUi.prototype.init = function()
    {
        editorUiInit.apply(this, arguments);
        var editorUi = this;
        var editor = editorUi.editor;

        if (! editorUi.isModeReady) {
            // TODO: check why is this not defined in most recent diagramly EditorUI/App files
            editorUi.isModeReady = editorUi.isModeEnabled = function(mode) {
                return  false;
            };
        }

        editorUi.exit = function()
        {
            if (tikiDiagram.backLocation) {
                window.location.href = tikiDiagram.backLocation;
            }
            else if (history.length > 1) {
                history.go(-1);
            }
            else {
                window.close();
            }
        };

        editorUi.collectPNGImages = function(node)
        {
            var pagesAmount = node.children.length;
            return new Promise(function(resolve, reject){
                var diagramPNGs = {};
                for (var i = 0; i < node.children.length; i++) {
                    let id = node.children[i].id;

                    editorUi.getEmbeddedPng(function(pngData) {
                        diagramPNGs[id] = pngData;

                        if (Object.keys(diagramPNGs).length === pagesAmount) {
                            resolve(diagramPNGs);
                        }
                    }, reject, '<mxfile>' + node.children[i].outerHTML + '</mxfile>');
                }
            });
        };

        editorUi.showErrorMessage = function(message) {
            $('div.diagram-saving').hide();
            $('p.diagram-error-message').html(message);

            $('div.diagram-error button').on('click', function() {
                editorUi.hideDialog();
            });

            $('div.diagram-error').show();
        };

        editorUi.saveFile = function(showDialog=true) {
            editorUi.editor.graph.stopEditing();
            var node = editorUi.getXmlFileData(true, false, !tikiDiagram.compressXml);
            var content = mxUtils.getXml(node);
            var saveElem = tikiDiagram.buildSaveModal();

            showDialog && editorUi.showDialog(saveElem, 400, 200, true, false, null, true);

            // most important, save things
            return tikiDiagram.uploadContent(content)
                .then(function(result){
                    if (tikiDiagram.page) {
                        var params = {};
                        var content = result.content ? result.content : '';
                        if(tikiDiagram.template){
                            params.template = tikiDiagram.template;
                        }
                        if(result.fileId){
                            tikiDiagram.fileId = result.fileId;
                            params.fileId = result.fileId;
                        }
                        // update the wiki_plugin, if we have a page
                        return tikiDiagram.updateWikiPlugin(content, params);
                    }
                    return result;
                })
                .then(function(){
                    // generate thumbs
                    return editorUi.collectPNGImages(node);
                })
                .then(function(images) {
                    // attempt to save the thumbnails
                    if (images) {
                        return tikiDiagram.uploadThumbnails(images, content);
                    }
                })
                .then(function() {
                    showDialog && editorUi.hideDialog(saveElem);
                })
                .catch(async function(error) {
                    var message = await tikiDiagram.getTikiErrorFromResponse(error);
                    return editorUi.showErrorMessage(message);
                });
        };

        editorUi.saveAndExit = async function(showDialog=true) {
            await editorUi.saveFile(showDialog);
            editorUi.exit();
        };

        editorUi.actions.get('exit').funct = function() {
            if (editor.modified) {
                editorUi.confirm(mxResources.get('allChangesLost'), null, function() {
                    editor.modified = false;
                    editorUi.exit();
                }, mxResources.get('cancel'), mxResources.get('discardChanges'));
            } else {
                editorUi.exit();
            }
        };

        mxResources.parse('saveAndExit=Save and Exit');
        editorUi.actions.addAction('saveAndExit', async function()
        {
            return editorUi.saveAndExit();
        });

        editorUi.keyHandler.bindAction(83, true, 'saveAndExit', true);
        editorUi.actions.get('saveAndExit').shortcut = Editor.ctrlKey + '+Shift+S';

        var menu = editorUi.menus.get('file');
        var oldFunct = menu.funct;

        menu.funct = function(menu, parent)
        {
            oldFunct.apply(this, arguments);
            editorUi.menus.addMenuItem(menu, 'saveAndExit', parent);

            let submenuItems = $(menu.table).children().children();
            let saveAndExit = submenuItems.last();

            for (var i = 0; i < submenuItems.length; i++) {
                if (submenuItems.get(i).innerText.toLowerCase() == ('Save' + Editor.ctrlKey + '+S').toLowerCase()) {
                    saveAndExit.insertAfter($(submenuItems.get(i)).before());
                    break;
                }
            }
        };
        mxResources.parse(tr('saveUnchanged=Unsaved changes. Click here to save.'));

        editorUi.menubar.addMenu(mxResources.get('saveUnchanged'), function(){
            editorUi.saveFile();
            $('.geMenubar').children().last().hide();
            } );

            $('.geMenubar').children().last().css(
            {'background-color': '#f2dede', 'color': '#a94442 !important', 'padding': '4px 6px 4px 6px',
            'border': '1px solid #ebccd1', 'border-radius': '3px', 'font-size': '12px'}
            );

        $('.geMenubar').children().last().hide();

        editor.graph.model.addListener(mxEvent.CHANGE, function(sender, evt){
            var changes = evt.getProperty('edit').changes;

            for (var i = 0; i < changes.length; i++)
            {
                var change = changes[i];
                if (change instanceof mxChildChange || change instanceof mxGeometryChange || change instanceof mxStyleChange){

                    $('.geMenubar').children().last().show();
                }
            }
        });

    };
    // Adds required resources (disables loading of fallback properties, this can only
    // be used if we know that all keys are defined in the language specific file)
    mxResources.loadDefaultBundle = false;
    var bundle = mxResources.getDefaultBundle(RESOURCE_BASE, mxLanguage) ||
        mxResources.getSpecialBundle(RESOURCE_BASE, mxLanguage);

    // Fixes possible asynchronous requests
    mxUtils.getAll([bundle, STYLE_PATH + '/default.xml'], function(xhr)
    {
        // Adds bundle text to resources
        mxResources.parse(xhr[0].getText());

        // Configures the default graph theme
        var themes = new Object();
        themes[Graph.prototype.defaultThemeName] = xhr[1].getDocumentElement();

        // Main
        var ui = new EditorUi(new Editor(urlParams['chrome'] == '0', themes));
        var xml = tiki.xmlDiagram;
        ui.openLocalFile(xml, 'tiki diagram', true);

    }, function()
    {
        document.body.innerHTML = '<div class=\"mt-5 text-center alert alert-danger\">Error loading resource files. Please check browser console.</div>';
    });
};
