<?php
namespace System\Core;

use System\Driver;

/**
 * Class Apps: 主调度程序
 * @package System\Core
 */
class App
{
    public $controller;

    private static $dbHandler;
    private static $cacheHandler;
    private static $sessionHandler;

    /**
     * __construct
     */
    public function __construct()
    {
        // 注册系统处理事件
        APP_DEBUG ? set_error_handler(array($this, 'errorHandler')) : null;
        set_exception_handler(array($this, 'exceptionHandler'));
        // register_shutdown_function(array($this, 'shutdownHandler'));

        // self::event('system.startup');

        $appConfig = null;
        $appConfigName = APP_DIR . '/Config/' . (APP_NAME == APP_DIR_NAME ? 'App' : APP_NAME);
        $appConfigFile = $appConfigName . '.' . $_SERVER['SERVER_ADDR'] . '.php';
        if (!file_exists($appConfigFile)) {
            $appConfigFile = $appConfigName . '.php';
        }
        include $appConfigFile;

        // store to conf
        self::conf('app', $appConfig);

        // 初始化Cache
        if (isset($appConfig['cache'])) {
            if (isset($appConfig['cache']['driver'])) {
                self::$cacheHandler = $appConfig['cache']['driver']($appConfig['cache']);
            } else {
                self::$cacheHandler = new Driver\Memcached($appConfig['cache']);
            }
        }

        // 初始化DB
        if (isset($appConfig['db'])) {
            if (isset($appConfig['db']['driver'])) {
                self::$dbHandler = new $appConfig['db']['driver']($appConfig['db'], self::$cacheHandler);
            } else {
                self::$dbHandler = new Driver\MySqli($appConfig['db'], self::$cacheHandler);
            }
        }

        // 加载系统配置
        $this->loadSysConfig($appConfig['sys_config_table']);

		// 初始化Session
        self::$sessionHandler = new Session($appConfig['session_provider']);
        //var_dump(self::$sessionHandler);
        //echo '<hr/>';
    }

    /**
     * run: 系统运行入口
     * @return bool|mixed
     * @throws \Exception
     */
    public function run()
    {
        //var_dump(self::$sessionHandler);
        //echo '<hr/>';
        // 加载Session
        self::session('init');
       // 解析请求
        $controller = $method = $params = null;
        $requestPath = parse_url($this->getRequestUrl(), PHP_URL_PATH);
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        //$arr = $this->route($requestPath, $requestMethod, self::conf('app.base_route_url'));
        extract($this->route($requestPath, $requestMethod, self::conf('app.base_route_url')));

        // 控制器和方法名称
        $controllerName = $controller;
        $methodName = $method . 'Method';

        // 不合法的$method，该判断会降低性能
        if (preg_match("/\W/", $method)) {
            throw new \Exception($method . ' method does not allowed.', 404);
        }

        // 插件入口
        if ($controllerName == 'do') {
            return $this->invokePlugin($method, $params);
        }

        // 不合法的$controllerName，该判断会降低性能
        if (preg_match("/\W/", $controllerName)) {
            throw new \Exception($controllerName . ' controller does not allowed.', 404);
        }

        // 保存控制器和方法名称
        App::val('controllerName', $controllerName);
        App::val('methodName', $method);

        // 构造Controller
        //var_dump(APP_NS . '\\Controller\\' . ucfirst($controllerName));exit;
        //echo '<hr/>';
        $controllerClass = APP_NS . '\\Controller\\' . ucfirst($controllerName);
        $this->controller = new $controllerClass();

        // 判断方法是否存在
        if (!method_exists($this->controller, $methodName)) {
            throw new \Exception($controllerClass . '->' . $methodName . ' does not exists.', 404);
        }

        // 执行
        if (empty($params)) { // 无参调用
            $this->controller->$methodName();
        } else { // 有参调用
            //echo 1;
            call_user_func_array(array($this->controller, $methodName), $params);
        }
    }

    // ----------------------  public static methods  ----------------------

    /**
     * db
     * @param int $serverId
     * @return Driver\MySqli
     */
    public static function db($serverId = -1)
    {
        if (self::$dbHandler !== null) {
            self::$dbHandler->setServerId($serverId);
        }
        return self::$dbHandler;
    }

    /**
     * session
     * @param null $key
     * @return null|Session
     */
    public static function session($key = null)
    {
        //var_dump(self::$sessionHandler);

        if ($key === null) {
            return self::$sessionHandler;
        }

        if (self::$sessionHandler === null) {
            return null;
        }

        if (method_exists(self::$sessionHandler, $key)) {
            //echo 1;
            //echo '<hr/>';
            return self::$sessionHandler->$key();
        } else {
            //echo 2;
            //echo '<hr/>';
            return self::$sessionHandler->$key;
        }
    }

    /**
     * cache
     * @param null $key 当$key为null时，返回$cache实例
     * @param null $value
     * @param int $expires
     * @return bool|mixed|null|Driver\Memcached
     */
    public static function cache($key = null, $value = null, $expires = 0)
    {
        if ($key === null) {
            return self::$cacheHandler;
        }

        if (self::$cacheHandler == null) {
            return null;
        }

        if ($expires == -1) {
            return self::$cacheHandler->del($key);
        }

        if ($value === null) {
            return self::$cacheHandler->get($key);
        } else {
            return self::$cacheHandler->set($key, $value, $expires);
        }
    }

