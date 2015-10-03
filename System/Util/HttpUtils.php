<?php
namespace System\Util;


class HttpUtils
{
    /**
     * get
     * @param $url
     * @param null $options
     * @return mixed|string
     */
    public static function get($url, $options = null)
    {
        if (empty($options)) {
            $options = array();
        }

        $options['method'] = 'GET';

        return self::request($url, $options);
    }

    /**
     * post
     * @param $url
     * @param null $postFields
     * @param null $options
     * @return mixed|string
     */
    public static function post($url, $postFields = null, $options = null)
    {
        if (empty($options)) {
            $options = array();
        }

        $options['method'] = 'POST';
        $options['postFields'] = $postFields;

        return self::request($url, $options);
    }

    /**
     * request
     * @param $url
     * @param null $options
     * @return mixed|string
     */
    public static function request($url, $options = null)
    {
        if (empty($options)) {
            $options = array();
        }

        $method = isset($options['method']) ? $options['method'] : 'GET';
        $sslVerifypeer = isset($options['sslVerifypeer']) ? $options['sslVerifypeer'] : false;
        $referer = isset($options['referer']) ? $options['referer'] : '';
        $userAgent = isset($options['userAgent']) ? $options['userAgent'] : 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)';
        $httpHeader = isset($options['httpHeader']) ? $options['httpHeader'] : null;
        $postFields = isset($options['postFields']) ? $options['postFields'] : '';
        $cookieFields = isset($options['cookieFields']) ? $options['cookieFields'] : '';
        $outputHeader = isset($options['outputHeader']) ? $options['outputHeader'] : false;

        $fromEncoding = isset($options['fromEncoding']) ? $options['fromEncoding'] : '';
        $toEncoding = isset($options['toEncoding']) ? $options['toEncoding'] : '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerifypeer);
        curl_setopt($ch, CURLOPT_REFERER, empty($referer) ? $url : $referer);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, $outputHeader);

        if (!empty($httpHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        }

        if (!empty($postFields)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }

        if (!empty($cookieFields)) {
            curl_setopt($ch, CURLOPT_COOKIE, http_build_query($cookieFields, '', '; '));//http_build_cookie($cookieFields));
        }

        // more options
        $opts = $options['CURLOPTS'];
        if (!empty($opts)) {
            foreach ($opts as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }

        $buffer = curl_exec($ch);

        curl_close($ch);

        if (empty($buffer) || empty($toEncoding) || empty($fromEncoding) || $toEncoding == $fromEncoding) {
            return $buffer;
        } else {
            return mb_convert_encoding($buffer, $toEncoding, $fromEncoding);
        }
    }

}