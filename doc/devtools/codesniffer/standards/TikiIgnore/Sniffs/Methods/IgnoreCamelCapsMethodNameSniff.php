<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Standards\TikiIgnore\Sniffs\Methods;

require_once __DIR__ . DIRECTORY_SEPARATOR . '../../Helpers/SafeIgnoreList.php';

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff;
use Tiki\Standards\TikiIgnore\Helpers\SafeIgnoreList;

class IgnoreCamelCapsMethodNameSniff extends CamelCapsMethodNameSniff
{
    protected $ignoreSniff = 'PSR1.Methods.CamelCapsMethodName.NotCamelCaps';

    protected $ignoreList = [];

    protected $relativePathOffset = null;

    public function __construct()
    {
        if (file_exists(dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'ignore_list.json')) {
            $ignoreList = json_decode(file_get_contents(dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'ignore_list.json'), true);
            if (! empty($ignoreList[$this->ignoreSniff])) {
                $this->ignoreList = $ignoreList[$this->ignoreSniff];
            }
        }

        parent::__construct();
    }

    /**
     * Processes the tokens within the scope.  - Clone of the original method, will insert exceptions on the fly
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being processed.
     * @param int                         $stackPtr  The position where this token was
     *                                               found.
     * @param int                         $currScope The position of the current scope.
     *
     * @return void
     */
    protected function processTokenWithinScope(File $phpcsFile, $stackPtr, $currScope)
    {
        if (empty($this->ignoreList)) {
            return;
        }

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


        if ($tokens[$currScope]['code'] === T_ANON_CLASS) {
            // Skipping anonymous class in {$phpcsFile->pat[$currScope]['line']}
            return;
        }

        $className = $phpcsFile->getDeclarationName($currScope);
        if (! isset($className)) {
            // ignore anonymous class.
            return;
        }

        if ($this->relativePathOffset === null) {
            $pos = 0;
            do {
                $searchPath = substr($phpcsFile->path, $pos + 1);
                if (! empty($this->ignoreList[$searchPath])) {
                    $this->relativePathOffset = $pos + 1;
                    break;
                }
            } while (($pos = strpos($phpcsFile->path, '/', $pos + 1)) !== false);
        }
        if ($this->relativePathOffset === null) {
            return;
        }

        if (empty($this->ignoreList[substr($phpcsFile->path, $this->relativePathOffset)][$className . '::' . $methodName])) {
            return;
        }

        $line = $tokens[$stackPtr]['line'];
        if (empty($phpcsFile->tokenizer->ignoredLines[$line])) {
            $phpcsFile->tokenizer->ignoredLines[$line] = SafeIgnoreList::forLine();
        }
        SafeIgnoreList::markIgnored($phpcsFile->tokenizer->ignoredLines[$line], $this->ignoreSniff,);
    }
}
