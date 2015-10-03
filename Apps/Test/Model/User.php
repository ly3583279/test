<?php
namespace Apps\Test\Model;
use System\Core\App;

/**
 * Class Controller
 * @package System\Core
 */
class User
{
    function __initSession(){

    }
    static function getInstance(){
        return new User();
    }
} 