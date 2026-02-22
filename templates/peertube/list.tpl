{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
  <div class="peertube-media-list">
      {foreach $entries as $item}
          {assign var=vid value=$item->shortUUID|default:$item->uuid|default:$item->id}
          <div class="d-flex media" data-id="{$vid}" data-name="{$item->name|default:$item->title|escape}">
              <div class="mb-2">
                {if $item->thumbUrl}
                  <img class="border rounded" src="{$item->thumbUrl|escape}" alt="{$item->description|default:''|escape}" height="65" width="110">
                {else}
                  <div class="bg-light border rounded d-inline-block" style="width:110px;height:65px;"></div>
                {/if}
              </div>
              <div class="flex-grow-1 d-flex align-items-center ms-3">
                  <p>{$item->name|default:$item->title|escape}</p>
              </div>
          </div>
      {/foreach}
  </div>

  <script>
    $(function() {
        // Debug: Check if script runs
        console.log("PeerTube List Script Loaded (via script tag)");

        // Use event delegation to handle clicks on dynamically loaded content
        $(document).on("click", ".peertube-media-list .media", function (e) {
            // Debug: Check if click fires
            console.log("PeerTube Item Clicked");
            e.preventDefault();
            e.stopPropagation();
            
            // Use .attr() instead of .data() to ensure we get raw string values
            var videoId = String($(this).attr('data-id') || '');
            var videoName = $(this).attr('data-name') || videoId;
            var targetName = '{$targetName|escape:"javascript"}';
            
            console.log('PeerTube: Clicked video', videoId, 'Target:', targetName);

            if (!videoId || !targetName) {
                console.error('PeerTube: Missing videoId or targetName');
                return;
            }
            
            // Find the list button to locate the parent container
            var $listButton = $('a.list-peertube-media').filter(function() {
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
                
                // Ensure the ol is inside a form
                var $form = $ol.closest('form');
                if ($form.length === 0) {
                    // If ol is not in a form, find the form and move ol into it
                    $form = $('input[name="' + targetName + '[]"]').closest('form');
                    if ($form.length === 0) {
                        $form = $('form').first();
                    }
                    if ($form.length > 0 && $ol.parent().length === 0) {
                        // Move ol into form if it's not already there
                        $form.find('input[name="' + targetName + '[]"]').first().before($ol);
                    }
                }
                
                // Check if this video is already in the list
                var existing = $ol.find('input[type="checkbox"][name="' + targetName + '[]"][value="' + videoId + '"]');
                if (existing.length > 0) {
                    // Video already added, just close modal
                    $.closeModal();
                    return;
                }
                
                // Add the video to the list (matching the structure in trackerinput/peertube.tpl)
                // Generate unique ID for checkbox
                var checkboxId = targetName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + videoId.replace(/[^a-zA-Z0-9]/g, '_') + '_' + Date.now();
                
                // Note: We need the remove icon here. Since this TPL is loaded via AJAX in modal, capturing it here works.
                var removeIcon = '{{icon name="remove"}}'; // This renders as <span...> because we are in TPL

                var $li = $('<li>')
                    .append($('<input type="checkbox" class="form-check-input d-none">')
                        .attr('name', targetName + '[]')
                        .attr('id', checkboxId)
                        .attr('value', videoId)
                        .prop('checked', true))
                    // No old_ input for new items
                    .append($('<a href="#" class="text-danger remove-peertube-item" title="{tr}Remove{/tr}">' + removeIcon + '</a>'))
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
                // Fallback: if we can't find the button, find form and add checkbox directly
                var $form = $('input[name="' + targetName + '[]"]').closest('form');
                if ($form.length === 0) {
                    $form = $('form').first();
                }
                if ($form.length > 0) {
                    var checkboxId = targetName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + videoId.replace(/[^a-zA-Z0-9]/g, '_') + '_' + Date.now();
                    var $checkbox = $('<input type="checkbox" class="form-check-input d-none">')
                        .attr('name', targetName + '[]')
                        .attr('id', checkboxId)
                        .attr('value', videoId)
                        .prop('checked', true);
                    $form.append($checkbox);
                }
            }

            // Close the Bootstrap modal
            $.closeModal();
        });
        
        // Make items look clickable
        $(".peertube-media-list .media").css("cursor", "pointer");
    });
  </script>
{/block}
