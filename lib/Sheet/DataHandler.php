<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use BadMethodCallException;

/** DataHandler
 * Base data handler to link the sheet to the data
 * source. Before being sent as an handler, the object
 * must know the target location of the data if they
 * are required.
 */
class DataHandler
{
    public $maxrows = 300;
    public $maxcols = 26;
    public $output = "";
    public $cssName;

    /** name
     * Identifies the handler in a readable form.
     * @return The name of the handler.
     */
    public function name()
    {
        throw new BadMethodCallException(tr("Abstract method call. %0 not defined in %1", __FUNCTION__ . '()', get_class($this)));
    }
    /** supports
     * Function to indicate the features that are supported
     * by the handler.
     * @param The feature constant.
     * @return true if the feature is supported.
     * @static
     * @abstract
     */
    public function supports($feature)
    {
        throw new BadMethodCallException(tr("Abstract method call. %0 not defined in %1", __FUNCTION__ . '()', get_class($this)));
    }

    /** version
     * Indicates the handler's version.
     * @return The version number as a string.
     */
    public function version()
    {
        throw new BadMethodCallException(tr("Abstract method call. %0 not defined in %1", __FUNCTION__ . '()', get_class($this)));
    }
}
