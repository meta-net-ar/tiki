{* \brief Show wiki syntax help
 * included by tiki-show_help.tpl via smarty_block_add_help()
 * TODO: Add links to add samples to edit form *}

{if $prefs.feature_help eq 'y'}
    {remarksbox type="info"}
        {tr}More information:{/tr} <a href="{$prefs.helpurl}Wiki-Page-Editor" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Page Editor:{/tr} {tr}More help on editing wiki pages{/tr}">
            {tr}Wiki Page Editor{/tr} {icon name="link-external" istyle="font-size: 70%"}
        </a>
        {tr}and{/tr}
        <a href="{$prefs.helpurl}Wiki-syntax" target="tikihelp" class="tikihelp alert-link" title="{tr}Wiki Syntax:{/tr} {tr}The syntax system used for creating pages in Tiki{/tr}">
            {tr}Wiki Syntax{/tr} {icon name="link-external" istyle="font-size: 70%"}.
        </a>
    {/remarksbox}
{/if}

<div class="wiki-help-masonry">
    {if (!isset($wysiwyg) or $wysiwyg ne 'y') or (isset($wysiwyg) and $wysiwyg eq 'y' and $prefs.wysiwyg_wiki_parsed eq 'y')}
        <div class="help-item">{icon name='bold'} <strong>{tr}Bold text{/tr}</strong> &nbsp;&nbsp;&nbsp; __{tr}text{/tr}__</div>
        <div class="help-item">{icon name='italic'} <strong>{tr}Italic text{/tr}</strong> &nbsp;&nbsp;&nbsp; 2 {tr}single quotes{/tr} ('). &nbsp;&nbsp;&nbsp; '"{tr}text{/tr}"'</div>
        <div class="help-item">{icon name='underline'} <strong>{tr}Underlined text{/tr}</strong> &nbsp;&nbsp;&nbsp; ==={tr}text{/tr}===</div>
        <div class="help-item">{icon name='font' istyle='color:red'} <strong>{tr}Colored text{/tr}</strong> <br/> ~~#FFEE33:{tr}text{/tr}~~ {tr}or{/tr} ~~yellow:{tr}text{/tr}~~. {tr}Will display using the indicated HTML color or color name. Color name can contain two colors separated by a comma. In this case, the first color would be the foreground and the second one the background.{/tr}</div>
        <div class="help-item">{icon name='strikethrough'} <strong>{tr}Deleted text{/tr}</strong> &nbsp;&nbsp;&nbsp; --{tr}text{/tr}--</div>
        <div class="help-item">{icon name='h1'} <strong>{tr}Headings{/tr}</strong> <br/> !heading1, !!heading2, !!!heading3</div>
        <div class="help-item"><strong>{tr}Show/Hide{/tr}</strong> <br/> !+, !!- {tr}show/hide heading section. + (shown) or - (hidden) by default{/tr}.</div>
        <div class="help-item"><strong>{tr}Autonumbered Headings{/tr}</strong> <br/> !#, !!#, !+#, !-# ...</div>
    {/if}
    <div class="help-item"><strong>{tr}Table of contents{/tr}</strong> <br/>{tr}{literal}{toc}{/literal}, {literal}{maketoc}{/literal} prints out a table of contents for the current page based on structures (toc) or ! headings (maketoc){/tr}. {tr}Common optional parameters for maketoc are: title|maxdepth|levels|nums, and for toc are: order|showdesc|shownum|structId|maxdepth|pagename.{/tr}</div>
    {if (!isset($wysiwyg) or $wysiwyg ne 'y') or (isset($wysiwyg) and $wysiwyg eq 'y' and $prefs.wysiwyg_wiki_parsed eq 'y')}
        <div class="help-item">{icon name='horizontal-rule'} <strong>{tr}Horizontal rule{/tr}</strong> &nbsp;&nbsp;&nbsp; ----</div>
        <div class="help-item">{icon name='box'} <strong>{tr}Text box{/tr}</strong> &nbsp;&nbsp;&nbsp; ^{tr}Box content{/tr}^</div>
        <div class="help-item">{icon name='align-center'} <strong>{tr}Centered text{/tr}</strong> &nbsp;&nbsp;&nbsp; {if $prefs.feature_use_three_colon_centertag eq 'y'}:::{tr}text:::{/tr}{else}::{tr}text::{/tr}{/if}</div>
    {/if}
    <div class="help-item">{icon name='cog'} <strong>{tr}Dynamic variables{/tr}</strong> <br/> %{tr}Name{/tr}% {tr}Inserts an editable variable{/tr}</div>
    <div class="help-item">{icon name='link-external'} <strong>{tr}External links{/tr}</strong> <br/> {tr}use square brackets for an external link: [URL], [URL|link_description],[URL|link_description|relation] or [URL|description|relation|nocache]{/tr}</div>
    <div class="help-item"><strong>{tr}Square Brackets{/tr}</strong> <br/> {tr}Use [[foo] to show [foo].{/tr}</div>
    <div class="help-item">{icon name='link'} <strong>{tr}Wiki references{/tr}</strong> <br/>(({tr}page{/tr})) {tr}or{/tr} (({tr}page|description{/tr})) {tr}for wiki references{/tr}</div>
    {if (!isset($wysiwyg) or $wysiwyg ne 'y') or (isset($wysiwyg) and $wysiwyg eq 'y' and $prefs.wysiwyg_wiki_parsed eq 'y')}
        <div class="help-item">{icon name='list'} {icon name='list-numbered'} <strong>{tr}Lists{/tr}</strong> <br> * {tr}for bullet lists,{/tr} # {tr}for numbered lists,{/tr} ;{tr}Word:{/tr}{tr}definition{/tr} {tr}for definiton lists{/tr}</div>
        <div class="help-item"><strong>{tr}Indentation{/tr}</strong> <br/>+, ++ {tr}Creates an indentation for each plus (useful in list to continue at the same level){/tr}</div>
        <div class="help-item">{icon name='table'} <strong>{tr}Tables{/tr}</strong> <br/> || {tr}row{/tr}1-{tr}col{/tr}1 | {tr}row{/tr}1-{tr}col{/tr}2 | {tr}row{/tr}1-{tr}col{/tr}3<br>{tr}row{/tr}2-{tr}col{/tr}1 | {tr}row{/tr}2-{tr}col{/tr}2 | {tr}row{/tr}2-{tr}col{/tr}3 ||</div>
        <div class="help-item">{icon name='title'} <strong>{tr}Title bar{/tr}</strong> &nbsp;&nbsp;&nbsp; -={tr}Title{/tr}=-</div>
        <div class="help-item"><strong>{tr}Monospace font{/tr}</strong> &nbsp;&nbsp;&nbsp; -+{tr}Code sample{/tr}+-</div>
    {/if}
    <div class="help-item"><strong>{tr}Line break{/tr}</strong> <br/>%%% {tr}(very useful especially in tables){/tr}</div>
    <div class="help-item"><strong>{tr}Multi-page pages{/tr}</strong> <br/>{tr}Use{/tr} ...page... {tr}to separate pages{/tr}</div>
    <div class="help-item"><strong>{tr}Non-parsed sections{/tr}</strong> <br/> ~np~ {tr}data{/tr} ~/np~</div>
    <div class="help-item"><strong>{tr}Preformated sections{/tr}</strong> <br/> {tr}~pp~ data ~/pp~ Displays preformated text/code; no Wiki processing is done inside these sections{/tr}</div>
    <div class="help-item"><strong>{tr}Comments{/tr}</strong> <br/> {tr}~tc~ Tiki Comment ~/tc~ makes a Tiki comment. ~hc~ HTML Comment ~/hc~{/tr}</div>
    {if $prefs.feature_wiki_monosp eq 'y'}
        <div class="help-item"><strong>{tr}Block Preformatting{/tr}</strong> <br/> {tr}Indent text with any number of spaces to change it to a monospaced block{/tr}</div>
    {/if}
    <div class="help-item"><strong>{tr}Direction{/tr}</strong> <br/>{literal}{r2l}{/literal}, {literal}{l2r}{/literal}, {literal}{rm}{/literal}, {literal}{lm}{/literal}{tr}Controls text direction{/tr}</div>
    <div class="help-item"><strong>{tr}Special characters{/tr}</strong> <br/>
      {literal}~c~{/literal} &copy;, 
      {literal}~amp~{/literal} &amp;, 
      {literal}~lt~{/literal} &lt;, 
      {literal}~gt~{/literal} &gt;, 
      {literal}~ldq~{/literal} &ldquo;, 
      {literal}~rdq~{/literal} &rdquo;, 
      {literal}~lsq~{/literal} &lsquo;, 
      {literal}~rsq~{/literal} &rsquo;, 
      {literal}~--~{/literal} &mdash;, 
      {literal}~bs~{/literal} &#92;, 
      {tr}numeric between ~ for HTML numeric characters entity{/tr}
    </div>
</div>

{if $prefs.feature_wiki_paragraph_formatting eq 'y'}
    {remarksbox type="info" title="{tr}Note{/tr}" close="n"}
        {tr}Because the wiki paragraph formatting feature is on, all groups of non-blank lines are collected into paragraphs. Lines can be of any length, and will be wrapped together with the next line. Paragraphs are separated by blank lines.{/tr}
    {/remarksbox}
{else}
    {remarksbox type="info" title="{tr}Note{/tr}" close="n"}
        {tr}Because the Wiki paragraph formatting feature is off, each line will be presented as you write it. This means that if you want paragraphs to be wrapped properly, a paragraph should be all together on one line.{/tr}
    {/remarksbox}
{/if}
