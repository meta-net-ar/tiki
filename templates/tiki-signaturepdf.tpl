{title help="Signature PDF" admpage="SignaturePDF"}{tr}Signature PDF{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}

<script type="module">
import * as bootstrap from "bootstrap";
window.bootstrap = bootstrap;
</script>

{ticket}
{$output_data}

<script defer>
var REVERSE_PROXY_URL = '{{$tikiroot}}'.slice(1) + REVERSE_PROXY_URL;
</script>