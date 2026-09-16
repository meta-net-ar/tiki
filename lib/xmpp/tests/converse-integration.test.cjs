// Run with: node --test lib/xmpp/tests/converse-integration.test.cjs
// No Tiki database, browser credentials, XMPP connection or generated files needed.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { execFileSync } = require('node:child_process');
const { webcrypto } = require('node:crypto');
const vm = require('node:vm');

const root = resolve(__dirname, '../../..');
const read = name => readFileSync(resolve(root, 'lib/xmpp/js', name), 'utf8');
const plain = value => JSON.parse(JSON.stringify(value));

function memoryStorage() {
    const values = new Map();
    return {
        getItem: key => values.get(String(key)) ?? null,
        setItem: (key, value) => values.set(String(key), String(value)),
        removeItem: key => values.delete(String(key)),
        get length() { return values.size; },
        key: index => [...values.keys()][index],
    };
}

function environment(blocked = false) {
    const logs = [];
    const listeners = {};
    const document = {
        documentElement: { dataset: {}, style: { setProperty() {} } }, body: {},
        querySelector: () => null, querySelectorAll: () => [], getElementById: () => null,
        addEventListener() {},
    };
    const window = { document, crypto: webcrypto, setTimeout, clearTimeout, requestAnimationFrame() {}, addEventListener() {} };
    for (const name of ['localStorage', 'sessionStorage', 'indexedDB']) {
        if (blocked) Object.defineProperty(window, name, { get() { throw new Error('storage denied'); } });
        else window[name] = name === 'indexedDB' ? undefined : memoryStorage();
    }
    const ctx = vm.createContext({
        window, document, console: { error: (...args) => logs.push(args.join(' ')), warn: (...args) => logs.push(args.join(' ')) },
        setInterval() {}, setTimeout() {}, requestAnimationFrame() {},
        MutationObserver: class { observe() {} disconnect() {} },
        fetch: async () => ({ ok: true, json: async () => ({}) }),
        customElements: { get: () => undefined },
    });
    vm.runInContext(read('conversejs-tiki-storage.js'), ctx);
    vm.runInContext(read('conversejs-tiki-presentation.js'), ctx);
    return { ctx, window, logs, listeners };
}

function plugin(env, options = {}) {
    const values = { ...options };
    let registered;
    env.window.converse = {
        plugins: { add: (name, value) => { registered = value; } },
        env: { Strophe: { Connection: function () {} } },
    };
    // Expose private helpers only in the in-memory test copy, never in production.
    const source = read('conversejs-tiki.js').replace('    converse.plugins.add("tiki", {',
        '    window.testHooks = { contract: TIKI_SETTINGS_CONTRACT, noteChatActivity, getReadCursors, whenConverseInitialized, getConverseCollection, canPatchConverseMethod, removeUnavailableGuestOccupants, pruneUnavailableGuestOccupant };\n    converse.plugins.add("tiki", {');
    vm.runInContext(source, env.ctx);
    const internal = {
        state: {}, exports: {}, session: { get: () => 'me@example' },
        api: {
            settings: {
                get: key => values[key], set: (key, value) => { values[key] = value; },
                extend: defaults => Object.entries(defaults).forEach(([key, value]) => {
                    if (values[key] === undefined) values[key] = value;
                }),
            },
            waitUntil: () => new Promise(() => {}),
            listen: { on: (name, callback) => {
                env.listeners[name] = env.listeners[name] || [];
                env.listeners[name].push(callback);
            } },
        },
    };
    return { registered, internal, values, initialize: () => registered.initialize.call({ _converse: internal }) };
}

function php(options = {}, alwaysLoad = 'y') {
    const code = `
        define('CONVERSEJS_DIST_PATH', 'public/generated/vendor_dist/converse.js/dist');
        define('JS_ASSETS_PATH', 'public/generated');
        require $argv[1] . '/lib/xmpp/ConverseJS.php';
        class TikiLib {
            public static function lib($name) { return new class {
                public function add_jq_onready($script, $priority) { return $script; }
                public function add_js_module($script) {}
                public function add_log($type, $message) {}
            }; }
        }
        function tra($text) { return $text; }
        $user = 'test-user';
        $prefs = ['xmpp_conversejs_debug' => 'n', 'xmpp_conversejs_always_load' => $argv[2]];
        $_SESSION = ['chat-session-init' => 123];
        $options = json_decode(stream_get_contents(STDIN), true);
        $converse = new ConverseJS($options);
        $property = (new ReflectionClass(ConverseJS::class))->getProperty('TIKI_JS_SETTINGS_CONTRACT');
        echo json_encode(['script' => $converse->render(), 'options' => $converse->get_options(),
            'schema' => $property->getValue(), 'dependencies' => $converse->get_js_dependencies()]);
    `;
    return JSON.parse(execFileSync('php', ['-r', code, root, alwaysLoad], {
        input: JSON.stringify(options), encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'],
    }));
}

