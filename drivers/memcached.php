<?php

/**
 * 使用memcached
 * @Author: huchao
 * @Date:   2020-05-09 12:27:50
 * @Last Modified by:   huchao
 * @Last Modified time: 2020-05-16 18:20:19
 */
namespace imwpcache\drivers;

class memcached implements driver
{
    private $Memcached;

    public function connect($config)
    {
        if (!$this->Memcached) {
            $this->Memcached = new \Memcached();
        }

        if ($this->Memcached->addServer($config['host'], $config['port'])) {
            $this->Memcached->setOption(Memcached::OPT_COMPRESSION, true);
            return true;
        } else {
            return false;
        }
    }

    public function set($key, $value, $expire)
    {
        return $this->Memcached->set($key, $value, $expire);
    }

    public function get($key)
    {
        return $this->Memcached->get($key);
    }

    public function delete($key)
    {
        return $this->Memcached->delete($key);
    }

    public function flush()
    {
        return $this->Memcached->flush();
    }

    public function getStats()
    {
        return $this->Memcached->getStats();
    }

    /**
     * memcache缓存会自动过期，这个方法总是返回true
     * @return true
     */
    public function isExpire($key = null)
    {
        return false;
    }
}