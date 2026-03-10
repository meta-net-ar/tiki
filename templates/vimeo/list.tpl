{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
  <div class="vimeo-media-list">
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
        console.log("Vimeo List Script Loaded (via script tag)");

        $(document).on("click", ".vimeo-media-list .media", function (e) {
            console.log("Vimeo Item Clicked");
            e.preventDefault();
            e.stopPropagation();
            
            var videoId = String($(this).attr('data-id') || '');
            var videoName = $(this).attr('data-name') || videoId;
            var targetName = '{$targetName|escape:"javascript"}';
            
            console.log('Vimeo: Clicked video', videoId, 'Target:', targetName);

            if (!videoId || !targetName) {
                console.error('Vimeo: Missing videoId or targetName');
                return;
            }
            
            var $listButton = $('a.list-vimeo-media').filter(function() {
                var href = $(this).attr('href') || '';
                return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 || 
                       href.indexOf('targetName=' + targetName) !== -1;
            });
            
            if ($listButton.length > 0) {
                var $parent = $listButton.parent();
                var $ol = $parent.find('ol').first();
                
                if ($ol.length === 0) {
                    $ol = $('<ol class="list-unstyled"></ol>');
                    $listButton.before($ol);
                }
                
                var $form = $ol.closest('form');
                if ($form.length === 0) {
                    $form = $('input[name="' + targetName + '[]"]').closest('form');
                    if ($form.length === 0) {
                        $form = $('form').first();
                    }
                    if ($form.length > 0 && $ol.parent().length === 0) {
                        $form.find('input[name="' + targetName + '[]"]').first().before($ol);
                    }
                }
                
                var existing = $ol.find('input[type="checkbox"][name="' + targetName + '[]"][value="' + videoId + '"]');
                if (existing.length > 0) {
                    $.closeModal();
                    return;
                }
                
                var checkboxId = targetName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + videoId.replace(/[^a-zA-Z0-9]/g, '_') + '_' + Date.now();
                var removeIcon = '{icon name="remove"}'; 

                var $li = $('<li>')
                    .append($('<input type="checkbox" class="form-check-input d-none">')
                        .attr('name', targetName + '[]')
                        .attr('id', checkboxId)
                        .attr('value', videoId)
                        .prop('checked', true))
                    .append($('<a href="#" class="text-danger remove-vimeo-item" title="{tr}Remove{/tr}">' + removeIcon + '</a>'))
                    .append($('<label class="form-check-label ms-1">')
                        .attr('for', checkboxId)
                        .text(videoName));
                $ol.append($li);
                
                var $highlight = $parent.find('span.highlight');
                if ($highlight.length > 0) {
                    var count = $ol.find('input[type="checkbox"][name="' + targetName + '[]"]').length;
                    $highlight.text('+' + count);
                }
            } else {
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

            $.closeModal();
        });
        
        $(".vimeo-media-list .media").css("cursor", "pointer");
    });
  </script>
{/block}