    /**
     * 获取配置项
     * @param string $key app.name or sys.title
     * @param null $defaultValue
     * @return null
     */
    public static function conf($key, $defaultValue = null)
    {
        static $_confStore = array();
        if ($key === null) {
            return $_confStore;
        }

        if (strpos($key, '.') === false) {
            if ($defaultValue === null) {
                return $_confStore[$key];
            } else {
                $_confStore[$key] = $defaultValue;
            }
        } else {
            list($key, $name) = explode('.', $key, 2);
            if (array_key_exists($key, $_confStore) && is_array($_confStore[$key]) && array_key_exists($name, $_confStore[$key])) {
                return $_confStore[$key][$name];
            }
        }

        return $defaultValue;
    }

    /**
     * 获取临时存储值，取代$GLOBALS
     * @param null $key
     * @param null $value
     * @return array|null
     */
    public static function val($key = null, $value = null)
    {
        static $_valStore = array();
        /*echo '<pre/>';
        echo '<hr/>';
        print_r($_valStore);*/

        // get all values
        if ($key === null) {
            return $_valStore;
        }

        // get/set item
        if ($value === null) { // get item
            if (isset($_valStore[$key])) {
                return $_valStore[$key];
            }
            return null;
        } else { // set item
            if (is_array($key)) {
                $_valStore += $key;
            } else {
                $_valStore[$key] = $value;
            }
        }
    }

    /**
     * 系统事件入口
     * @param $event
     * @param null $value
     * @param null $callback
     * @return mixed|null
     */
    public static function event($event, $value = null, $callback = null)
    {
        static $_eventStore;

        if ($callback === null) { // 调用Event
            if (isset($_eventStore[$event])) {
                foreach ($_eventStore[$event] as $function) {
                    $value = call_user_func_array($function, is_array($value) ? $value : array($value));
                }
            }
            return $value;
        } else { // 添加或删除一个事件
            if ($callback) {
                $_eventStore[$event][] = $callback;
            } else {
                unset($_eventStore[$event]);
            }
        }
    }

    // ----------------------  private functions  ----------------------

    /**
     * loadSysConfig
     * @param string $configTableName
     */
    private function loadSysConfig($configTableName = 'sys_config')
    {
        $configFile = APP_DIR . '/Config/Sys.php';
        $sysConfig = array();
        if (file_exists($configFile)) {
            include $configFile;
        } else {
            $params = array('indexField' => 'name', 'valueField' => 'value', 'rowCount' => 1000);
            $sysConfig = self::db()->getScalars($configTableName, null, $params, 86400);
        }

        self::conf('sys', $sysConfig);
    }

    /**
     * getRequestUrl
     * @return string
     */
    private function getRequestUrl()
    {
        if (Request::isCliRequest()) {
            $argc = intval($_SERVER['argc']) - 1;
            $argv = $_SERVER['argv'];
            $delimiter = '/';
            $requestUrl = $delimiter;
            $isUrl = false;
            foreach ($argv as $i => $arg) {
                if ($i == 0) {
                    continue;
                }

                if ($arg == '-u') {
                    $isUrl = true;
                    continue;
                }

                if ($arg == '-d') {
                    $delimiter = '&';
                } else {
                    $requestUrl .= ($delimiter == '/') ? ($isUrl ? urlencode($arg) : $arg) : str_replace('&', '%26', $arg);
                }

                if ($i < $argc) {
                    $requestUrl .= ($arg == '-d') ? '?' : $delimiter;
                }
            }

            parse_str(parse_url($requestUrl, PHP_URL_QUERY), $query);

            if (!empty($query)) {
                $_GET += $query;
            }
        } else {
           $requestUrl = $_SERVER['REQUEST_URI'];
        }

        return $requestUrl;
    }

