<?php
namespace System\Core;

use System\Util\DateUtils;
use System\Util\TextUtils;

/**
 * Class Session
 * @package System\Core
 */
class Session
{
    public $provider;

    // user global data
    public $uid = 0;
    public $username = null;
    public $status = 0;

    // user extra data
    public $adminType = 0;
    public $openIdType = null;

    // user session data
    public $userData = null;
    public $userGroup = null;

    // session data
    public $sessionData;

    private $providerClass;
    // private $adminTypeNames = array('-', '管理员', '主编', '高级编辑', '编辑', '实习编辑');

    /**
     * __construct
     * @param null $providerClass
     */
    public function __construct($providerClass = null)
    {
        $this->providerClass = $providerClass;
        $this->sessionId = $this->getSessionId();
        $this->setSessionData(null, null);
    }

    /**
     * init: 警告：该函数仅由系统自动调用，请勿手工调用
     */
    public function init()
    {
       $providerClass = $this->providerClass;
        //var_dump($providerClass);
        //echo 11;

        if (empty($providerClass)) {
            return;
        }
        //echo 22;
        //new Apps\Test\Model\User();

        /*echo '<hr/>';
        var_dump($providerClass);
        echo 33;
        var_dump(method_exists($providerClass, 'getInstance'));*/
        if (method_exists($providerClass, 'getInstance')) {
            //echo 444;
            $this->provider = $providerClass::getInstance();
        } else {
            $this->provider = new $providerClass;
        }

        if (empty($this->provider)) {
            return;
        }

        if (method_exists($this->provider, '__initSession')) {
            $this->provider->__initSession();
        }
    }

    /**
     * getProvider
     * @return mixed
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * getSessionId
     * @return mixed|null|string
     */
    public function getSessionId()
    {
        $sessionId = App::val('sessionId');

        // 当启用匿名Session时，Session无需写入Cookie
        if (App::conf('app.anonymous_session', false)) {
            return uniqid('SESSID');
        }

        if (empty($sessionId)) {
            $sessionId = Request::cookie('SESSID');
            $requestTime = Request::getRequestTime(true);
            if (!empty($sessionId)) { // 判断是否过期
                $terms = explode('_', $sessionId);
                if (count($terms) < 5 || $requestTime - $terms[3] > 2592000) { // 86400*30
                    $sessionId = '';
                }
            }

            if (empty($sessionId)) { // 生成SessionID
                $sessionHash = substr(md5(Request::getHttpClientIp() . Request::getHttpUserAgent()), 8, 8);
                $sessionId = str_replace('.', '_', uniqid($sessionHash . '_', true) . '_' . $requestTime);
                Request::setCookie('SESSID', $sessionId, 0);
            }

            App::val('sessionId', $sessionId);
        }

        return $sessionId;
    }

    /**
     * getSessionData
     * @return array
     */
    public function getSessionData()
    {
        return $this->sessionData;
    }

    /**
     * setSessionData
     * @param array $userData
     * @param array $userGroup
     * @param string $openIdType null/web/weibo/qq
     */
    public function setSessionData($userData, $userGroup, $openIdType = null)
    {
        if (empty($userData)) {
            $userData = array('uid' => 0, 'username' => null, 'status' => 0);
        }

        if (empty($userGroup)) {
            $userGroup = array('groupid' => 0, 'admintype' => 0, 'groupname' => null);
        }

        $this->uid = $userData['uid'];
        $this->username = $userData['username'];
        $this->status = $userData['status'];

        $this->userData = $userData;
        $this->userGroup = $userGroup;
        $this->openIdType = $openIdType;

        $this->adminType = $userGroup['admintype'];

        $sessionData = array(
            'uid' => $this->uid,
            'username' => $this->username,
            'status' => $this->status,
            'adminType' => $this->adminType,
            'openIdType' => $this->openIdType,
            'groupId' => $userGroup['groupid'],
            'groupName' => $userGroup['groupname'],
            'isLogin' => $this->isLogin(),
            'isMember' => $this->isMember(),
            'isAdministrator' => $this->isAdministrator(),
            'isSuperAdministrator' => $this->isSuperAdministrator(),
            'isTester' => $this->isTester()
        );

        $this->sessionData = $sessionData;
    }

