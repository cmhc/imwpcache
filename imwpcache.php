<?php
/*
    Plugin Name: imwpcache
    Plugin URI: http://www.imwpweb.com/tag/imwpcache
    Description: 可能是最快的wordpress缓存插件，缓存驱动支持sqlite，文件，memcache/memcached，redis
    Version: 1.2.1
    Author: imwpweb
    Author URI: http://www.imwpweb.com
*/

define('IMWPCACHE_URL', plugin_dir_url( __FILE__ ));
define('IMWPCACHE_DIR', plugin_dir_path( __FILE__ ));

if (!class_exists('imwpf\modules\Form')) {
    add_action('admin_notices', function(){
        echo "<div class='update-nag'>imwpcache 需要 imwpf环境支持，请安装<a href='http://www.imwpweb.com/400.html' target='_blank'>imwpf插件!</a></div>";
    });
    return false;
}

// 延后激活
add_filter('pre_update_option_active_plugins', function($plugins){
	$currentPlugin = plugin_basename(__FILE__);
	$key = array_search($currentPlugin, $plugins);
	if ($key === false) {
		return $plugins;
	}
	unset($plugins[$key]);
	$plugins[] = $currentPlugin;
	return $plugins;
});

new imwpcache\classes\admin();