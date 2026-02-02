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

  {jq}
    $(".media", ".peertube-media-list").on("click", function () {
        var hidden = $('<input type="hidden">')
            .attr('name', '{{$targetName}}')
            .attr('value', $(this).data('id'));
        $('#{{$formId}}').append(hidden);

        $("a[data-target-name='{{$targetName}}']").parent().find("ol").append($('<li>')
            .text($(this).data('name')));

        $(this).parents(".ui-dialog-content").data("ui-dialog").close();
    }).css("cursor", "pointer");
  {/jq}
{/block}
