<?php

/**
 * 利用文件实现的缓存服务
 */

namespace imwpcache\drivers;

class sqlite implements driver
{
    protected $cacheDir;

    protected $isExpire = false;

    protected $pdo = null;

    /**
     * 初始化缓存文件夹
     */
    public function __construct()
    {
        $this->cacheDir = dirname(dirname(dirname(__DIR__))) . '/cache';
        if (file_exists($this->cacheDir)) {
            return;
        }
        if (!mkdir($this->cacheDir, 0775)) {
            echo "创建缓存文件夹失败，请设置" . $this->cacheDir . "目录为775权限";
        }
    }

    /**
     * 连接数据库
     */
    public function connect($config)
    {
        try {
            $this->pdo = new \PDO("sqlite:" . $this->cacheDir . '/cache.db');
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * 写入缓存
     * @param string $key    缓存key
     * @param string $value  缓存内容
     * @param int $expire 有效期
     */
    public function set($key, $value, $expire)
    {
        if ($expire != 0) {
            $expire = time() + $expire;
        }

        if (function_exists("gzencode")) {
            $value = gzencode($value, 9);
        }

        $sth = $this->pdo->prepare("SELECT k FROM content where k=:key");
        $sth->execute(array('key' => $key));
        $k = $sth->fetch();
        $sth->closeCursor();
        if (!empty($k)) {
            $h = $this->pdo->prepare("UPDATE content SET v=:value, ex=:expire WHERE k=:key");
        } else {
            $h = $this->pdo->prepare("INSERT INTO content(k,v,ex) VALUES(:key, :value, :expire)");
        }

        $h->execute(array(
            ":key" => $key,
            ":value" => $value,
            ":expire" => $expire,
        ));

        $this->setStats(false, strlen($value));
    }

    /**
     * 获取缓存内容
     */
    public function get($key)
    {
        $h = $this->pdo->prepare("SELECT v,ex FROM content WHERE k=:key");
        $h->execute(array(
            ":key" => $key,
        ));
        $result = $h->fetch();
        $h->closeCursor();
        if (!empty($result)) {
            $this->setStats(true);
            
            if ($result['ex'] != 0 && time() > $result['ex']) {
                $this->isExpire = true;
            }

            if (function_exists("gzencode")) {
                return gzdecode($result['v']);
            } else {
                return $result['v'];
            }
        }
        return false;
    }

    /**
     * 删除缓存内容
     * @param  string $key
     * @return
     */
    public function delete($key)
    {
        $h = $this->pdo->prepare("DELETE FROM content WHERE k=:key");
        $h->execute(array('key' => $key));
        return true;
    }

    /**
     * 删除所有的缓存文件
     * @param  string $dir
     * @return 
     */
    public function flush()
    {
        @unlink($this->cacheDir . '/cache.db');
    }

    /**
     * 获取缓存状态
     * @return
     */
    public function getStats()
    {
        if (file_exists($this->cacheDir . '/sqlite_stats')) {
            $stats = json_decode(file_get_contents($this->cacheDir . '/sqlite_stats'), true);
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
     * 判断是否过期
     */
    public function isExpire($key = null)
    {
        return $this->isExpire;
    }

    /**
     *  创建缓存表
     */
    public function createTable()
    {
        $createTable = "CREATE TABLE content(
            k char(32) PRIMARY KEY NOT NULL,
            v text NOT NULL,
            ex int NOT NULL
        )";
        $addIndex = "CREATE INDEX ex ON content(ex)";
        $this->pdo->query($createTable);
        // print_r($this->pdo->errorInfo());
        $this->pdo->query($addIndex);
        // print_r($this->pdo->errorInfo());
        echo "缓存表生成成功";
    }

    /**
     * 写入统计信息
     * @param boolean $hits 是否命中
     * @param int $size 页面大小
     * 
     */
    protected function setStats($isHit = true, $size = 0)
    {
        if (file_exists($this->cacheDir . '/sqlite_stats')) {
            $stats = json_decode(file_get_contents($this->cacheDir . '/sqlite_stats'), true);
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

        if ($isHit) {
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

        file_put_contents($this->cacheDir . '/sqlite_stats', json_encode($stats));
    }
}
