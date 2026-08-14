<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_If extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $cond = $this->evaluateChild($element[0]);
        $then = $element[1];
        $else = $element[2] ?: 0;

        if ($cond) {
            return $this->evaluateChild($then);
        }

        $elseIfIndexes = $this->findElseIf($element);
        foreach ($elseIfIndexes as $elseIfIndex) {
            $args = [$element[$elseIfIndex + 1], $element[$elseIfIndex + 2]];
            $this->validateArgs($args);

            $cond = $this->evaluateChild($args[0]);
            if ($cond) {
                return $this->evaluateChild($args[1]);
            }
        }

        $elseIndex = $this->findElse($element);
        if ($elseIndex > 0) {
            $args = [$element[$elseIndex + 1]];
            $this->validateArgs($args);

            $then = $args[0];
            return $this->evaluateChild($then);
        }
        return $this->evaluateChild($else);
    }

    public function evaluateFull($element)
    {
        $evaluatedElement = [];
        foreach ($element as $elt) {
            $evaluatedElement[] = $this->evaluateChild($elt);
        }
        $cond = $evaluatedElement[0];
        $then = $evaluatedElement[1];
        $else = $evaluatedElement[2] ?: 0;

        if ($cond) {
            return $then;
        }

        $elseIfIndexes = $this->findElseIf($evaluatedElement);
        foreach ($elseIfIndexes as $elseIfIndex) {
            $args = [$evaluatedElement[$elseIfIndex + 1], $evaluatedElement[$elseIfIndex + 2]];
            $this->validateArgs($args);

            $cond = $args[0];
            $then = $args[1];
            if ($cond) {
                return $then;
            }
        }

        $elseIndex = $this->findElse($evaluatedElement);
        if ($elseIndex > 0) {
            $args = [$evaluatedElement[$elseIfIndex + 1]];
            $this->validateArgs($args);

            $then = $args[0];
            return $then;
        }
        return $else;
    }

    private function findElseIf($element): array
    {
        $elseIfIndexes = [];
        foreach ($element as $index => $elt) {
            if ($elt == 'elseif') {
                $elseIfIndexes[] = $index;
            }
        }
        return $elseIfIndexes;
    }

    private function findElse($element): int
    {
        foreach ($element as $index => $elt) {
            if ($elt == 'else') {
                return $index;
            }
        }
        return 0;
    }

    /**
     * Validate arguments of a elseif/else clause
     * @param array $args The clause arguments
     * @return void
     * @throws Math_Formula_Exception
     */
    private function validateArgs(array $args): void
    {
        if (empty($args)) {
            $this->error(tra('Missing arguments for \'else\' or \'elseif\' clause.'));
        }
        if (! isset($args[0])) {
            $this->error(tra('\'else\' clause needs 1 argument and \'elseif\' clause needs 2 arguments. No argument found.'));
        }
        $firstArg = $args[0];
        $validFirstArg = $firstArg != 'elseif' && $firstArg != 'else';
        if (! $validFirstArg) {
            $this->error(tr('Invalid argument for \'else\' or Invalid first argument for \'elseif\' found. %0 is a reserved keyword.', $firstArg));
        }
        $validSecondArg = true; //Assume the second arg is truthy if the clause is 'else'

        if ($validFirstArg && isset($args[1])) {
            $secondArg = $args[1];
            $validSecondArg = $args[1] != 'elseif' && $args[1] != 'else';//Validate the second arg as the clause is 'elseif'
            if (! $validSecondArg) {
                $this->error(tr('Invalid second argument for \'elseif\' found. %0 is a reserved keyword.', $secondArg));
            }
        }
    }
}
