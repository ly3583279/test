<?php
namespace System\Core;

use System\Util\TextUtils;

/**
 * Class View
 * @package System\Core
 */
class View
{
    private $viewName = 'default';
    private $viewData = array();
    private $themeName = '';

    /**
     * $sessionData
     * @param null|array $sessionData
     */
    public function __construct($sessionData = null)
    {
        if ($sessionData === null) {
            return;
        }

        // 初始化数据
        if (empty($sessionData)) {
            $sessionData = array();
        }

        $this->addData('SESSION', $sessionData);

        // 设置Theme
        $this->setTheme(App::conf('app.theme'));

        // 设置输出编码
        $this->setCharset(App::conf('sys.charset'));

        // 设置是否为Ajax请求
        $this->setAjaxRequest(Request::isAjaxRequest());

        // 初始化基础变量
        $this->setRequestTime(Request::getRequestTime());
        $this->setRequestHash(Request::getRequestHash());
    }

    /**
     * add: 添加一个单项或数组
     * @param $mixed
     * @param null $value
     */
    public function add($mixed, $value = null)
    {
        if ($mixed) {
            if (is_array($mixed)) {
                $this->addDataArray($mixed);
            } else {
                $this->addData($mixed, $value);
            }
        }
    }

    /**
     * addData: 添加一个单项
     * @param $key
     * @param $value
     */
    public function addData($key, $value)
    {
        $this->viewData[$key] = $value;
    }

    /**
     * addDataUrl: 将数据通过urlencode转换
     * @param $key
     * @param $value
     */
    public function addDataUrl($key, $value)
    {
        $this->viewData[$key] = urlencode($value);
    }

    /**
     * addDataSpecialChars：将数据转换为SpecialChars
     * @param $key
     * @param $value
     */
    public function addDataSpecialChars($key, $value)
    {
        $this->viewData[$key] = TextUtils::encodeHtmlSpecialChars($value);
    }

    /**
     * addDataBase64：将数据转换为Base64
     * @param $key
     * @param $value
     */
    public function addDataBase64($key, $value)
    {
        $this->viewData[$key] = TextUtils::encodeBase64($value);
    }

    /**
     * addDataJson
     * @param $key
     * @param $value
     */
    public function addDataJson($key, $value)
    {
        $this->viewData[$key] = TextUtils::encodeJson($value);
    }

    /**
     * addDataArray
     * @param $arr
     */
    public function addDataArray($arr)
    {
        if (empty($arr)) {
            return;
        }

        //$this->_viewData += $arr;
        $this->viewData = array_merge($this->viewData, $arr);
    }

    /**
     * set: alias addData
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->viewData[$key] = $value;
    }

    /**
     * get
     * @param null $key
     * @return array
     */
    public function get($key = null)
    {
        if ($key === null) {
            return $this->viewData;
        } else {
            return isset($this->viewData[$key]) ? $this->viewData[$key] : null;
        }
    }

    /**
     * clear
     */
    public function clear()
    {
        $this->viewData = array();
    }

    /**
     * setTheme
     * @param $themeName
     */
    public function setTheme($themeName)
    {
        $this->themeName = $themeName;
        $this->addData('themeName', $themeName);
    }

    /**
     * setViewName
     * @param string $viewName
     */
    public function setViewName($viewName)
    {
        $this->viewName = $viewName;
        $this->addData('viewName', $viewName);
    }

    /**
     * setRequestTime
     * @param int $requestTime
     */
    public function setRequestTime($requestTime = 0)
    {
        $this->addData('requestTime', $requestTime);
    }

    /**
     * setRequestHash
     * @param null $requestHash
     */
    public function setRequestHash($requestHash = null)
    {
        $this->addData('requestHash', $requestHash);
    }

    /**
     * setCharset
     * @param string $charset
     */
    public function setCharset($charset = 'UTF-8')
    {
        $this->addData('charset', $charset);
    }

