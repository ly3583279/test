<?php
namespace System\Util;

use System\Core\App;


/**
 * Class OpenSearchUtils
 * @package System\Util
 */
class OpenSearchUtils
{
    private static $cloudSearchClientInstance;

    /**
     * 获取 CloudsearchClient
     * @param null $config
     * @return \CloudsearchClient
     */
    public static function client($config = null)
    {
        if (empty($config)) {
            if (self::$cloudSearchClientInstance != null) {
                return self::$cloudSearchClientInstance;
            }

            $config = App::conf('app.openSearch');
        }

        include_once SYS_DIR . '/Library/OpenSearch/CloudsearchClient.php';

        $opts = array('host' => $config['host'], 'debug' => false);
        self::$cloudSearchClientInstance = new \CloudsearchClient($config['access_key'], $config['secret'], $opts, $config['key_type']);

        return self::$cloudSearchClientInstance;
    }

    /**
     * search
     * @param string $query 指定的搜索查询串，可以为query=>"索引名:'鲜花'"
     * @param array $indexes 指定的搜索应用，可以为一个应用，也可以多个应用查询
     * @param int $start 指定搜索结果集的偏移量。默认为0
     * @param int $hits 指定返回结果集的数量。默认为20
     * @param int $expires 是否需要缓存，>0为缓存时间
     * @param array $params 扩展选项
     * @param null $callback
     * @optField string formula_name 指定的表达式名称，此名称需在网站中设定
     * @optField array fetch_fields 设定返回的字段列表，如果只返回url和title，则为 array('url', 'title')
     * @optField array sort 指定排序规则。默认值为：'self::SORT_DECREASE' (降序)
     * @optField string filter 指定通过某些条件过滤结果集
     * @optField array aggregate 指定统计类的信息
     * @optField array distinct 指定distinct排序
     * @optField string kvpair 指定的kvpair
     * @return mixed|null
     */
    public static function search($query, $indexes, $start = 0, $hits = 20, $expires = 0, $params = null, $callback = null)
    {
        if (empty($params)) {
            $params = array();
        }
        $params['query'] = $query;
        $params['indexes'] = $indexes;
        $params['format'] = 'json';
        $params['start'] = $start;
        $params['hits'] = $hits;

        // fix $opts->fetch_field bug
        if (isset($params['fetch_fields'])) {
            $params['fetch_field'] = $params['fetch_fields'];
        }

        $cacheKey = $cacheData = null;
        if ($expires && App::cache() != null) {
            $cacheKey = is_array($params) && isset($params['cacheKey']) ? $params['cacheKey'] : null;
            if (empty($cacheKey)) {
                $cacheKey = App::cache()->generateCacheKey('openSearch', 'search', $params);
            }

            if ($expires < 0) {
                App::cache()->del($cacheKey);
                if ($expires == -1) {
                    return null;
                }
                $expires = $expires * -1;
            }

            $cacheData = App::cache()->get($cacheKey);
        } else {
            $expires = 0;
        }

        if (empty($cacheData)) {
            include_once SYS_DIR . '/Library/OpenSearch/CloudsearchSearch.php';
            $searchInstance = new \CloudsearchSearch(self::client());

            $json = $searchInstance->search($params);
            if (!empty($json)) {
                $jsonData = json_decode($json, true); // print_r($jsonData);
                if (!empty($jsonData) && $jsonData['status'] == 'OK' && is_array($jsonData['result'])) {
                    $cacheData = $jsonData['result']['items'];
                }

                if (!empty($callback) && is_array($cacheData)) {
                    array_walk($cacheData, $callback);
                }
            }

            if ($expires && !empty($cacheData)) {
                App::cache()->set($cacheKey, $cacheData, $expires);
            }
        }

        return $cacheData;
    }

    /**
     * suggest
     * @param string $query 查询关键词
     * @param string $index_name 应用名称
     * @param string $suggest_name 下拉提示名称
     * @param int $hits 返回结果条数
     * @param int $expires 是否需要缓存，>0为缓存时间
     * @param null $callback
     * @return mixed|null
     */
    public static function suggest($query, $index_name = '', $suggest_name = '', $hits = 10, $expires = 0, $callback = null)
    {
        if (empty($params)) {
            $params = array();
        }
        $params['query'] = $query;
        $params['index_name'] = $index_name;
        $params['suggest_name'] = $suggest_name;
        $params['hits'] = $hits;

        $cacheKey = $cacheData = null;
        if ($expires && App::cache() != null) {
            $cacheKey = is_array($params) && isset($params['cacheKey']) ? $params['cacheKey'] : null;
            if (empty($cacheKey)) {
                $cacheKey = App::cache()->generateCacheKey('openSearch', 'suggest', $params);
            }

            if ($expires < 0) {
                App::cache()->del($cacheKey);
                if ($expires == -1) {
                    return null;
                }
                $expires = $expires * -1;
            }

            $cacheData = App::cache()->get($cacheKey);
        } else {
            $expires = 0;
        }

        if (empty($cacheData)) {
            include_once SYS_DIR . '/Library/OpenSearch/CloudsearchSuggest.php';
            $searchInstance = new \CloudsearchSuggest(self::client());

            $json = $searchInstance->search($params);
            if (!empty($json)) {
                $jsonData = json_decode($json, true); // print_r($jsonData);
                if (empty($jsonData) || isset($jsonData['errors'])) {
                    $cacheData = null;
                } else {
                    $suggestions = $jsonData['suggestions'];
                    if (is_array($suggestions)) {
                        $cacheData = array();
                        foreach ($suggestions as $suggestion) {
                            $cacheData[] = $suggestion['suggestion'];
                        }
                    }
                }

                if (!empty($callback) && is_array($cacheData)) {
                    array_walk($cacheData, $callback);
                }
            }

            if ($expires && !empty($cacheData)) {
                App::cache()->set($cacheKey, $cacheData, $expires);
            }
        }

        return $cacheData;
    }

}