<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_Plugin_ReportTemplate implements Search_Formatter_Plugin_Interface
{
    private $template;
    private $format;

    public function __construct($template)
    {
        $this->template = WikiParser_PluginMatcher::match($template);
        $this->format = self::FORMAT_WIKI;
    }

    public function setRaw($isRaw)
    {
        $this->format = $isRaw ? self::FORMAT_HTML : self::FORMAT_WIKI;
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

                if (isset($arguments['name'])) {
                    if (! isset($fields[$arguments['name']])) {
                        $fields[$arguments['name']] = $arguments['default'] ?? null;
                    } else {
                        Feedback::warning(tr('Duplicate field name used in display blocks, only the first one defined will be used: %0', $arguments['name']));
                    }
                }
            }
        }

        return $fields;
    }

    public function prepareEntry($valueFormatter)
    {
        // TODO: handle both plain values from the search index for the report and display formatted values in the output
        return $valueFormatter->getPlainValues();
    }

    public function renderEntries(Search_ResultSet $entries)
    {
        $parser = new WikiParser_PluginArgumentParser();

        $variables = ['results' => (array)$entries];

        $matches = clone $this->template;
        foreach ($matches as $match) {
            $name = $match->getName();

            if ($name === 'groupreport') {
                $arguments = $parser->parse($match->getArguments());
                if (isset($arguments['field'], $arguments['value'], $arguments['name'])) {
                    $filtered = [];
                    foreach ($entries as $entry) {
                        if ($entry[$arguments['field']] == $arguments['value']) {
                            $filtered[] = $entry;
                        }
                    }
                    $variables[$arguments['name']] = $filtered;
                }
                $match->replaceWith('');
            }
        }
        foreach ($matches as $match) {
            $name = $match->getName();

            if ($name === 'calc') {
                $runner = new Math_Formula_Runner(
                    [
                        'Math_Formula_Function_' => '',
                        'Tiki_Formula_Function_' => '',
                    ]
                );
                $value = '';
                try {
                    $runner->setFormula($match->getBody());
                    $runner->setVariables($variables);
                    $value = $runner->evaluate();
                } catch (Math_Formula_Exception $e) {
                    $value = tr('Error evaluating formula %0: %1', $match->getBody(), $e->getMessage());
                }
                $match->replaceWith((string)$value);

                $arguments = $parser->parse($match->getArguments());
                if (! empty($arguments['cache_as'])) {
                    Math_Formula_Runner::$cached_variables[$arguments['cache_as']] = $value;
                }
            }
        }
        return $matches->getText();
    }
}
