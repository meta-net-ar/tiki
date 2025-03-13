{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}{$title|escape}{/title}
{/block}
{block name="content"}
    <div id="upload-avatar" class="clearfix">
        <div class="user-avatar user-avatar-preview float-start" style="width: {$prefs.user_small_avatar_size}px">{$userwatch|avatarize:"":"":false}</div>
        <form method="post" enctype="multipart/form-data" action="{service controller=user action=upload_avatar user={$userwatch|escape}}" id="uploadAvatarForm">
            <input id="userfile" name="userfile" type="file" accept="image/*">
            {ticket mode='confirm'}
            <div class="submit">
                <button href="{service controller=user action=upload_avatar user={$userwatch|escape} reset=y}" class="float-start btn btn-primary">{tr}Remove avatar{/tr}</button>
                <button type="submit" class="btn btn-primary btn-upload-avatar disabled">{tr}Upload avatar{/tr}</button>
            </div>
        </form>
    </div>
{/block}
