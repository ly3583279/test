<?php
namespace System\Util;

use System\Core\App;

class LeanCloudUtils
{
    private static $config;

    /**
     * 初始化配置
     * @param null $config
     * @return null
     */
    public static function init($config = null)
    {
        if (empty($config)) {
            if (self::$config !== null) {
                return self::$config;
            }

            $config = App::conf('app.leanCloud');
        }

        self::$config = $config;

        return self::$config;
    }

    /**
     * requestSmsCode
     * @param $mobilePhoneNumber
     * @param string $smsType
     * @param null $template
     * @param null $params
     * @return bool|null
     */
    public static function requestSmsCode($mobilePhoneNumber, $smsType = 'sms', $template = null, $params = null)
    {
        self::init();

        if (empty($mobilePhoneNumber)) {
            return null;
        }

        $requestUrl = self::$config['api_url'] . '/requestSmsCode';

        $data = array();
        $data['mobilePhoneNumber'] = $mobilePhoneNumber;
        $data['smsType'] = $smsType;
        if (!empty($template)) {
            $data['template'] = $template;
        }
        if (!empty($params)) {
            $data += $params;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-AVOSCloud-Application-Id: ' . self::$config['api_id'],
            'X-AVOSCloud-Application-Key: ' . self::$config['api_key'],
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, TextUtils::encodeJson($data));
        curl_setopt($ch, CURLOPT_URL, $requestUrl);

        $result = curl_exec($ch);

        curl_close($ch);

        if (empty($result)) {
            return false;
        }

        $result = json_decode($result, true);

        return is_array($result) && empty($result);
    }

    /**
     * verifySmsCode
     * @param $mobilePhoneNumber
     * @param $code
     * @return bool|null
     */
    public static function verifySmsCode($mobilePhoneNumber, $code)
    {
        self::init();

        if (empty($mobilePhoneNumber) || empty($code)) {
            return null;
        }

        $requestUrl = self::$config['api_url'] . '/verifySmsCode/' . $code . '?mobilePhoneNumber=' . $mobilePhoneNumber;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-AVOSCloud-Application-Id: ' . self::$config['api_id'],
            'X-AVOSCloud-Application-Key: ' . self::$config['api_key'],
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, TextUtils::encodeJson($data));
        curl_setopt($ch, CURLOPT_URL, $requestUrl);

        $result = curl_exec($ch);

        curl_close($ch);

        if (empty($result)) {
            return false;
        }

        $result = json_decode($result, true);

        return is_array($result) && empty($result);
    }

}