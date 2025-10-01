<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE.

namespace Services\Peertube;

class ServicesPeerTubeController
{
    public function setUp()
    {
        \Services_Exception_Disabled::check('feature_peertube');
    }

    public function actionList($input)
    {
        $perms = \Perms::get();
        if (! $perms->list_videos) {
            throw new \Services_Exception_Denied('Not allowed to list videos');
        }

        $sort_mode = $input->sort_mode->word() ?: 'created_desc';
        $find      = $input->find->text();
        $page_size = min(max(1, $input->maxRecords->int() ?: 25), 100);
        $offset    = max(0, $input->offset->int());
        $page      = (int) floor($offset / $page_size) + 1;

        $peertube = \TikiLib::lib('peertubeuser');
        try {
            $list = $peertube->listVideos($sort_mode, $page, $page_size, $find);
        } catch (\Exception $e) {
            throw new \Services_Exception('Error fetching videos: ' . $e->getMessage());
        }

        $entries = $list->data ?? $list->objects ?? [];
        $total   = $list->total ?? $list->totalCount ?? 0;

        global $prefs;
        $base = rtrim($prefs['peertube_service_url'] ?? '', '/');

        $normalized = [];
        foreach ((array) $entries as $v) {
            $o = is_array($v) ? (object) $v : $v;
            if (! empty($o->thumbnailUrl)) {
                $o->thumbUrl = $o->thumbnailUrl;
            } elseif (! empty($o->thumbnailPath) && $base) {
                $o->thumbUrl = $base . $o->thumbnailPath;
            }
            $normalized[] = $o;
        }

        return [
            'entries'    => $normalized,
            'totalCount' => $total,
            'formId'     => $input->formId->text(),
            'targetName' => $input->targetName->text(),
        ];
    }

    public function actionUpload($input)
    {
        $perms = \Perms::get();
        if (! $perms->upload_videos) {
            throw new \Services_Exception_Denied('Not allowed to upload videos');
        }

        $out = [
            'formId' => $input->formId->text(),
            'targetName' => $input->targetName->text(),
        ];

        return $out;
    }
}

class_alias(\Services\Peertube\ServicesPeerTubeController::class, 'Services_PeerTube_Controller');
