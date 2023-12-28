<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * convert utf-8 to unicode
 */
class Utf8Unicode
{
    public function handle($utf8_text)
    {
        $output = [];

        for ($pos = 0, $pos_strlen_utf8_text = strlen($utf8_text); $pos < $pos_strlen_utf8_text; $pos++) {
            $chval = ord($utf8_text[$pos]);

            $bytes = 0;
            if (($chval >= 0x00) && ($chval <= 0x7F)) {
                $bytes = 1;
                $outputval = $chval;    // Since 7-bit ASCII is unaffected, the output equals the input
            } else {
                for ($i = 5; $i > 0; $i--) {
                    if (($chval >> $i) == ((pow(2, (8 - $i))) - 2)) {
                        $bytes = 7 - $i;
                        $outputval = $chval & ((2 ^ $i) - 1);
                    }
                }
            }

            if ($bytes !== 0) {
                if ($pos + $bytes - 1 < strlen($utf8_text)) {
                    while ($bytes > 1) {
                        $pos++;
                        $bytes--;

                        $outputval = $outputval * 0x40 + ((ord($utf8_text[$pos])) & 0x3F);
                    }
                    if ($outputval != 0) {
                        $output[] = $outputval;
                    }
                }
            }
        }

        $htmloutput = "";

        foreach ($output as $unistr) {
            if ($bytes < 3) {
                $htmloutput .= "&#" . str_pad($unistr, 3, "0", STR_PAD_LEFT) . ';';
            } else {
                $htmloutput .= "&#" . $unistr . ';';
            }
        }
        return $htmloutput;
    }
}
