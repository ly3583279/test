<?php
namespace Apps\Test\Controller;
use System\Core\Controller;
use System\Core\Request;
use System\Core\App;

/**
 * Class Controller
 * @package System\Core
 */
class Account extends Controller
{
    public function loginMethod()
    {
        if(Request::isAjaxRequest()){
            $str = Request::post('str');
            list($username, $password) = explode(',', $str);
            if($username === 'admin'&&$password == '123'){
                setcookie('username', $username);
                setcookie('password', $password);
                echo 'success';
            }else{
                echo 'false';
            }
            exit;
        }
        $this->view->display();
    }

    public function logoutMethod()
    {
        setcookie("username", "", time()-3600);
        setcookie("password", "", time()-3600);
        header('location:/Apps/index.php?c=Account&m=login');
    }
} 