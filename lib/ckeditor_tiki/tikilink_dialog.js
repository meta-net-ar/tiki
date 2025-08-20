/*
* (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
*
* All Rights Reserved. See copyright.txt for details and a complete list of authors.
* Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
*
*
*
* Actually not a real plugin but a modfier for the standard ckeditor link dialog
* Initially taken from http://docs.cksource.com/CKEditor_3.x/Developers_Guide/Dialog_Customization
*/

// Prevent duplicate script execution
if (window.tikiModalManagerInitialized) {
    throw new Error('ModalManager already initialized');
}
window.tikiModalManagerInitialized = true;

// Modal and Focus Management
class ModalManager {
    static getInstance() {
        if (!ModalManager.instance) {
            ModalManager.instance = new ModalManager();
        }
        return ModalManager.instance;
    }

    constructor() {
        if (ModalManager.instance) {
            return ModalManager.instance;
        }
        ModalManager.instance = this;

        this.modalStack = [];
        this.isHandlingFocus = false;
        this.ckEditorDialogActive = false;
        this.jQueryUIDialogActive = false;
        this.initialized = false;
        this.dropdownState = new Map();
        this.isCalendarModal = false;
        this.modalInitialFocus = null;
    }

    initialize() {
        if (this.initialized) return;

        // Track modal events
        document.addEventListener('show.bs.modal', (event) => {
            const modal = event.target;
            this.isCalendarModal = modal.classList.contains('calendar-modal');

            // Store the element that triggered the modal
            this.modalInitialFocus = document.activeElement;

            // For calendar modals, delay focus handling
            if (this.isCalendarModal) {
                event.preventDefault();
                setTimeout(() => {
                    this.trackModal(modal.id);
                    modal.classList.add('show');
                    modal.style.display = 'block';
                    modal.setAttribute('aria-modal', 'true');
                    modal.removeAttribute('aria-hidden');

                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance?._focustrap) {
                        modalInstance._focustrap.deactivate();
                    }
                }, 0);
            } else {
                this.trackModal(modal.id);
            }
        });

        document.addEventListener('shown.bs.modal', (event) => {
            const modal = event.target;

            // For calendar modals, ensure dropdowns remain interactive
            if (this.isCalendarModal) {
                modal.querySelectorAll('.dropdown-toggle, .dropdown-menu').forEach(element => {
                    element.style.pointerEvents = 'auto';
                    element.style.zIndex = '1050';
                });
            }
        });

        // Enhanced focus management
        document.addEventListener('focusin', (event) => {
            const element = event.target;
            const modal = element.closest('.modal');
            const isCalendarModal = modal?.classList.contains('calendar-modal');

            // For calendar modals, handle focus differently
            if (isCalendarModal) {
                const insideDropdown = !!element.closest('.dropdown-menu');
                const isDropdownButton = element.matches('.dropdown-toggle');

                if (insideDropdown || isDropdownButton) {
                    return; // Don't interfere with dropdown focus
                }
            }

            this.handleFocus(element);
        });

        // Track dropdown events
        document.addEventListener('click', (event) => {
            const dropdownToggle = event.target.closest('.dropdown-toggle');
            const dropdownMenu = event.target.closest('.dropdown-menu');

            if (dropdownToggle) {
                const dropdownId = dropdownToggle.id || dropdownToggle.getAttribute('aria-controls');
                const isOpen = dropdownToggle.getAttribute('aria-expanded') === 'true';

                // Track dropdown state
                this.dropdownState.set(dropdownId, {
                    isOpen: isOpen,
                    toggle: dropdownToggle,
                    menu: document.getElementById(dropdownToggle.getAttribute('aria-controls')),
                    lastInteraction: Date.now()
                });
            }
        }, true);

        // Track dropdown visibility changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'aria-expanded') {
                    const dropdownToggle = mutation.target;
                    const dropdownId = dropdownToggle.id || dropdownToggle.getAttribute('aria-controls');
                    const isOpen = dropdownToggle.getAttribute('aria-expanded') === 'true';

