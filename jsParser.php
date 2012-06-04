<?php
class JsParserException extends Exception {}
class jsParser {

    /**
     * Decode a JSON / javascript variable definition into a PHP equivalent
     * e.g. "{test:5}" returns array('test' => 5);
     *      "false"    returns false
     * @param string $str
     * @return mixed
     */
    public static function doParse($str) {
        return self::parse($str);
    }

    public static function parse(&$str) {
        $str = ltrim($str);

        if ('[' == $str[0]) {
            return self::parseArray($str);
        }

        if ('{' == $str[0]) {
            return self::parseObj($str);
        }

        if ('"' == $str[0] || "'" == $str[0]) {
            if (!preg_match("@^".$str[0]."([^".$str[0]."]*)".$str[0]."@i", $str, $matches)) {
                throw new JsParserException("Missing end token for string");
            }
            $str = ltrim(substr($str, strlen($matches[0])));
            return $matches[1];
        }

        if (preg_match("@^\d\.?\d*@", $str, $matches)) {
            $str = ltrim(substr($str, strlen($matches[0])));
            return $matches[0];
        }

        if (stripos($str, 'true') === 0) {
            $str = ltrim(substr($str, 4));
            return true;
        }

        if (stripos($str, 'false') === 0) {
            $str = ltrim(substr($str, 5));
            return false;
        }

        if (stripos($str, 'null') === 0) {
            $str = ltrim(substr($str, 4));
            return null;
        }

        if (stripos($str, 'undefined') === 0) {
            $str = ltrim(substr($str, 9));
            return null;
        }

        throw new JsParserException("Unexpected token '".$str[0]."'");
    }

    protected static function parseArray(&$str) {
        $ret = array();

        $str = ltrim(substr($str, 1));

        while (']' !== $str[0]) {
            $parsedSection = self::parse($str);
            $ret[] = $parsedSection;

            if (',' == $str[0]) {
                $str = ltrim(substr($str, 1));
            } else {
                break;
            }
        }

        if (']' != $str[0]) {
            throw new JsParserException("Unexpected token '".$str[0]."' expected ]");
        }

        $str = ltrim(substr($str, 1));

        return $ret;
    }

    protected static function parseObj(&$str) {
        $ret = array();

        $str = ltrim(substr($str, 1));

        while ('}' !== $str[0]) {
            if (!preg_match('/^"?([a-zA-z_][a-zA-Z_\d]*)"?\s*:/', $str, $matches)) {
                throw new JsParserException("Unexpected token '".$str[0]."' expecting object key");
            }
            $str = ltrim(substr($str, strlen($matches[0])));
            $key = $matches[1];

            $ret[$matches[1]] = self::parse($str);

            if (',' == $str[0]) {
                $str = ltrim(substr($str, 1));
            } else {
                break;
            }
        }

        if ('}' != $str[0]) {
            throw new JsParserException("Unexpected token '".$str[0]."' expected }");
        }

        $str = ltrim(substr($str, 1));

        return $ret;
    }
}
