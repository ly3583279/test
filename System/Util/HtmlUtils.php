<?php
namespace System\Util;

/**
 * Class HtmlUtils
 * @package System\Util
 */
class HtmlUtils
{
    private static $htmlPurifierInstance;

    /**
     * getPurifiedHtml
     * @param $str
     * @param null $options
     * @return string
     */
    public static function getPurifiedHtml($str, $options = null)
    {
        if (empty($options)) {
            $allowableTags = '<p><span><img><strong><em><del><b><i><s><blockquote><code><pre><br><hr><sub><sup><a><table><thead><tbody><tfoot><tr><th><td><ol><ul><li><dl><dt><dd><embed>';
        } elseif (is_string($options)) {
            $allowableTags = $options;
        } else {
            if (empty($options['allowableTags'])) {
                $allowableTags = '<p><span><img><strong><em><del><b><i><s><blockquote><code><pre><br><hr><sub><sup>';
            } else {
                $allowableTags = $options['allowableTags'];
            }

            if (isset($options['allowLink']) && $options['allowLink']) {
                $allowableTags .= '<a>';
            }

            if (isset($options['allowTable']) && $options['allowTable']) {
                $allowableTags .= '<table><thead><tbody><tfoot><tr><th><td>';
            }

            if (isset($options['allowList']) && $options['allowList']) {
                $allowableTags .= '<ol><ul><li><dl><dt><dd>';
            }

            if (isset($options['allowEmbed']) && $options['allowEmbed']) {
                $allowableTags .= '<embed>';
            }
        }

        return strip_tags(self::purifyHtml($str), $allowableTags);
    }

    /**
     * getPurifiedText
     * @param $str
     * @return string
     */
    public static function getPurifiedText($str)
    {
        return strip_tags(self::purifyHtml($str));
    }

    /**
     * purifyHtml
     * @param $html
     * @param null $config
     * @return string
     */
    public static function purifyHtml($html, $config = null)
    {
        if (self::$htmlPurifierInstance == null) {
            include SYS_DIR . '/Library/HTMLPurifier/HTMLPurifier.standalone.php';
            self::$htmlPurifierInstance = new \HTMLPurifier(\HTMLPurifier_Config::createDefault());
        }
        return self::$htmlPurifierInstance->purify($html, $config);
    }
}