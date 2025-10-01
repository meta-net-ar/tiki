var toast_notifications = 0;
var toast_pending_callback = {};

function completeToastClosed(id) {
    // Call the close callback function if provided
    if (toast_pending_callback.hasOwnProperty(id) && typeof toast_pending_callback[id].onClose === "function") {
        toast_pending_callback[id].onClose();
        delete toast_pending_callback[id];
    }

    (function () {
        setTimeout(function () {
            $(`#${id}`).remove();
        }, 1000);
    })();
}

/**
 * Display a toast notification with customizable options.
 * @example
 *
 * $.fn.toastNotification("hello!"); //will show a toast notification at the top-left of the page with light background
 * $.fn.toastNotification({body: "I have a title", title:"Hello!"});
 * $.fn.toastNotification({body:"hello",containerClasses:"bg-danger text-white"}) //will show a toast notification with the container having bg-danger & text-white classes, containerClasses can be anything you want not just bootstrap ones
 * $.fn.toastNotification({body:"hello",containerClasses:"bg-danger",delay:-1}) //this toast will not close automatically
 * $.fn.toastNotification({
        body: "This toast notification will be closed after 5 seconds",
        position: "top-center",
        delay: 5000,
        containerClasses: "bg-danger text-light",
        onClose: function() {
            console.log("Toast notification closed!");
        }
    }); // example with callback

 * @param {string|Object} options - The options for customizing the toast notification. This can either be a string representing the body of the notification or an object with the following properties:
 * @param {string} [options.body=""] - The content of the toast notification.
 * @param {string|null} [options.title=null] - The title of the toast notification.
 * @param {string} [options.position="top-end"] - The position of the toast notification. Possible values: "top-end", "top-start", "bottom-end", "bottom-start", "top-center", "middle-start", "middle-center", "middle-end", "bottom-center".
 * @param {number|string} [options.delay="5000"] - The delay before the toast notification disappears (in milliseconds). Use "-1" if notification should be closed manually.
 * @param {string} [options.containerClasses=""] - Additional CSS classes for styling the toast notification container.
 * @param {string} [options.style=""] - Inline CSS styles for styling the toast notification.
 * @param {Function} [options.onClose=null] - Callback function to be invoked when the toast notification is closed.
 * @throws {Error} - Throws an error if options provided are not valid.
 */

$.fn.toastNotification = function (options) {
    // Default options
    const defaultOptions = {
        body: "",
        title: null,
        position: "top-end",
        delay: "5000",
        autohide: options.delay == -1 ? false : true,
        containerClasses: "",
        style: "",
        onClose: null, // Close callback function
    };

    // Merge user-provided options with default options
    if (typeof options === "object") options = { ...defaultOptions, ...options };
    else if (typeof options === "string") options = { ...defaultOptions, body: options };
    else throw new Error(`Invalid option provided, options should be a string or an object,${typeof options} provided`);

    let positions_classes = {
        "top-end": "top-0 end-0",
        "top-start": "top-0 start-0",
        "bottom-end": "bottom-0 end-0",
        "bottom-start": "bottom-0 start-0",
        "top-center": "top-0 start-50 translate-middle-x",
        "middle-start": "top-50 start-0 translate-middle-y",
        "middle-center": "top-50 start-50 translate-middle",
        "middle-end": "top-50 end-0 translate-middle-y",
        "bottom-center": "bottom-0 start-50 translate-middle-x",
    };

    let [position_key, current_position] =
        options.position && positions_classes.hasOwnProperty(options.position)
            ? [options.position, positions_classes[options.position]]
            : ["top-right", "top-0 end-0"];
    let classes_array = current_position.split(" ");
    let toastwrapper = $("body").find(`.toast-wrapper .toast-container.${position_key}`); //position classes pair help us have different containers per position
    if (!toastwrapper.length) {
        $("body").append(
            `<div aria-live="polite" aria-atomic="true" class="position-relative toast-wrapper"><div class="toast-container position-fixed ${position_key} ${current_position} p-3"></div></div>`
        );
        toastwrapper = $("body").find(`.toast-wrapper .toast-container.${classes_array[0]}.${classes_array[1]}`);
    }
    const currentid = `liveToast-${toast_notifications + 1}`;
    const toastElement = $(
        `<div id="${currentid}" class="toast" data-bs-delay="${options.delay}" data-bs-autohide="${options.autohide ? "true" : "false"}" role="alert" aria-live="assertive" aria-atomic="true"></div>`
    );
    if (options.title) {
        if (options.containerClasses) toastElement.addClass(options.containerClasses);
        if (options.style) toastElement.attr("style", options.style);
        toastElement.html(`
            <div class="toast-header">
                <i class="bi bi-info"></i>
                <strong class="me-auto">${options.title}</strong>
                <button style="background-color:white; padding:6px;" type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${options.body}
            </div>`);
    } else {
        if (options.containerClasses) toastElement.addClass(options.containerClasses);
        if (options.style) toastElement.attr("style", options.style);
        toastElement.addClass("align-items-center");
        toastElement.html(`
            <div class="d-flex">
                <div class="toast-body">
                    ${options.body}
                </div>
                <button style="background-color:white; padding:6px;" type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`);
    }
    toastwrapper.prepend(toastElement);
    toastElement.on("hidden.bs.toast", function () {
        completeToastClosed($(this).attr("id"));
    });
    const toastBootstrap = window.bootstrap.Toast.getOrCreateInstance($(`#${currentid}`).get(0));
    toastBootstrap.show();
    toast_notifications += 1;
    toast_pending_callback[currentid] = {};
    toast_pending_callback[currentid].onClose = options.onClose;
};
