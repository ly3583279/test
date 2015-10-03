<?php
namespace System\Core;
use System\Util\TextUtils;

/**
 * Class Request
 * @package System\Core
 */
class Request
{

    // get/post/cookie/server variables

    /**
     * 获取$_GET值
     * @param string $key
     * @param string $defaultValue
     * @param null $allowableTags
     * @return string
     */
    public static function get($key = null, $defaultValue = '', $allowableTags = null)
    {
        return self::fetchFromArray($_GET, $key, $defaultValue, true, $allowableTags);
    }

    /**
     * 获取$_GET原生值
     * @param string|null $key
     * @param null $defaultValue
     * @return string
     */
    public static function get_raw($key = null, $defaultValue = null)
    {
        return self::fetchFromArray($_GET, $key, $defaultValue, false, false);
    }

    /**
     * 获取$_GET整数值
     * @param $key
     * @param int $defaultValue
     * @return int|string
     */
    public static function get_int($key, $defaultValue = 0)
    {
        $var = self::get($key, $defaultValue);
        return is_numeric($var) ? $var : intval($var);
    }

    /**
     * 获取$_GET小数值
     * @param $key
     * @param int $defaultValue
     * @return float|int|string
     */
    public static function get_float($key, $defaultValue = 0)
    {
        $var = self::get($key, $defaultValue);
        return is_numeric($var) ? $var : floatval($var);
    }

    /**
     * 获取$_GET JSON值
     * @param $key
     * @param bool|true $assoc
     * @return mixed|null
     */
    public static function get_json($key, $assoc = true)
    {
        $var = self::get_raw($key);
        return empty($var) ? null : TextUtils::decodeJson($var, $assoc);
    }

    /**
     * 获取$_POST值
     * @param null $key
     * @param string $defaultValue
     * @param null $allowableTags
     * @return string
     */
    public static function post($key = null, $defaultValue = '', $allowableTags = null)
    {
        return self::fetchFromArray($_POST, $key, $defaultValue, true, $allowableTags);
    }

    /**
     * 获取$_POST原生值
     * @param null $key
     * @param null $defaultValue
     * @return string
     */
    public static function post_raw($key = null, $defaultValue = null)
    {
        return self::fetchFromArray($_POST, $key, $defaultValue, false, false);
    }

    /**
     * 获取$_POST整数值
     * @param $key
     * @param int $defaultValue
     * @return int|string
     */
    public static function post_int($key, $defaultValue = 0)
    {
        $var = self::post($key, $defaultValue);
        return is_numeric($var) ? $var : intval($var);
    }

    /**
     * 获取$_POST小数值
     * @param $key
     * @param int $defaultValue
     * @return float|int|string
     */
    public static function post_float($key, $defaultValue = 0)
    {
        $var = self::post($key, $defaultValue);
        return is_numeric($var) ? $var : floatval($var);
    }

    /**
     * 获取$_POST JSON值
     * @param $key
     * @param bool|true $assoc
     * @return mixed|null
     */
    public static function post_json($key, $assoc = true)
    {
        $var = self::post_raw($key);
        return empty($var) ? null : TextUtils::decodeJson($var, $assoc);
    }

    /**
     * 获取$_GET/$_POST值，$_GET优先
     * @param null $key
     * @param string $defaultValue
     * @param null $allowableTags
     * @return string
     */
    public static function get_post($key = null, $defaultValue = '', $allowableTags = null)
    {
        return self::fetchFromArray(isset($_GET[$key]) ? $_GET : $_POST, $key, $defaultValue, true, $allowableTags);
    }

    /**
     * 获取$_GET/$_POST原生值，$_GET优先
     * @param $key
     * @param null $defaultValue
     * @return string
     */
    public static function get_post_raw($key, $defaultValue = null)
    {
        return self::fetchFromArray(isset($_GET[$key]) ? $_GET : $_POST, $key, $defaultValue, false, false);
    }

    /**
     * 获取$_GET/$_POST整数值，$_GET优先
     * @param $key
     * @param int $defaultValue
     * @return int|string
     */
    public static function get_post_int($key, $defaultValue = 0)
    {
        $var = self::get_post($key, $defaultValue);
        return is_numeric($var) ? $var : intval($var);
    }

    /**
     * 获取$_GET/$_POST小数值，$_GET优先
     * @param $key
     * @param int $defaultValue
     * @return float|int|string
     */
    public static function get_post_float($key, $defaultValue = 0)
    {
        $var = self::get_post($key, $defaultValue);
        return is_numeric($var) ? $var : floatval($var);
    }

