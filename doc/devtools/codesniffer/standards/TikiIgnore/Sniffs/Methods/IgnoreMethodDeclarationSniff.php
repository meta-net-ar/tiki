<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Standards\TikiIgnore\Sniffs\Methods;

require_once __DIR__ . DIRECTORY_SEPARATOR . '../../Helpers/IgnoreListTrait.php';

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractScopeSniff;
use PHP_CodeSniffer\Util\Tokens;
use Tiki\Standards\TikiIgnore\Helpers\IgnoreListTrait;

class IgnoreMethodDeclarationSniff extends AbstractScopeSniff
{
    use IgnoreListTrait;

    protected const SNIFF_UNDERSCORE = 'PSR2.Methods.MethodDeclaration.Underscore';

    /**
     * Constructs a Squiz_Sniffs_Scope_MethodScopeSniff.
     */
    public function __construct()
    {
        $this->loadIgnoreList([self::SNIFF_UNDERSCORE]);
        parent::__construct(Tokens::$ooScopeTokens, [T_FUNCTION]);
    }


    /**
     * Processes the function tokens within the class.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position where the token was found.
     * @param int                         $currScope The current scope opener token.
     *
     * @return void
     */
    protected function processTokenWithinScope(File $phpcsFile, $stackPtr, $currScope)
    {
        $tokens = $phpcsFile->getTokens();

        // Determine if this is a function which needs to be examined.
        $conditions = $tokens[$stackPtr]['conditions'];
        end($conditions);
        $deepestScope = key($conditions);
        if ($deepestScope !== $currScope) {
            return;
        }

        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if (empty($methodName)) {
            // Ignore closures.
            return;
        }

        if ($methodName[0] === '_' && isset($methodName[1]) === true && $methodName[1] !== '_') {
            if ($this->inIgnoreList(self::SNIFF_UNDERSCORE, $phpcsFile->path, $methodName)) {
                $this->ignoreToken(self::SNIFF_UNDERSCORE, $phpcsFile, $tokens, $stackPtr);
            }
        }
    }


    /**
     * Processes a token that is found within the scope that this test is
     * listening to.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position in the stack where this
     *                                               token was found.
     *
     * @return void
     */
    protected function processTokenOutsideScope(File $phpcsFile, $stackPtr)
    {
    }
}