    /**
     * getLastPostInterval
     * @return int
     */
    public function getLastPostInterval()
    {
        $lastPostInterval = App::cache('lastPostInterval:uid.' . $this->uid);
        return empty($lastPostInterval) ? 0 : $lastPostInterval;
    }

    /**
     * updateLastPostInterval
     * @param int $time
     */
    public function updateLastPostInterval($time = 0)
    {
        App::cache('lastPostInterval:uid.' . $this->uid, empty($time) ? Request::getRequestTime() : $time, 86400);
    }

    // user functions

    /**
     * isLogin: 判断用户是否登录
     * @return boolean
     */
    public function isLogin()
    {
        //echo '111<br/>';
        return $this->provider && ($this->uid > 0);
    }

    /**
     * isMember
     * @return bool
     */
    public function isMember()
    {
        return $this->isLogin();
    }

    /**
     * isAdministrator
     * @return boolean
     */
    public function isAdministrator()
    {
        return $this->isLogin() && ($this->adminType > 0);
    }

    /**
     * isSuperAdministrator
     * @return boolean
     */
    public function isSuperAdministrator()
    {
        return $this->isLogin() && ($this->uid == App::conf('sys.master'));
    }

    /**
     * isTester: 判断是否位测试人员
     * @return boolean
     */
    public function isTester()
    {
        if (!$this->isLogin()) {
            return false;
        }

        $allowUser = App::conf('sys.allow_user');
        if (empty($allowUser)) {
            return false;
        }

        return in_array($this->uid, explode(',', $allowUser));
    }

    // check functions

    /**
     * checkLogin
     * @param string $forward
     * @param bool $return
     * @param string $template
     * @param string $message
     * @return bool
     * @throws \Exception
     */
    public function checkLogin($forward = '', $return = false, $template = 'account_login', $message = '你还没有登录，请先登录！')
    {
        if ($this->isLogin()) {
            return true;
        }

        if ($return) {
            return false;
        } else {
            if (empty($forward)) {
                throw new \Exception($message, 401);
            } elseif (Request::isAjaxRequest()) {
                //$responseScript = '$("body").exRequest({requestUrl:"/account/login?requestForward=' . urlencode($forward) . '"});';
                //$this->View->setResponseScript($responseScript);
                $exceptionMessage = array('url' => App::conf('app.login_url') . '?forward=' . urlencode($forward), 'message' => $message, 'template' => $template);
                throw new \Exception(json_encode($exceptionMessage, JSON_HEX_QUOT), 401);
            } else {
                $exceptionMessage = array('url' => App::conf('app.login_url') . '?forward=' . urlencode($forward), 'message' => $message, 'template' => $template);
                throw new \Exception(json_encode($exceptionMessage, JSON_HEX_QUOT), 401);
            }
        }
    }

