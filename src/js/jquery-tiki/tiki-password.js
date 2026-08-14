$(document).ready(function () {
    /*
        This script adds toggle icons to password fields to allow users to show or hide their password input.

        Functions:
        1. addToggleIconsToPasswords()
            - Selects all elements with the class 'tiki-password'.
            - Wraps each password field in a container div.
            - Adds a toggle icon (eye icon) next to each password field.
            - Sets up event listeners to toggle password visibility and to show/hide the icon based on input.

        2. togglePasswordVisibility($passwordInput, $toggleIcon)
            - Toggles the type attribute of the password input between 'password' and 'text'.
            - Changes the icon class between 'fa-eye' and 'fa-eye-slash' to reflect the current state.

        Usage:
        - Ensure jQuery is included in your project.
        - Include Font Awesome for the eye icons.
        - Call addToggleIconsToPasswords() to initialize the functionality.
    */

    function addToggleIconsToPasswords() {
        $(".tiki-password").each(function () {
            const $passwordField = $(this);
            const $passwordContainer = $("<div>", { class: "password-container" });
            $passwordField.before($passwordContainer);
            $passwordContainer.append($passwordField);
            const $toggleIcon = $("<i>", { class: "fas fa-eye" });
            $toggleIcon.on("click", function () {
                togglePasswordVisibility($passwordField, $toggleIcon);
            });
            $passwordField.on("input", function () {
                $toggleIcon.css("visibility", $passwordField.val() ? "visible" : "hidden");
            });
            $passwordContainer.append($toggleIcon);
        });
    }

    function togglePasswordVisibility($passwordInput, $toggleIcon) {
        if ($passwordInput.attr("type") === "password") {
            $passwordInput.attr("type", "text");
            $toggleIcon.removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            $passwordInput.attr("type", "password");
            $toggleIcon.removeClass("fa-eye-slash").addClass("fa-eye");
        }
    }

    addToggleIconsToPasswords();
});