                    // Update dropdown state
                    if (this.dropdownState.has(dropdownId)) {
                        const state = this.dropdownState.get(dropdownId);
                        state.isOpen = isOpen;
                        state.lastInteraction = Date.now();
                        this.dropdownState.set(dropdownId, state);
                    }
                }
            });
        });

        // Observe all dropdown toggles
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            observer.observe(toggle, { attributes: true });
        });

        // Modal tracking
        document.addEventListener('hide.bs.modal', (event) => {
            this.untrackModal(event.target.id);
        });

        // Modal reset when hidden
        document.addEventListener('hidden.bs.modal', (event) => {
            const modal = event.target;
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance?._focustrap) {
                modalInstance._focustrap.activate();
            }
            modal.style.pointerEvents = '';
            modal.querySelector('.modal-dialog').style.pointerEvents = '';
        });

        // CKEditor Dialog Hooks
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.on('dialogShow', (evt) => {
                const dialogName = evt.data.name;
                this.trackModal('cke-dialog-' + dialogName, true);
                this.ckEditorDialogActive = true;
                this.jQueryUIDialogActive = true;

                const parentModal = document.querySelector('.modal.show');
                if (parentModal) {
                    const modalInstance = bootstrap.Modal.getInstance(parentModal);
                    if (modalInstance?._focustrap) {
                        modalInstance._focustrap.deactivate();
                    }
                    parentModal.setAttribute('aria-modal', 'false');
                    parentModal.style.pointerEvents = 'none';
                    parentModal.querySelector('.modal-dialog').style.pointerEvents = 'auto';
                }
            });

            CKEDITOR.on('dialogHide', (evt) => {
                this.untrackModal('cke-dialog-' + evt.data.name);
                this.ckEditorDialogActive = false;
                this.jQueryUIDialogActive = false;

                const modal = document.querySelector('.modal.show');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance?._focustrap) {
                        modalInstance._focustrap.activate();
                    }
                    modal.setAttribute('aria-modal', 'true');
                    modal.style.pointerEvents = '';
                    modal.querySelector('.modal-dialog').style.pointerEvents = '';
                }
            });
        }

        // Track pointer events changes
        const originalSetProperty = CSSStyleDeclaration.prototype.setProperty;
        CSSStyleDeclaration.prototype.setProperty = function(property, value, priority) {
            return originalSetProperty.call(this, property, value, priority);
        };

        this.initialized = true;
    }

    trackModal(modalId, isCKEditorDialog = false) {
        if (!this.modalStack.some(m => m.id === modalId)) {
            this.modalStack.push({ id: modalId, isCKEditorDialog });
        }
    }

    untrackModal(modalId) {
        const index = this.modalStack.findIndex(m => m.id === modalId);
        if (index > -1) {
            this.modalStack.splice(index, 1);
        }
    }

    handleFocus(element) {
        if (this.isHandlingFocus) {
            return;
        }

        // Skip focus handling for calendar modals
        const modal = element.closest('.modal');
        if (modal?.classList.contains('calendar-modal')) {
            return;
        }

        this.isHandlingFocus = true;

        try {
            const modalId = modal?.id;
            const insideCKEditor = !!(element.closest('.cke_dialog') || element.closest('.cke_dialog_container'));
            const insideJQueryUI = !!element.closest('.ui-dialog, .ui-dialog-content, .ui-dialog-buttons');
            const insideDropdown = !!element.closest('.dropdown-menu');
            const isDropdownButton = element.matches('.dropdown-toggle');

            // Don't modify pointer events if we're interacting with a dropdown
            if (insideDropdown || isDropdownButton) {
                return;
            }

            if (this.ckEditorDialogActive || insideCKEditor || this.jQueryUIDialogActive || insideJQueryUI) {
                const parentModal = document.querySelector('.modal.show');
                if (parentModal) {
                    const modalInstance = bootstrap.Modal.getInstance(parentModal);
                    if (modalInstance?._focustrap) {
                        modalInstance._focustrap.deactivate();
                    }
                    // Only modify pointer events if we're not in a dropdown context
                    if (!insideDropdown && !isDropdownButton) {
                        parentModal.style.pointerEvents = 'none';
                        parentModal.querySelector('.modal-dialog').style.pointerEvents = 'auto';
                    }
                }
            } else if (modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance?._focustrap) {
                        modalInstance._focustrap.activate();
                    }
                    // Only modify pointer events if we're not in a dropdown context
                    if (!insideDropdown && !isDropdownButton) {
                        modal.style.pointerEvents = '';
                        modal.querySelector('.modal-dialog').style.pointerEvents = '';
                    }
                }
            }

            // Ensure dropdown menus remain interactive
            if (insideDropdown) {
                const dropdownMenu = element.closest('.dropdown-menu');
                if (dropdownMenu) {
                    dropdownMenu.style.pointerEvents = 'auto';
                    dropdownMenu.style.zIndex = '1050'; // Ensure dropdown is above modal
                }
            }
        } finally {
            this.isHandlingFocus = false;
        }
    }
}

