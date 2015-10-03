<?php
namespace System\Shell\Controller;

use System\Core;
use System\Core\App;
use System\Core\Request;
use System\Util\DateUtils;

/**
 * Class BaseController
 * @package System\Shell\Controller
 */
class BaseController extends Core\Controller
{
    /**
     * __construct
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        if (!Request::isCliRequest()) {
            throw new \Exception('Access Denied', 503);
        }

        echo '# ', $_SERVER['_'], ' ', implode(' ', $_SERVER['argv']);
        echo "\r\n\r\n";
    }

    /**
     * indexMethod
     */
    public function indexMethod()
    {
        $this->helpMethod();
    }

    /**
     * helpMethod
     * @param null $helpArr
     */
    public function helpMethod($helpArr = null)
    {
        if ($helpArr === null) {
            $helpArr = array();

            $helpArr['help'] = 'show help';

            $helpArr[] = '-';
            $helpArr['make/app'] = '$appName [$isForceCreate=false]';
            $helpArr['make/controller'] = '$appName $controllerName [$isSharedController=0 [$parentControllerName=Core\Controller]]';
            $helpArr['make/model'] = '$appName $modelName [$isSharedModel=1 [$parentModelName=Core\Model]]';
            $helpArr['make/view'] = '$appName $viewName';
            $helpArr['make/layout'] = '$appName $layoutName';
            $helpArr['make/widget'] = '$appName $widgetName';

            //$helpArr[] = '-';
            //$helpArr['make/password'] = '[$passwordLength=40]';
            //$helpArr['make/jsCompiler'] = '[$jsFile=Public/Static/jkit/jkit.js [$jsOutputFile=null]]';

            $helpArr[] = '-';
            $helpArr['generatePhpDocs'] = '-u $appName $baseUrl $saveDir $indexTitle';

            $helpArr[] = '-';
            $helpArr['test'] = 'test utils';
        }

        $phpCommand = 'php'; //$_SERVER['_'];
        $scriptName = $_SERVER['argv'][0];
        echo "Usage: {$phpCommand} {$scriptName} {method} -u {args1} {args2} -d {x1=data1} {x2=data2}", "\r\n";
        echo str_pad('', 2), str_pad('-u', 4), 'convert args to urlencode string', "\r\n";
        echo str_pad('', 2), str_pad('-d', 4), 'convert data to x1=data1&x2=data2', "\r\n";
        echo "\n\n";

        foreach ($helpArr as $method => $args) {
            if ($args == '-') {
                echo str_pad('', 2), str_pad($args, 18);
            } elseif (strpos($method, 'help') !== false) {
                echo str_pad('', 2), str_pad($method, 18), ' ' . $args;
            } else {
                echo str_pad('', 2), str_pad($method, 18), ' args: ', $args;
            }
            echo "\r\n";
        }
    }

    public function __destruct()
    {
        echo "\r\n\r\n";

        /*
        $files = get_included_files();
        echo "# ", count($files), " files:\r\n";
        foreach ($files as $file) {
            echo "  ", $file, "\r\n";
        }
        //*/

        //print_r($_SERVER);

        echo '# ', App::conf('app.name'), ' ', App::conf('app.version');
        echo '  ', DateUtils::format(0, '-Y-m-d H:i:s');
        echo '  files: ', count(get_included_files());
        echo '  times: ', microtime(true) - Core\Request::getRequestTime(true);
        echo "\r\n";
    }
}