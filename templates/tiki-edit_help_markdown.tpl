{if $prefs.feature_help eq 'y'}
    {remarksbox type="info" title="{tr}More information{/tr}"}
    {if $prefs.feature_wysiwyg eq 'y' && $prefs.wysiwyg_optional eq 'y'}
        <a href="{$prefs.helpurl}Markdown-WYSIWYG-Page-Editor" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Page Editor:{/tr} {tr}More help on editing wiki pages{/tr}">
            {tr}Markdown WYSIWYG Page Editor{/tr}
        </a>
    {else}
        <a href="{$prefs.helpurl}Wiki-Page-Editor" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Page Editor:{/tr} {tr}More help on editing wiki pages{/tr}">
            {tr}Wiki Page Editor{/tr}
        </a>
    {/if}
    {tr}and{/tr}
        <a href="{$prefs.helpurl}Markdown-syntax" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Syntax:{/tr} {tr}The syntax system used for creating pages in Tiki{/tr}">
            {tr}Markdown Syntax{/tr}
        </a>
        <a href="{$prefs.helpurl}Converting-from-Tiki-syntax-to-Markdown" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Syntax:{/tr} {tr}Converting from Tiki-syntax to Markdown{/tr}">
            {tr}Markdown Syntax{/tr}
        </a>
    {/remarksbox}
{/if}
<table class="table table-condensed table-hover">
    <tr>
        <td>
            {icon name='bold'}
            <strong>{tr}Bold text{/tr}</strong><br>
            <code>**{tr}text{/tr}**</code> {tr}or{/tr} <code>__{tr}text{/tr}__</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='italic'}
            <strong>{tr}Italic text{/tr}</strong><br>
            <code>*{tr}text{/tr}*</code> {tr}or{/tr} <code>_{tr}text{/tr}_</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='strikethrough'}
            <strong>{tr}Deleted text{/tr}</strong><br>
            <code>~~{tr}text{/tr}~~</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='h1'}
            <strong>{tr}Headings{/tr}</strong><br>
            <code># Heading 1</code>, <code>## Heading 2</code>, <code>### Heading 3</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='horizontal-rule'}
            <strong>{tr}Horizontal rule{/tr}</strong><br>
            <code>---</code> {tr}or{/tr} <code>***</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='link-external'}
            <strong>{tr}External links{/tr}</strong> <br>
            <code>[text](url)</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='image'}
            <strong>{tr}Image{/tr}</strong><br>
            <code>![alt ext](image.jpg)</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='list'} {icon name='list-numbered'}
            <strong>{tr}Lists{/tr}</strong><br>
            {tr}for bullet lists{/tr}<br>
            <code>*</code> {tr}or{/tr} <code>-</code> <br>
            {tr}for numbered lists{/tr}<br>
            <code>1. First<br> 2. Second<br>3. Third</code><br>
            {tr}Lists task{/tr}<br>
            <code>- [x] Write the press release <br>- [ ] Update the website<br>- [ ] Contact the media</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='list'}
            <strong>{tr}Blockquotes{/tr}</strong><br>
            <code>> This is a blockquote.<br>> It can span multiple lines.</code
        </td>
    </tr>
    <tr>
        <td>
            {icon name='table'}
            <strong>{tr}Tables{/tr}</strong><br/>
            <code>
                | {tr}row{/tr}1-{tr}col{/tr}1 | {tr}row{/tr}1-{tr}col{/tr}2 | {tr}row{/tr}1-{tr}col{/tr}3 |<br>
                | --------- | --------- | --------- |<br>
                | {tr}row{/tr}2-{tr}col{/tr}1 | {tr}row{/tr}2-{tr}col{/tr}2 | {tr}row{/tr}2-{tr}col{/tr}3 |
            </code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='superscript'}
            <strong>{tr}Footnote{/tr}</strong><br>
            <code>Here's a sentence with a footnote. [^1]</code><br>
            <code>[^1]: This is the footnote.</code>
        </td>
    </tr>
    <tr>
        <td>
            {icon name='code'}
            <strong>{tr}Code{/tr}</strong><br>
            {tr}Simple Code{/tr}<br>
            <code>`Code sample`</code><br>
            {tr}For code Block{/tr}</> <br>
            <code>```php<br>echo "Hello, World!";<br>```</code>
        </td>
    </tr>
</table>
