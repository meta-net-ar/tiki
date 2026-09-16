(function(window, converse) {
    /*
     * Tiki integration and UX policy for Converse 14.
     *
     * PHP loads storage, presentation, then this plugin before initialize().
     * The helpers use window exports to retain the existing loading contract.
     *
     * Plugin access to this._converse is explicitly documented:
     * https://conversejs.org/docs/development/plugin-development/
     * The underscore denotes an object hidden from ordinary page scripts, not
     * a prohibition on plugin access. Documented api.settings/listen/waitUntil,
     * chats/rooms/archive/disco/connection/sendIQ operations are distinct from
     * direct state, exports and component prototype dependencies.
     *
     * Version-matched API source:
     * https://github.com/conversejs/converse.js/tree/v14.0.0/src/headless/shared/api
     * https://github.com/conversejs/converse.js/tree/v14.0.0/src/headless/plugins
     * API documentation does not guarantee compatibility across major upgrades.
     * Direct initialization-promise access is replaced with api.waitUntil().
     *
     * assertConverseInternalsAvailable checks required roots at plugin init.
     * Later lifecycle and method/collection checks diagnose incompatible optional
     * adaptations and skip the affected setup. These checks do not establish
     * compatibility with future versions or validate every event payload.
     *
     * The presentation file owns DOM observation, view-mode menus and guest
     * profile restrictions. Shared DOM hooks live in its SELECTORS object.
     * Attachment, receipt, badge and destination-choice UX remains below;
     * extraction does not make those features independent of Converse's UI.
     *
     * TIKI_SETTINGS_CONTRACT and ConverseJS::$TIKI_JS_SETTINGS_CONTRACT define
     * the same Tiki-owned keys, types, allowed values and defaults. Boundary
     * validation preserves existing flat keys, string flags and aliases.
     * /tiki-xmpp-read-cursors.php shares authenticated read positions.
     *
     * TikiConverseStorage owns optional browser persistence failures, including
     * acquisition of localStorage/sessionStorage. Callers pass storage names.
     * Browser notification, audio, network and other optional operations retain
     * their separate error handling; not every remaining catch is storage-related.
     */
    var _converse;
    var GUEST_IDENTITY_STORAGE_KEY = 'tiki-xmpp-guest-identity';

    if (!converse || !converse.plugins || typeof converse.plugins.add !== 'function') {
        return;
    }
    if (!window.TikiConverseStorage || !window.TikiConversePresentation) {
        // eslint-disable-next-line no-console
        console.error('[tiki-xmpp] conversejs-tiki-storage.js / conversejs-tiki-presentation.js did not load before conversejs-tiki.js; aborting Tiki Converse integration.');
        return;
    }
    var TikiConverseStorage = window.TikiConverseStorage;
    var TIKI_SELECTORS = window.TikiConversePresentation.SELECTORS;

    /*
     * Guest identity and cross-tab session bookkeeping.
     *
     * Anonymous/guest chat has no server-side account to key persistence
     * off, so a handful of tiki_xmpp_* localStorage/sessionStorage keys
     * stand in for it: the remembered MUC nickname, whether a support DM
     * was already auto-opened once, the chat picked before a fullscreen
     * reload, and a heartbeat used below to tell "still the same open tab"
     * apart from "a new session" when deciding what counts as unread.
     */
    function tikiMucNickStorageKey() {
        var barejid = _converse && _converse.session && _converse.session.get('bare_jid');
        return barejid ? ('tiki_xmpp_muc_nick:' + barejid) : null;
    }

    function getStoredMucNick() {
        var key = tikiMucNickStorageKey();
        return key ? TikiConverseStorage.get('localStorage', key) : null;
    }

    function setStoredMucNick(nick) {
        var key = tikiMucNickStorageKey();
        if (key) {
            TikiConverseStorage.set('localStorage', key, nick);
        }
    }

    function dmAutoOpenKey(jid) {
        return jid ? ('tiki_xmpp_dm_autoopened:' + jid) : null;
    }

    function hasAutoOpenedDm(jid) {
        var key = dmAutoOpenKey(jid);
        return key ? TikiConverseStorage.get('localStorage', key) === '1' : false;
    }

    function markDmAutoOpened(jid) {
        var key = dmAutoOpenKey(jid);
        if (key) {
            TikiConverseStorage.set('localStorage', key, '1');
        }
    }

    var FULLSCREEN_CHAT_KEY = 'tiki_xmpp_fullscreen_chat';

    // Ending an anonymous support chat has no account to log out of: this
    // removes the generated guest identity and requests browser-cache cleanup
    // before the next page load starts a new guest. Server archives are retained.
    function clearTikiGuestSessionStorage() {
        TikiConverseStorage.remove('localStorage', GUEST_IDENTITY_STORAGE_KEY);
        TikiConverseStorage.clearMatching('localStorage', /^(converse|strophe|tiki_xmpp_)/i);
        TikiConverseStorage.clearMatching('sessionStorage', /^(converse|strophe|tiki_xmpp_|guest-[0-9a-f]{8,}@)/i);
        return TikiConverseStorage.clearIndexedDbMatching(/(converse|strophe)/i);
    }

    // Fullscreen view-mode switches reload the page (see
    // conversejs-tiki-presentation.js rememberChatForFullscreen); this
    // reads back which chat to reopen after that reload, once.
    function takeFullscreenChatSelection() {
        var stored = TikiConverseStorage.get('sessionStorage', FULLSCREEN_CHAT_KEY);
        TikiConverseStorage.remove('sessionStorage', FULLSCREEN_CHAT_KEY);
        if (!stored) return null;
        var selection = TikiConverseStorage.parseJSON(stored, null);
        return selection && selection.jid ? selection : null;
    }

    var SESSION_HEARTBEAT_KEY = 'tiki_xmpp_session_heartbeat';

    // A message archived/forwarded/delayed message older than the last time
    // this browser had a live session is history, not something that
    // happened while the user was away; countArchivedMessageAsUnreadIfNeeded()
    // below uses this cutoff to decide which archived messages should still
    // increment the unread badge.
    var lastKnownSessionActiveAt = (function () {
        var stored = TikiConverseStorage.get('localStorage', SESSION_HEARTBEAT_KEY);
        return stored ? parseInt(stored, 10) : 0;
    })();

    function touchSessionHeartbeat() {
        TikiConverseStorage.set('localStorage', SESSION_HEARTBEAT_KEY, String(Date.now()));
    }

    touchSessionHeartbeat();
    setInterval(touchSessionHeartbeat, 10000);
    window.addEventListener('pagehide', touchSessionHeartbeat);
    window.addEventListener('beforeunload', touchSessionHeartbeat);

    var READ_CURSORS_KEY = 'tiki_xmpp_read_cursors';
    var CHAT_ACTIVITY_KEY = 'tiki_xmpp_chat_activity';
    var chatSortScheduled = false;

    /*
     * Chat list activity sorting.
     *
     * Converse's own roster/rooms lists have no "most recently active"
     * order. Tiki wants one, like any modern messenger, so every incoming
     * or outgoing message (noteChatActivity, wired up further down in
     * initialize()) timestamps its JID here, and scheduleChatListSort()
     * reorders the rendered DOM rows to match on the next animation frame.
     */
    function getChatActivity() {
        return TikiConverseStorage.getObject('localStorage', CHAT_ACTIVITY_KEY, {});
    }

    function noteChatActivity(jid, time) {
        var bare = String(jid || '').split('/')[0].toLowerCase();
        if (!bare) return;
        var timestamp = new Date(time || Date.now()).getTime();
        if (!isFinite(timestamp)) timestamp = Date.now();
        var activity = getChatActivity();
        if (!activity[bare] || timestamp > activity[bare]) {
            activity[bare] = timestamp;
            TikiConverseStorage.setJSON('localStorage', CHAT_ACTIVITY_KEY, activity);
        }
        scheduleChatListSort();
    }

    function getListItemJid(row) {
        var contactEl = row.matches && row.matches(TIKI_SELECTORS.ROSTER_CONTACT) ? row : row.querySelector(TIKI_SELECTORS.ROSTER_CONTACT);
        var contact = contactEl && contactEl.model;
        var jid = contact && (contact.get('jid') || contact.get('id'));
        if (!jid) {
            var jidEl = row.matches && row.matches(TIKI_SELECTORS.JID_HOLDER_OR_JID_ATTR) ? row : row.querySelector(TIKI_SELECTORS.JID_HOLDER_OR_JID_ATTR);
            jid = jidEl && (jidEl.getAttribute('data-room-jid') || jidEl.getAttribute('data-jid') || jidEl.getAttribute('jid'));
        }
        if (!jid && row.model && typeof row.model.get === 'function') {
            jid = row.model.get('jid') || row.model.get('id');
        }
        return String(jid || '').split('/')[0].toLowerCase();
    }

    function sortChatList(list, activity) {
        if (!list) return;
        var rows = Array.prototype.slice.call(list.children);
        rows.forEach(function (row, index) {
            row.__tikiChatJid = getListItemJid(row);
            row.__tikiOriginalIndex = index;
        });
        if (!rows.some(function (row) { return row.__tikiChatJid; })) return;
        var sorted = rows.slice().sort(function (a, b) {
            var byActivity = (activity[b.__tikiChatJid] || 0) - (activity[a.__tikiChatJid] || 0);
            return byActivity || a.__tikiOriginalIndex - b.__tikiOriginalIndex;
        });
        if (sorted.some(function (row, index) { return row !== rows[index]; })) {
            sorted.forEach(function (row) { list.appendChild(row); });
        }
    }

    function scheduleChatListSort() {
        if (chatSortScheduled) return;
        chatSortScheduled = true;
        window.requestAnimationFrame(function () {
            chatSortScheduled = false;
            var activity = getChatActivity();
            document.querySelectorAll(TIKI_SELECTORS.CONVERSEJS_ROOT + ' ' + TIKI_SELECTORS.ROSTER_GROUP).forEach(function (group) {
                sortChatList(group.querySelector(TIKI_SELECTORS.ROSTER_GROUP_CONTACTS), activity);
            });
            document.querySelectorAll(TIKI_SELECTORS.CONVERSEJS_ROOT + ' ' + TIKI_SELECTORS.OPEN_ROOMS_LIST).forEach(function (list) {
                var domainLists = list.querySelectorAll(TIKI_SELECTORS.MUC_DOMAIN_GROUP_ROOMS);
                if (domainLists.length) {
                    domainLists.forEach(function (domainList) { sortChatList(domainList, activity); });
                } else {
                    sortChatList(list, activity);
                }
            });
        });
    }

    /*
     * Read cursors track the last message time the user has seen per JID,
     * independently of Converse's own unread counters, because those
     * counters reset to zero when a chatbox model is torn down (closing a
     * tab, switching view mode) while a read cursor must survive that.
     * Authenticated users additionally sync their cursor to
     * /tiki-xmpp-read-cursors.php (markJidRead below) so it follows them
     * across devices; anonymous/guest sessions keep it local only, since
     * there is no account to attach server-side state to.
     */
    function getReadCursors() {
        return TikiConverseStorage.getObject('localStorage', READ_CURSORS_KEY, {});
    }

    function setReadCursor(jid, isoTime) {
        if (!jid) {
            return;
        }
        var cursors = getReadCursors();
        if (!cursors[jid] || new Date(isoTime) > new Date(cursors[jid])) {
            cursors[jid] = isoTime;
            TikiConverseStorage.setJSON('localStorage', READ_CURSORS_KEY, cursors);
        }
    }

    function markJidRead(jid) {
        if (!jid) {
            return;
        }
        var now = new Date().toISOString();
        setReadCursor(jid, now);
        if (_converse && _converse.tikiSettings && _converse.tikiSettings.anonymous === 'y') {
            return;
        }
        try {
            var body = {};
            body[jid] = now;
            fetch('/tiki-xmpp-read-cursors.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
                keepalive: true,
            }).catch(function () {});
        } catch (e) {}
    }

    var visuallySuppressedChatJids = new Set();
    var explicitlyOpenedChatJids = new Set();

    function isGeneratedGuestJid(jid) {
        var bare = String(jid || '').split('/')[0];
        var localpart = bare.split('@')[0];
        return /^guest-[0-9a-f]{8,}$/i.test(localpart);
    }

    function suppressGuestContactSuggestion(contact) {
        if (!contact || typeof contact.get !== 'function' || typeof contact.set !== 'function') return;
        var jid = contact.get('jid') || contact.get('id');
        if (isGeneratedGuestJid(jid) && contact.get('hide_contact_add_alert') !== true) {
            contact.set('hide_contact_add_alert', true);
        }
    }

    function isGeneratedGuestOccupant(occupant) {
        if (!occupant || typeof occupant.get !== 'function') return false;
        return isGeneratedGuestJid(occupant.get('jid') || occupant.get('id') || occupant.get('nick'));
    }

    function isUnavailableOccupant(occupant) {
        if (!occupant || typeof occupant.get !== 'function') return false;
        // Converse tracks MUC connectivity in presence; show may be unset online.
        var presence = occupant.get('presence');
        if (presence === 'online') return false;
        if (presence === 'offline') return true;
        var show = occupant.get('show');
        var type = occupant.get('type') || occupant.get('presence_type');
        return !show || show === 'offline' || show === 'unavailable' || type === 'unavailable';
    }

    function removeUnavailableGuestOccupants(room) {
        var occupants = room && room.occupants;
        if (!occupants || !Array.isArray(occupants.models) || typeof occupants.remove !== 'function') return;
        var ownNick = typeof room.get === 'function' ? room.get('nick') : null;
        occupants.models.slice().forEach(function (occupant) {
            var nick = occupant && typeof occupant.get === 'function' ? occupant.get('nick') : null;
            if (ownNick && nick === ownNick) return;
            if (isGeneratedGuestOccupant(occupant) && isUnavailableOccupant(occupant)) {
                occupants.remove(occupant);
            }
        });
    }

    function pruneUnavailableGuestOccupant(room, occupant) {
        var occupants = room && room.occupants;
        if (!occupants || typeof occupants.remove !== 'function') return;
        var ownNick = typeof room.get === 'function' ? room.get('nick') : null;
        var nick = occupant && typeof occupant.get === 'function' ? occupant.get('nick') : null;
        if (ownNick && nick === ownNick) return;
        if (isGeneratedGuestOccupant(occupant) && isUnavailableOccupant(occupant)) {
            occupants.remove(occupant);
        }
    }

    function findChatboxElement(jid) {
        var direct = document.getElementById('box-' + jid);
        if (direct) return direct;
        var boxes = document.querySelectorAll(TIKI_SELECTORS.CHATBOX_IN_ROOT);
        for (var i = 0; i < boxes.length; i++) {
            var heading = boxes[i].querySelector(TIKI_SELECTORS.CHATBOX_TITLE_TEXT);
            var jidHolder = boxes[i].querySelector(TIKI_SELECTORS.JID_HOLDER);
            var nestedJid = jidHolder && (jidHolder.getAttribute('data-room-jid') || jidHolder.getAttribute('data-jid'));
            if (boxes[i].getAttribute('data-jid') === jid || nestedJid === jid || (heading && heading.getAttribute('title') === jid)) return boxes[i];
        }
        return null;
    }

    function getChatboxElementJid(el) {
        if (!el) return null;
        var heading = el.querySelector(TIKI_SELECTORS.CHATBOX_TITLE_TEXT);
        var jidHolder = el.querySelector(TIKI_SELECTORS.JID_HOLDER);
        return el.getAttribute('data-jid') ||
            (jidHolder && (jidHolder.getAttribute('data-room-jid') || jidHolder.getAttribute('data-jid'))) ||
            (heading && heading.getAttribute('title')) || null;
    }

    /*
     * Chatbox visibility suppression.
     *
     * Converse auto-opens a chatbox model as soon as it is restored from
     * storage or created for an incoming message, which would otherwise
     * flood the page with every room/DM the user has ever had on session
     * restore. visuallySuppressedChatJids/explicitlyOpenedChatJids track,
     * per JID, whether the user actually asked to see that chat; the
     * force-/apply- prefixed helpers below only ever add CSS classes, they never
     * change unread counts or delete messages, so a suppressed chatbox is
     * still fully there once the user opens it.
     */
    function applyAutomaticChatboxSuppression(el) {
        var jid = getChatboxElementJid(el);
        if (!jid) return;
        el.classList.toggle('tiki-chat-auto-suppressed', visuallySuppressedChatJids.has(jid) && !explicitlyOpenedChatJids.has(jid));
        el.classList.toggle('tiki-chat-user-selected', explicitlyOpenedChatJids.has(jid));
    }

    function forceHideChatboxElement(jid) {
        if (!jid || explicitlyOpenedChatJids.has(jid)) return;
        visuallySuppressedChatJids.add(jid);
        var el = findChatboxElement(jid);
        if (el) el.classList.add('tiki-chat-auto-suppressed');
    }

    function forceShowChatboxElement(jid) {
        if (!jid) return;
        explicitlyOpenedChatJids.add(jid);
        visuallySuppressedChatJids.delete(jid);
        var el = findChatboxElement(jid);
        if (el) {
            el.classList.add('tiki-chat-user-selected');
            el.classList.remove('tiki-chat-auto-suppressed', 'hidden');
        }
    }

    function openExistingControlbox() {
        try {
            var model = _converse && _converse.state && _converse.state.chatboxes && _converse.state.chatboxes.get('controlbox');
            var view = _converse && _converse.state && _converse.state.chatboxviews && _converse.state.chatboxviews.get('controlbox');
            if (!model) return false;
            model.set({ hidden: false, minimized: false, closed: false });
            model.trigger('show');
            if (view && typeof view.show === 'function') view.show();
            var launcher = document.querySelector(TIKI_SELECTORS.TOGGLE_CONTROLBOX);
            if (launcher) launcher.classList.add('tiki-chat-launcher-hidden');
            return true;
        } catch (e) {
            return false;
        }
    }

    var reportedCompatibilityProblems = new Set();

    // Report each broken contract once, without dumping account or message data.
    function reportConverseCompatibility(feature, contract) {
        var key = feature + ':' + contract;
        if (reportedCompatibilityProblems.has(key)) return;
        reportedCompatibilityProblems.add(key);
        // eslint-disable-next-line no-console
        console.error('[tiki-xmpp] ' + feature + ': expected ' + contract + ' in Converse 14.');
    }

    // These roots are required at plugin init. Missing required roots disable the
    // Tiki integration instead of allowing a later dereference to abort startup.
    function assertConverseInternalsAvailable() {
        var missing = [];
        if (!_converse.state) missing.push('state');
        if (!_converse.exports) missing.push('exports');
        if (!_converse.session || typeof _converse.session.get !== 'function') missing.push('session.get');
        if (!_converse.api || typeof _converse.api.waitUntil !== 'function') missing.push('api.waitUntil');
        if (missing.length) {
            reportConverseCompatibility('Tiki integration disabled', missing.join(', '));
            return false;
        }
        return true;
    }

    // api.waitUntil returns the same initialized promise previously accessed via
    // _converse.promises. A missing promise must not run patches prematurely.
    function whenConverseInitialized(feature, callback) {
        var ready;
        try {
            ready = _converse.api.waitUntil('initialized');
        } catch (e) {
            reportConverseCompatibility(feature, 'api.waitUntil("initialized")');
            return Promise.resolve();
        }
        if (!ready || typeof ready.then !== 'function') {
            reportConverseCompatibility(feature, 'initialized lifecycle promise');
            return Promise.resolve();
        }
        return ready.then(callback).catch(function () {
            reportConverseCompatibility(feature, 'successful initialization callback');
        });
    }

    function canPatchConverseMethod(Ctor, method, feature) {
        if (Ctor && Ctor.prototype && typeof Ctor.prototype[method] === 'function') return true;
        reportConverseCompatibility(feature, 'prototype.' + method);
        return false;
    }

    // Check collections only when the corresponding lifecycle event has fired.
    function getConverseCollection(name, methods, feature) {
        var collection = _converse.state && _converse.state[name];
        if (collection && Array.isArray(collection.models) && methods.every(function (method) {
            return typeof collection[method] === 'function';
        })) return collection;
        reportConverseCompatibility(feature, 'state.' + name + ' collection');
        return null;
    }

    /*
     * Tiki <-> Converse settings contract.
     *
     * The single documented list of Converse settings that PHP populates
     * (see ConverseJS::$TIKI_JS_SETTINGS_CONTRACT and ConverseJS::render()
     * in lib/xmpp/ConverseJS.php, which must list the same keys) and that
     * this file reads via _converse.api.settings.get(). Each entry doubles
     * as: (a) the default passed to _converse.api.settings.extend() below,
     * so Converse itself knows about the key even if PHP omits it, and
     * (b) - when `alias` is set - the field name used to copy that value
     * onto _converse.tikiSettings, the plain object the rest of this file
     * reads instead of calling the settings API repeatedly. Entries with
     * `alias: null` are read directly via _converse.api.settings.get(key)
     * where needed instead.
     *
     * This is the one place to add, rename or document a Tiki/Converse
     * setting; keeping the JS default and the tikiSettings fallback in one
     * array (instead of duplicated literals in two places, as before)
     * prevents them from silently drifting apart.
     */
    var TIKI_SETTINGS_CONTRACT = [
        { key: 'dm_target', type: 'string', default: null, alias: 'dm_target',
            doc: 'Bare JID of the private support contact. Set by ConverseJS::set_auth() only for anonymous guests when xmpp_anonymous_mode is "support"; empty/null means anonymous guests have no private support option.' },
        { key: 'anon_room', type: 'string', default: null, alias: 'anon_room',
            doc: 'JID of the community MUC room anonymous guests are dropped into. Set by ConverseJS::set_auth() from the xmpp_anonymous_room preference.' },
        { key: 'anonymous', type: 'string', values: ['y', 'n'], default: null, alias: 'anonymous',
            doc: '"y" when this session is an anonymous/guest session (no Tiki user account). Drives guest-only UI: nickname reset box, destination-choice dialog, support presence badge, anonymous profile restrictions.' },
        { key: 'anonymous_auto_open', type: 'string', values: ['y', 'n'], default: null, alias: null,
            doc: '"n"/unset prepares the guest support DM without automatically showing a new overlay. "y" skips that preparation; this flag alone does not open a DM or bypass the chooser.' },
        { key: 'auto_open', type: 'string', values: ['y', 'n'], default: null, alias: null,
            doc: '"y" opens the configured authenticated DM once on the dedicated embedded/fullscreen page. Set by ConverseJS::set_auth()/getUserAuthOptions() from the `auto_open` request param.' },
        { key: 'on_xmpp_page', type: 'string', values: ['y', 'n'], default: null, alias: null,
            doc: '"y" when Converse is embedded on the dedicated tiki-xmpp page (as opposed to the floating launcher available on every page). Set by ConverseJS::set_auth().' },
        { key: 'current_room', type: 'string', default: null, alias: null,
            doc: 'JID of the room the current tiki-xmpp page should show/join, if any. Set by ConverseJS::set_auth() from the `room` request param.' },
        { key: 'external_jid', type: 'string', default: null, alias: null,
            doc: 'Bare JID of the user\'s external (non-Tiki-domain) XMPP account, when one is configured and available. Set by ConverseJS::getUserAuthOptions().' },
        { key: 'using_external', type: 'string', values: ['y', 'n'], default: null, alias: null,
            doc: '"y" when the current session authenticated with external_jid rather than the Tiki-internal JID. Set by ConverseJS::getUserAuthOptions(); toggled by the tiki_xmpp_external cookie set from data-tiki-xmpp-identity clicks below.' },
        { key: 'local_jid', type: 'string', default: null, alias: null,
            doc: 'The Tiki-internal JID counterpart to external_jid, kept so the UI can offer switching back. Set by ConverseJS::getUserAuthOptions().' },
        { key: 'tiki_site_name', type: 'string', default: 'Tiki', alias: 'site_name',
            doc: 'Site display name for chat UI chrome. Set by ConverseJS::render() from the sitetitle/browsertitle/sitelogo_title preferences.' },
        { key: 'tiki_site_logo', type: 'string', default: '', alias: 'site_logo',
            doc: 'Site logo URL (relative or absolute). Empty is intentional (see ConverseJS::render()): detectTikiSiteLogo() below then looks for a logo already rendered on the page instead of assuming a file exists.' },
        { key: 'tiki_support_label', type: 'string', default: 'Support', alias: 'support_label',
            doc: 'Display name substituted for dm_target\'s JID in chat headers (see prettifyJidName below). Set by ConverseJS::render() via tra("Support team").' },
        { key: 'tiki_destination_close_label', type: 'string', default: 'Close', alias: 'destination_close_label',
            doc: 'aria-label for the close button of the anonymous destination-choice dialog.' },
        { key: 'tiki_destination_title', type: 'string', default: 'How would you like to chat?', alias: 'destination_title',
            doc: 'Heading of the anonymous destination-choice dialog (shown when both dm_target and anon_room are configured).' },
        { key: 'tiki_destination_description', type: 'string', default: 'Community chat is best for general questions and shared discussion. Choose private support for help specific to you.', alias: 'destination_description',
            doc: 'Body text of the anonymous destination-choice dialog.' },
        { key: 'tiki_destination_community_label', type: 'string', default: 'Join community chat', alias: 'destination_community_label',
            doc: 'Label of the "join the public room" button in the destination-choice dialog.' },
        { key: 'tiki_destination_private_label', type: 'string', default: 'Start private chat', alias: 'destination_private_label',
            doc: 'Label of the "start a private support chat" button in the destination-choice dialog.' },
        { key: 'tiki_support_end_title', type: 'string', default: 'End this conversation?', alias: 'support_end_title',
            doc: 'Heading of the guest session end confirmation.' },
        { key: 'tiki_support_end_description', type: 'string', default: 'Your guest session will end and you will leave all chats. When you start chatting again, you will have a new guest identity. Previous messages may remain visible to other participants.', alias: 'support_end_description',
            doc: 'Explains the consequences of ending a guest session.' },
        { key: 'tiki_support_end_cancel_label', type: 'string', default: 'Continue chatting', alias: 'support_end_cancel_label',
            doc: 'Cancels ending the guest session.' },
        { key: 'tiki_support_end_confirm_label', type: 'string', default: 'End conversation', alias: 'support_end_confirm_label',
            doc: 'Confirms ending the guest session.' },
        { key: 'tiki_anonymous_nick', type: 'string', values: ['custom', 'visitor'], default: 'visitor', alias: 'anonymous_nick',
            doc: '"custom" lets an anonymous guest set/keep their own MUC nickname; "visitor" uses the existing Converse/previous-nickname fallback and generates a visitor nickname when needed. Set by ConverseJS::set_auth() from xmpp_anonymous_allow_custom_nickname.' },
    ];

    function buildTikiSettingsDefaults() {
        var defaults = {};
        TIKI_SETTINGS_CONTRACT.forEach(function (entry) {
            defaults[entry.key] = entry.default;
        });
        return defaults;
    }

    /*
     * Validate all Tiki keys once, including entries without aliases and nullable
     * defaults. Write normalized values back so direct settings.get() consumers
     * and tikiSettings aliases observe the same contract. Log keys, not values.
     */
    function buildTikiSettings() {
        var settings = {};
        TIKI_SETTINGS_CONTRACT.forEach(function (entry) {
            var value = _converse.api.settings.get(entry.key);
            if (value === null || value === undefined) {
                value = entry.default;
            } else if (typeof value !== entry.type || (entry.values && entry.values.indexOf(value) === -1)) {
                // eslint-disable-next-line no-console
                console.warn('[tiki-xmpp] Invalid Tiki setting "' + entry.key + '"; using its documented default.');
                value = entry.default;
            }
            _converse.api.settings.set(entry.key, value);
            if (entry.alias) settings[entry.alias] = value || entry.default;
        });
        return settings;
    }

    /*
     * Public Converse plugin entry point.
     *
     * dependencies contains Converse plugin identifiers, not JavaScript module
     * paths. initialize() wires Tiki settings and lifecycle listeners after
     * those plugins are available.
     */
    converse.plugins.add("tiki", {
        "dependencies": [
            'converse-muc-views',
            'converse-controlbox',
            'converse-chatview',
            'converse-mam',
        ],

        "initialize": function () {
            _converse = this._converse;
            if (!assertConverseInternalsAvailable()) return;
            window.TikiConversePresentation.install({
                converse: _converse,
                storage: TikiConverseStorage,
                fullscreenChatStorageKey: FULLSCREEN_CHAT_KEY,
                getChatboxElementJid: getChatboxElementJid,
                scheduleChatListSort: scheduleChatListSort,
                suppressGuestContactSuggestion: suppressGuestContactSuggestion,
                isGeneratedGuestJid: isGeneratedGuestJid,
                clearTikiGuestSessionStorage: clearTikiGuestSessionStorage,
                forceShowChatboxElement: forceShowChatboxElement,
                applyAutomaticChatboxSuppression: applyAutomaticChatboxSuppression,
                explicitlyOpenedChatJids: explicitlyOpenedChatJids,
            });

            // Browsers require a user gesture before granting notification
            // permission or letting audio play with sound. This "warms up"
            // both on the very first click/keydown/touch anywhere on the
            // page (not just inside the chat), so a later incoming message
            // can show a desktop notification and play a sound without
            // itself needing to be a user gesture.
            function primeNotificationsAndAudio() {
                if (window.Notification && Notification.permission === 'default') {
                    try {
                        var permissionRequest = Notification.requestPermission();
                        if (permissionRequest && typeof permissionRequest.catch === 'function') permissionRequest.catch(function () {});
                    } catch (e) {}
                }
                try {
                    if (window.Audio) {
                        var soundsPath = (_converse.api.settings.get('sounds_path')) || '/';
                        var audio = new Audio(soundsPath + 'msg_received.ogg');
                        audio.volume = 0;
                        var playback = audio.play();
                        if (playback && typeof playback.then === 'function') {
                            playback.then(function () { audio.pause(); }).catch(function () {});
                        } else {
                            audio.pause();
                        }
                    }
                } catch (e) {}
            }
            ['click', 'keydown', 'touchstart'].forEach(function (evt) {
                document.addEventListener(evt, primeNotificationsAndAudio, { once: true, capture: true });
            });

            _converse.api.settings.extend(buildTikiSettingsDefaults());
            _converse.tikiSettings = buildTikiSettings();
            // Load private archives like a modern messenger: a small initial
            // page, with older MAM pages requested when the user scrolls up.
            _converse.api.settings.set('archived_messages_page_size', '20');
            _converse.api.settings.set('mam_request_all_pages', false);
            _converse.api.settings.set('muc_nickname_from_jid', true);

            // Prosody can omit `to` from outgoing messages returned by a
            // private MAM query. Converse cannot route those archived stanzas,
            // even though the query's `with` value identifies the recipient.
            // Restore only that missing value before Converse parses them.
            var archiveApi = _converse.api.archive;
            if (archiveApi && typeof archiveApi.query === 'function' && !archiveApi.query.__tikiMissingToPatched) {
                var originalArchiveQuery = archiveApi.query;
                var patchedArchiveQuery = async function (query) {
                    var result = await originalArchiveQuery.apply(this, arguments);
                    var peerJid = query && !query.is_groupchat && query.mam && query.mam.with;
                    var ownJid = _converse.session && _converse.session.get('bare_jid');
                    if (!peerJid || !ownJid || !result || !Array.isArray(result.messages)) {
                        return result;
                    }
                    result.messages.forEach(function (stanza) {
                        if (!stanza || typeof stanza.getAttribute !== 'function') return;

                        var message = stanza;
                        var forwarded = stanza.getElementsByTagName && stanza.getElementsByTagName('forwarded');
                        if (forwarded && forwarded.length) {
                            for (var i = 0; i < forwarded.length; i++) {
                                if (forwarded[i].getAttribute('xmlns') !== converse.env.Strophe.NS.FORWARD) continue;
                                var messages = forwarded[i].getElementsByTagName('message');
                                if (messages.length) {
                                    message = messages[0];
                                    break;
                                }
                            }
                        }
                        if (message.getAttribute('to')) return;
                        var from = message.getAttribute('from');
                        var fromBare = from && converse.env.Strophe.getBareJidFromJid(from);
                        if (fromBare && fromBare.toLowerCase() === ownJid.toLowerCase()) {
                            message.setAttribute('to', peerJid);
                        }
                    });
                    return result;
                };
                patchedArchiveQuery.__tikiMissingToPatched = true;
                archiveApi.query = patchedArchiveQuery;
            }

            // Authenticated users may read the same account from more than
            // one device/browser; each keeps its own local read-cursor copy
            // (see getReadCursors() above), so on startup the local copy is
            // merged with whatever /tiki-xmpp-read-cursors.php last saw,
            // keeping the most recent cursor per JID from either source.
            // Anonymous guests have no account to sync against, so they
            // keep only the local copy.
            if (_converse.api.settings.get('anonymous') !== 'y') {
                fetch('/tiki-xmpp-read-cursors.php', { credentials: 'include' })
                    .then(function (res) { return res.ok ? res.json() : {}; })
                    .then(function (serverCursors) {
                        var local = getReadCursors();
                        var merged = Object.assign({}, local);
                        Object.keys(serverCursors || {}).forEach(function (jid) {
                            if (!merged[jid] || new Date(serverCursors[jid]) > new Date(merged[jid])) {
                                merged[jid] = serverCursors[jid];
                            }
                        });
                        TikiConverseStorage.setJSON('localStorage', READ_CURSORS_KEY, merged);
                    })
                    .catch(function () {});
            }



            function detectTikiSiteLogo() {
                var selectors = [
                    '#sitelogo img',
                    '.sitelogo img',
                    '.navbar-brand img',
                    '#top_modules img',
                    'header .site-logo img',
                    'header img[alt*="logo" i]',
                ];
                for (var i = 0; i < selectors.length; i++) {
                    var image = document.querySelector(selectors[i]);
                    if (image && !image.closest('#conversejs')) {
                        return image.currentSrc || image.getAttribute('src') || '';
                    }
                }
                return '';
            }

            var logo = String(_converse.tikiSettings.site_logo || detectTikiSiteLogo());
            if (logo) {
                try {
                    logo = new URL(logo, document.baseURI).href;
                } catch (e) {}
                logo = logo.replace(/["\\\n\r]/g, '\\$&');
                document.documentElement.style.setProperty('--tiki-chat-logo-image', 'url("' + logo + '")');
            }

            var error = function (msg) { return feedback(msg, "error", false); };
            var tr = window.tr
                ? window.tr
                : function(str) { return str; };

            // Strophe/Converse pick a SASL mechanism by priority and would
            // otherwise prefer a stronger one the guest server may also
            // advertise. Guest identities (see ConverseJS::render()) only
            // ever have SASL ANONYMOUS credentials, so raising its priority
            // ensures that mechanism is actually the one selected.
            if (_converse.api.settings.get('authentication') === "anonymous") {
                whenConverseInitialized('anonymous authentication', function () {
                    const connection = _converse.api.connection.get();
                    if (connection && connection.mechanisms && connection.mechanisms.ANONYMOUS) {
                        delete connection.mechanisms.ANONYMOUS.priority;
                        connection.mechanisms.ANONYMOUS.priority = 50;
                    }
                });
            }

            // MUC nickname continuity: Converse's own default nickname
            // logic has no notion of "the nickname this browser used last
            // time". Authenticated users get their last chosen nickname
            // back (getStoredMucNick(), tiki_xmpp_muc_nick:<jid> in
            // localStorage) instead of a fresh Converse-generated one on
            // every join; anonymous guests instead get a random
            // "visitor-NNNNN" nickname each time, unless
            // tiki_anonymous_nick is "custom" (xmpp_anonymous_allow_custom_nickname),
            // in which case Converse's own default is left alone so the
            // nick-reset box (see the ChatBoxView overrides below) is what
            // controls it.
            whenConverseInitialized('MUC nickname', function () {
                const originalGetDefaultMUCNickname = _converse.exports.getDefaultMUCNickname;

                if (typeof originalGetDefaultMUCNickname !== 'function') {
                    reportConverseCompatibility('MUC nickname', 'exports.getDefaultMUCNickname');
                    return;
                }

                Object.assign(_converse.exports, {
                    getDefaultMUCNickname: function (...args) {
                        if (_converse.tikiSettings.anonymous !== 'y') {
                            const stored = getStoredMucNick();
                            return stored || originalGetDefaultMUCNickname.apply(this, args);
                        }

                        if (_converse.tikiSettings.anonymous_nick === 'custom') {
                            return originalGetDefaultMUCNickname.apply(this, args);
                        }

                        const previousNick = (typeof getPreviousAnonymousNick === 'function')
                            ? getPreviousAnonymousNick()
                            : null;

                        const randomVisitorNick = (typeof randomNick === 'function')
                            ? randomNick('visitor')
                            : 'visitor-' + Math.floor(Math.random() * 100000);

                        return (
                            originalGetDefaultMUCNickname.apply(this, args) ||
                            previousNick ||
                            randomVisitorNick
                        );
                    }
                });
            });

            whenConverseInitialized('display names and history', function () {
                // Bookmarks are a feature of the user's own XMPP account;
                // Tiki-internal JIDs (using_external !== 'y') have no
                // meaningful bookmarks of their own; only an external
                // account the user owns should surface/auto-join them.
                if (_converse.api.settings.get('using_external') !== 'y') {
                    _converse.api.settings.set('bookmarks', false);
                    _converse.api.settings.set('auto_join_bookmarks', false);
                }
                // Converse renders its first minimized-chats toggle with
                // the "primary" (accent) button style; Tiki reserves that
                // styling for actionable/attention-seeking buttons
                // elsewhere on the page, so it is restyled once, the first
                // time Converse creates it.
                const observer = new MutationObserver(function() {
                    const toggleBtn = document.querySelector('#minimized-chats > button.btn-primary');
                    if (toggleBtn) {
                        toggleBtn.classList.remove('btn-primary');
                        toggleBtn.classList.add('btn-secondary');
                        observer.disconnect();
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });

                // Converse's own display name for a contact/occupant is
                // often a bare JID or JID-derived string. This substitutes
                // the configured support label for the support JID, and
                // title-cases the local part of any other bare JID, so the
                // chat UI never shows a raw address to the user.
                function prettifyJidName(name) {
                    if (typeof name !== 'string') {
                        return name;
                    }
                    var dmTarget = _converse.tikiSettings && _converse.tikiSettings.dm_target;
                    if (dmTarget && name.toLowerCase() === dmTarget.toLowerCase()) {
                        return _converse.tikiSettings.support_label;
                    }
                    var atIndex = name.indexOf('@');
                    if (atIndex <= 0) {
                        return name;
                    }
                    var local = name.slice(0, atIndex);
                    return local.charAt(0).toUpperCase() + local.slice(1);
                }

                function patchGetDisplayName(Ctor) {
                    if (!canPatchConverseMethod(Ctor, 'getDisplayName', 'display names')) {
                        return;
                    }
                    var original = Ctor.prototype.getDisplayName;
                    Ctor.prototype.getDisplayName = function () {
                        return prettifyJidName(original.apply(this, arguments));
                    };
                }

                function patchMissingJidVCardLookup(Ctor) {
                    if (!canPatchConverseMethod(Ctor, 'getVCard', 'VCard lookup') || Ctor.prototype.__tikiMissingJidVCardPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiMissingJidVCardPatched = true;
                    var original = Ctor.prototype.getVCard;
                    Ctor.prototype.getVCard = function () {
                        var from = this.get && (this.get('jid') || this.get('from'));
                        var occupantJid = this.occupant && this.occupant.get && this.occupant.get('jid');
                        if (!from && !occupantJid) {
                            // Converse cannot perform a VCard request without a
                            // JID. System and fully-anonymous MUC messages are
                            // valid, so skip only this impossible lookup.
                            return Promise.resolve(null);
                        }
                        return original.apply(this, arguments);
                    };
                }

                function patchNonBlockingInitialMessageFetch(Ctor) {
                    if (!canPatchConverseMethod(Ctor, 'fetchMessages', 'private history') || Ctor.prototype.__tikiNonBlockingFetchPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiNonBlockingFetchPatched = true;
                    var originalFetchMessages = Ctor.prototype.fetchMessages;
                    Ctor.prototype.fetchMessages = function () {
                        // Keep groupchat restoration unchanged. This shortcut
                        // is only for potentially very long one-to-one chats.
                        if (this.get('type') !== 'chatbox' || !this.messages || this.messages.fetched_flag) {
                            return originalFetchMessages.apply(this, arguments);
                        }
                        var storage = this.messages.storage;
                        if (storage && storage.store && typeof storage.findAll === 'function' && !storage.__tikiRecentMessagesOnly) {
                            storage.__tikiRecentMessagesOnly = true;
                            var originalFindAll = storage.findAll;
                            storage.findAll = async function () {
                                // Read only the newest local page initially.
                                // Nothing is deleted; older history remains in
                                // IndexedDB and can also be retrieved via MAM.
                                storage.findAll = originalFindAll;
                                try {
                                    await storage.storeInitialized;
                                    var keys = await storage.store.getItem(storage.name);
                                    if (!Array.isArray(keys) || !keys.length) return [];
                                    var recentKeys = keys.slice(-20);
                                    var items = await storage.store.getItems(recentKeys);
                                    return recentKeys.map(function (key) { return items[key]; }).filter(Boolean);
                                } catch (e) {
                                    return originalFindAll.call(storage);
                                }
                            };
                        }
                        try {
                            var fetchResult = originalFetchMessages.apply(this, arguments);
                            if (fetchResult && typeof fetchResult.catch === 'function') {
                                fetchResult.catch(function () {});
                            }
                        } catch (e) {
                            return Promise.reject(e);
                        }
                        // Let the chatbox mount immediately while IndexedDB is
                        // read in the background.
                        return Promise.resolve();
                    };
                }

                [
                    _converse.exports.VCard,
                    _converse.exports.ChatBox,
                    _converse.exports.Message,
                    _converse.exports.RosterContact,
                    _converse.exports.MUC,
                    _converse.exports.MUCOccupant,
                ].forEach(patchGetDisplayName);
                patchNonBlockingInitialMessageFetch(_converse.exports.ChatBox);
                patchMissingJidVCardLookup(_converse.exports.Message);
                patchMissingJidVCardLookup(_converse.exports.MUCMessage);
            });

            // Converse 14 tracks XEP-0085 chat-state notifications
            // (composing/paused) on the model but does not render a
            // "user is typing" indicator for them; this patch re-renders
            // the notifications area on every model update and toggles a
            // CSS class Tiki's stylesheet turns into that indicator.
            whenConverseInitialized('typing indicator', function () {
                function patchTypingIndicator(Ctor) {
                    if (!Ctor || !Ctor.prototype) {
                        reportConverseCompatibility('typing indicator', 'registered custom element');
                        return;
                    }
                    if (Ctor.prototype.__tikiTypingPatched) return;
                    Ctor.prototype.__tikiTypingPatched = true;
                    var originalUpdated = Ctor.prototype.updated;
                    Ctor.prototype.updated = function (changed) {
                        if (originalUpdated) {
                            originalUpdated.call(this, changed);
                        }
                        var el = this.querySelector('.chat-content__notifications');
                        if (el && this.model && this.model.notifications) {
                            var composing = this.model.notifications.get('composing');
                            var isComposing = (composing && composing.length) ||
                                this.model.notifications.get('chat_state') === 'composing';
                            el.classList.toggle('tiki-is-typing', !!isComposing);
                        }
                    };
                }
                patchTypingIndicator(customElements.get(TIKI_SELECTORS.CHAT_CONTENT));
            });

            // WhatsApp-style single/double read-receipt ticks, rendered
            // next to Converse's own delivery receipt icon. Converse
            // exposes the underlying XEP-0184 "received" and XEP-0333
            // "marker_displayed" message attributes but has no built-in
            // tick UI for them; this reads those two attributes on every
            // message re-render and (re)inserts the tick markup.
            whenConverseInitialized('receipt ticks', function () {
                function patchReceiptTicks(Ctor) {
                    if (!Ctor || !Ctor.prototype) {
                        reportConverseCompatibility('receipt ticks', 'registered custom element');
                        return;
                    }
                    if (Ctor.prototype.__tikiReceiptPatched) return;
                    Ctor.prototype.__tikiReceiptPatched = true;
                    var originalUpdated = Ctor.prototype.updated;
                    Ctor.prototype.updated = function (changed) {
                        if (originalUpdated) {
                            originalUpdated.call(this, changed);
                        }
                        if (!this.model) {
                            return;
                        }
                        var receiptIcon = this.querySelector('.chat-msg__receipt');
                        var wrapper = this.querySelector('.chat-msg__body--wrapper');
                        if (!wrapper) {
                            return;
                        }
                        var existingTicks = wrapper.querySelector('.tiki-receipt-ticks');
                        if (existingTicks) {
                            existingTicks.remove();
                        }
                        if (!receiptIcon || !this.model.get('received')) {
                            return;
                        }
                        var isRead = !!this.model.get('marker_displayed');
                        var ticks = document.createElement('span');
                        ticks.className = 'tiki-receipt-ticks' + (isRead ? ' tiki-receipt-ticks--read' : '');
                        var tick1 = document.createElement('span');
                        tick1.className = 'tiki-tick';
                        ticks.appendChild(tick1);
                        if (isRead) {
                            var tick2 = document.createElement('span');
                            tick2.className = 'tiki-tick';
                            ticks.appendChild(tick2);
                        }
                        receiptIcon.insertAdjacentElement('afterend', ticks);
                    };
                }
                patchReceiptTicks(customElements.get(TIKI_SELECTORS.CHAT_MESSAGE));
            });

            // Converse applies an incoming XEP-0333 chat marker (e.g.
            // "displayed") to its own internal state but does not persist
            // *when* that marker arrived onto the message itself; the
            // receipt-ticks patch above needs that per-message timestamp
            // (marker_displayed) to know a specific tick should turn into
            // two, so this records it the first time each marker is seen.
            whenConverseInitialized('chat markers', function () {
                function patchHandleChatMarker(Ctor) {
                    if (!canPatchConverseMethod(Ctor, 'handleChatMarker', 'chat markers') || Ctor.prototype.__tikiChatMarkerPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiChatMarkerPatched = true;
                    var originalHandleChatMarker = Ctor.prototype.handleChatMarker;
                    Ctor.prototype.handleChatMarker = function (attrs) {
                        var result = originalHandleChatMarker.apply(this, arguments);
                        if (attrs && attrs.marker_id && attrs.marker) {
                            var message = this.messages.findWhere({ msgid: attrs.marker_id });
                            var fieldName = 'marker_' + attrs.marker;
                            if (message && !message.get(fieldName)) {
                                message.save(fieldName, new Date().toISOString());
                            }
                        }
                        return result;
                    };
                }
                patchHandleChatMarker(_converse.exports.ChatBox);
            });
            whenConverseInitialized('activity and read cursors', function () {
                // Every roster contact is (re)checked once it exists so a
                // guest JID added before this listener was attached (e.g.
                // restored from IndexedDB) is still covered, not just ones
                // added afterwards.
                Promise.resolve(_converse.api.waitUntil('rosterInitialized')).then(function () {
                    var roster = getConverseCollection('roster', ['on'], 'guest roster');
                    if (!roster) return;
                    roster.models.forEach(suppressGuestContactSuggestion);
                    roster.on('add change:jid', function (contact) {
                        suppressGuestContactSuggestion(contact);
                        scheduleChatListSort();
                    });
                }).catch(function () {});

                // Seeds the chat-activity-sort timestamps (see "Chat list
                // activity sorting" above) from each chatbox's own already
                // loaded history, then keeps tracking as new messages
                // arrive - covering both chatboxes that already existed
                // when this ran and ones created afterwards.
                Promise.resolve(_converse.api.waitUntil('chatBoxesInitialized')).then(function () {
                    function trackChatboxActivity(chatbox) {
                        if (!chatbox || chatbox.__tikiActivityTracked) return;
                        chatbox.__tikiActivityTracked = true;
                        var jid = chatbox.get('jid');
                        if (!jid || !chatbox.messages) return;
                        if (chatbox.messages.models.length) {
                            var latest = chatbox.messages.models.reduce(function (time, message) {
                                var candidate = new Date(message.get('time') || 0).getTime();
                                return isFinite(candidate) && candidate > time ? candidate : time;
                            }, 0);
                            if (latest) noteChatActivity(jid, latest);
                        }
                        chatbox.messages.on('add', function (message) {
                            if (!message || message.get('is_error')) return;
                            noteChatActivity(jid, message.get('time') || Date.now());
                        });
                    }
                    var boxes = getConverseCollection('chatboxes', ['on'], 'sidebar activity');
                    if (!boxes) return;
                    boxes.models.forEach(trackChatboxActivity);
                    boxes.on('add', trackChatboxActivity);
                    scheduleChatListSort();
                }).catch(function () {});

                // Fires whenever a chatbox is shown or hidden: re-applies
                // the suppression from "Chatbox visibility suppression"
                // above if Converse itself tried to reveal a chat the user
                // never asked for, and otherwise treats becoming visible
                // (while scrolled to the bottom) as the user having read
                // it - clearing Converse's own unread counter and, for a
                // non-groupchat that is not talking to yourself, syncing
                // the read cursor via markJidRead().
                var boxes = getConverseCollection('chatboxes', ['on'], 'read cursors');
                if (!boxes) return;
                boxes.on('change:hidden', function (model) {
                    var modelJid = model.get('jid');
                    if (
                        model.get('hidden') === false &&
                        visuallySuppressedChatJids.has(modelJid) &&
                        !explicitlyOpenedChatJids.has(modelJid)
                    ) {
                        model.set({ hidden: true, minimized: true });
                        forceHideChatboxElement(modelJid);
                        return;
                    }
                    if (model.get('hidden') || typeof model.clearUnreadMsgCounter !== 'function') {
                        return;
                    }
                    if (typeof model.isScrolledUp === 'function' && model.isScrolledUp()) {
                        return;
                    }
                    model.clearUnreadMsgCounter();
                    if (model.get('type') === 'chatroom') {
                        return;
                    }
                    var jid = modelJid;
                    var bareJid = _converse.session && _converse.session.get('bare_jid');
                    if (jid && bareJid && jid.toLowerCase() === bareJid.toLowerCase()) {
                        return;
                    }
                    markJidRead(jid);
                });
            });

            /*
             * Unread-count reconstruction for archived/backlog messages.
             *
             * Converse only increments its own unread counters for
             * messages received live, on an already-open connection; MAM
             * catch-up and delayed/forwarded delivery (the 'message' /
             * 'MAMResult' listeners below) skip that increment entirely.
             * Without this, reconnecting or opening a chat after being
             * away would show 0 unread even though messages arrived while
             * the user was gone. countArchivedMessageAsUnreadIfNeeded()
             * re-derives whether each backlog message should count:
             * already covered by a read cursor -> clear any stale counter;
             * older than this browser's last known active moment, sent by
             * the user themselves, or the chat is already visible -> not
             * unread; otherwise increment the counter Converse would have
             * incremented itself, deduplicated per message.
             */
            function isOwnAttrsMessage(attrs, chatbox) {
                if (chatbox.get('type') === 'chatroom') {
                    var resource = attrs.from && converse.env.Strophe.getResourceFromJid(attrs.from);
                    return !!(resource && chatbox.get('nick') && resource === chatbox.get('nick'));
                }
                var bareJid = _converse.session && _converse.session.get('bare_jid');
                var fromBare = attrs.from && converse.env.Strophe.getBareJidFromJid(attrs.from);
                return !!(bareJid && fromBare && fromBare.toLowerCase() === bareJid.toLowerCase());
            }

            var countedArchivedMessageKeys = new Set();

            function countArchivedMessageAsUnreadIfNeeded(attrs, chatbox) {
                if (!attrs || !chatbox || !attrs.time || !attrs.body) {
                    return;
                }
                var jid = chatbox.get('jid');
                var cursor = jid && getReadCursors()[jid];
                var isReadPerCursor = cursor && new Date(attrs.time) <= new Date(cursor);
                if (isReadPerCursor) {
                    Promise.resolve().then(function () {
                        if (typeof chatbox.clearUnreadMsgCounter === 'function') {
                            chatbox.clearUnreadMsgCounter();
                        }
                    });
                }
                var msgTime = new Date(attrs.time).getTime();
                if (!(msgTime > lastKnownSessionActiveAt)) {
                    return;
                }
                if (isOwnAttrsMessage(attrs, chatbox)) {
                    return;
                }
                if (chatbox.get('hidden') === false) {
                    return;
                }
                var dedupKey = jid + '|' + attrs.from + '|' + attrs.time + '|' + attrs.body.slice(0, 80);
                if (countedArchivedMessageKeys.has(dedupKey)) {
                    return;
                }
                countedArchivedMessageKeys.add(dedupKey);
                Promise.resolve().then(function () {
                    try {
                        if (chatbox.get('type') === 'chatroom') {
                            chatbox.save('num_unread_general', (chatbox.get('num_unread_general') || 0) + 1);
                        } else {
                            chatbox.save('num_unread', (chatbox.get('num_unread') || 0) + 1);
                        }
                    } catch (e) {}
                });
            }

            _converse.api.listen.on('message', function (data) {
                try {
                    var attrs = data && data.attrs;
                    var chatbox = data && data.chatbox;
                    if (!attrs || !chatbox || (!attrs.is_archived && !attrs.is_forwarded && !attrs.is_delayed)) {
                        return;
                    }
                    countArchivedMessageAsUnreadIfNeeded(attrs, chatbox);
                } catch (e) {}
            });

            _converse.api.listen.on('MAMResult', function (data) {
                try {
                    var chatbox = data && data.chatbox;
                    var messages = data && data.messages;
                    if (!chatbox || !messages || !messages.length) {
                        return;
                    }
                    messages.forEach(function (attrs) {
                        if (attrs && !attrs.is_error) {
                            countArchivedMessageAsUnreadIfNeeded(attrs, chatbox);
                        }
                    });
                } catch (e) {}
            });

            _converse.api.listen.on('message', function (data) {
                var attrs = data && data.attrs;
                var chatbox = data && data.chatbox;
                if (!attrs || !chatbox || attrs.is_archived || attrs.is_forwarded || attrs.is_error) {
                    return;
                }
                if (!attrs.body && !attrs.message) {
                    return;
                }
                // Incoming and outgoing messages make their contact or room
                // the most recent item in its current sidebar section.
                noteChatActivity(chatbox.get('jid'), attrs.time || Date.now());
                // A live (non-archived) message from someone else, while
                // this tab is focused, is treated as "the user is looking
                // right now": the chatbox is force-shown even if it was
                // suppressed above, like a messenger surfacing an active
                // conversation instead of leaving it minimized behind a
                // badge count. Groupchat messages are deferred a tick so
                // Converse finishes its own handling of the stanza first.
                function openChatbox() {
                    try {
                        if (!document.hasFocus()) {
                            return;
                        }
                        var bareJid = _converse.session && _converse.session.get('bare_jid');
                        var fromBare = attrs.from && converse.env.Strophe.getBareJidFromJid(attrs.from);
                        if (bareJid && fromBare && fromBare.toLowerCase() === bareJid.toLowerCase()) {
                            return;
                        }
                        var jid = chatbox.get('jid');
                        if (!jid) {
                            return;
                        }
                        forceShowChatboxElement(jid);
                        if (typeof chatbox.save === 'function') {
                            chatbox.save({ hidden: false, minimized: false, closed: false });
                        } else if (typeof chatbox.set === 'function') {
                            chatbox.set({ hidden: false, minimized: false, closed: false });
                        }
                        if (typeof chatbox.maybeShow === 'function') {
                            chatbox.maybeShow(true);
                        } else if (typeof chatbox.trigger === 'function') {
                            chatbox.trigger('show');
                        }
                    } catch (e) {}
                }
                if (attrs.type === 'groupchat') {
                    Promise.resolve().then(openChatbox);
                } else {
                    openChatbox();
                }
            });

            /*
             * Unread badge: mirrors the total unread count (already tracked
             * by Converse per-chatbox, topped up above for backlog
             * messages) onto UI Converse does not update itself - the
             * document favicon (via the bundled Favico.js), the OS-level
             * Badging API (navigator.setAppBadge, for an installed PWA/tab),
             * and a numeric badge on Tiki's own floating toggle button.
             */
            whenConverseInitialized('unread badge', function () {
                var boxes = getConverseCollection('chatboxes', ['on'], 'unread badge');
                if (!boxes) return;
                var tikiFavico = null;

                function getTotalUnread() {
                    var chats = boxes.models;
                    return chats.reduce(function (acc, chat) {
                        var isGroupchat = chat.get('type') === 'chatroom';
                        var count = isGroupchat
                            ? (chat.get('num_unread_general') || 0)
                            : (chat.get('num_unread') || 0);
                        return acc + count;
                    }, 0);
                }

                function updateBrowserBadge(numUnread) {
                    try {
                        if (!tikiFavico && converse.env && typeof converse.env.Favico === 'function') {
                            tikiFavico = new converse.env.Favico({ type: 'circle', animation: 'pop' });
                        }
                        if (tikiFavico) tikiFavico.badge(numUnread);
                    } catch (e) {}
                    try {
                        if (navigator.setAppBadge) {
                            if (numUnread > 0) navigator.setAppBadge(numUnread).catch(function () {});
                            else if (navigator.clearAppBadge) navigator.clearAppBadge().catch(function () {});
                        }
                    } catch (e) {}
                }

                function updateChatButtonBadge() {
                    var numUnread = getTotalUnread();
                    updateBrowserBadge(numUnread);
                    var btn = document.querySelector(TIKI_SELECTORS.TOGGLE_CONTROLBOX);
                    if (!btn) {
                        return;
                    }
                    var badge = btn.querySelector('.tiki-chat-unread-badge');
                    if (numUnread > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'tiki-chat-unread-badge';
                            btn.appendChild(badge);
                        }
                        badge.textContent = numUnread > 99 ? '99+' : String(numUnread);
                    } else if (badge) {
                        badge.remove();
                    }
                }

                boxes.on('change:num_unread', function () { requestAnimationFrame(updateChatButtonBadge); });
                boxes.on('change:num_unread_general', function () { requestAnimationFrame(updateChatButtonBadge); });

                var observer = new MutationObserver(function () {
                    if (document.querySelector(TIKI_SELECTORS.TOGGLE_CONTROLBOX)) {
                        updateChatButtonBadge();
                        observer.disconnect();
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });

                updateChatButtonBadge();
            });

            // Tiki's floating chat-launcher button would otherwise sit on
            // top of an already-open chatbox; this hides it while any
            // chatbox flyout is visible and brings it back once none are,
            // reacting to any DOM change under #conversejs rather than to
            // a specific Converse event (none covers every way a flyout
            // can appear/disappear: open, close, minimize, view-mode switch).
            whenConverseInitialized('launcher visibility', function () {
                function isAnyChatboxOpen() {
                    var flyouts = document.querySelectorAll(TIKI_SELECTORS.CONVERSEJS_ROOT + ' .chatbox ' + TIKI_SELECTORS.BOX_FLYOUT + ', ' + TIKI_SELECTORS.CONVERSEJS_ROOT + ' #controlbox ' + TIKI_SELECTORS.BOX_FLYOUT);
                    for (var i = 0; i < flyouts.length; i++) {
                        if (flyouts[i].offsetParent !== null) {
                            return true;
                        }
                    }
                    return false;
                }

                function updateToggleButtonVisibility() {
                    var btn = document.querySelector(TIKI_SELECTORS.TOGGLE_CONTROLBOX);
                    if (!btn) {
                        return;
                    }
                    btn.classList.toggle('tiki-chat-launcher-hidden', isAnyChatboxOpen());
                }

                function scheduleToggleButtonUpdate() {
                    requestAnimationFrame(updateToggleButtonVisibility);
                }

                function observeConverseRoot() {
                    var root = document.getElementById('conversejs');
                    if (!root) {
                        return false;
                    }
                    var rootObserver = new MutationObserver(scheduleToggleButtonUpdate);
                    rootObserver.observe(root, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['class', 'style', 'hidden'],
                    });
                    updateToggleButtonVisibility();
                    return true;
                }

                if (!observeConverseRoot()) {
                    var waitForRootObserver = new MutationObserver(function () {
                        if (observeConverseRoot()) {
                            waitForRootObserver.disconnect();
                        }
                    });
                    waitForRootObserver.observe(document.body, { childList: true, subtree: true });
                }
            });

            /*
             * File attachment staging: pasting, dragging, or picking a file
             * does not upload it immediately. It is held in memory
             * (model._tiki_pending_files) with a thumbnail preview and a
             * remove control rendered above the message form, so the user
             * can attach several files, review and remove any of them,
             * then send them together with the typed message text on
             * submit (model.sendFiles(), Converse's own upload API).
             */
            whenConverseInitialized('attachment staging', function () {
                var html = converse.env.html;

                function isImageFile(file) {
                    return !!file && typeof file.type === 'string' && file.type.indexOf('image/') === 0;
                }

                function getPendingFiles(model) {
                    return model.get('_tiki_pending_files') || [];
                }

                function stagePendingFiles(model, fileList) {
                    var existing = getPendingFiles(model).slice();
                    Array.from(fileList).forEach(function (file) {
                        existing.push({
                            file: file,
                            name: file.name,
                            url: isImageFile(file) ? URL.createObjectURL(file) : null,
                        });
                    });
                    model.set('_tiki_pending_files', existing);
                }

                function clearPendingFiles(model) {
                    getPendingFiles(model).forEach(function (entry) {
                        if (entry.url) {
                            URL.revokeObjectURL(entry.url);
                        }
                    });
                    model.set('_tiki_pending_files', []);
                }

                function removePendingFile(model, index) {
                    var existing = getPendingFiles(model).slice();
                    var removed = existing.splice(index, 1)[0];
                    if (removed && removed.url) {
                        URL.revokeObjectURL(removed.url);
                    }
                    model.set('_tiki_pending_files', existing);
                }

                function renderPendingFilesPreview(view) {
                    var pending = getPendingFiles(view.model);
                    if (!pending.length) {
                        return '';
                    }
                    return html`
                        <div class="tiki-pending-files">
                            ${pending.map(function (entry, idx) {
                                return html`
                                    <div class="tiki-pending-file">
                                        ${entry.url
                                            ? html`<img class="tiki-pending-file-thumb" src="${entry.url}">`
                                            : html`<span class="tiki-pending-file-icon fa fa-file-o"></span>`}
                                        <span class="tiki-pending-file-name">${entry.name}</span>
                                        <button type="button" class="tiki-pending-file-remove" title="Remove"
                                            @click=${function (ev) {
                                                ev.preventDefault();
                                                ev.stopPropagation();
                                                removePendingFile(view.model, idx);
                                                view.requestUpdate();
                                            }}>&times;</button>
                                    </div>`;
                            })}
                        </div>`;
                }

                function patchRenderPreview(Ctor) {
                    if (!canPatchConverseMethod(Ctor, 'render', 'attachment preview') || Ctor.prototype.__tikiRenderPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiRenderPatched = true;
                    var originalRender = Ctor.prototype.render;
                    Ctor.prototype.render = function () {
                        var preview = renderPendingFilesPreview(this);
                        var original = originalRender.call(this);
                        return preview ? html`${preview}${original}` : original;
                    };
                }

                function patchMessageFormBehavior(Ctor) {
                    if (!canPatchConverseMethod(Ctor, 'onFormSubmitted', 'attachment submission') || Ctor.prototype.__tikiBehaviorPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiBehaviorPatched = true;

                    Ctor.prototype.onPaste = function (ev) {
                        if (ev.clipboardData.files.length !== 0) {
                            ev.stopPropagation();
                            ev.preventDefault();
                            stagePendingFiles(this.model, ev.clipboardData.files);
                            this.requestUpdate();
                            return;
                        }
                        var textarea = this.querySelector('.chat-textarea');
                        if (!textarea) {
                            return;
                        }
                        ev.preventDefault();
                        ev.stopPropagation();
                        var draft = textarea.value || '';
                        var pasted_text = ev.clipboardData.getData('text/plain');
                        var cursor_pos = textarea.selectionStart;
                        var before = draft.substring(0, cursor_pos);
                        var after = draft.substring(textarea.selectionEnd);
                        var separator = (before.endsWith(' ') || before.length === 0) ? '' : ' ';
                        var end_separator = (after.startsWith(' ') || after.length === 0) ? '' : ' ';
                        this.model.save({ draft: before + separator + pasted_text + end_separator + after });
                        var new_pos = before.length + separator.length + pasted_text.length + end_separator.length;
                        setTimeout(function () { textarea.setSelectionRange(new_pos, new_pos); }, 0);
                    };

                    Ctor.prototype.onDrop = function (ev) {
                        if (ev.dataTransfer.files.length === 0) {
                            return;
                        }
                        ev.preventDefault();
                        stagePendingFiles(this.model, ev.dataTransfer.files);
                        this.requestUpdate();
                    };

                    var originalOnFormSubmitted = Ctor.prototype.onFormSubmitted;
                    Ctor.prototype.onFormSubmitted = async function (ev) {
                        var pending = getPendingFiles(this.model);
                        if (!pending.length) {
                            return originalOnFormSubmitted.call(this, ev);
                        }
                        var files = pending.map(function (entry) { return entry.file; });
                        clearPendingFiles(this.model);
                        this.requestUpdate();
                        await originalOnFormSubmitted.call(this, ev);
                        await this.model.sendFiles(files);
                    };
                }

                function patchToolbar(Ctor) {
                    if (!canPatchConverseMethod(Ctor, 'onFileSelection', 'attachment selection') || Ctor.prototype.__tikiBehaviorPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiBehaviorPatched = true;
                    Ctor.prototype.onFileSelection = function (ev) {
                        var input = ev.target;
                        if (input.files.length) {
                            stagePendingFiles(this.model, input.files);
                            var form = this.closest(TIKI_SELECTORS.MESSAGE_FORMS);
                            if (form) {
                                form.requestUpdate();
                            }
                        }
                        input.value = '';
                    };
                }

                var MessageForm = customElements.get(TIKI_SELECTORS.MESSAGE_FORM);
                var MUCMessageForm = customElements.get(TIKI_SELECTORS.MUC_MESSAGE_FORM);
                var ChatToolbar = customElements.get(TIKI_SELECTORS.CHAT_TOOLBAR);

                patchMessageFormBehavior(MessageForm);
                patchRenderPreview(MessageForm);
                patchRenderPreview(MUCMessageForm);
                patchToolbar(ChatToolbar);
            });

            // Converse's own Bootstrap dropdown toggle can end up
            // double-bound (once by Bootstrap's own data-api, once by
            // Converse's Lit component) inside Tiki's page chrome, opening
            // and immediately reclosing the menu. Handling the click here
            // in the capture phase, stopping it from reaching Bootstrap's
            // own handler, and calling the component's own dropdown
            // controller directly makes it open exactly once.
            document.addEventListener('click', function (ev) {
                var toggle = ev.target.closest && ev.target.closest(TIKI_SELECTORS.DROPDOWN_TOGGLE_IN_ROOT);
                if (!toggle) {
                    return;
                }
                var host = toggle.closest(TIKI_SELECTORS.DROPDOWN_HOSTS);
                if (!host || !host.dropdown) {
                    return;
                }
                ev.stopPropagation();
                ev.stopImmediatePropagation();
                host.dropdown.toggle();
            }, true);

            // Lets a user with both a Tiki-internal and an external XMPP
            // identity switch which one Converse authenticates as. The
            // choice has to survive a full page reload (the connection is
            // torn down and rebuilt from scratch), hence a cookie
            // (tiki_xmpp_external) rather than in-memory state; PHP reads
            // it back in ConverseJS::getUserAuthOptions() on the next load.
            document.addEventListener('click', function (ev) {
                var identityButton = ev.target.closest && ev.target.closest('[data-tiki-xmpp-identity]');
                if (!identityButton) {
                    return;
                }
                ev.preventDefault();
                var identity = identityButton.getAttribute('data-tiki-xmpp-identity');
                var cookie = 'tiki_xmpp_external=' + (identity === 'external' ? '1' : '');
                cookie += '; path=/; max-age=' + (identity === 'external' ? (60 * 60 * 24 * 30) : '0');
                cookie += '; SameSite=Lax';
                if (window.location.protocol === 'https:') {
                    cookie += '; Secure';
                }
                document.cookie = cookie;
                window.location.reload();
            }, true);

            // Tiki's floating chat-launcher button behaves differently per
            // session: an authenticated user just gets the controlbox
            // (Converse's own contact/room list) opened. An anonymous
            // guest has no controlbox to speak of, so the button instead
            // opens whichever of anon_room/dm_target is configured, or -
            // when both are - shows the destination-choice dialog below so
            // the guest picks one.
            document.addEventListener('click', function (ev) {
                var toggle = ev.target.closest && ev.target.closest(TIKI_SELECTORS.TOGGLE_CONTROLBOX);
                if (!toggle) {
                    return;
                }
                var settings = _converse.tikiSettings;
                var isAnonymous = settings && settings.anonymous === 'y';

                function showChatbox(chatbox) {
                    if (!chatbox) {
                        return;
                    }
                    forceShowChatboxElement(chatbox.get('jid'));
                    if (typeof chatbox.save === 'function') {
                        chatbox.save({ hidden: false, minimized: false, closed: false });
                    } else if (typeof chatbox.set === 'function') {
                        chatbox.set({ hidden: false, minimized: false, closed: false });
                    }
                    if (typeof chatbox.maybeShow === 'function') {
                        chatbox.maybeShow(true);
                    } else if (typeof chatbox.trigger === 'function') {
                        chatbox.trigger('show');
                    }
                }

                function showAnonymousDestinationChoice() {
                    var oldChoice = document.querySelector('.tiki-chat-destination-choice');
                    if (oldChoice) oldChoice.remove();
                    var backdrop = document.createElement('div');
                    backdrop.className = 'tiki-chat-destination-choice';
                    backdrop.innerHTML = '<div class="tiki-chat-destination-card" role="dialog" aria-modal="true" aria-labelledby="tiki-chat-destination-title">' +
                        '<button type="button" class="tiki-chat-destination-close">&times;</button>' +
                        '<h2 id="tiki-chat-destination-title"></h2>' +
                        '<p></p>' +
                        '<button type="button" class="btn btn-primary tiki-chat-destination-community"></button>' +
                        '<button type="button" class="btn btn-outline-primary tiki-chat-destination-private"></button>' +
                        '</div>';
                    backdrop.querySelector('.tiki-chat-destination-close').setAttribute('aria-label', settings.destination_close_label);
                    backdrop.querySelector('#tiki-chat-destination-title').textContent = settings.destination_title;
                    backdrop.querySelector('.tiki-chat-destination-card > p').textContent = settings.destination_description;
                    backdrop.querySelector('.tiki-chat-destination-community').textContent = settings.destination_community_label;
                    backdrop.querySelector('.tiki-chat-destination-private').textContent = settings.destination_private_label;
                    function closeChoice() { backdrop.remove(); }
                    backdrop.addEventListener('click', function (event) {
                        if (event.target === backdrop || event.target.closest('.tiki-chat-destination-close')) closeChoice();
                    });
                    backdrop.querySelector('.tiki-chat-destination-community').addEventListener('click', function () {
                        closeChoice();
                        Promise.resolve(_converse.api.rooms.create(settings.anon_room)).then(showChatbox).catch(function () {});
                    });
                    backdrop.querySelector('.tiki-chat-destination-private').addEventListener('click', function () {
                        closeChoice();
                        Promise.resolve(_converse.api.chats.open(settings.dm_target, {}, true)).then(showChatbox).catch(function () {});
                    });
                    document.body.appendChild(backdrop);
                    backdrop.querySelector('.tiki-chat-destination-community').focus();
                }

                // Permission prompts and audio playback must be initiated from a
                // real user gesture. This must run for authenticated users too.
                if (window.Notification && Notification.permission === 'default') {
                    try {
                        var permissionRequest = Notification.requestPermission();
                        if (permissionRequest && typeof permissionRequest.catch === 'function') permissionRequest.catch(function () {});
                    } catch (e) {}
                }

                if (!isAnonymous) {
                    if (openExistingControlbox()) {
                        ev.preventDefault();
                        ev.stopPropagation();
                    }
                    return;
                }

                if (!settings.dm_target && !settings.anon_room) {
                    return;
                }

                ev.preventDefault();
                ev.stopPropagation();

                if (settings.dm_target && settings.anon_room) {
                    showAnonymousDestinationChoice();
                    return;
                }

                if (settings.dm_target) {
                    Promise.resolve(_converse.api.chats.open(settings.dm_target, {}, true))
                        .then(showChatbox)
                        .catch(function () {});
                    return;
                }

                Promise.resolve(_converse.api.rooms.create(settings.anon_room))
                    .then(showChatbox)
                    .catch(function () {});
            }, true);

            // ConverseJS.php sets discover_connection_methods: false (an
            // XEP-0156 HTTP lookup Tiki does not need, since it already
            // knows its own BOSH/WebSocket URLs), which also skips
            // Strophe's own protocol auto-detection; this replaces it with
            // the same selection logic Strophe would otherwise have run,
            // driven only by the service URL/protocol Tiki supplied.
            converse.env.Strophe.Connection.prototype.setProtocol = function() {
                const proto = this.options.protocol || "";
                if (this.options.worker) {
                    this._proto = new Strophe.WorkerWebsocket(this);
                } else if (
                    this.service.indexOf("ws:") === 0 ||
                    this.service.indexOf("wss:") === 0 ||
                    proto.indexOf("ws") === 0
                ) {
                    this._proto = new Strophe.Websocket(this);
                } else {
                    this._proto = new Strophe.Bosh(this);
                }
            };

            // A BOSH/prebind session Converse cannot resume (expired,
            // server restart, ...) leaves the widget stuck with a dead
            // connection and no obvious explanation; surface it as a
            // visible Tiki error and hide the now-unusable widget instead.
            _converse.api.listen.on("noResumeableSession", function (xhr) {
                error(tr("XMPP Module error") + ": " + xhr.statusText);
                $("#conversejs").fadeOut("fast");
            });

            // Converse's default MUC occupant order is not presence-aware.
            // Sorting online-and-available occupants before away/dnd/
            // offline ones matches what users expect from a member list.
            _converse.api.listen.on('chatRoomViewInitialized', function (view) {
                if (view.model && view.model.occupants) {
                    removeUnavailableGuestOccupants(view.model);
                    if (!view.model.occupants.__tikiPruneUnavailableGuests) {
                        view.model.occupants.__tikiPruneUnavailableGuests = true;
                        view.model.occupants.on('add change:presence change:show change:type change:presence_type change:jid change:nick', function (occupant) {
                            pruneUnavailableGuestOccupant(view.model, occupant);
                        });
                    }
                    view.model.occupants.comparator = function (a, b) {
                        const order = { 'chat': 1, 'available': 1, 'away': 2, 'xa': 3, 'dnd': 4, 'offline': 5 };
                        const presA = a.get('show') || 'offline';
                        const presB = b.get('show') || 'offline';
                        return order[presA] - order[presB];
                    };
                }
            });

            function suppressAutomaticallyOpenedChatbox(box, jid) {
                const chatJid = jid || box.get('jid');
                if (!chatJid || explicitlyOpenedChatJids.has(chatJid)) return;
                // Hidden/minimized does not clear unread counters or messages. It only
                // prevents restored rooms from covering the page before the user picks one.
                box.set({ hidden: true, minimized: true });
                forceHideChatboxElement(chatJid);
            }

            // Restoring a session (bfcache navigation, reconnect) can bring
            // back every chatbox/room the user ever had open, which would
            // bury the one specific room/DM a tiki-xmpp page in embedded or
            // fullscreen view mode exists to show. This suppresses every
            // restored chatbox except that one (or all of them, off that
            // page). It is deliberately re-run (see the 'connected' and
            // 'pageshow' listeners below) rather than run once, since
            // Converse can restore chatboxes at points this file cannot
            // otherwise hook into.
            async function tikiStrictHideEverythingExceptThisPage() {
                const onXmppPage = _converse.api.settings.get('on_xmpp_page') === 'y';
                const viewMode = _converse.api.settings.get('view_mode');
                const pageShowsRoomDirectly = onXmppPage && (viewMode === 'embedded' || viewMode === 'fullscreen');
                const keepJids = new Set();
                if (pageShowsRoomDirectly) {
                    const currentRoom = _converse.api.settings.get('current_room');
                    const dmTarget = _converse.tikiSettings && _converse.tikiSettings.dm_target;
                    if (currentRoom) {
                        keepJids.add(currentRoom);
                    }
                    if (dmTarget) {
                        keepJids.add(dmTarget);
                    }
                }
                try {
                    const allBoxes = await _converse.api.chatboxes.get();
                    (allBoxes || []).forEach((box) => {
                        const id = box.get('id');
                        const boxId = box.get('box_id');
                        const type = box.get('type');
                        if (
                            id === 'controlbox' ||
                            boxId === 'controlbox' ||
                            type === 'controlbox' ||
                            typeof box.getDisplayName !== 'function'
                        ) {
                            return;
                        }
                        if (keepJids.has(box.get('jid'))) {
                            return;
                        }
                        suppressAutomaticallyOpenedChatbox(box, box.get('jid'));
                    });
                } catch (e) {}
            }

            window.addEventListener('pageshow', function (ev) {
                if (ev.persisted && _converse && _converse.api) {
                    tikiStrictHideEverythingExceptThisPage();
                }
            });

            /*
             * Room/DM restoration on every (re)connect.
             *
             * Converse's own auto_join_rooms/auto_join_private_chats only
             * cover a fixed, PHP-rendered list; Tiki's room membership can
             * change between page loads (rooms an authenticated user was
             * added/removed from) and differs entirely by session kind, so
             * this listener re-derives what should be open every time the
             * connection (re)establishes, branching on the same session
             * kinds used throughout this file:
             *   - anonymous guest (tikiAnon === 'y'): join anon_room and/or
             *     open dm_target, per the destination the guest is in.
             *   - authenticated, using an external XMPP account
             *     (using_external === 'y'): sync via /tiki-xmpp-sync.php,
             *     then join current_room plus whatever /tiki-xmpp-rooms.php
             *     reports as this account's rooms/bookmarks.
             *   - authenticated, Tiki-internal JID: same sync, then close
             *     chatboxes for rooms the user is no longer authorized for
             *     and open the authorized rooms/DM partners
             *     /tiki-xmpp-rooms.php reports.
             * Every room/DM opened this way goes through
             * suppressAutomaticallyOpenedChatbox() unless the current view
             * mode should always show it (alwaysShow), so restoring ten
             * rooms cannot bury the page under ten open chatboxes.
             */
            _converse.api.listen.on('connected', async function () {
                const dmTarget = _converse.tikiSettings.dm_target;
                const anonRoom = _converse.tikiSettings.anon_room;
                const tikiAnon = _converse.tikiSettings.anonymous;
                const autoOpen = _converse.api.settings.get('auto_open') === 'y';
                const anonymousAutoOpen = _converse.api.settings.get('anonymous_auto_open') === 'y';
                const viewMode = _converse.api.settings.get('view_mode');
                const alwaysShow = viewMode === 'embedded' || viewMode === 'fullscreen';
                const onXmppPage = _converse.api.settings.get('on_xmpp_page') === 'y';
                const configuredPageRoom = _converse.api.settings.get('current_room');
                const fullscreenSelection = viewMode === 'fullscreen' && !onXmppPage
                    ? takeFullscreenChatSelection()
                    : null;
                await tikiStrictHideEverythingExceptThisPage();

                if (fullscreenSelection) {
                    try {
                        await _converse.api.waitUntil('chatBoxesInitialized');
                        let selectedChat = await _converse.api.chatboxes.get(fullscreenSelection.jid);
                        if (!selectedChat && fullscreenSelection.type === 'chatroom') {
                            selectedChat = await _converse.api.rooms.create(fullscreenSelection.jid);
                        } else if (!selectedChat) {
                            selectedChat = await _converse.api.chats.get(fullscreenSelection.jid, {}, true);
                        }
                        if (selectedChat) {
                            forceShowChatboxElement(fullscreenSelection.jid);
                            selectedChat.set({ hidden: false, minimized: false, closed: false });
                            if (typeof selectedChat.maybeShow === 'function') {
                                selectedChat.maybeShow(true);
                            } else {
                                selectedChat.trigger('show');
                            }
                        }
                    } catch (e) {}
                }

                if (onXmppPage && !alwaysShow) {
                    try {
                        const launcher = document.querySelector(TIKI_SELECTORS.TOGGLE_CONTROLBOX);
                        if (launcher) launcher.classList.add('tiki-chat-launcher-hidden');
                        openExistingControlbox();
                    } catch (e) {}
                }

                if (tikiAnon === 'y') {
                    const anonymousRoom = (onXmppPage && configuredPageRoom) ? configuredPageRoom : anonRoom;
                    if (anonymousRoom) {
                        try {
                            await _converse.api.waitUntil('chatBoxesInitialized');
                            const room = await _converse.api.rooms.create(anonymousRoom);
                            if (alwaysShow) {
                                forceShowChatboxElement(anonymousRoom);
                                room.set({ hidden: false, minimized: false, closed: false });
                                room.trigger('show');
                            } else suppressAutomaticallyOpenedChatbox(room, anonymousRoom);
                        } catch (e) {}
                    }
                    if (dmTarget && !anonymousAutoOpen) {
                        try {
                            const existedDm = !!(await _converse.api.chatboxes.get(dmTarget));
                            const dmChat = await _converse.api.chats.get(dmTarget, {}, true);
                            if (dmChat && !existedDm && !alwaysShow) {
                                suppressAutomaticallyOpenedChatbox(dmChat, dmTarget);
                            }
                        } catch (e) {}
                    }
                    return;
                }

                if (_converse.api.settings.get('using_external') === 'y') {
                    const currentRoom = _converse.api.settings.get('current_room');

                    try {
                        await fetch((window.location.origin || '') + '/tiki-xmpp-sync.php', {
                            credentials: 'include',
                            keepalive: true,
                        });
                    } catch (e) {}

                    if (currentRoom) {
                        try {
                            const room = await _converse.api.rooms.create(currentRoom);
                            if (alwaysShow) {
                                forceShowChatboxElement(currentRoom);
                                room.set({ hidden: false, minimized: false, closed: false });
                                room.trigger('show');
                            } else suppressAutomaticallyOpenedChatbox(room, currentRoom);
                        } catch (e) {}
                    }

                    try {
                        const controller = new AbortController();
                        const timeout = setTimeout(() => controller.abort(), 3000);
                        const response = await fetch((window.location.origin || '') + '/tiki-xmpp-rooms.php', {
                            credentials: 'include',
                            signal: controller.signal,
                        });
                        clearTimeout(timeout);
                        const roomData = response.ok ? await response.json() : [];
                        const mappedRooms = Array.isArray(roomData) ? roomData : (roomData.rooms || []);
                        for (const mappedRoom of mappedRooms) {
                            if (!mappedRoom || mappedRoom === currentRoom) {
                                continue;
                            }
                            const room = await _converse.api.rooms.create(mappedRoom);
                            if (!alwaysShow) suppressAutomaticallyOpenedChatbox(room, mappedRoom);
                        }
                    } catch (e) {}

                    if (_converse.api.settings.get('on_xmpp_page') === 'y' && _converse.api.settings.get('allow_bookmarks') !== false) {
                        try {
                            await _converse.api.waitUntil('bookmarksInitialized');
                            var bookmarks = (_converse.state.bookmarks && _converse.state.bookmarks.models) || [];
                            var bookmarkedJids = bookmarks.map(function (b) { return b.get('jid'); })
                                .filter(function (jid) { return jid && jid !== currentRoom; });
                            var allRooms = currentRoom ? [currentRoom].concat(bookmarkedJids) : bookmarkedJids;
                            if (allRooms.length <= 1 && _converse.api.controlbox && typeof _converse.api.controlbox.close === 'function') {
                                await _converse.api.controlbox.close();
                            }
                        } catch (e) {}
                    }

                    return;
                }

                try {
                    await fetch((window.location.origin || '') + '/tiki-xmpp-sync.php', {
                        credentials: 'include',
                        keepalive: true,
                    });
                } catch (e) {}

                let data = [];
                try {
                    const controller = new AbortController();
                    const timeout = setTimeout(() => controller.abort(), 3000);
                    const res = await fetch((window.location.origin || '') + '/tiki-xmpp-rooms.php', {
                        credentials: 'include',
                        signal: controller.signal,
                    });
                    clearTimeout(timeout);
                    if (res.ok) {
                        data = await res.json();
                    }
                } catch (e) {
                    data = [];
                }

                const rooms = Array.isArray(data) ? data : (data.rooms || []);
                const providedNick = (data && typeof data.nickname === 'string') ? data.nickname.trim() : '';
                const currentRoom = _converse.api.settings.get('current_room');

                try {
                    const authorizedSet = new Set(rooms);
                    if (currentRoom) {
                        authorizedSet.add(currentRoom);
                    }
                    const existingChatboxes = await _converse.api.chatboxes.get();
                    const staleRooms = (existingChatboxes || []).filter((c) =>
                        c.get('type') === 'chatroom' && c.get('jid') && !authorizedSet.has(c.get('jid'))
                    );
                    await Promise.all(staleRooms.map((c) => c.close()));
                } catch (e) {}

                const dmPartners = Array.isArray(data && data.dm_partners) ? data.dm_partners : [];
                for (const partnerJid of dmPartners) {
                    if (partnerJid === dmTarget) {
                        continue;
                    }
                    try {
                        const existed = !!(await _converse.api.chatboxes.get(partnerJid));
                        if (existed) {
                            continue;
                        }
                        const chat = await _converse.api.chats.get(partnerJid, {}, true);
                        if (chat) {
                            suppressAutomaticallyOpenedChatbox(chat, partnerJid);
                        }
                    } catch (e) {}
                }

                const attrs = {};
                if (providedNick) {
                    attrs.nick = providedNick;
                }

                if (onXmppPage && currentRoom) {
                    try {
                        const room = await _converse.api.rooms.create(currentRoom, attrs);
                        if (alwaysShow) {
                            forceShowChatboxElement(currentRoom);
                            room.set({ hidden: false, minimized: false, closed: false });
                            room.trigger('show');
                        } else suppressAutomaticallyOpenedChatbox(room, currentRoom);
                    } catch (e) {}
                }

                for (const room of rooms) {
                    if (room === currentRoom) {
                        continue;
                    }
                    try {
                        const chatRoom = await _converse.api.rooms.create(room, attrs);
                        if (!alwaysShow) {
                            suppressAutomaticallyOpenedChatbox(chatRoom, room);
                        }
                    } catch (e) {}
                }

                if (dmTarget && autoOpen && onXmppPage && alwaysShow) {
                    try {
                        const chat = await _converse.api.chats.get(dmTarget, {}, true);
                        if (chat && !hasAutoOpenedDm(dmTarget)) {
                            forceShowChatboxElement(dmTarget);
                            chat.set('hidden', false);
                            chat.set('minimized', false);
                            chat.trigger('show');
                            if (chat.view && typeof chat.view.show === 'function') {
                                chat.view.show();
                            }
                            markDmAutoOpened(dmTarget);
                        }
                    } catch (e) {}
                }
            });

            var supportPresenceListenersBound = false;
            var SUPPORT_ONLINE_WINDOW_MS = 45000;

            function setupSupportPresenceTracking() {
                var tikiSettings = _converse.tikiSettings;
                if (!tikiSettings || tikiSettings.anonymous !== 'y' || !tikiSettings.dm_target) {
                    return;
                }
                var supportJid = tikiSettings.dm_target;

                if (supportPresenceListenersBound) {
                    return;
                }
                supportPresenceListenersBound = true;

                function formatLastSeen(msAgo) {
                    var minutes = Math.round(msAgo / 60000);
                    if (minutes < 1) return 'Last seen just now';
                    if (minutes < 60) return 'Last seen ' + minutes + ' min ago';
                    var hours = Math.round(minutes / 60);
                    if (hours < 24) return 'Last seen ' + hours + ' h ago';
                    var days = Math.round(hours / 24);
                    return 'Last seen ' + days + ' d ago';
                }

                function renderSupportStatus(activityModel) {
                    var el = findChatboxElement(supportJid);
                    if (!el) return;
                    var titleEl = el.querySelector(TIKI_SELECTORS.CHATBOX_TITLE_TEXT);
                    if (!titleEl) return;
                    var lastActivityAt = activityModel.get('tiki_last_activity_at');
                    var serverLastSeenAt = activityModel.get('tiki_server_last_seen_at');
                    var referenceAt = lastActivityAt || serverLastSeenAt;
                    var msSinceActivity = referenceAt ? (Date.now() - referenceAt) : Infinity;
                    var isOnline = activityModel.get('tiki_xmpp_online') === true ||
                        (activityModel.get('tiki_activity_online_until') || 0) > Date.now();
                    var badge = titleEl.querySelector('.tiki-support-status');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'tiki-support-status';
                        badge.style.fontWeight = 'normal';
                        badge.style.fontSize = '0.8em';
                        badge.style.marginTop = '2px';
                        badge.style.lineHeight = '1.2';
                        badge.style.whiteSpace = 'nowrap';
                        badge.style.overflow = 'hidden';
                        badge.style.textOverflow = 'ellipsis';
                        badge.style.maxWidth = '100%';
                        titleEl.appendChild(badge);
                    }
                    badge.style.color = isOnline ? '#2ecc71' : '#999';
                    badge.textContent = isOnline
                        ? 'Online'
                        : (referenceAt ? formatLastSeen(msSinceActivity) : 'Offline');
                }

                function queryServerLastSeen(activityModel) {
                    if (activityModel.get('tiki_last_seen_query_pending')) return;
                    activityModel.set('tiki_last_seen_query_pending', true);
                    try {
                        var Strophe = converse.env.Strophe;
                        var iq = new Strophe.Builder('iq', { to: supportJid, type: 'get' }).c('query', { xmlns: 'jabber:iq:last' });
                        _converse.api.sendIQ(iq, 10000, false).then(function (stanza) {
                            if (!stanza || stanza.getAttribute('type') === 'error') {
                                return;
                            }
                            var queryEl = stanza.querySelector('query');
                            var secondsAttr = queryEl && queryEl.getAttribute('seconds');
                            var seconds = secondsAttr !== null ? parseInt(secondsAttr, 10) : NaN;
                            if (isNaN(seconds)) {
                                return;
                            }
                            activityModel.set('tiki_server_last_seen_at', Date.now() - (seconds * 1000));
                            renderSupportStatus(activityModel);
                        }).catch(function () {}).finally(function () {
                            activityModel.set('tiki_last_seen_query_pending', false);
                        });
                    } catch (e) {
                        activityModel.set('tiki_last_seen_query_pending', false);
                    }
                }

                function lastActivityStorageKey() {
                    return 'tiki_xmpp_last_activity:' + supportJid;
                }

                function loadStoredLastActivity() {
                    var stored = TikiConverseStorage.get('localStorage', lastActivityStorageKey());
                    return stored ? parseInt(stored, 10) : null;
                }

                function storeLastActivity(timestamp) {
                    TikiConverseStorage.set('localStorage', lastActivityStorageKey(), String(timestamp));
                }

                Promise.resolve(_converse.api.chats.get(supportJid, {}, true)).then(function (chatbox) {
                    if (!chatbox) return;

                    var presences = getConverseCollection('presences', ['get', 'create'], 'support presence');
                    if (!presences) {
                        supportPresenceListenersBound = false;
                        return;
                    }
                    var activityModel = presences.get(supportJid) || presences.create({ jid: supportJid });

                    // Only concrete presence resources or fresh chat activity
                    // may promote the support account to online.
                    activityModel.set('tiki_xmpp_online', false);
                    activityModel.set('tiki_activity_online_until', 0);

                    var storedActivity = loadStoredLastActivity();
                    if (storedActivity && !activityModel.get('tiki_last_activity_at')) {
                        activityModel.set('tiki_last_activity_at', storedActivity);
                    }

                    function markActive() {
                        var now = Date.now();
                        activityModel.set('tiki_last_activity_at', now);
                        activityModel.set('tiki_activity_online_until', now + SUPPORT_ONLINE_WINDOW_MS);
                        storeLastActivity(now);
                        renderSupportStatus(activityModel);
                    }

                    // Presence is aggregated across every XMPP resource, so an
                    // administrator connected through Tiki, Gajim, Monal, or
                    // another client is treated the same way. Keep a resource
                    // set because one phone going offline must not hide a still
                    // connected desktop client.
                    var onlineResources = new Set();
                    var connection = _converse.api.connection.get();
                    if (connection && typeof connection.addHandler === 'function') {
                        connection.addHandler(function (stanza) {
                            try {
                                var from = stanza.getAttribute('from') || '';
                                var fromBare = converse.env.Strophe.getBareJidFromJid(from);
                                if (!fromBare || fromBare.toLowerCase() !== supportJid.toLowerCase()) return true;
                                var resource = converse.env.Strophe.getResourceFromJid(from) || '__bare__';
                                var type = stanza.getAttribute('type') || 'available';
                                if (type === 'unavailable' || type === 'error') {
                                    if (resource === '__bare__') onlineResources.clear();
                                    else onlineResources.delete(resource);
                                } else if (type === 'available') {
                                    onlineResources.add(resource);
                                } else {
                                    // Subscription acknowledgements and probes
                                    // are presence protocol traffic, not proof
                                    // that this user has an online resource.
                                    return true;
                                }
                                var online = onlineResources.size > 0;
                                activityModel.set('tiki_xmpp_online', online);
                                if (online) markActive();
                                else {
                                    activityModel.set('tiki_activity_online_until', 0);
                                    renderSupportStatus(activityModel);
                                }
                            } catch (e) {}
                            return true;
                        }, null, 'presence');
                    }

                    function syncAggregatedPresence() {
                        var show = activityModel.get('show');
                        if (!show) return;
                        var online = show !== 'offline' && show !== 'unavailable';
                        activityModel.set('tiki_xmpp_online', online);
                        if (online) markActive();
                        else renderSupportStatus(activityModel);
                    }
                    activityModel.on('change:show', syncAggregatedPresence);

                    // Do not read `show` immediately from a model that may
                    // have just been created locally: its default is not proof
                    // that a remote client is connected. Existing resources,
                    // however, are concrete sessions already seen by Converse.
                    if (activityModel.resources) {
                        var syncPresenceResources = function () {
                            var count = activityModel.resources.length || 0;
                            activityModel.set('tiki_xmpp_online', count > 0);
                            if (count > 0) markActive();
                            else {
                                activityModel.set('tiki_activity_online_until', 0);
                                renderSupportStatus(activityModel);
                            }
                        };
                        activityModel.resources.on('add remove reset', syncPresenceResources);
                        if (activityModel.resources.length) syncPresenceResources();
                    }

                    if (chatbox.notifications) {
                        chatbox.notifications.on('change', function () {
                            var composing = chatbox.notifications.get('composing');
                            var chatState = chatbox.notifications.get('chat_state');
                            if ((composing && composing.length) || chatState === 'composing' || chatState === 'active') {
                                markActive();
                            }
                        });
                    }

                    if (chatbox.messages) {
                        chatbox.messages.on('add', function (message) {
                            try {
                                if (!message || typeof message.get !== 'function') return;
                                var sender = message.get('sender');
                                var isArchived = message.get('is_archived') || message.get('is_forwarded');
                                var time = message.get('time');
                                var msgAgeMs = time ? (Date.now() - new Date(time).getTime()) : Infinity;
                                var isFresh = msgAgeMs >= 0 && msgAgeMs < 10000;
                                if (sender !== 'me' && !isArchived && isFresh) {
                                    markActive();
                                }
                            } catch (e) {}
                        });
                    }

                    _converse.api.listen.on('message', function (data) {
                        try {
                            var attrs = data && data.attrs;
                            if (!attrs || attrs.is_archived || attrs.is_forwarded) return;
                            var fromBare = attrs.from && converse.env.Strophe.getBareJidFromJid(attrs.from);
                            if (fromBare && fromBare.toLowerCase() === supportJid.toLowerCase()) {
                                markActive();
                            }
                        } catch (e) {}
                    });

                    renderSupportStatus(activityModel);
                    queryServerLastSeen(activityModel);
                    [0, 50, 150, 350, 700, 1500, 3000, 5000].forEach(function (delay) {
                        setTimeout(function () { renderSupportStatus(activityModel); }, delay);
                    });
                    setInterval(function () {
                        renderSupportStatus(activityModel);
                        queryServerLastSeen(activityModel);
                    }, 60000);
                }).catch(function () {});
            }

            _converse.api.listen.on('connected', setupSupportPresenceTracking);
            _converse.api.listen.on('reconnected', setupSupportPresenceTracking);
        },

        /*
         * Converse plugin overrides.
         *
         * These names are Converse model/view extension points. Each override
         * handles restored state or Tiki-specific guest/MUC behavior, then
         * delegates to this.__super__ when default behavior should continue.
         */
        "overrides": {
            "ChatBoxes": {
                // A chatbox restored from IndexedDB can be missing/corrupt
                // (partial write, or a type this view mode's registry does
                // not register at all - e.g. a "controlbox" model
                // restored on an embedded/fullscreen page). Converse would
                // otherwise throw constructing it; rejecting it here with a
                // validationError is Backbone's own documented way to make
                // model creation a no-op instead.
                "createModel": function (attrs, options) {
                    if (!attrs || typeof attrs !== 'object' || !attrs.type) {
                        return { validationError: 'tiki: ignoring an invalid cached chatbox' };
                    }
                    if (!_converse.api.chatboxes.registry.get(attrs.type)) {
                        return { validationError: 'tiki: chatbox type "' + attrs.type + '" is not available in this view mode' };
                    }
                    return this.__super__.createModel.apply(this, arguments);
                },
            },
            "RosterContacts": {
                // Same rationale as ChatBoxes.createModel above: a roster
                // contact restored without a JID is unusable and would
                // otherwise throw deeper in Converse's own rendering.
                "createModel": function (attrs, options) {
                    if (!attrs || typeof attrs !== 'object' || !attrs.jid) {
                        return { validationError: 'tiki: ignoring an invalid roster contact without a JID' };
                    }
                    return this.__super__.createModel.apply(this, arguments);
                },
                // Anonymous/guest JIDs are one-time, throwaway identities
                // (see clearTikiGuestSessionStorage above); accepting a
                // subscription request from or as one would create a
                // roster entry for an identity that will never come back
                // and that the guest never chose to add. Auto-declining
                // keeps the roster meaningful for both sides.
                "handleIncomingSubscription": function (presence) {
                    var jid = presence.getAttribute('from');
                    var isAnonymousSession = _converse.tikiSettings && _converse.tikiSettings.anonymous === 'y';
                    var isGuestJid = isGeneratedGuestJid(jid);
                    if (isAnonymousSession || isGuestJid) {
                        try {
                            var Strophe = converse.env.Strophe;
                            var stanza = new Strophe.Builder('presence', { to: jid, type: 'unsubscribed' });
                            _converse.api.send(stanza);
                        } catch (e) {}
                        return;
                    }
                    return this.__super__.handleIncomingSubscription.apply(this, arguments);
                },
            },
            "Bookmarks": {
                // Converse's own openBookmarkedRoom does more than this
                // plugin wants when using_external is 'y' (see the
                // bookmarks/auto_join_bookmarks toggle above); this minimal
                // version only (re)joins an autojoin bookmark, without the
                // extra Converse behavior this integration does not need.
                "openBookmarkedRoom": async function (bookmark) {
                    if (bookmark.get('autojoin')) {
                        const room = await _converse.api.rooms.create(bookmark.get('jid'), bookmark.get('nick'));
                    }
                    return bookmark;
                }
            },
            "ChatRoom": {
                // On the first resumed check for this room model, report not
                // joined so restoration re-establishes membership without the
                // usual self-ping. The marker lasts for this model instance,
                // not for each reconnect. A missing nickname also returns false.
                "isJoined": async function () {
                    var sessionResumed = _converse.session && _converse.session.get('smacks_resumed');
                    var skipResumedPing = sessionResumed && !this.__tikiSkippedResumedPing;
                    if (skipResumedPing) {
                        this.__tikiSkippedResumedPing = true;
                    }
                    if (!this.get('nick') || skipResumedPing) {
                        return false;
                    }
                    return this.__super__.isJoined.apply(this, arguments);
                },
                "getDiscoInfo": async function () {
                    var jid = this.get('jid');
                    var identity = await _converse.api.disco.getIdentity('conference', 'text', jid);
                    var name = identity && identity.get('name');

                    // During session resumption a restored MUC can refresh its
                    // disco data before the chatbox persistence adapter is
                    // attached. `save` then falls through to HTTP sync and
                    // throws because chatboxes intentionally have no URL.
                    this.set({ name: name || converse.env.Strophe.getNodeFromJid(jid) });
                    await this.getDiscoInfoFields();
                    await this.getDiscoInfoFeatures();
                },
                "sendMarkerForMessage": function (message, type, force) {
                    var stanzaIdField = 'stanza_id ' + this.get('jid');
                    if (message && !message.get(stanzaIdField)) {
                        // A marker cannot be addressed without the server's
                        // stanza ID. Skipping it is the XMPP-compliant no-op.
                        return Promise.resolve();
                    }
                    return this.__super__.sendMarkerForMessage.apply(this, arguments);
                },
                // Converse's own nickname-clash handling does not
                // increment a trailing number the way most MUC clients do
                // (name, name-2, name-3, ...). This computes that instead
                // of Converse's default suffix and, for a non-anonymous
                // user, also remembers it (setStoredMucNick) so the next
                // join in this room starts from the nickname that actually
                // worked rather than colliding again.
                "onNicknameClash": function (presence) {
                    const attemptedNick = (presence.getAttribute('from') || '').split('/')[1];
                    if (!attemptedNick) {
                        return this.__super__.onNicknameClash.apply(this, arguments);
                    }
                    const lastDash = attemptedNick.lastIndexOf('-');
                    const suffix = lastDash !== -1 ? attemptedNick.substring(lastDash + 1) : '';
                    const num = parseInt(suffix, 10);
                    const newNick = !isNaN(num) && suffix !== ''
                        ? attemptedNick.substring(0, lastDash + 1) + (num + 1)
                        : attemptedNick + '-2';

                    if (_converse.tikiSettings.anonymous !== 'y') {
                        setStoredMucNick(newNick);
                    }
                    this.join(newNick);
                },
            },

            /*
             * Anonymous nickname reset box.
             *
             * An anonymous guest joining a MUC (not the dm_target support
             * chat) has not chosen a nickname yet - only the auto-generated
             * "visitor-NNNNN" one from getDefaultMUCNickname above. Before
             * that guest has set one for this browser session (tracked in
             * sessionStorage under the connection's own JID, not
             * TikiConverseStorage's usual tiki_xmpp_ keys, since it must
             * not survive past this one connection), the message form is
             * replaced with a nickname prompt; picking one sends the
             * Converse "/nick" command and swaps the prompt back for the
             * real message form.
             */
            "ChatBoxView": {
                "renderMessageForm": function() {
                    this.__super__.renderMessageForm();

                    var form_container = this.el.querySelector('.message-form-container');
                    var nick_reset_box = this.el.querySelector('.tiki-reset-box');
                    var object = TikiConverseStorage.getObject('sessionStorage', _converse.jid, null);
                    var nickSet = object ? object.nickSet : null;

                    const isDmSupportMode =
                        _converse.tikiSettings &&
                        _converse.tikiSettings.anonymous === 'y' &&
                        _converse.tikiSettings.dm_target;

                    if (
                        form_container &&
                        _converse.tikiSettings.anonymous === 'y' &&
                        !nickSet &&
                        !isDmSupportMode &&
                        !nick_reset_box
                    ) {
                        form_container.classList.add('hidden');
                        this.renderNickResetBox(form_container.parentElement);
                        return this;
                    }

                    return this;
                },

                "onNickReset": function(evt, nick) {
                    if (!(typeof nick === "string" || (nick instanceof String))) {
                        return;
                    }

                    nick = nick.trim().replace(/\s+/g, ' ');
                    if (!nick.length) {
                        return;
                    }

                    var object = TikiConverseStorage.getObject('sessionStorage', _converse.jid, {});
                    object.nickSet = nick;

                    try {
                        this.parseMessageForCommands('/nick ' + nick);
                        TikiConverseStorage.setJSON('sessionStorage', _converse.jid, object);
                    } catch(e) {}

                    var message_box = this.el.querySelector('.message-form-container.hidden');
                    var nick_reset_box = this.el.querySelector('.tiki-reset-box');

                    if (nick_reset_box) nick_reset_box.classList.add('hidden');
                    if (message_box) message_box.classList.remove('hidden');
                },

                "renderNickResetBox": function (container) {
                    var resetBox = document.createElement('div');
                    resetBox.classList.add('tiki-reset-box', 'form-row', 'px-1', 'py-1', 'bg-secondary');

                    var col = document.createElement('div');
                    col.classList.add('col');
                    resetBox.appendChild(col);

                    var input = document.createElement('input');
                    input.type="text";
                    input.placeholder="Type a nick";
                    input.classList.add('form-control');
                    col.appendChild(input);

                    col = document.createElement('div');
                    col.classList.add('col', 'btn-group');
                    resetBox.appendChild(col);

                    var button = document.createElement('button');
                    button.innerText = 'Join';
                    button.classList.add('btn', 'btn-info');
                    col.appendChild(button);

                    var onNickReset = this.onNickReset.bind(this);
                    button.addEventListener("click", function (evt) {
                        return onNickReset(evt, input.value);
                    });
                    input.addEventListener("keydown", function(evt){
                        return evt.keyCode === 13 && onNickReset(evt, input.value);
                    });

                    var regBtn = document.createElement('a');
                    regBtn.href = 'tiki-register.php';
                    regBtn.innerText = 'Register';
                    regBtn.classList.add('btn', 'btn-success', 'text-white');
                    col.appendChild(regBtn);

                    container.appendChild(resetBox);
                    return this;
                },
            }
        }

    });
})(window, window.converse);
