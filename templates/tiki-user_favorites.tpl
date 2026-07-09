 {* $Id$ *}

{title help="Favorites"}{tr}My favourites{/tr}{/title}
{include file='tiki-mytiki_bar.tpl'}

<h2>{tr}My watches{/tr}</h2>

{remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use this page to manage your favorite pages, articles and trackers items{/tr}  {/remarksbox}

{if $prefs.feature_wiki == 'y'}
    <h3>{tr}Favorites Pages{/tr}</h3>
    <div class="table-responsive">
        <table class="table ">
            <tr>
                <th>{tr}Page Name{/tr}</th>
                <th>{tr}Action{/tr}</th>
            </tr>
            {if $wikiPages}
                {foreach item=wikiPage from=$wikiPages}
                    <tr>
                        <td class="text">
                            <a href="tiki-index.php?page={{$wikiPage}}"> {{$wikiPage}} </a>
                        </td>
                        <td >
                            <div  class="form-switch">
                                    <input type="checkbox" class="form-check-input" name="wiki page" id="{{$wikiPage}}" checked>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <tr> {tr}No record found{/tr} </tr>
            {/if}
        </table>
    </div>
{/if}

{if $prefs.feature_articles == 'y'}
    <h3>{tr}Favorites Articles{/tr}</h3>
    <div class="table-responsive">
        <table class="table ">
            <tr>
                <th>{tr}Title article{/tr}</th>
                <th>{tr}Action{/tr}</th>
            </tr>
            {if $articles}
                {foreach item=article from=$articles}
                    <tr>
                        <td class="text">
                            <a href="tiki-read_article.php?articleId={{$article.id}}">{{$article.title}}</a>
                        </td>
                        <td >
                            <div  class="form-switch">
                                <input type="checkbox" class="form-check-input" name="article" id="{{$article.id}}" checked>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <tr> {tr}No record found{/tr} </tr>
            {/if}
        </table>
    </div>

{/if}

{if $prefs.feature_trackers == 'y'}
    <h3>{tr}Favorites Items{/tr}</h3>
    <div class="table-responsive">
        <table class="table ">
            <tr>
                <th>{tr}Item{/tr}</th>
                <th>{tr}Action{/tr}</th>
            </tr>
            {if $trackersItem}
                {foreach item=item from=$trackersItem}
                    <tr>
                        <td class="text">
                            <a href="tiki-view_tracker_item.php?itemId={{$item.id}}"> {{$item.title}} </a>
                        </td>
                        <td >
                            <div  class="form-switch">
                                <input type="checkbox" class="form-check-input" name="trackeritem" id="{{$item.id}}" checked>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <tr> {tr}No record found{/tr} </tr>
            {/if}
        </table>
    </div>
{/if}

{jq}
    $('.form-check-input').on("click", function () {
        var type = $(this).attr("name");
        var object = $(this).attr("id");
        $.post("tiki-ajax_services.php", {
        'controller':'favorite',
        'action':'toggle',
        'type': type,
        'object': object,
        }).done(function(){
            location.reload();
        }).fail(function(err){
            feedback(tr("the action was not successful"));
        })
    });
{/jq}
