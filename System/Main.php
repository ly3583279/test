<?php
if (!defined('APP_NAME')) {
    exit('APP_NAME does not defined.');
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

// 该系统仅在PHP5.5以上运行
if (APP_DEBUG) {
    ini_set('display_errors', 1);
	error_reporting(E_ALL);
    //error_reporting(E_ALL ^ E_NOTICE);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

/*
// 禁止session.auto_start
if (ini_get('session.auto_start') != 0) {
    exit('php.ini session.auto_start must is 0 ! ');
}

// 允许的请求，TRACE & CONNECT
if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD'))) {
    exit('Invalid Request Method.');
}
//*/

// 过滤$_REQUEST和旧版PHP参数，推荐过滤掉$_REQUEST
unset($_REQUEST, $HTTP_ENV_VARS, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_POST_FILES);

// 定义基本变量
// define('ALL', 1);
// define('NONE', 0);
define('VERSION', '6.0.0.1506270');

// 指定内核目录
define('SYS_DIR', realpath(__DIR__));

// 指定系统根路径
define('ABS_ROOT', realpath(SYS_DIR . '/../'));

// 指定App入口
if (!defined('APP_IN')) {
    define('APP_IN', 'Apps');
}

// 指定App目录
if (!defined('APP_DIR_NAME')) {
    define('APP_DIR_NAME', APP_NAME);
}

// 定义App变量
define('APP_ROOT', ABS_ROOT . '/' . APP_IN);
define('APP_DIR', APP_ROOT . '/' . APP_DIR_NAME);
define('APP_NS', str_replace('/', '\\', APP_IN) . '\\' . APP_DIR_NAME);
//echo APP_IN;exit;

// 定义SERVER_ADDR
if (!isset($_SERVER['SERVER_ADDR'])) {
    $_SERVER['SERVER_ADDR'] = '0.0.0.0';
}

// 设置当前时区
//date_default_timezone_set('UTC');
date_default_timezone_set('Etc/GMT-8'); // PRC

/*
// 设定 Multibyte Encoding 默认编码
if( function_exists('mb_internal_encoding') ) {
    mb_internal_encoding('UTF-8');
}
//*/
// 注册自动加载函数
spl_autoload_register(function ($className) {
    $classPath = ABS_ROOT . '/' . str_replace('\\', '/', $className) . '.php';
    //echo $className,'<br/>';
    if (is_file($classPath)) {
        require $classPath;
    } else {
        throw new \Exception($className . ' does not exists.', 404);
    }
});

// Create Apps Instance
//$Apps = new System\Core\Apps();
//$Apps->run();
(new System\Core\App())->run();