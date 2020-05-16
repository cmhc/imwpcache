<?php
/**
 * 缓存读写类
 */
namespace imwpcache\classes;

class imwpcache
{
    /**
     * 缓存对象
     */
    private $Cache;

    /**
     * 缓存配置
     */
    private $config;

    /**
     * 缓存 key
     */
    private $key;

    public function __construct()
    {
        if ($this->disableCache()) {
            return false;
        }

        $protocol = ($_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://';

        if ($this->isMobile()) {
            $prefix = 'm';
        } else {
            $prefix = 'pc';
        }

        //兼容ajax请求
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == 'xmlhttprequest') {
            $prefix .= 'ajax';
        }
        $this->loadCacheDriver();

        $this->key = md5(rtrim($prefix . $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING'] , "/"));
        $result = $this->Cache->get($this->key);
        if (!empty($result)) {
            echo $result;
            if (!$this->Cache->isExpire($this->key)) {
                die();
            } else {
                file_put_contents(dirname(__DIR__) . '/lock', 1);
                fastcgi_finish_request();
            }
        }
        $this->run();
    }

    /**
     * 自动写缓存
     */
    public function run()
    {
        ob_start(array(&$this, 'writeCache'));
    }

    /**
     * 脚本结束后写入缓存
     * @param  string $content
     * @return boolean false
     */
    public function writeCache($content)
    {
        // 防止没有完整加载的页面被缓存
        if (strpos($content, '<!--statusok-->') === false ) {
            return false;
        }
        // 手动不缓存标记
        if (strpos($content, '<!--disable_cache-->') !== false) {
            return false;
        }
        $content .= '<!--cached by imwpcache ' . date("Y-m-d H:i:s") . '-->';
        $this->Cache->set($this->key, $content, $this->config['expires']);
        return false;
    }

    /**
     * 用户登陆的情况下不使用缓存
     * @return boolean
     */
    public static function isLogin()
    {
        foreach ($_COOKIE as $k=>$v) {
            if (strpos($k, 'wordpress_logged_in') !== false ) {
                return true;
            }
        }
        return false;
    }

    /**
     * 表示是否能被缓存
     * @return boolean
     */
    public function disableCache()
    {
        // 登陆状态不缓存
        if ($this->isLogin()) {
            return true;
        }

        // POST 结果不缓存
        if (!empty($_POST)) {
            return true;
        }

        return false;
    }

    /**
     * 载入选定的缓存驱动
     */
    protected function loadCacheDriver()
    {
        $dir = dirname(__DIR__);
        if (!file_exists($dir . '/config/cache.php')) {
            return false;
        }
        if (isset($this->Cache)) {
            return true;
        }

        $this->config = require_once $dir . '/config/cache.php';
        $driver = 'imwpcache\\drivers\\' . $this->config['type'];
        require_once $dir . '/drivers/driver.php';
        require_once $dir . '/drivers/' . $this->config['type'] . '.php';
        $this->Cache = new $driver;
        $this->Cache->connect($this->config);
        return true;
    }

    /**
     * 判断是否是手机请求
     * @return boolean
     */
    protected function isMobile()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            $isMobile = false;
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false // many mobile devices (all iPhone, iPad, etc.)
            || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
            || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
            || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
            || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
            || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
            || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false ) {
                $isMobile = true;
        } else {
            $isMobile = false;
        }
        return $isMobile;
    }

}