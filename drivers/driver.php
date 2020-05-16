<?php
/**
 * 缓存接口
 */
namespace imwpcache\drivers;

interface driver
{

    /**
     * 连接到缓存服务
     * @return 
     */
    public function connect($config);

    /**
     * 写缓存
     * @param string $key
     * @param stirng $value
     * @param int $expire
     */
    public function set($key, $value, $expires);

    /**
     * 读缓存
     * @param  string $key
     * @return string
     */
    public function get($key);

    /**
     * 删除缓存
     * @return boolean
     */
    public function delete($key);

    /**
     * 清空缓存
     * @return boolean
     */
    public function flush();

    /**
     * 获取缓存状态
     * @return string
     */
    public function getStats();

    /**
     * 判断缓存是否过期
     */
    public function isExpire($key = null);
}