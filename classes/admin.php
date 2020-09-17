<?php

/**
 * 缓存管理类
 */

namespace imwpcache\classes;

use imwpf\modules\Form;

class admin
{
    private $config;

    private $cache;

    /**
     * 初始化菜单
     */
    public function __construct()
    {
        add_action('init', array($this, 'checkFlush'));
        // 缓存入口失效之后增加提示
        add_action('admin_notices', array($this, 'adminNotice'));

        add_action('admin_menu', array($this, 'addMenu'));
        add_action('admin_enqueue_scripts', array($this, 'loadscript'));
        add_action('save_post_post', array($this, 'updateCache'), 100, 2);

        //插件启用或者停用时候同时更新首页入口
        add_action('activate_imwpcache/imwpcache.php', array($this, 'addCacheToInterface'));
        add_action('deactivate_imwpcache/imwpcache.php', array($this, "removeCacheFromInterface"));
        add_action('wp_footer', array($this, 'addCacheTag'));

        //兼容性
        add_action('the_post', array($this, 'postviewsCompatible'));
        add_action('wp_ajax_nopriv_show_postviews', array($this, 'showPostviews'));
        add_action('wp_ajax_show_postviews', array($this, 'showPostviews'));
    }

    /**
     * 载入cache
     */
    public function loadCacheDriver()
    {
        $dir = dirname(__DIR__);
        if (!file_exists($dir . '/config/cache.php')) {
            return false;
        }
        if (isset($this->cache)) {
            return true;
        }

        $this->config = require_once $dir . '/config/cache.php';
        $driver = 'imwpcache\\drivers\\' . $this->config['type'];
        require_once $dir . '/drivers/driver.php';
        require_once $dir . '/drivers/' . $this->config['type'] . '.php';
        $this->cache = new $driver;

        if (!$this->cache->connect($this->config)) {
            $this->cache = false;
            return false;
        }

        return true;
    }

    /**
     * add menu
     */
    public function addMenu()
    {
        add_menu_page('缓存', '缓存', 'manage_options', 'imwpcacheadmin', array(&$this, 'imwpcacheSettings'));
        add_submenu_page('imwpcacheadmin', '缓存配置', '缓存配置', 'manage_options', 'imwpcacheadmin', array(&$this, 'imwpcacheSettings'));
        add_submenu_page('imwpcacheadmin', '缓存功能', '缓存功能', 'manage_options', 'imwpcachecontrol', array(&$this, 'imwpcacheControl'));
        add_submenu_page('imwpcacheadmin', '缓存统计','缓存统计' , 'manage_options', 'imwp', array(&$this, 'imwpcacheStatus'));
    }

    /**
     * add script
     * @return none
     */
    public function loadscript($hook)
    {
        if ($hook != 'toplevel_page_imwpcacheadmin') {
            return;
        }
    }

    /**
     * 在首页入口加入缓存入口
     * @throws  \Exception 写入异常时候确保插件不被开启
     * @return  void 
     */
    public function addCacheToInterface()
    {
        $content = file_get_contents(ABSPATH . '/index.php');

        if (strpos($content, '/*{imwpcache*/') !== false) {
            return true;
        }

        $content = str_replace("<?php", "<?php /*{imwpcache*/ include('" . IMWPCACHE_DIR . "/bootstrap/index.php'); /*}*/", $content);

        if (!file_put_contents(ABSPATH . '/index.php', $content)) {
            throw new \Exception("写入index.php文件失败，请确保根目录可写", 1);
        }
    }

    /**
     * 在首页入口移除缓存内容
     * @return 
     */
    public function removeCacheFromInterface()
    {
        $content = file_get_contents(ABSPATH . '/index.php');
        $content = preg_replace("|/\*{imwpcache\*/(.*?)/\*}\*/|is", "", $content);
        if (!file_put_contents(ABSPATH . '/index.php', $content)) {
            $line = "/*{imwpcache*/ include('" . IMWPCACHE_DIR . "/bootstrap/index.php'); /*}*/";
            throw new \Exception("恢复index.php文件失败，请将" . ABSPATH . '/index.php' . "文件权限设置为可写", 1);
        }
    }