    /**
     * setContentType
     * @param string $contentType text/html text/plain application/json
     * @param string $charset
     */
    public function setContentType($contentType = 'text/html', $charset = null)
    {
        if (empty($charset)) {
            $charset = App::conf('sys.charset');
        } else {
            $this->setCharset($charset);
        }

        header('Content-Type: ' . $contentType . '; charset=' . $charset);
    }

    /**
     * setAjaxRequest
     * @param boolean $ajaxRequest default: false
     */
    public function setAjaxRequest($ajaxRequest = false)
    {
        $this->addData('ajaxRequest', $ajaxRequest);
    }

    /**
     * setAjaxReset
     * @param bool $ajaxReset
     */
    public function setAjaxReset($ajaxReset = false)
    {
        $this->addData('ajaxReset', $ajaxReset);
    }

    /**
     * setTitle
     * @param string /[] $_
     */
    public function setTitle($_ = null)
    {
        $args = is_array($_) ? $_ : func_get_args();
        $pageTitle = empty($args) ? '' : trim(implode(' - ', $args));

        $title = empty($pageTitle) ? (App::conf('app.name') . ' - ' . App::conf('sys.slogan')) : ($pageTitle . ' - ' . App::conf('app.name'));

        $this->addData('title', $title);
        $this->setPageTitle($pageTitle);
    }

    /**
     * setKeywords
     * @param $keywords
     */
    public function setKeywords($keywords)
    {
        $this->addData('keywords', $keywords);
    }

    /**
     * setDescription
     * @param $description
     */
    public function setDescription($description)
    {
        $this->addData('description', $description);
    }

    /**
     * setBodyStyle
     * @param string $bodyStyle default: background:transparent
     */
    public function setBodyStyle($bodyStyle = 'background:transparent')
    {
        $this->addData('bodyStyle', $bodyStyle);
    }

    /**
     * setCustomHead
     * @param $_
     */
    public function setCustomHead($_)
    {
        $args = is_array($_) ? $_ : func_get_args();
        $headText = empty($args) ? '' : trim(implode("\r\n", $args));

        if (empty($headText)) {
            return;
        }

        $this->addData('customHead', $headText);
    }

    /**
     * setCustomStyle
     * @param string $styleText CSS内容
     */
    public function setCustomStyle($styleText)
    {
        $this->addData('customStyle', $styleText);
    }

    /**
     * setCustomScript
     * @param $scriptText
     */
    public function setCustomScript($scriptText)
    {
        $this->addData('customScript', $scriptText);
    }

    /**
     * setPageTitle
     * @param string $pageTitle
     * @param bool $overrideTitle
     */
    public function setPageTitle($pageTitle = '', $overrideTitle = false)
    {
        if ($overrideTitle) {
            $this->addData('title', $pageTitle);
        }

        $this->addData('pageTitle', $pageTitle);
    }

    /**
     * setPageUrl
     * @param string $pageUrl
     */
    public function setPageUrl($pageUrl = '')
    {
        $this->addData('pageUrl', $pageUrl);
    }

    /**
     * setPageStyle
     * @param string /array $_ CSS URL
     */
    public function setPageStyle($_)
    {
        $args = is_array($_) ? $_ : func_get_args();
        $out = '';
        foreach ($args as $src) {
            list($type, $src) = TextUtils::explodeKeyValue($src, ':', 'all');
            $out .= '<link type="text/css" rel="stylesheet" href="' . $src . '" media="' . $type . '"/>';
        }
        $this->addData('pageStyle', $out);
    }

    /**
     * setPageScript
     * @param string /array $_ Script URL
     */
    public function setPageScript($_)
    {
        $args = is_array($_) ? $_ : func_get_args();
        $out = '';
        foreach ($args as $src) {
            list($type, $src) = TextUtils::explodeKeyValue($src, ':', 'javascript');
            $out .= '<script type="text/' . $type . '" src="' . $src . '"></script>';
        }
        $this->addData('pageScript', $out);
    }

    /**
     * setSearchUrl
     * @param string $searchUrl
     */
    public function setSearchUrl($searchUrl = '/search')
    {
        $this->addData('searchUrl', $searchUrl);
    }