test('blocked storage property getters do not prevent plugin registration or initialization', () => {
    const env = environment(true);
    const app = plugin(env);
    assert.ok(app.registered);
    assert.doesNotThrow(app.initialize);
});

test('storage failures return documented fallbacks without acquiring storage at the call site', () => {
    const { window } = environment(true);
    const storage = window.TikiConverseStorage;
    for (const name of ['localStorage', 'sessionStorage']) {
        assert.equal(storage.get(name, 'x'), null);
        assert.equal(storage.set(name, 'x', 'v'), false);
        assert.equal(storage.remove(name, 'x'), false);
        assert.equal(storage.clearMatching(name, /x/), false);
        assert.deepEqual(storage.getObject(name, 'x', {}), {});
    }
    assert.doesNotThrow(() => storage.clearIndexedDbMatching(/converse/));
});

test('quota errors are contained, and later storage recovery is usable', () => {
    const { window } = environment();
    window.localStorage.setItem = () => { throw new Error('quota'); };
    assert.equal(window.TikiConverseStorage.set('localStorage', 'x', 'v'), false);
    window.localStorage = memoryStorage();
    assert.equal(window.TikiConverseStorage.set('localStorage', 'x', 'v'), true);
    assert.equal(window.TikiConverseStorage.get('localStorage', 'x'), 'v');
});

for (const raw of ['null', '[]', '42', '"text"', '{broken']) {
    test('activity and cursor maps recover from stored ' + raw, () => {
        const env = environment();
        plugin(env);
        env.window.localStorage.setItem('tiki_xmpp_chat_activity', raw);
        env.window.localStorage.setItem('tiki_xmpp_read_cursors', raw);
        assert.doesNotThrow(() => env.window.testHooks.noteChatActivity('peer@example', 123));
        assert.deepEqual(plain(env.window.testHooks.getReadCursors()), {});
        assert.equal(JSON.parse(env.window.localStorage.getItem('tiki_xmpp_chat_activity'))['peer@example'], 123);
    });
}

test('storage cleanup removes adjacent matching keys and preserves unrelated keys', () => {
    const { window } = environment();
    for (const key of ['converse-a', 'converse-b', 'other']) window.localStorage.setItem(key, '1');
    assert.equal(window.TikiConverseStorage.clearMatching('localStorage', /^converse/g), true);
    assert.equal(window.localStorage.length, 1);
    assert.equal(window.localStorage.getItem('other'), '1');
});

test('PHP and JS declare identical keys, defaults, types and allowed values', () => {
    const env = environment();
    plugin(env);
    const phpSchema = php().schema;
    assert.deepEqual(Object.keys(phpSchema).sort(), plain(env.window.testHooks.contract.map(entry => entry.key)).sort());
    for (const entry of env.window.testHooks.contract) {
        assert.equal(entry.default, phpSchema[entry.key].default, entry.key);
        assert.equal(entry.type, phpSchema[entry.key].type, entry.key);
        assert.deepEqual(plain(entry.values || []), phpSchema[entry.key].values || [], entry.key);
    }
});

for (const options of [
    { anonymous: 'y', auto_open: 'n', using_external: 'y', tiki_anonymous_nick: 'custom', dm_target: 'support@example' },
    { anonymous: true, auto_open: 'bad', dm_target: [], current_room: 42, tiki_site_name: {}, tiki_anonymous_nick: 'invalid' },
    { anonymous: null, tiki_site_name: null, tiki_support_label: '', tiki_site_logo: '', current_room: '' },
]) {
    test('PHP and JS normalize the same configuration: ' + JSON.stringify(options), () => {
        const env = environment();
        const app = plugin(env, options);
        app.initialize();
        const phpOptions = php(options).options;
        for (const key of Object.keys(options)) assert.deepEqual(plain(app.values[key]), phpOptions[key], key);
        if (options.tiki_site_name === null) assert.equal(app.internal.tikiSettings.site_name, 'Tiki');
    });
}

test('required missing internals disable Tiki setup with a named diagnostic', () => {
    const env = environment();
    const app = plugin(env);
    delete app.internal.session;
    assert.doesNotThrow(app.initialize);
    assert.equal(env.logs.length, 1);
    assert.match(env.logs[0], /session.get/);
    assert.equal(app.internal.tikiSettings, undefined);
});

