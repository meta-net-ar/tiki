{tabset name="pagetabs{$id}" toggle="n"}
    {foreach from=$pages item=page}
        {tab name="{$page}" key="{$page|replace:' ':'_'}"}
        {/tab}
    {/foreach}
{/tabset}

<script type="module">import loadTabsContent from "@tiki/plugins/pagetabs";loadTabsContent('pagetabs{$id}');</script>