    /**
     * checkBadWords: 检测关键词屏蔽
     * @param $str
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkBadWords($str, $return = false, $exceptionMessage = '和谐社会，文明上网！请检查你输入的内容！')
    {
        if (empty($str)) {
            return false;
        }

        $badWordsRegEx = $this->getBadWordsRegEx();
        if (empty($badWordsRegEx)) {
            return false;
        }

        $newStr = $str;
        $newStr .= preg_replace('/\s+/', '', html_entity_decode($str));
        $newStr .= preg_replace('/\s+/', '', html_entity_decode(html_entity_decode($str)));

        if (preg_match($badWordsRegEx, $newStr, $matches)) {
            if ($return) {
                return $matches[0];
            } else {
                throw new \Exception($exceptionMessage, 403);
            }
        } else {
            return false;
        }
    }

    /**
     * checkIpBanned
     * @param null $ip
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkIpBanned($ip = null, $return = false, $exceptionMessage = '对不起，你的IP已禁止访问，请咨询管理员！')
    {
        $ipBannedRegEx = $this->getIpBannedRegEx();
        if (empty($ipBannedRegEx)) {
            return false;
        }

        if ($ip == null) {
            $ip = Request::getHttpClientIp();
        }

        if (preg_match($ipBannedRegEx, $ip)) {
            if ($return) {
                return true;
            } else {
                throw new \Exception($exceptionMessage, 403);
            }
        }

        return false;
    }

    /**
     * checkDisallowPost: 检测用户所在组是否有操作权限
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkDisallowPost($return = false, $exceptionMessage = '对不起，你没有发布权限，请咨询管理员！')
    {
        $disallowPost = $this->userGroup['disallow_post'];
        if ($disallowPost) {
            if ($return) {
                return true;
            } else {
                throw new \Exception($exceptionMessage, 403);
            }
        } else {
            return false;
        }
    }

    /**
     * checkPostInterval: 检测用户操作间隔
     * @param int $defaultPostInterval
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkPostInterval($defaultPostInterval = 5, $return = false, $exceptionMessage = '对不起，你的操作太快了，请稍后再试！')
    {
        $userPostInterval = $this->userGroup['post_interval'];
        $postInterval = $userPostInterval > $defaultPostInterval ? $userPostInterval : $defaultPostInterval;

        $timestamp = Request::getRequestTime();
        $lastPostInterval = $this->getLastPostInterval();
        if (($timestamp - $lastPostInterval) > $postInterval) {
            return false;
        } else { // 用户操作间隔太短
            if ($return) {
                return true;
            } else {
                throw new \Exception($exceptionMessage, 403);
            }
        }
    }

    /**
     * checkNewbieLimit
     * @param int $defaultLimitTime
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkNewbieLimit($defaultLimitTime = 0, $return = false, $exceptionMessage = '对不起，你还是新手，暂时没有权限进行该操作！')
    {
        $uid = $this->uid;
        if ($uid > 355000 && $this->status == 0) {
            if ($return) {
                return true;
            } else {
                $validateLink = App::conf('sys.base_url') . '/account/validate?uid=' . $uid . '&code=null';
                $exceptionMessage = '你的帐号还没激活，请先激活：<a href="' . $validateLink . '">' . $validateLink . '</a>';
                throw new \Exception($exceptionMessage, 403);
            }
        }

        // 检测新用户限制
        if (empty($defaultLimitTime)) {
            $defaultLimitTime = App::conf('sys.user_newbie_limit');
        }

        // 对白天注册的用户不限制
        $theHour = DateUtils::format(0, '-G');
        if ($theHour > 7 && $theHour < 23) {
            if ($defaultLimitTime > 900) {
                $defaultLimitTime = 900;
            }
        }

        if ($defaultLimitTime <= 0) {
            return false;
        }

        $timestamp = Request::getRequestTime();
        $regDate = $this->userData['regdate'];
        if ($regDate > 0) {
            if ($timestamp - $regDate < $defaultLimitTime) {
                if ($return) {
                    return true;
                } else {
                    throw new \Exception($exceptionMessage, 403);
                }
            }
        }

        return false;
    }

    /**
     * checkCaptchaCode
     * @param $captchaCode
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkCaptchaCode($captchaCode, $return = false, $exceptionMessage = '对不起，请输入正确的验证码！')
    {
        $validityState = true;

        if (strlen($captchaCode) == 8) { // 内置验证
            $validityState = ($captchaCode == Request::getCaptchaHash());
        } else {
            $captchaHash = Request::cookie('captcha');
            if (strlen($captchaHash) < 16) {
                $validityState = false;
            }

            if ($validityState) {
                $requestHash = substr($captchaHash, 8);
                if ($captchaHash == Request::getCaptchaHash($captchaCode, $requestHash)) {
                    $now = Request::getRequestTime();
                    if (empty($requestHash)) {
                        $requestTime = 0;
                    } else {
                        $requestTime = Request::getRequestHash($requestHash);
                    }

                    $validityState = (($now - $requestTime) < 3600); // 1个小时的有效期
                } else {
                    $validityState = false;
                }
            }
        }

        if ($validityState) {
            return true;
        } else {
            if ($return) {
                return false;
            } else {
                throw new \Exception($exceptionMessage, 403);
            }
        }
    }

    /**
     * checkPrivilege
     * @param $privilege
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkPrivilege($privilege, $return = false, $exceptionMessage = '对不起，你没有权限进行该操作！')
    {
        if ($this->isSuperAdministrator()) {
            return true;
        }

        // 判断权限
        $privilegeRules = $this->userGroup['privilege_rules'];
        $privilegeArr = empty($privilegeRules) ? array() : explode(',', $privilegeRules);
        $checkPrivilegeValue = !empty($privilege) && ($this->status >= 0) && in_array($privilege, $privilegeArr);

        if ($checkPrivilegeValue) {
            return true;
        }

        if ($return) {
            return false;
        } else {
            throw new \Exception($exceptionMessage, 403); // 没有权限
        }
    }

    /**
     * checkRequest
     * @param string $message
     * @param bool $return
     * @param string $exceptionMessage
     * @return int
     * @throws \Exception
     */
    public function checkRequest($message = '', $return = false, $exceptionMessage = '对不起，你没有权限进行该操作！')
    {
        $message = trim($message);

        $requestState = 0;

        if ($this->checkIpBanned(null, $return)) {
            $requestState = -2;
        } elseif ($this->checkDisallowPost($return)) {
            $requestState = -3;
        } elseif ($this->checkNewbieLimit(86400, $return)) {
            $requestState = -4;
        } elseif ($this->checkPostInterval(5, $return)) {
            $requestState = -5;
        } elseif (mb_strlen($message, 'UTF-8') < 2) {
            $requestState = -6;
        } elseif (in_array($message, ['asdf', 'fasd', 'sdf', 'test', 'haha', '呵呵', '哈哈', '测试', '评论', '沙发', '测试一下', '测试评论'])) {
            $requestState = -7;
        } elseif ($this->checkBadWords($message, $return)) {
            $requestState = -9;
        }

        /*
        if( empty($requestState) ) {
            $lastRequestSignature = cookie_item('mask');
            $stringHelper = new \System\Helper\StringHelper();
            $requestSignature = $stringHelper->getTextSignature($message);
            if( $requestSignature==$lastRequestSignature ) {
                $requestState = -8;
            } else {
                global_item('requestSignature', $requestSignature);
            }
        }
        */

        if (empty($requestState)) {
            return 0;
        }

        if ($return) {
            return $requestState;
        } else {
            $messages = array('0' => '提交失败，请稍后再试', '-1' => '对象不存在', '-2' => '你的IP已禁止访问', '-3' => '你没有发布权限', '-4' => '你没有该操作权限', '-5' => '你的操作太快了', '-6' => '输入内容太少啦', '-7' => '说点其他的吧', '-8' => '老发重复内容多没意思哦', '-9' => '和谐社会，文明上网！请检查你输入的内容');

            if (array_key_exists($requestState, $messages)) {
                $exceptionMessage = $messages[$requestState];
            }

            throw new \Exception($exceptionMessage, 403);
        }
    }