test('missing lifecycle promises skip callbacks and diagnose each feature once', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.api.waitUntil = () => null;
    let called = false;
    for (let i = 0; i < 2; i++) await env.window.testHooks.whenConverseInitialized('test feature', () => { called = true; });
    assert.equal(called, false);
    assert.equal(env.logs.length, 1);
    assert.match(env.logs[0], /initialized lifecycle promise/);
});

test('one failed initialization callback does not prevent another', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.api.waitUntil = () => Promise.resolve();
    let healthy = false;
    await env.window.testHooks.whenConverseInitialized('broken', () => { throw new Error('private data'); });
    await env.window.testHooks.whenConverseInitialized('healthy', () => { healthy = true; });
    assert.equal(healthy, true);
    assert.match(env.logs[0], /broken/);
    assert.ok(!env.logs[0].includes('private data'));
});

test('collection and prototype checks reject incompatible shapes', () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.state.chatboxes = { on() {} };
    assert.equal(env.window.testHooks.getConverseCollection('chatboxes', ['on'], 'badges'), null);
    assert.equal(env.window.testHooks.canPatchConverseMethod(undefined, 'render', 'preview'), false);
    assert.equal(env.logs.length, 2);
});

test('offline generated guest occupants are pruned from restored room rosters', () => {
    const env = environment();
    plugin(env);
    const occupant = attrs => ({ get: key => attrs[key] });
    const staleGuest = occupant({ nick: 'guest-ab6cf524d6fff6d6a33d', presence: 'offline' });
    const unavailableGuest = occupant({ jid: 'guest-7ff374dcb72d86aca18d@example', type: 'unavailable' });
    const onlineGuest = occupant({ nick: 'guest-e47d717f00ff7240d54d', presence: 'online' });
    const offlineMember = occupant({ nick: 'admin', show: 'offline' });
    const currentGuest = occupant({ nick: 'guest-11111111111111111111', show: 'offline' });
    const occupants = {
        models: [staleGuest, unavailableGuest, onlineGuest, offlineMember, currentGuest],
        remove(model) {
            this.models = this.models.filter(candidate => candidate !== model);
        },
    };
    const room = { occupants, get: key => key === 'nick' ? 'guest-11111111111111111111' : undefined };

    env.window.testHooks.removeUnavailableGuestOccupants(room);

    assert.deepEqual(occupants.models, [onlineGuest, offlineMember, currentGuest]);
});

test('individual guest updates prune unavailable guests and preserve other occupants', () => {
    const env = environment();
    plugin(env);
    const guest = 'guest-ab6cf524d6fff6d6a33d';
    const ownNick = 'guest-11111111111111111111';
    const cases = [
        [{ nick: guest, presence: 'online' }, false],
        [{ nick: guest, presence: 'online', show: 'away' }, false],
        [{ nick: guest, presence: 'offline' }, true],
        [{ nick: guest, presence: 'offline', show: 'away' }, true],
        [{ nick: guest, show: 'offline' }, true],
        [{ jid: guest + '@example', show: 'chat', type: 'unavailable' }, true],
        [{ id: guest + '@example', show: 'chat', presence_type: 'unavailable' }, true],
        [{ nick: guest, show: 'unavailable' }, true],
        [{ nick: guest }, true],
        [{ nick: guest, show: 'chat' }, false],
        [{ nick: guest, show: 'away' }, false],
        [{ nick: 'admin', show: 'offline' }, false],
        [{ nick: 'guest-not-generated', show: 'offline' }, false],
        [{ nick: ownNick, show: 'offline' }, false],
    ];
    for (const [attrs, shouldRemove] of cases) {
        const occupant = { get: key => attrs[key] };
        const removed = [];
        const room = { occupants: { remove: model => removed.push(model) }, get: key => key === 'nick' ? ownNick : undefined };
        env.window.testHooks.pruneUnavailableGuestOccupant(room, occupant);
        assert.deepEqual(removed, shouldRemove ? [occupant] : [], JSON.stringify(attrs));
    }
});

