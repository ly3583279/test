<?php
$appConfig = array(
    'cache' => array(
        'enabled' => false,
    ),
    'session_provider' => 'Apps\Test\Model\User',
    'sys_config_table' => '',
    'base_route_url' => '/Apps/index.php',
    'anonymous_controller' => array(
        'Apps\Test\Controller\Account',
    ),
    'anonymous_method' => array(
        'loginMethod',
        'logoutMethod',
    ),
    'allowed_user_role' => 'Administrator',
    /*'db' => array(
        'diver' => '',
        'servers' => '',
        'master_count' => 0,
        'log_enabled' => true,
    ),*/
);
