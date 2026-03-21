<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Quizzes;

use TikiLib;

class Quiz
{
    public $sAuthor;
    /**
     * @var string
     */
    public $nShuffleQuestions;
    /**
     * @var string
     */
    public $forum;
    /**
     * @var string
     */
    public $forumName;
    /**
     * @var string
     */
    public $prologue;
    /**
     * @var string
     */
    public $epilogue;
    public $author;
    public $id;
    public $bDeleted;
    public $timestamp;
    public $nAuthor;
    public $bOnline;
    public $nTaken;
    public $sName;
    public $sDescription;
    public $datePub;
    public $dateExp;
    public $bRandomQuestions;
    public $nRandomQuestions;
    public $bShuffleQuestions;
    public $bShuffleAnswers;
    public $bLimitQuestionsPerPage;
    public $nLimitQuestionsPerPage;
    public $bTimeLimited;
    public $nTimeLimit;
    public $bMultiSession;
    public $bCanRepeat;
    public $nCanRepeat;
    public $sGradingMethod;
    public $sShowScore;
    public $sShowCorrectAnswers;
    public $sPublishStats;
    public $bAdditionalQuestions;
    public $bForum;
    public $sForum;
    public $sPrologue;
    public $sData;
    public $sEpilogue;
    public $dbFields;

    public function __construct()
    {
        global $user;
        $userlib = TikiLib::lib('user');
        $this->dbFields = [
                "id",
                "bDeleted",
                "timestamp",
                "nAuthor",
                "bOnline",
                "nTaken",
                "sName",
                "sDescription",
                "datePub",
                "dateExp",
                "bRandomQuestions",
                "nRandomQuestions",
                "bShuffleQuestions",
                "bShuffleAnswers",
                "bLimitQuestionsPerPage",
                "nLimitQuestionsPerPage",
                "bTimeLimited",
                "nTimeLimit",
                "bMultiSession",
                "bCanRepeat",
                "nCanRepeat",
                "sGradingMethod",
                "sShowScore",
                "sShowCorrectAnswers",
                "sPublishStats",
                "bAdditionalQuestions",
                "bForum",
                "sForum",
                "sPrologue",
                "sData",
                "sEpilogue"
        ];
        $this->id = 0;
        $this->bDeleted = 0;
        $this->timestamp = time();
        $this->nAuthor = $userlib->get_user_id($user);
        $this->sAuthor = $user;
        $this->bOnline = 'n';
        $this->nTaken = 'n';
        $this->sName = "";
        $this->sDescription = "";
        $this->datePub = time();
        $this->dateExp = TikiLib::make_time(0, 0, 0, 1, 1, TikiLib::date_format("%Y") + 10);
        $this->bRandomQuestions = "y";
        $this->nRandomQuestions = 10;
        $this->nShuffleQuestions = "y";
        $this->bShuffleAnswers = "y";
        $this->bLimitQuestionsPerPage = "y";
        $this->nLimitQuestionsPerPage = 1;
        $this->bTimeLimited = "n";
        $this->nTimeLimit = "1";
        $this->bMultiSession = "n";
        $this->bCanRepeat = "y";
        $this->nCanRepeat = "unlimited";
        $this->sGradingMethod = "machine";
        $this->sShowScore = "immediately";
        $this->sShowCorrectAnswers = "immediately";
        $this->sPublishStats = "immediately";
        $this->bAdditionalQuestions = "n";

        $this->forum = "n";
        $this->forumName = "";
        $this->prologue = "";
        $this->epilogue = "";
    }

    // Use any data in the array to replace the instance data.
    /**
     * @param $data
     */
    public function data_load($data)
    {
        foreach ($this as $key => $val) {
            if (isset($data[$key]) && ($data[$key] != $val)) {
                $this->$key = $data[$key];
            }
        }
    }

    /**
     * @param $quiz
     */
    public function compare($quiz)
    {
    }

    public function getAnswerCount()
    {
        // How many possible answers (i.e. choices in a multiple-choice)
    }
}