test('room presence events retain silent guests and prune departures across reopening', () => {
    const env = environment();
    plugin(env).initialize();
    const handlers = {};
    const occupants = {
        models: [],
        on(events, callback) {
            for (const event of events.split(' ')) handlers[event] = callback;
        },
        remove(model) { this.models = this.models.filter(candidate => candidate !== model); },
    };
    const room = { occupants, get: () => 'admin' };
    const open = () => env.listeners.chatRoomViewInitialized.forEach(callback => callback({ model: room }));
    open();
    const attrs = { nick: 'guest-ab6cf524d6fff6d6a33d', presence: 'online' };
    const guest = { get: key => attrs[key] };
    occupants.models.push(guest);
    handlers.add(guest);
    assert.deepEqual(occupants.models, [guest], 'arrival without a message remains visible');
    open();
    assert.deepEqual(occupants.models, [guest], 'reopening preserves an online guest without show');
    attrs.presence = 'offline';
    handlers['change:presence'](guest);
    assert.deepEqual(occupants.models, [], 'departure removes the guest without a show change');
    occupants.models.push(guest);
    open();
    assert.deepEqual(occupants.models, [], 'reopening removes restored offline guests');
    attrs.presence = 'online';
    occupants.models.push(guest);
    handlers.add(guest);
    assert.deepEqual(occupants.models, [guest], 'rejoining does not require a message');
});

test('guest pruning tolerates missing collections and malformed occupants', () => {
    const env = environment();
    plugin(env);
    const { removeUnavailableGuestOccupants, pruneUnavailableGuestOccupant } = env.window.testHooks;
    for (const room of [null, {}, { occupants: {} }, { occupants: { models: [] } }]) {
        assert.doesNotThrow(() => removeUnavailableGuestOccupants(room));
        assert.doesNotThrow(() => pruneUnavailableGuestOccupant(room, null));
    }
    const removed = [];
    const room = { occupants: { models: [null, {}], remove: model => removed.push(model) } };
    removeUnavailableGuestOccupants(room);
    pruneUnavailableGuestOccupant(room, null);
    pruneUnavailableGuestOccupant(room, {});
    assert.deepEqual(removed, []);
});

for (const authentication of ['login', 'anonymous']) {
    test('PHP-generated ' + authentication + ' startup survives disabled browser storage', async () => {
        const env = environment(true);
        const calls = [];
        env.window.converse = { initialize: options => calls.push(options) };
        env.window.tikiConverseReady = Promise.resolve(env.window.converse);
        const rendered = php({ authentication, jid: 'guest.example', anonymous: authentication === 'anonymous' ? 'y' : 'n' });
        await vm.runInContext(rendered.script, env.ctx);
        await Promise.resolve();
        assert.equal(calls.length, 1);
        assert.equal(calls[0].authentication, 'login');
        assert.equal(calls[0].auto_open, 'n');
        if (authentication === 'anonymous') {
            assert.match(calls[0].jid, /^guest-[a-f0-9]{20}@guest.example$/);
            assert.match(calls[0].password, /^[a-f0-9]{48}$/);
        }
        assert.ok(!env.logs.some(line => line.includes('FATAL')));
    });
}

test('generated startup awaits helpers and reuses the same guest identity on a subsequent render', async () => {
    const env = environment();
    const calls = [];
    env.window.converse = { initialize: options => calls.push(plain(options)) };
    let ready;
    env.window.tikiConverseReady = new Promise(resolve => { ready = resolve; });
    const rendered = php({ authentication: 'anonymous', jid: 'guest.example', anonymous: 'y' });
    const pending = vm.runInContext(rendered.script, env.ctx);
    assert.equal(calls.length, 0);
    ready(env.window.converse);
    await pending;
    await Promise.resolve();
    await vm.runInContext(rendered.script, env.ctx);
    await Promise.resolve();
    assert.equal(calls.length, 2);
    assert.equal(calls[0].jid, calls[1].jid);
    assert.equal(calls[0].password, calls[1].password);
});

test('both PHP loading modes keep storage and presentation before the main plugin', () => {
    for (const always of ['y', 'n']) {
        const deps = php({}, always).dependencies.map(path => path.split('?')[0]);
        assert.deepEqual(deps.slice(0, 3), [
            'lib/xmpp/js/conversejs-tiki-storage.js', 'lib/xmpp/js/conversejs-tiki-presentation.js', 'lib/xmpp/js/conversejs-tiki.js',
        ]);
    }
});

test('shared dropdown and flyout selectors retain their previous values', () => {
    const { window } = environment();
    const selectors = window.TikiConversePresentation.SELECTORS;
    assert.equal(selectors.CHAT_FLYOUTS, '#conversejs .chatbox .box-flyout, #conversejs #controlbox .box-flyout');
    assert.equal(selectors.DROPDOWN, 'converse-dropdown');
    assert.equal(selectors.CHATBOX, '.chatbox:not(#controlbox)');
});

