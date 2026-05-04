{extends $global_extend_layout|default:'layout_edit.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form id="enter-key-form" method="post" class="no-ajax"
          action="{service controller=encryption action=enter_key keyId=$encryption_key.keyId}&amp;noTemplate">
        <div class="mb-3 row mx-0">
            <label for="shared_key" class="col-form-label">{tr _0=$encryption_key.name}Enter shared secret for key "%0"{/tr}</label>
            <input type="password" id="shared_key" name="shared_key" value="{$shared_key|escape}" class="form-control">
            <div class="form-text">
                {tr}If you have a shared secret key not saved into your account, you can paste it here to encrypt or decrypt data with it.{/tr}
            </div>
        </div>
        <div class="submit">
            <input type="hidden" name="keyId" value="{$encryption_key.keyId|escape}">
            <input type="submit" class="btn btn-primary" value="{tr}Submit{/tr}">
        </div>
    </form>
    <script>
    $(function () {
        var $form = $('#enter-key-form');
        if (!$form.length) return;
        $form.on('submit', function (e) {
            e.preventDefault();
            var $modal = $form.closest('.modal-dialog');
            $modal.tikiModal(tr('Loading...'));
            $.ajax({
                type: 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                dataType: 'json',
                preventGlobalErrorHandle: true,
            })
                .done(function (data) {
                    if (data && data.extra === 'close') {
                        $.closeModal();
                    }
                })
                .fail(function (jqxhr) {
                    $form.showError(jqxhr);
                    $form.find('#shared_key').val('').trigger('focus');
                })
                .always(function () {
                    $modal.tikiModal();
                });
        });
    });
    </script>
{/block}