    /**
     * get_post_json
     * @param $key
     * @param bool|true $assoc
     * @return mixed|null
     */
    public static function get_post_json($key, $assoc = true)
    {
        $var = self::get_post_raw($key);
        return empty($var) ? null : TextUtils::decodeJson($var, $assoc);
    }

    /**
     * 获取$_POST/$_GET值，$_POST优先
     * @param null $key
     * @param string $defaultValue
     * @param null $allowableTags
     * @return string
     */
    public static function post_get($key = null, $defaultValue = '', $allowableTags = null)
    {
        return self::fetchFromArray(isset($_POST[$key]) ? $_POST : $_GET, $key, $defaultValue, true, $allowableTags);
    }

    /**
     * 获取$_POST/$_GET原生值，$_POST优先
     * @param $key
     * @param null $defaultValue
     * @return string
     */
    public static function post_get_raw($key, $defaultValue = null)
    {
        return self::fetchFromArray(isset($_POST[$key]) ? $_POST : $_GET, $key, $defaultValue, false, false);
    }

    /**
     * 获取$_POST/$_GET整数值，$_POST优先
     * @param $key
     * @param int $defaultValue
     * @return int|string
     */
    public static function post_get_int($key, $defaultValue = 0)
    {
        $var = self::post_get($key, $defaultValue);
        return is_numeric($var) ? $var : intval($var);
    }

    /**
     * 获取$_POST/$_GET小数值，$_POST优先
     * @param $key
     * @param int $defaultValue
     * @return float|int|string
     */
    public static function post_get_float($key, $defaultValue = 0)
    {
        $var = self::post_get($key, $defaultValue);
        return is_numeric($var) ? $var : floatval($var);
    }

    /**
     * post_get_json
     * @param $key
     * @param bool|true $assoc
     * @return mixed|null
     */
    public static function post_get_json($key, $assoc = true)
    {
        $var = self::post_get_raw($key);
        return empty($var) ? null : TextUtils::decodeJson($var, $assoc);
    }

    /**
     * 获取$_COOKIE值
     * @param null $key
     * @param string $defaultValue
     * @param null $allowableTags
     * @return string
     */
    public static function cookie($key = null, $defaultValue = '', $allowableTags = null)
    {
        return self::fetchFromArray($_COOKIE, App::conf('sys.cookie_pre') . $key, $defaultValue, true, $allowableTags);
    }

    /**
     * 获取$_COOKIE原生值
     * @param null $key
     * @param null $defaultValue
     * @return string
     */
    public static function cookie_raw($key = null, $defaultValue = null)
    {
        return self::fetchFromArray($_COOKIE, $key, $defaultValue, false, false);
    }

    /**
     * 获取$_COOKIE整数值
     * @param $key
     * @param int $defaultValue
     * @return int|string
     */
    public static function cookie_int($key, $defaultValue = 0)
    {
        $var = self::cookie($key, $defaultValue);
        return is_numeric($var) ? $var : intval($var);
    }

    /**
     * 获取$_COOKIE小数值
     * @param $key
     * @param int $defaultValue
     * @return float|int|string
     */
    public static function cookie_float($key, $defaultValue = 0)
    {
        $var = self::cookie($key, $defaultValue);
        return is_numeric($var) ? $var : floatval($var);
    }

    /**
     * cookie_json
     * @param $key
     * @param bool|true $assoc
     * @return mixed|null
     */
    public static function cookie_json($key, $assoc = true)
    {
        $var = self::cookie_raw($key);
        return empty($var) ? null : TextUtils::decodeJson($var, $assoc);
    }

    /**
     * 获取$_SERVER值
     * @param null $key
     * @param string $defaultValue
     * @param null $allowableTags
     * @return string
     */
    public static function server($key = null, $defaultValue = '', $allowableTags = null)
    {
        return self::fetchFromArray($_SERVER, $key, $defaultValue, true, $allowableTags);
    }

    /**
     * 获取$_SERVER原生值
     * @param null $key
     * @param null $defaultValue
     * @return string
     */
    public static function server_raw($key = null, $defaultValue = null)
    {
        return self::fetchFromArray($_SERVER, $key, $defaultValue, false, false);
    }

    /**
     * 获取$_SERVER整数值
     * @param $key
     * @param int $defaultValue
     * @return int|string
     */
    public static function server_int($key, $defaultValue = 0)
    {
        $var = self::server($key, $defaultValue);
        return is_numeric($var) ? $var : intval($var);
    }

    /**
     * 获取$_SERVER小数值
     * @param $key
     * @param int $defaultValue
     * @return float|int|string
     */
    public static function server_float($key, $defaultValue = 0)
    {
        $var = self::server($key, $defaultValue);
        return is_numeric($var) ? $var : floatval($var);
    }

