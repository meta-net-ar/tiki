<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class BaseTest extends \SearchIndexBase
{
    use IndexBuilder;

    protected function setUp(): void
    {
        $this->index = $this->createIndex('_base');
        $this->index->destroy();

        $this->populate($this->index);
    }

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }

    protected function assertResultCount($count, $filterMethod, $argument)
    {
        $arguments = func_get_args();
        $arguments = array_slice($arguments, 2);

        if ($filterMethod == 'filterTextRange') {
            $this->addWarning('Manticore does not support text range searches.');
            return;
        } else {
            return parent::assertResultCount($count, $filterMethod, ...$arguments);
        }
    }
}
