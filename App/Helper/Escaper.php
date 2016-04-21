<?php

namespace App\Helper;


/**
 * Class Escaper
 * @package App\Helper
 *
 * This class is used as an helper when outputting user input in html/css/js.
 * Based on zendframework/zend-escaper and twig/twig's escape functions.
 *
 * Assumes encoding is UTF-8
 */
class Escaper
{
    public static function escapeHtml($string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function escapeUrl($string): string
    {
        return rawurlencode($string);
    }

    public static function escapeHtmlAttribute($string): string
    {
        if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
            throw new \Exception('The string to escape is not a valid UTF-8 string.');
        }

        return preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', ['\\App\\Helper\\Escaper', '_escapeHtmlAttribute'], $string);
    }

    protected static function _escapeHtmlAttribute($matches)
    {
        /*
         * While HTML supports far more named entities, the lowest common denominator
         * has become HTML5's XML Serialisation which is restricted to the those named
         * entities that XML supports. Using HTML entities would result in this error:
         *     XML Parsing Error: undefined entity
         */

        static $entityMap = array(
            34 => 'quot', /* quotation mark */
            38 => 'amp',  /* ampersand */
            60 => 'lt',   /* less-than sign */
            62 => 'gt',   /* greater-than sign */
        );

        $chr = $matches[0];
        $ord = ord($chr);

        /*
         * The following replaces characters undefined in HTML with the
         * hex entity for the Unicode replacement character.
         */
        if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r") || ($ord >= 0x7f && $ord <= 0x9f)) {
            return '&#xFFFD;';
        }

        /*
         * Check if the current character to escape has a name entity we should
         * replace it with while grabbing the hex value of the character.
         */
        if (strlen($chr) == 1) {
            $hex = strtoupper(substr('00' . bin2hex($chr), -2));
        } else {
            $chr = mb_convert_encoding($chr, 'UTF-16BE', 'UTF-8');
            $hex = strtoupper(substr('0000' . bin2hex($chr), -4));
        }

        $int = hexdec($hex);
        if (array_key_exists($int, $entityMap)) {
            return sprintf('&%s;', $entityMap[$int]);
        }

        /*
         * Per OWASP recommendations, we'll use hex entities for any other
         * characters where a named entity does not exist.
         */

        return sprintf('&#x%s;', $hex);
    }

    public static function escapeCss($string): string
    {
        if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
            throw new \Exception('The string to escape is not a valid UTF-8 string.');
        }

        $string = preg_replace_callback('#[^a-zA-Z0-9]#Su', ['\\App\\Helper\\Escaper', '_escapeCss'], $string);

        return $string;
    }

    private static function _escapeCss($matches)
    {
        $char = $matches[0];
        // \xHH
        if (!isset($char[1])) {
            $hex = ltrim(strtoupper(bin2hex($char)), '0');
            if (0 === strlen($hex)) {
                $hex = '0';
            }
            return '\\'.$hex.' ';
        }
        // \uHHHH
        $char = mb_convert_encoding($char, 'UTF-16BE', 'UTF-8');
        return '\\'.ltrim(strtoupper(bin2hex($char)), '0').' ';
    }

    public static function escapeJs($string): string
    {
        if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
            throw new \Exception('The string to escape is not a valid UTF-8 string.');
        }

        return preg_replace_callback('#[^a-zA-Z0-9,\._]#Su', ['\\App\\Helper\\Escaper', '_escapeJs'], $string);
    }

    private function _escapeJs($matches)
    {
        $char = $matches[0];
        // \xHH
        if (!isset($char[1])) {
            return '\\x'.strtoupper(substr('00'.bin2hex($char), -2));
        }
        // \uHHHH
        $char = mb_convert_encoding($char, 'UTF-16BE', 'UTF-8');
        return '\\u'.strtoupper(substr('0000'.bin2hex($char), -4));
    }
}