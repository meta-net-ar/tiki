<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace SmartyTiki\Utils;

/**
 * Class StyleType
 *
 * functions for converting numbers to different formats defined by css List-Style-Type
 *
 *
 */
class StyleType
{
    /**
     * Takes a positive integer and returns the equivalent alpha value.
     *
     * eg. The below returns a lower case alphabetic value for 90.
     * toAlpha(90,range('a','z')
     *
     * eg. The below returns a lower case greek letters for 128.
     * toAlpha(128,array('α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'θ', 'ι', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'π', 'ρ', 'σ', 'τ', 'υ', 'φ', 'χ', 'ψ', 'ω'));
     *
     * @param $number int The number to be turned into characters
     *
     * @param $alphabet array set of letters
     *
     * @return string alpha representation of $number
     */
    public function toAlpha($number, $alphabet)
    {

        $count = count($alphabet);
        if ($number <= $count) {
            return $alphabet[$number - 1];
        }
        $alpha = '';
        while ($number > 0) {
            $modulo = ($number - 1) % $count;
            $alpha  = $alphabet[$modulo] . $alpha;
            $number = floor((($number - $modulo) / $count));
        }
        return $alpha;
    }


    /**
     *
     * Turns a positive integer into a uppercase roman numeral.
     * @param $number int The value to be converted into a roman numeral
     * @param $table array An array of roman numerals, (zero normally specified)
     *
     * @return string|int   Roman numeral of $number
     */
    public function numerals($number, $table = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1])
    {
        $return = '';
        while ($number > 0) {
            foreach ($table as $rom => $value) {
                if ($number >= $value) {
                    $number -= $value;
                    $return .= $rom;
                    break;
                }
            }
        }
        return $return;
    }
}
