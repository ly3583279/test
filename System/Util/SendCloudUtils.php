<?php
namespace System\Util;

use System\Core\App;

class SendCloudUtils
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

            $config = App::conf('app.sendCloud');
        }

        self::$config = $config;

        return self::$config;
    }

    /**
     * sendMail
     * @param $from
     * @param string $to 多个邮件地址使用,或;隔开
     * @param null $subject
     * @param null $body substitution_vars 传递方式 array('%username%' => array('abc', '123'), '%validateCode%' => array(1024, 2180))
     * @param null $templateInvokeName
     * @param null $label
     * @return null|string
     */
    public static function sendMail($from, $to, $subject = null, $body = null, $templateInvokeName = null, $label = null)
    {
        self::init();

        if (empty($body)) {
            return null;
        }

        if (!empty($from) && preg_match('/^(.+?)\<(.+?)\>$/', $from, $match)) {
            $from = $match[2];
            $fromName = trim($match[1]);
        }

        if (empty($from)) {
            $from = self::$config['from'];
        }

        if (empty($fromName)) {
            $fromName = self::$config['fromname'];
        }

        $params = array(
            'api_user' => self::$config['api_user'],
            'api_key' => self::$config['api_key'],
            'resp_email_id' => 'true',
            'from' => $from,
            'fromname' => $fromName
        );

        if (!empty($subject)) {
            $params['subject'] = $subject;
        }

        if (!empty($label)) {
            $params['label'] = $label;
        }

        if (empty($templateInvokeName)) {
            $sendUrl = self::$config['send_url'];
            $params['to'] = $to;
            $params['html'] = $body;
        } else {
            $sendUrl = self::$config['send_template_url'];
            $substitutionVars = array('to' => explode(';', str_replace(',', ';', $to)), 'sub' => $body);
            $params['substitution_vars'] = json_encode($substitutionVars, JSON_HEX_QUOT);
            $params['template_invoke_name'] = $templateInvokeName;
        }

        $data = http_build_query($params);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($sendUrl, false, $context);

        if (empty($result)) {
            return false;
        }

        $result = json_decode($result, true);

        return is_array($result) && isset($result['message']) && $result['message'] == 'success';
    }

}