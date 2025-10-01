// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
(function ($) {

    const deviceRegFailure = tr("We couldn't register your device. Please ensure your device supports WebAuthn and try again. If the issue persists, contact support.");
    const credsVerifyFailure = tr("The credential verification step failed. Please check your device and try again. If the issue persists, contact support.");
    const usernameRequiredMsg = tr("Username is required.");

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
                                        reject(error?.message || deviceRegFailure);
                                    }
                                } else {
                                    reject(data.message);
                                }
                            },
                            error: function (req, status, error) {
                                reject(error?.message || deviceRegFailure);
                            },
                        });
                    } catch (err) {
                        reject(err?.message || deviceRegFailure);
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
                                reject(error?.message || deviceRegFailure);
                            },
                        });
                    } catch (err) {
                        reject(err?.message || deviceRegFailure);
                    }
                });
            },
        },
        loginWebAuth: {
            loginStart: async function (event, module_logo_instance = 0, form) {
                return new Promise(async (resolve, reject) => {
                    try {
                        event.preventDefault();
                        if ($("#webauthn_checkbox_" + form).is(":checked")) {
                            if (!$("#login-user_" + module_logo_instance).val()) {
                                $("#login-user_" + module_logo_instance).focus();
                                resolve(usernameRequiredMsg);
                                return;
                            }
                        }
                        const authUser = $("#login-user_" + module_logo_instance).val();
                        $.ajax({
                            type: "POST",
                            url: $.service("webauthn", "LoginStart"),
                            data: { username: authUser },
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
                                        reject(credsVerifyFailure);
                                    }
                                } else {
                                    reject(data?.message || credsVerifyFailure);
                                }
                            },
                            error: function (req, status, error) {
                                reject(error?.message || credsVerifyFailure);
                            },
                        });
                    } catch (err) {
                        reject(err?.message || credsVerifyFailure);
                    }
                });
            },
            loginFinish: function (cred, module_logo_instance = 0, form) {
                return new Promise((resolve, reject) => {
                    const assertion = cred.response;
                    const authUser = $("#login-user_" + module_logo_instance).val();
                    $.ajax({
                        type: "POST",
                        url: $.service("webauthn", "LoginFinish"),
                        data: {
                            username: authUser,
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
})(jQuery);
