<?php
/*
Plugin Name: Directorium
Description: Customizable directory listings plugin.
Version: 0.1.1
Author: Barry Hughes
Author URI: http://freshlybakedwebsites.net
License: GPL3 or later
*/


// Pre-flight checks
if (defined('ABSPATH') === false) return;
if (version_compare($GLOBALS['wp_version'], '3.4.2') < 0) wp_die('WP version failure');
if (version_compare(PHP_VERSION, '5.3') < 0) wp_die('PHP version failure');

// Load Directorium
require __DIR__.'/directorium/core.php';