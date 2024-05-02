(function() {
    var _converse, plugin;

    converse.plugins.add("tiki-oauth", {
        "initialize": async function () {
            _converse = this._converse;
            _converse.api.settings.extend({
                'oauth_providers': {}
            });

            plugin = this;
            var provider = (_converse.api.settings.get('oauth_providers') || {}).tiki;
            var error = window.error
                ? window.error
                : (window.feedback
                    ? function(msg){ feedback(msg, 'error'); }
                    : function(msg){ console.error(msg); }
                );

            if(!provider) {
                return;
            }

            var endpoint = provider.authorize_url;
            endpoint = endpoint + '&client_id=' + provider.client_id;

            var response = await fetch(endpoint, { method: 'POST' });
            var token = response.url.match(/[?&]access_token=([^&]+)/)?.[1];

            if (token) {
                plugin.force_oauth_mechanism();
                _converse.api.user.login(_converse.jid, token);
            }
        },

        "force_oauth_mechanism": function() {
            return _converse.promises.initialized.then(function() {
                delete _converse.connection.mechanisms.OAUTHBEARER.priority;
                _converse.connection.mechanisms.OAUTHBEARER.priority = 100;
                console.log('Forced OAUTHBEARER mechanism');
            });
        },
    });
})(converse);