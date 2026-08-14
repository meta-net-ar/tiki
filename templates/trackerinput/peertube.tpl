<ol class="list-unstyled">
    {foreach from=$data.videos item=video name=vidloop}
        {assign var=vid value=$video.shortUUID|default:$video.uuid|default:$video.id}
        <li>
            <input type="checkbox" class="form-check-input d-none" name="{$field.html_name|escape}[]" id="{$field.ins_id|escape}_{$smarty.foreach.vidloop.index}" value="{$vid|escape}" checked="checked">
            <input type="hidden" name="old_{$field.html_name|escape}" value="{$vid|escape}">
            <a href="#" class="text-danger remove-peertube-item" title="{tr}Remove{/tr}">
                {icon name="remove"}
            </a>
            <label for="{$field.ins_id|escape}_{$smarty.foreach.vidloop.index}" class="form-check-label">
                {$video.name|default:$video.title|escape}
            </label>
        </li>
    {/foreach}
</ol>

<a class="add-peertube-media btn btn-primary btn-sm" href="{bootstrap_modal controller=peertube action=upload targetName="{$field.html_name}"}" role="button">{tr}Add Media{/tr}</a>
<a class="list-peertube-media btn btn-primary btn-sm" href="{bootstrap_modal controller=peertube action=list targetName="{$field.html_name}"}" role="button">{tr}List Media{/tr}</a>

{foreach from=$data.extras item=entryId}
    <input type="hidden" name="{$field.html_name|escape}[]" value="{$entryId|escape}">
{/foreach}
{if $data.extras|count}
    <span class="highlight">+{$data.extras|count}</span>
{/if}

{capture assign=remove_icon}{icon name="remove"}{/capture}
<script>
    $(function() {
        var targetName = '{$field.html_name|escape}';
        var removeIcon = '{$remove_icon|escape:"javascript"}';
        var $addButton = $('.add-peertube-media').filter(function() {
            var href = $(this).attr('href') || '';
            return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                   href.indexOf('targetName=' + targetName) !== -1;
        });

        var $listButton = $('.list-peertube-media').filter(function() {
            var href = $(this).attr('href') || '';
            return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                   href.indexOf('targetName=' + targetName) !== -1;
        });

        // Delegate click event for remove buttons (handles both existing and new items)
        $(document).on('click', '.remove-peertube-item', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            // Uncheck the checkbox
            $li.find('input[type="checkbox"]').prop('checked', false);
            // Hide the item visually
            $li.hide();
            // Update count if needed (optional optimization, backend handles it via unchecked box)
        });

        // Store the success callback
        var uploadSuccessCallback = function (data) {
            if (!data.entries || data.entries.length === 0) {
                return;
            }

            // Find the add button to locate the parent container
            var $addButton = $('.add-peertube-media').filter(function() {
                var href = $(this).attr('href') || '';
                return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                       href.indexOf('targetName=' + targetName) !== -1;
            });

            // Use list button as fallback anchor if add button not found (though unusual)
            var $anchor = $addButton.length ? $addButton : $('.list-peertube-media').filter(function() {
                 var href = $(this).attr('href') || '';
                 return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                        href.indexOf('targetName=' + targetName) !== -1;
            });

            if ($anchor.length === 0) {
                return;
            }

            var $parent = $anchor.parent();
            var $listButton = $('a.list-peertube-media').filter(function() {
                var href = $(this).attr('href') || '';
                return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                       href.indexOf('targetName=' + targetName) !== -1;
            });
            
            // ensure $addButton is defined correctly for positioning if we used fallback
            if ($addButton.length === 0 && $listButton.length > 0) {
                 // If we only have list button, we position relative to it
                 $addButton = $listButton; 
            }

            // Find or create the ol element
            var $ol = $parent.find('ol').first();
            if ($ol.length === 0) {
                // Create ol before the buttons
                $ol = $('<ol class="list-unstyled"></ol>');
                if ($listButton.length > 0) {
                    $listButton.before($ol);
                } else {
                    $addButton.before($ol);
                }
            }

            // Add each uploaded video
            $.each(data.entries, function(k, videoId) {
                // Check if this video is already in the list
                var existing = $ol.find('input[value="' + videoId + '"]');
                if (existing.length > 0) {
                    // If it exists but was "removed" (unchecked and hidden), revive it
                    if (!existing.prop('checked')) {
                        existing.prop('checked', true);
                        existing.closest('li').show();
                    }
                    return; // Skip if already added
                }

                // Get video name if available
                var videoName = (data.entryNames && data.entryNames[k]) ? data.entryNames[k] : videoId;

                // Generate unique ID for checkbox
                var checkboxId = targetName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + videoId.replace(/[^a-zA-Z0-9]/g, '_') + '_' + Date.now() + '_' + k;
                var $li = $('<li>')
                    .append($('<input type="checkbox" class="form-check-input d-none">')
                        .attr('name', targetName + '[]')
                        .attr('id', checkboxId)
                        .attr('value', videoId)
                        .prop('checked', true))
                    .append($('<a href="#" class="text-danger remove-peertube-item" title="{tr}Remove{/tr}">' + removeIcon + '</a>'))
                    .append($('<label class="form-check-label ms-1">')
                        .attr('for', checkboxId)
                        .text(videoName));
                $ol.append($li);
            });

            // Update the highlight count if it exists
            var $highlight = $parent.find('span.highlight');
            var count = $ol.find('input[type="checkbox"][name="' + targetName + '[]"]:checked').length;
            if ($highlight.length > 0) {
                $highlight.text('+' + count);
            } else if (count > 0) {
                if ($listButton.length > 0) {
                    $listButton.after($('<span class="highlight">+' + count + '</span>'));
                } else {
                    $addButton.after($('<span class="highlight">+' + count + '</span>'));
                }
            }
        };

        // Listen for upload success event from the upload form (both generic and specific)
        var eventName = 'peertube.upload.success.' + targetName.replace(/[^a-zA-Z0-9]/g, '_');
        $(document).off(eventName).on(eventName, uploadSuccessCallback);
        // Also listen to generic event as fallback
        $(document).off('peertube.upload.success.' + targetName).on('peertube.upload.success.' + targetName, function(e, data) {
            // Only handle if targetName matches
            if (data.targetName === targetName) {
                uploadSuccessCallback(data);
            }
        });

        $addButton.clickModal({
            title: function() { return $(this).text(); },
            success: uploadSuccessCallback
        });
        
        $listButton.clickModal({
            title: function() { return $(this).text(); },
            success: uploadSuccessCallback
        });
    });
</script>
