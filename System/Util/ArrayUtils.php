<?php
namespace System\Util;

/**
 * Class ArrayUtils
 * @package System\Util
 */
class ArrayUtils
{

    /**
     * cropArray
     * @param $array
     * @param null $first
     * @param null $last
     * @param bool|false $preserve
     */
    public static function cropArray(&$array, $first = null, $last = null, $preserve = false)
    {
        if (empty($array)) {
            return $array;
        }

        // 去除$first之前的数据，即表头数据
        if ($first != null && array_key_exists($first, $array)) {
            $keyArr = array_keys($array);
            foreach ($keyArr as $key) {
                if ($preserve && $key == $first) {
                    break;
                }
                unset($array[$key]);
                if ($key == $first) {
                    break;
                }
            }
        }

        // 去除$last之后的数据，即表尾数据
        if ($last != null && array_key_exists($last, $array)) {
            $keyArr = array_keys($array);
            $maybeUnset = false;
            foreach ($keyArr as $key) {
                if ($key == $last) {
                    $maybeUnset = true;
                    if ($preserve) {
                        continue;
                    }
                }
                if ($maybeUnset) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }

    /**
     * subArray
     * @param $array
     * @param int $start
     * @param null $length
     * @param bool|false $preserveKeys
     * @return array
     */
    public static function subArray($array, $start = 0, $length = null, $preserveKeys = false)
    {
        return array_slice($array, $start, $length, $preserveKeys);
    }
}