// Create singleton instance
const modalManager = ModalManager.getInstance();
modalManager.initialize();

// CKEditor plugin registration with duplicate check
if (typeof CKEDITOR !== 'undefined') {
    // Prevent duplicate plugin registration
    const registerPlugin = (name, plugin) => {
        if (!CKEDITOR.plugins.registered[name]) {
            CKEDITOR.plugins.add(name, plugin);
        }
    };

    // Register plugins only once
    if (!window.tikiPluginsRegistered) {
        window.tikiPluginsRegistered = true;

        // Register tikilink plugin
        registerPlugin('tikilink', {
            requires: ['dialog'],
            init: function(editor) {
                // ... existing tikilink plugin code ...
            }
        });

        // Register externallink plugin
        registerPlugin('externallink', {
            requires: ['dialog'],
            init: function(editor) {
                // ... existing externallink plugin code ...
        }
    });
}

    // Dialog definition
CKEDITOR.on('dialogDefinition', function(ev) {
    // Take the dialog name and its definition from the event data.
    var dialogName = ev.data.name;
    var dialogDefinition = ev.data.definition;

    // Language definitions
    var lang = {
        wikiPage: "Wiki Page",
        linkToPage: "Link to page",
        linkText: "Link Text",
        noPageSpecified: "No page specified",
        showPage: "Show this text"
    };

    // Check if the definition is from the dialog we're
    // interested on (the Link dialog).
    if (dialogName == 'link') {
        var infoPanel = dialogDefinition.getContents('info');
        var tikilinkOptions = {
            type :  'vbox',
            id : 'tikilinkOptions',
            padding : 1,
            children :
            [
                {
                    type : 'text',
                    id : 'tikilinkPage',
                    label : lang.linkToPage,
                    required : true,
                    validate : function() {
                        var dialog = this.getDialog();
                        if (!dialog.getContentElement('info', 'linkType') ||
                                dialog.getValueOf('info', 'linkType') != 'tikilink') {
                            return true;
                        }
                        var func = CKEDITOR.dialog.validate.notEmpty(lang.noPageSpecified);
                        return func.apply(this);
                    },
                    setup : function(data) {
                        if (data.tikilink) {
                            this.setValue(data.tikilink.page);
                        }

                        var linkType = this.getDialog().getContentElement('info', 'linkType');
                        if (linkType && linkType.getValue() == 'tikilink') {
                            this.select();
                        }
                    },
                    commit : function(data) {
                        if (!data.tikilink) {
                            data.tikilink = {};
                        }
                        data.tikilink.page = this.getValue();
                    },
                    onLoad : function() {
                        var dialog = this.getDialog();
                        var input = this.getInputElement();

                        // Wait for the input to be fully rendered
                        setTimeout(() => {
                            if (!input || !input.$) return;

                            // Create a temporary input for autocomplete
                            var tempInput = document.createElement('input');
                            tempInput.type = 'text';
                            tempInput.style.position = 'absolute';
                            tempInput.style.left = '-9999px';
                            document.body.appendChild(tempInput);

                            // Initialize autocomplete on temporary input
                            if (typeof jqueryTiki !== 'undefined' && jqueryTiki.autocomplete) {
                                $(tempInput).tiki('autocomplete', 'pagename', {
                                    select: function(event, ui) {
                                        // Update CKEditor input when autocomplete item is selected
                                        dialog.getContentElement('info', 'tikilinkPage').setValue(ui.item.value);
                                    }
                                });
                            }

                            // Sync values between CKEditor input and temporary input
                            input.on('keyup', function() {
                                tempInput.value = this.getValue();
                                $(tempInput).trigger('keyup');
                            });

                            // Clean up temporary input when dialog is closed
                            dialog.on('hide', function() {
                                if (tempInput && tempInput.parentNode) {
                                    document.body.removeChild(tempInput);
                                }
                            });
                        }, 50);
                    }
                },
                {
                    type : 'text',
                    id : 'tikilinkLabel',
                    label : lang.linkText,
                    setup : function(data) {
                        if (data.tikilink) {
                            this.setValue(data.tikilink.label);
                        }
                    },
                    commit : function(data) {
                        if (!data.tikilink) {
                            data.tikilink = {};
                        }
                        data.tikilink.label = this.getValue();
                    }
                }
            ],
            setup : function(data) {
                if (!this.getDialog().getContentElement('info', 'linkType')) {
                    this.getElement().hide();
                }
            }
        };

        infoPanel.add(tikilinkOptions);

        var typeSelector = infoPanel.get('linkType');
        typeSelector.items.push([lang.wikiPage, 'tikilink']);

        var originalLinkTypeChanged = typeSelector.onChange;
        typeSelector.onChange = function() {
            var dialog = this.getDialog(),
                typeValue = this.getValue();

            var element = dialog.getContentElement('info', 'tikilinkOptions');
            if (element) {
                element = element.getElement().getParent().getParent();
            }

            if (typeValue == 'tikilink') {
                dialog.hidePage('target');
                dialog.hidePage('advanced');

                if (element) {
                    element.show();
                }
            } else {
                dialog.showPage('advanced');
                if (element) {
                    element.hide();
                }
            }
            originalLinkTypeChanged.call(this);
        };

        var originalOnOkFunction = dialogDefinition.onOk;

        dialogDefinition.onOk = function() {
            if (this.getContentElement('info', 'linkType').getValue() == "tikilink") {
                this.getContentElement('info', 'linkType').setValue('url');
                var page = encodeURI(this.getContentElement('info', 'tikilinkPage').getValue().replace(/ /g, '+'));
                if (!jqueryTiki.sefurl) {
                    page = 'tiki-index.php?page=' + page;
                }
                this.getContentElement('info', 'url').setValue(page);
                this.getContentElement('info', 'protocol').setValue('');
                this.getContentElement('advanced', 'advCSSClasses').setValue('wiki');
                if (this._.selectedElement && this._.selectedElement.setText) {
                    this._.selectedElement.setText(this.getContentElement('info', 'tikilinkLabel').getValue());
                }
            }
            originalOnOkFunction.call(this);
        };

        var originalOnShowFunction = dialogDefinition.onShow;

        dialogDefinition.onShow = function() {
            originalOnShowFunction.call(this);

            if (this.getContentElement('advanced', 'advCSSClasses').getValue() === 'wiki') {
                this.getContentElement('info', 'linkType').setValue('tikilink');
                var page = this.getContentElement('info', 'url').getValue();
                if (!jqueryTiki.sefurl) {
                    page = page.replace('tiki-index.php?page=', '');
                }
                page = tiki_decodeURI(page).replace(/\+/g, ' ');
                this.getContentElement('info', 'tikilinkPage').setValue(page);
                this.getContentElement('info', 'tikilinkLabel').setValue(this._.selectedElement.getText());
            } else {
                var seltext = this.getParentEditor().getSelection().getSelectedText();
                this.getContentElement('info', 'tikilinkLabel').setValue(seltext);
            }
        };
    }
});
}

