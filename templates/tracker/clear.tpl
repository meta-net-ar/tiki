{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
{if $trackerId}
    <form class="simple" method="post" action="{service controller=tracker action=clear}">
        <p>{tr _0=$name}Do you really want to clear all the items from tracker "%0"? (Note: there is no undo and notifications will not be sent.){/tr}</p>
        <div class="submit">
            {ticket mode="confirm"}
            <input type="hidden" name="trackerId" value="{$trackerId|escape}">
            <input type="submit" class="btn btn-danger" value="{tr}Clear All{/tr}">
        </div>
    </form>
{else}
    <a href="tiki-list_trackers.php">{tr}Back to tracker list{/tr}
{/if}
{/block}
