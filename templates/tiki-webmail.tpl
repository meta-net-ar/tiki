{title help="Webmail" admpage="webmail"}{tr}Webmail{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}

{$output_data}

{jq}
$('.pagetitle a, .t_navbar a').each(function() {
    $(this).data('href', $(this).attr('href'));
    $(this).attr('href', '#');
    $(this).click(() => {
        window.location.href = $(this).data('href');
    });
})
{/jq}
