<div class="icon-selector-container">
    <input type="hidden" name="{$field.ins_id|escape}" value="{$field.value|escape}">
    <img class="icon" src="{$field.value|escape}" role="button" alt="{tr}Select Icon{/tr}">
    <div class="selector" style="display: none;">
        <div class="sections" style="float: left; width: 25%;">
            <ul class="list-group pe-3">
                {foreach from=$data.galleries item=gal}
                    <li class="list-group-item">
                        <a href="{$gal.url|escape}">{tr}{$gal.label|escape}{/tr}</a>
                    </li>
                {/foreach}
            </ul>
        </div>
        <div class="contents" style="float: left; width: 75%; max-height: 600px;">
        </div>
    </div>
</div>

{jq}
    {literal}
    $('.icon-selector-container').removeClass('icon-selector-container').each(function () {
        const icon = $('.icon', this);
        const field = $(':input', this);
        let jqxhr;
        icon.on("click", () => {
             $.openModal({
                title: icon.attr('alt'),
                size: 'modal-lg',
                dialogVariants: ['scrollable'],
                content: $('.selector', this).html(),
                open: function () {
                    const contents = $('.contents', this);
                    $('.sections a', this).css('display', 'block').on("click",function () {
                        contents.empty().append($('{/literal}{icon name='spinner' iclass='fa-spin'}{literal}'));
                        if (jqxhr) {
                            jqxhr.abort();
                        }
                        jqxhr = $.getJSON($(this).attr('href'), function (data) {
                            jqxhr = null;
                            contents.empty();
                            $.each(data.result, function (k, v) {
                                const link = $(v.link);
                                link.attr('title', tr(v.title));
                                link.empty().append($('<img>').attr('src', link.attr('href')));
                                link.on("click", function () {
                                    field.val($(this).attr('href'));
                                    icon.attr('src', $(this).attr('href'));
                                    $.closeModal();
                                    return false;
                                });

                                link.appendTo(contents);
                            });
                        });
                        return false;
                    });
                    $('.sections a:first', this).trigger("click");
                }
             })
        });
    });
    {/literal}
{/jq}
