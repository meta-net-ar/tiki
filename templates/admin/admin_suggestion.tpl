{if $tikiShowSuggestionsPopup}
    <div id="suggestionsPopup" class="alert alert-info alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <h4 class="alert-heading">
            <span class="icon icon-information bi bi-info-circle "></span> <span>{tr}Tiki Suggestions{/tr}</span>
        </h4>
        <p>{tr}Do you need help with your Tiki?{/tr}<br/>
                {tr}You can reach out to a specialist:{/tr}
                <a target="_blank" class="alert-link" title="{tr}Tiki Consultants{/tr}" href="https://tiki.org/Consultants">https://tiki.org/Consultants</a>
        </p>
    </div>
{/if}

{jq}
    $(function() {
        $('.close').on("click", function() {
            let buttonId = $(this).attr('id');
            let warningTitle = $(this).siblings('.alert-heading').children('.rboxtitle').html();
            let warningTitleCheck = "{tr}Tiki Suggestions{/tr}";
            if (warningTitle == warningTitleCheck) {
                $.ajax({
                    url: 'tiki-admin.php',
                    data: {
                        tikiSuggestion: false
                    }
                });
            }
            if (buttonId == 'suggestionsClosePopup') {
                $('#suggestionsPopup').hide();
                $.ajax({
                    url: 'tiki-admin.php',
                    data: {
                        tikiSuggestionPopup: false
                    }
                });
            }
        });
    });
{/jq}
