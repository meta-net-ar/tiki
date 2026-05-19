<?php

require_once 'tiki-setup.php';

use Tiki\Sitemap\Generator;

if ($prefs['llms_txt_enable'] !== 'y') {
    Feedback::errorAndDie(tra('llms.txt feature is not enabled.'), 404);
}

$type       = $_REQUEST['type'] ?? 'llmstxt';
$useMdLinks = ($prefs['llms_md_enable'] ?? 'n') === 'y';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: none');

switch ($type) {
    case 'llmstxt':
        $generator = new Generator(null);
        echo $generator->generateLlmsTxt($useMdLinks);
        break;

    case 'content':
        // Only when llms_md_enable is y
        if (! $useMdLinks) {
            Feedback::errorAndDie(tra('Not available'), 404);
        }

        $contentType = $_REQUEST['content_type'] ?? '';

        // TODO: add ref to the html url when llms_md_enable y.
        switch ($contentType) {
            case 'wiki':
                $page = $_REQUEST['page'] ?? '';
                $info = $tikilib->get_page_info($page);
                if (! $info) {
                    Feedback::errorAndDie(tra('Page not found'), 404);
                }
                $editlib = TikiLib::lib('edit');
                $markdown = $editlib->convertWikiSyntax($info['data'], 'markdown', $page);
                echo "# {$info['pageName']}\n\n{$markdown}";
                break;

            case 'article':
                $articleId = (int) ($_REQUEST['articleId'] ?? 0);
                if ($prefs['feature_articles'] !== 'y' || $articleId === 0) {
                    Feedback::errorAndDie(tra('Article not available'), 404);
                }

                $article = TikiLib::lib('art')->get_article($articleId);
                if (! $article) {
                    Feedback::errorAndDie(tra('Article not found'), 404);
                }

                $converter = new \League\HTMLToMarkdown\HtmlConverter([
                    'strip_tags' => false,
                    'hard_break' => true,
                    'header_style' => 'atx',
                ]);
                $heading  = $converter->convert($article['heading'] ?? '');
                $body     = $converter->convert($article['body'] ?? '');
                $markdown = trim('> ' . $heading . "\n\n" . $body);
                echo "# {$article['title']}\n\n{$markdown}";
                break;

            default:
                Feedback::errorAndDie(tra('Unsupported content type'), 404);
        }
        break;

    default:
        Feedback::errorAndDie(tra('Unknown type'), 404);
}