    /**
     * setSearchWords
     * @param string $searchWords
     */
    public function setSearchWords($searchWords = '')
    {
        $this->addData('searchWords', TextUtils::encodeHtmlSpecialChars($searchWords));
    }

    /**
     * setNavigation
     * @param string /array $_ 例如：'title' 或者 ['url'=>'title1', 'title']
     */
    public function setNavigation($_ = null)
    {
        if (is_array($_)) {
            //$out = '<ol class="breadcrumb">';
            $out = '<li><a href="' . App::conf('app.index_url') . '">' . App::conf('app.index_name') . '</a></li>';
            foreach ($_ as $url => $title) {
                if (is_numeric($url)) {
                    $out .= '<li class="active">' . $title . '</li>';
                } else {
                    $out .= '<li><a href="' . $url . '">' . $title . '</a><li>';
                }
            }
            //$out .= '</ol>';
        } else {
            $out = $_;
        }

        $this->addData('navigation', $out);
    }

    /**
     * setPagination
     * @param string $format
     * @param int|number $page
     * @param int|number $totalCount
     * @param int|number $pageSize
     * @param array $options count[7]/format[%s]/fixed[false]/first[第一页]/previous[&lt;]/next[&gt;]/last[最末页]
     */
    public function setPagination($format, $page = 1, $totalCount = 0, $pageSize = 25, $options = null)
    {
        $out = '';
        $pageCount = 0;
        if ($totalCount > 0) {
            $pageCount = intval(($totalCount - 1) / $pageSize) + 1;

            if (empty($options)) {
                $options = array();
            }

            $isPager = isset($options['pager']) ? $options['pager'] : 0;
            if ($isPager) {
                $itemCount = 0;
                $itemFormat = isset($options['format']) ? $options['format'] : '%s';
                $itemFixed = isset($options['fixed']) ? $options['fixed'] : true;
                $itemFirst = 0;
                $itemPrevious = isset($options['previous']) ? $options['previous'] : '上一页';
                $itemNext = isset($options['next']) ? $options['next'] : '下一页';
                $itemLast = 0;
            } else {
                $itemCount = isset($options['count']) ? $options['count'] : 7;
                $itemFormat = isset($options['format']) ? $options['format'] : '%s';
                $itemFixed = isset($options['fixed']) ? $options['fixed'] : true;
                $itemFirst = isset($options['first']) ? $options['first'] : '第一页'; // &laquo;
                $itemPrevious = isset($options['previous']) ? $options['previous'] : '&lt;';
                $itemNext = isset($options['next']) ? $options['next'] : '&gt;';
                $itemLast = isset($options['last']) ? $options['last'] : '最末页'; // &raquo;
            }

            $ajaxRequest = isset($options['ajaxRequest']) ? $options['ajaxRequest'] : '';
            $requestTarget = isset($options['requestTarget']) ? $options['requestTarget'] : '';
            $requestAlert = isset($options['requestAlert']) ? $options['requestAlert'] : '';
            $click = isset($options['click']) ? $options['click'] : '';

            $elemAttr = '';
            if ($ajaxRequest) {
                if ($requestTarget) {
                    $elemAttr .= ' data-request-target="' . $requestTarget . '"';
                }
                if ($requestAlert) {
                    $elemAttr .= ' data-request-alert="' . $requestAlert . '"';
                }
                if ($click) {
                    $elemAttr .= ' onclick="' . $click . '"';
                }
            }

            if ($page < 1)
                $page = 1;
            if ($page > $pageCount)
                $page = $pageCount;

            if ($page > 1) {
                $page1 = $page - 1;
                if (!empty($itemFirst)) {
                    $out .= '<li><a href="' . str_replace('%s', 1, $format) . '"' . $elemAttr . '>' . $itemFirst . '</a></li>';
                }
                if (!empty($itemPrevious)) {
                    $out .= '<li><a href="' . str_replace('%s', $page1, $format) . '"' . $elemAttr . '>' . $itemPrevious . '</a></li>';
                }
            } else {
                if ($itemFixed && !empty($itemFirst)) {
                    $out .= '<li class="disabled"><a href="###">' . $itemFirst . '</a></li>';
                }
                if ($itemFixed && !empty($itemPrevious)) {
                    $out .= '<li class="disabled"><a href="###">' . $itemPrevious . '</a></li>';
                }
            }

            if ($itemCount > 0) {
                $iStart = $page - intval(($itemCount - 1) / 2);
                if ($iStart < 1)
                    $iStart = 1;

                $iEnd = $iStart + ($itemCount - 1);
                if ($iEnd > $pageCount)
                    $iEnd = $pageCount;

                for ($i = $iStart; $i <= $iEnd; $i++) {
                    $activeClass = ($i == $page) ? ' class="active"' : '';
                    $out .= '<li' . $activeClass . '><a name="a' . $i . '" href="' . str_replace('%s', $i, $format) . '"' . $elemAttr . '>' . str_replace('%s', $i, $itemFormat) . '</a></li>';
                }
            }

            if ($page < $pageCount) {
                $page2 = $page + 1;
                if (!empty($itemNext)) {
                    $out .= '<li><a href="' . str_replace('%s', $page2, $format) . '"' . $elemAttr . '>' . $itemNext . '</a></li>';
                }
                if (!empty($itemLast)) {
                    $out .= '<li><a href="' . str_replace('%s', $pageCount, $format) . '"' . $elemAttr . '>' . $itemLast . '</a></li>';
                }
            } else {
                if ($itemFixed && !empty($itemNext)) {
                    $out .= '<li class="disabled"><a href="###">' . $itemNext . '</a></li>';
                }
                if ($itemFixed && !empty($itemLast)) {
                    $out .= '<li class="disabled"><a href="###">' . $itemLast . '</a></li>';
                }
            }
        }

        $this->addData('page', $page);
        $this->addData('pageSize', $pageSize);
        $this->addData('pageCount', $pageCount);
        $this->addData('totalCount', $totalCount);

        $this->addData('pagination', $out);
    }

