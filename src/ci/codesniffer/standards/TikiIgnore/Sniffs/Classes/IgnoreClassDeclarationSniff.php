<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Standards\TikiIgnore\Sniffs\Classes;

require_once __DIR__ . DIRECTORY_SEPARATOR . '../../Helpers/IgnoreListTrait.php';

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Tiki\Standards\TikiIgnore\Helpers\IgnoreListTrait;

class IgnoreClassDeclarationSniff implements Sniff
{
    use IgnoreListTrait;

    protected const SNIFF_MULTIPLE_CLASSES = 'PSR1.Classes.ClassDeclaration.MultipleClasses';
    protected const SNIFF_MISSING_NAMESPACE = 'PSR1.Classes.ClassDeclaration.MissingNamespace';

    public function __construct()
    {
        $this->loadIgnoreList([self::SNIFF_MULTIPLE_CLASSES, self::SNIFF_MISSING_NAMESPACE]);
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
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param integer                     $stackPtr  The position of the current token in
     *                                               the token stack.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]['scope_closer']) === false) {
            return;
        }

        $nextClass = $phpcsFile->findNext([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], ($tokens[$stackPtr]['scope_closer'] + 1));
        if ($nextClass !== false) {
            $classType = $tokens[$nextClass]['content'];
            $className = $tokens[$phpcsFile->findNext(T_STRING, $nextClass)]['content'];
            $key = $classType . ':' . $className;

            if ($this->inIgnoreList(self::SNIFF_MULTIPLE_CLASSES, $phpcsFile->path, $key)) {
                $this->ignoreToken(self::SNIFF_MULTIPLE_CLASSES, $phpcsFile, $tokens, $nextClass);
            }
        }

        $namespace = $phpcsFile->findNext([T_NAMESPACE, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], 0);
        if ($tokens[$namespace]['code'] !== T_NAMESPACE) {
            // Ignore this one - MissingNamespace
            $classType = $tokens[$stackPtr]['content'];
            $className = $tokens[$phpcsFile->findNext(T_STRING, $stackPtr)]['content'];
            $key = $classType . ':' . $className;

            if ($this->inIgnoreList(self::SNIFF_MISSING_NAMESPACE, $phpcsFile->path, $key)) {
                $this->ignoreToken(self::SNIFF_MISSING_NAMESPACE, $phpcsFile, $tokens, $stackPtr);
            }
        }
    }
}
