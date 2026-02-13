{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
<div class="kaltura-media-list">
    {foreach $entries as $item}
        <div class="d-flex media mb-3" data-id="{$item->id}" data-name="{$item->name|escape}">
            <div class="mb-2">
                <img class="border rounded" src="{$item->thumbnailUrl}" alt="{$item->description|default:''|escape}" height="65" width="110">
            </div>
            <div class="flex-grow-1 d-flex align-items-center ms-3">
                <p class="mb-0"><strong>{$item->name|escape}</strong></p>
            </div>
        </div>
    {/foreach}
</div>

<script>
    $(function() {
        // Use event delegation to handle clicks on dynamically loaded content
        $(document).on("click", ".kaltura-media-list .media", function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Use .attr() instead of .data() to ensure we get raw string values
            // jQuery's .data() can auto-parse values which may cause issues
            var videoId = String($(this).attr('data-id') || '');
            var videoName = $(this).attr('data-name') || videoId;
            var targetName = '{$targetName|escape:"javascript"}';

            if (!videoId || !targetName) {
                console.error('Missing videoId or targetName');
                return;
            }

            // Find the form that contains the tracker field
            // The targetName matches the field's html_name (e.g., ins_123)
            var $form = $('input[name="' + targetName + '"], input[name="' + targetName + '[]"], input[type="checkbox"][name="' + targetName + '"]').closest('form');

            if ($form.length === 0) {
                // Fallback: find the main form on the page
                $form = $('form').first();
            }

            if ($form.length === 0) {
                console.error('Could not find form');
                return;
            }

            // Find the list button to locate the parent container
            var $listButton = $('a.list-kaltura-media').filter(function() {
                var href = $(this).attr('href') || '';
                return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 || 
                       href.indexOf('targetName=' + targetName) !== -1;
            });

            if ($listButton.length > 0) {
                var $parent = $listButton.parent();
                var $ol = $parent.find('ol').first();

                // If no ol exists, create one before the buttons
                if ($ol.length === 0) {
                    $ol = $('<ol class="list-unstyled"></ol>');
                    $listButton.before($ol);
                }

                // Check if this video is already in the list
                var existing = $ol.find('input[value="' + videoId + '"]');
                if (existing.length > 0) {
                    // Video already added, just close modal
                    $.closeModal();
                    return;
                }

                // Add the video to the list (matching the structure in trackerinput/kaltura.tpl)
                var checkboxId = targetName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + String(videoId).replace(/[^a-zA-Z0-9]/g, '_') + '_' + Date.now();
                var removeIcon = '{{icon name="remove"}}';

                var $li = $('<li>')
                    .append($('<input type="checkbox" class="form-check-input d-none">')
                        .attr('name', targetName + '[]')
                        .attr('id', checkboxId)
                        .attr('value', videoId)
                        .prop('checked', true))
                    // No old_ input for new items
                    .append($('<a href="#" class="text-danger remove-kaltura-item" title="{tr}Remove{/tr}">' + removeIcon + '</a>'))
                    .append($('<label class="form-check-label ms-1">')
                        .attr('for', checkboxId)
                        .text(videoName));
                $ol.append($li);
                
                // Update the highlight count if it exists
                var $highlight = $parent.find('span.highlight');
                if ($highlight.length > 0) {
                    var count = $ol.find('input[type="checkbox"][name="' + targetName + '[]"]').length;
                    $highlight.text('+' + count);
                }
            } else {
                // If we can't find the button, just add a hidden input to the form
                // This ensures the video ID is saved even if the UI doesn't update
                var $hidden = $('<input type="hidden">')
                    .attr('name', targetName + '[]')
                    .attr('value', videoId);
                $form.append($hidden);
            }
            
            // Close the Bootstrap modal
            $.closeModal();
        });
        
        // Make items look clickable
        $(".kaltura-media-list .media").css("cursor", "pointer");
    });
</script>
{/block}
