{title}
    {if $user ne $userwatch}
        {tr}Profile picture:{/tr} {$userwatch}
    {else}
        {tr}Pick your profile picture{/tr}
    {/if}
{/title}
{if $user eq $userwatch}
    {include file='tiki-mytiki_bar.tpl'}
{else}
    <div class="t_navbar">
        {$thisuserwatch=$userwatch|escape}
        {button href="tiki-user_preferences.php?view_user=$thisuserwatch" class="btn btn-primary" _text="{tr}User Preferences{/tr}"}
    </div>
{/if}
<div class="profile-picture-page">
    <div class="profile-picture-page__grid">
        <section class="profile-picture-page__panel profile-picture-page__panel--preview" aria-labelledby="current-picture-heading">
            <h2 id="current-picture-heading" class="h4 mb-3">{if $user eq $userwatch}{tr}Your current profile picture{/tr}{else}{tr}Profile picture{/tr}{/if}</h2>
            <div class="profile-picture-page__preview" id="user-picture">
                {if $avatar}
                    {$avatar}
                {else}
                    <span class="profile-picture-page__empty">{tr}no profile picture{/tr}</span>
                {/if}
            </div>
            {if isset($user_picture_id)}
                <details class="text-start mb-3" id="full-size-picture">
                    <summary class="small">{tr}View full size{/tr}</summary>
                    <img src="tiki-download_file.php?fileId={$user_picture_id|escape}&amp;display=y" class="img-fluid rounded-1 mt-2" alt="{tr}Full size profile picture{/tr}" style="object-fit:cover">
                </details>
            {/if}
            <div class="profile-picture-page__actions">
                {if sizeof($avatars) eq 0 and $avatar}
                    <a class="tips btn btn-outline-secondary" href="tiki-pick_avatar.php?reset=y&amp;view_user{$userwatch|escape}" title=":{tr}Reset{/tr}">
                        {icon name='remove'} {tr}Reset{/tr}
                    </a>
                {/if}
                {if $prefs.user_dicebear_avatar eq 'y'}
                    <button class="btn btn-primary tips" id="show-avatar-picker" title=":{tr}Choose an avatar{/tr}">{icon name="user-edit"} {tr}Choose an avatar{/tr}</button>
                {/if}
            </div>
        </section>

        <section class="profile-picture-page__panel profile-picture-page__upload" aria-labelledby="upload-picture-heading">
            <h2 id="upload-picture-heading" class="h4 mb-1">{tr}Upload your own profile picture{/tr}</h2>
            <p class="text-secondary small mb-4">{tr}Use an image from your device as your profile picture.{/tr}</p>
            <form enctype="multipart/form-data" action="tiki-pick_avatar.php" method="post">
                {ticket}
                {if $user ne $userwatch}<input type="hidden" name="view_user" value="{$userwatch|escape}">{/if}
                <input type="hidden" name="MAX_FILE_SIZE" value="10000000">
                <div class="tiki-form-group">
                    <label for="userfile1" class="form-label">{tr}Image file{/tr}</label>
                    {if $prefs.elementplus_upload eq 'y'}
                        <el-file-input accept="image/*" id="el-userfile1"></el-file-input>
                        <input id="userfile1" name="userfile1" type="file" class="d-none">
                        {jq}
                            $("#el-userfile1").on("change", function(event) {
                                const file = event.detail[0];
                                const dataTransfer = new DataTransfer();

                                dataTransfer.items.add(file.raw);

                                $("#userfile1")[0].files = dataTransfer.files;
                                $("#upload").prop("disabled", !dataTransfer.files.length);
                            }).on("remove", function(event) {
                                $("#userfile1")[0].value = "";
                                $("#upload").prop("disabled", true);
                            });
                        {/jq}
                    {else}
                        <input id="userfile1" name="userfile1" type="file" accept="image/*" class="form-control">
                    {/if}
                    <div class="form-text mt-2">
                        {if $prefs.user_store_file_gallery_picture neq 'y'}{tr}GIF, JPG, or PNG images approximately 45px × 45px{/tr}{else}{tr}GIF, JPG, or PNG images{/tr}{/if}
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="upload" name="upload" disabled>{icon name="upload"} {tr}Upload{/tr}</button>
            </form>
        </section>
    </div>

{ticket}

{if $prefs.user_dicebear_avatar eq 'y'}
    <script type="module">
        import { showPickerModal, renderAvatars } from 'avatar-generator';

        $('#show-avatar-picker').on('click', showPickerModal);
        renderAvatars();
    </script>
{/if}

{if sizeof($avatars) > 0}

    {if $showall eq 'y'}
        <section class="profile-picture-page__panel profile-picture-page__library">
        <h2 class="h4">{if $user eq $userwatch}{tr}Pick user profile picture from the library{/tr}{else}{tr}Pick user profile picture{/tr}{/if} <a class="float-end small" href="tiki-pick_avatar.php?showall=n">{tr}Hide all{/tr}</a></h2>
        <div class="profile-picture-page__library-grid">
            {section name=im loop=$avatars}
                <a href="tiki-pick_avatar.php?showall=n&amp;avatar={$avatars[im]|escape:"url"}&amp;uselib=use"><img src="{$avatars[im]}"></a>
            {/section}
        </div>
        </section>
    {else}

        {jq}
            var avatars = new Array();
            {{section name=ix loop=$avatars}
                avatars[{$smarty.section.ix.index}] = '{$avatars[ix]}';
                {if $smarty.section.ix.index eq $yours}
                    {$yours=$avatars[ix]}
                {/if}
            {/section}}
            var pepe=1;
            function addavt() {
                pepe++;
                if(pepe > avatars.length-1) {
                    pepe =0;
                }
                document.getElementById('avtimg').src=avatars[pepe];
                document.getElementById('avatar').value=avatars[pepe];
            }

            function subavt() {
                pepe--;
                if(pepe < 0 ) {
                    pepe=avatars.length-1
                }
                document.getElementById('avtimg').src=avatars[pepe];
                document.getElementById('avatar').value=avatars[pepe];
            }
        {/jq}

        <section class="profile-picture-page__panel profile-picture-page__library">
        <h2 class="h4">{tr}Pick user profile picture from the library{/tr} <a class="float-end small" href="tiki-pick_avatar.php?showall=y">{tr}Show all{/tr}</a></h2>
        <form action="tiki-pick_avatar.php" method="post">
        {ticket}
            <input id="avatar" type="hidden" name="avatar" value="{$yours|escape}">
            {if $user ne $userwatch}<input type="hidden" name="view_user" value="{$userwatch|escape}">{/if}
            <div class="profile-picture-page__carousel">
                <a class="btn btn-outline-secondary btn-sm" href="javascript:subavt();">{tr}Prev{/tr}</a>
                <img id="avtimg" src="{$yours}" alt="{tr}Profile picture{/tr}">
                <a class="btn btn-outline-secondary btn-sm" href="javascript:addavt();">{tr}Next{/tr}</a>
            </div>
            <div class="profile-picture-page__carousel-actions">
                <input type="submit" class="btn btn-outline-secondary btn-sm" name="rand" value="{tr}random{/tr}">
                <input type="submit" class="btn btn-primary btn-sm" name="uselib" value="{tr}Use{/tr}">
                <input type="submit" class="btn btn-outline-secondary btn-sm" name="reset" value="{tr}no profile picture{/tr}">
            </div>
        </form>
        </section>
    {/if}
{/if}

{jq}
    $("#userfile1").on("change", function() {
        $("#upload").prop("disabled", !this.files.length);
    });
{/jq}
</div>
