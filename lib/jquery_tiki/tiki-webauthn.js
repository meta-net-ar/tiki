// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
(function ($) {

    const deviceRegFailure = tr("We couldn't register your device. Please ensure your device supports WebAuthn and try again. If the issue persists, contact support.");
    const credsVerifyFailure = tr("The credential verification step failed. Please check your device and try again. If the issue persists, contact support.");
    const usernameRequiredMsg = tr("Username is required.");
    const cancelRegisterMsg = tr("Passkey creation was cancelled. You can try again whenever you're ready.");
    const cancelLoginMsg = tr("Passkey sign-in was cancelled. You can try again or sign in with your password.");
    const httpsRequiredMsg = tr("Passkeys require a secure (HTTPS) connection.");
    const alreadyRegisteredMsg = tr("This authenticator is already registered for your account.");
    const unsupportedBrowserMsg = tr("Your browser does not support passkeys.");
    const unsupportedBrowserLoginMsg = tr("Your browser does not support passkeys. Please use a different browser or sign in with your password.");

    /**
     * Translate a raw WebAuthn / DOMException error into a user-friendly message.
     * Strips browser-generated W3C spec URLs and technical jargon.
     *
     * @param {Error|string} error
     * @param {'login'|'register'} context
     * @returns {{ message: string, code: string }}
     */
    function classifyWebAuthnError(error, context) {
        const name = (error && error.name) ? error.name : '';

        if (name === 'NotAllowedError') {
            const msg = context === 'register' ? cancelRegisterMsg : cancelLoginMsg;
            return { message: msg, code: 'USER_CANCELLED' };
        }

        if (error && error.code === 'NO_PASSKEYS') {
            return { message: error.message, code: 'NO_PASSKEYS' };
        }

        if (name === 'SecurityError') {
            return { message: httpsRequiredMsg, code: 'SECURITY_ERROR' };
        }

        if (name === 'InvalidStateError') {
            return { message: alreadyRegisteredMsg, code: 'ALREADY_REGISTERED' };
        }

        // For any other error, return the original message but strip any W3C spec URLs.
        const rawMsg = (error && error.message) ? error.message : (error ? error.toString() : '');
        const cleanMsg = rawMsg.replace(/\s*See:\s*https?:\/\/[^\s]+/gi, '').trim();
        return {
            message: cleanMsg || (context === 'register' ? deviceRegFailure : credsVerifyFailure),
            code: name || 'UNKNOWN',
        };
    }

    $.fn = $.extend($.fn, {
        helper: {
            atb: function (b) {
                let u = new Uint8Array(b),
                    s = "";
                for (let i = 0; i < u.byteLength; i++) {
                    s += String.fromCharCode(u[i]);
                }
                return btoa(s);
            },
            detectPlatform: function () {
                const userAgent = navigator.userAgent;
                const platform = navigator.platform;
                if (userAgent.includes("Chrome")) {
                    return "Chrome on " + platform;
                } else if (userAgent.includes("Firefox")) {
                    return "Firefox on " + platform;
                } else if (userAgent.includes("Safari") && !userAgent.includes("Chrome")) {
                    return "Safari on " + platform;
                } else if (userAgent.includes("Edge")) {
                    return "Edge on " + platform;
                } else if (userAgent.includes("Android")) {
                    return "Android Device";
                } else if (userAgent.includes("iPhone") || userAgent.includes("iPad")) {
                    return "iOS Device";
                }
                return "Unknown Platform (" + platform + ")";
            },
        },
        registerWebAuth: {
            createCredentials: async function (event, username = null) {
                return new Promise((resolve, reject) => {
                    try {
                        event.preventDefault();
                        if (!username) {
                            reject(usernameRequiredMsg);
                            return;
                        }
                        $.ajax({
                            type: "POST",
                            url: $.service("webauthn", "CreateCredential"),
                            data: { username: username },
                            success: async function (data) {
                                if (data.status === "success") {
                                    const res = data.options;
                                    res.challenge = Uint8Array.from(atob(res.challenge), (c) => c.charCodeAt(0));
                                    res.user.id = Uint8Array.from(atob(res.user.id), (c) => c.charCodeAt(0));
                                    try {
                                        const credential = await navigator.credentials.create({ publicKey: res });
                                        const response = await $.fn.registerWebAuth.registerResponse(credential);
                                        resolve(response);
                                    } catch (error) {
                                        reject(classifyWebAuthnError(error, 'register'));
                                    }
                                } else {
                                    reject(data.message);
                                }
                            },
                            error: function (req, status, error) {
                                reject(error || deviceRegFailure);
                            },
                        });
                    } catch (err) {
                        reject(classifyWebAuthnError(err, 'register'));
                    }
                });
            },
            registerResponse: function (cred) {
                return new Promise((resolve, reject) => {
                    try {
                        const response = cred.response;
                        const clientDataJSON = new Uint8Array(response.clientDataJSON);
                        const attestationObject = new Uint8Array(response.attestationObject);
                        $.ajax({
                            type: "POST",
                            url: $.service("webauthn", "RegisterResponse"),
                            data: {
                                clientDataJSON: $.fn.helper.atb(clientDataJSON),
                                attestationObject: $.fn.helper.atb(attestationObject),
                                device_name: $.fn.helper.detectPlatform(),
                            },
                            success: function (data) {
                                if (data.status === "success") {
                                    resolve(data);
                                } else {
                                    reject(data.message);
                                }
                            },
                            error: function (req, status, error) {
                                reject(error || deviceRegFailure);
                            },
                        });
                    } catch (err) {
                        reject(err?.message || err?.name || deviceRegFailure);
                    }
                });
            },
        },
        passkeyPrompt: {
            init: function (username, redirectUrl) {
                var card = document.getElementById('passkey-prompt-card');
                redirectUrl = redirectUrl || (card && card.dataset.redirect) || 'index.php';

                function showSpinner() {
                    document.getElementById('passkey-actions').hidden = true;
                    document.getElementById('passkey-error').hidden   = true;
                    document.getElementById('passkey-spinner').hidden = false;
                }

                function showError(err, alertType) {
                    var msg = (err && err.message) ? err.message : (err ? String(err) : '');
                    var type = alertType || (err && err.code === 'USER_CANCELLED' ? 'warning' : 'danger');
                    var msgEl = document.getElementById('passkey-error-msg');
                    msgEl.textContent = msg;
                    msgEl.className = 'alert alert-' + type;
                    document.getElementById('passkey-spinner').hidden = true;
                    document.getElementById('passkey-actions').hidden = true;
                    document.getElementById('passkey-error').hidden   = false;
                }

                function showSuccess() {
                    var successEl = document.getElementById('passkey-success');
                    if (successEl) {
                        document.getElementById('passkey-prompt-card').hidden = true;
                        successEl.hidden = false;
                    } else {
                        window.location.href = redirectUrl;
                    }
                }

                function redirect() {
                    window.location.href = redirectUrl;
                }

                function attemptPasskey() {
                    if (!window.PublicKeyCredential) {
                        showError(unsupportedBrowserMsg);
                        return;
                    }
                    showSpinner();
                    var fakeEvent = { preventDefault: function () {} };
                    $.fn.registerWebAuth.createCredentials(fakeEvent, username)
                        .then(function () {
                            showSuccess();
                        })
                        .catch(function (err) {
                            showError(err);
                        });
                }

                $(document).ready(function () {
                    $('#addPasskeyBtn, #retryPasskeyBtn').on('click', function () {
                        attemptPasskey();
                    });
                    $('#skipPasskeyBtn, #skipAfterErrorBtn').on('click', function () {
                        redirect();
                    });
                });
            },
        },
        loginWebAuth: {
            loginStart: async function (event, module_logo_instance = 0, form) {
                return new Promise(async (resolve, reject) => {
                    try {
                        event.preventDefault();
                        if (!window.PublicKeyCredential || !navigator.credentials || !navigator.credentials.get) {
                            reject({ message: unsupportedBrowserLoginMsg, code: 'UNSUPPORTED' });
                            return;
                        }
                        $.ajax({
                            type: "POST",
                            url: $.service("webauthn", "LoginStart"),
                            data: {},
                            success: async function (data) {
                                if (data.status === "success") {
                                    const res = data.options;
                                    res.challenge = Uint8Array.from(atob(res.challenge.replace(/-/g, "+").replace(/_/g, "/")), (c) =>
                                        c.charCodeAt(0)
                                    ).buffer;
                                    if (res.allowCredentials && res.allowCredentials.length > 0) {
                                        res.allowCredentials.forEach((cred) => {
                                            cred.id = Uint8Array.from(atob(cred.id.replace(/-/g, "+").replace(/_/g, "/")), (c) =>
                                                c.charCodeAt(0)
                                            ).buffer;
                                        });
                                    }
                                    try {
                                        const credential = await navigator.credentials.get({ publicKey: res });
                                        await $.fn.loginWebAuth.loginFinish(credential, module_logo_instance, form);
                                        resolve(true);
                                    } catch (error) {
                                        reject(classifyWebAuthnError(error, 'login'));
                                    }
                                } else {
                                    reject({ message: data.message || credsVerifyFailure, code: data.code || 'SERVER_ERROR' });
                                }
                            },
                            error: function (req, status, error) {
                                reject({ message: error || credsVerifyFailure, code: 'NETWORK_ERROR' });
                            },
                        });
                    } catch (err) {
                        reject(classifyWebAuthnError(err, 'login'));
                    }
                });
            },
            loginFinish: function (cred, module_logo_instance = 0, form) {
                return new Promise((resolve, reject) => {
                    const assertion = cred.response;
                    $.ajax({
                        type: "POST",
                        url: $.service("webauthn", "LoginFinish"),
                        data: {
                            rawId: btoa(String.fromCharCode(...new Uint8Array(cred.rawId))),
                            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(assertion.clientDataJSON))),
                            authenticatorData: btoa(String.fromCharCode(...new Uint8Array(assertion.authenticatorData))),
                            signature: btoa(String.fromCharCode(...new Uint8Array(assertion.signature))),
                        },
                        success: async function (data) {
                            if (data.status === "success") {
                                $("#webauthn_checkbox_" + form).prop("checked", false);
                                $("#webauthn_checkbox_" + form).attr("is_passed", "y");
                                resolve(true);
                            } else {
                                reject(data?.message || credsVerifyFailure);
                            }
                        },
                        error: function (req, status, error) {
                            reject(error?.message || credsVerifyFailure);
                        },
                    });
                });
            },
        },
    });

    window.tikiWebAuthn = $.fn.loginWebAuth;
})(jQuery);
