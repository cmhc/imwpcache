<?php
/*
    Plugin Name: imwpcache
    Plugin URI: http://www.imwpweb.com/tag/imwpcache
    Description: yet another wordpress cache plugin， support memcache and file cache
    Version: 1.0.0
    Author: imwpweb
    Author URI: http://www.imwpweb.com
*/

define('IMWPCACHE_URL', plugin_dir_url( __FILE__ ));
define('IMWPCACHE_DIR', plugin_dir_path( __FILE__ ));
require(IMWPCACHE_DIR .'/classes/imwpcacheadmin.php');