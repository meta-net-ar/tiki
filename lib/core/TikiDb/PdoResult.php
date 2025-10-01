<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class Tiki\TikiDb\PdoResult
 *
 * Returns result set along with affected rows
 */
namespace Tiki\TikiDb;

use PDO;
use PDOStatement;

class PdoResult
{
    /** @var array */
    public $result = null;
    /** @var int */
    public $numrows = 0;

    private ?PDOStatement $statement = null;

    /**
     * Tiki\TikiDb\PdoResult constructor.
     * @param $result
     * @param $rowCount
     */
    public function __construct($result, $rowCount = 0)
    {
        if (is_array($result)) {
            $this->result = &$result;
            $this->numrows = is_numeric($rowCount) ? $rowCount : count($this->result);
        } elseif ($result) {
            $this->statement = $result;
            $this->numrows = $result->rowCount();
        }
    }

    /** @return array */
    public function fetchRow()
    {
        if (is_array($this->result)) {
            return array_shift($this->result);
        } elseif ($this->statement) {
            return $this->statement->fetch(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    /** @return int */
    public function numRows()
    {
        return (int) $this->numrows;
    }
}