    /**
     * 刷新缓存
     * @param  $postId
     */
    public function updateCache($postId, $post)
    {
        if ($post->post_status != 'publish') {
            return false;
        }

        if (!$this->loadCacheDriver()) {
            return false;
        }

        //update post cache
        $postUrl = rtrim(get_permalink($postId), "/");
        $homeUrl = rtrim(get_bloginfo("url"), "/");

        $cacheKey = array(
            md5('pc' . $postUrl),
            md5('pc' . $homeUrl),
        );

        // pc和移动端为同一个url
        if ($this->config['has_mobile_page']) {
            $cacheKey[] = md5('m' . $postUrl);
            $cacheKey[] = md5('m' .  $homeUrl);
        }

        // 有ajax载入
        if ($this->config['has_ajax']) {
            $cacheKey[] = md5('pcajax' . $postUrl);
            $cacheKey[] = md5('pcajax' .  $homeUrl);
        }

        if ($this->config['has_mobile_page'] && $this->config['has_ajax']) {
            $cacheKey[] = md5('majax' . $postUrl);
            $cacheKey[] = md5('majax' .  $homeUrl);
        }

        foreach ($cacheKey as $key) {
            $this->cache->delete($key);
        }
        //重新生成缓存
        file_get_contents($homeUrl);
        file_get_contents($postUrl);
    }

    /**
     * 添加管理页面
     */
    public function imwpcacheStatus()
    {
        if (!$this->loadCacheDriver()) {
            echo "<p>请首先<a href=\"?page=imwpcachesettings\">配置缓存驱动</a></p>";
            return false;
        }
        echo "<h2>缓存运行状态</h2>";
        $stats = $this->cache->getStats();
        require_once IMWPCACHE_DIR . '/pages/' . $this->config['type'] . 'stats.php';
    }

    /**
     * 缓存设置页面
     */
    public function imwpcacheSettings()
    {
        if (!empty($_POST)) {
            if ($_POST['type'] == 'memcache' && !class_exists('Memcache')) {
                echo "<p>当前主机未安装Memcache扩展，该选项不可用</p>";
                return false;
            }

            if ($_POST['type'] == 'rediscache' && !class_exists('Redis')) {
                echo "<p>当前主机未安装Redis扩展，该选项不可用</p>";
                return false;
            }

            if ($_POST['type'] == 'memcached' && !class_exists('Memcached')) {
                echo "<p>当前主机未安装Memcached扩展，该选项不可用</p>";
                return false;
            }

            // 保存配置
            try {
                $this->saveConfig($_POST);
            } catch (\Exception $e) {
                echo $e->getMessage();
                return ;
            }
        }

        if (file_exists(IMWPCACHE_DIR . '/config/cache.php')) {
            $config = require IMWPCACHE_DIR . '/config/cache.php';
        } else {
            $config = array();
        }

        $formData = array(
            'method' => 'POST',
            'action' => '',
            'data' => array(
                array(
                    'name' => 'type',
                    'type' => 'select',
                    'label' => '缓存类型',
                    'options' => array(
                        'sqlite' => 'SQLite 缓存',
                        'file' => '文件缓存',
                        'memcache' => 'Memcache 缓存',
                        'redis' => 'Redis 缓存',
                        'memcached' => 'Memcached 缓存',
                    ),
                    'value' => $config['type'],
                    'desc'  => '缓存类型, 文件缓存和DBLite数据库缓存不需要安装任何软件。memcache,redis需要安装以及相应php扩展',
                ),
                array(
                    'name'  => 'host',
                    'type'  => 'text',
                    'label' => '缓存服务器',
                    'value' => $config['host'],
                    'desc'  => '缓存服务器。只有memcache和redis才需要设置。如果安装在本地，填写127.0.0.1',
                    'is_hide' => true,
                    'show'  => array(
                        'key' => 'type',
                        'value' => array('memcache', 'redis', 'memcached'),
                    ),
                ),
                array(
                    'name' => 'port',
                    'type' => 'text',
                    'label' => '缓存端口',
                    'value' => $config['port'],
                    'desc' => '填写memcache（默认11211）或者redis（默认6379）的服务端口号',
                    'is_hide'  => true,
                    'show'  => array(
                        'key' => 'type',
                        'value' => array('memcache', 'redis', 'memcached'),
                    ),
                ),
                array(
                    'name' => 'expires',
                    'type' => 'select',
                    'label' => '缓存有效期',
                    'options' => array(
                        '60' => '一分钟',
                        '3600' => '一小时',
                        '86400' => '一天',
                        '604800' => '一周',
                        '2592000' => '一月',
                        '0' => '不过期',
                    ),
                    'value' => $config['expires'],
                    'desc' => '站点如果更新不频繁，建议设着较长的过期时间',
                ),
                array(
                    'name' => 'has_mobile_page',
                    'type' => 'select',
                    'label' => '移动页面',
                    'options' => array(
                        '0' => '无，或响应式页面',
                        '1' => '有，和PC域名一致',
                        '2' => '有，和PC域名不一致',
                    ),
                    'value' => $config['has_mobile_page'],
                    'desc'  => '有，和pc域名一致将会使用两份缓存；其他情况将会使用一份缓存'
                ),
                array(
                    'name' => 'has_ajax',
                    'type' => 'select',
                    'label' => 'AJAX版本',
                    'options' => array(
                        '0' => '无',
                        '1' => '有',
                    ),
                    'value' => $config['has_ajax'],
                    'desc'  => '有ajax版本将会为ajax版本单独建立一份缓存',
                ),
            ),
        );
        $form = new Form();
        echo $form->get($formData);
    }

