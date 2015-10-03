<?php
namespace System\Core;

use System\Util\TextUtils;

/**
 * Class Controller
 * @package System\Core
 */
class Controller
{
    public $view;

    protected $controllerName;
    protected $methodName;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->view = new View(App::session('sessionData'));

        $this->controllerName = App::val('controllerName');
        $this->methodName = App::val('methodName');

        $this->view->addData('appName', APP_NAME);
        $this->view->addData('controllerName', $this->controllerName);
        $this->view->addData('methodName', $this->methodName);
        $this->view->setViewName(strtolower($this->controllerName . '_' . $this->methodName));

        // 验证用户身份
        $allowedUserRole = App::conf('app.allowed_user_role');
        //echo $allowedUserRole;exit;
        if (empty($allowedUserRole)) {
            return;
        }

        // 忽略特定的Controller
        $className = get_class($this);
        //echo $className;
        if (in_array($className, App::conf('app.anonymous_controller'))) {
            return;
        }

        if (!App::session('is' . $allowedUserRole)) {
            if (App::session('isLogin')) {
                $this->showError('你没有访问权限！');
            } else {
                if(isset($_COOKIE['username']) && isset($_COOKIE['password'])) return;
                $this->showMessage('你还没有登录，请先登录！', '/Apps/index.php?c=Account&m=login');
            }
        }
    }

    /**
     * show404
     * @param string $message
     */
    public function show404($message = '出错啦！你访问的页面不存在！')
    {
        $this->showMessage($message, '/', 9, 'show_message', 404);
    }

    /**
     * showError
     * @param string $message
     * @param int $messageCode
     */
    public function showError($message = '出错啦！你访问的页面不存在！', $messageCode = 400)
    {
        $this->showMessage($message, '/', 9, 'show_message', $messageCode);
    }

    /**
     * 跳转到新页面：302 临时重定向
     * @param string $forwardUrl
     */
    public function showForward($forwardUrl)
    {
        $this->showMessage(null, $forwardUrl, 0, null, 302);
    }

    /**
     * 跳转到新页面：301 永久重定向
     * @param string $redirectUrl
     */
    public function showRedirect($redirectUrl)
    {
        $this->showMessage(null, $redirectUrl, 0, null, 301);
    }

    /**
     * showMessage
     * @param string $message "title;message"
     * @param string $forwardUrl "title;url"
     * @param int|number $forwardSecond 跳转时间，0：直接跳转，-1：不自动跳转；>0：跳转间隔时间
     * @param string $messageTemplate 默认为'show_message'
     * @param int|number $messageCode 默认为'200'
     */
    public function showMessage($message, $forwardUrl = null, $forwardSecond = 2, $messageTemplate = 'show_message', $messageCode = 200)
    {
        //防决定路径中：被TextUtils::explodeKeyValue（）处理掉
        //if($forwardUrl == APP_RUN_PATH .'\index.php?c=Main&m=login') $forwardLoginUrl = $forwardUrl;

        // 防钓鱼
        if (!empty($forwardUrl) && strtolower(substr($forwardUrl, 0, 11)) == 'javascript:') {
            $forwardUrl = '';
        }
        if (!empty($forwardUrl) && empty($forwardSecond)) { // 直接跳转
            header('Location: ' . $forwardUrl, true, $messageCode);
            exit(1);
        }

        list($title, $message) = TextUtils::explodeKeyValue($message, ':', '提示');
        $pageTitle = $title;

        if ($messageCode != 200) {
            $httpStatusCodes = array(400 => 'Bad request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method not allowed', 500 => 'Internal server error');

            $httpServerProtocol = $_SERVER['SERVER_PROTOCOL'];
            if (empty($httpServerProtocol)) {
                $httpServerProtocol = 'HTTP/1.1';
            }

            $httpStatusCode = $messageCode;
            if (!array_key_exists($httpStatusCode, $httpStatusCodes)) {
                $httpStatusCode = 404;
            }

            $messageCodeStatus = ' ' . $httpStatusCode . ' ' . $httpStatusCodes[$httpStatusCode];

            // send header
            if (Request::isCliRequest()) {
                // do nothing ..
            } elseif (Request::isAjaxRequest()) {
                header($httpServerProtocol . ' 200 OK', true, 200);
                header('Status: 200 OK', true, 200);
            } else {
                header($httpServerProtocol . $messageCodeStatus, true, $httpStatusCode);
                header('Status:' . $messageCodeStatus, true, $httpStatusCode);
            }

            if ($httpStatusCode == 404) {
                $title = '404';
                $pageTitle = '出错啦';
                if (empty($message) || (defined('APP_DEBUG') && !APP_DEBUG)) {
                    $message = '出错啦！你访问的资源不存在或已删除！';
                }
            }
        }

        $forwardMessage = $message;
        $forwardLink = '';
        $forwardScript = '';

        if (!empty($forwardUrl)) {
           list($forwardTitle, $forwardUrl) = TextUtils::explodeKeyValue($forwardUrl);
            $forwardLink = '<a href="' . $forwardUrl . '">稍后转入 ' . (empty($forwardTitle) ? $forwardUrl : $forwardTitle) . ' ..</a>';
            //防决定路径中：被TextUtils::explodeKeyValue（）处理掉,与104行对应
            //$forwardUrl = isset($forwardLoginUrl) ? $forwardLoginUrl : $forwardLoginUrl;
            $forwardScript = $forwardSecond > 0 ? '<script>setTimeout("window.location.href=\'' . $forwardUrl . '\';",' . ($forwardSecond * 1000) . ');</script>' : '';
        }

        $this->view->addDataSpecialChars('status', $messageCode);
        $this->view->addDataSpecialChars('message', $message);
        $this->view->addDataSpecialChars('messageCode', $messageCode);
        $this->view->addDataSpecialChars('forwardMessage', $forwardMessage);
        $this->view->addData('forwardUrl', $forwardUrl);
        $this->view->addData('forwardSecond', $forwardSecond);
        $this->view->addData('forwardLink', $forwardLink);
        $this->view->addData('forwardScript', $forwardScript);

        $this->view->setTitle($title);
        $this->view->setPageTitle($pageTitle, true);

        $this->view->display($messageTemplate);

        exit(1);
    }
} 