{tikimodule error=$module_params.error title=$tpl_module_title name="zotero" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    <form class="zoterosearch" method="post" action="tiki-ajax_services.php">
        <div class="input-group">
            <input type="text" name="zotero_tags" placeholder="Enter a tag to filter (optional)"/>
            <button type="submit" class="btn btn-info btn-sm" name="zotero">
                <i class="fas fa-search"></i> {tr}Search{/tr}
            </button>
        </div>
        <small class="form-text text-muted">
            Leave the field empty to see all your references, or enter a tag to filter
        </small>
        <div class="results-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 10px; background-color: #f8f9fa;">
            <ul class="results list-unstyled mb-0">
            </ul>
        </div>
        <div class="alert alert-warning">
            {remarksbox type="errors" title="{tr}No results!{/tr}"}
                <p>{tr}No results were found. Are you sure you searched for a tag?{/tr}</p>

                {if ! $zotero_authorized}
                    <p>{tr}We are not autorized to access the group at this time. If you have access to the Zotero group, you can grant this site read access.{/tr}</p>
                    <p><a href="{service controller=oauth action=request provider=zotero}" class="alert-link">{tr}Authenticate with Zotero{/tr}</a></p>
                {/if}
            {/remarksbox}
        </div>
    </form>
    {jq}
    $('.zoterosearch:not(.done)').addClass('done')
        .find('.results-container, .alert').hide().end()
        .on("submit", function (e) {
            var form = this;

            e.preventDefault();
            $.ajax({
                url: this.action,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (data) {
                    var isError = data.type == 'unauthorized' || data.type == 'no_results';
                    $('.alert', form).toggle(isError || data.results.length === 0);
                    $('.results-container', form).toggle(! isError);
                                        $('.results', form).empty();
                    
                    if (data.results.length > 0) {
                        $('.results', form).prepend('<div class="alert alert-success mb-3">Found ' + data.results.length + ' reference(s)</div>');
                    }

                    $.each(data.results, function (k, i) {
                        var entry = $('<li/>').hide().addClass('mb-3 p-2 border-bottom'), link = $('<strong/>');
                        
                        var contentDiv = $('<div/>').addClass('mt-2');
                        if (i.content) {
                            contentDiv.append($('<div/>').html(i.content));
                        }
                        
                        var zoteroLink = $('<a/>').text('View in Zotero').attr('href', i.url).addClass('btn btn-sm btn-outline-primary me-2');
                        var copyInput = $('<input type="text"/>').val('{zotero key=' + i.key + '}').addClass('form-control form-control-sm d-inline-block w-auto me-2');
                        entry.append(contentDiv).append(zoteroLink).append(copyInput);
                        link.text(i.title);
                        link.css('cursor', 'pointer').addClass('text-primary d-block mb-1');
                        link.on("click", function () {
                            entry.toggle();
                            copyInput.trigger("select").trigger("focus");
                        });
                        
                        $('.results', form).append(link).append(entry);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Zotero search error:', error);
                    $('.alert', form).show();
                    $('.results-container', form).hide();
                }
            });

            return false;
        });
    {/jq}
{/tikimodule}