    /**
     * 缓存控制页面
     */
    public function imwpcacheControl()
    {
        if (!$this->loadCacheDriver()) {
            echo "缓存载入失败";
            return;
        }
        if (isset($_POST['clearcache']) && $_POST['clearcache'] == '1') {
            if ($this->cache->flush()) {
                echo "缓存已经全部清空";
            }
        }

        //check cache
        $pageurl = '';
        if (isset($_POST['pageurl'])) {
            $pageurl = $_POST['pageurl'];
            $key = md5($_POST['pageurl']);
            $content = $this->cache->get($key);
        }
        //delete cache
        if (isset($_POST['delcache'])) {
            $pageurl = $_POST['delcache'];
            $key = md5($_POST['delcache']);
            if ($this->cache->delete($key)) {
                $delstatus = true;
            }
        }
        require_once IMWPCACHE_DIR . '/pages/control.php';
    }

    /**
     * 在页脚加入缓存标志
     */
    public function addCacheTag()
    {
        if (is_404()) {
            return false;
        }
        echo '<!--statusok-->';
    }

    /**
     * 兼容wp-postview
     */
    public function postviewsCompatible($post)
    {
        //postview 插件兼容
        if (function_exists('the_views')) {
            $data = "jQuery(document).ready(function($) {
                $.get(viewsCacheL10n.admin_ajax_url + '?action=show_postviews&id=' + viewsCacheL10n.post_id, function(data) {
                    $('.ajax-views').html(data);
                });
            });";
            wp_add_inline_script('wp-postviews-cache', $data, 'after');
        }
    }

    /**
     * 显示文章数
     */
    public function showPostviews()
    {
        if (!function_exists('the_views')) {
            return;
        }

        $viewsOptions = get_option('views_options');
        $id = intval($_GET["id"]);
        $view = get_post_meta($id, 'views', true);
        if ($view < 1) {
            echo 0;
        } else {
            echo $view;
        }
        die();
    }

    /**
     * 刷新所有缓存
     */
    public function checkFlush()
    {
        if (!isset($_GET['imwpcache']) || $_GET['imwpcache'] != 'createall') {
            return false;
        }
        if (isset($_GET['page'])) {
            $page = (int) $_GET['page'];
            add_option('imwpcache_page', $page);
        } else {
            $page = get_option('imwpcache_page');
            if (!$page) {
                $page = 1;
                add_option('imwpcache_page', 1);
            }
        }

        $query = new \WP_Query(array('posts_per_page' => 10, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC'));
        while ($query->have_posts()) {
            $query->the_post();
            $link = get_permalink();
            echo $link . "<br/>";
            file_get_contents($link);
        }
        update_option('imwpcache_page', $page + 1);
        if (isset($link)) {
            echo '<meta http-equiv="refresh" content="1">';
        }
        die();
    }

    /**
     * admin 提示
     * @return string
     */
    public function adminNotice()
    {
        $content = file_get_contents(ABSPATH . '/index.php');

        if (strpos($content, '/*{imwpcache*/') !== false) {
            return true;
        }

        echo '<div class="update-nag notice notice-warning inline">由于wp自动更新或者其他原因导致入口文件被覆盖，请<a href="admin.php?page=imwpcacheadmin">保存一次缓存配置</a>来手动更新入口</div>';
    }

    /**
     * 保存配置
     * @param array $array
    */
    protected function saveConfig($array)
    {
        $config = $this->parseConfig($array);
        if (!file_put_contents(IMWPCACHE_DIR . '/config/cache.php', $config)) {
            throw new \Exception("缓存配置写入失败，请检查插件目录权限");
        }

        // 添加缓存文件到入口
        $this->addCacheToInterface();

        // 创建表结构
        if ($array['type'] == 'sqlite') {
            $this->loadCacheDriver();
            $this->cache->createTable();
        }
        return ;
    }

    /**
     * 写入配置内容
     * @param array $array 
     * @return string  <?php return array(...)
     */
    protected function parseConfig($array)
    {
        $str = '<?php return array(';
        foreach ($array as $k => $v) {
            if (is_string($k)) {
                $str .= "'{$k}'=>'{$v}',";
            } else {
                $str .= "'{$k}'=>{$v},";
            }
        }
        $str = rtrim($str, ',');
        $str .= ');';
        return $str;
    }
}
