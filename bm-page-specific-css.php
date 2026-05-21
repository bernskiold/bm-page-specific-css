<?php
/**
 * Plugin Name: BM Page Specific CSS
 * Plugin URI:  https://bernskioldmedia.com
 * Description: Allows editors to add styles specific for pages and post.
 * Version:     1.1.3
 * Author:      Bernskiold Media
 * Author URI:  https://bernskioldmedia.com
 * Text Domain: bm-page-specific-css
 * Domain Path: /languages/
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

use BernskioldMedia\WP\PageSpecificCSS\Plugin;

defined( 'ABSPATH' ) || exit;

require_once 'autoloader.php';
require 'vendor/autoload.php';

/**
 * Basic Constants
 */
define( 'BM_PAGE_SPECIFIC_CSS_FILE_PATH', __FILE__ );

/**
 * Initialize and boot the plugin.
 *
 * @return Plugin
 */
function bm_page_specific_css() {
	return Plugin::instance();
}

bm_page_specific_css();
