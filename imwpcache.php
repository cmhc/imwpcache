<?php
/*
    Plugin Name: imwpcache
    Plugin URI: http://www.imwpweb.com/tag/imwpcache
    Description: yet another wordpress cache plugin， support memcache and file cache
    Version: 1.1.0
    Author: imwpweb
    Author URI: http://www.imwpweb.com
*/

define('IMWPCACHE_URL', plugin_dir_url( __FILE__ ));
define('IMWPCACHE_DIR', plugin_dir_path( __FILE__ ));

if (!class_exists('imwpf\modules\Form')) {
    add_action('admin_notices', function(){
        echo "<div class='update-nag'>imwpcache 需要 imwpf环境支持，请安装<a href='http://www.imwpweb.com/400.html' target='_blank'>imwpf插件!</a></div>";
    });
}

require_once IMWPCACHE_DIR .'/classes/admin.php';
new imwpcache\classes\admin();