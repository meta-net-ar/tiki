/**
 * This is the JavaScript equivalent to PHP's preg_quote : https://php.net/manual/function.preg-quote.php
 * credits: https://locutus.io/php/preg_quote
 * @param {string} str The input string
 * @param {string} delimiter The delimiter to use (optional)
 */
export function preg_quote(str, delimiter) {
    return (str + "").replace(new RegExp("[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\" + (delimiter || "") + "-]", "g"), "\\$&");
}