    /**
     * checkHttpAuthenticate
     * @param string $type
     * @param string $realm
     * @param bool $return
     * @param string $exceptionMessage
     * @return bool
     * @throws \Exception
     */
    public function checkHttpAuthenticate($type = 'Basic', $realm = 'WWW-Authenticate', $return = false, $exceptionMessage = 'Error: Unauthorized.')
    {
        if ($type == 'Basic') {
            if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
                $authorized = false;
            } else {
                if ($_SERVER['PHP_AUTH_USER'] == App::conf('app.WWW_AUTH_USER') && $_SERVER['PHP_AUTH_PW'] == App::conf('app.WWW_AUTH_PW')) {
                    $authorized = true;
                } else {
                    $authorized = false;
                }
            }
        } elseif ($type == 'Digest') {
            if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
                $authorized = false;
            } else {
                // $data = array();
                // parse_str(str_replace(array(', ', ','), '&', $_SERVER['PHP_AUTH_DIGEST']), $data);
                $data = TextUtils::parseString($_SERVER['PHP_AUTH_DIGEST'], ', ');
                if (empty($data)) {
                    $authorized = false;
                } else {
                    foreach ($data as $k => $v) {
                        $data[$k] = trim($v, '\'" ');
                    }
                    $A1 = md5(App::conf('app.WWW_AUTH_USER') . ':' . $realm . ':' . App::conf('app.WWW_AUTH_PW'));
                    $A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);
                    $validResponse = md5($A1 . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);
                    if ($data['response'] == $validResponse) {
                        $authorized = true;
                    } else {
                        $authorized = false;
                    }
                }
            }
        } else {
            $authorized = false;
        }

        if ($authorized) {
            return true;
        } else {
            header('WWW-Authenticate: ' . $type . ' realm="' . $realm . '"' . ($type == 'Digest' ? ', qop="auth", nonce="' . uniqid() . '", opaque="' . md5($realm) . '"' : ''));
            header('HTTP/1.1 401 Unauthorized');
            if ($return) {
                return false;
            } else {
                throw new \Exception($exceptionMessage, 401);
            }
        }
    }


    // private functions

    /**
     * getBadWordsRegEx
     * @return string
     */
    private function getBadWordsRegEx()
    {
        $regEx = App::cache('system:badWordsRegEx');
        if (empty($regEx)) {
            $tableName = App::conf('app.sys_bad_words_table', 'sys_bad_words');
            $now = Request::getRequestTime();

            App::db()->delete($tableName, 'expires<=' . $now);

            $rows = App::db()->getRows($tableName, null, array('fields' => 'word', 'rowCount' => 10000));
            if (empty($rows)) {
                $rows[] = array('word' => '操你妈');
                $rows[] = array('word' => '法轮功');
                $rows[] = array('word' => '法轮大法');
            }

            $regEx = '';
            if (!empty($rows)) {
                $separator = '';
                foreach ($rows as $row) {
                    $regEx .= $separator . $row['word'];
                    $separator = '|';
                }
                $regEx = '/' . str_replace(array('.', '||', '*'), array('\.', '|', '.{0,3}'), $regEx) . '|&#.{0,5};/iu';
            }

            App::cache('system:badWordsRegEx', $regEx);
        }

        return $regEx;
    }

    /**
     * getIpBannedRegEx
     * @return string
     */
    private function getIpBannedRegEx()
    {
        $regEx = App::cache('system:ipBannedRegEx');
        if (empty($regEx)) {
            $tableName = App::conf('app.sys_ip_banned_table', 'sys_ip_banned');
            $now = Request::getRequestTime();

            App::db()->delete($tableName, 'expires<=' . $now);

            $rows = App::db()->getRows($tableName, null, array('fields' => 'ip', 'rowCount' => 10000));
            if (empty($rows)) {
                $rows[] = array('ip' => '255.255.255.255');
            }

            $regEx = '';
            $separator = '';
            foreach ($rows as $row) {
                $regEx .= $separator . str_replace(array('*', '.'), array('\w+', '\\.'), $row['ip']);
                $separator = '|';
            }

            $regEx = '/^(' . $regEx . ')$/';

            App::cache('system:ipBannedRegEx', $regEx);
        }

        return $regEx;
    }

    // magic methods

    /**
     * __call
     * @param $name
     * @param null $arguments
     * @return mixed|null
     */
    public function __call($name, $arguments = null)
    {
        $methodName = trim($name);

        if (!$this->provider) {
            return null;
        }

        // 判断方法是否存在
        if (!method_exists($this->provider, $methodName)) {
            return null;
        }

        // 执行
        if (empty($arguments)) { // 无参调用
            return $this->provider->$methodName();
        } else { // 有参调用
            return call_user_func_array(array($this->provider, $methodName), $arguments);
        }
    }

    /**
     * __get
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        if (is_array($this->userData) && array_key_exists($name, $this->userData)) {
            return $this->userData[$name];
        }

        return null;
    }

    /**
     * __set
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->userData[$name] = $value;
    }

    /**
     * __isset
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->userData[$name]);
    }

    /**
     * __unset
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->userData[$name]);
    }
} 