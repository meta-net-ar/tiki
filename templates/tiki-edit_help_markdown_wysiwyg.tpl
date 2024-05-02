<h3>{tr}WYSIWYG Mardown Syntax{/tr}</h3>
{if $prefs.feature_help eq 'y'}
    {remarksbox type="info"}
    {tr}More information:{/tr} <a href="{$prefs.helpurl}Markdown-WYSIWYG-Page-Editor" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Page Editor:{/tr} {tr}More help on editing wiki pages{/tr}">
            {tr}Markdown WYSIWYG Page Editor{/tr}
        </a>
        {tr}and{/tr}
        <a href="{$prefs.helpurl}Markdown-syntax" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Syntax:{/tr} {tr}The syntax system used for creating pages in Tiki{/tr}">
            {tr}Markdown Syntax{/tr}
        </a>
    {/remarksbox}
{/if}
<table class="table table-condensed table-hover">
    <tr>
        <th>
            {tr}Markdown Syntax{/tr}
        </th>
    </tr>
    <tr>
        <td>
            {icon name='bold'} <strong>{tr}Bold text{/tr}</strong> &nbsp;&nbsp;&nbsp; __{tr}text{/tr}__
        </td>
    </tr>
    <tr>
        <td>
            {icon name='italic'} <strong>{tr}Italic text{/tr}</strong> &nbsp;&nbsp;&nbsp; _{tr}text{/tr}_
        </td>
    </tr>
    <tr>
        <td>
            {icon name='strikethrough'} <strong>{tr}Deleted text{/tr}</strong> &nbsp;&nbsp;&nbsp; {tr}2 dashes{/tr} "-". &nbsp;&nbsp;&nbsp; --{tr}text{/tr}--
        </td>
    </tr>
    <tr>
        <td>
            {icon name='h1'} <strong>{tr}Headings{/tr}</strong> <br/> #heading1, ##heading2, ###heading3
        </td>
    </tr>
    <tr>
        <td>
            {icon name='horizontal-rule'} <strong>{tr}Horizontal rule{/tr}</strong> &nbsp;&nbsp;&nbsp; -<em></em>-<em></em>-<em></em>-
        </td>
    </tr>
    <tr>
        <td>
            {icon name='link-external'} <strong>{tr}External links{/tr}</strong> &nbsp;&nbsp;&nbsp;  [text](url)
        </td>
    </tr>
    <tr>
        <td>
            {icon name='list'} {icon name='list-numbered'} <strong>{tr}Lists{/tr}</strong> <br> * {tr}for bullet lists,{/tr} 1., 2., 3. etc {tr}for numbered lists,{/tr}
        </td>
    </tr>
    <tr>
        <td>
            {icon name='table'} <strong>{tr}Tables{/tr}</strong> <br/>
            | {tr}row{/tr}1-{tr}col{/tr}1 | {tr}row{/tr}1-{tr}col{/tr}2 | {tr}row{/tr}1-{tr}col{/tr}3<br>
            | ----- | ----- | ----- |<br>
            | {tr}row{/tr}2-{tr}col{/tr}1 | {tr}row{/tr}2-{tr}col{/tr}2 | {tr}row{/tr}2-{tr}col{/tr}3 |
        </td>
    </tr>
    <tr>
        <td>
            <strong>{tr}Monospace font{/tr}</strong> &nbsp;&nbsp;&nbsp; `{tr}Code sample{/tr}`
        </td>
    </tr>
</table>
