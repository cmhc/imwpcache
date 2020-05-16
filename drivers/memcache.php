<?php
/**
 * 使用memcache缓存
 */
namespace imwpcache\drivers;

class memcache implements driver
{
    private $memcache;

    public function connect($config)
    {
        if (!$this->memcache) {
            $this->memcache = new Memcache();
        }

        if ($this->memcache->connect($config['host'], $config['port'])) {
            $this->memcache->setCompressThreshold(20000,0.2);
            return true;
        } else {
            return false;
        }
    }

    public function set($key, $value, $expire)
    {
        return $this->memcache->set($key, $value, MEMCACHE_COMPRESSED, $expire);
    }

    public function get($key)
    {
        return $this->memcache->get($key);
    }

    public function delete($key)
    {
        return $this->memcache->delete($key);
    }

    public function flush()
    {
        return $this->memcache->flush();
    }

    public function getStats()
    {
        return $this->memcache->getStats();
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