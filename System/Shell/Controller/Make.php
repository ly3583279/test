<?php
namespace System\Shell\Controller;

/**
 * Class Make
 * @package System\Shell\Controller
 */
class Make extends BaseController
{
    /**
     * 创建App
     * @param $appName
     * @param bool|false $forceCreate
     * @throws \Exception
     */
    public function appMethod($appName, $forceCreate = false)
    {
        if (empty($appName)) {
            throw new \Exception('{$appName} does not empty.');
        }

        $appName = ucfirst(trim($appName));
        $appDir = ABS_ROOT . '/Apps/' . $appName;
        if (!$forceCreate && file_exists($appDir)) {
            throw new \Exception("{$appName} exists.");
        }

        $this->initDir('Shared');
        $this->initDir($appName);

        $this->view->addData('appName', $appName);

        $appPubDir = ABS_ROOT . '/Public/' . $appName;
        $this->write($appPubDir . '/index.php', $this->view->loadView('make_index_php'));
        $this->write($appPubDir . '/robots.txt', $this->view->loadView('make_robots_txt'), false);
        if (!file_exists($appPubDir . '/favicon.ico')) {
            @copy(APP_DIR . '/Config/favicon.ico', $appPubDir . '/favicon.ico');
        }

        $appConfigDir = $appDir . '/Config';
        $this->write($appConfigDir . '/App.php', $this->view->loadView('make_app_config'));
        $this->write($appConfigDir . '/App.dev.php', $this->view->loadView('make_app_config'));
        $this->write($appConfigDir . '/' . $appName . '.php', $this->view->loadView('make_app_config'));
        $this->write($appConfigDir . '/' . $appName . '.0.0.0.0.php', $this->view->loadView('make_app_config'));
        $this->write($appConfigDir . '/Routes.php', $this->view->loadView('make_routes_config'));
        //$this->write($appConfigDir . '/Database.dev.php', $this->view->loadView('make_database_sql'), FALSE);
        $this->write($appConfigDir . '/Sys.php', $this->view->loadView('make_sys_config'));
        $this->write($appConfigDir . '/Sys.dev.php', $this->view->loadView('make_sys_config'));

        $this->log($appName, 'create ok.');

        // create default controller
        $this->controllerMethod($appName, 'Main');

        // create app helper
        $this->modelMethod($appName, 'AppHelper', true);

        // create default model
        $this->modelMethod($appName, 'Demo', false);

        // create default view
        $this->viewMethod($appName, 'main_index');

        $this->layoutMethod($appName, 'header');
        $this->layoutMethod($appName, 'footer');

        $this->widgetMethod($appName, 'menu');

        // create show message view
        $this->viewMethod($appName, 'show_message');
        $this->viewMethod($appName, 'show_exception');
    }

    /**
     * 创建Controller
     * @param $appName
     * @param $controllerName
     * @param int $isSharedController
     * @param string $parentControllerName
     * @throws \Exception
     */
    public function controllerMethod($appName, $controllerName, $isSharedController = 0, $parentControllerName = 'Core\Controller')
    {
        if (empty($appName)) {
            throw new \Exception('{$appName} does not empty.');
        }

        if (empty($controllerName)) {
            throw new \Exception('{$controllerName} does not empty.');
        }

        $appName = ucfirst(trim($appName));
        $appDir = ABS_ROOT . '/Apps/' . $appName;
        if (!file_exists($appDir)) {
            throw new \Exception("{$appName} does not exists.");
        }

        $appControllerDir = $isSharedController ? ABS_ROOT . '/Apps/Shared/Controller' : $appDir . '/Controller';
        is_dir($appControllerDir) ? null : mkdir($appControllerDir);

        $controllerName = ucfirst(trim($controllerName));
        $controllerClassName = $controllerName;
        $controllerClassPath = $appControllerDir . '/' . $controllerName . '.php';

        if (file_exists($controllerClassPath)) {
            throw new \Exception("{$controllerClassName} => {$controllerClassPath} exists.");
        }

        $isAdminApp = strpos(strtolower($appName), 'admin') !== FALSE;

        $privilegePrefix = $isAdminApp ? 'admin' : 'allow';
        $this->view->addData('privilegePrefix', $privilegePrefix);

        $this->view->addData('isAdminApp', $isAdminApp);

        $this->view->addData('appName', $appName);
        $this->view->addData('controllerName', $controllerName);
        $this->view->addData('controllerClassName', $controllerClassName);
        $this->view->addData('isSharedController', $isSharedController);
        $this->view->addData('parentClassName', $parentControllerName);

        $this->write($controllerClassPath, $this->view->loadView('make_controller'));

        $this->log($appName, "{$controllerClassName} => {$controllerClassPath} create ok.");
    }