    /**
     * server_json
     * @param $key
     * @param bool|true $assoc
     * @return mixed|null
     */
    public static function server_json($key, $assoc = true)
    {
        $var = self::server_raw($key);
        return empty($var) ? null : TextUtils::decodeJson($var, $assoc);
    }

    // request utils

    /**
     * isCliRequest
     * @return bool
     */
    public static function isCliRequest()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * isAjaxRequest
     * @return bool
     */
    public static function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    /**
     * isPjaxRequest
     * @return bool
     */
    public static function isPjaxRequest()
    {
        return self::isAjaxRequest() && isset($_SERVER['HTTP_X_PJAX']) && !empty($_SERVER['HTTP_X_PJAX']);
    }

    /**
     * isGetRequest
     * @return bool
     */
    public static function isGetRequest()
    {
        return self::getRequestMethod() == 'GET';
    }

    /**
     * isPostRequest
     * @return bool
     */
    public static function isPostRequest()
    {
        return self::getRequestMethod() == 'POST';
    }

    /**
     * isPutRequest
     * @return bool
     */
    public static function isPutRequest()
    {
        return self::getRequestMethod() == 'PUT';
    }

    /**
     * isDeleteRequest
     * @return bool
     */
    public static function isDeleteRequest()
    {
        return self::getRequestMethod() == 'DELETE';
    }

