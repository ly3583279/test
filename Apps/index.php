<?php
// 必需：系统运行环境
define('APP_DEBUG', true);

// 必需：定义当前模块
define('APP_NAME', 'Test');

// 可选：App入口，默认为'Apps'
define('APP_IN', 'Apps');

// 可选：App目录，默认等于APP_NAME
//define('APP_DIR_NAME', 'Shell');

// 必需：当前运行目录
define('APP_RUN_PATH', __DIR__);

//echo APP_RUN_PATH;exit;
// 加载主程序
require APP_RUN_PATH . '/../System/Main.php';