    /**
     * 创建Model
     * @param $appName
     * @param $modelName
     * @param int $isSharedModel
     * @param string $parentModelName
     * @throws \Exception
     */
    public function modelMethod($appName, $modelName, $isSharedModel = 1, $parentModelName = 'Core\Model')
    {
        if (empty($appName)) {
            throw new \Exception('{$appName} does not empty.');
        }

        if (empty($modelName)) {
            throw new \Exception('{$modelName} does not empty.');
        }

        $appName = ucfirst(trim($appName));
        $appDir = ABS_ROOT . '/Apps/' . $appName;
        if (!file_exists($appDir)) {
            throw new \Exception("{$appName} does not exists.");
        }

        if ($modelName == 'AppHelper') {
            $appModelDir = ABS_ROOT . '/Apps/Shared/Helper';
        } else {
            $appModelDir = $isSharedModel ? ABS_ROOT . '/Apps/Shared/Model' : $appDir . '/Model';
            if (substr($modelName, -5) != 'Model') {
                $modelName .= 'Model';
            }
        }
        is_dir($appModelDir) ? null : mkdir($appModelDir, 0777, true);

        $modelName = ucfirst(trim($modelName));
        $modelClassName = $modelName;
        $modelClassPath = $appModelDir . '/' . $modelName . '.php';

        if (file_exists($modelClassPath)) {
            if ($modelName == 'AppHelper') {
                return;
            }
            throw new \Exception("{$modelClassName} => {$modelClassPath} exists.");
        }

        $this->view->addData('appName', $appName);
        $this->view->addData('isSharedModel', $isSharedModel);
        $this->view->addData('modelClassName', $modelClassName);
        $this->view->addData('parentClassName', $parentModelName);

        if ($modelName == 'AppHelper') {
            $this->write($modelClassPath, $this->view->loadView('make_app_helper'));
        } else {
            $this->write($modelClassPath, $this->view->loadView('make_model'));
        }

        $this->log($appName, "{$modelClassName} => {$modelClassPath} create ok.");
    }

    /**
     * 创建View
     * @param $appName
     * @param string $viewName
     * @throws \Exception
     */
    public function viewMethod($appName, $viewName = 'main_index')
    {

        if (empty($appName)) {
            throw new \Exception('{$appName} does not empty.');
        }

        $appName = ucfirst(trim($appName));
        $appDir = ABS_ROOT . '/Apps/' . $appName;
        if (!file_exists($appDir)) {
            throw new \Exception("{$appName} does not exists.");
        }

        $appViewDir = $appDir . '/View';
        is_dir($appViewDir) ? null : mkdir($appViewDir, 0777);

        $viewPath = $appViewDir . '/' . $viewName . '.phtml';

        $this->view->addData('appName', $appName);
        $this->view->addData('requestHash', '{$requestHash}');

        $this->view->addData('includeHeader', '{#include layout/header}');
        $this->view->addData('includeFooter', '{#include layout/footer}');

        $this->view->addData('ajaxBlockStart', '{%if(!$isAjaxRequest):}');
        $this->view->addData('ajaxBlockEnd', '{%endif;}');

        $this->view->addData('pageTitle', '{$pageTitle}');

        if ($viewName == 'show_message') {
            $this->view->addData('includeMenu', '');
            $this->view->addData('codeAction1', '{$forwardMessage}');
            $this->view->addData('codeAction2', '');
            $this->view->addData('codeAction3', '{$forwardScript}');
        } else {
            $this->view->addData('includeMenu', '{#include widget/menu}');

            $this->view->addData('codeAction1', '{%for($i=0;$i<10;$i++):}--------{%endfor;}');
            $this->view->addData('codeAction2', '{%if(!empty($GLOBALS)):}{?print_r($GLOBALS)}{%endif;}');

            $this->view->addData('codeAction3', '{=DateUtils::format(\'\',\'-\')}');
        }

        $this->write($viewPath, $this->view->loadView('make_view'), false);

        $this->log($appName, "{$viewName} => {$viewPath} create ok.");
    }

