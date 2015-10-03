<?php
namespace System\Plugin;

use System\Core\App;
use System\Core\Request;
use System\Util\DateUtils;

/**
 * Class Dashboard
 * @package System\Plugin
 */
class Dashboard
{

    /**
     * cacheStatus
     */
    public function cacheStatus()
    {
        $output = App::cache() ? print_r(App::cache()->getStats(), true) : null;
        $this->output('cacheStatus', $output);
    }

    /**
     * dbStatus
     */
    public function dbStatus()
    {
        $output = App::db() ? App::db()->stat() : null;
        $this->output('dbStatus', $output);
    }

    /**
     * opCacheStatus
     */
    public function opCacheStatus()
    {
        $output = function_exists('opcache_get_status') ? print_r(opcache_get_status(true), true) : null;
        $this->output('opCacheStatus', $output);
    }

    /**
     * serverStatus
     */
    public function serverStatus()
    {
        $this->output('serverStatus', print_r($_SERVER, true));
    }

    /**
     * tplStatus
     */
    public function tplStatus()
    {
        $tplDir = APP_DIR . '/Data/tpl';
        $tplFiles = scandir($tplDir);
        $output = '';
        if ($tplFiles !== false) {
            foreach ($tplFiles as $fileName) {
                if ($fileName == '.' || $fileName == '..') {
                    continue;
                }

                $tplFile = $tplDir . '/' . $fileName;
                if (is_file($tplFile)) {
                    $output .= DateUtils::format(filemtime($tplFile), '-Y-m-d H:i:s') . ' => ' . $fileName . "\r\n";
                }
            }
        }

        $this->output('tplStatus', $output);
    }

    /**
     * clearCache
     */
    public function clearCache()
    {
        $output = null;
        if (App::cache()) {
            App::cache()->clear();
            $output = print_r(App::cache()->getStats(), true);
        }

        $this->output('clearCache', $output);
    }

    /**
     * clearTplCache
     */
    public function clearTplCache()
    {
        $tplDir = APP_DIR . '/Data/tpl';
        $tplFiles = scandir($tplDir);
        $output = '';
        if ($tplFiles !== false) {
            foreach ($tplFiles as $fileName) {
                if ($fileName == '.' || $fileName == '..') {
                    continue;
                }
                $tplFile = $tplDir . '/' . $fileName;
                if (is_file($tplFile)) {
                    $returnVal = unlink($tplFile);
                    $output .= "# del " . $fileName . ($returnVal ? " ok." : " failed.") . "\r\n";
                }
            }
        }
        $output .= '# clear ok.';

        $this->output('clearTplCache', $output);
    }

    /**
     * reloadConfig
     */
    public function reloadConfig()
    {
        $output = null;
        if (App::db()) {
            $configTableName = App::conf('app.sys_config_table');
            $params = array('indexField' => 'name', 'valueField' => 'value', 'rowCount' => 1000);
            $conf = App::db()->getScalars($configTableName, null, $params, -86400);
            $output = print_r($conf, true);
        }

        $this->output('reloadConfig', $output);
    }

    /**
     * ip
     */
    public function ip()
    {
        $output = Request::getHttpClientIp();
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $output .= ' / ' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'];
        }

        $this->output('ip', $output);
    }

    /**
     * userAgent
     */
    public function userAgent()
    {
        $this->output('userAgent', Request::getHttpUserAgent());
    }

    /**
     * ping
     */
    public function ping()
    {
        $output = $_SERVER['HTTP_HOST'];
        if (isset($_SERVER['SERVER_ADDR'])) {
            $output .= ' / ' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'];
        }

        $this->output('ping', $output);
    }

    /**
     * status
     */
    public function status()
    {
        $output = DateUtils::format(0, '-Y-m-d H:i:s') . "  files: " . count(get_included_files()) . "  times: " . (microtime(true) - Request::getRequestTime(true));
        $this->output('status', $output);
    }

    /**
     * version
     */
    public function version()
    {
        $this->output('version', VERSION);
    }

    /**
     * load
     */
    public function load()
    {
        App::session()->checkHttpAuthenticate('Digest');

        $action = Request::get('ac');
        if (!empty($action) && method_exists($this, $action)) {
            return call_user_func(array($this, $action));
        }

        $baseUrl = rtrim(App::conf('app.base_url'), '/');

        $methods = get_class_methods($this);
        $menu = '';
        foreach ($methods as $method) {
            if ($method == 'load' || $method == 'output') {
                continue;
            }
            $menu .= '# <a href="' . $baseUrl . '/load?m=dashboard&ac=' . $method . '">' . ucfirst($method) . '</a>';
            $menu .= "\r\n";
        }

        $this->output('Dashboard', $menu);
    }

    /**
     * output
     * @param $documentTitle
     * @param string $outputContent
     */
    public function output($documentTitle, $outputContent = '')
    {
        $baseUrl = rtrim(App::conf('app.base_url'), '/');
        $pageTitle = '<a href="' . $baseUrl . '/do?m=dashboard">Dashboard</a>';
        if ($documentTitle != 'Dashboard') {
            if (Request::isAjaxRequest()) {
                echo $outputContent;
                exit;
            }
            $pageTitle .= ' / <a href="' . $baseUrl . '/do?m=dashboard&ac=' . $documentTitle . '">' . ucfirst($documentTitle) . '</a>';
            $documentTitle = 'Dashboard / ' . ucfirst($documentTitle);
        }

        echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"/><title>' . ucfirst($documentTitle) . '</title><style>body{margin:0;padding:0;background:#f5f5f5;line-height:1.75;font-family:monospace;}header{position:fixed;margin:0;padding:10px 15px;left:0;right:0;border-bottom: 1px solid #e5e5e5;background:#f5f5f5;}footer{margin:0;padding:15px;border-top: 1px solid #e5e5e5;}main{margin:0;padding:15px 15px 30px;background:#fff;overflow-x:auto;}h1,pre{margin:0;padding:0;}h1{font-size:20px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}</style></head><body><header><h1>' . $pageTitle . '</h1></header><main><pre>';
        echo "\r\n\r\n\r\n\r\n", $outputContent, "\r\n\r\n\r\n";
        echo '</pre></main><footer><em>-- FastPHP Dashboard.</em></footer></body></html>';
    }
}