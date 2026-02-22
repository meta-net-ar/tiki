<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Standards\TikiIgnore\Sniffs\Classes;

require_once __DIR__ . DIRECTORY_SEPARATOR . '../../Helpers/IgnoreListTrait.php';

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Common;
use Tiki\Standards\TikiIgnore\Helpers\IgnoreListTrait;

class IgnoreValidClassNameSniff implements Sniff
{
    use IgnoreListTrait;

    protected const SNIFF_NOT_PASCAL_CASE = 'Squiz.Classes.ValidClassName.NotPascalCase';

    public function __construct()
    {
        $this->loadIgnoreList([self::SNIFF_NOT_PASCAL_CASE]);
    }

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
            T_ENUM,
        ];
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being processed.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            $error = 'Possible parse error: %s missing opening or closing brace';
            $data  = [$tokens[$stackPtr]['content']];
            $phpcsFile->addWarning($error, $stackPtr, 'MissingBrace', $data);
            return;
        }

        // Determine the name of the class or interface. Note that we cannot
        // simply look for the first T_STRING because a class name
        // starting with the number will be multiple tokens.
        $opener    = $tokens[$stackPtr]['scope_opener'];
        $nameStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), $opener, true);
        $nameEnd   = $phpcsFile->findNext([T_WHITESPACE, T_COLON], $nameStart, $opener);
        if ($nameEnd === false) {
            $name = $tokens[$nameStart]['content'];
        } else {
            $name = trim($phpcsFile->getTokensAsString($nameStart, ($nameEnd - $nameStart)));
        }

        // Check for PascalCase format.
        $valid = Common::isCamelCaps($name, true, true, false);
        if ($valid === false) {
            if ($this->inIgnoreList(self::SNIFF_NOT_PASCAL_CASE, $phpcsFile->path, $name)) {
                $this->ignoreToken(self::SNIFF_NOT_PASCAL_CASE, $phpcsFile, $tokens, $stackPtr);
            }
        }
    }
}