    /**
     * route
     * @param $path
     * @param string $requestMethod
     * @param string $baseRoutePath
     * @return array
     */
    private function route($path, $requestMethod = 'GET', $baseRoutePath = '')
    {
        $path = preg_replace('"/+"', '/', $path);
        $baseRoutePath = rtrim($baseRoutePath, '/');
        $baseRouteLength = (empty($baseRoutePath) || $baseRoutePath == '/') ? 0 : strlen($baseRoutePath);
        if ($baseRouteLength > 0) {
            if ($path == $baseRoutePath) {
                $path = '';
            } elseif (strlen($path) > $baseRouteLength) {
                $pathStart = substr($path, 0, $baseRouteLength);
                if ($pathStart == $baseRoutePath && $path[$baseRouteLength] == '/') { //substr($path, $baseRouteLength, 1) == '/') {
                    $path = substr($path, $baseRouteLength);
                }
            }
        }
        $path = trim($path, '/');

       if (empty($path) || file_exists(APP_RUN_PATH . '/' . $path)) {
            $x = array(isset($_GET['c']) ? trim(strip_tags($_GET['c'])) : 'main');
        } elseif ($path == 'do' || $path == 'load') {
            $x = array(isset($_GET['c']) ? trim(strip_tags($_GET['c'])) : 'do');
        } else {
            $routes = null;

            include APP_DIR . '/Config/Routes.php';

            // route match
            foreach ($routes as $key => $val) {
                if (strpos($key, '(') === false) { //不带正则的映射
                    if ($key == $path) {
                        if (is_array($val)) {
                            $path = isset($val[$requestMethod]) ? $val[$requestMethod] : '';
                        } else {
                            $path = $val;
                        }
                        break;
                    }
                } else { // 正则映射
                    $key = str_replace(array(':num', ':any'), array('\d+', '.+'), $key);
                    if (preg_match('#^' . $key . '$#', $path)) {
                        if (is_array($val)) {
                            $path = isset($val[$requestMethod]) ? preg_replace('#^' . $key . '$#', $val[$requestMethod], $path) : '';
                        } else {
                            $path = preg_replace('#^' . $key . '$#', $val, $path);
                        }
                        break;
                    }
                }
            }

            // parse route result
            if (strpos($path, '?') !== false) {
                $urls = parse_url($path);
                $path = $urls['path'];
                if (!empty($urls['query'])) {
                    $queries = array();
                    parse_str($urls['query'], $queries);
                    $_GET += $queries;
                }
            }

            $x = explode('/', $path);
        }

        $c = count($x);
        $_x = array();
        $_x['controller'] = $c > 0 && !empty($x[0]) ? trim($x[0]) : 'main';
        $_x['method'] = $c > 1 && !empty($x[1]) ? trim($x[1]) : (isset($_GET['m']) ? trim(strip_tags($_GET['m'])) : 'index');
        $_x['params'] = $c > 2 ? array_slice($x, 2) : (isset($_GET['v']) ? array($_GET['v']) : null);

        //print_r($_x); print_r($_GET); exit;
        //print_r($_x);
        return $_x;
    }

    /**
     * invokePlugin
     * @param $method
     * @param null $params
     * @return bool|mixed
     */
    private function invokePlugin($method, $params = null)
    {
        $className = $method;
        if (empty($className)) {
            $className = 'Dashboard';
        }

        $handlerClass = 'System\\Plugin\\' . ucfirst($className);
        $methodName = 'load';

        $handler = new $handlerClass;
        if (!method_exists($handler, $methodName)) {
            return false;
        }

        // 执行
        if (empty($params)) {
            return $handler->$methodName();
        } else { // 有参调用
            return call_user_func_array(array($handler, $methodName), $params);
        }
    }

    // ----------------------  system internal handler  ----------------------

    /**
     * messageHandler
     * @param $message
     * @param string $forwardUrl
     * @param int $forwardSecond
     * @param string $messageTemplate
     * @param int $messageCode
     */
    public function messageHandler($message, $forwardUrl = '', $forwardSecond = 2, $messageTemplate = 'show_message', $messageCode = 200)
    {
        $handler = $this->controller;
        if (!$handler) {
            $handler = new Controller();
        }

        $handler->showMessage($message, $forwardUrl, $forwardSecond, $messageTemplate, $messageCode);
    }

    /**
     * exceptionHandler
     * @param $exception
     */
    public function exceptionHandler($exception)
    {
        $message = $exception->getMessage();
        if (!empty($message) && $message[0] == '{') { //substr($message, 0, 1) == '{') {
            $messageArr = json_decode($message, true);
        }

        if (empty($messageArr)) {
            $messageArr = array('message' => $message);
        }

        $message = $messageArr['message'];
        $forwardUrl = isset($messageArr['forwardUrl']) ? $messageArr['forwardUrl'] : '';
        $forwardSecond = isset($messageArr['forwardSecond']) ? $messageArr['forwardSecond'] : 0;
        $messageTemplate = isset($messageArr['messageTemplate']) ? $messageArr['messageTemplate'] : 'show_exception';

        $this->messageHandler($message, $forwardUrl, $forwardSecond, $messageTemplate, $exception->getCode());
    }

    /**
     * errorHandler
     * @param $errorNo
     * @param $errorStr
     * @param $errorFile
     * @param $errorLine
     * @return bool
     * @throws \Exception
     */
    public function errorHandler($errorNo, $errorStr, $errorFile, $errorLine)
    {
        if (!(error_reporting() & $errorNo)) {
            return false;
        }

        $message = sprintf("[%s] (%s: %s) %s <br />\r\n", $errorNo, $errorFile, $errorLine, $errorStr);

        if ($errorNo == E_USER_ERROR || $errorNo == E_ERROR) {
            throw new \Exception($message, 500);
        } else {
            echo $message;
        }

        return true;
    }

    /**
     * shutdownHandler
     */
    public function shutdownHandler()
    {
        // shutdown handler
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        // print_r($_COOKIE);
        if (isset(self::$dbHandler)) {
            self::$dbHandler->close();
        }

        // self::event('system.shutdown');
    }
} 
