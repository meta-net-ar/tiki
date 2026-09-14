(function (window, document) {
    /*
     * Presentation adapter for Converse 14's rendered Web Components.
     *
     * Converse 14 renders its UI as custom elements (tags beginning with
     * "converse-") that appear and change asynchronously as XMPP state
     * changes, outside of any lifecycle hook conversejs-tiki.js can listen
     * to directly. This file observes that DOM with a MutationObserver and
     * layers Tiki-only view-mode controls, accessibility state and visual
     * classifications on top of it. It never sends XMPP stanzas or creates
     * conversations - that stays in conversejs-tiki.js, which calls
     * install() below and supplies the handful of cross-cutting helpers
     * (chatbox lookup, activity tracking, guest-session teardown) this file
     * needs but does not own.
     *
     * SELECTORS is exported so both files reference one definition of each
     * DOM hook instead of duplicating the literal strings; keep shared hooks here.
     * Component-local selectors can remain next to their rendering logic.
     *
     * Attached to window (not an ES module export): this file must load
     * identically as a classic <script> tag (xmpp_conversejs_always_load)
     * or as a dynamically imported module (registerJsDependencies() in
     * ConverseJS.php), so it must not contain import/export statements.
     */

    var SELECTORS = {
        CHAT_FLYOUTS: "#conversejs .chatbox .box-flyout, #conversejs #controlbox .box-flyout",
        DROPDOWN: "converse-dropdown",
        DROPDOWN_HOSTS: "converse-dropdown, converse-emoji-dropdown",
        DROPDOWN_TOGGLE_IN_ROOT: "#conversejs [data-bs-toggle=\"dropdown\"]",
        CONTACT_APPROVAL_ALERT: "converse-contact-approval-alert",
        CHAT_CONTENT: "converse-chat-content",
        CHAT_MESSAGE: "converse-chat-message",
        MESSAGE_FORM: "converse-message-form",
        MUC_MESSAGE_FORM: "converse-muc-message-form",
        MESSAGE_FORMS: "converse-message-form, converse-muc-message-form",
        CHAT_TOOLBAR: "converse-chat-toolbar",
        PROFILE_MODAL: "converse-profile-modal, .profile-modal",
        CONVERSEJS_ROOT: '#conversejs',
        CONTROLBOX: '#controlbox',
        CHATBOX: '.chatbox:not(#controlbox)',
        CHATBOX_IN_ROOT: '#conversejs .chatbox:not(#controlbox)',
        CHATBOX_TITLE_TEXT: '.chatbox-title__text',
        CLOSE_CHATBOX_BUTTON: '.close-chatbox-button',
        JID_HOLDER: '[data-room-jid], [data-jid]',
        JID_HOLDER_OR_JID_ATTR: '[data-room-jid], [data-jid], [jid]',
        ROSTER_CONTACT: 'converse-roster-contact',
        TOGGLE_CONTROLBOX: '.toggle-controlbox',
        ROSTER_GROUP: '.roster-group',
        ROSTER_GROUP_CONTACTS: '.roster-group-contacts',
        OPEN_ROOMS_LIST: '.open-rooms-list',
        MUC_DOMAIN_GROUP_ROOMS: '.muc-domain-group-rooms',
        ROOMS_LIST: '.rooms-list',
        BOX_FLYOUT: '.box-flyout',
        REACTION_PICKER: '.reaction-picker',
    };

    /*
     * deps (all required, supplied by conversejs-tiki.js's initialize()):
     *   converse                     - the live _converse plugin instance
     *   storage                      - window.TikiConverseStorage
     *   fullscreenChatStorageKey     - sessionStorage key for the chat a
     *                                  fullscreen reload should reopen
     *   getChatboxElementJid         - (el) => jid|null, shared chatbox DOM
     *                                  lookup so both files agree on how a
     *                                  chatbox element is matched to a JID
     *   scheduleChatListSort         - re-sorts the roster/rooms lists by
     *                                  last activity on the next frame
     *   suppressGuestContactSuggestion - hides the "add contact" prompt for
     *                                  auto-generated guest JIDs
     *   isGeneratedGuestJid          - (jid) => bool
     *   clearTikiGuestSessionStorage - wipes the guest identity and
     *                                  Converse/Strophe storage
     *   forceShowChatboxElement      - (jid) => marks a chatbox as
     *                                  explicitly opened by the user
     *   applyAutomaticChatboxSuppression - (el) => toggles the CSS classes
     *                                  that hide/show an auto-opened chatbox
     *   explicitlyOpenedChatJids     - the shared Set of JIDs the user
     *                                  explicitly opened, cleared when a
     *                                  page is restored from bfcache
     */
    function install(deps) {
        if (document.documentElement.dataset.tikiChatPresentation === '1') return;
        document.documentElement.dataset.tikiChatPresentation = '1';

        var _converse = deps.converse;
        var storage = deps.storage;

        function isEmojiOnly(text) {
            var compact = (text || '').replace(/[\s\u200d\ufe0f]/g, '');
            if (!compact || compact.length > 32) return false;
            try {
                return /^(?:\p{Extended_Pictographic}|\p{Emoji_Presentation}|\p{Emoji_Modifier}|[#*0-9]\ufe0f?\u20e3)+$/u.test(compact);
            } catch (e) { return false; }
        }
        function classifyMessage(body) {
            var rich = body.querySelector('img, audio, video, figure, .chat-msg__media, .chat-image, .file, .attachment, a[href$=".pdf"], a[href$=".doc"], a[href$=".docx"], a[href$=".xls"], a[href$=".xlsx"], a[href$=".zip"], a[href$=".mp3"], a[href$=".m4a"], a[href$=".wav"], a[href$=".ogg"]');
            var text = body.querySelector('.chat-msg__text');
            body.classList.toggle('tiki-chat-msg--bare', !!rich || isEmojiOnly(text && text.textContent));
        }
        function suppressGuestAlertElement(element) {
            if (element && element.contact) deps.suppressGuestContactSuggestion(element.contact);
        }
        var TIKI_VIEW_MODES = {
            overlayed: { label: 'Overlayed', icon: 'fa-window-restore' },
            embedded: { label: 'Embedded', icon: 'fa-table-cells' },
            fullscreen: { label: 'Fullscreen', icon: 'fa-expand' }
        };
        function rememberChatForFullscreen(dropdown) {
            if (_converse.api.settings.get('on_xmpp_page') === 'y') return;
            var chatboxElement = dropdown.closest(SELECTORS.CHATBOX);
            var jid = deps.getChatboxElementJid(chatboxElement);
            if (!jid) return;
            var type = '';
            try {
                var model = _converse.state.chatboxes && _converse.state.chatboxes.get(jid);
                type = model && model.get('type');
            } catch (e) {}
            storage.setJSON('sessionStorage', deps.fullscreenChatStorageKey, {
                jid: String(jid).split('/')[0],
                type: type
            });
        }
        function addViewModeMenuItems(dropdown) {
            var menu = dropdown.querySelector('.dropdown-menu');
            if (!menu || menu.querySelector('.tiki-chat-viewmode-menu-item')) return;
            var current = _converse.api.settings.get('view_mode');
            var pluginDefault = (document.getElementById('conversejs') || {}).dataset;
            pluginDefault = pluginDefault ? (pluginDefault.pluginDefaultViewMode || '') : '';
            var modes = Object.keys(TIKI_VIEW_MODES);
            if (current === 'fullscreen') {
                modes = modes.filter(function (mode) { return mode !== 'overlayed'; });
                modes.push('overlayed');
            }
            modes.forEach(function (mode) {
                if (mode === current) return;
                if (mode === 'embedded' && _converse.api.settings.get('on_xmpp_page') !== 'y') return;
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item tiki-chat-viewmode-menu-item';
                var label = current === 'fullscreen' && mode === 'overlayed'
                    ? 'Quit fullscreen'
                    : TIKI_VIEW_MODES[mode].label;
                item.innerHTML = '<i class="fa ' + TIKI_VIEW_MODES[mode].icon + ' me-2" aria-hidden="true"></i>' + label;
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (mode === 'fullscreen') rememberChatForFullscreen(dropdown);
                    document.cookie = 'tiki_xmpp_view_mode=' + pluginDefault + ':' + mode + '; path=/; max-age=31536000; SameSite=Lax';
                    location.reload();
                });
                menu.appendChild(item);
            });
        }
        function showExplicitlySelectedChat(event) {
            var target = event.target.closest(SELECTORS.JID_HOLDER_OR_JID_ATTR);
            var jid = target && (target.getAttribute('data-room-jid') || target.getAttribute('data-jid') || target.getAttribute('jid'));
            if (!jid) {
                var rosterElement = event.target.closest(SELECTORS.ROSTER_CONTACT);
                var contact = rosterElement && rosterElement.model;
                jid = contact && (contact.get('jid') || contact.get('id'));
            }
            if (!jid) return;
            deps.forceShowChatboxElement(jid);
            // Converse's click handler creates unopened conversations. Reveal
            // only an existing model here: repeated async lookups used to
            // trigger concurrent renders and history loads on large chats.
            try {
                var box = _converse.state.chatboxes && _converse.state.chatboxes.get(jid);
                if (box) {
                    box.set({ hidden: false, minimized: false });
                    box.trigger('show');
                }
            } catch (e) {}
        }
        function isAnonymousProfileSession() {
            if (!_converse) return false;
            var settings = _converse.api && _converse.api.settings;
            var bareJid = _converse.session && _converse.session.get("bare_jid");
            return (settings && settings.get("anonymous") === "y") ||
                (settings && settings.get("authentication") === "anonymous") ||
                deps.isGeneratedGuestJid(bareJid);
        }
        function restrictAnonymousProfile(root) {
            if (!root || !isAnonymousProfileSession()) return;

            var modals = [];
            var modalSelector = SELECTORS.PROFILE_MODAL;
            var closestModal = root.closest && root.closest(modalSelector);
            if (closestModal) modals.push(closestModal);
            if (root.querySelectorAll) {
                root.querySelectorAll(modalSelector).forEach(function (modal) {
                    if (modals.indexOf(modal) === -1) modals.push(modal);
                });
            }

            modals.forEach(function (modal) {
                var controls = modal.querySelectorAll(
                    "#profile-tab, #profile-tabpanel, #password-tab, #reset-password-tab, " +
                    "#passwordreset-tab, #passwordreset-tabpanel, " +
                    "[href=\"#profile\"], [href=\"#password\"], [href=\"#reset-password\"], [href=\"#passwordreset-tabpanel\"], " +
                    "[aria-controls=\"profile\"], [aria-controls=\"password\"], [aria-controls=\"reset-password\"], " +
                    "[aria-controls=\"passwordreset-tabpanel\"], .logout, [data-action=\"logout\"], " +
                    ".modal-footer .btn-danger"
                );
                controls.forEach(function (control) {
                    var item = control.closest(".nav-item") || control;
                    item.hidden = true;
                    control.setAttribute("aria-hidden", "true");
                    if ("disabled" in control) control.disabled = true;
                });

                modal.querySelectorAll("button, a").forEach(function (control) {
                    var label = (control.textContent || "").trim().toLowerCase();
                    if (label === "log out" || label === "logout" || label === "reset password") {
                        var item = control.closest(".nav-item") || control;
                        item.hidden = true;
                        control.setAttribute("aria-hidden", "true");
                        if ("disabled" in control) control.disabled = true;
                    }
                });
            });
        }
        function isAnonymousSessionClose(event) {
            if (!_converse || !_converse.tikiSettings || _converse.tikiSettings.anonymous !== 'y') {
                return false;
            }
            var closeButton = event.target.closest && event.target.closest(SELECTORS.CHATBOX + ' ' + SELECTORS.CLOSE_CHATBOX_BUTTON);
            if (!closeButton) {
                return false;
            }
            var chatboxElement = closeButton.closest(SELECTORS.CHATBOX);
            var jid = deps.getChatboxElementJid(chatboxElement);
            var settings = _converse.tikiSettings;
            var bareJid = String(jid || '').split('/')[0].toLowerCase();
            // Leaving either guest destination ends the same browser session,
            // including community-only configurations without private support.
            return !!bareJid && [settings.dm_target, settings.anon_room].some(function (destination) {
                return !!destination && String(destination).split('/')[0].toLowerCase() === bareJid;
            });
        }
        var endingGuestSession = false;
        function confirmEndAnonymousSession() {
            if (endingGuestSession || document.getElementById('tiki-support-end-dialog')) return;
            var settings = _converse.tikiSettings;
            var previousFocus = document.activeElement;
            var dialog = document.createElement('dialog');
            dialog.id = 'tiki-support-end-dialog';
            dialog.className = 'p-4 rounded-4 shadow-lg bg-body text-body';
            dialog.style.cssText = 'margin: auto; max-width: min(32rem, 90vw); border: 0;';
            dialog.setAttribute('aria-labelledby', 'tiki-support-end-title');
            dialog.setAttribute('aria-describedby', 'tiki-support-end-description');
            dialog.innerHTML = '<h2 class="fs-5" id="tiki-support-end-title"></h2>' +
                '<p id="tiki-support-end-description"></p>' +
                '<button type="button" class="btn btn-secondary w-100 mt-2" data-cancel autofocus></button> ' +
                '<button type="button" class="btn btn-danger w-100 mt-2" data-confirm></button>';
            dialog.querySelector('h2').textContent = settings.support_end_title;
            dialog.querySelector('p').textContent = settings.support_end_description;
            var cancel = dialog.querySelector('[data-cancel]');
            var confirm = dialog.querySelector('[data-confirm]');
            cancel.textContent = settings.support_end_cancel_label;
            confirm.textContent = settings.support_end_confirm_label;
            function dismiss() {
                dialog.close();
                dialog.remove();
                if (previousFocus && previousFocus.isConnected) previousFocus.focus();
            }
            cancel.addEventListener('click', dismiss);
            dialog.addEventListener('cancel', function (event) {
                event.preventDefault();
                if (!endingGuestSession) dismiss();
            });
            confirm.addEventListener('click', function () {
                if (endingGuestSession) return;
                endingGuestSession = true;
                cancel.disabled = true;
                confirm.disabled = true;
                dialog.setAttribute('aria-busy', 'true');
                // Explicitly leave every MUC while the old identity is still
                // connected. A transport disconnect alone can leave occupants
                // visible until the server expires the resumable session.
                var leaveRooms = Promise.resolve().then(function () {
                    return _converse.api.rooms.get();
                }).then(function (rooms) {
                    return Promise.allSettled(rooms.map(function (room) {
                        return Promise.resolve().then(function () { return room.close(); });
                    }));
                }).catch(function () {
                    // A failed room lookup must not prevent logout.
                });
                var roomsLeft = new Promise(function (resolve) {
                    var timer = window.setTimeout(resolve, 3000);
                    leaveRooms.then(function () {
                        window.clearTimeout(timer);
                        resolve();
                    });
                });
                // Stop the active connection before clearing its persisted state.
                var logout = roomsLeft.then(function () {
                    return _converse.api.user.logout();
                }).catch(function () {
                    // Still reset the local guest identity if logout fails.
                });
                var disconnected = roomsLeft.then(function () {
                    return new Promise(function (resolve) {
                        var timer = window.setTimeout(resolve, 3000);
                        logout.then(function () {
                            window.clearTimeout(timer);
                            resolve();
                        });
                    });
                });
                disconnected.then(function () {
                    return deps.clearTikiGuestSessionStorage();
                }).then(function () {
                    window.location.reload();
                });
            });
            document.body.appendChild(dialog);
            dialog.showModal();
            cancel.focus();
        }
        /*
         * Keeps a message popup (the emoji reaction picker) inside the
         * visible chat window instead of being cut off by it.
         */
        function keepPopupWithinBounds(popup, boundsSelector) {
            var bounds = popup.closest(boundsSelector);
            if (!bounds) return;
            var popupRect = popup.getBoundingClientRect();
            var boundsRect = bounds.getBoundingClientRect();
            var clippedLeft = popupRect.left < boundsRect.left;
            var clippedRight = popupRect.right > boundsRect.right;
            if (!clippedLeft && !clippedRight) return;
            var left = clippedLeft
                ? boundsRect.left + 4
                : Math.min(popupRect.left, boundsRect.right - popupRect.width - 4);
            popup.style.setProperty('position', 'fixed', 'important');
            popup.style.setProperty('top', popupRect.top + 'px', 'important');
            popup.style.setProperty('left', left + 'px', 'important');
            popup.style.setProperty('right', 'auto', 'important');
            popup.style.setProperty('bottom', 'auto', 'important');
        }
        function fixReactionPickerClipping(picker) {
            // Checked twice: once immediately (covers the common case where
            // Converse has already set its inline position by the time this
            // observer callback runs) and once more on the next animation
            // frame, in case that position is only finalized after paint.
            keepPopupWithinBounds(picker, SELECTORS.BOX_FLYOUT);
            window.requestAnimationFrame(function () {
                keepPopupWithinBounds(picker, SELECTORS.BOX_FLYOUT);
            });
        }
        function enhance(root) {
            (root.querySelectorAll ? root.querySelectorAll(SELECTORS.CONVERSEJS_ROOT + ' .chat-msg__body') : []).forEach(classifyMessage);
            (root.querySelectorAll ? root.querySelectorAll(SELECTORS.CONVERSEJS_ROOT + ' converse-contact-approval-alert') : []).forEach(suppressGuestAlertElement);
            (root.querySelectorAll ? root.querySelectorAll(SELECTORS.REACTION_PICKER) : []).forEach(fixReactionPickerClipping);
            restrictAnonymousProfile(root);
            deps.scheduleChatListSort();
        }
        var observer = new MutationObserver(function (records) {
            var rosterChanged = false;
            records.forEach(function (record) { record.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1 || !node.matches) return;
                if (node.matches(SELECTORS.CONVERSEJS_ROOT + ' .chat-msg__body')) classifyMessage(node);
                if (node.querySelectorAll) node.querySelectorAll(SELECTORS.CONVERSEJS_ROOT + ' .chat-msg__body, .chat-msg__body').forEach(classifyMessage);
                if (node.matches(SELECTORS.CONTACT_APPROVAL_ALERT)) suppressGuestAlertElement(node);
                if (node.querySelectorAll) node.querySelectorAll(SELECTORS.CONTACT_APPROVAL_ALERT).forEach(suppressGuestAlertElement);
                if (node.matches(SELECTORS.REACTION_PICKER)) fixReactionPickerClipping(node);
                if (node.querySelectorAll) node.querySelectorAll(SELECTORS.REACTION_PICKER).forEach(fixReactionPickerClipping);
                restrictAnonymousProfile(node);
                var parentBox = node.closest && node.closest(SELECTORS.CHATBOX_IN_ROOT);
                if (parentBox) deps.applyAutomaticChatboxSuppression(parentBox);
                if (node.querySelectorAll) node.querySelectorAll(SELECTORS.CHATBOX_IN_ROOT + ', ' + SELECTORS.CHATBOX).forEach(deps.applyAutomaticChatboxSuppression);
                if (
                    node.matches(SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.ROSTER_GROUP + ', ' + SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.ROSTER_GROUP + ' *, ' + SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.OPEN_ROOMS_LIST + ', ' + SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.OPEN_ROOMS_LIST + ' *, ' + SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.ROOMS_LIST + ', ' + SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.ROOMS_LIST + ' *') ||
                    (node.querySelector && node.querySelector(SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.ROSTER_GROUP + ', ' + SELECTORS.ROSTER_GROUP + ', ' + SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.OPEN_ROOMS_LIST + ', ' + SELECTORS.OPEN_ROOMS_LIST + ', ' + SELECTORS.CONVERSEJS_ROOT + ' ' + SELECTORS.ROOMS_LIST + ', ' + SELECTORS.ROOMS_LIST))
                ) {
                    rosterChanged = true;
                }
            }); });
            if (rosterChanged) deps.scheduleChatListSort();
        });
        observer.observe(document.body, { childList: true, subtree: true });
        enhance(document);
        document.addEventListener('click', function (event) {
            if (!event.target.closest) return;
            if (isAnonymousSessionClose(event)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                confirmEndAnonymousSession();
                return;
            }
            if (event.target.closest(SELECTORS.CONTROLBOX)) showExplicitlySelectedChat(event);
            var dropdownToggle = event.target.closest(SELECTORS.CHATBOX + ' converse-dropdown [data-bs-toggle="dropdown"], ' + SELECTORS.CHATBOX + ' converse-dropdown.chatbox-btn');
            if (dropdownToggle) {
                var dropdown = dropdownToggle.closest(SELECTORS.DROPDOWN);
                setTimeout(function () { addViewModeMenuItems(dropdown); }, 0);
                setTimeout(function () { addViewModeMenuItems(dropdown); }, 100);
            }
        }, true);
        window.addEventListener('pageshow', function () {
            deps.explicitlyOpenedChatJids.clear();
            document.querySelectorAll(SELECTORS.CONVERSEJS_ROOT + ' .tiki-chat-user-selected').forEach(function (box) {
                box.classList.remove('tiki-chat-user-selected');
            });
        });
    }

    window.TikiConversePresentation = {
        SELECTORS: SELECTORS,
        install: install,
    };
})(window, document);
