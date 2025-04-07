<?php
/*
* Plugin Name: PageSpeed Optimizations
* Plugin URI:
* Version: 1.0
* Author: PageSpeed Dev
* Author URI:
* Text Domain: pagespeed-optimizations
* Description: Adds lazy loading, preloading, redis cache and more!
*/

require_once __DIR__ . '/vendor/autoload.php';
define('PSO_PLUGIN_FILE', __FILE__);
define('PSO_PLUGIN_VERSION', '1.0.0');

use PSO\Plugin;

new Plugin();

// In your plugin's main file
