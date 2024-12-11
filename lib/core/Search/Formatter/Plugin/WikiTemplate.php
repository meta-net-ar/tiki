<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_Plugin_WikiTemplate implements Search_Formatter_Plugin_Interface
{
    private $template;
    private $format;
    private string $editable;
    private string $editableId;
    private ?string $context;

    public function __construct($template)
    {
        $this->template = WikiParser_PluginMatcher::match($template);
        $this->format = self::FORMAT_WIKI;
        $this->editable = '';
    }

    public function setRaw($isRaw)
    {
        $this->format = $isRaw ? self::FORMAT_HTML : self::FORMAT_WIKI;
    }

    public function setEditable(string $editable, string $id)
    {
        $this->editable = $editable;
        $this->editableId = $id;
    }

    public function setContext(?string $context)
    {
        $this->context = $context;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getFields()
    {
        $parser = new WikiParser_PluginArgumentParser();

        $fields = [];
        foreach ($this->template as $match) {
            $name = $match->getName();

            if ($name === 'display') {
                $arguments = $parser->parse($match->getArguments());

                if (isset($arguments['name']) && ! isset($fields[$arguments['name']])) {
                    $fields[$arguments['name']] = isset($arguments['default']) ? $arguments['default'] : null;
                }
            }
        }

        return $fields;
    }

    public function prepareEntry($valueFormatter)
    {
        $parser = new WikiParser_PluginArgumentParser();

        $matches = clone $this->template;

        foreach ($matches as $match) {
            $name = $match->getName();

            if ($name === 'display') {
                $match->replaceWith((string) $this->processDisplay($valueFormatter, $match->getBody(), $match->getArguments()));
            } elseif ($name === 'calc') {
                $value = $this->processCalc($valueFormatter, $match);
                $match->replaceWith((string) $value);

                $arguments = $parser->parse($match->getArguments());
                if (! empty($arguments['cache_as'])) {
                    Math_Formula_Runner::$cached_variables[$arguments['cache_as']] = $value;
                }
            }
        }

        return $matches->getText();
    }

    public function renderEntries(Search_ResultSet $entries)
    {
        if ($this->editable && isset($_REQUEST[$this->editableId])) {
            return $this->wrapEditableByContext($_REQUEST[$this->editableId]);
        }

        $out = '';
        foreach ($entries as $entry) {
            $out .= $entry;
        }
        if ($this->editable) {
            return $this->wrapEditableByContext($out);
        } else {
            return $out;
        }
    }

    private function wrapEditableByContext($content)
    {
        if ($this->context !== 'actions') {
            return new Tiki_Render_Editable(
                $content,
                [
                    'layout' => $this->editable,
                    'field' => [
                        'id' => $this->editableId,
                        'type' => 'form',
                        'wysiwyg' => false,
                    ],
                ],
            );
        } else {
            return $content;
        }
    }

    private function processDisplay($valueFormatter, $body, $arguments)
    {
        $parser = new WikiParser_PluginArgumentParser();
        $arguments = $parser->parse($arguments);

        $name = $arguments['name'];

        if (isset($arguments['format'])) {
            $format = $arguments['format'];
        } else {
            $format = 'plain';
        }

        unset($arguments['format']);
        unset($arguments['name']);
        return $valueFormatter->$format($name, $arguments);
    }

    /**
     * Process {calc} plugins
     *
     * @param \Search_Formatter_ValueFormatter $valueFormatter
     * @param \WikiParser_PluginMatcher_Match $match
     *
     * @return mixed
     */
    private function processCalc($valueFormatter, $match)
    {
        $runner = new Math_Formula_Runner(
            [
                'Math_Formula_Function_' => '',
                'Tiki_Formula_Function_' => '',
            ]
        );
        try {
            $runner->setFormula($match->getBody());
            $runner->setVariables($valueFormatter->getPlainValues());
            $value = $runner->evaluate();
        } catch (Math_Formula_Exception $e) {
            $value = tr('Error evaluating formula %0: %1', $match->getBody(), $e->getMessage());
        }
        return $value;
    }
}
