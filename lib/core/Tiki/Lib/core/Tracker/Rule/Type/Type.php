<?php

namespace Tiki\Lib\core\Tracker\Rule\Type;

use Tiki\Lib\core\Tracker\Rule\Column;

abstract class Type extends Column
{
    /** @var array of operators or actions */
    protected $operators = [];

    /**
     * Type constructor.
     *
     * @param string $argType
     * @param array  $operators
     */
    public function __construct($argType)
    {
        parent::__construct('', $argType, []);
        $this->operators = [];
    }

    public function get(): array
    {
        $operator_ids = array_map(
            function ($operator) {
                return $operator->getId();
            },
            $this->operators
        );

        return [
            'type_id' => $this->getId(),
            'operator_ids' => $operator_ids,
        ];
    }

    public function addOperator(Column $type)
    {
        $this->operators[] = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::class;
    }
}
