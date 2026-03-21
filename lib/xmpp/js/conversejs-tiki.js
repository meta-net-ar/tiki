(function(window, converse) {
    var _converse;

    converse.plugins.add("tiki", {
        "dependencies": [
            'converse-muc-views',
            'converse-controlbox',
            'converse-chatview',
            'converse-bookmarks',
        ],

        "initialize": function () {
            _converse = this._converse;

            // Store Tiki-provided settings for later use (DM target, anon flag)
            _converse.tikiSettings = {
                dm_target: _converse.api.settings.get('dm_target'),
                anonymous: _converse.api.settings.get('anonymous'),
            };
            var error = console && console.error
                ? console.error.bind(console)
                : function (msg) { return feedback(msg, "error", false); };
            var tr = window.tr
                ? tr
                : function(str) { return str; };

            if (_converse.api.settings.get('authentication') === "anonymous") {
                _converse.promises.initialized.then(function() {
                    const connection = _converse.api.connection.get();
                    if (connection && connection.mechanisms && connection.mechanisms.ANONYMOUS) {
                        delete connection.mechanisms.ANONYMOUS.priority;
                        connection.mechanisms.ANONYMOUS.priority = 50;
                    }
                });
            }

            // --- Override default MUC nickname for anonymous users (visitor-xxx style) ---
            _converse.promises.initialized.then(function () {
                const originalGetDefaultMUCNickname = _converse.exports.getDefaultMUCNickname;

                if (!originalGetDefaultMUCNickname) {
                    console.error('[tiki] getDefaultMUCNickname is not initialized.');
                    return;
                }

                Object.assign(_converse.exports, {
                    getDefaultMUCNickname: function (...args) {
                        // Only affect anonymous users
                        if (_converse.api.settings.get('authentication') !== 'anonymous') {
                            return originalGetDefaultMUCNickname.apply(this, args);
                        }

                        const previousNick = (typeof getPreviousAnonymousNick === 'function')
                            ? getPreviousAnonymousNick()
                            : null;

                        const randomVisitorNick = (typeof randomNick === 'function')
                            ? randomNick('visitor')
                            : 'visitor-' + Math.floor(Math.random() * 100000);

                        // Try Converse default first
                        return (
                            originalGetDefaultMUCNickname.apply(this, args) ||
                            previousNick ||
                            randomVisitorNick
                        );
                    }
                });
            });

            // Patch Strophe missing setProtocol method
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

            _converse.api.listen.on("noResumeableSession", function (xhr) {
                error(tr("XMPP Module error") + ": " + xhr.statusText);
                $("#conversejs").fadeOut("fast");
            });
            // Customize occupant sorting logic for Tiki: sort by presence (online first, offline last) instead of alphabetically.
            _converse.api.listen.on('chatRoomViewInitialized', function (view) {
                if (view.model && view.model.occupants) {
                    view.model.occupants.comparator = function (a, b) {
                        const order = { 'chat': 1, 'available': 1, 'away': 2, 'xa': 3, 'dnd': 4, 'offline': 5 };

                        const presA = a.get('show') || 'offline';
                        const presB = b.get('show') || 'offline';

                        return order[presA] - order[presB];
                    };
                }
            });
            _converse.api.listen.on('connected', async function () {
                const authMode = _converse.api.settings.get('authentication');
                const dmTarget = _converse.tikiSettings.dm_target;
                const tikiAnon = _converse.tikiSettings.anonymous;

                // Open DM automatically for anonymous support mode
                if (authMode === 'anonymous' && tikiAnon === 'y' && dmTarget) {
                    try {
                        await _converse.api.waitUntil('chatBoxesInitialized');
                        const chat = await _converse.api.chats.open(dmTarget);
                        if (chat) {
                            chat.set('hidden', false);
                            chat.trigger('show');
                            if (typeof chat.maybeShow === 'function') {
                                chat.maybeShow();
                            }
                            if (chat.view && typeof chat.view.render === 'function') {
                                chat.view.render();
                            }
                            if (chat.view && typeof chat.view.show === 'function') {
                                chat.view.show();
                            }
                            setTimeout(() => {
                                try {
                                    if (chat.view && typeof chat.view.scrollDown === 'function') {
                                        chat.view.scrollDown();
                                    }
                                } catch (e) {
                                    // ignore
                                }
                            }, 100);
                        }
                    } catch (e) {
                        console.error('[XMPP] Auto DM error:', e);
                    }
                    return;
                }

                if (authMode === 'anonymous') return;

                if (authMode !== 'anonymous') {
                    setTimeout(() => {
                        try {
                            fetch((window.location.origin || '') + '/tiki-xmpp-sync.php', {
                                credentials: 'include',
                                keepalive: true,
                            });
                        } catch (e) {
                            console.error('[XMPP] Deferred sync trigger error:', e);
                        }
                    }, 0);
                }

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
                    console.warn('[XMPP] Rooms fetch skipped:', e);
                    data = [];
                }

                const rooms = Array.isArray(data) ? data : (data.rooms || []);
                // Do not force a nickname for authenticated users: rely on Converse/user profile to avoid bad fallbacks (e.g., "registered").
                const providedNick = (data && typeof data.nickname === 'string') ? data.nickname.trim() : '';

                for (const room of rooms) {
                    if (providedNick) {
                        _converse.api.rooms.open(room, { nickname: providedNick });
                    } else {
                        _converse.api.rooms.open(room);
                    }
                }
            });
        },

        "overrides": {
            "Bookmarks": {
                "openBookmarkedRoom":  async function (bookmark) {
                    if (bookmark.get('autojoin')) {
                        const groupchat = await _converse.api.rooms.create(bookmark.get('jid'), bookmark.get('nick'));
                        if (!(groupchat.get('hidden') || groupchat.get('minimized'))) {
                            groupchat.trigger('show');
                        }
                    }
                    return bookmark;
                },
            },
            "ChatRoomView": {
                "initialize": function () {
                    if (this.model) {
                        this.model.set('minimized', this.is_chatroom === true);
                    }
                    this.__super__.initialize.apply(this, arguments);
                },
                "createOccupantsView": function() {
                    var show_occupants =
                        _converse.user_settings && _converse.user_settings.show_occupants_by_default;

                    if (show_occupants === undefined || show_occupants === null) {
                        show_occupants = window.innerWidth > 576;
                    }

                    if (this.model && this.model.occupants) {
                        this.model.occupants.chatroomview = this;
                        this.occupantsview = new _converse.ChatRoomOccupantsView({
                            'model': this.model.occupants
                        });

                        this.model.save({
                            'hidden_occupants': !show_occupants
                        });

                        const container_el = this.el.querySelector('.chatroom-body');
                        if (container_el) {
                            container_el.insertAdjacentElement('beforeend', this.occupantsview.el);
                        }
                    }
                    return this;
                },
            },
            "ChatBoxes": {
                "chatBoxMayBeShown": function(chatbox) {
                    if (chatbox.get('id') === 'controlbox') {
                        const show = !!_converse.show_controlbox_by_default;
                        return show && window.innerWidth >= 1024 && window.innerHeight >= 768;
                    }
                    return this.__super__.chatBoxMayBeShown(chatbox);
                }
            },
            "ChatBoxView": {
                "renderMessageForm": function() {
                    this.__super__.renderMessageForm();

                    var form_container = this.el.querySelector('.message-form-container');
                    var nick_reset_box = this.el.querySelector('.tiki-reset-box');
                    var object = window.sessionStorage.getItem(_converse.jid);

                    if (object) {
                        try {
                            object = JSON.parse(object);
                        } catch (e) {
                            object = null;
                        }
                    }
                    var nickSet = object ? object.nickSet : null;

                    const isDmSupportMode =
                        _converse.tikiSettings &&
                        _converse.tikiSettings.anonymous === 'y' &&
                        _converse.tikiSettings.dm_target;

                    if (
                        form_container &&
                        _converse.authentication === "anonymous" &&
                        !nickSet &&
                        !isDmSupportMode && // allow immediate typing in anonymous DM mode
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

                    var object = window.sessionStorage.getItem(_converse.jid);
                    object = object ? JSON.parse(object) : {};
                    object.nickSet = nick;

                    try {
                        this.parseMessageForCommands('/nick ' + nick);
                        window.sessionStorage.setItem(_converse.jid, JSON.stringify(object));
                    } catch(e) {
                        console.error("Can't change nickname:", e);
                    }

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
