<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class Services_Annotator_Controller
 *
 * Linking the recogito text annotator to inline comments
 */

const RANGE_ATTRIBUTE = 'tiki.comment.ranges';

class Services_Comment_AnnotationController
{
    /** @var  Comments */
    private $commentslib;
    /** @var  Services_Comment_Controller */
    private $commentController;


    /**
     * @throws Exception
     * @throws Services_Exception_Disabled
     */
    public function setUp()
    {
        Services_Exception_Disabled::check('comments_inline_annotator');

        $this->commentslib = TikiLib::lib('comments');
        $this->commentController = new Services_Comment_Controller();
    }

    /**
     * Create an inline comment
     *
     * @param jitFilter $input
     *        string    text    comment text
     *        string    quote   quoted text on page
     *        string    params  json encoded W3C annotation describing the quoted text
     *        string    uri     actually object-type:object-id identifier for the tiki object to be commented
     *        string    ticket  csrf ticket
     *
     * @return array    the new comment threadId plus a fresh csrf ticket
     *
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     */

    public function action_create($input)
    {
        global $user;

        $util = new Services_Utilities();

        if (! $util->checkCsrf()) {
            throw new Services_Exception(tr('Invalid security ticket.'), 401);
        }

        $text = $input->text->wikicontent();
        $quote = $input->quote->text();
        $annotation = $this->decodeAnnotation($input);

        list($objectType, $objectId) = $this->splitIdentifier($input);

        if (! $this->commentController->canPost($objectType, $objectId)) {
            throw new Services_Exception_Denied();
        }

        $comment = $this->formatComment($quote, $text);
        $title = $this->createTitle($text);
        $messageId = '';

        // create the comment
        $threadId = $this->commentslib->post_new_comment(
            $objectType . ':' . $objectId,
            0,
            $user,
            $title,
            $comment,
            $messageId
        );

        TikiLib::lib('attribute')->set_attribute(
            'comment',
            $threadId,
            RANGE_ATTRIBUTE,
            json_encode($annotation)
        );

        require_once(__DIR__ . '/../../../notifications/notificationemaillib.php');
        sendCommentNotification($objectType, $objectId, $title, $comment, $threadId, '');

        return [
            'id' => $threadId,
            'threadId' => $threadId,
            'ticket' => $this->refreshTicket($util),
        ];
    }

    /**
     * Update an inline comment
     *
     * @param jitFilter $input
     *        int       threadId    comment threadId
     *        string    text        comment text
     *        string    quote       quoted text on page
     *        string    params      json encoded W3C annotation describing the quoted text
     *        string    ticket      csrf ticket
     *
     * @return array    the edited comment plus a fresh csrf ticket
     *
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     */

    public function action_update($input)
    {
        $util = new Services_Utilities();

        if (! $util->checkCsrf()) {
            throw new Services_Exception(tr('Invalid security ticket.'), 401);
        }

        $threadId = $input->threadId->int();

        if (! $threadId) {
            throw new Services_Exception(tr('Thread not specified.'), 400);
        }

        $annotation = $this->decodeAnnotation($input);
        $text = $input->text->wikicontent();
        $quote = $input->quote->text();

        $input->offsetSet('data', $this->formatComment($quote, $text));
        $input->offsetSet('title', $this->createTitle($text));
        $input->offsetSet('edit', 1);
        $input->offsetSet('threadId', $threadId);

        $ret = $this->commentController->action_edit($input);

        if (! empty($ret['errors'])) {
            throw new Services_Exception(implode(' ', $ret['errors']), 400);
        }

        TikiLib::lib('attribute')->set_attribute(
            'comment',
            $threadId,
            RANGE_ATTRIBUTE,
            json_encode($annotation)
        );

        $ret['ticket'] = $this->refreshTicket($util);

        return $ret;
    }

    /**
     * Remove an inline comment
     *
     * @param jitFilter $input
     *        int       threadId  comment id to delete
     *        string    ticket    csrf ticket
     *
     * @return array
     * @throws Services_Exception
     */

