<div id="comment-container my-3 mx-0" data-bs-target="{service controller=comment action=list type=$wikiplugin_comment_objectType objectId=$wikiplugin_comment_objectId}"></div>
{jq}
var id = '#comment-container';
$(id).comment_load($(id).data('bs-target'));
$(document).on("ajaxComplete", function(){$(id).tiki_popover();});
{/jq}
