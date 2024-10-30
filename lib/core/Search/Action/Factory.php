<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Action_Factory
{
    private $actions = [];

    public function register(array $actions)
    {
        $this->actions = array_merge($this->actions, $actions);
    }

    public function fromMatch($match)
    {
        $parser = new WikiParser_PluginArgumentParser();
        $arrayBuilder = new Search_Formatter_ArrayBuilder();
        $arguments = $parser->parse($match->getArguments());

        if (! empty($arguments['name'])) {
            $default = isset($arguments['default']) && ($arguments['default'] === 'y' || $arguments['default'] === '1');
            if (isset($arguments['default']) && $arguments['default'] != 'y' && $arguments['default'] != '1') {
                Feedback::error(tr("The possible value for the default parameter is 'y' or '1'. Not putting this parameter means that the action is not set as default. The value you provided for the default parameter is %0%1%2 on the action named %0%3%2", '<code>', htmlentities($arguments['default']), '</code>', htmlentities($arguments['name'])));
            }
            $sequence = $this->build($arguments['name'], $default, $arrayBuilder->getData($match->getBody()));

            if (isset($arguments['group'])) {
                $sequence->setRequiredGroup($arguments['group']);
            }

            return $sequence;
        }
    }

    public function build($name, $default, array $data)
    {
        $sequence = new Search_Action_Sequence($name, $default);

        if (! isset($data['step'])) {
            $data['step'] = [];
        }

        if (isset($data['step']['action'])) {
            $data['step'] = [$data['step']];
        }

        foreach ($data['step'] as $definition) {
            $sequence->addStep($this->buildStep($definition));
        }

        return $sequence;
    }

    private function buildStep($definition)
    {
        if (empty($definition['action'])) {
            return new Search_Action_UnknownStep();
        }

        $action = trim($definition['action']);

        if (! isset($this->actions[$action])) {
            return new Search_Action_UnknownStep($action);
        }

        $actionClass = $this->actions[$action];
        unset($definition['action']);

        if (! class_exists($actionClass)) {
            return new Search_Action_UnknownStep($action);
        }

        return new Search_Action_ActionStep(new $actionClass(), $definition);
    }
}
