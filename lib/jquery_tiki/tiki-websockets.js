function initTikiGlobalWS() {
    $(function() {
        if ($('#ws-response-container').length == 0) {
            checkRunningCommands();
        }
    });

    function checkRunningCommands() {
        var tikiConsoleWS = tikiOpenWS("console");
        tikiConsoleWS.onmessage = function(e) {
            if (parseInt(e.data) > 0) {
                // TODO: consider placing this in a separate page and styling it better (e.g. max height and vertical scroll)
                feedback(tr("<p>Background commands are running. <a href='#' id='ws-response-toggle'>Click here</a> to check their progress.</p>")+'<div class="rounded bg-dark text-light p-3" style="display:none" id="ws-response-container"></div>');
                $('#ws-response-toggle').on('click', function(e) {
                    e.preventDefault();
                    $(this).closest('p').hide();
                    $('#ws-response-container').show();
                    return false;
                });
            } else {
                $('#ws-response-container').append(e.data.trim().replaceAll("\n", "<br>\n") + "<br>");
            }
        };
        tikiConsoleWS.onopen = function(e) {
            tikiConsoleWS.send("attach");
        };
    }
}
