<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Webservices;

/**
 * Write your SOAP webservices as methods of this class, it will be automagically
 * added to the WSDL file.
 *
 * Warning : while developing your web services, you should consider to set the
 * soap.wsdl_cache_enabled parameter to 0 in your php.ini.
 */
class Base
{
    /**
     * Prints some test data.
     *
     * @param string $param2
     * @param string $param1
     * @param string $param3
     * @return string
     */
    public function test($param2, $param1, $param3)
    {
        return 'test1 ' . $param1 . ' test2 ' . $param2 . ' test3 ' . $param3;
    }

    /**
     * Displays the ComplexType data.
     *
     * @param ComplexType $complex_param
     * @return string
     */
    public function testComplex(ComplexType $complex_param)
    {
        return $complex_param->param1 . ' =====> ' . $complex_param->param2;
    }
}