    public function action_destroy($input)
    {
        $util = new Services_Utilities();

        if (! $util->checkCsrf()) {
            throw new Services_Exception(tr('Invalid security ticket.'), 401);
        }

        $threadId = $input->threadId->int();

        if (! $threadId) {
            throw new Services_Exception(tr('Thread not specified.'), 400);
        }

        $input->offsetSet('threadId', $threadId);
        $input->offsetSet('confirm', 1);

        $ret = $this->commentController->action_remove($input);

        $ret['id'] = $ret['threadId'];
        $ret['ticket'] = $this->refreshTicket($util);

        return $ret;
    }

    /**
     * List inline comments for a tiki object
     *
     * @param jitFilter $input
     *        int       limit   page size TODO
     *        int       offset  page start
     *        string    uri     object-type:object-id identifier for the tiki object to search
     *
     * @return array    [total, rows]
     * @throws Exception
     * @throws Services_Exception
     */

    public function action_search($input)
    {
        global $prefs;

        $tikilib = TikiLib::lib('tiki');
        $userlib = TikiLib::lib('user');

        list($objectType, $objectId) = $this->splitIdentifier($input);

        $list = $this->commentController->action_list(new JitFilter([
            'type' => $objectType,
            'objectId' => $objectId,
        ]));

        $comments = [];

        foreach ($list['comments'] as $comment) {
            if (str_starts_with($comment['data'], ';note:')) {         // only the "inline" ones starting ;note: so far
                $data = explode("\n", $comment['data'], 2);
                $quote = trim(substr($data[0], 6));
                $text = isset($data[1]) ? trim($data[1]) : '';

                $annotation_data = json_decode(
                    TikiLib::lib('attribute')->get_attribute(
                        'comment',
                        $comment['threadId'],
                        RANGE_ATTRIBUTE
                    ),
                    true
                );

                $permissions = [
                    'create' => $list['allow_post'],
                    'update' => $comment['can_edit'],
                    'delete' => $list['allow_remove'],
                ];

                $annotation = [
                    'id'          => $comment['threadId'],
                    'text'        => $text,
                    'quote'       => $quote,
                    'created'     => $tikilib->get_iso8601_datetime($comment['commentDate']),
                    'updated'     => $tikilib->get_iso8601_datetime($comment['commentDate']),   // we don't have a commentUpdated column?
                    'annotation'  => $annotation_data ? $annotation_data : [],
                    'permissions' => $permissions,
                    'threadId'    => $comment['threadId'],
                ];

                if ($prefs['comments_inline_annotator_with_info'] === 'y') {
                    $annotation['commentDate'] = $tikilib->get_short_datetime($comment['commentDate']);
                    $annotation['user']        = $comment['userName'];
                    $annotation['realName']    = $userlib->clean_user($comment['userName']);
                }

                $comments[] = $annotation;
            }
        };

        return [
            'total' => count($comments),    // TODO pagination
            'rows' => $comments,
        ];
    }

    /**
     * Split the "uri" input into object type and id
     *
     * @param jitFilter $input
     * @return array    [objectType, objectId]
     * @throws Services_Exception
     */
    private function splitIdentifier($input)
    {
        $identifier = urldecode($input->uri->url());
        $parts = explode(':', $identifier, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new Services_Exception(tr('Object not specified.'), 400);
        }

        return $parts;
    }

    /**
     * Decode the W3C annotation sent as a json string in the "params" field
     *
     * @param jitFilter $input
     * @return array
     */
    private function decodeAnnotation($input)
    {
        $raw = $input->params->none();

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Issue a fresh csrf ticket for the next call
     *
     * @param Services_Utilities $util
     * @return mixed
     * @throws Exception
     */
    private function refreshTicket(Services_Utilities $util)
    {
        $util->setTicket();

        return $util->getTicket();
    }

    /**
     * @param $text
     * @return string
     * @throws Exception
     * @throws SmartyException
     */
    private function createTitle($text)
    {
        $smarty = TikiLib::lib('smarty');
        return \SmartyTiki\Modifier\Truncate::apply($text, 50);
    }

    /**
     * @param $quote
     * @param $text
     * @return string
     */
    private function formatComment($quote, $text)
    {
        $safeQuote = str_replace(["\n", "\r"], ' ', $quote);
        $safeQuote = addslashes($safeQuote);
        return ';note:' . $safeQuote . "\n\n" . $text;
    }
}
