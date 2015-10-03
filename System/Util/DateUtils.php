<?php
namespace System\Util;

use System\Core\App;
use System\Core\Request;

/**
 * Class DateUtils
 * @package System\Util
 */
class DateUtils
{
    /**
     * 当前时间
     * @return mixed
     */
    public static function getTimestamp()
    {
        return $_SERVER['REQUEST_TIME']; //Request::server('REQUEST_TIME');
    }

    /**
     * 系统设定的时区
     * @return int
     */
    public static function getTimezone()
    {
        return App::conf('sys.timezone');
    }

    /**
     * 格式化时间
     * @param int $time 时间值
     * @param string $format 格式，-开头时强制使用指定格式输出
     * @return string
     */
    public static function format($time = 0, $format = 'Y-m-d H:i:s')
    {
        $timestamp = self::getTimestamp();
        if (empty($time)) {
            $time = $timestamp;
        } elseif ($time < 0) {
            $time += $timestamp;
        }

        $maybeFormat = false;
        $interval = $timestamp - $time;

        if ($format[0] == '-') {
            $maybeFormat = true;
            $format = substr($format, 1);
        }

        if ($maybeFormat || $interval > 2592000) {
            $timezone = self::getTimezone();
            if (empty($format)) {
                $format = 'Y-m-d H:i:s';
            } elseif ($format == 'APM') {
                $h = gmdate('G', $time + ($timezone * 3600));
                if ($h < 9) {
                    return '早上';
                } elseif ($h < 12) {
                    return '上午';
                } elseif ($h < 13) {
                    return '中午';
                } elseif ($h < 19) {
                    return '下午';
                } else {
                    return '晚上';
                }
            } elseif ($format == 'WEEK') {
                $week = array('日', '一', '二', '三', '四', '五', '六');
                return $week[gmdate('w', $time + ($timezone * 3600))];
            }
            return gmdate($format, $time + ($timezone * 3600));
        } elseif ($interval > 604800) { // 大于7天
            return intval($interval / 604800) . '个星期前';
        } elseif ($interval > 86400) { // 大于1天
            return intval($interval / 86400) . '天前';
        } elseif ($interval > 3600) { // 大于1小时
            return intval($interval / 3600) . '小时前';
        } elseif ($interval > 60) { // 大于1分钟
            return intval($interval / 60) . '分钟前';
        } elseif ($interval > 15) {
            return $interval . '秒前';
        } else {
            return '刚刚';
        }
    }
} 