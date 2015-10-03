<?php
namespace System\Util;

/**
 * Class FileUtils
 * @package System\Util
 */
class FileUtils
{
    /**
     * getFileExt
     * @param $fileName
     * @param string $defaultExt
     * @return string
     */
    public static function getFileExt($fileName, $defaultExt = 'tmp')
    {
        $ext = strtolower(trim(pathinfo($fileName, PATHINFO_EXTENSION)));
        return empty($ext) ? $defaultExt : $ext;
    }

    /**
     * getFileSize
     * @param $fileSize
     * @return string
     */
    public static function getFileSize($fileSize)
    {
        if (is_string($fileSize) && file_exists($fileSize)) {
            $fileSize = filesize($fileSize);
        }

        $fileSize = intval($fileSize);

        if ($fileSize < 1048576) {
            return sprintf("%.2f M", $fileSize /= 1048576);
        }

        foreach (array('', 'K', 'M', 'G', 'T') as $i => $k) {
            if ($fileSize < 1024) break;
            $fileSize /= 1024;
        }

        return sprintf("%.2f %s", $fileSize, $k);
    }
}