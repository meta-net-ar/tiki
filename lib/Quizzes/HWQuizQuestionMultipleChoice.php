<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Quizzes;

// A multiple-choice quiz question
// e.g.
//   $question = "What is your favorite color?";
//   $choices = Array(Array('text'=>"Red",  'correct'=>1),
//                    Array('text'=>"Blue", 'correct'=>1),
//                    Array('text'=>"Green",'correct'=>1));
//   Any of the answers are correct in this example.
/**
 *
 */
class HWQuizQuestionMultipleChoice extends HWQuizQuestion
{
    public $choices  = [];

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
    //   The rest of the lines are answers.
    //   Correct answers start with a "*"
    /**
     * @param $lines
     */
    public function from_text($lines)
    {
        $this->question = $lines[0];
        $this->choices  = [];
        $lines = array_slice($lines, 1);
        foreach ($lines as $line) {
            if (preg_match("/^\*\s*(.*)/", $line, $match)) {
                // Ignore spaces after the "*"
                $a = ['text' => $match[1],'correct' => 1];
            } else {
                $a = ['text' => $line,'correct' => 0];
            }
            array_push($this->choices, $a);
        }
    }

    // Export the question to an array of text lines.
    /**
     * @param bool $show_answer
     * @return array
     */
    public function to_text($show_answer = false)
    {
        $lines = [];
        array_push($lines, $this->question);
        foreach ($this->choices as $choice) {
            if ($show_answer && $choice['correct']) {
                array_push($lines, "*" . $choice['text']);
            } else {
                array_push($lines, " " . $choice['text']);
            }
        }
        return $lines;
    }

    /**
     * @return int
     */
    public function getChoiceCount()
    {
        return count($this->choices);
    }

    /**
     * @param $i
     * @return mixed
     */
    public function getChoice($i)
    {
        return $this->choices[$i]['text'];
    }

    /**
     * @param $i
     * @return mixed
     */
    public function getCorrect($i)
    {
        return $this->choices[$i]['correct'];
    }

    public function dump()
    {
        echo "question = \"" . $this->question . "\"\n";
        echo "choices =\n";
        foreach ($this->choices as $choice) {
            if ($choice['correct']) {
                echo "*";
            } else {
                echo " ";
            }
            echo $choice['text'] . "\n";
        }
    }
}
