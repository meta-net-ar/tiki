<!DOCTYPE html>
<html id="installer" lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}">

<head>
    <script>
        (function() {
            var key = 'tki-theme';
            var theme = 'light';
            try {
                var saved = localStorage.getItem(key);
                if (saved === 'dark' || saved === 'light') {
                    theme = saved;
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                }
            } catch (e) {}
            document.documentElement.setAttribute('data-tki-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    {include file='header.tpl'}
</head>

<body class="tiki fixed_width">
    {$mid_data}
    {include file='footer.tpl'}
</body>

</html>
