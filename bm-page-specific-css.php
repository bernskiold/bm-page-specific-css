<?php
/**
 * Plugin Name:       BM Page Specific CSS
 * Plugin URI:        https://bernskioldmedia.com
 * Description:       Allows editors to add styles specific for pages and post.
 * Version:           1.2.0
 * Author:            Bernskiold Media
 * Author URI:        https://bernskioldmedia.com
 * Requires at least: 7.0
 * Tested up to:      7.1
 * Requires PHP:      8.2
 * Text Domain:       bm-page-specific-css
 * Domain Path:       /languages/
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

use BernskioldMedia\WP\PageSpecificCSS\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloaders
 *
 * The plugin's own classes always load through autoloader.php, so the plugin
 * boots whether or not Composer has run inside it. Its one dependency
 * (matthiasmullie/minify) comes from Composer: from the plugin's own vendor/
 * when it was installed standalone, or from the site's autoloader when the
 * plugin is a Composer package of the site, where the plugin has no vendor/.
 */
require_once __DIR__ . '/autoloader.php';

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( file_exists( WP_CONTENT_DIR . '/vendor/autoload.php' ) ) {
	require_once WP_CONTENT_DIR . '/vendor/autoload.php';
}

/**
 * Basic Constants
 */
define( 'BM_PAGE_SPECIFIC_CSS_FILE_PATH', __FILE__ );

/**
 * Initialize and boot the plugin.
 *
 * @return Plugin
 */
function bm_page_specific_css(): Plugin {
	return Plugin::instance();
}

bm_page_specific_css();
