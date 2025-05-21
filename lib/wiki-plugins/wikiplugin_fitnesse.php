<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\WikiPlugin\Fixture\MockTrackerField;
use Tiki\WikiPlugin\Fixture\Table;

function wikiplugin_fitnesse_info()
{
    return [
        'name' => tra('Fitnesse Test Suite'),
        'documentation' => 'PluginFitnesse',
        'description' => tra('Create test suites for applications built using Tiki'),
        'prefs' => ['wikiplugin_fitnesse'],
        'default' => 'n',
        'format' => 'wiki',
        'body' => tra('Test execution scenario'),
        'filter' => 'wikicontent',
        'iconname' => 'pencil',
        'introduced' => 12.1,
        'tags' => ['advanced'],
        'profile_reference' => 'fitnesse_content',
        'params' => [
        ],
    ];
}

function wikiplugin_fitnesse($data, $params)
{
    $runner = Tracker_Field_Math::getRunner();
    $mock = new MockTrackerField();
    $runner->mockFunction('tracker-field', $mock);

    $fixtures = [
        'trackermath' => 'wp_fixture_tracker_math',
        'trackerdata' => function ($data, $params) use ($mock) {
            return wp_fixture_tracker_data($data, $params, $mock);
        },
    ];

    $matches = WikiParser_PluginMatcher::match($data);
    $argParser = new WikiParser_PluginArgumentParser();

    foreach ($matches as $table) {
        $fixture = $table->getName();

        $arguments = $table->getArguments();
        $arguments = $argParser->parse($arguments);
        $body = trim($table->getBody());

        if (isset($fixtures[$fixture])) {
            $replace = call_user_func($fixtures[$fixture], $body, new JitFilter($arguments));
            $table->replaceWith($replace);
        } else {
            $data = new Table($body);
            $table->replaceWith('__' . tr('Fixture not found: %0', $fixture) . "__\n$data");
        }
    }

    Tracker_Field_Math::resetRunner();
    return $matches->getText();
}

function wp_fixture_tracker_math($data, $params)
{
    $table = new Table($data);
    $trackerId = $params->trackerId->int();
    $tracker = Tracker_Definition::get($trackerId);

    if (! $tracker) {
        return '__' . tr('Tracker not found.') . '__';
    }

    $smarty = TikiLib::lib('smarty');
    $url = smarty_modifier_sefurl($trackerId, 'tracker');
    $table->setTitle(tr('Tracker Math for [%0|%1]', $url, $tracker->getConfiguration('name')));

    $factory = new Tracker_Field_Factory($tracker);

    $checks = [];

    $headings = $table->getHeadings();
    foreach ($headings as $key => $heading) {
        $permName = rtrim($heading, '?');

        if (! $field = $tracker->getFieldFromPermName($permName)) {
            return '__' . tr('Tracker Field not found: %0', $permName) . '__';
        }

        if ($permName != $heading) {
            $checks[$key] = $factory->getHandler($field);

            if (! $checks[$key] instanceof Tracker_Field_Math) {
                return '__' . tr('Field is not a math field: %0', $permName) . '__';
            }
        }
    }

    foreach ($table as $row) {
        $variableNames = array_map('rtrim', $headings, array_fill(0, count($headings), '?'));
        $variables = array_combine($variableNames, $row);

        foreach ($checks as $key => $field) {
            $out = $field->handleFinalSave($variables);

            if ($out == $row[$key]) {
                $table->setValue($key, $out, 'green');
            } else {
                $table->setValue($key, tr("%0 (expect: %1)", $out, $row[$key]), 'red');
            }
        }
    }

    return $table;
}

function wp_fixture_tracker_data($data, $params, $mock)
{
    $table = new Table($data);
    $headings = $table->getHeadings();

    $trackerId = $params->trackerId->int();
    $tracker = Tracker_Definition::get($trackerId);

    if (! $tracker) {
        return '__' . tr('Tracker not found.') . '__';
    }

    $smarty = TikiLib::lib('smarty');
    $url = smarty_modifier_sefurl($trackerId, 'tracker');
    $table->setTitle(tr('Tracker Data for [%0|%1]', $url, $tracker->getConfiguration('name')));

    if (! in_array('itemId', $headings)) {
        return '__' . tr('Table must contain at least one field named itemId') . '__';
    }

    foreach ($headings as $permName) {
        if ($permName != 'itemId' && ! $field = $tracker->getFieldFromPermName($permName)) {
            return '__' . tr('Tracker Field not found: %0', $permName) . '__';
        }
    }

    foreach ($table as $row) {
        $fields = array_combine($headings, $row);
        $itemId = $fields['itemId'];
        unset($fields['itemId']);

        $mock->addValues($itemId, $fields);
    }

    return $table;
}
