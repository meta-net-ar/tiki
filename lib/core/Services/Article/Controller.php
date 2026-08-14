<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_Article_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_articles');
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'cms';
    }

    /**
     * Normalise a raw tiki_articles row into a consistent API response shape.
     *
     * @param array $article Raw row as returned by ArtLib
     * @return array
     */
    protected function formatArticle(array $article)
    {
        global $base_url;

        $articleId = (int) ($article['articleId'] ?? 0);
        $title     = $article['title'] ?? '';
        $heading   = $article['heading'] ?? '';
        $body      = $article['body'] ?? '';
        $lang      = $article['lang'] ?? '';
        $lastModif = (int) ($article['lastModif'] ?? $article['publishDate'] ?? 0);

        // Public permalink to the article read page.
        $url = $base_url . 'tiki-read_article.php?articleId=' . $articleId;

        return [
            'articleId'   => $articleId,
            'title'       => $title,
            'heading'     => $heading,
            'body'        => $body,
            // `content` is an alias of the full readable text (heading + body)
            // so ingestion has a single field to chunk and store.
            'content'     => trim($heading . "\n\n" . $body),
            'lang'        => $lang,
            'url'         => $url,
            'lastModif'   => $lastModif,
            'topicId'     => isset($article['topicId']) ? (int) $article['topicId'] : null,
            'topicName'   => $article['topicName'] ?? null,
            'authorName'  => $article['authorName'] ?? ($article['author'] ?? null),
            'publishDate' => isset($article['publishDate']) ? (int) $article['publishDate'] : null,
            'type'        => $article['type'] ?? null,
        ];
    }

    /**
     * Returns all accessible articles (list).
     * GET /api/articles
     *
     * @param $input
     * @return array
     * @throws Services_Exception_Denied
     */
    public function action_list_articles($input)
    {
        $perms = Perms::get();
        if (! $perms->read_article) {
            throw new Services_Exception_Denied();
        }

        $artlib = TikiLib::lib('art');

        $offset     = $input->offset->int() ?: 0;
        $maxRecords = $input->maxRecords->int() ?: -1;
        $sortMode   = $input->sortMode->text() ?: 'publishDate_desc';
        $find       = $input->find->text();

        // ArtLib::list_articles signature is positional; we pass only the
        // common discriminators and leave the rest at their permissive
        // defaults so this stays a general-purpose listing.
        $result = $artlib->list_articles(
            $offset,
            $maxRecords,
            $sortMode,
            $find,
            ''      // date filter: none
        );

        $articles = [];
        foreach (($result['data'] ?? []) as $row) {
            $articles[] = $this->formatArticle($row);
        }

        return [
            'cant'     => (int) ($result['cant'] ?? count($articles)),
            'offset'   => $offset,
            'articles' => $articles,
        ];
    }

    /**
     * Returns a single article.
     * GET /api/articles/{articleId}
     *
     * @param $input
     * @return array
     * @throws Services_Exception_NotFound
     * @throws Services_Exception_Denied
     */
    public function action_get_article($input)
    {
        $articleId = $input->articleId->int();
        if (! $articleId) {
            throw new Services_Exception(tr('Article id is required.'));
        }

        $artlib  = TikiLib::lib('art');
        $article = $artlib->get_article($articleId);
        if (! $article) {
            throw new Services_Exception_NotFound(tr('Article "%0" not found', $articleId));
        }

        $perms = Perms::get('article', $articleId);
        if (! $perms->read_article) {
            throw new Services_Exception_Denied();
        }

        return $this->formatArticle($article);
    }

    /**
     * Creates an article.
     * POST /api/articles
     *
     * @param $input
     * @return array
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     */
    public function action_create_article($input)
    {
        global $user;

        Services_Exception_Denied::checkGlobal('edit_article');

        $title = $input->title->text();
        if (empty($title)) {
            throw new Services_Exception(tr('Article title is required.'));
        }

        $tikilib = TikiLib::lib('tiki');
        $artlib  = TikiLib::lib('art');

        $heading = $input->heading->text();
        $body    = $input->body->text();
        $topicId = $input->topicId->int();
        $type    = $input->type->text() ?: 'article';
        $lang    = $input->lang->text();

        $publication = $input->publishDate->int() ?: $tikilib->now;

        // replace_article() with articleId = 0 inserts a new article.
        // Argument order matches ArtLib::replace_article():
        // (title, authorName, topicId, useImage, imgname, imgsize, imgtype,
        //  imgdata, heading, body, publishDate, user, articleId, image_x,
        //  image_y, type, topline, subtitle, linkto, image_caption, image_alt,
        //  lang, rating, isfloat, ...)
        $articleId = $artlib->replace_article(
            $title,
            $input->authorName->text() ?: $user,
            $topicId,
            'n',                       // useImage
            '',                        // imgname
            0,                         // imgsize
            '',                        // imgtype
            '',                        // imgdata
            $heading,                  // heading
            $body,                     // body
            $publication,              // publishDate
            $user,                     // user
            0,                         // articleId (0 = create)
            '',                        // image_x
            '',                        // image_y
            $type,                     // type
            '',                        // topline
            $input->description->text(), // subtitle
            '',                        // linkto
            '',                        // image_caption
            '',                        // image_alt
            $lang,                     // lang
            0,                         // rating
            'n',                       // isfloat
            '',                        // emails
            '',                        // from
            '',                        // list_image_x
            '',                        // list_image_y
            'y'                        // ispublished
        );

        if (! $articleId) {
            $errors = Feedback::errorMessages();
            throw new Services_Exception($errors ? implode(' ', $errors) : tr('Article could not be created.'));
        }

        $article = $artlib->get_article($articleId, false);

        return [
            'status'  => 'success',
            'article' => $this->formatArticle($article),
        ];
    }

    /**
     * Updates an existing article.
     * POST /api/articles/{articleId}
     *
     * Only the fields present in the request are changed; everything else is
     * preserved from the existing row, so partial updates are safe.
     *
     * @param $input
     * @return array
     * @throws Services_Exception
     * @throws Services_Exception_NotFound
     * @throws Services_Exception_Denied
     */
    public function action_update_article($input)
    {
        global $user;

        $articleId = $input->articleId->int();
        if (! $articleId) {
            throw new Services_Exception(tr('Article id is required.'));
        }

        $artlib    = TikiLib::lib('art');
        $tikilib   = TikiLib::lib('tiki');
        $existing  = $artlib->get_article($articleId);
        if (! $existing) {
            throw new Services_Exception_NotFound(tr('Article "%0" not found', $articleId));
        }

        $perms = Perms::get('article', $articleId);
        if (! $perms->edit_article) {
            throw new Services_Exception_Denied();
        }

        // Fall back to the existing value for any field not supplied.
        $title       = isset($input['title']) ? $input->title->text() : $existing['title'];
        $heading     = isset($input['heading']) ? $input->heading->text() : $existing['heading'];
        $body        = isset($input['body']) ? $input->body->text() : $existing['body'];
        $topicId     = isset($input['topicId']) ? $input->topicId->int() : (int) $existing['topicId'];
        $type        = isset($input['type']) ? $input->type->text() : $existing['type'];
        $subtitle    = isset($input['description']) ? $input->description->text() : ($existing['subtitle'] ?? '');
        $author      = isset($input['authorName']) ? $input->authorName->text() : ($existing['authorName'] ?? $user);
        $publication = isset($input['publishDate']) ? $input->publishDate->int() : (int) $existing['publishDate'];
        $lang        = isset($input['lang']) ? $input->lang->text() : ($existing['lang'] ?? '');

        // Argument order matches ArtLib::replace_article(); passing the
        // existing articleId updates in place.
        $result = $artlib->replace_article(
            $title,
            $author,
            $topicId,
            'n',                       // useImage
            '',                        // imgname
            0,                         // imgsize
            '',                        // imgtype
            '',                        // imgdata
            $heading,                  // heading
            $body,                     // body
            $publication,              // publishDate
            $user,                     // user
            $articleId,                // existing id = update
            '',                        // image_x
            '',                        // image_y
            $type,                     // type
            '',                        // topline
            $subtitle,                 // subtitle
            '',                        // linkto
            '',                        // image_caption
            '',                        // image_alt
            $lang,                     // lang
            (int) ($existing['rating'] ?? 0), // rating
            'n',                       // isfloat
            '',                        // emails
            '',                        // from
            '',                        // list_image_x
            '',                        // list_image_y
            'y'                        // ispublished
        );

        if (! $result) {
            $errors = Feedback::errorMessages();
            throw new Services_Exception($errors ? implode(' ', $errors) : tr('Article could not be updated.'));
        }

        $article = $artlib->get_article($articleId, false);

        return [
            'status'  => 'success',
            'article' => $this->formatArticle($article),
        ];
    }

    /**
     * Creates an article from a remote URL (existing behaviour, unchanged).
     * @param $input
     * @return array
     */
    public function action_create_from_url($input)
    {
        Services_Exception_Disabled::check('page_content_fetch');
        Services_Exception_Denied::checkGlobal('edit_article');

        $id = null;
        $title = null;
        $url = $input->url->url();
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $url) {
            $lib = TikiLib::lib('pagecontent');

            $data = $lib->grabContent($url);

            if (! $data) {
                throw new Services_Exception_FieldError($input->errorfield->text() ?: 'url', tr('Content could not be loaded.'));
            }
            $data['content'] = trim($data['content']) == '' ? $data['content'] : '~np~' . $data['content'] . '~/np~';
            $data['description'] = '';
            $data['author'] = '';
            $topicId = $input->topicId->int();
            $articleType = $input->type->text();
            $title = $data['title'];

            $hash = md5($data['title'] . $data['description'] . $data['content']);

            $id = TikiDb::get()->table('tiki_articles')->fetchOne('articleId', [
                'linkto' => $url,
            ]) ?: 0;

            if (! $id) {
                $tikilib = TikiLib::lib('tiki');
                $publication = $tikilib->now;
                $rating = 10;

                $artlib = TikiLib::lib('art');
                $id = $artlib->replace_article(
                    $title,
                    $data['author'],
                    $topicId,
                    'n',
                    '',
                    0,
                    '',
                    '',
                    $data['description'],
                    $data['content'],
                    $publication,
                    $GLOBALS['user'],
                    $id,
                    0,
                    0,
                    $articleType,
                    '',
                    '',
                    $url,
                    '',
                    '',
                    $rating,
                    'n',
                    '',
                    '',
                    '',
                    '',
                    'y',
                    true
                );
            }
        }

        $db = TikiDb::get();
        $topics = $db->table('tiki_topics')->fetchMap('topicId', 'name', [], -1, -1, 'name_asc');
        $types = $db->table('tiki_article_types')->fetchColumn('type', []);

        return [
            'title' => tr('Create article from URL'),
            'url' => $url,
            'id' => $id,
            'articleTitle' => $title,
            'topics' => $topics,
            'types' => $types,
        ];
    }
}
