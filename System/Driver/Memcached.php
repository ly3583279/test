<?php
namespace System\Driver;

/**
 * Class Memcached
 * Memcached客户端，基于LibMemcached实现
 * 关于LibMemcached的更多信息请参考：http://libmemcached.org/libMemcached.html
 * @package System\Driver
 */
class Memcached
{
    private $items = array();

    private $memcachedInstance;
    private $cacheEnabled = false;

    /**
     * __construct
     * @param $config
     */
    public function __construct($config)
    {
        if (empty($config)) {
            return;
        }

        $this->cacheEnabled = $config['enabled'];
        if (!$this->cacheEnabled) {
            return;
        }

        $this->memcachedInstance = new \Memcached($config['persistent_id']);
        if (isset($config['opt_compression'])) {
            $this->memcachedInstance->setOption(\Memcached::OPT_COMPRESSION, $config['opt_compression']);
        }
        if (isset($config['opt_binary_protocol'])) {
            $this->memcachedInstance->setOption(\Memcached::OPT_BINARY_PROTOCOL, $config['opt_binary_protocol']);
        }
        $this->memcachedInstance->addServers($config['servers']);

        $this->items = array();
    }

    // ----------------------  public methods  ----------------------

    /**
     * get
     * @param $key
     * @param null $cache_cb
     * @param int $cas_token
     * @return mixed|null
     */
    public function get($key, $cache_cb = null, &$cas_token = 0)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        } else {
            if (!$this->cacheEnabled) {
                return null;
            }

            $value = $this->memcachedInstance->get($key, $cache_cb, $cas_token);
            if ($this->memcachedInstance->getResultCode() == \Memcached::RES_NOTFOUND) {
                return null;
            } else {
                return $value;
            }
        }
    }

    /**
     * set
     * @param $key
     * @param null $value
     * @param int $expires
     * @return bool|null
     */
    public function set($key, $value = null, $expires = 0)
    {
        $this->items[$key] = $value;

        if (!$this->cacheEnabled) {
            return null;
        }

        return $this->memcachedInstance->set($key, $value, $expires);
    }

    /**
     * del
     * @param $key
     * @param int $time
     * @return bool|null
     */
    public function del($key, $time = 0)
    {
        return $this->delete($key, $time);
    }

    /**
     * delete
     * @param $key
     * @param int $time
     * @return bool|null
     */
    public function delete($key, $time = 0)
    {
        if (array_key_exists($key, $this->items)) {
            unset($this->items[$key]);
        }

        if (!$this->cacheEnabled) {
            return null;
        }

        return $this->memcachedInstance->delete($key, $time);
    }

    /**
     * clear
     * @param int $delay
     * @return bool|null
     */
    public function clear($delay = 0)
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        return $this->memcachedInstance->flush($delay);
    }

    /**
     * close
     */
    public function close()
    {
        if ($this->cacheEnabled && $this->memcachedInstance) {
            $this->memcachedInstance->quit();
        }
    }

    /**
     * getStats
     */
    public function getStats()
    {
        if ($this->cacheEnabled && $this->memcachedInstance) {
            return $this->memcachedInstance->getStats();
        }
    }

    /**
     * generateCacheKey
     * @param string $type
     * @param string $key
     * @param null|string|array $value
     * @param null|string|array $extra1
     * @param null|string|array $extra2
     * @return string
     */
    public function generateCacheKey($type = 'cache', $key = null, $value = null, $extra1 = null, $extra2 = null)
    {
        $value = is_array($value) ? md5(http_build_query($value)) : $value;
        $extra1 = is_array($extra1) ? http_build_query($extra1) : $extra1;
        $extra2 = is_array($extra2) ? http_build_query($extra2) : $extra2;
        $extra = $extra1 . $extra2;
        return sprintf('%s:%s%s%s%s', $type, $key, strlen($value) === 0 ? null : '.', $value, strlen($extra) === 0 ? null : '#' . md5($extra));
    }


    // ----------------------  private methods  ----------------------

    /**
     * __call: 更多方法通过__call来调用
     * @param $name
     * @param null $arguments
     * @return mixed|null
     */
    public function __call($name, $arguments = null)
    {
        if ($this->cacheEnabled && $this->memcachedInstance) {
            if (!method_exists($this->memcachedInstance, $name)) {
                return null;
            }

            if (empty($arguments)) {
                return call_user_func(array($this->memcachedInstance, $name));
            } else {
                return call_user_func_array(array($this->memcachedInstance, $name), $arguments);
            }
        }
    }

}
