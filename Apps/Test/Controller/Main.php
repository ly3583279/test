<?php
namespace Apps\Test\Controller;
use System\Core\Controller;

/**
 * Class Controller
 * @package System\Core
 */
class Main extends Controller
{
    public function indexMethod()
    {
        $this->view->display();
    }
} 