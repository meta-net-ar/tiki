<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Quizzes;

// A Yes-No quiz question
// e.g.
//   $question = "Do you wiki?";
//   $answer   = -1 (unknown), 0 (no), 1 (yes)
/**
 *
 */
class HWQuizQuestionYesNo extends HWQuizQuestion
{
    public $question;
    public $answer  = -1;

    /**
     * @param $lines
     */
    public function __construct($lines)
    {
        $this->from_text($lines);
    }

    // Import from text array
    // $lines is in array of text items.
    //   The 0th line is the question.
    //   The first line is the answer.
    /**
     * @param $lines
     */
    public function from_text($lines)
    {
        $this->question = $lines[0];
        if (preg_match("/^\s*[Yy][Ee][Ss]\s*$/", $lines[1])) {
            // Ignore spaces and case
            $this->answer = 1;
        } elseif (preg_match("/^\s*[Nn][Oo]\s*$/", $lines[1])) {
            // Ignore spaces and case
            $this->answer = 0;
        } else {
            $this->answer = -1;
        }
    }

    /**
     * @param bool $show_answer
     * @return array
     */
    public function to_text($show_answer = false)
    {
        // Export the question to an array of text lines.
        $lines = [];
        array_push($lines, $this->question);
        if ($this->answer == 1) {
            array_push($lines, " Yes");
        } elseif ($this->answer == 0) {
            array_push($lines, " No");
        } else {
            array_push($lines, " Unknown");
        }
        return $lines;
    }

    public function dump()
    {
        echo "question = \"" . $this->question . "\"\n";
        echo "answer = $this->answer\n";
    }
}
