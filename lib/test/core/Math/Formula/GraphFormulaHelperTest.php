<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests\Core\Math\Formula;

use TikiTestCase;
use Tiki\Math\Formula\GraphFormulaException;
use Tiki\Math\Formula\GraphFormulaHelper;

class GraphFormulaHelperTest extends TikiTestCase
{
    public function testSanitizeStripsLegacyCharacters()
    {
        $this->assertSame('sin(x)', GraphFormulaHelper::sanitize('sin(x)`\'"&[]$' . '{}'));
    }

    public function testLooksLegacyDetectsPhpStyleCall()
    {
        $this->assertTrue(GraphFormulaHelper::looksLegacy('sin(x)'));
        $this->assertFalse(GraphFormulaHelper::looksLegacy('(sin x)'));
    }

    public function testLooksLegacyDetectsInfixExpression()
    {
        $this->assertTrue(GraphFormulaHelper::looksLegacy('x+1'));
        $this->assertFalse(GraphFormulaHelper::looksLegacy('(add x 1)'));
    }

    public function testLooksLegacyDetectsBareVariable()
    {
        $this->assertTrue(GraphFormulaHelper::looksLegacy('x'));
        $this->assertTrue(GraphFormulaHelper::looksLegacy('pi'));
    }

    public function testLooksLegacyDetectsBareNumber()
    {
        $this->assertTrue(GraphFormulaHelper::looksLegacy('42'));
        $this->assertTrue(GraphFormulaHelper::looksLegacy('-1.5'));
    }

    public function testInvalidMessageIncludesMigrationHintForLegacySyntax()
    {
        $message = GraphFormulaHelper::invalidMessage('sin(x)', 'Expecting "("');
        $this->assertStringContainsString('(sin x)', $message);
        $this->assertStringContainsString('sin(x)', $message);
        $this->assertStringContainsString(GraphFormulaHelper::SYNTAX_DOC_URL, $message);
    }

    public function testInvalidMessageWithoutLegacySyntax()
    {
        $message = GraphFormulaHelper::invalidMessage('(bad x)', 'Unknown operation');
        $this->assertStringNotContainsString(GraphFormulaHelper::SYNTAX_DOC_URL, $message);
    }

    public function testParseElementRejectsLegacySyntax()
    {
        $this->expectException(GraphFormulaException::class);
        GraphFormulaHelper::parseElement('sin(x)');
    }

    public function testParseElementAcceptsSExpressionSyntax()
    {
        $element = GraphFormulaHelper::parseElement('(sin x)');
        $this->assertInstanceOf(\Math_Formula_Element::class, $element);
    }

    public function testLegacySyntaxExceptionIncludesMigrationHint()
    {
        try {
            GraphFormulaHelper::parseElement('sin(x)');
            $this->fail('Expected GraphFormulaException');
        } catch (GraphFormulaException $e) {
            $message = $e->getUserMessage();
            $this->assertStringContainsString(GraphFormulaHelper::SYNTAX_DOC_URL, $message);
            $this->assertStringContainsString('(sin x)', $message);
        }
    }
}
