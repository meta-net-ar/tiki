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
    (function () {
        var form = document.getElementById('enter-key-form');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = new URLSearchParams(new FormData(form));
            fetch(form.action, {
                method: 'POST',
                body: body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) {
                return r.json().then(function (data) {
                    return { ok: r.ok, data: data };
                });
            })
            .then(function (res) {
                if (res.ok && res.data && res.data.extra === 'close') {
                    $.closeModal();
                    return;
                }
                var msg = (res.data && res.data.message) || '{tr}An error occurred.{/tr}';
                var old = form.querySelector('.ajax-errors');
                if (old) old.remove();
                var alert = document.createElement('div');
                alert.className = 'ajax-errors alert alert-danger alert-dismissible';
                var closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn-close';
                closeBtn.dataset.bsDismiss = 'alert';
                alert.appendChild(closeBtn);
                var ul = document.createElement('ul');
                var li = document.createElement('li');
                li.textContent = msg;
                ul.appendChild(li);
                alert.appendChild(ul);
                form.prepend(alert);
                var input = form.querySelector('#shared_key');
                if (input) { input.value = ''; input.focus(); }
            })
            .catch(function () {});
        });
    })();
    </script>
{/block}
