<?php
/**
 * 使用redis的缓存类
 */
namespace imwpcache\drivers;

class redis implements driver
{

    private $redis;

    public function connect($config)
    {
        if (!$this->redis) {
            $this->redis = new \Redis();
        }

        if (!$this->redis->pconnect($config['host'], $config['port'], 5)) {
            return false;
        }

        return true;

    }

    public function set($key, $value, $expire)
    {
        return $this->redis->setex($key, $expire, $value);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function delete($key)
    {
        return $this->redis->delete($key);
    }

    public function flush()
    {
        return $this->redis->flushDb();
    }

    public function getStats()
    {
        return $this->redis->info();
    }
    
    /**
     * redis缓存会自动过期，这个方法总是返回true
     * @return boolean  true
     */
    public function isExpire($key = null)
    {
        return false;
    }
}