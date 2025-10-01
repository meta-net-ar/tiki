{if $prefs.feature_help eq 'y'}
    {remarksbox type="info" title="{tr}More information{/tr}"}
        <a href="{$prefs.helpurl}Wiki-Page-Editor" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Page Editor:{/tr} {tr}More help on editing wiki pages{/tr}">
            {tr}Wiki Page Editor{/tr}
        </a>
        {tr}and{/tr}
        <a href="{$prefs.helpurl}Markdown-syntax" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Syntax:{/tr} {tr}The syntax system used for creating pages in Tiki{/tr}">
            {tr}Markdown Syntax{/tr}
        </a>
    {/remarksbox}
{/if}
<table class="table table-condensed table-hover">
    <tr>
        <td>
            {icon name='bold'} 
            <strong>{tr}Bold text{/tr}</strong> &nbsp;&nbsp;&nbsp; 
            <code>**{tr}text{/tr}**</code> {tr}or{/tr} <code>__{tr}text{/tr}__</code>
        </td>
        </tr>
        <tr>
        <td>
            {icon name='italic'} 
            <strong>{tr}Italic text{/tr}</strong> &nbsp;&nbsp;&nbsp; 
            <code>*{tr}text{/tr}*</code> {tr}or{/tr} <code>_{tr}text{/tr}_</code>
        </td>
        </tr>
        <tr>
        <td>
            {icon name='strikethrough'} 
            <strong>{tr}Deleted text{/tr}</strong> &nbsp;&nbsp;&nbsp; 
            <code>~~{tr}text{/tr}~~</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='h1'} 
            <strong>{tr}Headings{/tr}</strong> <br/>
            <code># Heading 1</code>, <code>## Heading 2</code>, <code>### Heading 3</code>
        </td>
        </tr>
        <tr>
        <td>
            {icon name='horizontal-rule'} 
            <strong>{tr}Horizontal rule{/tr}</strong> &nbsp;&nbsp;&nbsp; 
            <code>---</code> {tr}or{/tr} <code>***</code>
        </td>
        </tr>
        <tr>
        <td>
            {icon name='link-external'} 
            <strong>{tr}External links{/tr}</strong> &nbsp;&nbsp;&nbsp; 
            <code>[text](url)</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='list'} {icon name='list-numbered'} 
            <strong>{tr}Lists{/tr}</strong>
            <br>
            <code>*</code> {tr}or{/tr} <code>-</code>  {tr}for bullet lists,{/tr}<br>
            <code>1.</code>, <code>2.</code>, <code>3.</code> etc.  {tr}for numbered lists,{/tr}
        </td>
    </tr>
    <tr>
        <td>
            {icon name='table'} 
            <strong>{tr}Tables{/tr}</strong>
            <br/>
            <code>
            | {tr}row{/tr}1-{tr}col{/tr}1 | {tr}row{/tr}1-{tr}col{/tr}2 | {tr}row{/tr}1-{tr}col{/tr}3 |<br>
            | --------- | --------- | --------- |<br>
            | {tr}row{/tr}2-{tr}col{/tr}1 | {tr}row{/tr}2-{tr}col{/tr}2 | {tr}row{/tr}2-{tr}col{/tr}3 |
            </code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='code'} 
            <strong>{tr}Monospace font{/tr}</strong> &nbsp;&nbsp;&nbsp; 
            <code>`{tr}Code sample{/tr}`</code>
        </td>
    </tr>
</table>
