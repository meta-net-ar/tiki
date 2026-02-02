<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Quizzes;

class HWQuizQuestion
{
    public $question;

    /**
     * @param $lines
     */
    public function from_text($lines)
    {
        // Set the question according to an array of text lines.
    }
    public function getQuestion()
    {
        return $this->question;
    }
    public function to_text()
    {
        // Export the question to an array of text lines.
    }
    public function getAnswerCount()
    {
        // How many possible answers (i.e. choices in a multiple-choice)
    }
}