test('initialization callbacks wait for the public lifecycle promise', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    let ready;
    app.internal.api.waitUntil = () => new Promise(resolve => { ready = resolve; });
    let called = false;
    const pending = env.window.testHooks.whenConverseInitialized('delayed', () => { called = true; });
    assert.equal(called, false);
    ready();
    await pending;
    assert.equal(called, true);
});

test('a throwing lifecycle lookup is diagnosed without running its patch', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.api.waitUntil = () => { throw new Error('incompatible API'); };
    let called = false;
    await env.window.testHooks.whenConverseInitialized('broken API', () => { called = true; });
    assert.equal(called, false);
    assert.match(env.logs[0], /api.waitUntil/);
});

test('generated startup reports a rejected Converse initialization promise', async () => {
    const env = environment();
    env.window.converse = { initialize: () => Promise.reject(new Error('test failure')) };
    const rendered = php({ authentication: 'login', jid: 'user@example' });
    await vm.runInContext(rendered.script, env.ctx);
    await new Promise(resolve => setImmediate(resolve));
    assert.ok(env.logs.some(line => line.includes('initialization failed')));
});

for (const mode of ['external-manual', 'external-prebind', 'local']) {
    const external = mode !== 'local';
    test('PHP auth rendering keeps stored external credentials server-side: ' + mode, () => {
        const code = `
            define('CONVERSEJS_DIST_PATH', 'public/generated/vendor_dist/converse.js/dist');
            define('JS_ASSETS_PATH', 'public/generated');
            require $argv[1] . '/lib/xmpp/ConverseJS.php';
            class TikiLib {
                public static function lib($name) { return new class {
                    public function get_preference($key) { return 'http'; }
                    public function canPrebindExternal($user, $connection) { return $GLOBALS['prebind']; }
                    public function getUrl($params) { return 'tiki-ajax_services.php?controller=xmpp&action=external_prebind'; }
                    public function buildRoomJid($room) { return $room; }
                    public function get_user_connection_info($user) {
                        return ['custom_endpoint' => true, 'websocket_url' => 'wss://external.example/ws',
                            'http_bind' => 'https://external.example/bosh'];
                    }
                    public function getJidInfoForUser($user) {
                        return ['isExternal' => true, 'jid' => 'alice@external.example', 'localJid' => 'alice@local.example'];
                    }
                    public function getExternalPassword($user) {
                        throw new RuntimeException('Rendering must not read the stored external password');
                    }
                    public function getXmppSessionToken($user) { return 'local-session-token'; }
                    public function add_jq_onready($script, $priority) { return $script; }
                    public function add_js_module($script) {}
                    public function add_log($type, $message) {}
                }; }
            }
            function tra($text) { return $text; }
            $user = 'alice';
            $prefs = ['xmpp_conversejs_debug' => 'n', 'xmpp_conversejs_always_load' => 'y',
                'xmpp_ws_url' => 'wss://local.example/ws', 'xmpp_server_http_bind' => 'https://local.example/bosh'];
            $_COOKIE['tiki_xmpp_external'] = $argv[2];
            $prebind = $argv[3] === 'external-prebind';
            $_SESSION = ['chat-session-init' => 123];
            $converse = new ConverseJS();
            $converse->set_auth(['on_xmpp_page' => 'y', 'auto_open' => 'y']);
            echo json_encode(['options' => $converse->get_options(), 'script' => $converse->render()]);
        `;
        const rendered = JSON.parse(execFileSync('php', ['-r', code, root, external ? '1' : '', mode], {
            encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'],
        }));
        const options = rendered.options;
        assert.equal(options.authentication, mode === 'external-prebind' ? 'prebind' : 'login');
        assert.equal(options.auto_login, mode !== 'external-manual');
        assert.equal(options.using_external, external ? 'y' : 'n');
        assert.equal(options.jid, external ? 'alice@external.example' : 'alice@local.example');
        assert.equal(options.websocket_url, mode === 'external-prebind' ? '' : external ? 'wss://external.example/ws' : 'wss://local.example/ws');
        if (mode === 'external-prebind') {
            assert.equal(options.bosh_service_url, 'https://external.example/bosh');
            assert.match(options.prebind_url, /action=external_prebind/);
        }
        if (external) {
            assert.equal(Object.hasOwn(options, 'password'), false);
            assert.ok(!rendered.script.includes('local-session-token'));
        } else {
            assert.equal(options.password, 'local-session-token');
        }
    });
}

