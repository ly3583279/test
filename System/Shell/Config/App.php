<?php
$appConfig = array();

// APP参数
$appConfig['name'] = 'FastPHP Shell';
$appConfig['version'] = '6.0.0.1509100';

// APP信息
$appConfig['url'] = '/';
$appConfig['base_url'] = '';
$appConfig['base_route_url'] = '/';
$appConfig['index_url'] = '/';
$appConfig['login_url'] = '/account/login';

// 系统设置
$appConfig['theme'] = '';
$appConfig['allowed_user_role'] = ''; // Member/Tester/Administrator/SuperAdministrator
$appConfig['anonymous_controller'] = ['Apps\Api\Controller\Account'];
$appConfig['session_provider'] = ''; //'Apps\Shared\Model\Member'; //'Member';
$appConfig['sys_config_table'] = 'sys_config';
$appConfig['sys_ip_banned_table'] = 'sys_ip_banned';
$appConfig['sys_bad_words_table'] = 'sys_bad_words';

/*
// WWW-Authenticate认证
$appConfig['WWW_AUTH_USER'] = 'Shell';
$appConfig['WWW_AUTH_PW'] = 'ea89b68c34ce4a63c0f77e17413c6e30';

// 数据库配置，servers支持读写分离，0默认为读写服务器(Master)，1之后的为读服务器(Slave)
$appConfig['db'] = array();
$appConfig['db']['driver'] = 'MySqli';
$appConfig['db']['master_count'] = 1;
$appConfig['db']['log_enabled'] = true;
$appConfig['db']['servers'] = array(array('host' => 'localhost', 'user' => 'user', 'password' => 'password', 'dbname' => 'dbname', 'charset' => 'UTF-8', 'table_pre' => 'tb_'));

// Cache配置
$appConfig['cache'] = array();
$appConfig['cache']['driver'] = 'Memcached';
$appConfig['cache']['enabled'] = true;
$appConfig['cache']['persistent_id'] = null;
$appConfig['cache']['servers'] = array(array('192.168.111.11', 11211, 100));
//*/