    // response methods

    /**
     * setResponse
     * @param int $responseStatus
     * @param null|string|array $responseMessage
     * @param null|string|array $responseData
     * @param null|string $dataType
     * @return int
     */
    public function setResponse($responseStatus = 0, $responseMessage = null, $responseData = null, $dataType = null)
    {
        $this->setResponseStatus($responseStatus, $responseMessage);
        if ($responseData !== null) {
            $this->setResponseData($responseData, $dataType);
        }
        return $responseStatus;
    }

    /**
     * setResponseStatus
     * @param int $responseStatus
     * @param null $responseMessage
     * @return int
     */
    public function setResponseStatus($responseStatus = 0, $responseMessage = null)
    {
        $this->addData('status', $responseStatus);
        if ($responseMessage !== null) {
            $this->addData('message', $responseMessage);
        }
        return $responseStatus;
    }

    /**
     * setResponseData
     * @param $responseData
     * @param string|<list> $dataType
     */
    public function setResponseData($responseData, $dataType = null)
    {
        $this->addData('data', $responseData);
        if ($dataType != null) {
            $this->addData('dataType', $dataType);
        }
    }

    // template functions

    /**
     * ajaxView
     * @param null $viewName
     * @param null $viewData
     */
    public function ajaxView($viewName = null, $viewData = null)
    {
        $this->output($viewName, $viewData);
        exit(0);
    }

    /**
     * ajaxOutput
     * @param null $data
     * @param int $options
     */
    public function ajaxOutput($data = null, $options = JSON_HEX_QUOT)
    {
        $data = empty($data) ? $this->viewData : $data;

        // 自定义输出
        $appHelper = '\Apps\Shared\Helper\AppHelper';
        if (method_exists($appHelper, 'jsonOutput')) {
            $appHelper::jsonOutput($data, $options);
        } else {
            echo json_encode($data, $options);
        }

        exit(0);
    }

