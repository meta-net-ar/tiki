<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Quizzes;

use Perms;
use Tiki\Lib\Quizzes\Quiz;
use TikiLib;

/**
 *
 */
class QuizLib extends TikiLib
{
    /**
     * @param int $quizId
     *
     * @return array|null
     */
    public function get_quiz(int $quizId): ?array
    {
        $query = "select * from `tiki_quizzes` where `quizId`=?";

        $result = $this->query($query, [(int) $quizId]);

        if (! $result->numRows()) {
            return null;
        }

        return $result->fetchRow();
    }

    public function compute_quiz_stats()
    {
        $query = "
        SELECT 
            q.quizId, 
            q.name AS quizName, 
            COUNT(uq.quizId) AS timesTaken, 
            AVG(uq.points) AS avgpoints, 
            MAX(uq.maxPoints) AS maxPoints, 
            AVG(uq.timeTaken) AS avgtime
        FROM tiki_quizzes q
        INNER JOIN tiki_user_quizzes uq ON q.quizId = uq.quizId
        GROUP BY q.quizId, q.name
    ";

        $result = $this->fetchAll($query, []);

        $quizStatsSum = $this->table('tiki_quiz_stats_sum');

        foreach ($result as $res) {
            $quizId = $res["quizId"];
            $maxPoints = $res['maxPoints'];
            $avgpoints = $res['avgpoints'];
            $avgavg = ($maxPoints > 0) ? $avgpoints / $maxPoints * 100 : 0.0;

            $quizStatsSum->delete(['quizId' => (int) $quizId,]);
            $quizStatsSum->insert(
                [
                    'quizId' => (int) $quizId,
                    'quizName' => $res['quizName'],
                    'timesTaken' => (int) $res['timesTaken'],
                    'avgpoints' => (float) $avgpoints,
                    'avgtime' => (float) $res['avgtime'],
                    'avgavg' => (float) $avgavg,
                ]
            );
        }
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param null $find
     * @return array
     */
    public function list_quizzes($offset, $maxRecords, $sort_mode = 'name_desc', $find = null)
    {

        $quizzes = $this->table('tiki_quizzes');
        $conditions = [];

        if (! empty($find)) {
            $findesc = '%' . $find . '%';
            $conditions['search'] = $quizzes->expr('(`name` like ? or `description` like ?)', [$findesc, $findesc]);
        }

        $result = $quizzes->fetchColumn('quizId', $conditions);
        $res = $ret = $retids = [];
        $n = 0;

        //FIXME Perm:filter ?
        foreach ($result as $res) {
            $objperm = Perms::get('quizzes', $res);

            if ($objperm->take_quiz) {
                if (($maxRecords == -1) || (($n >= $offset) && ($n < ($offset + $maxRecords)))) {
                    $retids[] = $res;
                }
                $n++;
            }
        }

        if ($n > 0) {
            $result = $quizzes->fetchAll(
                $quizzes->all(),
                ['quizId' => $quizzes->in($retids)],
                -1,
                -1,
                $quizzes->expr($this->convertSortMode($sort_mode))
            );

            $questions = $this->table('tiki_quiz_questions');
            $results = $this->table('tiki_quiz_results');

            foreach ($result as $res) {
                $res['questions'] = $questions->fetchCount(['quizId' => (int) $res['quizId']]);
                $res['results'] = $results->fetchCount(['quizId' => (int) $res['quizId']]);
                $ret[] = $res;
            }
        }

        return [
            'data' => $ret,
            'count' => $n,
        ];
    }

    /**
     * @param int $userResultId
     *
     * @return array|null
     */
    public function get_user_quiz_result(int $userResultId): ?array
    {
        $query = "select * from `tiki_user_quizzes` where `userResultId`=?";

        $result = $this->query($query, [$userResultId]);

        if (! $result->numRows()) {
            return null;
        }

        return $result->fetchRow();
    }

    /**
     * @param $quizId
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @return array
     */
    public function list_quiz_question_stats($quizId, $offset = 0, $maxRecords = -1, $sort_mode = 'position_asc', $find = '')
    {

        $query = "select distinct(tqs.`questionId`)"
                        . " from `tiki_quiz_stats` tqs,`tiki_quiz_questions` tqq"
                        . " where tqs.`questionId`=tqq.`questionId` and tqs.`quizId` = ? order by "
                        . $this->convertSortMode($sort_mode);

        $result = $this->query($query, [(int) $quizId]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $question = $this->getOne("select `question` from `tiki_quiz_questions` where `questionId`=?", [(int) $res["questionId"]]);

            $total_votes = $this->getOne(
                "select sum(`votes`) from `tiki_quiz_stats` where `quizId`=? and `questionId`=?",
                [(int) $quizId, (int) $res["questionId"]]
            );
            $query2 = "select tqq.`optionId`,`votes`,`optionText`"
                                . " from `tiki_quiz_stats` tqq,`tiki_quiz_question_options` tqo"
                                . " where tqq.`optionId`=tqo.`optionId` and tqq.`questionId`=?"
                                ;
            $result2 = $this->query($query2, [(int) $res["questionId"]]);
            $options = [];

            while ($res = $result2->fetchRow()) {
                $opt = [];

                $opt["optionText"] = $res["optionText"];
                $opt["votes"] = $res["votes"];
                $opt["avg"] = $res["votes"] / $total_votes * 100;
                $options[] = $opt;
            }

            $ques = [];
            $ques["options"] = $options;
            $ques["question"] = $question;
            $ret[] = $ques;
        }

        return $ret;
    }

    /**
     * @param $answerUploadId
     */
    public function download_answer($answerUploadId)
    {

        $query = "SELECT `filecontent`, `filetype`, `filename`, `filesize` FROM `tiki_user_answers_uploads` WHERE `answerUploadId`=?";

        $result = $this->query($query, [(int) $answerUploadId]);

        while ($res = $result->fetchRow()) {
            $data = $res['filecontent'];
            $name = $res['filename'];
            $type = $res['filetype'];
            $size = $res['filesize'];
        }

        $name = htmlspecialchars($name);

        header("Content-type: $type");
        header("Content-length: $size");
        header("Content-Disposition: attachment; filename=\"$name\"");
        header("Content-Description: PHP Generated Data");
        print $data;
    }


    /**
     * @param $userResultId
     * @return array
     */
    public function get_user_quiz_questions($userResultId)
    {
        $query = "select distinct(tqs.`questionId`) from `tiki_user_answers` tqs,`tiki_quiz_questions` tqq"
                        . " where tqs.`questionId`=tqq.`questionId` and tqs.`userResultId` = ? order by `position` desc";

        $result = $this->query($query, [(int) $userResultId]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $question = $this->getOne("select `question` from `tiki_quiz_questions` where `questionId`=?", [(int) $res["questionId"]]);

            $questionId = $res["questionId"];

            $query2 = "select tqq.`optionId`,tqo.`points`,`optionText`"
                                . " from `tiki_user_answers` tqq,`tiki_quiz_question_options` tqo"
                                . " where tqq.`optionId`=tqo.`optionId` and tqq.`userResultId`=? and tqq.`questionId`=?";
            $result2 = $this->query($query2, [(int) $userResultId, (int) $questionId]);
            $options = [];

            while ($res = $result2->fetchRow()) {
                $opt = [];

                $opt["optionText"] = $res["optionText"];
                $opt["points"] = $res["points"];

                $query3 = "select `answerUploadId`, `filename` from `tiki_user_answers_uploads` where `userResultId` = ? and `questionId` = ?";
                $result3 = $this->query($query3, [(int) $userResultId, (int) $questionId]);

                while ($res2 = $result3->fetchRow()) {
                    $opt["filename"] = $res2["filename"];
                    $opt["answerUploadId"] = $res2["answerUploadId"];
                }

                $options[] = $opt;
            }


            $ques = [];
            $ques["options"] = $options;
            $ques["question"] = $question;
            $ret[] = $ques;
        }


        return $ret;
    }

    /**
     * @param $userResultId
     *
     * @return bool
     */
    public function remove_quiz_stat($userResultId): bool
    {
        try {
            $this->beginTransaction();
            $query = "select `quizId`,`user` from `tiki_user_quizzes` where `userResultId`=?";
            $bindvars = [(int) $userResultId];

            $result = $this->query($query, $bindvars);
            $res = $result->fetchRow();
            $user = $res["user"];
            $quizId = $res["quizId"];

            $query = "delete from `tiki_user_taken_quizzes` where `user`=? and `quizId`=?";
            $this->query($query, [$user, (int) $quizId]);

            $query = "delete from `tiki_user_quizzes` where `userResultId`=?";
            $this->query($query, $bindvars);
            $query = "delete from `tiki_user_answers` where `userResultId`=?";
            $this->query($query, $bindvars);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * @param $quizId
     *
     * @return bool
     */
    public function clear_quiz_stats($quizId): bool
    {
        try {
            $this->beginTransaction();

            $bindvars = [(int) $quizId];

            $query = "delete from `tiki_user_taken_quizzes` where `quizId`=?";
            $this->query($query, $bindvars);

            $query = "delete from `tiki_quiz_stats_sum` where `quizId`=?";
            $this->query($query, $bindvars);

            $query = "delete from `tiki_quiz_stats` where `quizId`=?";
            $this->query($query, $bindvars);

            $query = "delete from `tiki_user_quizzes` where `quizId`=?";
            $this->query($query, $bindvars);

            $query = "delete from `tiki_user_answers` where `quizId`=?";
            $this->query($query, $bindvars);

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * @param $quizId
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_quiz_stats($quizId, $offset, $maxRecords, $sort_mode)
    {
        $this->compute_quiz_stats();

        $query = "select `passingperct` from `tiki_quizzes` where `quizId` = ?";
        $passingperct = $this->getOne($query, [(int) $quizId]);

        $mid = " where `quizId`=?";
        $bindvars = [(int) $quizId];

        $query = "select * from `tiki_user_quizzes` $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_user_quizzes` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["avgavg"] = ($res["maxPoints"] != 0) ? $res["points"] / $res["maxPoints"] * 100 : 0.0;

            if (isset($passingperct) && $passingperct > 0) {
                $res['ispassing'] = $res["avgavg"] >= $passingperct;
            }

            $hasDet = $this->getOne(
                "select count(*) from `tiki_user_answers` where `userResultId`=?",
                [(int) $res["userResultId"]]
            );
            if ($hasDet) {
                $res["hasDetails"] = 'y';
            } else {
                $res["hasDetails"] = 'n';
            }

            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_quiz_sum_stats($offset, $maxRecords, $sort_mode, $find): array
    {
        $this->compute_quiz_stats();

        $stats = $this->table('tiki_quiz_stats_sum');
        $conditions = [];

        if ($find) {
            $conditions['quizName'] = $stats->like("%$find%");
        }

        return [
            'data' => $stats->fetchAll($stats->all(), $conditions, $maxRecords, $offset, $stats->expr($this->convertSortMode($sort_mode))),
            'count' => $stats->fetchCount($conditions),
        ];
    }



    // Takes a given uploaded answer and inserts it into the DB. - burley

    /**
     * @param $userResultId
     * @param $questionId
     * @param $filename
     * @param $filetype
     * @param $filesize
     * @param $tmp_name
     *
     * @return bool
     */
    public function register_user_quiz_answer_upload($userResultId, $questionId, $filename, $filetype, $filesize, $tmp_name): bool
    {

        $data = fread(fopen($tmp_name, "r"), filesize($tmp_name));

        $query = "insert into `tiki_user_answers_uploads`"
                            . "(`userResultId`,`questionId`,`filename`,`filetype`,`filesize`,`filecontent`)"
                            . " values(?,?,?,?,?,?)";
        $result = $this->query($query, [(int) $userResultId, (int) $questionId, $filename, $filetype, $filesize, $data]);
        return $result && $result->numRows() > 0;
    }


    /**
     * @param $userResultId
     * @param $quizId
     * @param $questionId
     * @param $optionId
     *
     * @return bool
     */
    public function register_user_quiz_answer($userResultId, $quizId, $questionId, $optionId): bool
    {
        $query = "insert into `tiki_user_answers`(`userResultId`,`quizId`,`questionId`,`optionId`) values(?,?,?,?)";
        $result = $this->query($query, [(int) $userResultId, (int) $quizId, (int) $questionId, (int) $optionId]);
        return $result && $result->numRows() > 0;
    }

    /**
     * @param $quizId
     * @param $user
     * @param $timeTaken
     * @param $points
     * @param $maxPoints
     * @param $resultId
     * @return int
     */
    public function register_quiz_stats($quizId, $user, $timeTaken, $points, $maxPoints, $resultId): int
    {
        // Fix a bug if no result is indicated.
        if (! $resultId) {
            $resultId = 0;
        }

        $query = "insert into `tiki_user_quizzes`(`user`,`quizId`,`timestamp`,`timeTaken`,`points`,`maxPoints`,`resultId`) values(?,?,?,?,?,?,?)";
        $result = $this->query(
            $query,
            [
                $user,
                $quizId,
                $this->now,
                $timeTaken,
                $points,
                $maxPoints,
                $resultId
            ]
        );
        if ($result && $result->numRows() > 0) {
            return $this->lastInsertId();
        }
        return 0;
    }

    /**
     * @param $quizId
     * @param $questionId
     * @param $optionId
     * @return bool
     */
    public function register_quiz_answer($quizId, $questionId, $optionId)
    {
        $query = "INSERT INTO `tiki_quiz_stats` (`quizId`, `questionId`, `optionId`, `votes`) VALUES (?, ?, ?, 1) 
              ON DUPLICATE KEY UPDATE `votes` = `votes` + 1";

        $bindvars = [(int) $quizId, (int) $questionId, (int) $optionId];

        $result = $this->query($query, $bindvars);

        return $result && $result->numRows() > 0;
    }

    /**
     * @param int $quizId
     * @param int $points
     *
     * @return array|null
     */
    public function calculate_quiz_result(int $quizId, int $points): ?array
    {
        $query = "select * from `tiki_quiz_results` where `fromPoints`<=? and `toPoints`>=? and `quizId`=?";

        $result = $this->query($query, [$points, $points, $quizId]);

        if ($result && $result->numRows() > 0) {
            return $result->fetchRow();
        }

        return null;
    }

    /**
     * @param $user
     * @param $quizId
     * @return mixed
     */
    public function user_has_taken_quiz($user, $quizId)
    {
        $count = $this->getOne("select count(*) from `tiki_user_taken_quizzes` where `user`=? and `quizId`=?", [$user, (int) $quizId]);

        return $count;
    }

    /**
     * @param $user
     * @param $quizId
     */
    public function user_takes_quiz($user, $quizId)
    {
        $bindvars = [$user,(int) $quizId];
        $query = "INSERT INTO `tiki_user_taken_quizzes` (`user`, `quizId`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `user` = VALUES(`user`)";
        $result = $this->query($query, $bindvars);

        return $result && $result->numRows() > 0;
    }

    /**
     * @param $resultId
     * @param $quizId
     * @param $fromPoints
     * @param $toPoints
     * @param $answer
     * @return mixed
     */
    public function replace_quiz_result($resultId, $quizId, $fromPoints, $toPoints, $answer)
    {
        if ($resultId) {
            // update an existing quiz
            $query = "update `tiki_quiz_results` set `fromPoints` = ?, `toPoints` = ?, `quizId` = ?, `answer` = ?  where `resultId` = ?";
            $bindvars = [(int) $fromPoints,(int) $toPoints, (int) $quizId, $answer, (int) $resultId];
            $result = $this->query($query, $bindvars);
            if (! $result) {
                return 0;
            }
        } else {
            // insert a new quiz
            $query = "insert into `tiki_quiz_results`(`quizId`,`fromPoints`,`toPoints`,`answer`) values(?,?,?,?)";
            $bindvars = [(int) $quizId, (int) $fromPoints, (int) $toPoints, $answer];
            $result = $this->query($query, $bindvars);

            if ($result && $result->numRows() > 0) {
                $quizId = $this->lastInsertId();
            } else {
                return 0;
            }
        }

        return $quizId;
    }

    /**
     * @param $resultId
     * @return array|null
     */
    public function get_quiz_result($resultId)
    {
        $query = "select * from `tiki_quiz_results` where `resultId`=?";

        $result = $this->query($query, [(int) $resultId]);

        if ($result && $result->numRows() > 0) {
            return $result->fetchRow();
        } else {
            return null;
        }
    }

    /**
     * @param $resultId
     * @return bool
     */
    public function remove_quiz_result($resultId)
    {
        $query = "delete from `tiki_quiz_results` where `resultId`=?";

        $result = $this->query($query, [$resultId]);
        return ($result && $result->numRows() > 0);
    }

    /**
     * @param $quizId
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_quiz_results($quizId, $offset, $maxRecords, $sort_mode, $find)
    {

        if ($find) {
            $findesc = '%' . $find . '%';

            $mid = " where `quizId`=? and `answer` like ? ";
            $bindvars = [(int) $quizId, $findesc];
        } else {
            $mid = " where `quizId`=? ";
            $bindvars = [(int) $quizId];
        }

        $query = "select * from `tiki_quiz_results` $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_quiz_results` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    // called by tiki-edit_quiz.php
    /**
     * @param $quizId
     * @param $name
     * @param $description
     * @param $canRepeat
     * @param $storeResults
     * @param $immediateFeedback
     * @param $showAnswers
     * @param $shuffleQuestions
     * @param $shuffleAnswers
     * @param $questionsPerPage
     * @param $timeLimited
     * @param $timeLimit
     * @param $publishDate
     * @param $expireDate
     * @param $passingperct
     * @return mixed
     */
    public function replace_quiz($quizId, $name, $description, $canRepeat, $storeResults, $immediateFeedback, $showAnswers, $shuffleQuestions, $shuffleAnswers, $questionsPerPage, $timeLimited, $timeLimit, $publishDate, $expireDate, $passingperct)
    {
        if ($quizId) {
            // update an existing quiz
            $query = "update `tiki_quizzes` set `name` = ?, `description` = ?, `canRepeat` = ?, `storeResults` = ?,";
            $query .= "`immediateFeedback` = ?, `showAnswers` = ?,    `shuffleQuestions` = ?, `shuffleAnswers` = ?, ";
            $query .= "`publishDate` = ?, `expireDate` = ?, ";
            $query .= "`questionsPerPage` = ?, `timeLimited` = ?, `timeLimit` =?, `passingperct` = ?  where `quizId` = ?";
            $bindvars = [$name,
                                $description,
                                $canRepeat,
                                $storeResults,
                                $immediateFeedback,
                                $showAnswers,
                                $shuffleQuestions,
                                $shuffleAnswers,
                                $publishDate,
                                $expireDate,
                                (int) $questionsPerPage,
                                $timeLimited,
                                (int) $timeLimit,
                                (int) $passingperct,
                                (int) $quizId
            ];

            $result = $this->query($query, $bindvars);
            if (! $result) {
                return 0;
            }
        } else {
            // insert a new quiz

            $query  = "insert into `tiki_quizzes`(`name`,`description`,`canRepeat`,`storeResults`,";
            $query .= "`immediateFeedback`, `showAnswers`,    `shuffleQuestions`, `shuffleAnswers`,";
            $query .= "`publishDate`, `expireDate`,";
            $query .= "`questionsPerPage`,`timeLimited`,`timeLimit`,`created`,`taken`,`passingperct`)";
            $query .= " values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $bindvars = [
                                    $name,
                                    $description,
                                    $canRepeat,
                                    $storeResults,
                                    $immediateFeedback,
                                    $showAnswers,
                                    $shuffleQuestions,
                                    $shuffleAnswers,
                                    $publishDate,
                                    $expireDate,
                                    (int) $questionsPerPage,
                                    $timeLimited,
                                    (int) $timeLimit,
                                    $this->now,
                                    0,
                                    (int) $passingperct
            ];
            $result = $this->query($query, $bindvars);
            if ($result && $result->numRows() > 0) {
                $quizId = $this->lastInsertId();
            } else {
                return 0;
            }
        }

        return $quizId;
    }

    /**
     * @param $questionId
     * @param $question
     * @param $type
     * @param $quizId
     * @param $position
     * @return mixed
     */
    public function replace_quiz_question($questionId, $question, $type, $quizId, $position)
    {
        if ($questionId) {
            // update an existing quiz
            $query = "update `tiki_quiz_questions` set `type`=?, `position` = ?, `question` = ?  where `questionId` = ? and `quizId`=?";
            $bindvars = [$type,(int) $position, $question, (int) $questionId, (int) $quizId];
            $result = $this->query($query, $bindvars);
            if (! $result) {
                return 0;
            }
        } else {
            // insert a new quiz
            $query = "insert into `tiki_quiz_questions`(`question`,`type`,`quizId`,`position`) values(?,?,?,?)";
            $bindvars = [$question, $type, (int) $quizId, (int) $position];
            $result = $this->query($query, $bindvars);
            if ($result && $result->numRows() > 0) {
                $questionId = $this->lastInsertId();
            } else {
                return 0;
            }
        }
        return $questionId;
    }

    /**
     * @param $optionId
     * @param $option
     * @param $points
     * @param $questionId
     * @return mixed
     */
    public function replace_question_option($optionId, $option, $points, $questionId)
    {
        // validating the points value
        if ((! is_numeric($points)) || ($points == "")) {
            $points = 0;
        }
        if ($optionId) {
            $query = "update `tiki_quiz_question_options` set `points`=?, `optionText` = ?  where `optionId` = ? and `questionId`=?";
            $bindvars = [(int) $points, $option,(int) $optionId, (int) $questionId];
            $result = $this->query($query, $bindvars);
            if (! $result) {
                return 0;
            }
        } else {
            $query = "insert into `tiki_quiz_question_options`(`optionText`,`points`,`questionId`) values(?,?,?)";
            $result = $this->query($query, [$option, (int) $points, (int) $questionId]);
            if ($result && $result->numRows() > 0) {
                $optionId = $this->lastInsertId();
            } else {
                return 0;
            }
        }

        return $optionId;
    }

    /**
     * @param $questionId
     * @return bool
     */
    public function get_quiz_question($questionId)
    {
        $query = "select * from `tiki_quiz_questions` where `questionId`=?";
        $result = $this->query($query, [(int) $questionId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();
        return $res;
    }

    /**
     * @param $optionId
     * @return bool
     */
    public function get_quiz_question_option($optionId)
    {
        $query = "select * from `tiki_quiz_question_options` where `optionId`=?";
        $result = $this->query($query, [(int) $optionId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();
        return $res;
    }

    /**
     * @param $quizId
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_quiz_questions($quizId, $offset, $maxRecords, $sort_mode, $find)
    {
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where `quizId`=? and `question` like ? ";
            $bindvars = [(int) $quizId, $findesc];
        } else {
            $mid = " where `quizId`=? ";
            $bindvars = [(int) $quizId];
        }

        $query = "select * from `tiki_quiz_questions` $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_quiz_questions` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["options"] = $this->getOne("select count(*) from `tiki_quiz_question_options` where `questionId`=?", [(int) $res["questionId"]]);
            $res["maxPoints"] = $this->getOne("select max(`points`) from `tiki_quiz_question_options` where `questionId`=?", [(int) $res["questionId"]]);
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param $find
     * @return array
     */
    public function list_all_questions($offset, $maxRecords, $sort_mode, $find)
    {
        if ($find) {
            $findesc = '%' . $find . '%';

            $mid = " where `question` like ? ";
            $bindvars = [$findesc];
        } else {
            $mid = " ";
            $bindvars = [];
        }

        $query = "select * from `tiki_quiz_questions` $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_quiz_questions` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["options"]
                = $this->getOne("select count(*) from `tiki_quiz_question_options` where `questionId`=?", [(int) $res["questionId"]]);
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    /**
     * @param $questionId
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_quiz_question_options($questionId, $offset, $maxRecords, $sort_mode, $find)
    {
        if ($find) {
            $findesc = '%' . $find . '%';

            $mid = " where `questionId`=? and `optionText` like ? ";
            $bindvars = [(int) $questionId,$findesc];
        } else {
            $mid = " where `questionId`=? ";
            $bindvars = [(int) $questionId];
        }

        $query = "select * from `tiki_quiz_question_options` $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_quiz_question_options` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    /**
     * @param $questionId
     * @return bool
     */
    public function remove_quiz_question($questionId)
    {
        try {
            $this->beginTransaction();
            $query = "delete from `tiki_quiz_questions` where `questionId`=?";

            $this->query($query, [(int) $questionId]);
            // Remove all the options for the question
            $query = "delete from `tiki_quiz_question_options` where `questionId`=?";
            $this->query($query, [(int) $questionId]);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * @param $optionId
     * @return bool
     */
    public function remove_quiz_question_option($optionId)
    {
        $query = "delete from `tiki_quiz_question_options` where `optionId`=?";

        $result = $this->query($query, [(int) $optionId]);
        return ($result && $result->numRows() > 0);
    }

    /**
     * @param $quizId
     * @return bool
     */
    public function remove_quiz($quizId)
    {

        try {
            $this->beginTransaction();
            $query = "delete from `tiki_quizzes` where `quizId`=?";

            $this->query($query, [(int) $quizId]);
            $query = "select * from `tiki_quiz_questions` where `quizId`=?";
            $result = $this->query($query, [(int) $quizId]);

            // Remove all the options for each question
            while ($res = $result->fetchRow()) {
                $questionId = $res["questionId"];

                $query2 = "delete from `tiki_quiz_question_options` where `questionId`=?";
                $this->query($query2, [(int) $questionId]);
            }

            // Remove all the questions
            $query = "delete from `tiki_quiz_questions` where `quizId`=?";
            $this->query($query, [(int) $quizId]);
            $query = "delete from `tiki_quiz_results` where `quizId`=?";
            $this->query($query, [(int) $quizId]);
            $query = "delete from `tiki_quiz_stats` where `quizId`=?";
            $this->query($query, [(int) $quizId]);
            $query = "delete from `tiki_user_quizzes` where `quizId`=?";
            $this->query($query, [(int) $quizId]);
            $query = "delete from `tiki_user_answers` where `quizId`=?";
            $this->query($query, [(int) $quizId]);
            $this->remove_object('quiz', $quizId);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * @param $id
     * @return Tiki\Lib\Quizzes\Quiz
     */
    public function quiz_fetch($id)
    {
        if ($id == 0) {
            $quiz = new Quiz();
        } else {
            echo __FILE__ . " line: " . __LINE__ . " : Need to fetch a quiz from the database" . "<br />";
        }
        return $quiz;
    }

    // $quiz is a quiz object
    /**
     * @param $quiz
     * @return mixed
     */
    public function quiz_store($quiz)
    {
        echo __FILE__ . " line: " . __LINE__ . ": in quizlib->quiz_store<br />";
        echo "Store stuff in the dbFields array.<br />";
        foreach ($quiz->dbFields as $f) {
        }
        die;
        if ($quizId) {
            // update an existing quiz
            $query = "update `tiki_quizzes` set `name` = ?, `description` = ?, `canRepeat` = ?, `storeResults` = ?,";
            $query .= "`immediateFeedback` = ?, `showAnswers` = ?,    `shuffleQuestions` = ?, `shuffleAnswers` = ?, ";
            $query .= "`publishDate` = ?, `expireDate` = ?, ";
            $query .= "`questionsPerPage` = ?, `timeLimited` = ?, `timeLimit` =?  where `quizId` = ?";
            $bindvars = [
                            $name,
                            $description,
                            $canRepeat,
                            $storeResults,
                            $immediateFeedback,
                            $showAnswers,
                            $shuffleQuestions,
                            $shuffleAnswers,
                            $publishDate,
                            $expireDate,
                            (int) $questionsPerPage,
                            $timeLimited,
                            (int) $timeLimit,
                            (int) $quizId
            ];

            $this->query($query, $bindvars);
        } else {
            // insert a new quiz

            $query = "insert into `tiki_quizzes`(`name`,`description`,`canRepeat`,`storeResults`,";
            $query .= "`immediateFeedback`, `showAnswers`,    `shuffleQuestions`, `shuffleAnswers`,";
            $query .= "`publishDate`, `expireDate`,";
            $query .= "`questionsPerPage`,`timeLimited`,`timeLimit`,`created`,`taken`) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $bindvars = [$name,
                                $description,
                                $canRepeat,
                                $storeResults,
                                $immediateFeedback,
                                $showAnswers,
                                $shuffleQuestions,
                                $shuffleAnswers,
                                $publishDate,
                                $expireDate,
                                (int) $questionsPerPage,
                                $timeLimited,
                                (int) $timeLimit,
                                $this->now,
                                0
            ];
            $this->query($query, $bindvars);
            $queryid = "select max(`quizId`) from `tiki_quizzes` where `created`=?";
            $quizId = $this->getOne($queryid, [$this->now]);
        }

        return $quizId;
    }

    /**
     * @return string
     */
    public function get_upload_dir()
    {
        return "quiz_uploads/";
    }
}
