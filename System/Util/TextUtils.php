<?php
namespace System\Util;

/**
 * Class TextUtils
 * @package System\Util
 */
class TextUtils
{

    /**
     * addslashes
     * @param $var
     * @return array|string
     */
    public static function addslashes(&$var)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[$key] = self::addslashes($value);
            }
        } else {
            $var = addslashes($var);
        }
        return $var;
    }

    /**
     * getTextSignature
     * @param $str
     * @param bool|false $purified
     * @return string
     */
    public static function getTextSignature($str, $purified = false)
    {
        $str = $purified ? $str : HtmlUtils::getPurifiedText($str);
        return md5(preg_replace('/[^\w]+/u', '', $str));
    }

    /**
     * getTextSummary
     * @param $str
     * @param int $length
     * @param bool|true $forceCut
     * @param string $summarySuffix
     * @param string $encoding
     * @return string
     */
    public static function getTextSummary($str, $length = 240, $forceCut = true, $summarySuffix = '...', $encoding = 'UTF-8')
    {
        $strLen = mb_strlen($str, $encoding);
        if ($strLen < $length) {
            return $str;
        }

        $str = trim($str) . ' ';// trim( mb_substr($str, 0, $length, $encoding) );
        // 中文分割
        $str1 = str_replace(array("\r", "\n", '，', '。', '；', '：', '！', '？'), '`', $str);
        // 英文分割
        $str1 = str_replace(' ', ' ', $str1); // fix bug
        $str1 = rtrim(str_replace(array(', ', '. ', '; ', ': ', '! ', '? '), '` ', $str1), '` ');

        /*
		$pos = mb_strrpos($str1, '`', 0, $encoding);
		if( $pos===false ) {
            return $str;
		}
        */

        $pos = mb_strpos($str1, '`', 0, $encoding);
        if ($pos === false) {
            $pos = $strLen;
        } else {
            if ($pos < $length) {
                while (true) {
                    $lastPos = mb_strpos($str1, '`', $pos + 1, $encoding);
                    if ($lastPos === false || $lastPos > $length) {
                        break;
                    }
                    $pos = $lastPos;
                }
            }
        }

        // 强制截断
        if ($forceCut && $pos > $length) {
            $pos = $length;
        }

        //$str = mb_substr($str, 0, $pos+1, $encoding);
        $str = trim(mb_substr($str, 0, $pos, $encoding)) . ($pos == $strLen ? '' : $summarySuffix);

        return $str;
    }

    /**
     * explodeKeyValue: 将一个字符串按指定字符分割成一个key和一个value
     * @param $str
     * @param string $delimiter
     * @param string $defaultKey
     * @return array
     */
    public static function explodeKeyValue($str, $delimiter = ':', $defaultKey = '')
    {
        $delimiterPos = strpos($str, $delimiter);
        if ($delimiter == ':') {
            // 兼容中文冒号
            $delimiterCnPos = strpos($str, '：');
            if ($delimiterCnPos !== false && ($delimiterPos === false || $delimiterPos > $delimiterCnPos)) {
                $delimiter = '：';
                $delimiterPos = $delimiterCnPos;
            }
        }

        if ($delimiterPos === false) {
            return array($defaultKey, $str);
        } else {
            $delimiterCutPos = strpos($str, '://');
            if ($delimiterCutPos === false) {
                $delimiterCutPos = $delimiter == '/' ? false : strpos($str, '/');
            }
            if ($delimiterCutPos === false || $delimiterPos < $delimiterCutPos) {
                return explode($delimiter, $str, 2);
            } else {
                return array($defaultKey, $str);
            }
        }
    }

    /**
     * parseString
     * @param $str
     * @param string $delimiter
     * @return array
     */
    public static function parseString($str, $delimiter = '&')
    {
        $arr = array();
        if (empty($str)) {
            return $arr;
        }

        $strArr = explode($delimiter, $str);
        foreach ($strArr as $str) {
            list($key, $value) = explode('=', $str, 2);
            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * @param $str
     * @param $startStr
     * @param $endStr
     * @return string
     */
    public static function subString($str, $startStr, $endStr)
    {
        $lPos = strpos($str, $startStr);
        $rPos = strrpos($str, $endStr);
        return substr($str, $lPos + 1, $rPos - $lPos - 1);
    }

    /**
     * stripTags
     * @param $var
     * @param null $allowableTags
     * @return string
     */
    public static function stripTags($var, $allowableTags = null)
    {
        return strip_tags(strval($var), $allowableTags);
    }

    /**
     * sanitizeWords
     * @param $string
     * @param string $delimiter
     * @return mixed
     */
    public static function sanitizeWords($string, $delimiter = '|')
    {
        if (empty($string)) {
            return $string;
        }

        return preg_replace('/\s+/u', $delimiter, trim(preg_replace('/[^\w\@\-\.]+/u', ' ', $string)));
    }

    /**
     * sanitizeText
     * @param string $str
     * @param boolean $allowSpaces
     * @return mixed
     */
    public static function sanitizeText($str, $allowSpaces = false)
    {
        if (empty($str)) {
            return $str;
        }

        $search = array(
            '/[^\w\-\. ]+/u',            // Remove non safe characters
            '/\s\s+/',                    // Remove extra whitespace
            '/\.\.+/', '/--+/', '/__+/'    // Remove duplicate symbols
        );

        $str = preg_replace($search, array(' ', ' ', '.', '-', '_'), $str);

        if (!$allowSpaces) {
            $str = preg_replace('/--+/', '-', str_replace(' ', '-', $str));
        }

        return trim($str, '-._ ');
    }

    /**
     * alias htmlspecialchars
     * @param $str
     * @param int $flags
     * @return string
     */
    public static function encodeHtmlSpecialChars($str, $flags = ENT_QUOTES)
    {
        return htmlspecialchars($str, $flags, 'UTF-8');
    }

    /**
     * alias htmlspecialchars_decode
     * @param $str
     * @param int $flags
     * @return string
     */
    public static function decodeHtmlSpecialChars($str, $flags = ENT_QUOTES)
    {
        return htmlspecialchars_decode($str, $flags);
    }

    /**
     * 转换base64特殊字符
     * @param $str
     * @param bool|false $trimTilde
     * @return string
     */
    public static function encodeBase64($str, $trimTilde = false)
    {
        $str = strtr(base64_encode($str), '+/=', '-_~');
        return $trimTilde ? trim($str, '~') : $str;
    }

    /**
     * 解码base64
     * @param $str
     * @return string
     */
    public static function decodeBase64($str)
    {
        return base64_decode(strtr($str, '-_~', '+/='));
    }

    /**
     * alias json_encode
     * @param $data
     * @param int $options
     * @return string
     */
    public static function encodeJson($data, $options = JSON_HEX_QUOT)
    {
        return json_encode($data, $options);
    }

    /**
     * alias json_decode
     * @param $data
     * @param bool|true $assoc
     * @return mixed
     */
    public static function decodeJson($data, $assoc = true)
    {
        return json_decode($data, $assoc);
    }

    /**
     * encryptText: 加密数据
     * @param $key
     * @param $text
     * @return string
     */
    public static function encryptText($key, $text)
    {
        $mKey = sha1($key);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_RAND);
        $text = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hash('sha256', $mKey, TRUE), $text, MCRYPT_MODE_CBC, $iv) . $iv;

        return substr(md5($mKey . $text), 8, 8) . self::encodeBase64($text, true);
    }

    /**
     * decryptText: 解密数据
     * @param $key
     * @param $text
     * @return string
     */
    public static function decryptText($key, $text)
    {
        $mKey = sha1($key);

        $hash = substr($text, 0, 8);
        $text = self::decodeBase64(substr($text, 8));
        if (substr(md5($mKey . $text), 8, 8) != $hash) {
            return null;
        }

        $iv = substr($text, -mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC));
        $text = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hash('sha256', $mKey, TRUE), substr($text, 0, -strlen($iv)), MCRYPT_MODE_CBC, $iv);

        return rtrim($text, "\x0");
    }
} 