    /**
     * isPostSubmitRequest
     * @param bool $verifyRequestHash
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public static function isPostSubmitRequest($verifyRequestHash = true, $return = false, $exceptionMessage = '服务器错误，请求不合法！')
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['submit'] || $_POST['postSubmit'])) {
            if ($verifyRequestHash) {
                $now = self::getRequestTime();
                $requestHash = isset($_POST['requestHash']) ? $_POST['requestHash'] : $_POST['formHash'];
                if (empty($requestHash)) {
                    $requestTime = 0;
                } else {
                    $requestTime = self::getRequestHash($requestHash);
                }

                if (($now - $requestTime) > 86400) { // 超时
                    if ($return) {
                        return false;
                    } else {
                        throw new \Exception($exceptionMessage, 403);
                    }
                } else {
                    return true;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * isValidRequest
     * @param string $requestHash
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public static function isValidRequest($requestHash = '', $return = false, $exceptionMessage = '服务器错误，请求不合法！')
    {
        $now = self::getRequestTime();
        if (empty($requestHash)) {
            $requestTime = 0;
        } else {
            $requestTime = self::getRequestHash($requestHash);
        }

        if (($now - $requestTime) > 86400) { // 超时
            if ($return) {
                return false;
            } else {
                throw new \Exception($exceptionMessage, 403);
            }
        } else {
            return true;
        }
    }

    // utils function

    /**
     * getRequestTime
     * @param bool|false $float
     * @return mixed
     */
    public static function getRequestTime($float = false)
    {
        return $float ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];
    }

    /**
     * getRequestMethod
     * @param bool|false $strToLower
     * @return string
     */
    public static function getRequestMethod($strToLower = false)
    {
        return $strToLower ? strtolower($_SERVER['REQUEST_METHOD']) : strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * getHttpReferer
     * @return string
     */
    public static function getHttpReferer()
    {
        return self::server('HTTP_REFERER');
    }

    /**
     * getHttpUserAgent
     * @return string
     */
    public static function getHttpUserAgent()
    {
        return self::server('HTTP_USER_AGENT');
    }

    /**
     * getHttpClientIp
     * @return array|null|string
     */
    public static function getHttpClientIp()
    {
        $ipAddress = App::val('HTTP_CLIENT_IP');

        if (empty($ipAddress)) {
            foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR') as $key) {
                $ipAddress = self::server($key);
                if ($ipAddress && (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
                    break;
                }
            }

            if (empty($ipAddress)) {
                $ipAddress = '0.0.0.0';
            }

            App::val('HTTP_CLIENT_IP', $ipAddress);
        }

        return $ipAddress;
    }

    /**
     * 获取分页号码
     * @return int|number
     */
    public static function getPageNumber()
    {
        $page = intval(isset($_GET['page']) ? $_GET['page'] : (isset($_GET['p']) ? $_GET['p'] : 0));
        return ($page < 1) ? 1 : $page;
    }

    /**
     * 获取分页大小
     * @param int $defaultValue default: 25
     * @return sys.default_page_size|int
     */
    public static function getPageSize($defaultValue = 25)
    {
        return App::conf('sys.default_page_size', $defaultValue);
    }

    /**
     * 获取分页起始位置
     * @param int $page default: 1
     * @param int $pageSize default: 25
     * @return int
     */
    public static function getPageStart($page = 1, $pageSize = 25)
    {
        return ($page - 1) * $pageSize;
    }

    /**
     * 获取分页集成数据
     * @param int $pageSize if 0 the get default pageSize
     * @return array
     */
    public static function getPageExtract($pageSize = 0)
    {
        $page = self::getPageNumber();
        $pageSize = empty($pageSize) ? self::getPageSize($pageSize) : $pageSize;
        $pageStart = self::getPageStart($page, $pageSize);
        return array('page' => $page, 'pageSize' => $pageSize, 'pageStart' => $pageStart);
    }

    // hash utils

    /**
     * getCookieHash
     * @param int $length
     * @return array|null|string
     */
    public static function getCookieHash($length = 8)
    {
        $cookieHash = App::val('cookieHash');
        if (empty($cookieHash)) {
            $cookieHash = substr(md5(App::conf('sys.cookie_domain') . App::conf('sys.cookie_key') . App::conf('sys.cookie_pre')), 8, $length);
            App::val('cookieHash', $cookieHash);
        }
        return $cookieHash;
    }

    /**
     * getDataHash
     * @param $data
     * @param int $length
     * @return string
     */
    public static function getDataHash($data, $length = 8)
    {
        $cookieHash = self::getCookieHash();
        return substr(md5($data . $cookieHash), 8, $length);
    }

    /**
     * getRequestHash
     * @param null $requestHash
     * @return array|int|null|string
     */
    public static function getRequestHash($requestHash = null)
    {
        if ($requestHash === null) { // encode
            $requestHash = App::val('requestHash');
            if (empty($requestHash)) {
                $sessionId = App::val('sessionId');
                $cookieHash = self::getCookieHash();
                $timestamp = self::getRequestTime();
                $requestHash = substr(md5($cookieHash . $sessionId . $timestamp), 8, 8) . base_convert($timestamp, 10, 36);
                App::val('requestHash', $requestHash);
            }
        } else { // decode
            $sessionId = App::val('sessionId');
            $cookieHash = self::getCookieHash();
            $timestamp = base_convert(substr($requestHash, 8), 36, 10);
            $hashPin = substr(md5($cookieHash . $sessionId . $timestamp), 8, 8);
            if ($hashPin == substr($requestHash, 0, 8)) {
                $requestHash = $timestamp;
            } else {
                $requestHash = 0;
            }
        }

        return $requestHash;
    }

    /**
     * getCaptchaHash
     * @param null $captchaCode
     * @param null $requestHash
     * @return array|null|string
     */
    public static function getCaptchaHash($captchaCode = null, $requestHash = null)
    {
        if (empty($captchaCode)) {
            $captchaHash = App::val('captchaHash');
            if (empty($captchaHash)) {
                if (empty($requestHash)) {
                    $requestHash = self::getRequestHash();
                }
                $cookieHash = self::getCookieHash();
                $captchaHash = substr(md5(strtolower($requestHash) . $cookieHash), 8, 8);
                App::val('captchaHash', $captchaHash);
            }
        } else {
            if (empty($requestHash)) { // 生成hash
                $requestHash = self::getRequestHash();
            }
            $cookieHash = self::getCookieHash();
            $captchaHash = substr(md5(strtolower($captchaCode) . $cookieHash), 8, 8) . $requestHash;
        }

        return $captchaHash;
    }

    // set functions

    /**
     * setCookie
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    public static function setCookie($key, $value = null, $expire = 0)
    {
        $key = App::conf('sys.cookie_pre') . $key;
        $_COOKIE[$key] = $value;

        if (APP_DEBUG && $_SERVER['SERVER_ADDR'] == '0.0.0.0') { // 本地开发环境
            return setcookie($key, $value, $expire ? self::getRequestTime() + $expire : 0);
        }

        return setcookie($key, $value, $expire ? self::getRequestTime() + $expire : 0, App::conf('sys.cookie_path'), App::conf('sys.cookie_domain'));
    }

    // private functions

    /**
     * fetchFromArray
     * @param array $array
     * @param string|null $key
     * @param int|string|null $defaultValue
     * @param bool $trim
     * @param null $allowableTags
     * @return string
     */
    private static function fetchFromArray($array, $key, $defaultValue, $trim = true, $allowableTags = null)
    {
        if (isset($array) && isset($key)) {
            if (isset($array[$key])) {
                $var = $array[$key];
                if (is_string($var)) {
                    if ($allowableTags !== false) {
                        $var = strip_tags($var, $allowableTags);
                    }
                    if ($trim) {
                        $var = trim($var);
                    }
                }
                return $var;
            }
            return $defaultValue;
        }
        return $array;
    }
}