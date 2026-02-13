<ol class="list-unstyled">
    {foreach from=$data.movies item=movie name=movieloop}
        <li>
            <input type="checkbox" class="form-check-input d-none" name="{$field.html_name|escape}[]" id="{$field.ins_id|escape}_{$smarty.foreach.movieloop.index}" value="{$movie.id|escape}" checked="checked">
            <input type="hidden" name="old_{$field.html_name|escape}" value="{$movie.id|escape}">
            <a href="#" class="text-danger remove-kaltura-item" title="{tr}Remove{/tr}">
                {icon name="remove"}
            </a>
            <label for="{$field.ins_id|escape}_{$smarty.foreach.movieloop.index}" class="form-check-label">
                {$movie.name|escape}
            </label>
        </li>
    {/foreach}
</ol>
<a class="add-kaltura-media btn btn-primary btn-sm" href="{bootstrap_modal controller=kaltura action=upload targetName="{$field.html_name}"}" role="button">{tr}Add Media{/tr}</a>
<a class="list-kaltura-media btn btn-primary btn-sm" href="{bootstrap_modal controller=kaltura action=list targetName="{$field.html_name}"}" role="button">{tr}List Media{/tr}</a>
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
        var $addButton = $('.add-kaltura-media').filter(function() {
            var href = $(this).attr('href') || '';
            return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                   href.indexOf('targetName=' + targetName) !== -1;
        });

        var $listButton = $('.list-kaltura-media').filter(function() {
            var href = $(this).attr('href') || '';
            return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                   href.indexOf('targetName=' + targetName) !== -1;
        });

        // Delegate click event for remove buttons
        $(document).on('click', '.remove-kaltura-item', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            $li.find('input[type="checkbox"]').prop('checked', false);
            $li.hide();
        });

        var uploadSuccessCallback = function (data) {
            if (!data.entries || data.entries.length === 0) {
                return;
            }

            var $addButton = $('.add-kaltura-media').filter(function() {
                var href = $(this).attr('href') || '';
                return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                       href.indexOf('targetName=' + targetName) !== -1;
            });

            // Use list button as fallback anchor
            var $anchor = $addButton.length ? $addButton : $('.list-kaltura-media').filter(function() {
                 var href = $(this).attr('href') || '';
                 return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                        href.indexOf('targetName=' + targetName) !== -1;
            });

            if ($anchor.length === 0) {
                return;
            }

            var $parent = $anchor.parent();
            var $listButton = $('a.list-kaltura-media').filter(function() {
                var href = $(this).attr('href') || '';
                return href.indexOf('targetName=' + encodeURIComponent(targetName)) !== -1 ||
                       href.indexOf('targetName=' + targetName) !== -1;
            });

             if ($addButton.length === 0 && $listButton.length > 0) {
                 $addButton = $listButton; 
            }

            var $ol = $parent.find('ol').first();
            if ($ol.length === 0) {
                $ol = $('<ol class="list-unstyled"></ol>');
                if ($listButton.length > 0) {
                    $listButton.before($ol);
                } else {
                    $addButton.before($ol);
                }
            }

            $.each(data.entries, function(k, videoId) {
                var existing = $ol.find('input[value="' + videoId + '"]');
                if (existing.length > 0) {
                    if (!existing.prop('checked')) {
                        existing.prop('checked', true);
                        existing.closest('li').show();
                    }
                    return;
                }

                var videoName = (data.entryNames && data.entryNames[k]) ? data.entryNames[k] : videoId;
                var checkboxId = targetName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + String(videoId).replace(/[^a-zA-Z0-9]/g, '_') + '_' + Date.now() + '_' + k;
                var $li = $('<li>')
                    .append($('<input type="checkbox" class="form-check-input d-none">')
                        .attr('name', targetName + '[]')
                        .attr('id', checkboxId)
                        .attr('value', videoId)
                        .prop('checked', true))
                    .append($('<a href="#" class="text-danger remove-kaltura-item" title="{tr}Remove{/tr}">' + removeIcon + '</a>'))
                    .append($('<label class="form-check-label ms-1">')
                        .attr('for', checkboxId)
                        .text(videoName));
                $ol.append($li);
            });

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