test('external prebind uses the current account and returns only BOSH session credentials', () => {
    const code = `
        class TikiLib {}
        class XmppPrebind {
            public static $login;
            public function __construct($domain, $url, $resource, $ssl, $debug) {
                if ($domain !== 'external.example' || $url !== 'https://external.example/bosh' || $debug) {
                    throw new RuntimeException('Wrong prebind destination');
                }
            }
            public function connect($username, $password, $route = false) { self::$login = [$username, $password]; }
            public function auth() {}
            public function getSessionInfo() {
                return ['jid' => 'alice@external.example/tiki', 'sid' => 'test-sid', 'rid' => 123, 'password' => 'must-not-return'];
            }
        }
        require $argv[1] . '/lib/xmpp/xmpplib.php';
        class TestXmpp extends XMPPLib {
            public function __construct() {}
            public function getJidInfoForUser(string $username): array { return ['isExternal' => true]; }
            public function get_user_connection_info($user) {
                return ['domain' => 'external.example', 'username' => 'alice', 'custom_endpoint' => true,
                    'http_bind' => 'https://external.example/bosh'];
            }
            public function hasExternalPassword(string $username): bool { return true; }
            public function getExternalPassword(string $username): ?string { return 'stored-secret'; }
        }
        $user = 'alice';
        $prefs = ['auth_token_access' => 'n'];
        $lib = new TestXmpp();
        $session = $lib->prebindExternal('alice');
        if (XmppPrebind::$login !== ['alice', 'stored-secret']) { throw new RuntimeException('Wrong credentials'); }
        try { $lib->prebindExternal('bob'); throw new RuntimeException('Wrong user accepted'); }
        catch (RuntimeException $e) { if ($e->getCode() !== 403) { throw $e; } }
        foreach (['wss://external.example/ws', 'http://external.example/bosh', ''] as $url) {
            if ($lib->canPrebindExternal('alice', ['custom_endpoint' => true, 'http_bind' => $url])) {
                throw new RuntimeException('Unsupported transport accepted');
            }
        }
        if ($lib->canPrebindExternal('alice', ['custom_endpoint' => false, 'http_bind' => 'https://local.example/bosh'])) {
            throw new RuntimeException('Inherited local endpoint accepted');
        }
        echo json_encode($session);
    `;
    const session = JSON.parse(execFileSync('php', ['-r', code, root], { encoding: 'utf8' }));
    assert.deepEqual(session, { jid: 'alice@external.example/tiki', sid: 'test-sid', rid: 123 });
});

test('external BOSH transport rejects insecure and private destinations before sending', () => {
    const code = `
        class XmppPrebind {
            protected $boshUri;
            public function __construct($url) { $this->boshUri = $url; }
        }
        require $argv[1] . '/lib/xmpp/TikiXmppExternalPrebind.php';
        class TestTransport extends \\Tiki\\Lib\\Xmpp\\TikiXmppExternalPrebind {
            public function request() { return $this->send('<body/>'); }
        }
        $rejected = 0;
        foreach (['http://example.com/bosh', 'https://127.0.0.1/bosh', 'https://10.0.0.1/bosh',
            'https://169.254.169.254/bosh', 'https://[::1]/bosh', 'https://user:pass@example.com/bosh', 'https://example.com/bosh#fragment'] as $url) {
            try { (new TestTransport($url))->request(); }
            catch (RuntimeException $e) { $rejected++; }
        }
        echo $rejected;
    `;
    assert.equal(execFileSync('php', ['-r', code, root], { encoding: 'utf8' }), '7');
});

test('external prebind service blocks guests and hides upstream errors', () => {
    const code = `
        function tr($text) { return $text; }
        class Services_Exception extends Exception {}
        class TikiLib {
            public static function lib($name) { return new class {
                public function prebindExternal($user) { throw new RuntimeException('upstream-secret'); }
            }; }
        }
        require $argv[1] . '/lib/core/Services/Xmpp/Controller.php';
        $controller = new Services_Xmpp_Controller();
        $codes = [];
        foreach (['', 'alice'] as $user) {
            try { $controller->action_external_prebind(null); }
            catch (Services_Exception $e) {
                if (str_contains($e->getMessage(), 'upstream-secret')) { throw new RuntimeException('Secret leaked'); }
                $codes[] = $e->getCode();
            }
        }
        echo json_encode($codes);
    `;
    assert.deepEqual(JSON.parse(execFileSync('php', ['-r', code, root], { encoding: 'utf8' })), [403, 502]);
});

