<?php
namespace System\Util;

use System\Core\App;

class ChineseUtils
{

    /**
     * toBig5
     * @param $str
     * @return string
     */
    public static function toBig5($str)
    {
        return self::convertEncoding($str, 'BIG5');
    }

    /**
     * toGbk
     * @param $str
     * @return string
     */
    public static function toGbk($str)
    {
        return self::convertEncoding($str, 'GBK');
    }

    /**
     * isGbkEncoding
     * @param $str
     * @return bool
     */
    public static function isGbkEncoding($str)
    {
        return mb_check_encoding($str, 'GBK');
    }

    /**
     * convertEncoding
     * @param $str
     * @param string $toEncoding
     * @return string
     */
    private static function convertEncoding($str, $toEncoding = 'BIG5')
    {
        $str1 = App::cache('ChineseUtils:data.' . $toEncoding);

        if (empty($str1)) {
            if ($toEncoding == 'BIG5') {
                $mapFile = SYS_DIR . '/Library/Data/chinese_big5.dat';
            } else {
                $mapFile = SYS_DIR . '/Library/Data/chinese_gbk.dat';
            }

            $handle = fopen($mapFile, 'r');
            $str1 = fread($handle, filesize($mapFile));
            fclose($handle);

            App::cache('ChineseUtils:data.' . $toEncoding, $str1);
        }

        // convert to unicode and map code
        $chg_utf = array();
        for ($i = 0; $i < strlen($str1); $i = $i + 4) {
            $ch1 = ord(substr($str1, $i, 1)) * 256;
            $ch2 = ord(substr($str1, $i + 1, 1));
            $ch1 = $ch1 + $ch2;
            $ch3 = ord(substr($str1, $i + 2, 1)) * 256;
            $ch4 = ord(substr($str1, $i + 3, 1));
            $ch3 = $ch3 + $ch4;
            $chg_utf[$ch1] = $ch3;
        }

        // convert to UTF-8
        $outStr = '';
        for ($k = 0; $k < strlen($str); $k++) {
            $ch = ord(substr($str, $k, 1));

            if ($ch < 0x80) {
                $outStr .= substr($str, $k, 1);
            } else {
                if ($ch > 0xBF && $ch < 0xFE) {
                    if ($ch < 0xE0) {
                        $i = 1;
                        $uniCode = $ch - 0xC0;
                    } elseif ($ch < 0xF0) {
                        $i = 2;
                        $uniCode = $ch - 0xE0;
                    } elseif ($ch < 0xF8) {
                        $i = 3;
                        $uniCode = $ch - 0xF0;
                    } elseif ($ch < 0xFC) {
                        $i = 4;
                        $uniCode = $ch - 0xF8;
                    } else {
                        $i = 5;
                        $uniCode = $ch - 0xFC;
                    }
                }

                $ch1 = substr($str, $k, 1);
                for ($j = 0; $j < $i; $j++) {
                    $ch1 .= substr($str, $k + $j + 1, 1);
                    $ch = ord(substr($str, $k + $j + 1, 1)) - 0x80;
                    $uniCode = $uniCode * 64 + $ch;
                }

                if (!$chg_utf[$uniCode]) {
                    $outStr .= $ch1;
                } else {
                    $outStr .= self::convertUnicodeToUtf($chg_utf[$uniCode]);
                }

                $k += $i;
            }
        }

        return $outStr;
    }

    /**
     * convertUnicodeToUtf
     * @param $unicode
     * @return string
     */
    private static function convertUnicodeToUtf($unicode)
    {
        if ($unicode < 0x80) {
            return chr($unicode);
        }

        $i = 0;
        $outStr = '';
        while ($unicode > 63) { // 2^6=64
            $outStr = chr($unicode % 64 + 0x80) . $outStr;
            $unicode = floor($unicode / 64);
            $i++;
        }

        switch ($i) {
            case 1:
                $outStr = chr($unicode + 0xC0) . $outStr;
                break;
            case 2:
                $outStr = chr($unicode + 0xE0) . $outStr;
                break;
            case 3:
                $outStr = chr($unicode + 0xF0) . $outStr;
                break;
            case 4:
                $outStr = chr($unicode + 0xF8) . $outStr;
                break;
            case 5:
                $outStr = chr($unicode + 0xFC) . $outStr;
                break;
            default:
                echo "unicode error!!";
                exit;
        }

        return $outStr;
    }
}