<?php
namespace System\Shell\Controller;

use System\Core;
use System\Util\TextUtils;

/**
 * Class PhpDocs
 * @package System\Shell\Controller
 */
class PhpDocs extends BaseController
{

    /**
     * indexMethod
     */
    public function indexMethod()
    {
        $helpArr = array();
        $helpArr['phpDocs/generatePhpDocs'] = '$appName [$baseUrl=/ [$saveDir=null [$indexTitle=Document]]';

        $this->helpMethod($helpArr);
    }

    /**
     * generatePhpDocsMethod
     * @param null $appName
     * @param string $baseUrl
     * @param null $saveDir
     * @param string $indexTitle
     * @throws \Exception
     */
    public function generatePhpDocsMethod($appName = null, $baseUrl = '/', $saveDir = null, $indexTitle = 'Document')
    {
        if (empty($appName)) {
            throw new \Exception('$appName does not exists.');
        }

        $appControllerDir = ABS_ROOT . '/Apps/' . $appName . '/Controller';
        if (!file_exists($appControllerDir)) {
            throw new \Exception($appName . ' does not exists.');
        }

        if ($saveDir) {
            $saveDir = urldecode($saveDir);
            $saveDir = ($saveDir === true || $saveDir == 'true') ? '' : trim($saveDir, '/');
            $saveDir = str_replace('//', '/', ABS_ROOT . '/Public/' . $appName . '/' . $saveDir . '/docs');
            if (!file_exists($saveDir)) {
                mkdir($saveDir, 0777, true);
            }
        }

        $indexHtml = '';

        $files = scandir($appControllerDir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..' || $file == 'BaseController.php') {
                continue;
            }

            $classShortName = substr($file, 0, -4);
            $className = 'Apps\\' . $appName . '\\Controller\\' . $classShortName;

            if ($saveDir) {
                $classLowerShortName = strtolower($classShortName);
                $savePath = $saveDir . '/' . $classLowerShortName . '.html';
                $docsContent = TextUtils::encodeHtmlSpecialChars($this->getPhpClassDocs($className, $baseUrl));
                $documentTitle = 'Index / ' . $classShortName;
                $pageTitle = '<a href="index.html">Index</a> / <a href="' . $classLowerShortName . '.html">' . $classShortName . '</a>';
                $this->save($savePath, $docsContent, $documentTitle, $pageTitle);

                $baseUrl = urldecode($baseUrl);
                $indexHtml .= '# ' . $classShortName . ' =&gt; <a href="' . $classLowerShortName . '.html">' . rtrim($baseUrl, '/') . '/' . $classLowerShortName . '</a>';
                $indexHtml .= "\r\n";
            } else {
                echo $this->getPhpClassDocs($className, $baseUrl);
            }
        }

        if ($saveDir) {
            $savePath = $saveDir . '/index.html';
            $indexHtml .= "\r\n\r\n\r\n";
            $this->save($savePath, $indexHtml, urldecode($indexTitle), '<a href="index.html">Index</a>');
        }
    }

    /**
     * getPhpClassDocs
     * @param $className
     * @param string $baseUrl
     * @return string
     */
    public function getPhpClassDocs($className, $baseUrl = '/')
    {
        $baseUrl = urldecode($baseUrl);

        $class = new \ReflectionClass($className);
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $docs = '';
        foreach ($methods as $method) {
            $methodName = $method->getShortName();
            if (substr($methodName, -6) != 'Method') {
                continue;
            }
            $methodName = substr($methodName, 0, -6);
            $docs .= '# ' . rtrim($baseUrl, '/') . '/' . strtolower($class->getShortName()) . '/' . $methodName;
            $docs .= "\r\n";
            $docs .= $this->getPhpFunctionDocs($method);
            $docs .= "\r\n\r\n\r\n";
        }

        return $docs;
    }

    /**
     * getPhpFunctionDocs
     * @param $method
     * @return string
     */
    public function getPhpFunctionDocs($method)
    {
        $lines = explode("\n", $method->getDocComment());
        $docs = '';
        $lastFix = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line == '/**' || $line == '*/') {
                continue;
            }

            if ($line[0] == '*') {
                $line = trim(substr($line, 1));
            }

            $term = explode(' ', $line, 2);
            if ($term[0] == '@query') {
                $term[0] = '@param';
            }
            if (!empty($lastFix) && $lastFix != $term[0]) {
                $lastFix = $term[0];
                $docs .= "  --\r\n";
            }

            if (count($term) == 1) {
                $lastFix = '@description';
                $docs .= '# description: ' . $line;
            } elseif (in_array($term[0], ['@param', '@query', '@return', '@field']) || substr($term[0], -5) == 'Field') {
                $term = explode(' ', $line, 4);
                if ($term[0] == '@query') {
                    $term[0] = '@param';
                }
                $docs .= '  ' . str_pad($term[0], 12) . str_pad(trim($term[2], '$'), 12) . str_pad('<' . $term[1] . '>', 12) . '  ' . trim($term[3]);
            } elseif ($term[0] == '@status') {
                $term = explode(' ', $line, 3);
                $docs .= '  ' . str_pad($term[0], 12) . str_pad($term[1], 12) . trim($term[2]);
            } else {
                $docs .= '  ' . str_pad($term[0], 12) . $term[1];
            }

            $docs .= "\r\n";
        }

        return $docs;
    }

    /**
     * save
     * @param $fileName
     * @param $docsContent
     * @param $documentTitle
     * @param null $pageTitle
     */
    private function save($fileName, $docsContent, $documentTitle, $pageTitle = null)
    {
        if (empty($pageTitle)) {
            $pageTitle = $documentTitle;
        }

        $strHtml = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"/><title>' . $documentTitle . '</title><style>body{margin:0;padding:0;background:#f5f5f5;line-height:1.75;font-family:monospace;}header{position:fixed;margin:0;padding:10px 15px;left:0;right:0;border-bottom: 1px solid #e5e5e5;background:#f5f5f5;}footer{margin:0;padding:15px;border-top: 1px solid #e5e5e5;}main{margin:0;padding:15px 15px 30px;background:#fff;overflow-x:auto;}h1,pre{margin:0;padding:0;}h1{font-size:20px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}</style></head><body><header><h1>' . $pageTitle . '</h1></header><main><pre>';
        $strHtml .= "\r\n\r\n\r\n\r\n" . $docsContent;
        $strHtml .= '</pre></main><footer><em>-- generate by FastPHP Shell.</em></footer></body></html>';

        file_put_contents($fileName, $strHtml);

        echo "#generate: ", $fileName, "\r\n";
    }
}