test('external prebind is independent of content token access while legacy services stay gated', () => {
    const code = `
        class Services_Exception_Disabled extends Exception {
            public static $checked = [];
            public static function check($name) {
                self::$checked[] = $name;
                if ($name === 'auth_token_access') { throw new self('disabled'); }
            }
        }
        class TikiLib {
            public static function lib($name) { return new class {
                public function prebindExternal($user) { return ['jid' => 'alice@example', 'sid' => 'test', 'rid' => 1]; }
            }; }
        }
        require $argv[1] . '/lib/core/Services/Xmpp/Controller.php';
        $user = 'alice';
        $controller = new Services_Xmpp_Controller();
        $controller->setUp();
        $session = $controller->action_external_prebind(null);
        $blocked = 0;
        foreach (['check_token', 'get_user_info', 'prebind', 'groups_in_room', 'users_in_room'] as $action) {
            try { $controller->{'action_' . $action}(null); }
            catch (Services_Exception_Disabled $e) { $blocked++; }
        }
        echo json_encode(['session' => $session, 'blocked' => $blocked, 'checks' => Services_Exception_Disabled::$checked]);
    `;
    const result = JSON.parse(execFileSync('php', ['-r', code, root], { encoding: 'utf8' }));
    assert.equal(result.session.jid, 'alice@example');
    assert.equal(result.blocked, 5);
    assert.deepEqual(result.checks, ['xmpp_feature', ...Array(5).fill('auth_token_access')]);
});


test('IndexedDB cleanup waits for deletion and preserves unrelated databases', async () => {
    const { window } = environment();
    const requests = [];
    window.indexedDB = {
        databases: async () => [{ name: 'converse-chat' }, { name: 'other' }],
        deleteDatabase: name => { const request = { name }; requests.push(request); return request; },
    };
    let completed = false;
    const cleanup = window.TikiConverseStorage.clearIndexedDbMatching(/^converse/).then(result => {
        completed = true;
        return result;
    });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(completed, false);
    assert.deepEqual(requests.map(request => request.name), ['converse-chat']);
    requests[0].onsuccess();
    assert.equal(await cleanup, true);
});

test('IndexedDB cleanup reports errors and times out when another tab blocks deletion', async () => {
    for (const error of [true, false]) {
        const { window } = environment();
        let timeout;
        let request;
        window.setTimeout = callback => { timeout = callback; return 1; };
        window.clearTimeout = () => {};
        window.indexedDB = {
            databases: async () => [{ name: 'converse-chat' }],
            deleteDatabase: () => { request = {}; return request; },
        };
        const cleanup = window.TikiConverseStorage.clearIndexedDbMatching(/^converse/);
        await new Promise(resolve => setImmediate(resolve));
        if (error) request.onerror();
        else timeout();
        assert.equal(await cleanup, false);
    }
});

