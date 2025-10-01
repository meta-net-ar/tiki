{title}{tr _0=$page _1=$version}Revert page %0 to version %1{/tr}{/title}

<input type="submit" form="rollbackform" class="btn btn-primary btn-sm" name="rollback" value="{tr _0=$version}Revert to version %0{/tr}" onclick="confirmPopup('{tr _0=$page _1=$version}Are you sure you want to revert %0 to version #%1?{/tr}')">
<div class="wikitext">{$preview.data}</div>

<form action="tiki-rollback.php?page={$page|escape:url}&amp;version={$version|escape}&amp;rollback=y" method="post" id="rollbackform">
    <div>
        {ticket}
        <input type="hidden" name="page" value="{$page|escape}">
        <input type="hidden" name="version" value="{$version|escape}">
        <label for="comment">{tr}Describe the reason for revert{/tr} {help url='Using+Wiki+Pages' desc="{tr}Enter some text to describe the reason for reverting{/tr}"}</label>
        <input class="form-control wikiedit" type="text" id="comment" name="comment" value="" maxlength="255">
    </div>
    <div align="center">
        <input type="submit" class="btn btn-primary btn-sm" name="rollback" value="{tr _0=$version}Revert to version %0{/tr}" onclick="confirmPopup('{tr _0=$page _1=$version}Are you sure you want to revert %0 to version #%1?{/tr}')">
    </div>
</form>

{include file='tiki-page_bar.tpl'}