    /**
     * 创建Layout
     * @param $appName
     * @param string $layoutName
     * @throws \Exception
     */
    public function layoutMethod($appName, $layoutName = 'header')
    {

        if (empty($appName)) {
            throw new \Exception('{$appName} does not empty.');
        }

        if ($layoutName != 'header' && $layoutName != 'footer') {
            return;
        }

        $appName = ucfirst(trim($appName));
        $appDir = ABS_ROOT . '/Apps/' . $appName;
        if (!file_exists($appDir)) {
            throw new \Exception("{$appName} does not exists.");
        }

        $appViewDir = $appDir . '/View';
        is_dir($appViewDir) ? null : mkdir($appViewDir, 0777);

        $appLayoutDir = $appViewDir . '/layout';
        is_dir($appLayoutDir) ? null : mkdir($appLayoutDir, 0777);

        $viewName = strtolower($layoutName);
        $viewPath = $appLayoutDir . '/' . $viewName . '.phtml';

        $this->view->addData('appName', $appName);

        $this->view->addData('phpUse', '{#use System\Core\App, System\Core\Request, System\Util\DateUtils, System\Util\TextUtils;}');

        $this->view->addData('ajaxBlockStart', '{%if(!$isAjaxRequest):}');
        $this->view->addData('ajaxBlockEnd', '{%endif;}');

        $this->view->addData('charset', '{$charset}');
        $this->view->addData('title', '{$title}');

        $this->view->addData('siteStaticBaseUrl', '{=App::conf(\'sys.base_static_url\')}');

        $this->view->addData('keywords', '{%if(!empty($keywords)):}
<meta name="keywords" content="{$keywords}"/>
{%endif;}');
        $this->view->addData('description', '{%if(!empty($description)):}
<meta name="description" content="{$description}"/>
{%endif;}');

        $this->view->addData('pageStyle', '{%if(isset($pageStyle)):}
{$pageStyle}
{%endif;}');
        $this->view->addData('pageScript', '{%if(isset($pageScript)):}
{$pageScript}
{%endif;}');

        $this->view->addData('customHead', '{%if(isset($customHead)):}
{$customHead}
{%endif;}');
        $this->view->addData('customScript', '{%if(isset($customScript)):}
<script type="text/javascript">
{$customScript}
</script>
{%endif;}');
        $this->view->addData('customStyle', '{%if(isset($customStyle)):}
<style type="text/css">
{$customStyle}
</style>
{%endif;}');
        $this->view->addData('customBody', '<body data-uid="{$SESSION[\'uid\']}" data-request-app="{$appName}" data-request-hash="{$requestHash}"{=isset($bodyStyle)?\' style="\' . $bodyStyle . \'"\':\'\'}>');

        $this->write($viewPath, $this->view->loadView('make_' . $viewName), false);

        $this->log($appName, "{$viewName} => {$viewPath} create ok.");
    }

    /**
     * 创建Widget
     * @param $appName
     * @param $widgetName
     * @throws \Exception
     */
    public function widgetMethod($appName, $widgetName)
    {

        if (empty($appName)) {
            throw new \Exception('{$appName} does not empty.');
        }

        $appName = ucfirst(trim($appName));
        $appDir = ABS_ROOT . '/Apps/' . $appName;
        if (!file_exists($appDir)) {
            throw new \Exception("{$appName} does not exists.");
        }

        $appViewDir = $appDir . '/View';
        is_dir($appViewDir) ? null : mkdir($appViewDir, 0777);

        $appWidgetDir = $appViewDir . '/widget';
        is_dir($appWidgetDir) ? null : mkdir($appWidgetDir, 0777);

        $viewName = strtolower($widgetName);
        $viewPath = $appWidgetDir . '/' . $viewName . '.phtml';

        $this->view->addData('widgetName', $viewName);

        $this->write($viewPath, $this->view->loadView('make_widget'), false);

        $this->log($appName, "{$viewName} => {$viewPath} create ok.");
    }

    // private functions

    /**
     * 初始化目录
     * @param $appName
     */
    private function initDir($appName)
    {
        $appDir = ABS_ROOT . '/Apps/' . $appName;

        // 创建应用目录
        is_dir($appDir) ? null : mkdir($appDir, 0777, true);
        $this->log('initDir', $appDir);

        $appControllerDir = $appDir . '/Controller';
        is_dir($appControllerDir) ? null : mkdir($appControllerDir);
        $this->log('initDir', $appControllerDir);

        $appModelDir = $appDir . '/Model';
        is_dir($appModelDir) ? null : mkdir($appModelDir);
        $this->log('initDir', $appModelDir);

        $appViewDir = $appDir . '/View';
        is_dir($appViewDir) ? null : mkdir($appViewDir);
        is_dir($appViewDir . '/layout') ? null : mkdir($appViewDir . '/layout');
        is_dir($appViewDir . '/widget') ? null : mkdir($appViewDir . '/widget');
        $this->log('initDir', $appViewDir);

        if ($appName == 'Shared') {
            return;
        }

        $appConfigDir = $appDir . '/Config';
        is_dir($appConfigDir) ? null : mkdir($appConfigDir);
        $this->log('initDir', $appConfigDir);

        $appDataDir = $appDir . '/Data';
        is_dir($appDataDir) ? null : mkdir($appDataDir);
        is_dir($appDataDir . '/cache') ? null : mkdir($appDataDir . '/cache', 0777);
        chmod($appDataDir . '/cache', 0777);
        $this->write($appDataDir . '/cache/index.html', '', false);
        is_dir($appDataDir . '/conf') ? null : mkdir($appDataDir . '/conf', 0777);
        chmod($appDataDir . '/conf', 0777);
        $this->write($appDataDir . '/conf/index.html', '', false);
        is_dir($appDataDir . '/log') ? null : mkdir($appDataDir . '/log', 0777);
        chmod($appDataDir . '/log', 0777);
        $this->write($appDataDir . '/log/index.html', '', false);
        is_dir($appDataDir . '/tpl') ? null : mkdir($appDataDir . '/tpl', 0777);
        chmod($appDataDir . '/tpl', 0777);
        $this->write($appDataDir . '/tpl/index.html', '', false);
        $this->log('initDir', $appDataDir);

        $appPubDir = ABS_ROOT . '/Public/' . $appName;
        is_dir($appPubDir) ? null : mkdir($appPubDir, 0777, true);
        $this->log('initDir', $appPubDir);

        /*
        $appLibDir = ABS_ROOT . '/Library';
        is_dir($appLibDir) ? null : mkdir($appLibDir);
        $this->log('initDir', $appLibDir);
        //*/
    }

    /**
     * log
     * @param $tag
     * @param $msg
     */
    private function log($tag, $msg)
    {
        echo $tag, ": ", $msg;
        echo "\r\n";
    }

    /**
     * write
     * @param $fileName
     * @param $content
     * @param bool|true $addPhpTags
     */
    private function write($fileName, $content, $addPhpTags = true)
    {
        file_put_contents($fileName, $addPhpTags ? "<?php\r\n" . $content : $content);
        $this->log('write', $fileName);
    }

} 