for (const destination of ['support', 'community', 'community-only']) {
    test(`guest ${destination} confirmation leaves all rooms before logout and identity reset`, async () => {
        const { Window } = await import('happy-dom');
        const dom = new Window();
        const document = dom.document;
        const calls = [];
        let finishRooms;
        const roomsClosed = new Promise(resolve => { finishRooms = resolve; });
        let finishLogout;
        let finishCleanup;
        const logout = new Promise(resolve => { finishLogout = resolve; });
        const cleanup = new Promise(resolve => { finishCleanup = resolve; });
        const window = {
            document, setTimeout, clearTimeout, addEventListener() {}, requestAnimationFrame() {},
            location: { reload: () => calls.push('reload') },
        };
        const settings = {
            anonymous: 'y', dm_target: destination === 'community-only' ? null : 'support@example', anon_room: 'community@example',
            support_end_title: 'Titre traduit', support_end_description: 'Description traduite <test>',
            support_end_cancel_label: 'Continuer', support_end_confirm_label: 'Terminer',
        };
        const ctx = vm.createContext({ window, document, setTimeout,
            MutationObserver: class { observe() {} },
        });
        vm.runInContext(read('conversejs-tiki-presentation.js'), ctx);
        window.TikiConversePresentation.install({
            converse: { tikiSettings: settings, api: {
                rooms: { get: async () => [
                    { close: () => { calls.push('leave-community'); return roomsClosed; } },
                    { close: () => { calls.push('leave-other'); throw new Error('Room already disconnected'); } },
                ] },
                user: { logout: () => { calls.push('logout'); return logout; } },
            } },
            isGeneratedGuestJid: () => false,
            getChatboxElementJid: el => el.getAttribute('data-jid'),
            scheduleChatListSort() {},
            clearTikiGuestSessionStorage: () => { calls.push('cleanup'); return cleanup; },
        });
        const targetJid = destination === 'support' ? 'support@example' : 'community@example';
        document.body.innerHTML = `<div class="chatbox" data-jid="${targetJid}"><button class="close-chatbox-button"><span>Leave</span></button></div>`;
        const button = document.querySelector('button');
        let originalCloses = 0;
        button.addEventListener('click', () => originalCloses++);
        button.focus();
        button.click();
        let dialog = document.querySelector('dialog');
        assert.ok(dialog.open);
        assert.equal(dialog.querySelector('h2').textContent, settings.support_end_title);
        assert.equal(dialog.querySelector('p').textContent, settings.support_end_description);
        assert.equal(dialog.querySelector('test'), null);
        assert.equal(document.activeElement, dialog.querySelector('[data-cancel]'));
        dialog.querySelector('[data-cancel]').click();
        assert.equal(document.querySelector('dialog'), null);
        assert.equal(document.activeElement, button);
        assert.equal(originalCloses, 0);
        assert.deepEqual(calls, []);
        button.click();
        document.querySelector('dialog').dispatchEvent(new dom.Event('cancel', { cancelable: true }));
        assert.equal(document.querySelector('dialog'), null);
        assert.deepEqual(calls, []);
        settings.anonymous = 'n';
        button.click();
        assert.equal(originalCloses, 1);
        assert.equal(document.querySelector('dialog'), null);
        settings.anonymous = 'y';
        document.querySelector('.chatbox').setAttribute('data-jid', 'unrelated@example');
        button.click();
        assert.equal(originalCloses, 2);
        assert.equal(document.querySelector('dialog'), null);
        document.querySelector('.chatbox').setAttribute('data-jid', targetJid);
        button.querySelector('span').click();
        button.click();
        assert.equal(document.querySelectorAll('dialog').length, 1);
        dialog = document.querySelector('dialog');
        dialog.querySelector('[data-confirm]').click();
        dialog.querySelector('[data-confirm]').click();
        await new Promise(resolve => setImmediate(resolve));
        assert.deepEqual(calls, ['leave-community', 'leave-other']);
        finishRooms();
        await new Promise(resolve => setImmediate(resolve));
        assert.deepEqual(calls, ['leave-community', 'leave-other', 'logout']);
        finishLogout();
        await new Promise(resolve => setImmediate(resolve));
        assert.deepEqual(calls, ['leave-community', 'leave-other', 'logout', 'cleanup']);
        finishCleanup();
        await new Promise(resolve => setImmediate(resolve));
        assert.deepEqual(calls, ['leave-community', 'leave-other', 'logout', 'cleanup', 'reload']);
        await dom.happyDOM.close();
    });

}

for (const scenario of ['no rooms', 'lookup failure', 'room timeout']) {
    test(`guest support termination still logs out after ${scenario}`, async () => {
        const { Window } = await import('happy-dom');
        const dom = new Window();
        const document = dom.document;
        const calls = [];
        const timers = new Map();
        let nextTimer = 0;
        const window = {
            document, addEventListener() {}, requestAnimationFrame() {},
            setTimeout: callback => { timers.set(++nextTimer, callback); return nextTimer; },
            clearTimeout: id => timers.delete(id),
            location: { reload: () => calls.push('reload') },
        };
        const ctx = vm.createContext({ window, document, setTimeout, MutationObserver: class { observe() {} } });
        vm.runInContext(read('conversejs-tiki-presentation.js'), ctx);
        window.TikiConversePresentation.install({
            converse: {
                tikiSettings: { anonymous: 'y', dm_target: 'support@example' },
                api: {
                    rooms: { get: async () => {
                        if (scenario === 'lookup failure') throw new Error('Unavailable');
                        if (scenario === 'no rooms') return [];
                        return [{ close: () => { calls.push('leave'); return new Promise(() => {}); } }];
                    } },
                    user: { logout: async () => { calls.push('logout'); } },
                },
            },
            isGeneratedGuestJid: () => false,
            getChatboxElementJid: el => el.getAttribute('data-jid'),
            scheduleChatListSort() {},
            clearTikiGuestSessionStorage: async () => { calls.push('cleanup'); },
        });
        document.body.innerHTML = '<div class="chatbox" data-jid="support@example"><button class="close-chatbox-button">Close</button></div>';
        document.querySelector('button').click();
        document.querySelector('[data-confirm]').click();
        await new Promise(resolve => setImmediate(resolve));
        if (scenario === 'room timeout') {
            assert.deepEqual(calls, ['leave']);
            assert.equal(timers.size, 1);
            [...timers.values()][0]();
            await new Promise(resolve => setImmediate(resolve));
            assert.deepEqual(calls, ['leave', 'logout', 'cleanup', 'reload']);
        } else {
            assert.deepEqual(calls, ['logout', 'cleanup', 'reload']);
        }
        await dom.happyDOM.close();
    });
}
