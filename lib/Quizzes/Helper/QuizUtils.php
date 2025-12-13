<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Quizzes\Helper;

use Feedback;
use Laminas\Http\Response;
use Tiki\Lib\Quizzes\HWQuizQuestionMultipleChoice;

class QuizUtils
{
    /**
     * @param $text
     * @return int
     */
    public static function NextText($text)
    {
        $found = -1;
        for ($i = 0, $icount_text = count($text); $i < $icount_text; $i++) {
            if (strlen($text[$i]) > 0) {
                $found = $i;
                break;
            }
        }
        return $found;
    }

    // Find the next blank or retrun the last element
    /**
     * @param $text
     * @return int
     */
    public static function NextBlank($text)
    {
        $found = 0;
        for ($i = 0, $icount_text = count($text); $i < $icount_text; $i++) {
            $found = $i;
            if ($text[$i] == "") {
                break;
            }
        }
        return $found;
    }

    /**
     * @param $s
     */
    public static function quizlib_error_exit($s)
    {
        Feedback::errorAndDie($s, Response::STATUS_CODE_409);
    }

    // Called by tiki-edit_quiz_questions.php
    // Convert a block of text into an array of question objects.
    /**
     * @param $text
     * @return array
     */
    public static function TextToQuestions($text)
    {
        $text = preg_replace("/\r\n/", "\n", $text);
        $text = preg_replace("/\n\r/", "\n", $text);
        $text = preg_replace("/\r/", "\n", $text);
        $text = preg_replace("/\t/", " ", $text);
        $text = preg_replace("/[ ]+/", " ", $text);

        $text = preg_split("/\n/", $text);

        if ($text[count($text) - 1] != "") {
            $text[] = "";
        }

        for ($i = 0, $icount_text = count($text); $i < $icount_text; $i++) {
            $text[$i] = trim($text[$i]);
        }

        $questions = [];

        while (self::NextText($text) != -1) {
            $text = array_slice($text, self::NextText($text));
            $lines = array_slice($text, 0, self::NextBlank($text));
            $text = array_slice($text, self::NextBlank($text));
            if (count($lines) > 0) {
                $question = new HWQuizQuestionMultipleChoice($lines);
                $questions[] = $question;
            }
        }
        return $questions;
    }
}
