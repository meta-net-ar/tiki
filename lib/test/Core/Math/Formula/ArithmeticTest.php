<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests\Core\Math\Formula;

use Math_Formula_Runner;
use TikiTestCase;
use Tiki\Math\Formula\Function\Arithmetic;

class ArithmeticTest extends TikiTestCase
{
    /** @var Math_Formula_Runner */
    private $runner;

    protected function setUp(): void
    {
        $this->runner = new Math_Formula_Runner([
            '__graph_arithmetic__' => Arithmetic::resolver(),
            'Math_Formula_Function_' => null,
        ]);
    }

    public function testResolverHandlesWhitelistedBuiltin()
    {
        $resolver = Arithmetic::resolver();
        $this->assertInstanceOf(Arithmetic::class, $resolver('sin'));
        $this->assertInstanceOf(Arithmetic::class, $resolver('arithmetic'));
        $this->assertNull($resolver('not_a_graph_builtin'));
    }

    public function testSinAtZero()
    {
        $this->runner->setFormula('(sin x)');
        $this->runner->setVariables(['x' => 0]);
        $this->assertEqualsWithDelta(0, $this->runner->evaluate(), 1e-10);
    }

    public function testCosAtZero()
    {
        $this->runner->setFormula('(cos x)');
        $this->runner->setVariables(['x' => 0]);
        $this->assertEqualsWithDelta(1, $this->runner->evaluate(), 1e-10);
    }

    public function testExplicitArithmeticForm()
    {
        $this->runner->setFormula('(arithmetic sin x)');
        $this->runner->setVariables(['x' => 0]);
        $this->assertEqualsWithDelta(0, $this->runner->evaluate(), 1e-10);
    }

    public function testPiConstant()
    {
        $this->runner->setFormula('(pi)');
        $this->assertEqualsWithDelta(M_PI, $this->runner->evaluate(), 1e-10);
    }

    public function testCombinedWithAdd()
    {
        $this->runner->setFormula('(add (sin x) 1)');
        $this->runner->setVariables(['x' => 0]);
        $this->assertEqualsWithDelta(1, $this->runner->evaluate(), 1e-10);
    }
}
