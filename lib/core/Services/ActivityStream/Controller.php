<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_ActivityStream_Controller
{
    private $lib;

    public function setUp()
    {
        $this->lib = TikiLib::lib('unifiedsearch');
        Services_Exception_Disabled::check('wikiplugin_activitystream');
    }

    public function action_render(JitFilter $request)
    {
        global $user, $prefs;

        if ($prefs['activity_stream_disable_indexing'] === 'y') {
            throw new Services_Exception_Enabled('activity_stream_disable_indexing');
        }

        $loginlib = TikiLib::lib('login');
        $encoded = $request->stream->none();
        $page = $request->page->int() ?: 1;
        $userId = $loginlib->getUserId();

        if (! $baseQuery = Tiki_Security::get()->decode($encoded)) {
            throw new Services_Exception_Denied('Invalid request performed.');
        }

        $query = new Search_Query();
        $this->lib->initQuery($query);
        $query->filterType('activity');

        $matches = WikiParser_PluginMatcher::match($baseQuery['body']);

        $builder = new Search_Query_WikiBuilder($query);
        $builder->enableAggregate();
        $builder->apply($matches);

        if ($builder->isNextPossible()) {
            $query->setPage($page);
        }

        $group_ids = TikiLib::lib('tiki')->getUserGroupIds($user);
        $or_groups = '';
        foreach ($group_ids as $group_id) {
            $or_groups .= " OR criticalgrp$group_id OR highgrp$group_id OR lowgrp$group_id ";
        }

        $query->filterMultivalue("critical$userId OR high$userId OR low$userId $or_groups", 'stream');
        $query->filterMultivalue("NOT \"$user\"", 'clear_list');
        $query->setOrder('modification_date_desc');

        if (! $index = $this->lib->getIndex()) {
            throw new Services_Exception_NotAvailable(tr('Activity stream currently unavailable.'));
        }

        $result = $query->search($index);

        $paginationArguments = $builder->getPaginationArguments();

        $resultBuilder = new Search_ResultSet_WikiBuilder($result);
        $resultBuilder->setPaginationArguments($paginationArguments);
        $resultBuilder->apply($matches);

        try {
            $plugin = new Search_Formatter_Plugin_SmartyTemplate('activity/activitystream.tpl');
            $plugin->setFields([
                'like_list' => true,
                'user_groups' => true,
                'contributors' => true,
                'user_followers' => true
            ]);
            $formatter = Search_Formatter_Factory::newFormatter($plugin);
            $out = $formatter->format($result);
        } catch (SmartyException $e) {
            throw new Services_Exception_NotAvailable($e->getMessage());
        }

        return [
            'autoScroll' => $request->autoscroll->int(),
            'pageNumber' => $page,
            'nextPossible' => $builder->isNextPossible(),
            'stream' => $encoded,
            'body' => TikiLib::lib('parser')->parse_data($out, ['is_html' => true]),
        ];
    }
}
