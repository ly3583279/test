<?php
namespace System\Util;

/**
 * Class LogUtils
 * @package System\Util
 */
class LogUtils
{
    /**
     * LogUtils::d($tag, $params), dump log to error_log
     * @param $tag
     * @param $params
     * @return bool
     */
    public static function d($tag, $params)
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            // $params = is_array($params) ? $params : func_get_args();
            return error_log("[{$tag}]: " . print_r($params, true), 0);
        }
    }

    /**
     * LogUtils::d($tag, $params), dump log to log file
     * @param $tag
     * @param $params
     * @return int
     */
    public static function df($tag, $params)
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $logFile = APP_DIR . '/Data/log/log-' . DateUtils::format(0, '-Ymd') . '.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }

            // $params = is_array($params) ? $params : func_get_args();

            $data = '# ' . DateUtils::format(time(), '-Y-m-d H:i:s') . "\r\n";
            $data .= '# ' . $tag . "\r\n";
            $data .= print_r($params, true);
            $data .= "\r\n\r\n\r\n";

            return file_put_contents($logFile, $data, FILE_APPEND);
        }
    }
}