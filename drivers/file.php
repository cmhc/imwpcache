<?php
/**
 * 利用文件实现的缓存服务
 */
class imwpFile implements imwpcacheDriver
{
    protected $cacheDir;

    protected $isExpire = false;

    /**
     * 初始化缓存文件夹
     */
    public function __construct()
    {
        $this->cacheDir = dirname(dirname(dirname(__DIR__))) . '/cache';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir);
        }
    }

    public function connect($config)
    {

    }

    /**
     * 写入缓存
     * @param string $key    缓存key
     * @param string $value  缓存内容
     * @param int $expire 有效期
     */
    public function set($key, $value, $expire)
    {
        $path = $this->getCachePath($key);
        $expire = time() + $expire;
        $content = gzencode("{$expire}{$value}", 9);
        $fp = fopen($path, 'w');
        fwrite($fp, $content);
        fclose($fp);
        @unlink(dirname(__DIR__) . '/lock');
        $this->setStats(false, strlen($content));
    }

    /**
     * 获取缓存内容
     */
    public function get($key)
    {
        $path = $this->getCachePath($key);
        if (!file_exists($path)) {
            return false;
        }
        $content = gzdecode(file_get_contents($path));
        $expire = substr($content, 0, 10);
        if (time() > $expire && !file_exists(dirname(__DIR__) . '/lock')) {
            $this->isExpire = true;
        }
        $content = substr($content, 10);
        $this->setStats(true);
        return $content;
    }

    /**
     * 删除缓存内容
     * @param  string $key
     * @return
     */
    public function delete($key)
    {
        $path = $this->getCachePath($key);
        if (!file_exists($path)) {
            return true;
        }
        @unlink($path);
    }

    /**
     * 删除所有的缓存文件
     * @param  string $dir
     * @return 
     */
    public function flush($dir = '')
    {
        if ($dir == '') {
            $dir = $this->cacheDir;
        }
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) { 
            (is_dir("$dir/$file")) ? $this->flush("$dir/$file") : unlink("$dir/$file"); 
        }
        return rmdir($dir);
    }


    /**
     * 获取缓存状态
     * @return
     */
    public function getStats()
    {
        if (file_exists($this->cacheDir . '/stats')) {
            $stats = json_decode(file_get_contents($this->cacheDir . '/stats'), true);
        } else {
            $stats = array(
                'count' => 0,
                'size' => 0,
                'hits' => 0,
                'misses' => 0,
                'get' => 0,
                'set' => 0,
            );
        }
        return $stats;  
    }

    /**
     * always trhe
     */
    public function isExpire($key = null)
    {
        if ($key) {
            $this->get($key);
        }
        return $this->isExpire;
    }


    /**
     * 写入统计信息
     * @param boolean $hits 是否命中
     * @param int $size 页面大小
     * 
     */
    protected function setStats($isHit=true, $size=0)
    {
        if (file_exists($this->cacheDir . '/stats')) {
            $stats = json_decode(file_get_contents($this->cacheDir . '/stats'), true);
        } else {
            $stats = array(
                'count' => 0,
                'size' => 0,
                'hits' => 0,
                'misses' => 0,
                'get' => 0,
                'set' => 0,
            );
        }

        if ($hits) {
            //命中
            $stats['hits'] += 1;
            $stats['get'] += 1;
        } else {
            //不命中
            $stats['misses'] += 1;
            $stats['get'] += 1;
            $stats['set'] += 1;
            $stats['count'] += 1;
            $stats['size'] += $size;
        }

        file_put_contents($this->cacheDir . '/stats', json_encode($stats));
    }

    /**
     * 获取缓存路径
     * @param  $key
     * @return string
     */
    protected function getCachePath($key)
    {
        $hash = md5($key);
        $s1 = substr($hash, 0, 2);
        $s2 = substr($hash, 2, 2);
        $cacheDir = sprintf('%s/%s/%s', $this->cacheDir, $s1, $s2);
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }
        return $cacheDir . '/' . $hash . '.cac';
    }

}