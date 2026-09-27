{*
    Mounts the Vue comment module for one object.

    Callers pass:
      commentsVueType     - Tiki object type, e.g. "wiki page"
      commentsVueObjectId - the object identifier
      commentsVueMountId  - distinguishes mount points within one page, and must be safe
                            inside an element id. A tracker item page mounts two containers,
                            so both need their own token or the single-spa apps collide.
*}
{* With titles disabled the backend still stores a placeholder ("Untitled"), which the
   classic display hides too — see comment/list_inner.tpl. *}
{$commentsVueShowTitles = ($prefs.comments_notitle neq 'y')}
<div id="single-spa-application:@vue-mf/comments-{$commentsVueMountId|escape}" class="wp-comments-ux"></div>
{jq}
    window.registerApplication({
        name: "@vue-mf/comments-{{$commentsVueMountId|escape:'javascript'}}",
        app: () => importShim("@vue-mf/comments"),
        activeWhen: () => true,
        {* 15 = JSON_HEX_TAG|AMP|APOS|QUOT. These values land in an inline <script>, so the
           characters that could close it are hex-escaped; json_encode emits the quotes. *}
        customProps: {
            commentsContext: {
                type: {{json_encode($commentsVueType, 15)}},
                objectId: {{json_encode($commentsVueObjectId, 15)}},
                serviceUrl: {{json_encode("`$tikiroot`tiki-ajax_services.php", 15)}},
                showTitles: {{json_encode($commentsVueShowTitles)}}
            }
        }
    });
    onDOMElementRemoved("single-spa-application:@vue-mf/comments-{{$commentsVueMountId|escape:'javascript'}}", function () {
        window.unregisterApplication("@vue-mf/comments-{{$commentsVueMountId|escape:'javascript'}}");
    });
{/jq}