    /**
     * 加载指定视图
     * @param string $viewName
     * @param array $viewData
     * @return string
     */
    public function loadView($viewName = null, $viewData = null)
    {
        return $this->output($viewName, $viewData, true);
    }

    /**
     * output
     * @param null $viewName
     * @param null $viewData
     * @param bool|false $return
     * @return string
     */
    public function output($viewName = null, $viewData = null, $return = false)
    {
        $tplName = empty($viewName) ? $this->viewName : $viewName;

        extract($this->viewData);
        if (!empty($viewData)) {
            extract($viewData);
        }

        if ($return) {
            ob_start();
            require $this->getTemplate($tplName);
            return ob_get_clean();
        } else {
            require $this->getTemplate($tplName);
        }
    }

    /**
     * display
     * @param string $viewName
     * @param array $viewData
     */
    public function display($viewName = null, $viewData = null)
    {
        if (Request::isAjaxRequest()) {
            $this->ajaxOutput($viewData);
        } else {
            $this->output($viewName, $viewData);
        }
    }

    /**
     * getTemplate
     * @param $tplName
     * @return mixed|null|string
     */
    public function getTemplate($tplName)
    {
        return $this->parseTemplate($tplName);
    }

    // private methods

    /**
     * parseTemplate
     * @param $tplName
     * @param bool|false $return
     * @return mixed|null|string
     */
    private function parseTemplate($tplName, $return = false)
    {
        if (!empty($this->themeName)) {
            $tplName = $this->themeName . '/' . $tplName;
        }

        $tplViewName = APP_DIR . '/View/' . $tplName . '.phtml';
        $tplFileName = APP_DIR . '/Data/tpl/' . str_replace('/', '_', $tplName) . '.tpl.php';

        // 加载共享模板
        if (!is_file($tplViewName)) {
            $tplViewName = APP_ROOT . '/Shared/View/' . $tplName . '.phtml';
        }

        $tplTemplate = null;

        // 判断模板缓存是否存在，且是否需要刷新
        //echo $tplFileName;exit;
        if (!is_file($tplFileName) || filemtime($tplFileName) < filemtime($tplViewName)) { // 解析模板
            // 判断模板是否存在
            if (file_exists($tplViewName)) {
                $tplTemplate = file_get_contents($tplViewName);

                if (preg_match_all('/\{\#include ([^\}]+?)\}/is', $tplTemplate, $matches, PREG_SET_ORDER)) {
                    // $matches = array_unique($matches);
                    foreach ($matches as $match) {
                        $tplTemplate = str_replace($match[0], $this->parseTemplate($match[1], true), $tplTemplate);
                    }
                }

                // PHP嵌入式语句支持，用 ?、%、# 开头，表示一个嵌入式语句，如：use, print_r
                $tplTemplate = preg_replace('/\{[\?\%\#]([^\}]+?)\}/is', '<?php \\1 ?>', $tplTemplate);
                // 参数输出，支持 = 和 $
                $tplTemplate = preg_replace('/\{\=([^\}]+?)\}/is', '<?=\\1?>', $tplTemplate);
                $tplTemplate = preg_replace('/\{\$(\w[^\}]*?)\}/is', '<?=\$\\1?>', $tplTemplate);
            } else {
                $tplTemplate = $tplName . '.phtml does not exists.';
            }

            // exit($tplTemplate);
            file_put_contents($tplFileName, $tplTemplate);
        }

        // 解析模板，返回解析内容
        if ($return) {
            return ($tplTemplate === null) ? file_get_contents($tplFileName) : $tplTemplate;
        } else { // 返回模板路径
            return $tplFileName;
        }
    }

    // magic methods

    /**
     * __get
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        if (is_array($this->viewData) && array_key_exists($name, $this->viewData)) {
            return $this->viewData[$name];
        }

        return null;
    }

    /**
     * __set
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->viewData[$name] = $value;
    }

    /**
     * __isset
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->viewData[$name]);
    }

    /**
     * __unset
     * @param $name
     */
    public function __unset($name)
    {
        unset($this->viewData[$name]);
    }
} 